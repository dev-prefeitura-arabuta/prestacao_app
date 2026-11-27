<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendências por Pessoa - Sistema de Prestação</title>
    <?= view('review._styles')->render() ?>
    <style>
        .pp-toolbar { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .pp-toolbar button {
            border: 1px solid #cbd5e1; background: #ffffff; color: #334155; border-radius: 8px;
            padding: 0.45rem 0.85rem; font-size: 0.82rem; font-weight: 700; cursor: pointer;
        }

        /* Cartão da pessoa (fechado mostra só o resumo) */
        .pp-person {
            background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid #cbd5e1; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); margin-bottom: 0.75rem; overflow: hidden;
        }
        .pp-person.pp-overdue { border-left-color: #f59e0b; }
        .pp-person > summary {
            list-style: none; cursor: pointer; display: flex; align-items: center; gap: 0.9rem; padding: 0.95rem 1.15rem;
        }
        .pp-person > summary::-webkit-details-marker { display: none; }
        .pp-person > summary:hover { background: #f8fafc; }
        .pp-avatar {
            width: 40px; height: 40px; border-radius: 50%; background: #0f172a; color: #ffffff; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.95rem;
        }
        .pp-sum { flex: 1; min-width: 0; }
        .pp-name { font-size: 1rem; font-weight: 800; color: #0f172a; word-break: break-word; }
        .pp-chips { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.35rem; }
        .pp-chip { font-size: 0.74rem; font-weight: 700; border-radius: 999px; padding: 0.12rem 0.55rem; background: #f1f5f9; color: #475569; }
        .pp-chip.warn { background: #ffedd5; color: #c2410c; }
        .pp-chip.danger { background: #fee2e2; color: #b91c1c; }
        .pp-chip.info { background: #eff6ff; color: #1d4ed8; }
        .pp-chevron { color: #94a3b8; font-size: 0.9rem; transition: transform 0.15s ease; flex-shrink: 0; }
        .pp-person[open] .pp-chevron { transform: rotate(180deg); }

        .pp-body { border-top: 1px solid #f1f5f9; }
        .pp-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; padding: 0.8rem 1.15rem; background: #f8fafc; border-bottom: 1px solid #f1f5f9; }

        /* Grupo por prestação */
        .pp-group { border-bottom: 1px solid #f1f5f9; }
        .pp-group:last-child { border-bottom: none; }
        .pp-group-head {
            display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;
            padding: 0.75rem 1.15rem 0.35rem;
        }
        .pp-group-title { font-size: 0.92rem; font-weight: 700; color: #dd4814; text-decoration: none; word-break: break-word; }
        .pp-group-meta { font-size: 0.76rem; color: #64748b; white-space: nowrap; }
        .pp-group-meta.is-overdue { color: #c2410c; font-weight: 700; }
        .pp-items { list-style: none; padding: 0 1.15rem 0.6rem; }
        .pp-item {
            display: grid; grid-template-columns: auto 1fr auto; gap: 0.15rem 0.6rem; align-items: center;
            padding: 0.45rem 0; border-top: 1px dashed #f1f5f9;
        }
        .pp-item:first-child { border-top: none; }
        .pp-num { font-size: 0.74rem; font-weight: 800; background: #f1f5f9; color: #0f172a; border-radius: 999px; padding: 0.1rem 0.5rem; white-space: nowrap; }
        .pp-title { font-size: 0.85rem; color: #334155; min-width: 0; }
        .pp-hint { grid-column: 2 / -1; font-size: 0.74rem; color: #2563eb; }

        @media (max-width: 768px) {
            .pp-person > summary { padding: 0.85rem 0.95rem; gap: 0.7rem; }
            .pp-actions { padding: 0.75rem 0.95rem; }
            .pp-actions .btn { flex: 1; justify-content: center; padding: 0.7rem 1rem; font-size: 0.9rem; }
            .pp-group-head { padding: 0.7rem 0.95rem 0.3rem; }
            .pp-items { padding: 0 0.95rem 0.55rem; }
            /* Item: selo + situação na 1ª linha, nome do item embaixo em largura total */
            .pp-item { grid-template-columns: 1fr auto; }
            .pp-item .pp-title { grid-column: 1 / -1; grid-row: 2; }
            .pp-item .pp-hint { grid-column: 1 / -1; }
            .pp-num { justify-self: start; }
        }
    </style>
</head>
<body>
    <?php
    $statusLabels = ['pendente' => 'Pendente', 'rejeitado' => 'Rejeitado', 'prazo_vencido' => 'Prazo vencido'];
    // Iniciais do nome, ignorando setor entre parênteses: "Lucas Almeida (Engenharia)" → "LA"
    $initials     = static function (string $name): string {
        $clean = trim((string) preg_replace('/\([^)]*\)/u', ' ', $name));
        preg_match_all('/\p{L}[\p{L}\'-]*/u', $clean, $m);
        $words = $m[0] ?: ['?'];
        $first = mb_substr($words[0], 0, 1);
        $last  = count($words) > 1 ? mb_substr((string) end($words), 0, 1) : '';

        return mb_strtoupper($first . $last);
    };
    ?>
    <div class="app-layout">
        <?= view('partials.sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials.navbar', [
                'pageTitle' => 'Pendências por Pessoa',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <h1>Pendências por Pessoa (<?= count($rows) ?>)</h1>
                    <p>Toque em uma pessoa para ver o que ela ainda precisa enviar, agrupado por prestação. Quem tem itens com prazo vencido aparece primeiro.</p>
                </div>

                <?php if (empty($rows)): ?>
                    <div class="card"><div class="empty-state">Ninguém com pendências nas prestações em andamento.</div></div>
                <?php else: ?>
                    <div class="pp-toolbar">
                        <button type="button" data-pp-toggle="open">Expandir todos</button>
                        <button type="button" data-pp-toggle="close">Recolher todos</button>
                    </div>
                <?php endif; ?>

                <?php foreach ($rows as $row): ?>
                    <?php
                    // Agrupa as pendências da pessoa por prestação (mantendo a ordem)
                    $groups = [];
                    foreach ($row['items'] as $it) {
                        $gid = (int) $it['accountability_id'];
                        $groups[$gid] ??= [
                            'title'    => $it['accountability_title'],
                            'deadline' => $it['deadline'],
                            'overdue'  => $it['overdue'],
                            'items'    => [],
                        ];
                        $groups[$gid]['items'][] = $it;
                    }
                    $rejected = count(array_filter($row['items'], fn ($it) => $it['status'] === 'rejeitado'));
                    $toSend   = count(array_filter($row['items'], fn ($it) => $it['has_files']));
                    ?>
                    <details class="pp-person <?= $row['overdue_count'] > 0 ? 'pp-overdue' : '' ?>" <?= count($rows) === 1 ? 'open' : '' ?>>
                        <summary>
                            <span class="pp-avatar" aria-hidden="true"><?= esc($initials($row['user']['name'])) ?></span>
                            <span class="pp-sum">
                                <span class="pp-name"><?= esc($row['user']['name']) ?></span>
                                <span class="pp-chips">
                                    <span class="pp-chip"><?= count($row['items']) ?> pendência(s)</span>
                                    <span class="pp-chip"><?= count($groups) ?> prestação(ões)</span>
                                    <?php if ($row['overdue_count'] > 0): ?>
                                        <span class="pp-chip warn"><?= (int) $row['overdue_count'] ?> atrasada(s)</span>
                                    <?php endif; ?>
                                    <?php if ($rejected > 0): ?>
                                        <span class="pp-chip danger"><?= $rejected ?> rejeitada(s)</span>
                                    <?php endif; ?>
                                    <?php if ($toSend > 0): ?>
                                        <span class="pp-chip info"><?= $toSend ?> falta enviar</span>
                                    <?php endif; ?>
                                </span>
                            </span>
                            <span class="pp-chevron" aria-hidden="true">▼</span>
                        </summary>

                        <div class="pp-body">
                            <div class="pp-actions">
                                <?php if (! empty($row['wa_url'])): ?>
                                    <a class="btn btn-wa" href="<?= esc($row['wa_url']) ?>" target="_blank" rel="noopener">Cobrar tudo no WhatsApp</a>
                                <?php else: ?>
                                    <span class="btn btn-disabled" title="Cadastre o WhatsApp do usuário em Usuários e Permissões">Sem WhatsApp cadastrado</span>
                                <?php endif; ?>
                            </div>

                            <?php foreach ($groups as $gid => $g): ?>
                                <div class="pp-group">
                                    <div class="pp-group-head">
                                        <a class="pp-group-title" href="<?= site_url('accountability/' . $gid) ?>"><?= esc($g['title']) ?></a>
                                        <span class="pp-group-meta <?= $g['overdue'] ? 'is-overdue' : '' ?>">
                                            <?= count($g['items']) ?> item(ns) • Prazo: <?= esc((string) ($g['deadline'] ?? 'não definido')) ?><?= $g['overdue'] ? ' (vencido)' : '' ?>
                                        </span>
                                    </div>
                                    <ul class="pp-items">
                                        <?php foreach ($g['items'] as $it): ?>
                                            <li class="pp-item">
                                                <span class="pp-num">Item <?= esc(str_pad((string) $it['item_number'], 2, '0', STR_PAD_LEFT)) ?></span>
                                                <span class="pp-title"><?= esc($it['item_title']) ?></span>
                                                <span class="pill pill-<?= esc($it['status']) ?>"><?= esc($statusLabels[$it['status']] ?? $it['status']) ?></span>
                                                <?php if ($it['has_files']): ?>
                                                    <span class="pp-hint">Já anexou, falta clicar em Enviar</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </main>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-pp-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const open = btn.getAttribute('data-pp-toggle') === 'open';
                document.querySelectorAll('details.pp-person').forEach(function (d) { d.open = open; });
            });
        });
    </script>
</body>
</html>
