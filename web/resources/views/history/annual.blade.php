<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Anual - Prestação de Contas</title>
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
            padding: 1.5rem 2rem;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .page-header h1 {
            font-size: 1.45rem;
            color: #0f172a;
        }

        .page-header p {
            color: #64748b;
            font-size: 0.88rem;
            margin-top: 0.2rem;
        }

        .year-pills {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .year-pill {
            padding: 0.42rem 0.9rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            color: #475569;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            transition: all 0.15s ease;
        }

        .year-pill:hover {
            border-color: #dd4814;
            color: #dd4814;
        }

        .year-pill.active {
            background: #dd4814;
            border-color: #dd4814;
            color: #ffffff;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            margin-bottom: 1.35rem;
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.2rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }

        .kpi-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .kpi-value {
            font-size: 1.45rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 0.35rem;
        }

        .kpi-sub {
            font-size: 0.76rem;
            color: #64748b;
            margin-top: 0.2rem;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }

        .card-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            flex-wrap: wrap;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .search-input {
            width: 100%;
            max-width: 340px;
            padding: 0.52rem 0.85rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.86rem;
            color: #0f172a;
        }

        .search-input:focus {
            outline: none;
            border-color: #dd4814;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .sheet-table th,
        .sheet-table td {
            border: 1px solid #e2e8f0;
        }

        .sheet-table thead th {
            background: #f1f5f9;
            color: #334155;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.65rem 0.85rem;
            white-space: nowrap;
        }

        .sheet-table tbody td {
            padding: 0.6rem 0.85rem;
            font-size: 0.84rem;
            color: #1e293b;
            vertical-align: middle;
        }

        .sheet-table tbody tr:nth-child(even) {
            background: #fafbfc;
        }

        .sheet-table tbody tr:hover {
            background: #fff7ed;
        }

        .sheet-table tfoot td {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.84rem;
            padding: 0.7rem 0.85rem;
            border-color: #1e293b;
            white-space: nowrap;
        }

        .sheet-row-num {
            text-align: center;
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            font-size: 0.76rem;
            width: 42px;
        }

        .progress-track {
            width: 110px;
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .progress-fill {
            height: 100%;
            background: #16a34a;
            border-radius: 999px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .badge-ok {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warn {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-muted {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-sm {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.7rem;
            border-radius: 7px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            background: #ffffff;
            transition: all 0.15s ease;
        }

        .btn-sm:hover {
            border-color: #dd4814;
            color: #dd4814;
        }

        .btn-sm.primary {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        .btn-sm.primary:hover {
            background: #1e293b;
            color: #ffffff;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: #64748b;
        }

        .years-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.15rem;
            margin-bottom: 1.5rem;
        }

        .year-folder-card {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.25rem 1.35rem;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .year-folder-card:hover {
            transform: translateY(-2px);
            border-color: #dd4814;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .year-folder-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .year-folder-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0f172a;
        }

        .year-folder-badge {
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .year-folder-stats {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            font-size: 0.82rem;
            color: #475569;
        }

        .year-folder-cta {
            margin-top: 0.25rem;
            padding-top: 0.65rem;
            border-top: 1px solid #f1f5f9;
            font-size: 0.8rem;
            font-weight: 700;
            color: #dd4814;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
                'pageTitle' => 'Histórico Anual',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <div>
                        <h1>
                            <?= $selectedYear !== null ? 'Histórico Anual — ' . esc((string) $selectedYear) : 'Histórico Anual de Prestações de Contas' ?>
                        </h1>
                        <p>
                            <?= $selectedYear !== null
                                ? 'Todos os registros de prestação de contas cadastrados no ano de ' . esc((string) $selectedYear) . '.'
                                : 'Selecione um ano abaixo para abrir a subpágina com todos os registros daquele ano. Novos anos são criados automaticamente.' ?>
                        </p>
                    </div>

                    <div class="year-pills">
                        <a href="<?= site_url('historico-anual') ?>" class="year-pill <?= $selectedYear === null ? 'active' : '' ?>">
                            <?= $selectedYear !== null ? '← Todos os Anos' : 'Visão por Anos' ?>
                        </a>
                        <?php foreach ($availableYears as $yr): ?>
                            <a href="<?= site_url('historico-anual/' . $yr) ?>" class="year-pill <?= $selectedYear === $yr ? 'active' : '' ?>">
                                <?= esc((string) $yr) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-label">Prestações <?= $selectedYear !== null ? 'em ' . esc((string) $selectedYear) : '(Todos os Anos)' ?></div>
                        <div class="kpi-value"><?= esc((string) ($kpis['total_accountabilities'] ?? 0)) ?></div>
                        <div class="kpi-sub"><?= esc((string) ($kpis['completed_count'] ?? 0)) ?> totalmente confirmada(s)</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-label">Documentos Anexados</div>
                        <div class="kpi-value"><?= esc((string) ($kpis['total_files'] ?? 0)) ?></div>
                        <div class="kpi-sub">Total de arquivos no período</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-label">Progresso Médio</div>
                        <div class="kpi-value"><?= esc((string) ($kpis['avg_progress'] ?? 0)) ?>%</div>
                        <div class="kpi-sub">Itens enviados ou confirmados</div>
                    </div>

                    <?php /* Modo Planilha ocultado
                    <div class="kpi-card">
                        <div class="kpi-label">Despesas (Planilha)</div>
                        <div class="kpi-value" style="color: #b91c1c;">
                            R$ <?= number_format((float) ($kpis['total_despesas'] ?? 0), 2, ',', '.') ?>
                        </div>
                        <div class="kpi-sub">
                            Receitas: R$ <?= number_format((float) ($kpis['total_receitas'] ?? 0), 2, ',', '.') ?>
                        </div>
                    </div>
                    */ ?>
                </div>

                <div class="card">
                    <div class="card-toolbar">
                        <strong style="font-size: 0.92rem; color: #0f172a;">
                            📊 Histórico Consolidado — <?= $selectedYear !== null ? 'Exercício ' . esc((string) $selectedYear) : 'Todos os Anos' ?> (<?= count($rows) ?>)
                        </strong>
                        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                            <input type="text" id="annualSearch" class="search-input" placeholder="Filtrar por ano, título, data ou situação...">
                            <button type="button" id="exportAnnualCsvBtn" class="btn-sm primary" style="padding: 0.5rem 0.9rem; cursor: pointer;">
                                ⬇ Exportar CSV (Excel)
                            </button>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="annualTable" class="sheet-table">
                            <thead>
                                <tr>
                                    <th style="text-align:center;">#</th>
                                    <th>Ano</th>
                                    <th>Registro</th>
                                    <th>Prestação de Contas</th>
                                    <th>Prazo</th>
                                    <th>Progresso</th>
                                    <th>Anexos</th>
                                    <th>Receitas (R$)</th>
                                    <th>Despesas (R$)</th>
                                    <th>Saldo (R$)</th>
                                    <th>Situação</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="12" class="empty-state">
                                            Nenhuma prestação de contas encontrada para o período selecionado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $idx => $r): ?>
                                        <?php $saldoRow = (float) $r['receitas'] - (float) $r['despesas']; ?>
                                        <tr>
                                            <td class="sheet-row-num"><?= $idx + 1 ?></td>
                                            <td style="white-space: nowrap;"><strong><?= esc((string) $r['year']) ?></strong></td>
                                            <td style="white-space: nowrap;"><?= esc($r['registered_at']) ?></td>
                                            <td>
                                                <div style="font-weight: 700; color: #0f172a;"><?= esc($r['title']) ?></div>
                                                <?php if ($r['description'] !== ''): ?>
                                                    <div style="font-size: 0.76rem; color: #64748b; margin-top: 2px;">
                                                        <?= esc(mb_substr($r['description'], 0, 90)) ?><?= mb_strlen($r['description']) > 90 ? '...' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="white-space: nowrap;"><?= esc($r['deadline']) ?></td>
                                            <td>
                                                <div class="progress-track">
                                                    <div class="progress-fill" style="width: <?= (int) $r['progress'] ?>%;"></div>
                                                </div>
                                                <div style="font-size: 0.74rem; color: #64748b;">
                                                    <?= (int) $r['progress'] ?>% (<?= (int) $r['confirmed'] ?>/<?= (int) $r['total_items'] ?>)
                                                </div>
                                            </td>
                                            <td style="text-align:center;"><strong><?= (int) $r['files_count'] ?></strong></td>
                                            <td style="white-space: nowrap; color: #15803d; font-weight: 600;">
                                                R$ <?= number_format((float) $r['receitas'], 2, ',', '.') ?>
                                            </td>
                                            <td style="white-space: nowrap; color: #b91c1c; font-weight: 600;">
                                                R$ <?= number_format((float) $r['despesas'], 2, ',', '.') ?>
                                            </td>
                                            <td style="white-space: nowrap; font-weight: 700; color: <?= $saldoRow >= 0 ? '#0f172a' : '#b91c1c' ?>;">
                                                R$ <?= number_format($saldoRow, 2, ',', '.') ?>
                                            </td>
                                            <td style="white-space: nowrap;">
                                                <?php if ($r['is_paused']): ?>
                                                    <span class="badge badge-muted">Pausada</span>
                                                <?php else: ?>
                                                    <span class="badge badge-ok">Finalizada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="white-space: nowrap;">
                                                <div style="display: inline-flex; gap: 0.35rem;">
                                                    <a href="<?= site_url('accountability/' . $r['id']) ?>" class="btn-sm primary">Abrir</a>
                                                    <a href="<?= site_url('accountability/' . $r['id'] . '/print-pdf') ?>" target="_blank" class="btn-sm">PDF</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (! empty($rows)): ?>
                                <?php $saldoTotal = (float) ($kpis['total_receitas'] ?? 0) - (float) ($kpis['total_despesas'] ?? 0); ?>
                                <tfoot>
                                    <tr>
                                        <td colspan="6">TOTAL GERAL (<?= count($rows) ?> registro<?= count($rows) === 1 ? '' : 's' ?>)</td>
                                        <td style="text-align:center;"><?= (int) ($kpis['total_files'] ?? 0) ?></td>
                                        <td>R$ <?= number_format((float) ($kpis['total_receitas'] ?? 0), 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float) ($kpis['total_despesas'] ?? 0), 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($saldoTotal, 2, ',', '.') ?></td>
                                        <td colspan="2">Progresso médio: <?= (int) ($kpis['avg_progress'] ?? 0) ?>%</td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function() {
            const search = document.getElementById('annualSearch');
            const rows = Array.from(document.querySelectorAll('#annualTable tbody tr')).filter(r => !r.querySelector('.empty-state'));
            if (search) {
                search.addEventListener('input', function() {
                    const q = this.value.trim().toLowerCase();
                    rows.forEach(tr => {
                        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
                    });
                });
            }

            const exportBtn = document.getElementById('exportAnnualCsvBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    const table = document.getElementById('annualTable');
                    if (!table) return;
                    const csvLines = [];
                    const headerCells = Array.from(table.querySelectorAll('thead th')).slice(0, -1);
                    csvLines.push(headerCells.map(th => '"' + th.textContent.trim().replace(/"/g, '""') + '"').join(';'));

                    rows.forEach(tr => {
                        if (tr.style.display === 'none') return;
                        const cells = Array.from(tr.querySelectorAll('td')).slice(0, -1);
                        csvLines.push(cells.map(td => '"' + td.textContent.replace(/\s+/g, ' ').trim().replace(/"/g, '""') + '"').join(';'));
                    });

                    const blob = new Blob(['\uFEFF' + csvLines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'historico_anual_<?= $selectedYear !== null ? (int) $selectedYear : 'todos_os_anos' ?>.csv';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);
                });
            }
        })();
    </script>
</body>

</html>
