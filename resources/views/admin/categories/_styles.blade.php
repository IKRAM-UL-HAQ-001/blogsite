<style>
    .category-shell {
        max-width: 1500px;
        margin: 0 auto;
    }

    .category-panel,
    .category-form-card {
        background: #111622;
        border: 1px solid #1d2436;
        border-radius: 8px;
        color: #f8fafc;
        padding: 1.25rem;
    }

    .category-table {
        --bs-table-bg: #111622;
        --bs-table-striped-bg: #0f172a;
        --bs-table-hover-bg: #151c2b;
        border-color: #1d2436;
    }

    .category-table th {
        color: #94a3b8;
        font-size: 0.75rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .category-pill,
    .count-pill {
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1;
        padding: 0.36rem 0.55rem;
        white-space: nowrap;
    }

    .category-pill {
        background: rgba(37, 99, 235, 0.14);
        border: 1px solid rgba(37, 99, 235, 0.28);
        color: #93c5fd;
    }

    .count-pill {
        background: rgba(16, 185, 129, 0.13);
        color: #10b981;
    }

    .muted-pill {
        background: rgba(100, 116, 139, 0.14);
        color: #94a3b8;
    }

    .empty-state {
        color: #94a3b8;
        background: #0a0d14;
        border: 1px dashed #1d2436;
        border-radius: 8px;
        padding: 1.25rem;
        text-align: center;
    }

    .detail-list,
    .activity-list {
        display: grid;
        gap: 0.75rem;
    }

    .detail-row,
    .activity-item {
        background: #0a0d14;
        border: 1px solid #1d2436;
        border-radius: 8px;
        padding: 0.9rem 1rem;
    }

    .detail-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .detail-row span {
        color: #94a3b8;
    }

    .pagination {
        --bs-pagination-bg: #111622;
        --bs-pagination-border-color: #1d2436;
        --bs-pagination-color: #93c5fd;
        --bs-pagination-hover-bg: #151c2b;
        --bs-pagination-hover-border-color: #2563eb;
        --bs-pagination-active-bg: #2563eb;
        --bs-pagination-active-border-color: #2563eb;
        --bs-pagination-disabled-bg: #0a0d14;
        --bs-pagination-disabled-border-color: #1d2436;
        --bs-pagination-disabled-color: #64748b;
    }

    @media (max-width: 767.98px) {
        .detail-row {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
