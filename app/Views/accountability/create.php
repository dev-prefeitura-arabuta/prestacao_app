<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Prestação de Contas - Sistema de Prestação</title>
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
        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 0.55rem 0.95rem;
            background-color: #e2e8f0;
            color: #1e293b;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background-color 0.15s ease;
        }
        .btn-back:hover {
            background-color: #cbd5e1;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            width: 100%;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .form-group {
            margin-bottom: 1.15rem;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }
        .form-control {
            width: 100%;
            padding: 0.72rem 0.95rem;
            font-size: 0.95rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background-color: #ffffff;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .form-control:focus {
            border-color: #dd4814;
            box-shadow: 0 0 0 3px rgba(221, 72, 20, 0.12);
        }
        textarea.form-control {
            min-height: 90px;
            resize: vertical;
        }

        /* Seleção dos 24 Itens de Anexo para a Nova Prestação de Contas */
        .items-section {
            margin-top: 1.75rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        .items-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .items-header h2 {
            font-size: 1.08rem;
            color: #0f172a;
        }
        .items-header p {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.2rem;
        }
        .items-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-mini {
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-mini:hover {
            background: #e2e8f0;
        }
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 0.65rem;
        }
        .item-checkbox-card {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            padding: 0.75rem 0.9rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background-color: #f8fafc;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease;
        }
        .item-checkbox-card:hover {
            border-color: #cbd5e1;
            background-color: #f1f5f9;
        }
        .item-checkbox-card input[type="checkbox"] {
            margin-top: 0.18rem;
            width: 17px;
            height: 17px;
            accent-color: #dd4814;
            flex-shrink: 0;
        }
        .item-num {
            font-weight: 700;
            color: #dd4814;
            font-size: 0.85rem;
            min-width: 26px;
        }
        .item-label-text {
            font-size: 0.85rem;
            color: #1e293b;
            line-height: 1.4;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid #e2e8f0;
        }
        .btn-submit {
            padding: 0.75rem 1.45rem;
            background-color: #dd4814;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .btn-submit:hover {
            background-color: #c23c0f;
        }

        @media (max-width: 768px) {
            .app-layout {
                flex-direction: column;
            }
            .container {
                padding: 1.25rem 1rem;
            }
            .items-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php
    $allItems = $accountabilityItems ?? \App\Models\AccountabilityModel::getItems();
    $oldItems = old('enabled_items');
    $selectedItems = is_array($oldItems)
        ? array_map('intval', $oldItems)
        : array_keys($allItems); // Todos os 24 itens marcados por padrão
    ?>

    <div class="app-layout">
        <?= view('partials/sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials/navbar', [
                'pageTitle' => 'Nova Prestação de Contas',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <div>
                        <h1>Nova Prestação de Contas</h1>
                        <p>Cadastre uma nova prestação de contas e defina quais itens de anexo farão parte deste documento.</p>
                    </div>
                    <a href="<?= site_url('accountability') ?>" class="btn-back">← Voltar</a>
                </div>

                <div class="card">
                    <form action="<?= site_url('accountability') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">Título da Prestação de Contas</label>
                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    class="form-control"
                                    placeholder="Ex: Pavimentação Asfáltica - Convênio 2026/01"
                                    value="<?= esc((string) old('title')) ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="deadline">Prazo / Data Limite (Deadline)</label>
                                <input
                                    type="date"
                                    id="deadline"
                                    name="deadline"
                                    class="form-control"
                                    value="<?= esc((string) old('deadline')) ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="status">Status Inicial</label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="aberta" <?= old('status', 'aberta') === 'aberta' ? 'selected' : '' ?>>Aberta</option>
                                    <option value="em_analise" <?= old('status') === 'em_analise' ? 'selected' : '' ?>>Em Análise</option>
                                    <option value="concluida" <?= old('status') === 'concluida' ? 'selected' : '' ?>>Concluída</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Descrição / Objeto do Termo</label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-control"
                                placeholder="Descreva os detalhes ou número do processo/convênio..."
                            ><?= esc((string) old('description')) ?></textarea>
                        </div>

                        <div class="items-section">
                            <div class="items-header">
                                <div>
                                    <h2>Documentos / Campos de Anexo desta Prestação (<span id="selectedCountLabel"><?= count($selectedItems) ?></span> de <?= count($allItems) ?> selecionados)</h2>
                                    <p>Todos os itens vêm selecionados por padrão. Caso desmarque algum item, esta prestação de contas será criada sem aquele campo de anexo.</p>
                                </div>
                                <div class="items-actions">
                                    <button type="button" class="btn-mini" id="btnSelectAllItems">Marcar Todos</button>
                                    <button type="button" class="btn-mini" id="btnUnselectAllItems">Desmarcar Todos</button>
                                </div>
                            </div>

                            <div class="items-grid">
                                <?php foreach ($allItems as $num => $label): ?>
                                    <?php $checked = in_array((int) $num, $selectedItems, true) ? 'checked' : ''; ?>
                                    <label class="item-checkbox-card">
                                        <input
                                            type="checkbox"
                                            name="enabled_items[]"
                                            value="<?= esc((string) $num) ?>"
                                            class="js-enabled-item-cb"
                                            <?= $checked ?>
                                        >
                                        <span class="item-num"><?= esc(str_pad((string) $num, 2, '0', STR_PAD_LEFT)) ?>.</span>
                                        <span class="item-label-text"><?= esc($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="<?= site_url('accountability') ?>" class="btn-back">Cancelar</a>
                            <button type="submit" class="btn-submit">Criar Prestação de Contas →</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function () {
            const checkboxes = Array.from(document.querySelectorAll('.js-enabled-item-cb'));
            const countLabel = document.getElementById('selectedCountLabel');
            const btnAll = document.getElementById('btnSelectAllItems');
            const btnNone = document.getElementById('btnUnselectAllItems');

            function updateCounter() {
                if (!countLabel) return;
                const checkedCount = checkboxes.filter(cb => cb.checked).length;
                countLabel.textContent = String(checkedCount);
            }

            checkboxes.forEach(cb => cb.addEventListener('change', updateCounter));

            if (btnAll) {
                btnAll.addEventListener('click', function () {
                    checkboxes.forEach(cb => { cb.checked = true; });
                    updateCounter();
                });
            }

            if (btnNone) {
                btnNone.addEventListener('click', function () {
                    checkboxes.forEach(cb => { cb.checked = false; });
                    updateCounter();
                });
            }
        })();
    </script>
</body>
</html>
