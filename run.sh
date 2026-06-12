#!/usr/bin/env bash
set -e

# ─────────────────────────────────────────────────────────────────────────────
# run.sh — development launcher for the blogsite market-intelligence platform
#
# Usage:
#   ./run.sh          — first-time setup + start dev server
#   ./run.sh setup    — install/migrate only (no server start)
#   ./run.sh start    — start dev server (assumes setup already done)
#   ./run.sh worker   — start queue worker only
#   ./run.sh migrate  — run pending migrations
#   ./run.sh seed     — seed news sources, market assets, indicators
#   ./run.sh fresh    — drop all tables, re-migrate, re-seed (destructive!)
# ─────────────────────────────────────────────────────────────────────────────

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

info()    { echo -e "${GREEN}[INFO]${NC}  $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

# ─────────────────────────────────────────────────────────────────────────────
# Prerequisites check
# ─────────────────────────────────────────────────────────────────────────────
check_deps() {
    command -v php  >/dev/null 2>&1 || error "PHP is not installed."
    command -v composer >/dev/null 2>&1 || error "Composer is not installed."
    command -v npm  >/dev/null 2>&1 || error "Node/npm is not installed."

    PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
    info "PHP $PHP_VERSION  |  composer $(composer --version --no-ansi 2>/dev/null | awk '{print $3}')  |  npm $(npm --version)"
}

# ─────────────────────────────────────────────────────────────────────────────
# Setup
# ─────────────────────────────────────────────────────────────────────────────
setup() {
    info "Installing PHP dependencies..."
    composer install --no-interaction --prefer-dist

    if [ ! -f ".env" ]; then
        info "Creating .env from .env.example..."
        cp .env.example .env
        php artisan key:generate --ansi
    else
        info ".env already exists — skipping key generation."
    fi

    # Ensure the SQLite database file exists
    if [ ! -f "database/database.sqlite" ]; then
        info "Creating SQLite database file..."
        touch database/database.sqlite
    fi

    info "Running database migrations..."
    php artisan migrate --force --ansi

    info "Installing Node dependencies..."
    npm install

    info "Building frontend assets..."
    npm run build

    info "Setup complete."
}

# ─────────────────────────────────────────────────────────────────────────────
# Seed reference data
# ─────────────────────────────────────────────────────────────────────────────
seed() {
    info "Seeding market assets..."
    php artisan tinker --execute="App\Models\MarketAsset::seedDefaults(); echo 'Market assets seeded.' . PHP_EOL;"

    info "Seeding economic indicators..."
    php artisan tinker --execute="App\Models\EconomicIndicator::seedDefaults(); echo 'Economic indicators seeded.' . PHP_EOL;" 2>/dev/null || warn "EconomicIndicator::seedDefaults() not available yet — skip."

    info "Seeding geopolitical event types..."
    php artisan tinker --execute="App\Models\GeopoliticalEventType::seedDefaults(); echo 'Geopolitical types seeded.' . PHP_EOL;" 2>/dev/null || warn "GeopoliticalEventType::seedDefaults() not available yet — skip."
}

# ─────────────────────────────────────────────────────────────────────────────
# Start full dev environment
# (server + queue worker + Vite + log tail in parallel)
# ─────────────────────────────────────────────────────────────────────────────
start() {
    info "Starting development environment..."
    info "  → Web server :  http://127.0.0.1:8000"
    info "  → Queue      :  database driver (ingestion / analysis / generation / market-data / maintenance / critical)"
    info "  → Vite       :  HMR for frontend assets"
    info "  → Logs       :  real-time via pail"
    echo ""

    npx concurrently \
        --names   "server,queue,vite,logs" \
        --prefix  "[{name}]" \
        -c        "#93c5fd,#c4b5fd,#fb7185,#fdba74" \
        --kill-others \
        "php artisan serve" \
        "php artisan queue:listen --tries=3 --timeout=300 --sleep=3 --queue=critical,ingestion,market-data,analysis,generation,maintenance,notifications" \
        "npm run dev" \
        "php artisan pail --timeout=0"
}

# ─────────────────────────────────────────────────────────────────────────────
# Queue worker only
# ─────────────────────────────────────────────────────────────────────────────
worker() {
    info "Starting queue worker (all queues)..."
    php artisan queue:listen \
        --tries=3 \
        --timeout=300 \
        --sleep=3 \
        --queue=critical,ingestion,market-data,analysis,generation,maintenance,notifications
}

# ─────────────────────────────────────────────────────────────────────────────
# Fresh install (destructive)
# ─────────────────────────────────────────────────────────────────────────────
fresh() {
    warn "This will DROP all tables and re-run all migrations."
    read -rp "Are you sure? [y/N] " confirm
    if [[ "$confirm" =~ ^[Yy]$ ]]; then
        php artisan migrate:fresh --force --ansi
        seed
        info "Fresh migration complete."
    else
        info "Aborted."
    fi
}

# ─────────────────────────────────────────────────────────────────────────────
# Entry point
# ─────────────────────────────────────────────────────────────────────────────
check_deps

COMMAND="${1:-}"

case "$COMMAND" in
    setup)
        setup
        ;;
    start)
        start
        ;;
    worker)
        worker
        ;;
    migrate)
        info "Running pending migrations..."
        php artisan migrate --force --ansi
        ;;
    seed)
        seed
        ;;
    fresh)
        fresh
        ;;
    "")
        # Default: setup if not done yet, then start
        if [ ! -d "vendor" ] || [ ! -f ".env" ] || [ ! -f "database/database.sqlite" ]; then
            setup
        fi
        start
        ;;
    *)
        echo "Usage: $0 [setup|start|worker|migrate|seed|fresh]"
        exit 1
        ;;
esac
