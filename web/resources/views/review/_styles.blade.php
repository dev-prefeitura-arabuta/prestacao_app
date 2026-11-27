<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background-color: #f8fafc;
        color: #1e293b;
        min-height: 100vh;
    }
    .app-layout { display: flex; min-height: 100vh; width: 100%; }
    .main-wrapper { flex: 1; display: flex; flex-direction: column; min-width: 0; width: 100%; }
    .container { width: 100%; max-width: 100%; padding: 1.75rem 2rem; }
    .page-header { margin-bottom: 1.5rem; }
    .page-header h1 { font-size: 1.5rem; color: #0f172a; }
    .page-header p { color: #64748b; font-size: 0.9rem; margin-top: 0.25rem; }
    .card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        width: 100%;
        overflow: hidden;
        margin-bottom: 1rem;
    }
    .table-responsive { width: 100%; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    thead th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid #e2e8f0;
    }
    tbody td {
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
        color: #334155;
        vertical-align: top;
    }
    tbody tr:hover { background-color: #f8fafc; }
    .empty-state { padding: 2.5rem; text-align: center; color: #64748b; }
    .link { color: #dd4814; font-weight: 600; text-decoration: none; }
    .muted { color: #64748b; font-size: 0.8rem; }
    .pill {
        display: inline-block;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .pill-pendente { background: #f1f5f9; color: #475569; }
    .pill-rejeitado { background: #fee2e2; color: #b91c1c; }
    .pill-prazo_vencido { background: #ffedd5; color: #c2410c; }
    .pill-paused { background: #e0e7ff; color: #3730a3; }
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 700;
        text-decoration: none;
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-primary { background: #dd4814; color: #ffffff; }
    .btn-wa { background: #16a34a; color: #ffffff; }
    .btn-disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }
    .person-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .person-head h2 { font-size: 1rem; color: #0f172a; }
    @media (max-width: 768px) {
        .app-layout { flex-direction: column; }
        .container { padding: 1.25rem 1rem; }
    }
</style>
