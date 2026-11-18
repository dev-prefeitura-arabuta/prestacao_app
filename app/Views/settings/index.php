<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Sistema de Prestação</title>
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
            padding: 1.15rem 1.75rem;
        }
        .page-header {
            margin-bottom: 0.75rem;
        }
        .page-header h1 {
            font-size: 1.35rem;
            color: #0f172a;
        }
        .page-header p {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 0.15rem;
        }

        /* Abas de Configurações */
        .tabs-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1rem;
        }
        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1.1rem;
            font-size: 0.86rem;
            font-weight: 600;
            color: #64748b;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: color 0.15s ease, border-color 0.15s ease;
        }
        .tab-btn:hover {
            color: #0f172a;
        }
        .tab-btn.active {
            color: #dd4814;
            border-bottom-color: #dd4814;
        }
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block;
        }

        /* Alertas */
        .alert {
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }
        .alert-success {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .alert ul {
            margin-left: 1.1rem;
        }

        /* Card de Configuração estilo SMTP Settings Compacto */
        .smtp-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            max-width: 100%;
            width: 100%;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.65rem;
        }
        .section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 600;
            background-color: #dcfce7;
            color: #15803d;
        }
        .status-pill.inactive {
            background-color: #f1f5f9;
            color: #64748b;
        }
        .section-divider {
            height: 1px;
            background-color: #f1f5f9;
            margin: 0.9rem 0;
            border: none;
        }
        .form-group {
            margin-bottom: 0.5rem;
        }
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .form-row-host {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
        }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.3rem;
        }
        .form-control {
            width: 100%;
            padding: 0.48rem 0.85rem;
            font-size: 0.85rem;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            background-color: #ffffff;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .form-control::placeholder {
            color: #94a3b8;
        }
        .form-control:focus {
            border-color: #dd4814;
            box-shadow: 0 0 0 3px rgba(221, 72, 20, 0.12);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
            margin-top: 0.45rem;
        }
        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.83rem;
            font-weight: 500;
            color: #334155;
            cursor: pointer;
        }
        .checkbox-label input {
            width: 15px;
            height: 15px;
            accent-color: #dd4814;
        }
        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-top: 0.85rem;
            padding-top: 0.85rem;
            border-top: 1px solid #f1f5f9;
        }
        .btn-submit {
            padding: 0.55rem 1.35rem;
            background-color: #dd4814;
            color: #ffffff;
            border: none;
            border-radius: 999px;
            font-size: 0.85rem;
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
            .form-row-2 {
                grid-template-columns: 1fr;
            }
            .smtp-card {
                padding: 1.5rem 1.25rem;
            }
        }
    </style>
</head>
<body>
    <?php
    $currentTab     = $activeTab ?? 'smtp';
    $smtpHost       = old('smtp_host', $smtpConfig['smtp_host'] ?? '');
    $smtpPort       = old('smtp_port', $smtpConfig['smtp_port'] ?? '587');
    $smtpEncryption = old('smtp_encryption', $smtpConfig['smtp_encryption'] ?? 'STARTTLS');
    $smtpUsername   = old('smtp_username', $smtpConfig['smtp_username'] ?? '');
    $emailAddress   = old('email_address', $smtpConfig['email_address'] ?? '');
    $emailName      = old('email_name', $smtpConfig['email_name'] ?? '');
    $replyToEmail   = old('reply_to_email', $smtpConfig['reply_to_email'] ?? '');
    $smtpTimeout    = old('smtp_timeout', $smtpConfig['smtp_timeout'] ?? 30);
    $smtpAuth       = (int) old('smtp_auth', $smtpConfig['smtp_auth'] ?? 1);
    $smtpVerifySsl  = (int) old('smtp_verify_ssl', $smtpConfig['smtp_verify_ssl'] ?? 1);
    $status         = (int) old('status', $smtpConfig['status'] ?? 1);
    $hasPassword    = ! empty($smtpConfig['smtp_password']);
    ?>

    <div class="app-layout">
        <?= view('partials/sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials/navbar', [
                'pageTitle' => 'Configurações',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <h1>Configurações</h1>
                    <p>Gerencie os parâmetros de envio de e-mail (SMTP) e preferências do sistema.</p>
                </div>

                <div class="tabs-nav">
                    <a href="<?= site_url('settings?tab=smtp') ?>" class="tab-btn <?= $currentTab === 'smtp' ? 'active' : '' ?>">
                        Servidor SMTP (E-mail)
                    </a>
                    <a href="<?= site_url('settings?tab=general') ?>" class="tab-btn <?= $currentTab === 'general' ? 'active' : '' ?>">
                        Geral
                    </a>
                </div>

                <!-- Aba: SMTP Settings -->
                <div class="tab-pane <?= $currentTab === 'smtp' ? 'active' : '' ?>">
                    <div class="smtp-card">
                        <form action="<?= site_url('settings/smtp') ?>" method="post">
                            <?= csrf_field() ?>

                            <div class="section-header">
                                <h2 class="section-title">SMTP Settings</h2>
                                <span class="status-pill <?= $status === 1 ? '' : 'inactive' ?>">
                                    <?= $status === 1 ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </div>

                            <div class="form-row-host">
                                <div class="form-group">
                                    <label for="smtp_host">SMTP Host</label>
                                    <input
                                        type="text"
                                        id="smtp_host"
                                        name="smtp_host"
                                        class="form-control"
                                        placeholder="smtp.example.com"
                                        value="<?= esc((string) $smtpHost) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="smtp_port">SMTP Port</label>
                                    <input
                                        type="text"
                                        id="smtp_port"
                                        name="smtp_port"
                                        class="form-control"
                                        placeholder="587"
                                        value="<?= esc((string) $smtpPort) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="smtp_encryption">Encryption</label>
                                    <select id="smtp_encryption" name="smtp_encryption" class="form-control" required>
                                        <option value="STARTTLS" <?= $smtpEncryption === 'STARTTLS' ? 'selected' : '' ?>>STARTTLS</option>
                                        <option value="SSL" <?= $smtpEncryption === 'SSL' ? 'selected' : '' ?>>SSL / TLS (465)</option>
                                        <option value="TLS" <?= $smtpEncryption === 'TLS' ? 'selected' : '' ?>>TLS</option>
                                        <option value="NONE" <?= $smtpEncryption === 'NONE' ? 'selected' : '' ?>>Nenhuma (NONE)</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="section-divider">

                            <div class="section-header">
                                <h2 class="section-title">Authentication</h2>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="smtp_username">Username</label>
                                    <input
                                        type="text"
                                        id="smtp_username"
                                        name="smtp_username"
                                        class="form-control"
                                        placeholder="user@example.com"
                                        value="<?= esc((string) $smtpUsername) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="smtp_password">Password</label>
                                    <input
                                        type="password"
                                        id="smtp_password"
                                        name="smtp_password"
                                        class="form-control"
                                        placeholder="<?= $hasPassword ? '•••••••••••• (em branco mantém atual)' : '••••••••••••' ?>"
                                    >
                                </div>
                            </div>

                            <hr class="section-divider">

                            <div class="section-header">
                                <h2 class="section-title">Sender Information</h2>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label for="email_address">From Email</label>
                                    <input
                                        type="email"
                                        id="email_address"
                                        name="email_address"
                                        class="form-control"
                                        placeholder="noreply@example.com"
                                        value="<?= esc((string) $emailAddress) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="email_name">From Name</label>
                                    <input
                                        type="text"
                                        id="email_name"
                                        name="email_name"
                                        class="form-control"
                                        placeholder="Município de Arabutã"
                                        value="<?= esc((string) $emailName) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="reply_to_email">Reply-To Email</label>
                                    <input
                                        type="email"
                                        id="reply_to_email"
                                        name="reply_to_email"
                                        class="form-control"
                                        placeholder="support@example.com"
                                        value="<?= esc((string) $replyToEmail) ?>"
                                    >
                                </div>
                            </div>

                            <hr class="section-divider">

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="smtp_timeout">Timeout (segundos)</label>
                                    <input
                                        type="number"
                                        id="smtp_timeout"
                                        name="smtp_timeout"
                                        class="form-control"
                                        value="<?= esc((string) $smtpTimeout) ?>"
                                        min="1"
                                        max="300"
                                    >
                                </div>

                                <div class="form-group">
                                    <label>Opções Adicionais</label>
                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="smtp_auth" value="1" <?= $smtpAuth === 1 ? 'checked' : '' ?>>
                                            Autenticação SMTP
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="smtp_verify_ssl" value="1" <?= $smtpVerifySsl === 1 ? 'checked' : '' ?>>
                                            Verificar SSL
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="status" value="1" <?= $status === 1 ? 'checked' : '' ?>>
                                            Ativo
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-submit">Salvar Configurações SMTP</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Aba: Geral -->
                <div class="tab-pane <?= $currentTab === 'general' ? 'active' : '' ?>">
                    <div class="smtp-card">
                        <div class="section-header">
                            <h2 class="section-title">Configurações Gerais</h2>
                        </div>
                        <p style="color: #64748b; font-size: 0.9rem;">Espaço reservado para demais configurações gerais do sistema.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
