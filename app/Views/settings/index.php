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
                    <a href="<?= site_url('settings?tab=ollama') ?>" class="tab-btn <?= $currentTab === 'ollama' ? 'active' : '' ?>">
                        Inteligência Artificial (Ollama / Qwen-VL)
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
                                <h2 class="section-title">Servidor de Envio (SMTP)</h2>
                                <span class="status-pill <?= $status === 1 ? '' : 'inactive' ?>">
                                    <?= $status === 1 ? 'Ativo' : 'Inativo' ?>
                                </span>
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
