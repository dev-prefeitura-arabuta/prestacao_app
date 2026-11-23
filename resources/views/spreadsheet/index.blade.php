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
                <div class="page-header">
                    <div>
                        <h1>Lançamento em Modo Planilha</h1>
                        <p>Lance receitas, despesas, números de notas fiscais/empenhos e fornecedores em grade editável por prestação de contas.</p>
                    </div>
                </div>

                <!-- Seletor de Prestação de Contas -->
                <div class="toolbar-card">
                    <form method="get" action="<?= site_url('planilha') ?>" class="selector-group">
                        <label for="accountability_id">Prestação de Contas Selecionada</label>
                        <select id="accountability_id" name="accountability_id" onchange="this.form.submit()">
                            <?php if (empty($accountabilities)): ?>
                                <option value="0">Nenhuma prestação cadastrada</option>
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
                    <div class="summary-box despesa">
                        <span>Total Despesas</span>
                        <strong id="sumDespesas">R$ 0,00</strong>
                    </div>
                    <div class="summary-box saldo">
                        <span>Saldo (Receitas - Despesas)</span>
                        <strong id="sumSaldo">R$ 0,00</strong>
                    </div>
                    <div class="summary-box">
                        <span>Linhas Lançadas</span>
                        <strong id="sumRows">0</strong>
                    </div>
                </div>

                <?php if ($selectedAccId > 0): ?>
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
                                </table>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        (function () {
            const tbody = document.getElementById('sheetBody');
            const btnAdd = document.getElementById('btnAddRow');
            const btnAddFive = document.getElementById('btnAddFiveRows');
            const btnExportCsv = document.getElementById('btnExportCsv');
            const shortItems = <?= json_encode($shortItems ?? [], JSON_UNESCAPED_UNICODE) ?>;

            let nextIndex = tbody ? tbody.querySelectorAll('tr.js-sheet-row').length : 0;

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
                let desp = 0;
                const rows = Array.from(tbody.querySelectorAll('tr.js-sheet-row'));

                rows.forEach((tr, i) => {
                    const idxCell = tr.querySelector('.row-index-cell');
                    if (idxCell) idxCell.textContent = String(i + 1);

                    const typeEl = tr.querySelector('.js-type-input');
                    const amtEl = tr.querySelector('.js-amount-input');
                    const val = parseMoney(amtEl ? amtEl.value : 0);
                    if (typeEl && typeEl.value === 'receita') {
                        rec += val;
                    } else {
                        desp += val;
                    }
                });

                document.getElementById('sumReceitas').textContent = formatBRL(rec);
                document.getElementById('sumDespesas').textContent = formatBRL(desp);
                document.getElementById('sumSaldo').textContent = formatBRL(rec - desp);
                document.getElementById('sumRows').textContent = String(rows.length);
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

            recalcTotals();
        })();
    </script>
</body>

</html>
