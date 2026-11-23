<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Auditoria - Sistema de Prestação</title>
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
        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            width: 100%;
            overflow: hidden;
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
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.9rem 1.5rem;
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
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #334155;
        }
        tbody tr:hover {
            background-color: #f8fafc;
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
    <div class="app-layout">
        <?= view('partials.sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials.navbar', [
                'pageTitle' => 'Histórico de Auditoria',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <div>
                        <h1>Histórico de Auditoria</h1>
                        <p>Consulte o registro de movimentações e anexos das prestações de contas.</p>
                    </div>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table id="auditTable">
                            <thead>
                                <tr>
                                    <th class="sortable" data-col="0" data-sort-type="number" title="Clique para ordenar crescente/decrescente">
                                        Código <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="1" data-sort-type="text" title="Clique para ordenar de A-Z / Z-A">
                                        Prestação de Contas <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="2" data-sort-type="text" title="Clique para ordenar de A-Z / Z-A">
                                        Usuário <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="3" data-sort-type="text" title="Clique para ordenar de A-Z / Z-A">
                                        Ação Registrada <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="4" data-sort-type="text" title="Clique para ordenar por data/hora">
                                        Data / Hora <span class="sort-indicator">↕</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="5" class="empty-state">Nenhuma movimentação registrada até o momento.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= esc((string) $log['id']) ?></td>
                                            <td>
                                                <a href="<?= site_url('accountability/' . $log['accountability_id']) ?>" style="color: #dd4814; font-weight: 600; text-decoration: none;">
                                                    <?= esc($log['accountability_title']) ?>
                                                </a>
                                            </td>
                                            <td><strong><?= esc($log['user_name']) ?></strong></td>
                                            <td><?= esc($log['status']) ?></td>
                                            <td><?= ! empty($log['created_at']) ? esc(date('d/m/Y H:i', strtotime((string) $log['created_at']))) : '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function() {
            const tbody = document.querySelector('#auditTable tbody');
            const allRows = Array.from(document.querySelectorAll('#auditTable tbody tr')).filter(
                r => !r.querySelector('.empty-state')
            );
            const sortableHeaders = Array.from(document.querySelectorAll('#auditTable thead th.sortable'));

            let sortCol = null;
            let sortDir = 'asc';
            let sortType = 'text';

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

                    allRows.sort(function(a, b) {
                        const valA = a.children[col] ? a.children[col].textContent.trim() : '';
                        const valB = b.children[col] ? b.children[col].textContent.trim() : '';

                        let cmp = 0;
                        if (sortType === 'number') {
                            const numA = parseFloat(valA) || 0;
                            const numB = parseFloat(valB) || 0;
                            cmp = numA - numB;
                        } else {
                            cmp = valA.localeCompare(valB, 'pt-BR', { sensitivity: 'base', numeric: true });
                        }

                        return sortDir === 'asc' ? cmp : -cmp;
                    });

                    if (tbody) {
                        allRows.forEach(row => tbody.appendChild(row));
                    }
                });
            });
        })();
    </script>
</body>
</html>

