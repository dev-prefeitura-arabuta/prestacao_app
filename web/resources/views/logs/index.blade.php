<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs do Sistema - Sistema de Prestação</title>
    <?= view('review._styles')->render() ?>
    <style>
        .toolbar { display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
        .toolbar select, .toolbar input[type="search"] {
            padding: 0.5rem 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; background: #ffffff; color: #0f172a;
        }
        .toolbar input[type="search"] { flex: 1; min-width: 180px; }
        .level-tabs { display: flex; flex-wrap: wrap; gap: 0.4rem; padding: 0.8rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
        .level-tab {
            padding: 0.35rem 0.75rem; border-radius: 999px; border: 1px solid #e2e8f0; font-size: 0.78rem; font-weight: 700;
            color: #475569; text-decoration: none; background: #ffffff;
        }
        .level-tab.active { background: #0f172a; color: #ffffff; border-color: #0f172a; }
        .entry { padding: 0.75rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
        .entry-head { display: flex; gap: 0.6rem; align-items: baseline; flex-wrap: wrap; }
        .entry-time { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.78rem; color: #64748b; white-space: nowrap; }
        .entry-msg { font-size: 0.88rem; color: #0f172a; word-break: break-word; flex: 1; min-width: 200px; }
        .lvl { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; padding: 0.12rem 0.45rem; border-radius: 6px; }
        .lvl-error { background: #fee2e2; color: #b91c1c; }
        .lvl-warning { background: #fef3c7; color: #92400e; }
        .lvl-info { background: #dbeafe; color: #1e40af; }
        .lvl-other { background: #f1f5f9; color: #475569; }
        details { margin-top: 0.4rem; }
        summary { cursor: pointer; font-size: 0.78rem; color: #2563eb; }
        pre {
            margin-top: 0.4rem; padding: 0.75rem; background: #0f172a; color: #e2e8f0; border-radius: 8px;
            font-size: 0.74rem; line-height: 1.45; overflow-x: auto; max-height: 360px; white-space: pre-wrap; word-break: break-all;
        }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
        .summary-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.9rem 1rem; }
        .summary-card .num { font-size: 1.5rem; font-weight: 800; color: #0f172a; }
        .summary-card .lbl { font-size: 0.78rem; color: #64748b; }
        .note { padding: 0.7rem 1.25rem; font-size: 0.8rem; color: #92400e; background: #fffbeb; border-bottom: 1px solid #fde68a; }

        .entry-clickable { cursor: pointer; transition: background-color 0.12s ease; }
        .entry-clickable:hover, .entry-clickable:focus { background: #f8fafc; outline: none; }
        .entry-clickable.selected { background: #fff7ed; box-shadow: inset 3px 0 0 #dd4814; }
        .entry-open { font-size: 0.75rem; color: #2563eb; font-weight: 700; white-space: nowrap; }
        .entry-sub { margin-top: 0.3rem; font-size: 0.76rem; color: #64748b; display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; }
        .entry-sub code, .lv-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        .ctx-chip { background: #f1f5f9; border-radius: 6px; padding: 0.1rem 0.45rem; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }

        /* Painel de preview */
        .lv-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); z-index: 1000; display: none; }
        .lv-backdrop.open { display: block; }
        .lv-drawer {
            position: fixed; top: 0; right: 0; height: 100vh; width: min(820px, 100vw); background: #ffffff; z-index: 1001;
            display: flex; flex-direction: column;
            transform: translateX(100%); transition: transform 0.18s ease;
        }
        /* sombra só com o painel aberto (fechado, ela vazaria na borda direita da tela) */
        .lv-drawer.open { transform: translateX(0); box-shadow: -12px 0 32px rgba(15, 23, 42, 0.18); }
        .lv-top { display: flex; align-items: center; gap: 0.5rem; padding: 0.9rem 1.1rem; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; }
        .lv-top .grow { flex: 1; min-width: 120px; font-size: 0.85rem; color: #475569; }
        .lv-btn { border: 1px solid #cbd5e1; background: #ffffff; color: #334155; border-radius: 8px; padding: 0.35rem 0.7rem; font-size: 0.8rem; font-weight: 700; cursor: pointer; }
        .lv-btn:disabled { opacity: 0.4; cursor: default; }
        .lv-btn.primary { background: #0f172a; color: #ffffff; border-color: #0f172a; }
        .lv-body { overflow-y: auto; padding: 1rem 1.1rem 2rem; flex: 1; }
        .lv-section { margin-bottom: 1.2rem; }
        .lv-section h3 { font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 0.45rem; display: flex; align-items: center; gap: 0.5rem; }
        .lv-message { font-size: 0.98rem; color: #0f172a; white-space: pre-wrap; word-break: break-word; line-height: 1.5; }
        .lv-kv { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        .lv-kv td { border-bottom: 1px solid #f1f5f9; padding: 0.4rem 0.5rem; vertical-align: top; }
        .lv-kv td:first-child { color: #64748b; width: 32%; font-weight: 600; }
        .lv-kv td:last-child { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; word-break: break-all; white-space: pre-wrap; }
        .lv-exc { border: 1px solid #fecaca; background: #fff5f5; border-radius: 10px; padding: 0.75rem 0.85rem; }
        .lv-exc.prev { border-color: #e2e8f0; background: #f8fafc; margin-top: 0.5rem; }
        .lv-exc .cls { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.8rem; color: #b91c1c; font-weight: 700; word-break: break-all; }
        .lv-exc.prev .cls { color: #475569; }
        .lv-exc .msg { margin-top: 0.3rem; font-size: 0.88rem; color: #0f172a; white-space: pre-wrap; word-break: break-word; }
        .lv-exc .loc { margin-top: 0.35rem; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.76rem; color: #475569; word-break: break-all; }
        .lv-frames { list-style: none; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
        .lv-frames li { padding: 0.45rem 0.7rem; border-bottom: 1px solid #f1f5f9; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.74rem; color: #64748b; }
        .lv-frames li:last-child { border-bottom: none; }
        .lv-frames li.app { background: #fff7ed; color: #0f172a; }
        .lv-frames .n { display: inline-block; min-width: 2.2rem; color: #94a3b8; }
        .lv-frames .file { font-weight: 700; word-break: break-all; }
        .lv-frames .call { display: block; margin-top: 0.15rem; padding-left: 2.2rem; word-break: break-all; }
        .lv-frames.hide-vendor li.vendor { display: none; }
        .lv-toggle { font-size: 0.75rem; font-weight: 600; color: #2563eb; cursor: pointer; text-transform: none; letter-spacing: 0; display: inline-flex; gap: 0.3rem; align-items: center; }
        .lv-muted { font-size: 0.8rem; color: #94a3b8; }
        @media (max-width: 768px) { .lv-kv td:first-child { width: 40%; } }
    </style>
</head>
<body>
    <?php
    $levelTabs = [
        'all'     => 'Todos',
        'error'   => 'Erros',
        'warning' => 'Avisos',
        'info'    => 'Informações',
        'other'   => 'Debug',
    ];
    $counts    = $result['counts'] ?? ['error' => 0, 'warning' => 0, 'info' => 0, 'other' => 0];
    $allCount  = array_sum($counts);
    $fmtSize   = static function (int $bytes): string {
        return $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', '.') . ' MB' : number_format(max(1, $bytes / 1024), 0, ',', '.') . ' KB';
    };
    $fileLabel = static function (array $f) use ($fmtSize): string {
        $label = $f['date'] ? date('d/m/Y', strtotime($f['date'])) : 'Arquivo único (laravel.log)';
        if ($f['date'] === date('Y-m-d')) {
            $label .= ' — hoje';
        }

        return $label . ' (' . $fmtSize($f['size']) . ')';
    };
    // Valor curto de um campo do contexto (na lista; o preview mostra completo)
    $chipValue = static function ($v): string {
        if ($v === null) {
            return 'null';
        }
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        $text = is_scalar($v) ? (string) $v : (string) json_encode($v, JSON_UNESCAPED_UNICODE);

        return mb_strlen($text) > 60 ? mb_substr($text, 0, 57) . '...' : $text;
    };
    $query = static fn (array $extra) => site_url('logs') . '?' . http_build_query(array_merge(['file' => $file, 'level' => $level, 'q' => $search], $extra));
    ?>
    <div class="app-layout">
        <?= view('partials.sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials.navbar', [
                'pageTitle' => 'Logs do Sistema',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <h1>Logs do Sistema</h1>
                    <p>Erros, avisos e eventos registrados pela aplicação, um arquivo por dia (mais recentes primeiro).</p>
                </div>

                <?php if (empty($files)): ?>
                    <div class="card"><div class="empty-state">Nenhum arquivo de log encontrado em storage/logs.</div></div>
                <?php else: ?>
                    <div class="summary-cards">
                        <div class="summary-card"><div class="num" style="color:#b91c1c;"><?= (int) $counts['error'] ?></div><div class="lbl">Erros no arquivo</div></div>
                        <div class="summary-card"><div class="num" style="color:#92400e;"><?= (int) $counts['warning'] ?></div><div class="lbl">Avisos</div></div>
                        <div class="summary-card"><div class="num" style="color:#1e40af;"><?= (int) $counts['info'] ?></div><div class="lbl">Informações (logins, envios…)</div></div>
                        <div class="summary-card"><div class="num"><?= count($files) ?></div><div class="lbl">Dias com registro</div></div>
                    </div>

                    <div class="card">
                        <form class="toolbar" method="get" action="<?= site_url('logs') ?>">
                            <select name="file" onchange="this.form.submit()" aria-label="Dia">
                                <?php foreach ($files as $f): ?>
                                    <option value="<?= esc($f['name']) ?>" <?= $f['name'] === $file ? 'selected' : '' ?>><?= esc($fileLabel($f)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="level" value="<?= esc($level) ?>">
                            <input type="search" name="q" value="<?= esc($search) ?>" placeholder="Buscar no texto (ex.: SMTP, e-mail, SQLSTATE)">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a class="btn" style="border:1px solid #cbd5e1;color:#334155;" href="<?= site_url('logs/download/' . rawurlencode($file)) ?>">Baixar arquivo</a>
                        </form>

                        <div class="level-tabs">
                            <?php foreach ($levelTabs as $key => $label): ?>
                                <?php $n = $key === 'all' ? $allCount : (int) ($counts[$key] ?? 0); ?>
                                <a class="level-tab <?= $level === $key ? 'active' : '' ?>" href="<?= esc($query(['level' => $key])) ?>">
                                    <?= esc($label) ?> (<?= $n ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if (! empty($result['truncated'])): ?>
                            <div class="note">Arquivo grande: exibindo apenas o trecho final. Use "Baixar arquivo" para ver tudo.</div>
                        <?php endif; ?>

                        <?php if (empty($result['entries'])): ?>
                            <div class="empty-state">Nenhum registro encontrado com esses filtros.</div>
                        <?php else: ?>
                            <?php foreach ($result['entries'] as $i => $e): ?>
                                <?php $grp = app(\App\Services\LogViewerService::class)->group($e['level']); ?>
                                <div class="entry entry-clickable" data-index="<?= (int) $i ?>" tabindex="0" role="button" aria-label="Abrir preview do registro">
                                    <div class="entry-head">
                                        <span class="entry-time"><?= esc(date('d/m H:i:s', strtotime($e['time']))) ?></span>
                                        <span class="lvl lvl-<?= esc($grp) ?>"><?= esc($e['level']) ?></span>
                                        <span class="entry-msg"><?= esc($e['clean_message']) ?></span>
                                        <span class="entry-open">Ver detalhes ›</span>
                                    </div>
                                    <?php if (! empty($e['exception'])): ?>
                                        <div class="entry-sub">
                                            <?= esc($e['exception']['class']) ?> em
                                            <code><?= esc($e['exception']['file']) ?>:<?= (int) $e['exception']['line'] ?></code>
                                        </div>
                                    <?php elseif (! empty($e['context'])): ?>
                                        <div class="entry-sub">
                                            <?php foreach (array_slice($e['context'], 0, 4, true) as $k => $v): ?>
                                                <span class="ctx-chip"><?= esc((string) $k) ?>: <?= esc($chipValue($v)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (($result['total'] ?? 0) > count($result['entries'])): ?>
                                <div class="note">Mostrando os <?= count($result['entries']) ?> registros mais recentes de <?= (int) $result['total'] ?>. Refine a busca para ver outros.</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Preview detalhado do registro -->
    <div class="lv-backdrop" id="lvBackdrop"></div>
    <aside class="lv-drawer" id="lvDrawer" aria-hidden="true" aria-label="Preview do registro de log">
        <div class="lv-top">
            <span class="lvl" id="lvLevel"></span>
            <span class="grow lv-mono" id="lvMeta"></span>
            <button type="button" class="lv-btn" id="lvPrev" title="Registro mais recente (←)">‹ Anterior</button>
            <button type="button" class="lv-btn" id="lvNext" title="Registro mais antigo (→)">Próximo ›</button>
            <button type="button" class="lv-btn" id="lvCopy" title="Copiar o texto bruto do registro">Copiar</button>
            <button type="button" class="lv-btn primary" id="lvClose" title="Fechar (Esc)">Fechar ✕</button>
        </div>
        <div class="lv-body" id="lvBody"></div>
    </aside>

    <script type="application/json" id="lvData"><?= json_encode(
        $result['entries'] ?? [],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ) ?></script>

    <script>
        (function () {
            const dataEl = document.getElementById('lvData');
            const entries = dataEl ? JSON.parse(dataEl.textContent || '[]') : [];
            const drawer = document.getElementById('lvDrawer');
            const backdrop = document.getElementById('lvBackdrop');
            const body = document.getElementById('lvBody');
            const levelEl = document.getElementById('lvLevel');
            const metaEl = document.getElementById('lvMeta');
            const prevBtn = document.getElementById('lvPrev');
            const nextBtn = document.getElementById('lvNext');
            const copyBtn = document.getElementById('lvCopy');
            const ERROR = ['emergency', 'alert', 'critical', 'error'];
            let current = -1;
            let hideVendor = true;

            function group(level) {
                if (ERROR.includes(level)) return 'error';
                if (level === 'warning') return 'warning';
                if (level === 'info' || level === 'notice') return 'info';
                return 'other';
            }

            // Monta elementos sempre com textContent (o conteúdo do log nunca vira HTML)
            function el(tag, cls, text) {
                const node = document.createElement(tag);
                if (cls) node.className = cls;
                if (text !== undefined && text !== null) node.textContent = String(text);
                return node;
            }

            function section(title, extra) {
                const sec = el('div', 'lv-section');
                const h = el('h3', null, title);
                if (extra) h.appendChild(extra);
                sec.appendChild(h);
                return sec;
            }

            function formatValue(v) {
                if (v === null) return 'null';
                if (typeof v === 'object') return JSON.stringify(v, null, 2);
                return String(v);
            }

            function excBox(exc, isPrev) {
                const box = el('div', 'lv-exc' + (isPrev ? ' prev' : ''));
                box.appendChild(el('div', 'cls', exc.class + (exc.code && exc.code !== '0' ? '  (código ' + exc.code + ')' : '')));
                box.appendChild(el('div', 'msg', exc.message));
                box.appendChild(el('div', 'loc', exc.file + ':' + exc.line));
                return box;
            }

            function framesList(frames) {
                const ul = el('ul', 'lv-frames' + (hideVendor ? ' hide-vendor' : ''));
                frames.forEach(function (f) {
                    const li = el('li', f.app ? 'app' : 'vendor');
                    li.appendChild(el('span', 'n', '#' + f.n));
                    li.appendChild(el('span', 'file', f.file ? f.file + ':' + f.line : '(interno)'));
                    li.appendChild(el('span', 'call', f.call));
                    ul.appendChild(li);
                });
                return ul;
            }

            function render(index) {
                const e = entries[index];
                if (!e) return;
                current = index;
                const grp = group(e.level);

                levelEl.className = 'lvl lvl-' + grp;
                levelEl.textContent = e.level;
                metaEl.textContent = e.time + '  •  ambiente: ' + e.env + '  •  ' + (index + 1) + ' de ' + entries.length;
                prevBtn.disabled = index <= 0;
                nextBtn.disabled = index >= entries.length - 1;

                body.replaceChildren();

                const msgSec = section('Mensagem');
                msgSec.appendChild(el('div', 'lv-message', e.clean_message || e.message));
                body.appendChild(msgSec);

                if (e.context && typeof e.context === 'object') {
                    const ctxSec = section('Contexto');
                    const table = el('table', 'lv-kv');
                    Object.keys(e.context).forEach(function (k) {
                        const tr = el('tr');
                        tr.appendChild(el('td', null, k));
                        tr.appendChild(el('td', null, formatValue(e.context[k])));
                        table.appendChild(tr);
                    });
                    ctxSec.appendChild(table);
                    body.appendChild(ctxSec);
                }

                if (e.exception) {
                    const excSec = section('Exceção');
                    excSec.appendChild(excBox(e.exception, false));
                    body.appendChild(excSec);
                }

                if (e.previous && e.previous.length) {
                    const prevSec = section('Exceções anteriores (' + e.previous.length + ')');
                    e.previous.forEach(function (p) { prevSec.appendChild(excBox(p, true)); });
                    body.appendChild(prevSec);
                }

                if (e.trace && e.trace.length) {
                    const vendorCount = e.trace.length - e.app_frames;
                    const toggle = el('label', 'lv-toggle');
                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.checked = hideVendor;
                    toggle.appendChild(cb);
                    toggle.appendChild(document.createTextNode('ocultar ' + vendorCount + ' frame(s) do vendor'));
                    const traceSec = section('Stack trace (' + e.trace.length + ' frames, ' + e.app_frames + ' do app)', toggle);
                    const list = framesList(e.trace);
                    cb.addEventListener('change', function () {
                        hideVendor = cb.checked;
                        list.classList.toggle('hide-vendor', hideVendor);
                    });
                    traceSec.appendChild(list);
                    if (e.app_frames === 0 && hideVendor) {
                        traceSec.appendChild(el('p', 'lv-muted', 'Nenhum frame do app: desmarque a opção acima para ver o trace completo.'));
                    }
                    body.appendChild(traceSec);
                }

                const rawSec = section('Texto bruto');
                rawSec.appendChild(el('pre', null, e.raw));
                body.appendChild(rawSec);
                body.scrollTop = 0;

                document.querySelectorAll('.entry-clickable.selected').forEach(function (n) { n.classList.remove('selected'); });
                const row = document.querySelector('.entry-clickable[data-index="' + index + '"]');
                if (row) {
                    row.classList.add('selected');
                    row.scrollIntoView({ block: 'nearest' });
                }
            }

            function open(index) {
                render(index);
                drawer.classList.add('open');
                backdrop.classList.add('open');
                drawer.setAttribute('aria-hidden', 'false');
            }

            function close() {
                drawer.classList.remove('open');
                backdrop.classList.remove('open');
                drawer.setAttribute('aria-hidden', 'true');
                const row = document.querySelector('.entry-clickable[data-index="' + current + '"]');
                if (row) row.focus();
            }

            document.querySelectorAll('.entry-clickable').forEach(function (row) {
                row.addEventListener('click', function () { open(parseInt(row.getAttribute('data-index'), 10)); });
                row.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter' || ev.key === ' ') {
                        ev.preventDefault();
                        open(parseInt(row.getAttribute('data-index'), 10));
                    }
                });
            });

            prevBtn.addEventListener('click', function () { if (current > 0) render(current - 1); });
            nextBtn.addEventListener('click', function () { if (current < entries.length - 1) render(current + 1); });
            document.getElementById('lvClose').addEventListener('click', close);
            backdrop.addEventListener('click', close);

            copyBtn.addEventListener('click', function () {
                const e = entries[current];
                if (!e) return;
                const done = function () {
                    copyBtn.textContent = 'Copiado ✓';
                    setTimeout(function () { copyBtn.textContent = 'Copiar'; }, 1500);
                };
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(e.raw).then(done).catch(function () {});
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = e.raw;
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); done(); } catch (err) { /* sem suporte */ }
                    ta.remove();
                }
            });

            document.addEventListener('keydown', function (ev) {
                if (!drawer.classList.contains('open')) return;
                if (ev.key === 'Escape') close();
                else if (ev.key === 'ArrowLeft' && current > 0) render(current - 1);
                else if (ev.key === 'ArrowRight' && current < entries.length - 1) render(current + 1);
            });
        })();
    </script>
</body>
</html>
