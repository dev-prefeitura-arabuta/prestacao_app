<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilha de Prestação de Contas</title>
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
        .container { width: 100%; max-width: 100%; padding: 1.5rem 2rem; }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .page-header h1 { font-size: 1.45rem; color: #0f172a; }
        .page-header p { color: #64748b; font-size: 0.88rem; margin-top: 0.2rem; }

        .toolbar-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.15rem;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .selector-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            min-width: 300px;
            flex: 1;
            max-width: 520px;
        }
        .selector-group label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .selector-group select {
            padding: 0.55rem 0.8rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.88rem;
            color: #0f172a;
            background: #ffffff;
            outline: none;
        }
        .selector-group select:focus {
            border-color: #dd4814;
            box-shadow: 0 0 0 3px rgba(221, 72, 20, 0.12);
        }

        .summary-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 0.85rem;
            margin-bottom: 1.15rem;
        }
        .summary-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.9rem 1.15rem;
        }
        .summary-box span {
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.03em;
        }
        .summary-box strong {
            display: block;
            margin-top: 0.25rem;
            font-size: 1.25rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .summary-box.receita strong { color: #15803d; }
        .summary-box.despesa strong { color: #b91c1c; }
        .summary-box.saldo strong { color: #0f172a; }

        .sheet-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .sheet-toolbar {
            padding: 0.85rem 1.15rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .sheet-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 0.95rem;
            border-radius: 6px;
            font-size: 0.83rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn:hover { background: #f1f5f9; }
        .btn-primary {
            background: #dd4814;
            border-color: #dd4814;
            color: #ffffff;
        }
        .btn-primary:hover { background: #c23c0f; }
        .btn-dark {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }
        .btn-dark:hover { background: #1e293b; }

        .sheet-scroll {
            width: 100%;
            overflow-x: auto;
        }
        table.spreadsheet-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1080px;
        }
        table.spreadsheet-table thead th {
            background: #f1f5f9;
            color: #334155;
            font-size: 0.73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 0.65rem 0.6rem;
            border: 1px solid #cbd5e1;
            text-align: left;
            white-space: nowrap;
        }
        table.spreadsheet-table tbody td {
            border: 1px solid #e2e8f0;
            padding: 0;
            background: #ffffff;
            vertical-align: middle;
        }
        table.spreadsheet-table tbody tr:hover td {
            background: #fafcff;
        }
        .row-index-cell {
            width: 38px;
            text-align: center;
            font-size: 0.78rem;
            font-weight: 700;
            color: #64748b;
            background: #f8fafc !important;
            user-select: none;
        }
        .cell-input, .cell-select {
            width: 100%;
            height: 36px;
            padding: 0.35rem 0.55rem;
            border: none;
            background: transparent;
            font-size: 0.84rem;
            color: #0f172a;
            outline: none;
            font-family: inherit;
        }
        .cell-input:focus, .cell-select:focus {
            background: #eff6ff;
            box-shadow: inset 0 0 0 2px #2563eb;
        }
        .cell-amount {
            text-align: right;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 600;
        }
        .btn-del-row {
            width: 100%;
            height: 36px;
            border: none;
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .btn-del-row:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        @media (max-width: 768px) {
            .app-layout { flex-direction: column; }
            .container { padding: 1.25rem 1rem; }
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
                'pageTitle' => 'Planilha de Lançamentos',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                    <div>
                        <h1>
                            <?= $selectedAccId > 0 ? 'Planilha Detalhada — #' . esc((string) $selectedAccId) . ' ' . esc((string) ($selectedAcc['title'] ?? '')) : 'Lançamento em Modo Planilha' ?>
                        </h1>
                        <p>
                            <?= $selectedAccId > 0
                                ? 'Edite receitas, aportes e despesas na grade abaixo, gere gráficos ou extraia dados dos anexos com IA.'
                                : 'Lista resumida das prestações que já possuem documentos anexados. Clique em "Abrir Detalhado" para visualizar e editar a tabela completa.' ?>
                        </p>
                    </div>
                    <?php if ($selectedAccId > 0): ?>
                        <a href="<?= site_url('planilha') ?>" class="btn">
                            <i class="fa-solid fa-arrow-left"></i> Voltar para Lista Resumida
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($selectedAccId === 0): ?>
                    <!-- Modo Lista Resumida (somente prestações com anexo) -->
                    <div class="sheet-card">
                        <div class="sheet-toolbar">
                            <strong style="font-size: 0.94rem; color: #0f172a;">
                                Prestações com Anexos Disponíveis (<?= count($accountabilities ?? []) ?>)
                            </strong>
                            <input
                                type="text"
                                id="summaryListSearch"
                                placeholder="Filtrar prestação por título ou código..."
                                style="padding: 0.48rem 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; width: 100%; max-width: 320px;">
                        </div>

                        <div class="sheet-scroll">
                            <table class="spreadsheet-table" id="summaryListTable">
                                <thead>
                                    <tr>
                                        <th style="width: 65px;">Cód.</th>
                                        <th>Prestação de Contas</th>
                                        <th style="width: 105px; text-align: center;">Anexos</th>
                                        <th style="width: 115px; text-align: center;">Lançamentos</th>
                                        <th style="width: 145px; text-align: right;">Receitas + Aportes</th>
                                        <th style="width: 135px; text-align: right;">Despesas</th>
                                        <th style="width: 135px; text-align: right;">Saldo</th>
                                        <th style="width: 155px; text-align: center;">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($accountabilities)): ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                                                Nenhuma prestação de contas possui documentos anexados ainda. Anexe ao menos um documento em uma prestação para ela aparecer aqui.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($accountabilities as $acc): ?>
                                            <?php
                                            $entradasAcc = (float) ($acc['receitas'] ?? 0) + (float) ($acc['aportes'] ?? 0);
                                            $despAcc     = (float) ($acc['despesas'] ?? 0);
                                            $saldoAcc    = (float) ($acc['saldo'] ?? 0);
                                            ?>
                                            <tr class="js-summary-row">
                                                <td style="padding: 0.75rem 0.85rem; font-weight: 700; color: #64748b;">#<?= esc((string) $acc['id']) ?></td>
                                                <td style="padding: 0.75rem 0.85rem;">
                                                    <div style="font-weight: 700; color: #0f172a;"><?= esc((string) $acc['title']) ?></div>
                                                    <?php if (! empty($acc['deadline'])): ?>
                                                        <div style="font-size: 0.76rem; color: #64748b; margin-top: 2px;">
                                                            Prazo: <?= esc(date('d/m/Y', strtotime((string) $acc['deadline']))) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem 0.85rem; text-align: center;">
                                                    <span style="display:inline-block;padding:0.2rem 0.6rem;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-weight:700;font-size:0.78rem;">
                                                        <?= (int) ($acc['attachments_count'] ?? 0) ?> arq.
                                                    </span>
                                                </td>
                                                <td style="padding: 0.75rem 0.85rem; text-align: center; font-weight: 700; color: #334155;">
                                                    <?= (int) ($acc['entries_count'] ?? 0) ?>
                                                </td>
                                                <td style="padding: 0.75rem 0.85rem; text-align: right; color: #15803d; font-weight: 600; white-space: nowrap;">
                                                    R$ <?= number_format($entradasAcc, 2, ',', '.') ?>
                                                </td>
                                                <td style="padding: 0.75rem 0.85rem; text-align: right; color: #b91c1c; font-weight: 600; white-space: nowrap;">
                                                    R$ <?= number_format($despAcc, 2, ',', '.') ?>
                                                </td>
                                                <td style="padding: 0.75rem 0.85rem; text-align: right; font-weight: 700; white-space: nowrap; color: <?= $saldoAcc < 0 ? '#b91c1c' : '#0f172a' ?>;">
                                                    R$ <?= number_format($saldoAcc, 2, ',', '.') ?>
                                                </td>
                                                <td style="padding: 0.75rem 0.85rem; text-align: center;">
                                                    <a href="<?= site_url('planilha?accountability_id=' . $acc['id']) ?>" class="btn btn-primary" style="padding: 0.42rem 0.85rem; font-size: 0.8rem;">
                                                        Abrir Detalhado →
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                <!-- Seletor de Prestação de Contas (Modo Detalhado) -->
                <div class="toolbar-card">
                    <form method="get" action="<?= site_url('planilha') ?>" class="selector-group">
                        <label for="accountability_id">Prestação de Contas Selecionada</label>
                        <select id="accountability_id" name="accountability_id" onchange="this.form.submit()">
                            <?php if (empty($accountabilities)): ?>
                                <option value="<?= esc((string) $selectedAccId) ?>">#<?= esc((string) $selectedAccId) ?> — <?= esc((string) ($selectedAcc['title'] ?? '')) ?></option>
                            <?php else: ?>
                                <?php foreach ($accountabilities as $acc): ?>
                                    <option value="<?= esc((string) $acc['id']) ?>" <?= (int) $acc['id'] === (int) $selectedAccId ? 'selected' : '' ?>>
                                        #<?= esc((string) $acc['id']) ?> — <?= esc($acc['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </form>

                    <?php if ($selectedAccId > 0): ?>
                        <div class="sheet-actions">
                            <form method="post" action="<?= site_url('planilha/extract-ai') ?>" id="extractAiForm" style="display:inline-flex;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accountability_id" value="<?= esc((string) $selectedAccId) ?>">
                                <button type="submit" class="btn btn-primary" id="btnExtractAi" title="Lê todos os documentos anexados (PDF + OCR) e preenche receitas, aportes e despesas com IA">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> Preencher com IA (Anexos)
                                </button>
                            </form>
                            <button type="button" class="btn btn-dark" id="btnToggleCharts">
                                <i class="fa-solid fa-chart-column"></i> Gerar Gráfico
                            </button>
                            <button type="button" class="btn" id="btnExportCsv">
                                <i class="fa-solid fa-file-csv"></i> Exportar CSV
                            </button>
                            <a href="<?= site_url('accountability/' . $selectedAccId) ?>" class="btn">
                                <i class="fa-solid fa-folder-open"></i> Abrir Checklist
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Resumo Financeiro em Tempo Real -->
                <div class="summary-bar">
                    <div class="summary-box receita">
                        <span>Total Receitas</span>
                        <strong id="sumReceitas">R$ 0,00</strong>
                    </div>
                    <div class="summary-box" style="border-left: 4px solid #2563eb;">
                        <span>Total Aportes</span>
                        <strong id="sumAportes" style="color: #1d4ed8;">R$ 0,00</strong>
                    </div>
                    <div class="summary-box despesa">
                        <span>Total Despesas</span>
                        <strong id="sumDespesas">R$ 0,00</strong>
                    </div>
                    <div class="summary-box saldo">
                        <span>Saldo (Receitas + Aportes - Despesas)</span>
                        <strong id="sumSaldo">R$ 0,00</strong>
                    </div>
                    <div class="summary-box">
                        <span>Linhas Lançadas</span>
                        <strong id="sumRows">0</strong>
                    </div>
                </div>

                <!-- Painel de Gráficos da Planilha (abre ao clicar em "Gerar Gráfico") -->
                <div id="chartsPanel" style="display: none; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 2px 6px rgba(15,23,42,0.05);">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <strong style="font-size: 1rem; color: #0f172a;">📊 Gráficos Financeiros da Planilha</strong>
                            <div style="font-size: 0.78rem; color: #64748b; margin-top: 2px;">Atualizados automaticamente conforme os lançamentos da grade abaixo.</div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button type="button" class="btn" id="btnDownloadBarPng" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;">
                                <i class="fa-solid fa-download"></i> Baixar Barras (PNG)
                            </button>
                            <button type="button" class="btn" id="btnDownloadPiePng" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;">
                                <i class="fa-solid fa-download"></i> Baixar Distribuição (PNG)
                            </button>
                            <button type="button" class="btn" id="btnCloseCharts" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;">
                                ✕ Fechar
                            </button>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; align-items: center;">
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem;">
                            <div style="font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 0.65rem;">Receitas × Despesas × Saldo (R$)</div>
                            <div style="position: relative; height: 260px;">
                                <canvas id="sheetBarChart"></canvas>
                            </div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem;">
                            <div style="font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 0.65rem;">Distribuição de Despesas por Fornecedor</div>
                            <div style="position: relative; height: 260px;">
                                <canvas id="sheetDoughnutChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                    <form method="post" action="<?= site_url('planilha/save') ?>" id="spreadsheetForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accountability_id" value="<?= esc((string) $selectedAccId) ?>">

                        <div class="sheet-card">
                            <div class="sheet-toolbar">
                                <div class="sheet-actions">
                                    <button type="button" class="btn btn-dark" id="btnAddRow">
                                        <i class="fa-solid fa-plus"></i> Adicionar Linha
                                    </button>
                                    <button type="button" class="btn" id="btnAddFiveRows">
                                        +5 Linhas
                                    </button>
                                </div>
                                <div class="sheet-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-floppy-disk"></i> Salvar Planilha
                                    </button>
                                </div>
                            </div>

                            <div class="sheet-scroll">
                                <table class="spreadsheet-table" id="spreadsheetTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 38px; text-align: center;">#</th>
                                            <th style="width: 130px;">Data</th>
                                            <th style="width: 135px;">Quesito / Item</th>
                                            <th style="width: 145px;">Nº Doc / NF / Empenho</th>
                                            <th style="width: 210px;">Fornecedor / Emitente</th>
                                            <th style="width: 145px;">CNPJ / CPF</th>
                                            <th style="min-width: 220px;">Descrição / Histórico</th>
                                            <th style="width: 110px;">Tipo</th>
                                            <th style="width: 135px; text-align: right;">Valor (R$)</th>
                                            <th style="width: 160px;">Observações</th>
                                            <th style="width: 40px; text-align: center;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="sheetBody">
                                        <?php
                                        $rowsToRender = ! empty($entries) ? $entries : [[], [], []];
                                        foreach ($rowsToRender as $idx => $r):
                                            $amtVal = isset($r['amount']) && (float) $r['amount'] != 0.0
                                                ? number_format((float) $r['amount'], 2, ',', '.')
                                                : '';
                                        ?>
                                            <tr class="js-sheet-row">
                                                <td class="row-index-cell"><?= $idx + 1 ?></td>
                                                <td>
                                                    <input type="hidden" name="rows[<?= $idx ?>][attachment_id]" value="<?= esc((string) ($r['attachment_id'] ?? '')) ?>">
                                                    <input type="date" name="rows[<?= $idx ?>][entry_date]" class="cell-input" value="<?= esc((string) ($r['entry_date'] ?? '')) ?>">
                                                </td>
                                                <td>
                                                    <select name="rows[<?= $idx ?>][item_number]" class="cell-select">
                                                        <option value="">— Geral —</option>
                                                        <?php foreach (($shortItems ?? []) as $iNum => $iName): ?>
                                                            <option value="<?= esc((string) $iNum) ?>" <?= (int) ($r['item_number'] ?? 0) === (int) $iNum ? 'selected' : '' ?>>
                                                                <?= esc(str_pad((string) $iNum, 2, '0', STR_PAD_LEFT)) ?> - <?= esc($iName) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="rows[<?= $idx ?>][document_number]" class="cell-input" placeholder="Ex: NF 1234" value="<?= esc((string) ($r['document_number'] ?? '')) ?>">
                                                </td>
                                                <td>
                                                    <input type="text" name="rows[<?= $idx ?>][supplier_name]" class="cell-input" placeholder="Nome / Razão Social" value="<?= esc((string) ($r['supplier_name'] ?? '')) ?>">
                                                </td>
                                                <td>
                                                    <input type="text" name="rows[<?= $idx ?>][cnpj_cpf]" class="cell-input" placeholder="00.000.000/0000-00" value="<?= esc((string) ($r['cnpj_cpf'] ?? '')) ?>">
                                                </td>
                                                <td>
                                                    <input type="text" name="rows[<?= $idx ?>][description]" class="cell-input" placeholder="Descrição do lançamento..." value="<?= esc((string) ($r['description'] ?? '')) ?>">
                                                </td>
                                                <td>
                                                    <select name="rows[<?= $idx ?>][entry_type]" class="cell-select js-type-input">
                                                        <option value="despesa" <?= ($r['entry_type'] ?? 'despesa') === 'despesa' ? 'selected' : '' ?>>Despesa</option>
                                                        <option value="receita" <?= ($r['entry_type'] ?? '') === 'receita' ? 'selected' : '' ?>>Receita</option>
                                                        <option value="aporte" <?= ($r['entry_type'] ?? '') === 'aporte' ? 'selected' : '' ?>>Aporte</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="rows[<?= $idx ?>][amount]" class="cell-input cell-amount js-amount-input" placeholder="0,00" value="<?= esc($amtVal) ?>">
                                                </td>
                                                <td>
                                                    <input type="text" name="rows[<?= $idx ?>][notes]" class="cell-input" placeholder="Opcional" value="<?= esc((string) ($r['notes'] ?? '')) ?>">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn-del-row js-del-row" title="Remover linha">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #f8fafc; font-weight: 700; border-top: 2px solid #cbd5e1;">
                                            <td colspan="7" style="padding: 0.65rem 0.85rem; text-align: right; color: #334155; font-size: 0.82rem;">
                                                Receitas (<span id="footCountRec">0</span>): <span style="color:#15803d;" id="footReceitas">R$ 0,00</span>
                                                &nbsp;|&nbsp;
                                                Aportes (<span id="footCountAporte">0</span>): <span style="color:#1d4ed8;" id="footAportes">R$ 0,00</span>
                                            </td>
                                            <td style="padding: 0.65rem 0.75rem; font-size: 0.78rem; color: #b91c1c; white-space: nowrap;">
                                                Despesas (<span id="footCountDesp">0</span>)
                                            </td>
                                            <td style="padding: 0.65rem 0.75rem; font-size: 0.82rem; color: #b91c1c; text-align: right; white-space: nowrap;">
                                                - <span id="footDespesas">R$ 0,00</span>
                                            </td>
                                            <td colspan="2" style="padding: 0.65rem 0.75rem; font-size: 0.78rem; color: #475569; white-space: nowrap;">
                                                Execução: <strong id="footExecPct">0,0%</strong>
                                            </td>
                                        </tr>
                                        <tr style="background: #0f172a; color: #ffffff; font-weight: 800;">
                                            <td colspan="7" style="padding: 0.75rem 0.85rem; text-align: right; font-size: 0.86rem; letter-spacing: 0.02em;">
                                                CÁLCULO FINAL ((RECEITAS + APORTES) − DESPESAS):
                                            </td>
                                            <td style="padding: 0.75rem; font-size: 0.78rem; color: #cbd5e1; white-space: nowrap;">
                                                SALDO FINAL
                                            </td>
                                            <td id="footSaldoCell" style="padding: 0.75rem; text-align: right; font-size: 0.95rem; white-space: nowrap; color: #4ade80;">
                                                R$ 0,00
                                            </td>
                                            <td colspan="2" style="padding: 0.75rem; font-size: 0.78rem; color: #cbd5e1; white-space: nowrap;">
                                                <span id="footTotalRows">0</span> lançamento(s)
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const tbody = document.getElementById('sheetBody');
            const btnAdd = document.getElementById('btnAddRow');
            const btnAddFive = document.getElementById('btnAddFiveRows');
            const btnExportCsv = document.getElementById('btnExportCsv');
            const btnToggleCharts = document.getElementById('btnToggleCharts');
            const btnCloseCharts = document.getElementById('btnCloseCharts');
            const btnDownloadBarPng = document.getElementById('btnDownloadBarPng');
            const btnDownloadPiePng = document.getElementById('btnDownloadPiePng');
            const chartsPanel = document.getElementById('chartsPanel');
            const shortItems = <?= json_encode($shortItems ?? [], JSON_UNESCAPED_UNICODE) ?>;

            let nextIndex = tbody ? tbody.querySelectorAll('tr.js-sheet-row').length : 0;
            let barChartInstance = null;
            let doughnutChartInstance = null;

            function parseMoney(val) {
                if (!val) return 0;
                let s = String(val).replace(/[R$\s]/g, '');
                if (s.includes(',') && s.includes('.')) {
                    s = s.replace(/\./g, '').replace(',', '.');
                } else if (s.includes(',')) {
                    s = s.replace(',', '.');
                }
                const n = parseFloat(s);
                return isNaN(n) ? 0 : n;
            }

            function formatBRL(num) {
                return 'R$ ' + Number(num || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function recalcTotals() {
                if (!tbody) return;
                let rec = 0;
                let aporte = 0;
                let desp = 0;
                let countRec = 0;
                let countAporte = 0;
                let countDesp = 0;
                const expensesBySupplier = {};
                const rows = Array.from(tbody.querySelectorAll('tr.js-sheet-row'));

                rows.forEach((tr, i) => {
                    const idxCell = tr.querySelector('.row-index-cell');
                    if (idxCell) idxCell.textContent = String(i + 1);

                    const typeEl = tr.querySelector('.js-type-input');
                    const amtEl = tr.querySelector('.js-amount-input');
                    const supEl = tr.querySelector('input[name*="[supplier_name]"]');
                    const descEl = tr.querySelector('input[name*="[description]"]');
                    const val = parseMoney(amtEl ? amtEl.value : 0);

                    if (typeEl && typeEl.value === 'receita') {
                        rec += val;
                        if (val > 0) countRec++;
                    } else if (typeEl && typeEl.value === 'aporte') {
                        aporte += val;
                        if (val > 0) countAporte++;
                    } else {
                        desp += val;
                        if (val > 0) {
                            countDesp++;
                            const label = (supEl && supEl.value.trim()) || (descEl && descEl.value.trim()) || ('Despesa #' + (i + 1));
                            const shortLbl = label.length > 32 ? label.slice(0, 32) + '…' : label;
                            expensesBySupplier[shortLbl] = (expensesBySupplier[shortLbl] || 0) + val;
                        }
                    }
                });

                const entradas = rec + aporte;
                const saldo = entradas - desp;
                const execPct = entradas > 0 ? ((desp / entradas) * 100) : (desp > 0 ? 100 : 0);

                document.getElementById('sumReceitas').textContent = formatBRL(rec);
                const elSumAp = document.getElementById('sumAportes');
                if (elSumAp) elSumAp.textContent = formatBRL(aporte);
                document.getElementById('sumDespesas').textContent = formatBRL(desp);
                document.getElementById('sumSaldo').textContent = formatBRL(saldo);
                document.getElementById('sumRows').textContent = String(rows.length);

                const elFootRec = document.getElementById('footReceitas');
                const elFootAp = document.getElementById('footAportes');
                const elFootDesp = document.getElementById('footDespesas');
                const elFootCountRec = document.getElementById('footCountRec');
                const elFootCountAp = document.getElementById('footCountAporte');
                const elFootCountDesp = document.getElementById('footCountDesp');
                const elFootExec = document.getElementById('footExecPct');
                const elFootSaldo = document.getElementById('footSaldoCell');
                const elFootRows = document.getElementById('footTotalRows');

                if (elFootRec) elFootRec.textContent = formatBRL(rec);
                if (elFootAp) elFootAp.textContent = formatBRL(aporte);
                if (elFootDesp) elFootDesp.textContent = formatBRL(desp);
                if (elFootCountRec) elFootCountRec.textContent = String(countRec);
                if (elFootCountAp) elFootCountAp.textContent = String(countAporte);
                if (elFootCountDesp) elFootCountDesp.textContent = String(countDesp);
                if (elFootExec) elFootExec.textContent = execPct.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
                if (elFootRows) elFootRows.textContent = String(rows.length);
                if (elFootSaldo) {
                    elFootSaldo.textContent = formatBRL(saldo);
                    elFootSaldo.style.color = saldo < 0 ? '#f87171' : (saldo === 0 ? '#93c5fd' : '#4ade80');
                }

                if (chartsPanel && chartsPanel.style.display !== 'none') {
                    renderCharts(rec, aporte, desp, saldo, expensesBySupplier);
                }
            }

            function renderCharts(rec, aporte, desp, saldo, expensesBySupplier) {
                if (typeof Chart === 'undefined') return;

                const barCanvas = document.getElementById('sheetBarChart');
                const pieCanvas = document.getElementById('sheetDoughnutChart');

                if (barCanvas) {
                    if (barChartInstance) barChartInstance.destroy();
                    barChartInstance = new Chart(barCanvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: ['Receitas', 'Aportes', 'Despesas', 'Saldo'],
                            datasets: [{
                                label: 'Valor (R$)',
                                data: [rec, aporte, desp, saldo],
                                backgroundColor: ['#16a34a', '#2563eb', '#dc2626', saldo >= 0 ? '#0ea5e9' : '#f59e0b'],
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => formatBRL(ctx.parsed.y)
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    ticks: {
                                        callback: (v) => 'R$ ' + Number(v).toLocaleString('pt-BR')
                                    }
                                }
                            }
                        }
                    });
                }

                if (pieCanvas) {
                    if (doughnutChartInstance) doughnutChartInstance.destroy();
                    const labels = Object.keys(expensesBySupplier);
                    const values = Object.values(expensesBySupplier);
                    const palette = ['#dd4814', '#2563eb', '#16a34a', '#f59e0b', '#7c3aed', '#0891b2', '#db2777', '#475569'];

                    doughnutChartInstance = new Chart(pieCanvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: labels.length ? labels : ['Sem despesas lançadas'],
                            datasets: [{
                                data: values.length ? values : [1],
                                backgroundColor: values.length ? labels.map((_, i) => palette[i % palette.length]) : ['#e2e8f0'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => values.length ? (ctx.label + ': ' + formatBRL(ctx.parsed)) : 'Sem despesas'
                                    }
                                }
                            }
                        }
                    });
                }
            }

            if (btnToggleCharts && chartsPanel) {
                btnToggleCharts.addEventListener('click', function () {
                    const opening = chartsPanel.style.display === 'none';
                    chartsPanel.style.display = opening ? 'block' : 'none';
                    if (opening) {
                                    recalcTotals();
                        chartsPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            }
            if (btnCloseCharts && chartsPanel) {
                btnCloseCharts.addEventListener('click', function () {
                    chartsPanel.style.display = 'none';
                });
            }
            if (btnDownloadBarPng) {
                btnDownloadBarPng.addEventListener('click', function () {
                    if (!barChartInstance) return;
                    const a = document.createElement('a');
                    a.href = barChartInstance.toBase64Image('image/png', 1);
                    a.download = 'grafico_receitas_despesas_prestacao_<?= esc((string) $selectedAccId) ?>.png';
                    a.click();
                });
            }
            if (btnDownloadPiePng) {
                btnDownloadPiePng.addEventListener('click', function () {
                    if (!doughnutChartInstance) return;
                    const a = document.createElement('a');
                    a.href = doughnutChartInstance.toBase64Image('image/png', 1);
                    a.download = 'grafico_despesas_fornecedor_prestacao_<?= esc((string) $selectedAccId) ?>.png';
                    a.click();
                });
            }

            function buildItemOptions() {
                let html = '<option value="">— Geral —</option>';
                for (const [k, v] of Object.entries(shortItems)) {
                    const pad = String(k).padStart(2, '0');
                    html += `<option value="${k}">${pad} - ${v}</option>`;
                }
                return html;
            }

            function addRow() {
                if (!tbody) return;
                const idx = nextIndex++;
                const tr = document.createElement('tr');
                tr.className = 'js-sheet-row';
                tr.innerHTML = `
                    <td class="row-index-cell"></td>
                    <td><input type="date" name="rows[${idx}][entry_date]" class="cell-input"></td>
                    <td><select name="rows[${idx}][item_number]" class="cell-select">${buildItemOptions()}</select></td>
                    <td><input type="text" name="rows[${idx}][document_number]" class="cell-input" placeholder="Ex: NF 1234"></td>
                    <td><input type="text" name="rows[${idx}][supplier_name]" class="cell-input" placeholder="Nome / Razão Social"></td>
                    <td><input type="text" name="rows[${idx}][cnpj_cpf]" class="cell-input" placeholder="00.000.000/0000-00"></td>
                    <td><input type="text" name="rows[${idx}][description]" class="cell-input" placeholder="Descrição do lançamento..."></td>
                    <td>
                        <select name="rows[${idx}][entry_type]" class="cell-select js-type-input">
                            <option value="despesa" selected>Despesa</option>
                            <option value="receita">Receita</option>
                            <option value="aporte">Aporte</option>
                        </select>
                    </td>
                    <td><input type="text" name="rows[${idx}][amount]" class="cell-input cell-amount js-amount-input" placeholder="0,00"></td>
                    <td><input type="text" name="rows[${idx}][notes]" class="cell-input" placeholder="Opcional"></td>
                    <td><button type="button" class="btn-del-row js-del-row" title="Remover linha"><i class="fa-solid fa-trash"></i></button></td>
                `;
                tbody.appendChild(tr);
                            recalcTotals();
            }

            if (btnAdd) btnAdd.addEventListener('click', addRow);
            if (btnAddFive) btnAddFive.addEventListener('click', function () {
                for (let i = 0; i < 5; i++) addRow();
            });

            if (tbody) {
                tbody.addEventListener('input', function (e) {
                    if (e.target.classList.contains('js-amount-input')) {
                                    recalcTotals();
                    }
                });
                tbody.addEventListener('change', function (e) {
                    if (e.target.classList.contains('js-type-input')) {
                                    recalcTotals();
                    }
                });
                tbody.addEventListener('click', function (e) {
                    const delBtn = e.target.closest('.js-del-row');
                    if (delBtn) {
                        const tr = delBtn.closest('tr');
                        if (tr) tr.remove();
                                    recalcTotals();
                    }
                });
            }

            if (btnExportCsv) {
                btnExportCsv.addEventListener('click', function () {
                    const rows = Array.from(tbody.querySelectorAll('tr.js-sheet-row'));
                    const headers = ['Data', 'Item', 'Documento', 'Fornecedor', 'CNPJ_CPF', 'Descricao', 'Tipo', 'Valor', 'Observacoes'];
                    const lines = [headers.join(';')];
                    rows.forEach(tr => {
                        const inputs = Array.from(tr.querySelectorAll('input, select'));
                        const vals = inputs.map(el => '"' + String(el.value || '').replace(/"/g, '""') + '"');
                        lines.push(vals.join(';'));
                    });
                    const blob = new Blob(["\uFEFF" + lines.join("\n")], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'planilha_prestacao_<?= esc((string) $selectedAccId) ?>.csv';
                    a.click();
                    URL.revokeObjectURL(url);
                });
            }

                        const extractAiForm = document.getElementById('extractAiForm');
            const btnExtractAi = document.getElementById('btnExtractAi');
            if (extractAiForm && btnExtractAi) {
                extractAiForm.addEventListener('submit', function () {
                    btnExtractAi.disabled = true;
                    btnExtractAi.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Extraindo com IA...';
                });
            }

            const summarySearch = document.getElementById('summaryListSearch');
            if (summarySearch) {
                const summaryRows = Array.from(document.querySelectorAll('#summaryListTable tbody tr.js-summary-row'));
                summarySearch.addEventListener('input', function () {
                    const q = this.value.trim().toLowerCase();
                    summaryRows.forEach(tr => {
                        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
                    });
                });
            }

            recalcTotals();
        })();
    </script>
</body>

</html>
