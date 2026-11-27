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
            padding: 0.55rem 0.85rem;
            font-size: 0.85rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
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
            padding: 0.6rem 1.35rem;
            background-color: #dd4814;
            color: #ffffff;
            border: none;
            border-radius: 6px;
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
    $isDeveloper    = (bool) ($isDeveloper ?? false);
    $currentTab     = $activeTab ?? ($isDeveloper ? 'smtp' : 'general');
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

    $waCfg            = $whatsappConfig ?? [];
    $waStatusInfo     = $whatsappStatus ?? ['online' => false, 'message' => '', 'sessions' => []];
    $waApiUrl         = old('api_url', $waCfg['api_url'] ?? 'http://localhost:2785');
    $waHasKey         = ! empty($waCfg['api_key']);
    $waDetectedKey    = (string) ($waCfg['detected_local_key'] ?? '');
    $waSessionId      = old('session_id', $waCfg['session_id'] ?? '');
    $waSessionName    = old('session_name', $waCfg['session_name'] ?? 'prestacao-contas');
    $waEngineType     = old('engine_type', $waCfg['engine_type'] ?? 'whatsapp-web.js');
    $waCountryCode    = old('default_country_code', $waCfg['default_country_code'] ?? '55');
    $waTimeout        = old('timeout', $waCfg['timeout'] ?? 15);
    $waWebhookUrl     = old('webhook_url', $waCfg['webhook_url'] ?? '');
    $waEnabled        = (int) old('status', $waCfg['status'] ?? 0);
    $waSaved          = ! empty($waCfg['saved']);
    ?>

    <div class="app-layout">
        <?= view('partials.sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials.navbar', [
                'pageTitle' => 'Configurações',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="page-header">
                    <h1>Configurações</h1>
                    <p><?= $isDeveloper
                        ? 'Gerencie os parâmetros de envio de e-mail (SMTP), WhatsApp e preferências do sistema.'
                        : 'Gerencie a identidade do sistema e a capa do PDF. As integrações de e-mail e WhatsApp são mantidas pelo desenvolvedor.' ?></p>
                </div>

                <div class="tabs-nav">
                    <?php if ($isDeveloper): ?>
                    <a href="<?= site_url('settings?tab=smtp') ?>" class="tab-btn <?= $currentTab === 'smtp' ? 'active' : '' ?>">
                        Servidor SMTP (E-mail)
                    </a>
                    <a href="<?= site_url('settings?tab=whatsapp') ?>" class="tab-btn <?= $currentTab === 'whatsapp' ? 'active' : '' ?>">
                        WhatsApp (OpenWA)
                    </a>
                    <?php endif; ?>
                    <?php /* Aba de IA temporariamente desabilitada na interface
                    <a href="<?= site_url('settings?tab=ollama') ?>" class="tab-btn <?= $currentTab === 'ollama' ? 'active' : '' ?>">
                        Inteligência Artificial (Ollama / Qwen-VL)
                    </a>
                    */ ?>
                    <a href="<?= site_url('settings?tab=general') ?>" class="tab-btn <?= $currentTab === 'general' ? 'active' : '' ?>">
                        Geral
                    </a>
                    <a href="<?= site_url('settings?tab=pdf_cover') ?>" class="tab-btn <?= $currentTab === 'pdf_cover' ? 'active' : '' ?>">
                        Capa do PDF Unificado
                    </a>
                </div>

                <?php if ($isDeveloper): /* Abas técnicas: SMTP e IA (somente desenvolvedor) */ ?>
                <!-- Aba: SMTP Settings -->
                <div class="tab-pane <?= $currentTab === 'smtp' ? 'active' : '' ?>">
                    <div class="smtp-card">
                        <form action="<?= site_url('settings/smtp') ?>" method="post">
                            <?= csrf_field() ?>

                            <div class="section-header">
                                <h2 class="section-title">Servidor de Envio (SMTP)</h2>
                                <div style="display: flex; align-items: center; gap: 0.65rem;">
                                    <span class="status-pill <?= $status === 1 ? '' : 'inactive' ?>">
                                        <?= $status === 1 ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                    <?php if (! empty($smtpConfig)): ?>
                                        <button
                                            type="submit"
                                            formaction="<?= site_url('settings/smtp/toggle') ?>"
                                            formnovalidate
                                            style="padding: 0.32rem 0.78rem; border-radius: 6px; font-size: 0.76rem; font-weight: 700; cursor: pointer; border: 1px solid <?= $status === 1 ? '#fecaca' : '#bbf7d0' ?>; background-color: <?= $status === 1 ? '#fef2f2' : '#f0fdf4' ?>; color: <?= $status === 1 ? '#dc2626' : '#15803d' ?>;">
                                            <i class="fa-solid <?= $status === 1 ? 'fa-power-off' : 'fa-circle-check' ?>"></i>
                                            <?= $status === 1 ? 'Desativar SMTP' : 'Ativar SMTP' ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-row-host">
                                <div class="form-group">
                                    <label for="smtp_host">Servidor SMTP (Host)</label>
                                    <input
                                        type="text"
                                        id="smtp_host"
                                        name="smtp_host"
                                        class="form-control"
                                        placeholder="Ex: smtp.gmail.com ou mail.seudominio.gov.br"
                                        value="<?= esc((string) $smtpHost) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="smtp_port">Porta SMTP</label>
                                    <input
                                        type="text"
                                        id="smtp_port"
                                        name="smtp_port"
                                        class="form-control"
                                        placeholder="Ex: 587 ou 465"
                                        value="<?= esc((string) $smtpPort) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="smtp_encryption">Segurança / Criptografia</label>
                                    <select id="smtp_encryption" name="smtp_encryption" class="form-control" required>
                                        <option value="STARTTLS" <?= $smtpEncryption === 'STARTTLS' ? 'selected' : '' ?>>STARTTLS (Recomendado - Porta 587)</option>
                                        <option value="SSL" <?= $smtpEncryption === 'SSL' ? 'selected' : '' ?>>SSL / TLS (Porta 465)</option>
                                        <option value="TLS" <?= $smtpEncryption === 'TLS' ? 'selected' : '' ?>>TLS</option>
                                        <option value="NONE" <?= $smtpEncryption === 'NONE' ? 'selected' : '' ?>>Sem criptografia</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="section-divider">

                            <div class="section-header">
                                <h2 class="section-title">Dados de Acesso (Autenticação)</h2>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="smtp_username">Usuário / E-mail de Login</label>
                                    <input
                                        type="text"
                                        id="smtp_username"
                                        name="smtp_username"
                                        class="form-control"
                                        placeholder="Ex: notificacoes@arabuta.sc.gov.br"
                                        value="<?= esc((string) $smtpUsername) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="smtp_password">Senha do E-mail</label>
                                    <input
                                        type="password"
                                        id="smtp_password"
                                        name="smtp_password"
                                        class="form-control"
                                        placeholder="<?= $hasPassword ? '•••••••••••• (deixe em branco para manter a atual)' : 'Digite a senha ou senha de aplicativo' ?>"
                                    >
                                </div>
                            </div>

                            <hr class="section-divider">

                            <div class="section-header">
                                <h2 class="section-title">Identificação do Remetente</h2>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label for="email_address">E-mail do Remetente</label>
                                    <input
                                        type="email"
                                        id="email_address"
                                        name="email_address"
                                        class="form-control"
                                        placeholder="Ex: nao-responda@arabuta.sc.gov.br"
                                        value="<?= esc((string) $emailAddress) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="email_name">Nome de Exibição do Remetente</label>
                                    <input
                                        type="text"
                                        id="email_name"
                                        name="email_name"
                                        class="form-control"
                                        placeholder="Ex: Município de Arabutã - Prestação de Contas"
                                        value="<?= esc((string) $emailName) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="reply_to_email">E-mail para Resposta (Opcional)</label>
                                    <input
                                        type="email"
                                        id="reply_to_email"
                                        name="reply_to_email"
                                        class="form-control"
                                        placeholder="Ex: controladoria@arabuta.sc.gov.br"
                                        value="<?= esc((string) $replyToEmail) ?>"
                                    >
                                </div>
                            </div>

                            <hr class="section-divider">

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="smtp_timeout">Tempo Limite de Conexão (segundos)</label>
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
                                    <label>Opções de Conexão</label>
                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="smtp_auth" value="1" <?= $smtpAuth === 1 ? 'checked' : '' ?>>
                                            Exigir autenticação SMTP
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="smtp_verify_ssl" value="1" <?= $smtpVerifySsl === 1 ? 'checked' : '' ?>>
                                            Verificar certificado SSL
                                        </label>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="status" value="1" <?= $status === 1 ? 'checked' : '' ?>>
                                            Envio de e-mails ativo
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-submit">Salvar Configurações de E-mail</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Aba: Ollama / Qwen-VL / Cloud Free APIs -->
                <?php
                $aiProvider     = old('provider', $ollamaConfig['provider'] ?? 'gemini');
                $geminiKey      = old('gemini_api_key', $ollamaConfig['gemini_api_key'] ?? '');
                $geminiModel    = old('gemini_model', $ollamaConfig['gemini_model'] ?? 'gemini-3.8-flash');
                $orKey          = old('openrouter_api_key', $ollamaConfig['openrouter_api_key'] ?? '');
                $orModel        = old('openrouter_model', $ollamaConfig['openrouter_model'] ?? 'qwen/qwen2.5-vl-72b-instruct:free');
                $groqKey        = old('groq_api_key', $ollamaConfig['groq_api_key'] ?? '');
                $groqModel      = old('groq_model', $ollamaConfig['groq_model'] ?? 'meta-llama/llama-4-scout-17b-16e-instruct');
                $ollamaBaseUrl  = old('base_url', $ollamaConfig['base_url'] ?? 'http://127.0.0.1:11434');
                $ollamaModel    = old('model', $ollamaConfig['model'] ?? 'qwen3-vl:8b');
                $ollamaTimeout  = old('timeout', $ollamaConfig['timeout'] ?? 180);
                $ollamaPrompt   = old('system_prompt', $ollamaConfig['system_prompt'] ?? '');
                $isOllamaOnline = ! empty($ollamaStatus['online']);
                $providerName   = $ollamaStatus['provider'] ?? 'IA';
                ?>
                <div class="tab-pane <?= $currentTab === 'ollama' ? 'active' : '' ?>">
                    <div class="smtp-card">
                        <form action="<?= site_url('settings/ollama') ?>" method="post">
                            <?= csrf_field() ?>

                            <div class="section-header">
                                <h2 class="section-title">Motor de Inteligência Artificial (Leitura de Notas e Valores)</h2>
                                <span class="status-pill <?= $isOllamaOnline ? '' : 'inactive' ?>">
                                    <?= $isOllamaOnline ? esc($providerName . ' Ativo') : esc($providerName . ' Pendente (.env)') ?>
                                </span>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="ai_provider">Provedor de IA Ativo (também configurável via <code>AI_PROVIDER</code> no <code>.env</code>)</label>
                                    <select id="ai_provider" name="provider" class="form-control">
                                        <option value="gemini" <?= $aiProvider === 'gemini' ? 'selected' : '' ?>>Google Gemini API (Grátis - Lê PDF e Imagem nativamente)</option>
                                        <option value="openrouter" <?= $aiProvider === 'openrouter' ? 'selected' : '' ?>>OpenRouter API (Grátis - Qwen2.5-VL 72B Online)</option>
                                        <option value="groq" <?= $aiProvider === 'groq' ? 'selected' : '' ?>>Groq Cloud API (Grátis - Llama 4 Scout Vision)</option>
                                        <option value="ollama" <?= $aiProvider === 'ollama' ? 'selected' : '' ?>>Ollama Local (Qwen3-VL 8B na máquina)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="ollama_timeout">Tempo Limite da Análise (segundos)</label>
                                    <input
                                        type="number"
                                        id="ollama_timeout"
                                        name="timeout"
                                        class="form-control"
                                        value="<?= esc((string) $ollamaTimeout) ?>"
                                        min="15"
                                        max="600"
                                        required
                                    >
                                </div>
                            </div>

                            <hr class="section-divider">

                            <div class="section-header">
                                <h2 class="section-title">1. Google Gemini API (Grátis — <a href="https://aistudio.google.com/apikey" target="_blank" style="color:#dd4814;">Gerar Chave no Google AI Studio ↗</a>)</h2>
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="gemini_api_key">Chave da API (<code>GEMINI_API_KEY</code> no <code>.env</code>)</label>
                                    <input
                                        type="password"
                                        id="gemini_api_key"
                                        name="gemini_api_key"
                                        class="form-control"
                                        placeholder="AIzaSy..."
                                        value="<?= esc((string) $geminiKey) ?>"
                                    >
                                </div>
                                <div class="form-group">
                                    <label for="gemini_model">Modelo Gemini</label>
                                    <input
                                        type="text"
                                        id="gemini_model"
                                        name="gemini_model"
                                        class="form-control"
                                        value="<?= esc((string) $geminiModel) ?>"
                                    >
                                </div>
                            </div>

                            <hr class="section-divider">

                            <div class="section-header">
                                <h2 class="section-title">2. OpenRouter / Groq Cloud (Alternativas Gratuitas Online)</h2>
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="openrouter_api_key">OpenRouter API Key (<code>OPENROUTER_API_KEY</code> no <code>.env</code>)</label>
                                    <input
                                        type="password"
                                        id="openrouter_api_key"
                                        name="openrouter_api_key"
                                        class="form-control"
                                        placeholder="sk-or-v1-..."
                                        value="<?= esc((string) $orKey) ?>"
                                    >
                                </div>
                                <div class="form-group">
                                    <label for="openrouter_model">Modelo OpenRouter</label>
                                    <input
                                        type="text"
                                        id="openrouter_model"
                                        name="openrouter_model"
                                        class="form-control"
                                        value="<?= esc((string) $orModel) ?>"
                                    >
                                </div>
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="groq_api_key">Groq API Key (<code>GROQ_API_KEY</code> no <code>.env</code>)</label>
                                    <input
                                        type="password"
                                        id="groq_api_key"
                                        name="groq_api_key"
                                        class="form-control"
                                        placeholder="gsk_..."
                                        value="<?= esc((string) $groqKey) ?>"
                                    >
                                </div>
                                <div class="form-group">
                                    <label for="groq_model">Modelo Groq</label>
                                    <input
                                        type="text"
                                        id="groq_model"
                                        name="groq_model"
                                        class="form-control"
                                        value="<?= esc((string) $groqModel) ?>"
                                    >
                                </div>
                            </div>

                            <hr class="section-divider">

                            <div class="section-header">
                                <h2 class="section-title">3. Ollama Local (Offline na Máquina)</h2>
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="ollama_base_url">URL da API do Ollama</label>
                                    <input
                                        type="text"
                                        id="ollama_base_url"
                                        name="base_url"
                                        class="form-control"
                                        placeholder="http://127.0.0.1:11434"
                                        value="<?= esc((string) $ollamaBaseUrl) ?>"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="ollama_model">Modelo Vision no Ollama</label>
                                    <input
                                        type="text"
                                        id="ollama_model"
                                        name="model"
                                        class="form-control"
                                        placeholder="qwen3-vl:8b"
                                        value="<?= esc((string) $ollamaModel) ?>"
                                    >
                                </div>
                            </div>

                            <hr class="section-divider">

                            <div class="form-group">
                                <label for="ollama_system_prompt">Instrução Padrão da IA (Prompt de Auditoria)</label>
                                <textarea
                                    id="ollama_system_prompt"
                                    name="system_prompt"
                                    class="form-control"
                                    rows="3"
                                    style="resize: vertical;"
                                ><?= esc((string) $ollamaPrompt) ?></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-submit">Salvar Configurações da IA</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php endif; ?>

                <!-- Aba: Geral -->
                <?php
                $gAppName     = old('app_name', $generalConfig['app_name'] ?? 'Sistema Patrimonial');
                $gCompanyName = old('company_name', $generalConfig['company_name'] ?? 'Município de Arabutã');
                $gCnpj        = old('cnpj', $generalConfig['cnpj'] ?? '');
                $gHasLogo     = ! empty($generalConfig['app_logo']);
                $gLogoUrl     = site_url('app-logo') . ($gHasLogo ? '?v=' . md5((string) $generalConfig['app_logo']) : '');
                ?>
                <div class="tab-pane <?= $currentTab === 'general' ? 'active' : '' ?>">
                    <div class="smtp-card">
                        <form action="<?= site_url('settings/general') ?>" method="post" enctype="multipart/form-data">
                            <?= csrf_field() ?>

                            <div class="section-header">
                                <h2 class="section-title">Identidade do Aplicativo e Dados da Empresa / Órgão</h2>
                            </div>

                            <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; padding: 1rem; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 1.15rem;">
                                <div style="width: 110px; height: 74px; border-radius: 6px; background-color: #0f172a; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; padding: 0.4rem; overflow: hidden; flex-shrink: 0;">
                                    <img
                                        src="<?= esc($gLogoUrl) ?>"
                                        alt="Logo do Aplicativo"
                                        id="generalLogoPreview"
                                        style="max-width: 100%; max-height: 100%; object-fit: contain; display: block;">
                                </div>

                                <div style="flex: 1; min-width: 240px;">
                                    <label for="app_logo_input" style="display: block; font-size: 0.82rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;">
                                        Logo do Aplicativo (PNG, JPG, WEBP ou SVG — máx. 5 MB)
                                    </label>
                                    <input
                                        type="file"
                                        id="app_logo_input"
                                        name="app_logo"
                                        class="form-control"
                                        accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-top: 0.4rem;">
                                        <span style="font-size: 0.76rem; color: #64748b;">
                                            Exibida na tela inicial, login e menu lateral do sistema.
                                        </span>
                                        <?php if ($gHasLogo): ?>
                                            <button
                                                type="submit"
                                                formaction="<?= site_url('settings/general/remove-logo') ?>"
                                                formnovalidate
                                                onclick="return confirm('Remover a logo personalizada e restaurar a padrão?');"
                                                style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 5px; padding: 0.25rem 0.65rem; font-size: 0.75rem; font-weight: 600; cursor: pointer;">
                                                Restaurar Logo Padrão
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label for="general_app_name">Nome do Aplicativo</label>
                                    <input
                                        type="text"
                                        id="general_app_name"
                                        name="app_name"
                                        class="form-control"
                                        placeholder="Ex: Sistema Patrimonial"
                                        value="<?= esc((string) $gAppName) ?>"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label for="general_company_name">Empresa / Órgão</label>
                                    <input
                                        type="text"
                                        id="general_company_name"
                                        name="company_name"
                                        class="form-control"
                                        placeholder="Ex: Município de Arabutã"
                                        value="<?= esc((string) $gCompanyName) ?>"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label for="general_cnpj">CNPJ</label>
                                    <input
                                        type="text"
                                        id="general_cnpj"
                                        name="cnpj"
                                        class="form-control"
                                        placeholder="00.000.000/0000-00"
                                        maxlength="18"
                                        value="<?= esc((string) $gCnpj) ?>">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-submit">Salvar Configurações Gerais</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Aba: Capa do PDF Unificado -->
                <?php
                $pdfCoverEnabled      = (int) old('pdf_cover_enabled', $generalConfig['pdf_cover_enabled'] ?? 1);
                $pdfIndexEnabled      = (int) old('pdf_cover_index_enabled', $generalConfig['pdf_cover_index_enabled'] ?? 1);
                $pdfShowLogo          = (int) old('pdf_cover_show_logo', $generalConfig['pdf_cover_show_logo'] ?? 1);
                $pdfShowBorder        = (int) old('pdf_cover_show_border', $generalConfig['pdf_cover_show_border'] ?? 1);
                $pdfCoverLabel        = old('pdf_cover_label', $generalConfig['pdf_cover_label'] ?? 'PRESTAÇÃO DE CONTAS');
                $pdfCoverSubtitle     = old('pdf_cover_subtitle', $generalConfig['pdf_cover_subtitle'] ?? 'DOSSIÊ DOCUMENTAL CONSOLIDADO');
                $pdfCoverFooter       = old('pdf_cover_footer', $generalConfig['pdf_cover_footer'] ?? '');
                $pdfCoverPrimaryColor = old('pdf_cover_primary_color', $generalConfig['pdf_cover_primary_color'] ?? '#0f172a');
                $pdfCoverAccentColor  = old('pdf_cover_accent_color', $generalConfig['pdf_cover_accent_color'] ?? '#dd4814');
                $pdfHasCoverArt       = ! empty($generalConfig['pdf_cover_art']);
                $pdfCoverArtUrl       = $pdfHasCoverArt ? site_url('settings/pdf-cover/art') . '?v=' . md5((string) $generalConfig['pdf_cover_art']) : '';
                ?>
                <div class="tab-pane <?= $currentTab === 'pdf_cover' ? 'active' : '' ?>">
                    <div class="smtp-card">
                        <form action="<?= site_url('settings/pdf-cover') ?>" method="post" enctype="multipart/form-data">
                            <?= csrf_field() ?>

                            <div class="section-header">
                                <h2 class="section-title">Montagem e Personalização da Capa do PDF Unificado</h2>
                            </div>

                            <div style="display: grid; grid-template-columns: 1.45fr 215px; gap: 1.25rem; align-items: start;">
                                <div>
                                    <div class="form-group" style="margin-bottom: 0.65rem;">
                                        <label>Elementos da Capa e Estrutura do PDF</label>
                                        <div class="checkbox-group" style="margin-top: 0.25rem; gap: 0.9rem;">
                                            <label class="checkbox-label">
                                                <input type="checkbox" id="pdf_cover_enabled" name="pdf_cover_enabled" value="1" <?= $pdfCoverEnabled === 1 ? 'checked' : '' ?>>
                                                Gerar Capa Institucional
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" id="pdf_cover_show_logo" name="pdf_cover_show_logo" value="1" <?= $pdfShowLogo === 1 ? 'checked' : '' ?>>
                                                Exibir Logo na Capa
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" id="pdf_cover_show_border" name="pdf_cover_show_border" value="1" <?= $pdfShowBorder === 1 ? 'checked' : '' ?>>
                                                Exibir Moldura Externa
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-row-2">
                                        <div class="form-group">
                                            <label for="pdf_cover_label">Título Superior da Capa</label>
                                            <input
                                                type="text"
                                                id="pdf_cover_label"
                                                name="pdf_cover_label"
                                                class="form-control"
                                                placeholder="PRESTAÇÃO DE CONTAS"
                                                value="<?= esc((string) $pdfCoverLabel) ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="pdf_cover_footer">Texto do Rodapé da Capa (Opcional)</label>
                                            <input
                                                type="text"
                                                id="pdf_cover_footer"
                                                name="pdf_cover_footer"
                                                class="form-control"
                                                placeholder="Em branco usa: <?= esc($gCompanyName . ' - ' . $gAppName) ?>"
                                                value="<?= esc((string) $pdfCoverFooter) ?>">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="pdf_cover_art_input">Arte / Imagem de Fundo da Capa A4 (PNG, JPG ou WEBP — Opcional)</label>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <input
                                                type="file"
                                                id="pdf_cover_art_input"
                                                name="pdf_cover_art"
                                                class="form-control"
                                                style="padding: 0.38rem 0.65rem;"
                                                accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                                            <?php if ($pdfHasCoverArt): ?>
                                                <button
                                                    type="submit"
                                                    formaction="<?= site_url('settings/pdf-cover/remove-art') ?>"
                                                    formnovalidate
                                                    onclick="return confirm('Remover a arte personalizada da capa?');"
                                                    style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 5px; padding: 0.45rem 0.7rem; font-size: 0.75rem; font-weight: 600; cursor: pointer; white-space: nowrap;">
                                                    Remover Arte
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-row-2">
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="pdf_cover_primary_color">Cor Principal (Faixa e Títulos)</label>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <input
                                                    type="color"
                                                    id="pdf_cover_primary_color"
                                                    name="pdf_cover_primary_color"
                                                    value="<?= esc((string) $pdfCoverPrimaryColor) ?>"
                                                    style="width: 38px; height: 34px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px; background: #fff; cursor: pointer;">
                                                <input
                                                    type="text"
                                                    id="pdf_cover_primary_hex"
                                                    class="form-control"
                                                    value="<?= esc((string) $pdfCoverPrimaryColor) ?>"
                                                    readonly>
                                            </div>
                                        </div>

                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label for="pdf_cover_accent_color">Cor de Destaque (Linhas e App)</label>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <input
                                                    type="color"
                                                    id="pdf_cover_accent_color"
                                                    name="pdf_cover_accent_color"
                                                    value="<?= esc((string) $pdfCoverAccentColor) ?>"
                                                    style="width: 38px; height: 34px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px; background: #fff; cursor: pointer;">
                                                <input
                                                    type="text"
                                                    id="pdf_cover_accent_hex"
                                                    class="form-control"
                                                    value="<?= esc((string) $pdfCoverAccentColor) ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mini Pré-visualização ao Vivo da Capa A4 -->
                                <div>
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.3rem; text-align: center;">
                                        Pré-visualização (A4)
                                    </div>
                                    <div id="coverPreviewSheet" style="width: 100%; max-width: 215px; margin: 0 auto; aspect-ratio: 210 / 297; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px; box-shadow: 0 3px 8px rgba(15, 23, 42, 0.08); display: flex; flex-direction: column; justify-content: space-between; text-align: center; position: relative; overflow: hidden;">
                                        <img
                                            id="coverPreviewArtBg"
                                            src="<?= esc($pdfCoverArtUrl) ?>"
                                            alt=""
                                            style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0; display: <?= $pdfHasCoverArt ? 'block' : 'none' ?>;">
                                        <div id="coverPreviewFrame" style="position: relative; z-index: 1; flex: 1; border: 1px solid #cbd5e1; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;">
                                            <div id="coverPreviewTopBar" style="height: 5px; background-color: <?= esc((string) $pdfCoverPrimaryColor) ?>; width: 100%;"></div>

                                            <div style="padding: 8px 6px 2px;">
                                                <img
                                                    id="coverPreviewLogo"
                                                    src="<?= esc($gLogoUrl) ?>"
                                                    alt="Logo"
                                                    style="max-width: 68px; max-height: 26px; object-fit: contain; margin: 0 auto 4px; display: <?= $pdfShowLogo === 1 ? 'block' : 'none' ?>;">
                                                <div id="coverPreviewCompany" style="font-size: 0.54rem; font-weight: 800; color: <?= esc((string) $pdfCoverPrimaryColor) ?>; text-transform: uppercase;">
                                                    <?= esc((string) $gCompanyName) ?>
                                                </div>
                                                <?php if ($gCnpj !== ''): ?>
                                                    <div style="font-size: 0.44rem; color: #64748b;">CNPJ: <?= esc((string) $gCnpj) ?></div>
                                                <?php endif; ?>
                                                <div id="coverPreviewApp" style="font-size: 0.48rem; font-weight: 700; color: <?= esc((string) $pdfCoverAccentColor) ?>; margin-top: 1px;">
                                                    <?= esc((string) $gAppName) ?>
                                                </div>
                                            </div>

                                            <div style="padding: 0 10px;">
                                                <div id="coverPreviewAccentLine" style="width: 55%; height: 1.5px; background-color: <?= esc((string) $pdfCoverAccentColor) ?>; margin: 0 auto 6px;"></div>
                                                <div id="coverPreviewLabel" style="font-size: 0.48rem; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">
                                                    <?= esc((string) $pdfCoverLabel) ?>
                                                </div>
                                                <div id="coverPreviewTitle" style="font-size: 0.76rem; font-weight: 800; color: <?= esc((string) $pdfCoverPrimaryColor) ?>; margin: 4px 0;">
                                                    Título da Prestação de Contas
                                                </div>
                                                <div style="font-size: 0.45rem; color: #64748b;">Descrição resumida do planejamento</div>
                                            </div>

                                            <div style="padding: 6px 8px;">
                                                <div id="coverPreviewFooter" style="font-size: 0.44rem; color: #94a3b8;">
                                                    <?= esc((string) ($pdfCoverFooter ?: ($gCompanyName . ' - ' . $gAppName))) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-submit">Salvar Configurações da Capa do PDF</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($isDeveloper): /* Aba técnica: WhatsApp (somente desenvolvedor) */ ?>
                <!-- Aba: WhatsApp (OpenWA Gateway) -->
                <div class="tab-pane <?= $currentTab === 'whatsapp' ? 'active' : '' ?>">
                    <?php if (session('success') && $currentTab === 'whatsapp'): ?>
                        <div class="alert alert-success"><?= esc((string) session('success')) ?></div>
                    <?php endif; ?>
                    <?php if (session('error') && $currentTab === 'whatsapp'): ?>
                        <div class="alert alert-error"><?= esc((string) session('error')) ?></div>
                    <?php endif; ?>
                    <?php if (session('errors_list') && $currentTab === 'whatsapp'): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ((array) session('errors_list') as $err): ?>
                                    <li><?= esc((string) $err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="smtp-card">
                        <form action="<?= site_url('settings/whatsapp') ?>" method="post">
                            <?= csrf_field() ?>

                            <div class="section-header">
                                <div>
                                    <h2 class="section-title">Gateway WhatsApp — OpenWA (Self-Hosted)</h2>
                                    <div style="font-size: 0.78rem; color: #64748b; margin-top: 0.2rem;">
                                        Instalação local baixada em <code>services/openwa</code> •
                                        <a href="<?= esc((string) $waApiUrl) ?>" target="_blank" style="color: #dd4814; font-weight: 600;">Abrir Dashboard OpenWA ↗</a>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                                    <span class="status-pill <?= ! empty($waStatusInfo['online']) ? '' : 'inactive' ?>">
                                        <?= ! empty($waStatusInfo['online']) ? 'Gateway Online' : 'Gateway Offline' ?>
                                    </span>
                                    <span class="status-pill <?= $waEnabled === 1 ? '' : 'inactive' ?>">
                                        <?= $waEnabled === 1 ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                    <?php if ($waSaved): ?>
                                        <button
                                            type="submit"
                                            formaction="<?= site_url('settings/whatsapp/toggle') ?>"
                                            formnovalidate
                                            style="padding: 0.32rem 0.78rem; border-radius: 6px; font-size: 0.76rem; font-weight: 700; cursor: pointer; border: 1px solid <?= $waEnabled === 1 ? '#fecaca' : '#bbf7d0' ?>; background-color: <?= $waEnabled === 1 ? '#fef2f2' : '#f0fdf4' ?>; color: <?= $waEnabled === 1 ? '#dc2626' : '#15803d' ?>;">
                                            <?= $waEnabled === 1 ? 'Desativar WhatsApp' : 'Ativar WhatsApp' ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (! empty($waStatusInfo['message'])): ?>
                                <div style="padding: 0.65rem 0.9rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.82rem; border: 1px solid <?= ! empty($waStatusInfo['online']) ? '#bbf7d0' : '#fde68a' ?>; background-color: <?= ! empty($waStatusInfo['online']) ? '#f0fdf4' : '#fffbeb' ?>; color: <?= ! empty($waStatusInfo['online']) ? '#166534' : '#92400e' ?>;">
                                    <?= esc((string) $waStatusInfo['message']) ?>
                                    <?php if (empty($waStatusInfo['online'])): ?>
                                        <div style="margin-top: 0.3rem; color: #78350f;">
                                            Para iniciar o OpenWA localmente, execute no terminal: <code>cd services/openwa &amp;&amp; npm run dev</code> (ou <code>docker compose -f docker-compose.dev.yml up -d</code>).
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="wa_api_url">URL do Gateway OpenWA</label>
                                    <input
                                        type="url"
                                        id="wa_api_url"
                                        name="api_url"
                                        class="form-control"
                                        placeholder="http://localhost:2785"
                                        value="<?= esc((string) $waApiUrl) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="wa_api_key">
                                        Chave da API (<code>X-API-Key</code>)
                                        <?php if ($waDetectedKey !== '' && ! $waHasKey): ?>
                                            <button type="button" id="waFillDetectedKeyBtn" style="margin-left: 0.4rem; font-size: 0.72rem; padding: 0.1rem 0.45rem; border-radius: 4px; border: 1px solid #86efac; background: #f0fdf4; color: #166534; cursor: pointer;">
                                                Usar chave detectada
                                            </button>
                                        <?php endif; ?>
                                    </label>
                                    <input
                                        type="password"
                                        id="wa_api_key"
                                        name="api_key"
                                        class="form-control"
                                        placeholder="<?= $waHasKey ? '•••••••••••• (deixe em branco para manter a atual)' : 'Cole a chave gerada em services/openwa/data/.api-key' ?>"
                                    >
                                </div>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label for="wa_session_name">Nome da Sessão</label>
                                    <input
                                        type="text"
                                        id="wa_session_name"
                                        name="session_name"
                                        class="form-control"
                                        placeholder="prestacao-contas"
                                        value="<?= esc((string) $waSessionName) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="wa_session_id">ID da Sessão (OpenWA)</label>
                                    <input
                                        type="text"
                                        id="wa_session_id"
                                        name="session_id"
                                        class="form-control"
                                        placeholder="Preenchido automaticamente ao criar sessão"
                                        value="<?= esc((string) $waSessionId) ?>"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="wa_engine_type">Engine WhatsApp</label>
                                    <select id="wa_engine_type" name="engine_type" class="form-control">
                                        <option value="whatsapp-web.js" <?= $waEngineType === 'whatsapp-web.js' ? 'selected' : '' ?>>whatsapp-web.js (Recomendado — menor risco)</option>
                                        <option value="baileys" <?= $waEngineType === 'baileys' ? 'selected' : '' ?>>Baileys (Mais leve em RAM)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label for="wa_country_code">DDI Padrão</label>
                                    <input
                                        type="text"
                                        id="wa_country_code"
                                        name="default_country_code"
                                        class="form-control"
                                        placeholder="55"
                                        value="<?= esc((string) $waCountryCode) ?>"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="wa_timeout">Timeout da API (segundos)</label>
                                    <input
                                        type="number"
                                        id="wa_timeout"
                                        name="timeout"
                                        class="form-control"
                                        value="<?= esc((string) $waTimeout) ?>"
                                        min="3"
                                        max="120"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="wa_webhook_url">Webhook URL (Opcional)</label>
                                    <input
                                        type="url"
                                        id="wa_webhook_url"
                                        name="webhook_url"
                                        class="form-control"
                                        placeholder="https://seudominio.com/webhook/whatsapp"
                                        value="<?= esc((string) $waWebhookUrl) ?>"
                                    >
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 0.4rem;">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="status" value="1" <?= $waEnabled === 1 ? 'checked' : '' ?>>
                                    Integração com WhatsApp (OpenWA) ativa
                                </label>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-submit">Salvar Configurações do WhatsApp</button>
                            </div>
                        </form>

                        <hr class="section-divider">

                        <div class="form-row-2" style="align-items: start;">
                            <!-- Conectar Sessão / QR Code -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem;">
                                <h3 style="font-size: 0.92rem; color: #0f172a; margin-bottom: 0.35rem;">Conectar Aparelho (QR Code)</h3>
                                <p style="font-size: 0.78rem; color: #64748b; margin-bottom: 0.85rem;">
                                    Salve a URL e a API Key primeiro, depois clique abaixo para criar/iniciar a sessão e escanear o QR Code no WhatsApp.
                                </p>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                                    <button type="button" id="waStartSessionBtn" class="btn-submit" style="padding: 0.45rem 0.9rem; font-size: 0.8rem;">
                                        Iniciar Sessão / Gerar QR
                                    </button>
                                    <button type="button" id="waRefreshQrBtn" style="padding: 0.45rem 0.85rem; font-size: 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-weight: 600; cursor: pointer;">
                                        Atualizar Status / QR
                                    </button>
                                </div>
                                <div id="waQrFeedback" style="font-size: 0.8rem; color: #475569; margin-bottom: 0.5rem;"></div>
                                <div id="waQrBox" style="display: none; text-align: center; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 0.85rem;">
                                    <img id="waQrImg" src="" alt="QR Code WhatsApp" style="max-width: 220px; width: 100%; height: auto; display: none; margin: 0 auto;">
                                    <pre id="waQrText" style="display: none; font-size: 0.72rem; word-break: break-all; white-space: pre-wrap; color: #334155;"></pre>
                                </div>
                            </div>

                            <!-- Enviar Mensagem de Teste -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem;">
                                <h3 style="font-size: 0.92rem; color: #0f172a; margin-bottom: 0.35rem;">Enviar Mensagem de Teste</h3>
                                <p style="font-size: 0.78rem; color: #64748b; margin-bottom: 0.85rem;">
                                    Valide o envio pelo OpenWA informando um número com DDD (ex: <code>49999999999</code>).
                                </p>
                                <form action="<?= site_url('settings/whatsapp/test') ?>" method="post" style="display: flex; flex-direction: column; gap: 0.65rem;">
                                    <?= csrf_field() ?>
                                    <input
                                        type="text"
                                        name="test_phone"
                                        class="form-control"
                                        placeholder="Ex: 49999999999 ou 5549999999999"
                                        required
                                    >
                                    <input
                                        type="text"
                                        name="test_message"
                                        class="form-control"
                                        value="Teste de integração do Sistema de Prestação de Contas via OpenWA."
                                    >
                                    <div>
                                        <button type="submit" class="btn-submit" style="padding: 0.45rem 0.95rem; font-size: 0.8rem; background-color: #16a34a;">
                                            Enviar Teste no WhatsApp
                                        </button>
                                    </div>
                                </form>

                                <hr class="section-divider" style="margin: 0.95rem 0;">

                                <h3 style="font-size: 0.9rem; color: #0f172a; margin-bottom: 0.3rem;">Alertas Automáticos de Documentos Atrasados</h3>
                                <p style="font-size: 0.77rem; color: #64748b; margin-bottom: 0.7rem;">
                                    Envia automaticamente no WhatsApp de cada usuário responsável pelos itens vencidos (agendado diariamente às 08:00 via <code>php artisan whatsapp:notify-overdue</code>).
                                </p>
                                <form action="<?= site_url('settings/whatsapp/notify-overdue') ?>" method="post" style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-submit" style="padding: 0.45rem 0.9rem; font-size: 0.8rem; background-color: #0f172a;">
                                        Disparar Alertas de Atraso Agora
                                    </button>
                                    <label style="font-size: 0.76rem; color: #475569; display: inline-flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                        <input type="checkbox" name="force" value="1">
                                        Reenviar mesmo se já avisado hoje
                                    </label>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        (function () {
            const logoInput = document.getElementById('app_logo_input');
            const logoPreview = document.getElementById('generalLogoPreview');
            const cnpjInput = document.getElementById('general_cnpj');

            if (logoInput && logoPreview) {
                logoInput.addEventListener('change', function () {
                    const file = this.files && this.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        logoPreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                });
            }

            if (cnpjInput) {
                cnpjInput.addEventListener('input', function () {
                    let v = this.value.replace(/\D/g, '').slice(0, 14);
                    if (v.length > 12) {
                        v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2}).*/, '$1.$2.$3/$4-$5');
                    } else if (v.length > 8) {
                        v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{0,4}).*/, '$1.$2.$3/$4');
                    } else if (v.length > 5) {
                        v = v.replace(/^(\d{2})(\d{3})(\d{0,3}).*/, '$1.$2.$3');
                    } else if (v.length > 2) {
                        v = v.replace(/^(\d{2})(\d{0,3}).*/, '$1.$2');
                    }
                    this.value = v;
                });
            }

            // Pré-visualização em tempo real da Capa do PDF Unificado
            const elShowLogo = document.getElementById('pdf_cover_show_logo');
            const elShowBorder = document.getElementById('pdf_cover_show_border');
            const elLabel = document.getElementById('pdf_cover_label');
            const elSubtitle = document.getElementById('pdf_cover_subtitle');
            const elFooter = document.getElementById('pdf_cover_footer');
            const elPrimary = document.getElementById('pdf_cover_primary_color');
            const elPrimaryHex = document.getElementById('pdf_cover_primary_hex');
            const elAccent = document.getElementById('pdf_cover_accent_color');
            const elAccentHex = document.getElementById('pdf_cover_accent_hex');

            function updateCoverPreview() {
                const frame = document.getElementById('coverPreviewFrame');
                const topBar = document.getElementById('coverPreviewTopBar');
                const logo = document.getElementById('coverPreviewLogo');
                const company = document.getElementById('coverPreviewCompany');
                const app = document.getElementById('coverPreviewApp');
                const accentLine = document.getElementById('coverPreviewAccentLine');
                const label = document.getElementById('coverPreviewLabel');
                const title = document.getElementById('coverPreviewTitle');
                const subtitle = document.getElementById('coverPreviewSubtitle');
                const footer = document.getElementById('coverPreviewFooter');

                if (logo && elShowLogo) logo.style.display = elShowLogo.checked ? 'block' : 'none';
                if (frame && elShowBorder) frame.style.border = elShowBorder.checked ? '1px solid #cbd5e1' : '1px solid transparent';
                if (label && elLabel) label.textContent = elLabel.value || 'PRESTAÇÃO DE CONTAS';
                if (subtitle && elSubtitle) subtitle.textContent = elSubtitle.value || 'DOSSIÊ DOCUMENTAL CONSOLIDADO';
                if (footer && elFooter) footer.textContent = elFooter.value || <?= json_encode($gCompanyName . ' - ' . $gAppName, JSON_UNESCAPED_UNICODE) ?>;

                if (elPrimary) {
                    const pc = elPrimary.value;
                    if (elPrimaryHex) elPrimaryHex.value = pc;
                    if (topBar) topBar.style.backgroundColor = pc;
                    if (company) company.style.color = pc;
                    if (title) title.style.color = pc;
                    if (subtitle) subtitle.style.color = pc;
                }
                if (elAccent) {
                    const ac = elAccent.value;
                    if (elAccentHex) elAccentHex.value = ac;
                    if (app) app.style.color = ac;
                    if (accentLine) accentLine.style.backgroundColor = ac;
                }
            }

            [elShowLogo, elShowBorder, elLabel, elSubtitle, elFooter, elPrimary, elAccent].forEach(function (el) {
                if (el) {
                    el.addEventListener('input', updateCoverPreview);
                    el.addEventListener('change', updateCoverPreview);
                }
            });

            const elCoverArtInput = document.getElementById('pdf_cover_art_input');
            const elCoverArtBg = document.getElementById('coverPreviewArtBg');
            if (elCoverArtInput && elCoverArtBg) {
                elCoverArtInput.addEventListener('change', function () {
                    const file = this.files && this.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        elCoverArtBg.src = e.target.result;
                        elCoverArtBg.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                });
            }

            // WhatsApp (OpenWA): preenchimento de chave detectada e QR Code
            const waFillKeyBtn = document.getElementById('waFillDetectedKeyBtn');
            const waKeyInput = document.getElementById('wa_api_key');
            if (waFillKeyBtn && waKeyInput) {
                waFillKeyBtn.addEventListener('click', function () {
                    waKeyInput.type = 'text';
                    waKeyInput.value = <?= json_encode($waDetectedKey, JSON_UNESCAPED_UNICODE) ?>;
                });
            }

            const waStartBtn = document.getElementById('waStartSessionBtn');
            const waRefreshBtn = document.getElementById('waRefreshQrBtn');
            const waSessionIdInput = document.getElementById('wa_session_id');
            const waSessionNameInput = document.getElementById('wa_session_name');
            const waQrFeedback = document.getElementById('waQrFeedback');
            const waQrBox = document.getElementById('waQrBox');
            const waQrImg = document.getElementById('waQrImg');
            const waQrText = document.getElementById('waQrText');
            const waCsrf = <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE) ?>;

            let waPollTimer = null;
            let waPollTries = 0;

            function stopWaPolling() {
                if (waPollTimer) {
                    clearTimeout(waPollTimer);
                    waPollTimer = null;
                }
            }

            function pollWaQr(sessionId) {
                stopWaPolling();
                waPollTries++;
                if (waPollTries > 18) {
                    if (waQrFeedback) {
                        waQrFeedback.style.color = '#92400e';
                        waQrFeedback.textContent = 'A sessão ainda está iniciando. Clique em "Atualizar Status / QR" em alguns segundos.';
                    }
                    return;
                }
                waPollTimer = setTimeout(function () {
                    const sid = encodeURIComponent(sessionId || (waSessionIdInput ? waSessionIdInput.value.trim() : ''));
                    fetch(<?= json_encode(site_url('settings/whatsapp/session/qr'), JSON_UNESCAPED_UNICODE) ?> + '?session_id=' + sid, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(data => renderWaQrResult(data, true))
                    .catch(() => {});
                }, 2500);
            }

            function renderWaQrResult(data, allowPoll) {
                if (!waQrFeedback || !waQrBox) return;
                if (!data || !data.success) {
                    stopWaPolling();
                    waQrFeedback.style.color = '#b91c1c';
                    waQrFeedback.textContent = (data && data.error) ? data.error : 'Não foi possível comunicar com o OpenWA.';
                    waQrBox.style.display = 'none';
                    return;
                }
                if (data.session_id && waSessionIdInput) {
                    waSessionIdInput.value = data.session_id;
                }

                const st = String(data.status || 'starting').toLowerCase();
                const connectedStates = ['ready', 'connected', 'authenticated'];

                if (data.qr) {
                    stopWaPolling();
                    waQrFeedback.style.color = '#166534';
                    waQrFeedback.textContent = 'QR Code pronto! Escaneie com o WhatsApp do seu celular (Status: ' + st + ').';
                    waQrBox.style.display = 'block';
                    if (String(data.qr).startsWith('data:image/')) {
                        if (waQrImg) { waQrImg.src = data.qr; waQrImg.style.display = 'block'; }
                        if (waQrText) { waQrText.style.display = 'none'; }
                    } else {
                        if (waQrImg) { waQrImg.style.display = 'none'; }
                        if (waQrText) { waQrText.textContent = data.qr; waQrText.style.display = 'block'; }
                    }
                } else if (connectedStates.includes(st)) {
                    stopWaPolling();
                    waQrFeedback.style.color = '#166534';
                    waQrFeedback.textContent = 'WhatsApp conectado com sucesso! (Status: ' + st + ')';
                    waQrBox.style.display = 'none';
                } else {
                    waQrBox.style.display = 'none';
                    waQrFeedback.style.color = '#475569';
                    waQrFeedback.textContent = 'Inicializando navegador do WhatsApp (Status: ' + st + ')... Aguardando QR Code...';
                    if (allowPoll) {
                        pollWaQr(data.session_id || (waSessionIdInput ? waSessionIdInput.value.trim() : ''));
                    }
                }
            }

            if (waStartBtn) {
                waStartBtn.addEventListener('click', function () {
                    stopWaPolling();
                    waPollTries = 0;
                    if (waQrFeedback) {
                        waQrFeedback.style.color = '#475569';
                        waQrFeedback.textContent = 'Criando/iniciando sessão no OpenWA...';
                    }
                    const fd = new FormData();
                    fd.append('_token', waCsrf);
                    if (waSessionIdInput) fd.append('session_id', waSessionIdInput.value.trim());
                    if (waSessionNameInput) fd.append('session_name', waSessionNameInput.value.trim());

                    fetch(<?= json_encode(site_url('settings/whatsapp/session/start'), JSON_UNESCAPED_UNICODE) ?>, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => renderWaQrResult(data, true))
                    .catch(() => renderWaQrResult({ success: false, error: 'Erro de rede ao conectar ao OpenWA.' }, false));
                });
            }

            if (waRefreshBtn) {
                waRefreshBtn.addEventListener('click', function () {
                    stopWaPolling();
                    waPollTries = 0;
                    if (waQrFeedback) {
                        waQrFeedback.style.color = '#475569';
                        waQrFeedback.textContent = 'Consultando status e QR Code...';
                    }
                    const sid = waSessionIdInput ? encodeURIComponent(waSessionIdInput.value.trim()) : '';
                    fetch(<?= json_encode(site_url('settings/whatsapp/session/qr'), JSON_UNESCAPED_UNICODE) ?> + '?session_id=' + sid, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(data => renderWaQrResult(data, false))
                    .catch(() => renderWaQrResult({ success: false, error: 'Erro de rede ao consultar QR Code.' }, false));
                });
            }
        })();
    </script>
</body>
</html>

