<?php
$currentUri = uri_string();
$role       = $userRole ?? session()->get('user_role') ?? session()->get('role') ?? 'user';
$isAdmin    = ($role === 'admin');
?>
<style>
    .admin-sidebar {
        width: 265px;
        background-color: #0f172a;
        color: #e2e8f0;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        border-right: 1px solid #1e293b;
        position: sticky;
        top: 0;
        height: 100vh;
        transition: width 0.22s ease;
        z-index: 50;
    }
    .admin-sidebar.collapsed {
        width: 76px;
    }
    .sidebar-header {
        padding: 1.25rem 1.15rem;
        border-bottom: 1px solid #1e293b;
        display: flex;
        align-items: center;
    }
    .admin-sidebar.collapsed .sidebar-header {
        justify-content: center;
        padding: 1.25rem 0.5rem;
    }
    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        overflow: hidden;
    }
    .admin-sidebar.collapsed .sidebar-logo-text {
        display: none;
    }
    .logo-badge {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: linear-gradient(135deg, #dd4814 0%, #f97316 100%);
        color: #ffffff;
        font-weight: 800;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .sidebar-brand {
        font-weight: 700;
        font-size: 1.02rem;
        color: #ffffff;
        line-height: 1.2;
        white-space: nowrap;
    }
    .role-badge {
        display: inline-block;
        margin-top: 0.2rem;
        font-size: 0.68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background-color: rgba(221, 72, 20, 0.2);
        color: #fb923c;
        padding: 0.12rem 0.45rem;
        border-radius: 4px;
        white-space: nowrap;
    }
    .sidebar-section-label {
        padding: 1.1rem 1.25rem 0.45rem;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
    }
    .admin-sidebar.collapsed .sidebar-section-label {
        text-align: center;
        padding: 0.9rem 0.25rem 0.35rem;
        font-size: 0.62rem;
    }
    .admin-sidebar.collapsed .sidebar-section-label span {
        display: none;
    }
    .admin-sidebar.collapsed .sidebar-section-label::after {
        content: "•••";
        letter-spacing: 2px;
        color: #475569;
    }
    .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 0 0.75rem;
    }
    .admin-sidebar.collapsed .sidebar-nav {
        padding: 0 0.6rem;
    }
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 0.85rem;
        border-radius: 8px;
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        white-space: nowrap;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .admin-sidebar.collapsed .sidebar-link {
        justify-content: center;
        padding: 0.7rem;
    }
    .admin-sidebar.collapsed .sidebar-link span {
        display: none;
    }
    .sidebar-link svg {
        width: 19px;
        height: 19px;
        flex-shrink: 0;
    }
    .sidebar-link:hover {
        background-color: #1e293b;
        color: #f8fafc;
    }
    .sidebar-link.active {
        background-color: #dd4814;
        color: #ffffff;
    }
    .sidebar-footer {
        margin-top: auto;
        padding: 0.85rem 0.75rem;
        border-top: 1px solid #1e293b;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }
    .admin-sidebar.collapsed .sidebar-footer {
        padding: 0.85rem 0.6rem;
        align-items: center;
    }
    .sidebar-toggle-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.55rem 0.75rem;
        border-radius: 8px;
        background-color: #1e293b;
        border: 1px solid #334155;
        color: #94a3b8;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
        width: 100%;
    }
    .sidebar-toggle-btn:hover {
        background-color: #334155;
        color: #ffffff;
    }
    .sidebar-toggle-btn svg {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        transition: transform 0.22s ease;
    }
    .admin-sidebar.collapsed .sidebar-toggle-btn span {
        display: none;
    }
    .admin-sidebar.collapsed .sidebar-toggle-btn svg {
        transform: rotate(180deg);
    }

    @media (max-width: 768px) {
        .admin-sidebar,
        .admin-sidebar.collapsed {
            width: 100%;
            height: auto;
            position: relative;
        }
    }
</style>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <span class="logo-badge" title="Prestação App">PA</span>
            <div class="sidebar-logo-text">
                <div class="sidebar-brand">Prestação App</div>
                <span class="role-badge"><?= $isAdmin ? 'Administrador' : 'Usuário' ?></span>
            </div>
        </div>
    </div>

    <div class="sidebar-section-label"><span>Menu Principal</span></div>
    <nav class="sidebar-nav">
        <a href="<?= site_url('dashboard') ?>" title="Dashboard" class="sidebar-link <?= ($currentUri === 'dashboard' || $currentUri === '') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                <rect x="3" y="14" width="7" height="7" rx="1"></rect>
            </svg>
            <span>Dashboard</span>
        </a>

        <a href="<?= site_url('accountability') ?>" title="Prestações de Contas" class="sidebar-link <?= str_starts_with($currentUri, 'accountability') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            <span>Prestações de Contas</span>
        </a>

        <?php if ($isAdmin): ?>
            <a href="<?= site_url('history') ?>" title="Histórico de Auditoria" class="sidebar-link <?= str_starts_with($currentUri, 'history') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Histórico de Auditoria</span>
            </a>
        <?php endif; ?>
    </nav>

    <?php if ($isAdmin): ?>
        <div class="sidebar-section-label"><span>Administração</span></div>
        <nav class="sidebar-nav">
            <a href="<?= site_url('users') ?>" title="Usuários e Permissões" class="sidebar-link <?= str_starts_with($currentUri, 'users') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Usuários e Permissões</span>
            </a>
        </nav>
    <?php endif; ?>

    <div class="sidebar-footer">
        <?php if ($isAdmin): ?>
            <a href="<?= site_url('settings') ?>" title="Configurações" class="sidebar-link <?= str_starts_with($currentUri, 'settings') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Configurações</span>
            </a>
        <?php endif; ?>

        <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" title="Ocultar / Expandir menu lateral">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span>Ocultar menu</span>
        </button>
    </div>
</aside>

<script>
    (function () {
        const sidebar = document.getElementById('adminSidebar');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const storageKey = 'prestacao_sidebar_collapsed';

        if (!sidebar) return;

        if (localStorage.getItem(storageKey) === '1') {
            sidebar.classList.add('collapsed');
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const isCollapsed = sidebar.classList.toggle('collapsed');
                localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
            });
        }
    })();
</script>
