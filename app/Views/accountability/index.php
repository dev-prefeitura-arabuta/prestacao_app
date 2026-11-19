<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestações de Contas - Sistema de Prestação</title>
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

        .info-banner {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 0.9rem 1.15rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            width: 100%;
            overflow: hidden;
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
            padding: 0.6rem 0.95rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.88rem;
            width: 100%;
            max-width: 380px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .search-input:focus {
            border-color: #dd4814;
            box-shadow: 0 0 0 3px rgba(221, 72, 20, 0.12);
        }

        .progress-box {
            min-width: 165px;
            padding: 0.45rem 0.65rem;
            border-radius: 8px;
            border: 1px solid transparent;
        }

        .progress-box.overdue {
            background-color: #fef3c7;
            border-color: #f59e0b;
        }

        .overdue-tag {
            display: inline-block;
            margin-top: 0.25rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: #b45309;
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
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #334155;
            vertical-align: middle;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-aberta {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .status-em_analise {
            background-color: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }

        .status-concluida {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .actions-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-open {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.9rem;
            background-color: #0f172a;
            color: #ffffff;
            text-decoration: none;
            border-radius: 7px;
            font-size: 0.82rem;
            font-weight: 600;
            transition: background-color 0.15s ease;
        }

        .btn-open:hover {
            background-color: #dd4814;
        }

        .btn-delete {
            padding: 0.45rem 0.75rem;
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 7px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-delete:hover {
            background-color: #dc2626;
            color: #ffffff;
        }

        .empty-state {
            padding: 3rem 1.5rem;
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
    $isAdmin      = (($userRole ?? 'user') === 'admin');
    $allowedCount = count($allowedItems ?? []);
    $statusLabels = [
        'aberta'     => 'Aberta',
        'em_analise' => 'Em Análise',
        'concluida'  => 'Concluída',
    ];
    ?>

    <div class="app-layout">
        <?= view('partials/sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials/navbar', [
                'pageTitle' => 'Prestações de Contas',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <div>
                        <h1>Prestações de Contas</h1>
                        <p>Acompanhe as prestações de contas e indexe múltiplos anexos (ZIP, JPG, JPEG, PNG e PDF) nos campos liberados.</p>
                    </div>
                    <?php if ($isAdmin): ?>
                        <a href="<?= site_url('accountability/create') ?>" class="btn-primary">
                            + Nova Prestação de Contas
                        </a>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="table-toolbar">
                        <input
                            type="text"
                            id="accountabilitySearch"
                            class="search-input"
                            placeholder="Buscar prestação de contas por título, descrição, prazo ou status...">
                    </div>

                    <div class="table-responsive">
                        <table id="accountabilitiesTable">
                            <thead>
                                <tr>
                                    <th class="sortable" data-col="0" data-sort-type="number" title="Clique para ordenar crescente/decrescente">
                                        Código <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="1" data-sort-type="text" title="Clique para ordenar de A-Z / Z-A">
                                        Título / Descrição <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="2" data-sort-type="text" title="Clique para ordenar por prazo">
                                        Prazo <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="3" data-sort-type="text" title="Clique para ordenar de A-Z / Z-A">
                                        Status <span class="sort-indicator">↕</span>
                                    </th>
                                    <th class="sortable" data-col="4" data-sort-type="number" title="Clique para ordenar crescente/decrescente">
                                        <?= $isAdmin ? 'Percentual Anexado' : 'Anexos Indexados' ?> <span class="sort-indicator">↕</span>
                                    </th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accountabilities)): ?>
                                    <tr>
                                        <td colspan="6" class="empty-state">
                                            Nenhuma prestação de contas cadastrada até o momento.
                                            <?php if ($isAdmin): ?>
                                                <br><br>
                                                <a href="<?= site_url('accountability/create') ?>" class="btn-primary">+ Criar Primeira Prestação de Contas</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($accountabilities as $acc): ?>
                                        <?php
                                        $accId       = (int) $acc['id'];
                                        $st          = $acc['status'] ?? 'aberta';
                                        $attCount    = $attachmentCounts[$accId] ?? 0;
                                        $filledItems = $filledItemsCounts[$accId] ?? 0;
                                        $percent     = $progressPercentages[$accId] ?? 0;
                                        $totalItems  = $totalItemsPerAcc[$accId] ?? ($totalItemsCount ?? 24);
                                        $rejCount    = $rejectedCounts[$accId] ?? 0;
                                        $isOverdue   = ! empty($acc['deadline'])
                                            && strtotime((string) $acc['deadline']) < strtotime(date('Y-m-d'))
                                            && $percent < 100
                                            && $st !== 'concluida';
                                        ?>
                                        <tr>
                                            <td><?= esc((string) $accId) ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                                    <strong><?= esc($acc['title']) ?></strong>
                                                    <?php if ($rejCount > 0): ?>
                                                        <span style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; font-size: 0.72rem; font-weight: 700; padding: 0.15rem 0.55rem; border-radius: 999px;">
                                                            <?= 'X   '  ?> Rejeitado
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (! empty($acc['description'])): ?>
                                                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.2rem;">
                                                        <?= esc($acc['description']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (! empty($acc['deadline'])): ?>
                                                    <span style="<?= $isOverdue ? 'color: #b45309; font-weight: 700;' : '' ?>">
                                                        <?= esc(date('d/m/Y', strtotime((string) $acc['deadline']))) ?>
                                                    </span>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?= esc($st) ?>">
                                                    <?= esc($statusLabels[$st] ?? ucfirst((string) $st)) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($isAdmin): ?>
                                                    <?php
                                                    $barColor   = $isOverdue ? '#f59e0b' : ($percent >= 100 ? '#16a34a' : ($percent > 0 ? '#2563eb' : '#cbd5e1'));
                                                    $trackColor = $isOverdue ? '#fde68a' : '#e2e8f0';
                                                    $textColor  = $isOverdue ? '#b45309' : '#0f172a';
                                                    ?>
                                                    <div class="progress-box <?= $isOverdue ? 'overdue' : '' ?>">
                                                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.82rem; margin-bottom: 0.3rem;">
                                                            <strong style="color: <?= $textColor ?>;"><?= esc((string) $percent) ?>%</strong>
                                                            <span style="color: <?= $isOverdue ? '#92400e' : '#64748b' ?>; font-size: 0.75rem;"><?= esc((string) $filledItems) ?>/<?= esc((string) $totalItems) ?> itens</span>
                                                        </div>
                                                        <div style="width: 100%; height: 8px; background-color: <?= $trackColor ?>; border-radius: 999px; overflow: hidden;">
                                                            <div style="width: <?= esc((string) min(100, max($isOverdue && $percent === 0 ? 100 : $percent, $percent))) ?>%; height: 100%; background-color: <?= $barColor ?>; border-radius: 999px; transition: width 0.25s ease;"></div>
                                                        </div>
                                                        <?php if ($isOverdue): ?>
                                                            <span class="overdue-tag">! Prazo vencido</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="progress-box <?= $isOverdue ? 'overdue' : '' ?>">
                                                        <strong style="<?= $isOverdue ? 'color: #b45309;' : '' ?>"><?= esc((string) $attCount) ?></strong> arquivo(s)
                                                        <?php if ($isOverdue): ?>
                                                            <div><span class="overdue-tag">! Prazo vencido</span></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="actions-cell">
                                                    <a href="<?= site_url('accountability/' . $accId) ?>" class="btn-open">
                                                        Abrir
                                                    </a>
                                                    <?php if ($attCount > 0): ?>
                                                        <a href="<?= site_url('accountability/' . $accId . '/print-pdf') ?>" target="_blank" class="btn-open" style="background-color: #334155;" title="Juntar todos os arquivos anexados em um único PDF para impressão">
                                                            🖨️ PDF Único
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php /* Relatório com IA temporariamente desabilitado na interface
                                                    <a href="<?= site_url('accountability/' . $accId . '/report') ?>" class="btn-open" style="background-color: #1e3a8a;">
                                                        Ver Relatório
                                                    </a>
                                                    */ ?>

                                                    <?php if ($isAdmin): ?>
                                                        <form
                                                            action="<?= site_url('accountability/' . $accId . '/delete') ?>"
                                                            method="post"
                                                            onsubmit="return confirm('Deseja realmente excluir esta prestação de contas?');">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn-delete">Excluir</button>
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
                </div>
            </main>
        </div>
    </div>

    <script>
        (function() {
            const accSearchInput = document.getElementById('accountabilitySearch');
            const tbody = document.querySelector('#accountabilitiesTable tbody');
            const allRows = Array.from(document.querySelectorAll('#accountabilitiesTable tbody tr')).filter(
                r => !r.querySelector('.empty-state')
            );
            const sortableHeaders = Array.from(document.querySelectorAll('#accountabilitiesTable thead th.sortable'));

            let sortCol = null;
            let sortDir = 'asc';
            let sortType = 'text';

            if (accSearchInput) {
                accSearchInput.addEventListener('input', function() {
                    const term = this.value.toLowerCase();
                    allRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(term) ? '' : 'none';
                    });
                });
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