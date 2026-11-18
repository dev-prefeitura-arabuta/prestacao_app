<?php
$uName  = $userName ?? session()->get('user_name') ?? 'Usuário';
$uEmail = $userEmail ?? session()->get('user_email') ?? '';
$uRole  = $userRole ?? session()->get('user_role') ?? session()->get('role') ?? 'user';
$title  = $pageTitle ?? ($uRole === 'admin' ? 'Painel Administrativo' : 'Prestação App');
?>
<style>
    .top-navbar {
        background-color: #ffffff;
        color: #0f172a;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.75rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }
    .top-navbar-brand {
        font-weight: 700;
        font-size: 1.1rem;
        color: #0f172a;
        text-decoration: none;
    }
    .top-navbar-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .top-user-info {
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }
    .top-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #0f172a;
        color: #ffffff;
        font-weight: 700;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .top-user-text {
        display: flex;
        flex-direction: column;
        line-height: 1.25;
    }
    .top-user-text strong {
        font-size: 0.88rem;
        color: #0f172a;
    }
    .top-user-text small {
        font-size: 0.76rem;
        color: #64748b;
    }
    .top-btn-logout {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.5rem 0.9rem;
        border-radius: 7px;
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .top-btn-logout svg {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }
    .top-btn-logout:hover {
        background-color: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
    }
</style>

<nav class="top-navbar">
    <a href="<?= site_url('dashboard') ?>" class="top-navbar-brand">
        <?= esc($title) ?>
    </a>

    <div class="top-navbar-right">
        <div class="top-user-info">
            <div class="top-user-avatar">
                <?= esc(strtoupper(substr((string) $uName, 0, 1))) ?>
            </div>
            <div class="top-user-text">
                <strong><?= esc($uName) ?></strong>
                <?php if ($uEmail !== ''): ?>
                    <small><?= esc($uEmail) ?></small>
                <?php endif; ?>
            </div>
        </div>

        <a href="<?= site_url('logout') ?>" class="top-btn-logout" title="Sair do sistema">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Sair</span>
        </a>
    </div>
</nav>
<?= view('partials/sweetalert') ?>
