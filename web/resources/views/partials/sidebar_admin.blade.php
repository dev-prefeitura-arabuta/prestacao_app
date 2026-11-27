<?php
$currentUri  = uri_string();
$role        = $userRole ?? session('user_role') ?? session('role') ?? 'user';
$isAdmin     = ($role === 'admin');
$isDeveloper = (bool) session('is_developer');
$genSettings = \App\Models\GeneralSettings::getSettings();
$appName     = $genSettings['app_name'] ?? 'Sistema Patrimonial';
$hasAppLogo  = ! empty($genSettings['app_logo']);
$appLogoUrl  = $hasAppLogo ? site_url('app-logo') . '?v=' . md5((string) $genSettings['app_logo']) : null;
$appInitials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $appName) ?: 'SP', 0, 2));
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

    .sidebar-subnav {
        display: none;
        flex-direction: column;
        gap: 0.15rem;
        margin: 0.15rem 0 0.25rem 1.35rem;
        padding-left: 0.7rem;
        border-left: 1px solid #334155;
    }

    .sidebar-subnav.open {
        display: flex;
    }

    .sidebar-accordion-btn {
        width: 100%;
        background: none;
        border: none;
        cursor: pointer;
        text-align: left;
        font-family: inherit;
    }

    .sidebar-chevron {
        width: 15px !important;
        height: 15px !important;
        margin-left: auto;
        transition: transform 0.2s ease;
    }

    .sidebar-accordion-btn.open .sidebar-chevron {
        transform: rotate(180deg);
    }

    .admin-sidebar.collapsed .sidebar-chevron {
        display: none;
    }

    .admin-sidebar.collapsed .sidebar-subnav {
        display: none !important;
    }

    .sidebar-sublink {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.38rem 0.65rem;
        border-radius: 6px;
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 500;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .sidebar-sublink:hover {
        background-color: #1e293b;
        color: #f8fafc;
    }

    .sidebar-sublink.active {
        background-color: rgba(221, 72, 20, 0.22);
        color: #fdba74;
        font-weight: 700;
    }

    .sidebar-sublink-count {
        font-size: 0.7rem;
        padding: 0.08rem 0.42rem;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.16);
        color: #cbd5e1;
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

    .sidebar-mobile-close,
    .sidebar-mobile-backdrop {
        display: none;
    }

    /* Celular/tablet: o menu vira uma gaveta escondida, aberta pelo botão ☰ da barra superior */
    @media (max-width: 768px) {
        .admin-sidebar,
        .admin-sidebar.collapsed {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            max-width: 86vw;
            height: 100vh;
            height: 100dvh;
            overflow-y: auto;
            transform: translateX(-100%);
            transition: transform 0.22s ease;
            z-index: 1100;
            box-shadow: none;
        }

        .admin-sidebar.mobile-open {
            transform: translateX(0);
            /* a sombra só aparece com o menu aberto (fechado, ela vazaria na borda da tela) */
            box-shadow: 12px 0 32px rgba(15, 23, 42, 0.35);
        }

        .sidebar-toggle-btn {
            display: none;
        }

        .sidebar-mobile-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 0.85rem;
            right: 0.85rem;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #1e293b;
            color: #e2e8f0;
            font-size: 1.1rem;
            cursor: pointer;
        }

        .sidebar-mobile-backdrop.open {
            display: block;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            z-index: 1090;
        }

        body.mobile-menu-open {
            overflow: hidden;
        }
    }
</style>
<div class="sidebar-mobile-backdrop" id="sidebarMobileBackdrop"></div>

<aside class="admin-sidebar" id="adminSidebar" aria-label="Menu">
    <button type="button" class="sidebar-mobile-close" id="sidebarMobileClose" aria-label="Fechar menu">✕</button>
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="sidebar-logo-text">
                <div class="sidebar-brand"><?= esc($appName) ?></div>
                <span class="role-badge"><?= $isDeveloper ? 'Desenvolvedor' : ($isAdmin ? 'Administrador' : 'Usuário') ?></span>
            </div>
        </div>
    </div>

    <div class="sidebar-section-label"><span><?= $isDeveloper ? 'Desenvolvedor' : 'Menu Principal' ?></span></div>
    <nav class="sidebar-nav">
        <?php if ($isDeveloper): /* A conta de desenvolvedor não usa a área de negócio */ ?>
        <a href="<?= site_url('logs') ?>" title="Logs do Sistema" class="sidebar-link <?= str_starts_with($currentUri, 'logs') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="4 17 10 11 4 5"></polyline>
                <line x1="12" y1="19" x2="20" y2="19"></line>
            </svg>
            <span>Logs do Sistema</span>
        </a>
        <?php else: ?>
        <a href="<?= site_url('accountability') ?>" title="Prestações de Contas" class="sidebar-link <?= (str_starts_with($currentUri, 'accountability') || $currentUri === '') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            <span>Prestações de Contas</span>
        </a>

        <?php if ($isAdmin): ?>
            <?php $sidebarReviewCount = count(app(\App\Services\ItemReviewService::class)->reviewQueue()); ?>
            <a href="<?= site_url('review') ?>" title="Itens aguardando avaliação" class="sidebar-link <?= str_starts_with($currentUri, 'review') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline>
                    <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
                </svg>
                <span>Para Avaliar</span>
                <?php if ($sidebarReviewCount > 0): ?>
                    <span class="sidebar-sublink-count"><?= (int) $sidebarReviewCount ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= site_url('pendencias') ?>" title="Pendências por pessoa" class="sidebar-link <?= str_starts_with($currentUri, 'pendencias') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <line x1="19" y1="8" x2="19" y2="14"></line>
                    <line x1="22" y1="11" x2="16" y2="11"></line>
                </svg>
                <span>Pendências por Pessoa</span>
            </a>

            <?php
            $sidebarAnnualYears = \App\Http\Controllers\AnnualHistoryController::getAvailableYearsWithCounts();
            $isAnnualActive     = str_starts_with($currentUri, 'historico-anual');
            ?>
            <button type="button" id="annualHistoryToggleBtn" title="Histórico Anual" class="sidebar-link sidebar-accordion-btn <?= $isAnnualActive ? 'active open' : '' ?>" aria-expanded="<?= $isAnnualActive ? 'true' : 'false' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Histórico Anual</span>
                <svg class="sidebar-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div id="annualHistorySubnav" class="sidebar-subnav <?= $isAnnualActive ? 'open' : '' ?>">
                <a href="<?= site_url('historico-anual') ?>" class="sidebar-sublink <?= $currentUri === 'historico-anual' ? 'active' : '' ?>">
                    <span>Todos os Anos</span>
                    <span class="sidebar-sublink-count"><?= count($sidebarAnnualYears) ?></span>
                </a>
                <?php foreach ($sidebarAnnualYears as $sbYear => $sbCount): ?>
                    <a href="<?= site_url('historico-anual/' . $sbYear) ?>" class="sidebar-sublink <?= $currentUri === ('historico-anual/' . $sbYear) ? 'active' : '' ?>">
                        <span><?= esc((string) $sbYear) ?></span>
                        <span class="sidebar-sublink-count"><?= (int) $sbCount ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <a href="<?= site_url('history') ?>" title="Histórico de Auditoria" class="sidebar-link <?= str_starts_with($currentUri, 'history') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Histórico de Auditoria</span>
            </a>
        <?php endif; ?>
        <?php endif; /* fim da área de negócio */ ?>
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
        <a href="<?= site_url('profile') ?>" title="Meu Perfil" class="sidebar-link <?= str_starts_with($currentUri, 'profile') ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Perfil</span>
        </a>

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
    (function() {
        const sidebar = document.getElementById('adminSidebar');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const storageKey = 'prestacao_sidebar_collapsed';

        if (!sidebar) return;

        const mobileQuery = window.matchMedia('(max-width: 768px)');
        const backdrop = document.getElementById('sidebarMobileBackdrop');

        // O modo "mini" do computador não existe no celular (lá o menu é uma gaveta)
        if (localStorage.getItem(storageKey) === '1' && !mobileQuery.matches) {
            sidebar.classList.add('collapsed');
        }

        function setMobileMenu(open) {
            sidebar.classList.toggle('mobile-open', open);
            if (backdrop) backdrop.classList.toggle('open', open);
            document.body.classList.toggle('mobile-menu-open', open);
            document.querySelectorAll('[data-mobile-menu-toggle]').forEach(function (btn) {
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        // O botão ☰ fica na barra superior (carregada depois deste menu)
        document.addEventListener('click', function (ev) {
            if (ev.target.closest('[data-mobile-menu-toggle]')) {
                setMobileMenu(!sidebar.classList.contains('mobile-open'));
            }
        });
        if (backdrop) backdrop.addEventListener('click', function () { setMobileMenu(false); });
        const closeBtn = document.getElementById('sidebarMobileClose');
        if (closeBtn) closeBtn.addEventListener('click', function () { setMobileMenu(false); });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && sidebar.classList.contains('mobile-open')) setMobileMenu(false);
        });
        mobileQuery.addEventListener('change', function (e) {
            if (!e.matches) setMobileMenu(false);
        });

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const isCollapsed = sidebar.classList.toggle('collapsed');
                localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
            });
        }

        // Menu abre/fecha do Histórico Anual
        const annualBtn = document.getElementById('annualHistoryToggleBtn');
        const annualSub = document.getElementById('annualHistorySubnav');
        const annualKey = 'prestacao_annual_submenu_open';

        if (annualBtn && annualSub) {
            const isCurrentlyActive = annualBtn.classList.contains('active');
            if (!isCurrentlyActive && localStorage.getItem(annualKey) === '1') {
                annualBtn.classList.add('open');
                annualSub.classList.add('open');
                annualBtn.setAttribute('aria-expanded', 'true');
            }

            annualBtn.addEventListener('click', function() {
                if (sidebar.classList.contains('collapsed')) {
                    window.location.href = <?= json_encode(site_url('historico-anual'), JSON_UNESCAPED_UNICODE) ?>;
                    return;
                }
                const isOpen = annualSub.classList.toggle('open');
                annualBtn.classList.toggle('open', isOpen);
                annualBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                localStorage.setItem(annualKey, isOpen ? '1' : '0');
            });
        }
    })();
</script>