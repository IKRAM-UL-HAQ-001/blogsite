<style>
    .source-shell {
        max-width: 1500px;
        margin: 0 auto;
    }

    .source-panel {
        background: #111622;
        border: 1px solid #1d2436;
        border-radius: 8px;
        color: #f8fafc;
        padding: 1.25rem;
    }

    .source-table {
        --bs-table-bg: #111622;
        --bs-table-striped-bg: #0f172a;
        --bs-table-hover-bg: #151c2b;
        border-color: #1d2436;
    }

    .source-table th {
        color: #94a3b8;
        font-size: 0.75rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .type-pill,
    .status-pill {
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1;
        padding: 0.36rem 0.55rem;
        white-space: nowrap;
    }

    .type-pill {
        background: rgba(37, 99, 235, 0.14);
        color: #93c5fd;
        border: 1px solid rgba(37, 99, 235, 0.28);
    }

    .status-completed {
        background: rgba(16, 185, 129, 0.13);
        color: #10b981;
    }

    .status-failed {
        background: rgba(239, 68, 68, 0.14);
        color: #ef4444;
    }

    .status-partial {
        background: rgba(245, 158, 11, 0.14);
        color: #f59e0b;
    }

    .status-running {
        background: rgba(37, 99, 235, 0.14);
        color: #93c5fd;
    }

    .stat-card {
        background: #111622;
        border: 1px solid #1d2436;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
    }

    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #f8fafc;
    }

    .stat-card .stat-label {
        font-size: 0.75rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .empty-state {
        color: #94a3b8;
        background: #0a0d14;
        border: 1px dashed #1d2436;
        border-radius: 8px;
        padding: 1.25rem;
        text-align: center;
    }

    .source-url {
        color: #93c5fd;
        display: inline-block;
        max-width: 320px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        text-decoration: none;
        vertical-align: middle;
    }

    .source-url:hover {
        color: #bfdbfe;
        text-decoration: underline;
    }

    .trigger-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 0.75rem;
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
</style>
