<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório IA de Valores e Notas - <?= esc($accountability['title']) ?></title>
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
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .page-header h1 {
            font-size: 1.4rem;
            color: #0f172a;
        }

        .page-header p {
            color: #64748b;
            font-size: 0.88rem;
            margin-top: 0.25rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 1rem;
            border-radius: 6px;
            font-size: 0.84rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #0f172a;
            transition: all 0.15s ease;
        }

        .btn:hover {
            background-color: #f1f5f9;
        }

        .btn-primary {
            background-color: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #1e293b;
        }

        .btn-accent {
            background-color: #dd4814;
            border-color: #dd4814;
            color: #ffffff;
        }

        .btn-accent:hover {
            background-color: #c23c0f;
        }

        .btn:disabled {
            opacity: 0.65;
            cursor: wait;
        }

        /* KPIs Superiores */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.15rem 1.35rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .kpi-card.highlight {
            border-left: 4px solid #16a34a;
            background-color: #f0fdf4;
        }

        .kpi-card.info {
            border-left: 4px solid #2563eb;
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

        .kpi-card.highlight .kpi-value {
            color: #15803d;
        }

        .kpi-sub {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 0.2rem;
        }

        /* Card Principal da Tabela */
        .report-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .report-card-header {
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            background-color: #ffffff;
        }

        .report-card-header h2 {
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .search-input {
            padding: 0.5rem 0.85rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.85rem;
            width: 100%;
            max-width: 340px;
            outline: none;
        }

        .search-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.85rem 1.15rem;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        tbody td {
            padding: 0.95rem 1.15rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
            color: #334155;
            vertical-align: top;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        tfoot td {
            padding: 1rem 1.15rem;
            background-color: #f8fafc;
            border-top: 2px solid #cbd5e1;
            font-weight: 800;
            font-size: 0.92rem;
            color: #0f172a;
        }

        .num-badge {
            display: inline-block;
            padding: 0.22rem 0.55rem;
            border-radius: 5px;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            font-weight: 700;
            font-size: 0.8rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .value-cell {
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .value-cell.positive {
            color: #15803d;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.55rem;
            border-radius: 5px;
            font-size: 0.73rem;
            font-weight: 700;
        }

        .pill-ok {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .pill-warn {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .pill-pending {
            background-color: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .btn-mini {
            padding: 0.32rem 0.65rem;
            border-radius: 5px;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #0f172a;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-mini:hover {
            background-color: #f1f5f9;
        }

        .btn-mini.ai {
            background-color: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        .ai-progress-banner {
            display: none;
            background-color: #eff6ff;
            border: 1px solid #93c5fd;
            color: #1e3a8a;
            padding: 0.9rem 1.25rem;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .ai-progress-banner.visible {
            display: block;
        }

        @media print {
            .admin-sidebar,
            .top-navbar,
            .header-actions,
            .search-input,
            .no-print {
                display: none !important;
            }

            .container {
                padding: 0;
            }

            .report-card,
            .kpi-card {
                box-shadow: none;
                border: 1px solid #cbd5e1;
            }
        }
    </style>
</head>

<body>
    <?php
    $accId          = (int) $accountability['id'];
    $isOllamaOnline = ! empty($ollamaStatus['online']);
    ?>

    <div class="app-layout">
        <?= view('partials.sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials.navbar', [
                'pageTitle' => 'Relatório Analítico por IA',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <div>
                        <h1>Relatório de Documentos, Números e Valores (IA)</h1>
                        <p>
                            Planejamento: <strong><?= esc($accountability['title']) ?></strong>
                            <?php if (! empty($accountability['deadline'])): ?>
                                • Prazo: <?= esc(date('d/m/Y', strtotime((string) $accountability['deadline']))) ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="header-actions">
                        <?php if ($totalAttachments > 0): ?>
                            <button
                                type="button"
                                class="btn btn-accent"
                                id="btnAnalyzePending"
                                data-url="<?= site_url('accountability/' . $accId . '/analyze-all') ?>"
                                data-force="0">
                                🤖 Analisar Documentos Pendentes (<?= esc((string) $pendingAiCount) ?>)
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary"
                                id="btnReanalyzeAll"
                                data-url="<?= site_url('accountability/' . $accId . '/analyze-all') ?>"
                                data-force="1">
                                ↻ Reprocessar Todos com IA
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn" onclick="window.print()">
                            🖨️ Imprimir / Salvar PDF
                        </button>
                        <a href="<?= site_url('accountability/' . $accId) ?>" class="btn">
                            ← Voltar ao Checklist
                        </a>
                    </div>
                </div>

                <div class="ai-progress-banner" id="aiProgressBanner">
                    ⏳ O motor de IA (<strong><?= esc(($ollamaStatus['provider'] ?? 'IA') . ' — ' . ($ollamaStatus['model'] ?? '')) ?></strong>) está lendo os documentos e extraindo números de notas e valores financeiros. Aguarde a conclusão...
                </div>

                <!-- Indicadores Consolidados -->
                <div class="kpi-grid">
                    <div class="kpi-card highlight">
                        <div class="kpi-label">Valor Total Extraído dos Documentos</div>
                        <div class="kpi-value">R$ <?= esc(number_format((float) $totalValue, 2, ',', '.')) ?></div>
                        <div class="kpi-sub">Soma automática dos valores identificados pela IA</div>
                    </div>

                    <div class="kpi-card info">
                        <div class="kpi-label">Documentos e Notas Indexados</div>
                        <div class="kpi-value"><?= esc((string) $totalAttachments) ?></div>
                        <div class="kpi-sub"><?= esc((string) $analyzedCount) ?> analisado(s) • <?= esc((string) $pendingAiCount) ?> pendente(s) de leitura</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-label">Motor de IA Configurado</div>
                        <div class="kpi-value" style="font-size: 1.05rem; margin-top: 0.55rem;">
                            <?= $isOllamaOnline
                                ? '🟢 ' . esc(($ollamaStatus['provider'] ?? 'IA') . ' (' . ($ollamaStatus['model'] ?? '') . ')')
                                : '⚪ ' . esc($ollamaStatus['error'] ?? 'Aguardando Chave no .env') ?>
                        </div>
                        <div class="kpi-sub">Extração separada de Números e Valores (R$)</div>
                    </div>
                </div>

                <!-- Tabela Detalhada Separando Números e Valores -->
                <div class="report-card">
                    <div class="report-card-header">
                        <h2>Extração Individualizada de Notas, Números e Valores</h2>
                        <input
                            type="text"
                            id="reportSearchInput"
                            class="search-input"
                            placeholder="Filtrar por nº da nota, CNPJ, emitente, valor ou item...">
                    </div>

                    <div class="table-responsive">
                        <table id="reportTable">
                            <thead>
                                <tr>
                                    <th>Quesito / Item</th>
                                    <th>Arquivo Indexado</th>
                                    <th>Tipo de Documento</th>
                                    <th>Nº Nota / Empenho / Doc</th>
                                    <th>Emitente &amp; CNPJ/CPF</th>
                                    <th>Data Emissão</th>
                                    <th>Valor Extraído (R$)</th>
                                    <th>Parecer / Resumo da IA</th>
                                    <th class="no-print">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documentsEntries)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 2.5rem; color: #64748b;">
                                            Nenhum documento ou nota foi indexado neste planejamento até o momento.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($documentsEntries as $entry): ?>
                                        <?php
                                        $att       = $entry['attachment'];
                                        $attId     = (int) $att['id'];
                                        $itemNum   = (int) $entry['item_num'];
                                        $numPad    = str_pad((string) $itemNum, 2, '0', STR_PAD_LEFT);
                                        $ai        = $entry['ai'];
                                        $hasAi     = ($ai !== null);
                                        $docType   = trim((string) ($ai['document_type'] ?? ''));
                                        $docNumber = trim((string) ($ai['document_number'] ?? ''));
                                        $emitter   = trim((string) ($ai['emitter_name'] ?? ''));
                                        $cnpjCpf   = trim((string) ($ai['cnpj_cpf'] ?? ''));
                                        $issueDate = trim((string) ($ai['issue_date'] ?? ''));
                                        $val       = (float) ($ai['total_value'] ?? 0);
                                        $summary   = trim((string) ($ai['summary'] ?? ''));
                                        $compat    = trim((string) ($ai['compatibility'] ?? ''));

                                        if (in_array(mb_strtolower($docNumber), ['n/i', 'n/a', '—', '-'], true)) {
                                            $docNumber = '';
                                        }
                                        if (in_array(mb_strtolower($emitter), ['n/i', 'n/a', '—', '-'], true)) {
                                            $emitter = '';
                                        }
                                        if (in_array(mb_strtolower($cnpjCpf), ['n/i', 'n/a', '—', '-'], true)) {
                                            $cnpjCpf = '';
                                        }
                                        if (in_array(mb_strtolower($issueDate), ['n/i', 'n/a', '—', '-'], true)) {
                                            $issueDate = '';
                                        }
                                        ?>
                                        <tr class="js-report-row">
                                            <td>
                                                <strong><?= esc($numPad) ?>. <?= esc($entry['item_short']) ?></strong>
                                                <div style="font-size: 0.76rem; color: #64748b; max-width: 210px; margin-top: 0.15rem;">
                                                    <?= esc($entry['item_title']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; color: #0f172a; word-break: break-all; max-width: 200px;">
                                                    <?= esc($att['original_name']) ?>
                                                </div>
                                                <div style="font-size: 0.74rem; color: #64748b; margin-top: 0.15rem;">
                                                    <?= esc(strtoupper((string) $att['file_ext'])) ?> • <?= esc((string) round(((int) $att['file_size']) / 1024, 1)) ?> KB
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($hasAi && $docType !== ''): ?>
                                                    <span class="pill pill-ok"><?= esc($docType) ?></span>
                                                <?php else: ?>
                                                    <span class="pill pill-pending">Aguardando IA</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($hasAi && $docNumber !== ''): ?>
                                                    <span class="num-badge"><?= esc($docNumber) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($emitter !== ''): ?>
                                                    <div style="font-weight: 600; color: #0f172a;"><?= esc($emitter) ?></div>
                                                <?php endif; ?>
                                                <?php if ($cnpjCpf !== ''): ?>
                                                    <div style="font-size: 0.76rem; color: #475569; font-family: monospace; margin-top: 0.15rem;">
                                                        <?= esc($cnpjCpf) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="white-space: nowrap;">
                                                <?php if ($issueDate !== ''): ?>
                                                    <?= esc($issueDate) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="value-cell <?= $val > 0 ? 'positive' : '' ?>">
                                                <?php if ($val > 0): ?>
                                                    R$ <?= esc(number_format($val, 2, ',', '.')) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td style="max-width: 340px; line-height: 1.45;">
                                                <?php if ($hasAi): ?>
                                                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-bottom: 0.3rem;">
                                                        <?php if ($compat !== ''): ?>
                                                            <strong style="font-size: 0.75rem; color: #1e3a8a;"><?= esc($compat) ?></strong>
                                                        <?php endif; ?>
                                                        <?php if ($docNumber !== ''): ?>
                                                            <span style="font-size: 0.74rem; color: #0f172a; background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 4px;">Nº <?= esc($docNumber) ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($val > 0): ?>
                                                            <span style="font-size: 0.74rem; color: #15803d; background: #dcfce7; padding: 0.1rem 0.4rem; border-radius: 4px; font-weight: 700;">R$ <?= esc(number_format($val, 2, ',', '.')) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($summary !== ''): ?>
                                                        <div style="font-size: 0.8rem; color: #334155;"><?= esc($summary) ?></div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div style="font-size: 0.8rem; color: #64748b;">Clique em "Ler com IA" para resumir os dados deste documento.</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="no-print" style="white-space: nowrap;">
                                                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                                    <button
                                                        type="button"
                                                        class="btn-mini ai js-analyze-single"
                                                        data-url="<?= site_url('accountability/attachment/' . $attId . '/analyze') ?>">
                                                        🤖 <?= $hasAi ? 'Reler IA' : 'Ler com IA' ?>
                                                    </button>
                                                    <a
                                                        href="<?= site_url('accountability/attachment/' . $attId . '/preview') ?>"
                                                        target="_blank"
                                                        class="btn-mini">
                                                        Abrir Doc ↗
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (! empty($documentsEntries)): ?>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" style="text-align: right;">TOTAL CONSOLIDADO DOS DOCUMENTOS ANALISADOS:</td>
                                        <td class="value-cell positive">R$ <?= esc(number_format((float) $totalValue, 2, ',', '.')) ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Resumo de Valores Agrupado por Quesito/Item -->
                <?php if (! empty($totalsByItem)): ?>
                    <div class="report-card">
                        <div class="report-card-header">
                            <h2>Subtotal Financeiro por Quesito do Planejamento</h2>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Descrição do Quesito</th>
                                        <th>Subtotal Identificado (R$)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($totalsByItem as $itemNum => $subVal): ?>
                                        <tr>
                                            <td style="width: 80px;"><strong><?= esc(str_pad((string) $itemNum, 2, '0', STR_PAD_LEFT)) ?></strong></td>
                                            <td><?= esc(rtrim($enabledItems[$itemNum] ?? ("Item {$itemNum}"), ';.')) ?></td>
                                            <td class="value-cell <?= $subVal > 0 ? 'positive' : '' ?>">
                                                R$ <?= esc(number_format((float) $subVal, 2, ',', '.')) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        (function() {
            let csrfTokenName = '<?= esc('_token') ?>';
            let csrfHash = '<?= esc(csrf_token()) ?>';

            const searchInput = document.getElementById('reportSearchInput');
            const rows = Array.from(document.querySelectorAll('.js-report-row'));
            const banner = document.getElementById('aiProgressBanner');
            const btnPending = document.getElementById('btnAnalyzePending');
            const btnAll = document.getElementById('btnReanalyzeAll');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const term = this.value.toLowerCase().trim();
                    rows.forEach(r => {
                        r.style.display = (!term || r.textContent.toLowerCase().includes(term)) ? '' : 'none';
                    });
                });
            }

            function runBatchAnalysis(btn) {
                if (!btn) return;
                const url = btn.getAttribute('data-url');
                const force = btn.getAttribute('data-force') || '0';

                if (btnPending) btnPending.disabled = true;
                if (btnAll) btnAll.disabled = true;
                if (banner) banner.classList.add('visible');

                const formData = new FormData();
                formData.append('force', force);
                formData.append(csrfTokenName, csrfHash);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.csrf_hash) {
                            csrfHash = data.csrf_hash;
                        }
                        window.location.reload();
                    })
                    .catch(() => {
                        if (btnPending) btnPending.disabled = false;
                        if (btnAll) btnAll.disabled = false;
                        if (banner) banner.classList.remove('visible');
                        alert('Erro ao processar documentos no Ollama. Verifique se o serviço está ativo.');
                    });
            }

            if (btnPending) {
                btnPending.addEventListener('click', function() {
                    runBatchAnalysis(this);
                });
            }

            if (btnAll) {
                btnAll.addEventListener('click', function() {
                    runBatchAnalysis(this);
                });
            }

            document.querySelectorAll('.js-analyze-single').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const url = this.getAttribute('data-url');
                    if (!url) return;

                    this.disabled = true;
                    this.textContent = '⏳ Lendo...';
                    if (banner) banner.classList.add('visible');

                    const formData = new FormData();
                    formData.append(csrfTokenName, csrfHash);

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                        .then(r => r.json())
                        .then(() => {
                            window.location.reload();
                        })
                        .catch(() => {
                            this.disabled = false;
                            this.textContent = '🤖 Ler com IA';
                            if (banner) banner.classList.remove('visible');
                        });
                });
            });
        })();
    </script>
</body>

</html>

