<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Editar Usuário' : 'Novo Usuário' ?> - Sistema de Prestação</title>
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
            margin-bottom: 1.5rem;
            gap: 1rem;
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

        .alert {
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .alert ul {
            margin-left: 1.1rem;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            width: 100%;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .form-group {
            margin-bottom: 0.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }

        .form-hint {
            display: block;
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 0.3rem;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem 0.9rem;
            font-size: 0.95rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            background-color: #ffffff;
            color: #1e293b;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control:focus {
            border-color: #dd4814;
            box-shadow: 0 0 0 3px rgba(221, 72, 20, 0.12);
        }

        /* Seção dos 24 Itens de Anexo */
        .permissions-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .permissions-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .permissions-header h2 {
            font-size: 1.1rem;
            color: #0f172a;
        }

        .permissions-header p {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.2rem;
        }

        .perm-actions {
            display: flex;
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

        .admin-notice {
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
            color: #c2410c;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 1rem;
            display: none;
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
            padding: 0.7rem 1.35rem;
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
    $formAction = $isEdit
        ? site_url('users/' . $user['id'] . '/update')
        : site_url('users');

    $currentName     = old('name', $user['name'] ?? '');
    $currentEmail    = old('email', $user['email'] ?? '');
    $currentWhatsapp = old('whatsapp', $user['whatsapp'] ?? '');
    $currentRole     = old('role', $user['role'] ?? 'user');
    $selectedPerm = array_map('intval', (array) old('allowed_items', $allowedItems ?? []));
    ?>

    <div class="app-layout">
        <?= view('partials.sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials.navbar', [
                'pageTitle' => 'Usuários e Permissões',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <div>
                        <h1><?= $isEdit ? 'Editar Usuário e Permissões' : 'Cadastrar Novo Usuário' ?></h1>
                        <p><?= $isEdit ? 'Atualize os dados cadastrais e os campos de prestação de contas liberados para este usuário.' : 'Preencha os dados abaixo e selecione quais campos de anexo este usuário poderá visualizar e anexar.' ?></p>
                    </div>
                    <a href="<?= site_url('users') ?>" class="btn-back">← Voltar</a>
                </div>

                <div class="card">
                    <form action="<?= $formAction ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Nome Completo</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control"
                                    placeholder="Ex: João da Silva"
                                    value="<?= esc((string) $currentName) ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="email">E-mail</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control"
                                    placeholder="Ex: joao@arabuta.sc.gov.br"
                                    value="<?= esc((string) $currentEmail) ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="whatsapp">WhatsApp (Opcional)</label>
                                <input
                                    type="text"
                                    id="whatsapp"
                                    name="whatsapp"
                                    class="form-control"
                                    inputmode="tel"
                                    maxlength="16"
                                    data-phone-mask
                                    placeholder="Ex: (49) 99999-9999"
                                    value="<?= esc((string) $currentWhatsapp) ?>">
                            </div>

                            <div class="form-group">
                                <label for="role">Permissão (Perfil)</label>
                                <?php if (! empty($isPrimaryAdmin)): ?>
                                    <?php /* Administrador principal: perfil travado (o servidor também recusa a troca) */ ?>
                                    <input type="hidden" name="role" value="admin">
                                    <div class="form-control" style="background:#f1f5f9; color:#334155; display:flex; align-items:center; gap:0.5rem; cursor:not-allowed;" title="O administrador principal sempre será administrador">
                                        <i class="fa-solid fa-lock" style="color:#64748b;"></i> Administrador (principal)
                                    </div>
                                    <small style="display:block; margin-top:0.35rem; color:#64748b; font-size:0.78rem;">
                                        Esta é a conta do administrador principal: ela sempre será administradora e não pode ser excluída.
                                    </small>
                                <?php else: ?>
                                    <select id="role" name="role" class="form-control" required>
                                        <option value="user" <?= $currentRole === 'user' ? 'selected' : '' ?>>Usuário Padrão</option>
                                        <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="password">
                                    <?= $isEdit ? 'Nova Senha (opcional)' : 'Senha' ?>
                                </label>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="<?= $isEdit ? 'Deixe em branco para manter a senha atual' : 'Mínimo de 6 caracteres' ?>"
                                    <?= $isEdit ? '' : 'required' ?>>
                                <?php if ($isEdit): ?>
                                    <span class="form-hint">Preencha apenas se desejar alterar a senha atual deste usuário.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="permissions-section">
                            <div class="permissions-header">
                                <div>
                                    <h2>Acesso aos Campos de Anexos da Prestação de Contas</h2>
                                    <p>Marque os documentos nos quais este usuário poderá visualizar e anexar arquivos (ZIP, JPG, JPEG, PNG e PDF).</p>
                                </div>
                                <div class="perm-actions" id="permActions">
                                    <button type="button" class="btn-mini" id="btnCheckAll">Marcar Todos</button>
                                    <button type="button" class="btn-mini" id="btnUncheckAll">Desmarcar Todos</button>
                                </div>
                            </div>
                            <div class="items-grid" id="itemsGrid">
                                <?php foreach (($accountabilityItems ?? []) as $num => $label): ?>
                                    <?php $checked = in_array((int) $num, $selectedPerm, true) ? 'checked' : ''; ?>
                                    <label class="item-checkbox-card">
                                        <input
                                            type="checkbox"
                                            name="allowed_items[]"
                                            value="<?= esc((string) $num) ?>"
                                            class="item-perm-checkbox"
                                            <?= $checked ?>>
                                        <span class="item-label-text"><?= esc($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="<?= site_url('users') ?>" class="btn-back">Cancelar</a>
                            <button type="submit" class="btn-submit">
                                <?= $isEdit ? 'Salvar Alterações' : 'Cadastrar Usuário' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function() {
            const roleSelect = document.getElementById('role');
            const adminNotice = document.getElementById('adminNotice');
            const itemsGrid = document.getElementById('itemsGrid');
            const permActions = document.getElementById('permActions');
            const btnCheckAll = document.getElementById('btnCheckAll');
            const btnUncheckAll = document.getElementById('btnUncheckAll');
            const checkboxes = document.querySelectorAll('.item-perm-checkbox');

            function updateRoleUI() {
                if (!roleSelect) return;
                const isAdmin = roleSelect.value === 'admin';
                if (adminNotice) adminNotice.style.display = isAdmin ? 'block' : 'none';
                if (itemsGrid) itemsGrid.style.opacity = isAdmin ? '0.55' : '1';
                if (permActions) permActions.style.display = isAdmin ? 'none' : 'flex';
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', updateRoleUI);
                updateRoleUI();
            }

            if (btnCheckAll) {
                btnCheckAll.addEventListener('click', function() {
                    checkboxes.forEach(cb => {
                        cb.checked = true;
                    });
                });
            }

            if (btnUncheckAll) {
                btnUncheckAll.addEventListener('click', function() {
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                    });
                });
            }
        })();
    </script>
    <?= view('partials.phone_mask')->render() ?>
</body>

</html>
