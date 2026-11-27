<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Sistema Patrimonial</title>
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

        .profile-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .profile-summary {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .profile-avatar-lg {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            color: #ffffff;
            font-size: 2rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            overflow: hidden;
            border: 3px solid #e2e8f0;
        }

        .profile-avatar-lg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .avatar-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .btn-avatar-upload {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.42rem 0.8rem;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #0f172a;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .btn-avatar-upload:hover {
            background-color: #f1f5f9;
        }

        .btn-avatar-remove {
            padding: 0.42rem 0.75rem;
            background-color: #ffffff;
            border: 1px solid #fecaca;
            border-radius: 6px;
            color: #dc2626;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-avatar-remove:hover {
            background-color: #fef2f2;
        }

        .profile-summary h2 {
            font-size: 1.1rem;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .profile-summary .email-text {
            font-size: 0.84rem;
            color: #64748b;
            margin-bottom: 0.75rem;
            word-break: break-all;
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.24rem 0.7rem;
            border-radius: 5px;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .role-pill.admin {
            background-color: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }

        .role-pill.user {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .profile-meta-list {
            width: 100%;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            text-align: left;
            font-size: 0.82rem;
        }

        .profile-meta-row {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .profile-meta-row span {
            color: #64748b;
        }

        .profile-meta-row strong {
            color: #0f172a;
        }

        .section-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 1rem;
        }

        .section-divider {
            height: 1px;
            background-color: #f1f5f9;
            margin: 1.25rem 0;
            border: none;
        }

        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 0.9rem;
        }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.35rem;
        }

        .form-control {
            width: 100%;
            padding: 0.58rem 0.85rem;
            font-size: 0.88rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: #ffffff;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control:focus {
            border-color: #dd4814;
            box-shadow: 0 0 0 3px rgba(221, 72, 20, 0.12);
        }

        .form-hint {
            display: block;
            font-size: 0.76rem;
            color: #64748b;
            margin-top: 0.3rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        .btn-submit {
            padding: 0.6rem 1.35rem;
            background-color: #dd4814;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .btn-submit:hover {
            background-color: #c23c0f;
        }

        .perms-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.5rem;
        }

        .perm-chip {
            font-size: 0.76rem;
            font-weight: 600;
            color: #1e3a8a;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 0.28rem 0.65rem;
            border-radius: 5px;
        }

        @media (max-width: 900px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .app-layout {
                flex-direction: column;
            }

            .container {
                padding: 1.25rem 1rem;
            }

            .form-row-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php
    $isAdmin      = (($user['role'] ?? $userRole ?? 'user') === 'admin');
    $currentId    = (int) ($user['id'] ?? session('user_id'));
    $currentName     = old('name', $user['name'] ?? $userName ?? '');
    $currentEmail    = old('email', $user['email'] ?? $userEmail ?? '');
    $currentWhatsapp = old('whatsapp', $user['whatsapp'] ?? '');
    $currentAvatar = $user['avatar'] ?? session('user_avatar');
    $hasAvatar    = ! empty($currentAvatar);
    $avatarUrl    = $hasAvatar ? site_url('profile/avatar/' . $currentId) . '?v=' . md5((string) $currentAvatar) : '';
    $allowedList  = array_map('intval', $allowedItems ?? []);
    ?>

    <div class="app-layout">
        <?= view('partials.sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials.navbar', [
                'pageTitle' => 'Meu Perfil',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <h1>Meu Perfil</h1>
                    <p>Visualize suas informações de conta, permissões e atualize seus dados de acesso.</p>
                </div>

                <div class="profile-grid">
                    <!-- Card Lateral: Resumo do Usuário -->
                    <div class="card profile-summary">
                        <div class="profile-avatar-lg" id="avatarPreviewContainer">
                            <?php if ($hasAvatar): ?>
                                <img src="<?= esc($avatarUrl) ?>" alt="Foto de perfil" id="avatarPreviewImg">
                            <?php else: ?>
                                <span id="avatarInitials"><?= esc(strtoupper(substr((string) ($currentName ?: 'U'), 0, 1))) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="avatar-actions">
                            <label for="avatarInput" class="btn-avatar-upload">
                                <?= $hasAvatar ? 'Alterar Foto' : 'Adicionar Foto' ?>
                            </label>
                            <?php if ($hasAvatar): ?>
                                <form action="<?= site_url('profile/avatar/remove') ?>" method="post" onsubmit="return confirm('Remover sua foto de perfil?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-avatar-remove">Remover</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <h2><?= esc((string) $currentName) ?></h2>
                        <div class="email-text"><?= esc((string) $currentEmail) ?></div>
                        <span class="role-pill <?= $isAdmin ? 'admin' : 'user' ?>">
                            <?= $isAdmin ? 'Administrador' : 'Usuário Padrão' ?>
                        </span>

                        <div class="profile-meta-list">
                            <?php if (trim((string) $currentWhatsapp) !== ''): ?>
                                <div class="profile-meta-row">
                                    <span>WhatsApp</span>
                                    <strong><?= esc((string) $currentWhatsapp) ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (! empty($user['created_at'])): ?>
                                <div class="profile-meta-row">
                                    <span>Cadastrado em</span>
                                    <strong><?= esc(date('d/m/Y', strtotime((string) $user['created_at']))) ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Card Principal: Edição de Dados e Senha -->
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="card">
                            <form action="<?= site_url('profile/update') ?>" method="post" enctype="multipart/form-data">
                                <?= csrf_field() ?>

                                <h2 class="section-title">Dados Cadastrais</h2>

                                <div class="form-group" style="display: none;">
                                    <label for="avatarInput">Foto de Perfil (JPG, PNG ou WEBP — máx. 5 MB)</label>
                                    <input
                                        type="file"
                                        id="avatarInput"
                                        name="avatar"
                                        hidden
                                        class="form-control"
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    <span class="form-hint">Ao escolher uma imagem, a pré-visualização aparece ao lado. Clique em "Salvar Alterações do Perfil" para confirmar.</span>
                                </div>

                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label for="name">Nome Completo</label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            class="form-control"
                                            value="<?= esc((string) $currentName) ?>"
                                            required>
                                    </div>

                                    <div class="form-group">
                                        <label for="email">E-mail de Acesso</label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            class="form-control"
                                            value="<?= esc((string) $currentEmail) ?>"
                                            required>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-top: 0.75rem;">
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

                                <hr class="section-divider">

                                <h2 class="section-title">Alterar Senha (Opcional)</h2>

                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label for="new_password">Nova Senha</label>
                                        <input
                                            type="password"
                                            id="new_password"
                                            name="new_password"
                                            class="form-control"
                                            placeholder="Deixe em branco para manter a senha atual">
                                        <span class="form-hint">Mínimo de 6 caracteres.</span>
                                    </div>

                                    <div class="form-group">
                                        <label for="confirm_password">Confirmar Nova Senha</label>
                                        <input
                                            type="password"
                                            id="confirm_password"
                                            name="confirm_password"
                                            class="form-control"
                                            placeholder="Repita a nova senha">
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn-submit">Salvar Alterações do Perfil</button>
                                </div>
                            </form>
                        </div>

                        <!-- Itens de Prestação de Contas Liberados -->
                        <div class="card">
                            <h2 class="section-title">Meus Quesitos Liberados na Prestação de Contas</h2>
                            <?php if ($isAdmin): ?>
                                <p style="font-size: 0.85rem; color: #475569;">
                                    Como <strong>Administrador</strong>, você possui acesso completo a todos os 24 quesitos de prestação de contas, auditoria e configurações.
                                </p>
                            <?php elseif (empty($allowedList)): ?>
                                <p style="font-size: 0.85rem; color: #64748b;">
                                    Nenhum quesito específico foi liberado para o seu usuário até o momento.
                                </p>
                            <?php else: ?>
                                <div class="perms-list">
                                    <?php foreach ($allowedList as $itemNum): ?>
                                        <?php $label = rtrim($accountabilityItems[$itemNum] ?? ("Item {$itemNum}"), ';.'); ?>
                                        <span class="perm-chip">
                                            <?= esc(str_pad((string) $itemNum, 2, '0', STR_PAD_LEFT)) ?>. <?= esc($label) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function() {
            const avatarInput = document.getElementById('avatarInput');
            const previewContainer = document.getElementById('avatarPreviewContainer');

            if (avatarInput && previewContainer) {
                avatarInput.addEventListener('change', function() {
                    const file = this.files && this.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewContainer.innerHTML = '';
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Pré-visualização da foto de perfil';
                        previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            }
        })();
    </script>
    <?= view('partials.phone_mask')->render() ?>
</body>

</html>