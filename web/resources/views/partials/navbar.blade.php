<?php
$uId     = (int) session('user_id');
$uName   = $userName ?? session('user_name') ?? 'Usuário';
$uEmail  = $userEmail ?? session('user_email') ?? '';
$uRole   = $userRole ?? session('user_role') ?? session('role') ?? 'user';
$uAvatar = session('user_avatar');
$title   = $pageTitle ?? ($uRole === 'admin' ? 'Painel Administrativo' : 'Prestação App');
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
        overflow: hidden;
    }
    .top-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
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
        font-family: inherit;
        line-height: inherit;
        cursor: pointer;
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
    .notif-wrap {
        position: relative;
    }
    .notif-bell-btn {
        position: relative;
        width: 38px;
        height: 38px;
        border-radius: 9px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1rem;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }
    .notif-bell-btn:hover {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        color: #0b4ea2;
    }
    .notif-bell-btn.muted {
        color: #94a3b8;
        background-color: #f1f5f9;
    }
    .notif-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        min-width: 19px;
        height: 19px;
        padding: 0 5px;
        border-radius: 999px;
        background-color: #dd4814;
        color: #ffffff;
        font-size: 0.68rem;
        font-weight: 800;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid #ffffff;
    }
    .notif-badge.visible {
        display: inline-flex;
    }
    .notif-dropdown {
        position: absolute;
        top: 46px;
        right: 0;
        width: 360px;
        max-width: 92vw;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 18px 40px -12px rgba(15, 23, 42, 0.22);
        z-index: 200;
        display: none;
        overflow: hidden;
    }
    .notif-dropdown.open {
        display: block;
    }
    .notif-header {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
    }
    .notif-header strong {
        font-size: 0.9rem;
        color: #0f172a;
    }
    .notif-toggle-bar {
        padding: 0.6rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.8rem;
        color: #334155;
        background: #ffffff;
    }
    .notif-toggle-btn {
        border: 1px solid #cbd5e1;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-size: 0.74rem;
        font-weight: 700;
        cursor: pointer;
        background: #f1f5f9;
        color: #475569;
    }
    .notif-toggle-btn.active {
        background: #dcfce7;
        border-color: #86efac;
        color: #166534;
    }
    .notif-list {
        max-height: 320px;
        overflow-y: auto;
    }
    .notif-item {
        display: block;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: #1e293b;
        transition: background-color 0.12s ease;
    }
    .notif-item:hover {
        background-color: #f8fafc;
    }
    .notif-item.unread {
        background-color: #fff7ed;
    }
    .notif-item-title {
        font-size: 0.82rem;
        font-weight: 700;
        color: #072c5e;
        margin-bottom: 0.2rem;
    }
    .notif-item-msg {
        font-size: 0.78rem;
        color: #475569;
        line-height: 1.4;
    }
    .notif-item-time {
        font-size: 0.7rem;
        color: #94a3b8;
        margin-top: 0.25rem;
    }
    .notif-empty {
        padding: 1.5rem 1rem;
        text-align: center;
        font-size: 0.82rem;
        color: #94a3b8;
    }
</style>

<nav class="top-navbar">
    <a href="<?= site_url('accountability') ?>" class="top-navbar-brand">
        <?= esc($title) ?>
    </a>

    <div class="top-navbar-right">
        <?php if ($uRole === 'admin'): ?>
            <div class="notif-wrap" id="adminNotifWrap">
                <button type="button" class="notif-bell-btn" id="adminNotifBellBtn" title="Notificações de atividades concluídas">
                    <i class="fa-solid fa-bell" id="adminNotifBellIcon"></i>
                    <span class="notif-badge" id="adminNotifBadge">0</span>
                </button>
                <div class="notif-dropdown" id="adminNotifDropdown">
                    <div class="notif-header">
                        <strong><i class="fa-solid fa-bell" style="color:#dd4814;"></i> Notificações</strong>
                        <button type="button" id="adminNotifReadAllBtn" style="background:none;border:none;color:#0b4ea2;font-size:0.75rem;font-weight:700;cursor:pointer;">
                            Marcar lidas
                        </button>
                    </div>
                    <div class="notif-toggle-bar">
                        <span>Receber alertas de atividades</span>
                        <button type="button" class="notif-toggle-btn active" id="adminNotifToggleBtn">Ativas</button>
                    </div>
                    <div class="notif-list" id="adminNotifList">
                        <div class="notif-empty">Carregando notificações...</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <a href="<?= site_url('profile') ?>" class="top-user-info" title="Acessar meu perfil" style="text-decoration: none;">
            <div class="top-user-avatar">
                <?php if (! empty($uAvatar) && $uId > 0): ?>
                    <img src="<?= site_url('profile/avatar/' . $uId) ?>?v=<?= esc(md5((string) $uAvatar)) ?>" alt="<?= esc($uName) ?>">
                <?php else: ?>
                    <?= esc(strtoupper(substr((string) $uName, 0, 1))) ?>
                <?php endif; ?>
            </div>
            <div class="top-user-text">
                <strong><?= esc($uName) ?></strong>
                <?php if ($uEmail !== ''): ?>
                    <small><?= esc($uEmail) ?></small>
                <?php endif; ?>
            </div>
        </a>

        <form action="<?= site_url('logout') ?>" method="post" style="margin:0;display:inline-flex;">
        <?= csrf_field() ?>
        <button type="submit" class="top-btn-logout" title="Sair do sistema">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Sair</span>
        </button>
        </form>
    </div>
</nav>
<?php if ($uRole === 'admin'): ?>
<script>
(function () {
    const csrfToken = '<?= csrf_token() ?>';
    const bellBtn = document.getElementById('adminNotifBellBtn');
    const bellIcon = document.getElementById('adminNotifBellIcon');
    const badge = document.getElementById('adminNotifBadge');
    const dropdown = document.getElementById('adminNotifDropdown');
    const listEl = document.getElementById('adminNotifList');
    const toggleBtn = document.getElementById('adminNotifToggleBtn');
    const readAllBtn = document.getElementById('adminNotifReadAllBtn');
    const wrap = document.getElementById('adminNotifWrap');
    if (!bellBtn || !dropdown) return;

    let notifEnabled = true;

    function renderNotifs(data) {
        notifEnabled = !!data.enabled;
        const unread = Number(data.unread_count || 0);

        if (unread > 0 && notifEnabled) {
            badge.textContent = unread > 99 ? '99+' : String(unread);
            badge.classList.add('visible');
        } else {
            badge.classList.remove('visible');
        }

        if (notifEnabled) {
            bellBtn.classList.remove('muted');
            bellIcon.className = 'fa-solid fa-bell';
            toggleBtn.classList.add('active');
            toggleBtn.textContent = 'Ativas';
        } else {
            bellBtn.classList.add('muted');
            bellIcon.className = 'fa-solid fa-bell-slash';
            toggleBtn.classList.remove('active');
            toggleBtn.textContent = 'Pausadas';
        }

        const items = Array.isArray(data.items) ? data.items : [];
        if (items.length === 0) {
            listEl.innerHTML = '<div class="notif-empty">Nenhuma atividade concluída registrada ainda.</div>';
            return;
        }

        listEl.innerHTML = items.map(function (it) {
            const href = it.accountability_id
                ? '<?= site_url('accountability') ?>/' + it.accountability_id
                : '<?= site_url('accountability') ?>';
            const cls = it.is_read ? 'notif-item' : 'notif-item unread';
            return '<a class="' + cls + '" href="' + href + '" data-notif-id="' + it.id + '">' +
                '<div class="notif-item-title">' + escapeHtml(it.title) + '</div>' +
                '<div class="notif-item-msg">' + escapeHtml(it.message) + '</div>' +
                '<div class="notif-item-time">' + escapeHtml(it.created_at) + '</div>' +
                '</a>';
        }).join('');

        listEl.querySelectorAll('a[data-notif-id]').forEach(function (a) {
            a.addEventListener('click', function () {
                const nid = a.getAttribute('data-notif-id');
                fetch('<?= site_url('notifications') ?>/' + nid + '/read', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    keepalive: true
                }).catch(function () {});
            });
        });
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function loadNotifs() {
        fetch('<?= site_url('notifications') ?>', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (d && d.success) renderNotifs(d); })
            .catch(function () {});
    }

    bellBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
            loadNotifs();
        }
    });

    document.addEventListener('click', function (e) {
        if (wrap && !wrap.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });

    toggleBtn.addEventListener('click', function () {
        fetch('<?= site_url('notifications/toggle') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ enabled: !notifEnabled })
        }).then(function () { loadNotifs(); }).catch(function () {});
    });

    readAllBtn.addEventListener('click', function () {
        fetch('<?= site_url('notifications/read-all') ?>', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        }).then(function () { loadNotifs(); }).catch(function () {});
    });

    loadNotifs();
    setInterval(function () {
        if (notifEnabled) loadNotifs();
    }, 25000);
})();
</script>
<?php endif; ?>
<?= view('partials.sweetalert') ?>

