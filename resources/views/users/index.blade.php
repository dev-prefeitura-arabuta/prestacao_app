<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários e Permissões - Sistema de Prestação</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }

        .app-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: 100%;
        }

        .container {
            width: 100%;
            max-width: 100%;
            padding: 1.75rem 2rem;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .page-header h1 {
            font-size: 1.5rem;
            color: #0f172a;
        }

        .page-header p {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background-color: #dd4814;
            color: #ffffff;
            text-decoration: none;
            padding: 0.65rem 1.15rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background-color 0.15s ease;
        }

        .btn-primary:hover {
            background-color: #c23c0f;
        }

        .alert {
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.15rem 1.25rem;
        }

        .stat-card span {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
        }

        .stat-card strong {
            display: block;
            margin-top: 0.35rem;
            font-size: 1.5rem;
            color: #0f172a;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            width: 100%;
        }

        .table-toolbar {
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-input {
            padding: 0.55rem 0.85rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.875rem;
            width: 100%;
            max-width: 360px;
            outline: none;
        }

        .search-input:focus {
            border-color: #dd4814;
            box-shadow: 0 0 0 3px rgba(221, 72, 20, 0.12);
        }

        .page-size-wrap {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 600;
        }

        .page-size-select {
            padding: 0.45rem 0.7rem;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #0f172a;
            background-color: #ffffff;
            outline: none;
            cursor: pointer;
        }

        .page-size-select:focus {
            border-color: #dd4814;
        }

        .table-footer {
            padding: 0.95rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            font-size: 0.83rem;
            color: #64748b;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .page-btn {
            padding: 0.38rem 0.72rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: #ffffff;
            color: #1e293b;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .page-btn:hover:not(:disabled) {
            background-color: #f1f5f9;
            border-color: #94a3b8;
        }

        .page-btn.active {
            background-color: #dd4814;
            border-color: #dd4814;
            color: #ffffff;
        }

        .page-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        thead th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.7rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            user-select: none;
        }

        thead th.sortable {
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        thead th.sortable:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        thead th.sortable .sort-indicator {
            display: inline-block;
            margin-left: 0.35rem;
            font-size: 0.75rem;
            color: #94a3b8;
        }

        thead th.sortable.sort-asc .sort-indicator,
        thead th.sortable.sort-desc .sort-indicator {
            color: #dd4814;
            font-weight: 800;
        }

        tbody td {
            padding: 0.65rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
            color: #334155;
            vertical-align: middle;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-cell-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .badge-admin {
            background-color: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }

        .badge-user {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .perm-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            max-width: 280px;
        }

        .perm-pill {
            display: inline-block;
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            font-size: 0.73rem;
            font-weight: 600;
            padding: 0.15rem 0.45rem;
            border-radius: 5px;
        }

        .perm-pill.none {
            background-color: #f8fafc;
            color: #94a3b8;
            border-color: #e2e8f0;
        }

        .perm-pill.all {
            background-color: #f0fdf4;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .actions-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-edit {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.75rem;
            background-color: #0f172a;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: background-color 0.15s ease;
        }

        .btn-edit:hover {
            background-color: #334155;
        }

        .btn-delete {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.75rem;
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .btn-delete:hover {
            background-color: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }

        .empty-state {
            padding: 2.5rem;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .app-layout {
                flex-direction: column;
            }

            .container {
                padding: 1.25rem 1rem;
            }
        }
    </style>
</head>

<body>
    <?php
    $totalUsers    = count($users);
    $totalAdmins   = count(array_filter($users, static fn($u) => ($u['role'] ?? 'user') === 'admin'));
    $totalCommon   = $totalUsers - $totalAdmins;
    $currentUserId = (int) session('user_id');
    $shortItems    = \App\Models\Accountability::getShortItems();
    $fullItems     = \App\Models\Accountability::getItems();
    ?>

    <div class="app-layout">
        <?= view('partials.sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials.navbar', [
                'pageTitle' => 'Usuários e Permissões',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <div>
                        <h1>Usuários e Permissões</h1>
                        <p>Visualize, cadastre, edite ou remova usuários e defina quais dos 24 campos de anexos cada um pode acessar.</p>
                    </div>
                    <a href="<?= site_url('users/create') ?>" class="btn-primary">
                        + Novo Usuário
                    </a>
                </div>

                <div class="card">
                    <div class="table-toolbar">
                        <input
                            type="text"
                            id="userSearch"
                            class="search-input"
                            placeholder="Buscar por nome, e-mail ou permissão...">

                        <div class="page-size-wrap">
                            <label for="pageSizeSelect">Exibir:</label>
                            <select id="pageSizeSelect" class="page-size-select">
                                <option value="10" selected>10</option>
                                <option value="30">30</option>
                                <option value="60">60</option>
                                <option value="100">100</option>
                                <option value="all">Todos</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="usersTable">
                            <thead>
                                <tr>
                                    <th class="sortable" data-col="0" data-sort-type="number" title="Clique para ordenar crescente/decrescente">
                                        Código <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="1" data-sort-type="text" title="Clique para ordenar de A-Z / Z-A">
                                        Nome <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="2" data-sort-type="text" title="Clique para ordenar de A-Z / Z-A">
                                        E-mail <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="3" data-sort-type="text" title="Clique para ordenar de A-Z / Z-A">
                                        Perfil <span class="sort-indicator">↕</span>
                                    </th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="5" class="empty-state">Nenhum usuário encontrado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                        <?php
                                        $uid   = (int) $u['id'];
                                        $uRole = $u['role'] ?? 'user';
                                        ?>
                                        <tr>
                                            <td><?= esc((string) $uid) ?></td>
                                            <td><strong><?= esc($u['name']) ?></strong></td>
                                            <td><?= esc($u['email']) ?></td>
                                            <td>
                                                <span class="badge <?= $uRole === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                                    <?= $uRole === 'admin' ? 'Administrador' : 'Usuário' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions-cell">
                                                    <a href="<?= site_url('users/' . $uid . '/edit') ?>" class="btn-edit">
                                                        Editar
                                                    </a>

                                                    <?php if ($uid !== $currentUserId): ?>
                                                        <form
                                                            action="<?= site_url('users/' . $uid . '/delete') ?>"
                                                            method="post"
                                                            onsubmit="return confirm('Tem certeza que deseja excluir o usuário <?= esc($u['name'], 'js') ?>?');">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn-delete">Deletar</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer" id="usersTableFooter">
                        <div id="usersPaginationInfo"></div>
                        <div class="pagination-controls" id="usersPaginationControls"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function() {
            const searchInput = document.getElementById('userSearch');
            const pageSizeSelect = document.getElementById('pageSizeSelect');
            const tbody = document.querySelector('#usersTable tbody');
            const allRows = Array.from(document.querySelectorAll('#usersTable tbody tr')).filter(
                r => !r.querySelector('.empty-state')
            );
            const sortableHeaders = Array.from(document.querySelectorAll('#usersTable thead th.sortable'));
            const infoEl = document.getElementById('usersPaginationInfo');
            const controlsEl = document.getElementById('usersPaginationControls');

            let currentPage = 1;
            let sortCol = null;
            let sortDir = 'asc';
            let sortType = 'text';

            function getCellValue(row, colIdx) {
                const cell = row.children[colIdx];
                return cell ? cell.textContent.trim() : '';
            }

            function sortRows() {
                if (sortCol === null) return;

                allRows.sort(function(a, b) {
                    const valA = getCellValue(a, sortCol);
                    const valB = getCellValue(b, sortCol);

                    let cmp = 0;
                    if (sortType === 'number') {
                        const numA = parseFloat(valA.replace(/[^\d.-]/g, '')) || 0;
                        const numB = parseFloat(valB.replace(/[^\d.-]/g, '')) || 0;
                        cmp = numA - numB;
                    } else {
                        cmp = valA.localeCompare(valB, 'pt-BR', { sensitivity: 'base', numeric: true });
                    }

                    return sortDir === 'asc' ? cmp : -cmp;
                });

                if (tbody) {
                    allRows.forEach(row => tbody.appendChild(row));
                }
            }

            sortableHeaders.forEach(function(th) {
                th.addEventListener('click', function() {
                    const col = parseInt(this.getAttribute('data-col'), 10);
                    const type = this.getAttribute('data-sort-type') || 'text';

                    if (sortCol === col) {
                        sortDir = (sortDir === 'asc') ? 'desc' : 'asc';
                    } else {
                        sortCol = col;
                        sortDir = 'asc';
                    }
                    sortType = type;

                    sortableHeaders.forEach(function(h) {
                        h.classList.remove('sort-asc', 'sort-desc');
                        const ind = h.querySelector('.sort-indicator');
                        if (ind) ind.textContent = '↕';
                    });

                    this.classList.add(sortDir === 'asc' ? 'sort-asc' : 'sort-desc');
                    const activeInd = this.querySelector('.sort-indicator');
                    if (activeInd) {
                        activeInd.textContent = sortDir === 'asc' ? '↑' : '↓';
                    }

                    sortRows();
                    currentPage = 1;
                    renderTable();
                });
            });

            function renderTable() {
                const term = (searchInput ? searchInput.value : '').toLowerCase().trim();
                const pageSizeVal = pageSizeSelect ? pageSizeSelect.value : '10';

                const matchedRows = allRows.filter(row => {
                    return !term || row.textContent.toLowerCase().includes(term);
                });

                const totalMatched = matchedRows.length;
                const pageSize = pageSizeVal === 'all' ? (totalMatched || 1) : parseInt(pageSizeVal, 10);
                const totalPages = Math.max(1, Math.ceil(totalMatched / pageSize));

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                const startIdx = (currentPage - 1) * pageSize;
                const endIdx = startIdx + pageSize;

                allRows.forEach(row => {
                    row.style.display = 'none';
                });

                matchedRows.slice(startIdx, endIdx).forEach(row => {
                    row.style.display = '';
                });

                if (infoEl) {
                    if (totalMatched === 0) {
                        infoEl.textContent = 'Nenhum usuário encontrado.';
                    } else {
                        const from = startIdx + 1;
                        const to = Math.min(endIdx, totalMatched);
                        infoEl.textContent = 'Exibindo ' + from + ' a ' + to + ' de ' + totalMatched + ' usuário(s)';
                    }
                }

                if (controlsEl) {
                    controlsEl.innerHTML = '';
                    if (totalPages > 1) {
                        const prevBtn = document.createElement('button');
                        prevBtn.type = 'button';
                        prevBtn.className = 'page-btn';
                        prevBtn.textContent = '← Anterior';
                        prevBtn.disabled = currentPage === 1;
                        prevBtn.addEventListener('click', function() {
                            if (currentPage > 1) {
                                currentPage--;
                                renderTable();
                            }
                        });
                        controlsEl.appendChild(prevBtn);

                        for (let p = 1; p <= totalPages; p++) {
                            const pBtn = document.createElement('button');
                            pBtn.type = 'button';
                            pBtn.className = 'page-btn' + (p === currentPage ? ' active' : '');
                            pBtn.textContent = String(p);
                            pBtn.addEventListener('click', function() {
                                currentPage = p;
                                renderTable();
                            });
                            controlsEl.appendChild(pBtn);
                        }

                        const nextBtn = document.createElement('button');
                        nextBtn.type = 'button';
                        nextBtn.className = 'page-btn';
                        nextBtn.textContent = 'Próxima →';
                        nextBtn.disabled = currentPage === totalPages;
                        nextBtn.addEventListener('click', function() {
                            if (currentPage < totalPages) {
                                currentPage++;
                                renderTable();
                            }
                        });
                        controlsEl.appendChild(nextBtn);
                    }
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    currentPage = 1;
                    renderTable();
                });
            }

            if (pageSizeSelect) {
                pageSizeSelect.addEventListener('change', function() {
                    currentPage = 1;
                    renderTable();
                });
            }

            renderTable();
        })();
    </script>
</body>

</html>
