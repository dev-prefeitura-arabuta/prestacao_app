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
        }
    </style>
</head>
<body>
    <?php
    $formAction = $isEdit
        ? site_url('users/' . $user['id'] . '/update')
        : site_url('users');

    $currentName  = old('name', $user['name'] ?? '');
    $currentEmail = old('email', $user['email'] ?? '');
    $currentRole  = old('role', $user['role'] ?? 'user');
    ?>

    <div class="app-layout">
        <?= view('partials/sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials/navbar', [
                'pageTitle' => 'Usuários e Permissões',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <div>
                        <h1><?= $isEdit ? 'Editar Usuário' : 'Cadastrar Novo Usuário' ?></h1>
                        <p><?= $isEdit ? 'Atualize os dados ou a permissão do usuário selecionado.' : 'Preencha os dados abaixo para criar um novo usuário.' ?></p>
                    </div>
                    <a href="<?= site_url('users') ?>" class="btn-back">← Voltar</a>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-error">
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <?php $errors = session()->getFlashdata('errors'); ?>
                <?php if (! empty($errors) && is_array($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= esc($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

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
                                    required
                                >
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
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="role">Permissão (Perfil)</label>
                                <select id="role" name="role" class="form-control" required>
                                    <option value="user" <?= $currentRole === 'user' ? 'selected' : '' ?>>Usuário Padrão (user)</option>
                                    <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Administrador (admin)</option>
                                </select>
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
                                    <?= $isEdit ? '' : 'required' ?>
                                >
                                <?php if ($isEdit): ?>
                                    <span class="form-hint">Preencha apenas se desejar alterar a senha atual deste usuário.</span>
                                <?php endif; ?>
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
</body>
</html>
