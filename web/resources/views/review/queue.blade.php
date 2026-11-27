<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Para Avaliar - Sistema de Prestação</title>
    <?= view('review._styles')->render() ?>
    <style>
        /* ---------- Celular: cada item vira um cartão (mesmo padrão da lista de prestações) ---------- */
        @media (max-width: 768px) {
            .rq-table thead {
                display: none;
            }

            .rq-table,
            .rq-table tbody {
                display: block;
                width: 100%;
            }

            .rq-table tbody {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.75rem;
            }

            .rq-table tr.rq-row {
                display: grid;
                grid-template-columns: 1fr auto;
                grid-template-areas:
                    "item item"
                    "acc acc"
                    "who count"
                    "action action";
                gap: 0.55rem 0.75rem;
                align-items: center;
                padding: 0.95rem 1rem;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-left: 4px solid #2563eb;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }

            .rq-table tr.rq-row.rq-paused {
                border-left-color: #6366f1;
            }

            .rq-table tr.rq-row:hover {
                background: #ffffff;
            }

            .rq-table tr.rq-row > td {
                display: block;
                padding: 0;
                border: none;
                min-width: 0;
            }

            .rq-table .rq-item {
                grid-area: item;
            }

            .rq-table .rq-item-num {
                display: inline-block;
                background: #eff6ff;
                color: #1d4ed8;
                border-radius: 999px;
                padding: 0.15rem 0.6rem;
                font-size: 0.8rem;
            }

            .rq-table .rq-item-title {
                margin-top: 0.35rem;
                font-size: 0.85rem;
                color: #334155;
            }

            /* A data divide a 1ª linha com o selo "Item 02"; o nome do item usa a largura toda */
            .rq-table .rq-sent {
                grid-row: 1;
                grid-column: 2;
                justify-self: end;
                align-self: start;
                font-size: 0.75rem;
                color: #64748b;
                text-align: right;
            }

            .rq-table .rq-acc {
                grid-area: acc;
                font-size: 0.95rem;
                line-height: 1.35;
                word-break: break-word;
            }

            .rq-table .rq-who {
                grid-area: who;
                font-size: 0.82rem;
                color: #334155;
            }

            .rq-table .rq-count {
                grid-area: count;
                justify-self: end;
                font-size: 0.82rem;
                color: #334155;
            }

            .rq-table .rq-who::before,
            .rq-table .rq-count::before {
                content: attr(data-label) ": ";
                color: #64748b;
                font-weight: 600;
            }

            .rq-table .rq-action {
                grid-area: action;
                padding-top: 0.6rem !important;
                border-top: 1px solid #f1f5f9 !important;
            }

            .rq-table .rq-action .btn {
                width: 100%;
                justify-content: center;
                padding: 0.7rem 1rem;
                font-size: 0.9rem;
            }

            .rq-table tr:not(.rq-row),
            .rq-table tr:not(.rq-row) > td {
                display: block;
                border: none;
            }

            .rq-card .table-responsive {
                overflow-x: visible;
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
                'pageTitle' => 'Para Avaliar',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <h1>Para Avaliar (<?= count($queue) ?>)</h1>
                    <p>Itens enviados pelos usuários aguardando confirmação ou rejeição, dos mais antigos para os mais recentes.</p>
                </div>

                <div class="card rq-card">
                    <div class="table-responsive">
                        <table class="rq-table">
                            <thead>
                                <tr>
                                    <th>Enviado em</th>
                                    <th>Prestação de Contas</th>
                                    <th>Item</th>
                                    <th>Enviado por</th>
                                    <th>Anexos</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($queue)): ?>
                                    <tr>
                                        <td colspan="6" class="empty-state">Tudo em dia! Nenhum item aguardando avaliação.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($queue as $row): ?>
                                        <tr class="rq-row <?= $row['is_paused'] ? 'rq-paused' : '' ?>">
                                            <td class="rq-sent" data-label="Enviado em"><?= esc((string) ($row['sent_at'] ?? '—')) ?></td>
                                            <td class="rq-acc">
                                                <a class="link" href="<?= site_url('accountability/' . $row['accountability_id']) ?>">
                                                    <?= esc($row['accountability_title']) ?>
                                                </a>
                                                <?php if ($row['is_paused']): ?>
                                                    <span class="pill pill-paused">Pausada</span>
                                                <?php endif; ?>
                                                <?php if (! empty($row['deadline'])): ?>
                                                    <div class="muted">Prazo: <?= esc($row['deadline']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="rq-item">
                                                <strong class="rq-item-num">Item <?= esc(str_pad((string) $row['item_number'], 2, '0', STR_PAD_LEFT)) ?></strong>
                                                <div class="muted rq-item-title"><?= esc($row['item_title']) ?></div>
                                            </td>
                                            <td class="rq-who" data-label="Enviado por"><?= esc(implode(', ', $row['uploaders'])) ?></td>
                                            <td class="rq-count" data-label="Anexos"><?= (int) $row['attachments_count'] ?></td>
                                            <td class="rq-action">
                                                <a class="btn btn-primary" href="<?= site_url('accountability/' . $row['accountability_id']) ?>#modal-item-<?= (int) $row['item_number'] ?>">
                                                    <i class="fa-solid fa-clipboard-check"></i> Avaliar
                                                </a>
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
</body>
</html>
