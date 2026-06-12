from __future__ import annotations

import argparse
import json
import logging
import re
from dataclasses import asdict, dataclass
from pathlib import Path
from typing import Iterable

import pandas as pd
from playwright.sync_api import (
    Browser,
    Locator,
    Page,
    TimeoutError as PlaywrightTimeoutError,
    sync_playwright,
)

BASE_URL = "https://www.forexfactory.com/calendar"

logging.basicConfig(
    level=logging.DEBUG,
    format="%(asctime)s | %(levelname)s | %(message)s",
)
logger = logging.getLogger(__name__)


@dataclass
class CalendarEvent:
    date: str = ""
    time: str = ""
    currency: str = ""
    impact: str = ""
    event: str = ""
    actual: str = ""
    forecast: str = ""
    previous: str = ""
    detail_url: str = ""


def clean_text(value: str | None) -> str:
    if not value:
        return ""
    return re.sub(r"\s+", " ", value).strip()


def first_text(row: Locator, selectors: Iterable[str]) -> str:
    for selector in selectors:
        element = row.locator(selector).first
        try:
            if element.count() > 0:
                value = clean_text(element.inner_text(timeout=1_500))
                if value:
                    return value
        except PlaywrightTimeoutError:
            continue
    return ""


def detect_impact(row: Locator) -> str:
    span = row.locator("td.calendar__impact span[title]").first
    try:
        if span.count() > 0:
            title = (span.get_attribute("title") or "").lower()
            if "high" in title:
                return "High"
            if "medium" in title:
                return "Medium"
            if "low" in title:
                return "Low"
            if "holiday" in title:
                return "Holiday"
            if "non-economic" in title:
                return "Non-Economic"
    except PlaywrightTimeoutError:
        pass
    return ""


def extract_detail_url(row: Locator) -> str:
    event_id = row.get_attribute("data-event-id") or ""
    if event_id:
        return f"https://www.forexfactory.com/calendar#detail={event_id}"
    return ""


def accept_cookie_banner(page: Page) -> None:
    possible_buttons = [
        "button:has-text('Accept')",
        "button:has-text('Agree')",
        "button:has-text('Allow all')",
        "[aria-label*='Accept']",
    ]
    for selector in possible_buttons:
        try:
            button = page.locator(selector).first
            if button.count() > 0 and button.is_visible():
                button.click(timeout=2_000)
                logger.info("Cookie banner accepted.")
                return
        except PlaywrightTimeoutError:
            continue


def calendar_url(period: str) -> str:
    period = period.strip().lower()
    if period in {"today", "tomorrow", "yesterday"}:
        return f"{BASE_URL}?day={period}"
    if period in {"this", "next", "last"}:
        return f"{BASE_URL}?week={period}"
    if re.fullmatch(r"[a-z]{3}\d{1,2}\.\d{4}", period):
        return f"{BASE_URL}?day={period}"
    raise ValueError(
        "Invalid period. Use today, tomorrow, yesterday, this, next, last, "
        "or a date such as jun15.2026."
    )


def find_calendar_rows(page: Page) -> Locator:
    selectors = [
        "tr.calendar__row",
        ".calendar__row",
        "[data-event-id]",
        "[class*='calendar'][class*='row']",
    ]
    for selector in selectors:
        rows = page.locator(selector)
        if rows.count() > 0:
            logger.info("Calendar rows found with selector: %s", selector)
            return rows
    raise RuntimeError(
        "No calendar event rows were found. Forex Factory may have changed "
        "its page structure or blocked the request."
    )


def scrape_calendar(
    period: str = "today",
    headless: bool = True,
    timeout_ms: int = 30_000,
) -> list[CalendarEvent]:
    url = calendar_url(period)
    events: list[CalendarEvent] = []

    with sync_playwright() as playwright:
        browser: Browser = playwright.chromium.launch(headless=headless)
        context = browser.new_context(
            viewport={"width": 1440, "height": 1000},
            locale="en-US",
            timezone_id="UTC",
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/124.0 Safari/537.36"
            ),
        )
        page = context.new_page()
        page.set_default_timeout(timeout_ms)

        logger.info("Opening %s", url)
        page.goto(url, wait_until="domcontentloaded")
        accept_cookie_banner(page)

        try:
            page.wait_for_load_state("networkidle", timeout=10_000)
        except PlaywrightTimeoutError:
            logger.warning("Network did not become idle; continuing.")

        rows = find_calendar_rows(page)
        logger.info("Processing %d candidate rows.", rows.count())

        for index in range(rows.count()):
            row = rows.nth(index)
            logger.debug(
                "ROW %d | class=%r | text=%r",
                index,
                row.get_attribute("class"),
                clean_text(row.inner_text()),
            )

        current_date = ""
        current_time = ""

        for index in range(rows.count()):
            row = rows.nth(index)

            row_classes = row.get_attribute("class") or ""

            date_text = first_text(row, ["td.calendar__date"])
            time_text = first_text(row, ["td.calendar__time"])

            if date_text:
                current_date = date_text
            if time_text:
                current_time = time_text

            event_name = first_text(row, ["td.calendar__event"])
            currency = first_text(row, ["td.calendar__currency"])

            if not event_name:
                logger.debug(
                    "Skipping non-event row %d, classes=%s",
                    index,
                    row_classes,
                )
                continue

            event = CalendarEvent(
                date=current_date,
                time=current_time,
                currency=currency,
                impact=detect_impact(row),
                event=event_name,
                actual=first_text(row, ["td.calendar__actual"]),
                forecast=first_text(row, ["td.calendar__forecast"]),
                previous=first_text(row, ["td.calendar__previous"]),
                detail_url=extract_detail_url(row),
            )
            events.append(event)

        browser.close()

    logger.info("Extracted %d calendar events.", len(events))
    return events


def save_events(
    events: list[CalendarEvent],
    csv_path: str = "forex_calendar.csv",
    json_path: str = "forex_calendar.json",
) -> None:
    records = [asdict(event) for event in events]

    Path(csv_path).parent.mkdir(parents=True, exist_ok=True)
    Path(json_path).parent.mkdir(parents=True, exist_ok=True)

    pd.DataFrame(records).to_csv(csv_path, index=False, encoding="utf-8")

    with open(json_path, "w", encoding="utf-8") as file:
        json.dump(records, file, indent=2, ensure_ascii=False)

    logger.info("CSV written to %s", csv_path)
    logger.info("JSON written to %s", json_path)


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Scrape the Forex Factory economic calendar."
    )
    parser.add_argument(
        "--period",
        default="today",
        help="today, tomorrow, yesterday, this, next, last, or a date such as jun15.2026",
    )
    parser.add_argument(
        "--show-browser",
        action="store_true",
        help="Run Chromium visibly for debugging.",
    )
    parser.add_argument("--csv", default="forex_calendar.csv", help="CSV output path.")
    parser.add_argument("--json", default="forex_calendar.json", help="JSON output path.")
    args = parser.parse_args()

    events = scrape_calendar(period=args.period, headless=not args.show_browser)

    if not events:
        logger.warning(
            "No economic events are listed for '%s'. "
            "This can be normal on weekends or quiet calendar days.",
            args.period,
        )
        save_events([], csv_path=args.csv, json_path=args.json)
        print("No calendar events found.")
        return

    save_events(events, csv_path=args.csv, json_path=args.json)

    print(f"\nExtracted {len(events)} events:")
    for event in events[:5]:
        print(asdict(event))


if __name__ == "__main__":
    main()
