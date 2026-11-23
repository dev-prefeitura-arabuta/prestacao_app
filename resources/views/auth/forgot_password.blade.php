<?php
$genSettings = \App\Models\GeneralSettings::getSettings();
$appName     = $genSettings['app_name'] ?? 'Sistema Patrimonial';
$companyName = $genSettings['company_name'] ?? 'Município de Arabutã';
$logoUrl     = site_url('app-logo') . (! empty($genSettings['app_logo']) ? '?v=' . md5((string) $genSettings['app_logo']) : '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Etapa 1 - <?= esc($appName) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 55%, #f1f5f9 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .auth-wrapper {
            width: 100%;
            max-width: 460px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .auth-brand {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .auth-brand img {
            max-width: 220px;
            width: 100%;
            height: auto;
            display: block;
        }
        .auth-card {
            background: #ffffff;
            width: 100%;
            border-radius: 14px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
            padding: 2rem 2rem 1.75rem;
            border: 1px solid #e2e8f0;
        }

        /* Stepper Profissional */
        .stepper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.75rem;
            position: relative;
        }
        .stepper::before {
            content: "";
            position: absolute;
            top: 15px;
            left: 16%;
            right: 16%;
            height: 2px;
            background: #e2e8f0;
            z-index: 0;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            z-index: 1;
            flex: 1;
        }
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 700;
            background: #f1f5f9;
            color: #64748b;
            border: 2px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        .step-item.active .step-circle {
            background: #dd4814;
            color: #ffffff;
            border-color: #dd4814;
            box-shadow: 0 0 0 4px rgba(221, 72, 20, 0.16);
        }
        .step-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .step-item.active .step-label {
            color: #0f172a;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 1.45rem;
        }
        .step-badge {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            color: #dd4814;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            margin-bottom: 0.6rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .auth-header h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.4rem;
        }
        .auth-header p {
            font-size: 0.86rem;
            color: #64748b;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 1.15rem;
        }
        .form-group label {
            display: block;
            font-size: 0.84rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }
        .form-control {
            width: 100%;
            padding: 0.74rem 0.92rem;
            font-size: 0.92rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            background-color: #ffffff;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .form-control:focus {
            border-color: #dd4814;
            box-shadow: 0 0 0 3px rgba(221, 72, 20, 0.14);
        }
        .alert-msg {
            border-radius: 8px;
            padding: 0.72rem 0.9rem;
            font-size: 0.84rem;
            font-weight: 600;
            margin-bottom: 1.1rem;
            text-align: center;
            line-height: 1.4;
        }
        .alert-error {
            color: #b91c1c;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
        }
        .alert-success {
            color: #15803d;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
        }
        .btn-submit {
            width: 100%;
            padding: 0.82rem 1rem;
            font-size: 0.93rem;
            font-weight: 700;
            color: #ffffff;
            background: linear-gradient(135deg, #dd4814 0%, #c23c0f 100%);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.15s ease, opacity 0.15s ease;
            box-shadow: 0 6px 16px -4px rgba(221, 72, 20, 0.45);
        }
        .btn-submit:hover {
            box-shadow: 0 10px 20px -6px rgba(221, 72, 20, 0.55);
        }
        .btn-submit:active {
            transform: translateY(1px);
        }
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: wait;
        }
        .auth-footer {
            margin-top: 1.35rem;
            padding-top: 1.05rem;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 0.84rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .auth-footer a {
            color: #475569;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-footer a:hover {
            color: #0f172a;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-brand">
            <img src="<?= esc($logoUrl) ?>" alt="<?= esc($companyName) ?>">
        </div>

        <div class="auth-card">
            <div class="stepper" aria-label="Progresso da recuperação de senha">
                <div class="step-item active">
                    <div class="step-circle">1</div>
                    <span class="step-label">E-mail</span>
                </div>
                <div class="step-item">
                    <div class="step-circle">2</div>
                    <span class="step-label">Código</span>
                </div>
                <div class="step-item">
                    <div class="step-circle">3</div>
                    <span class="step-label">Nova Senha</span>
                </div>
            </div>

            <div class="auth-header">
                <span class="step-badge">Etapa 1 de 3</span>
                <h1>Recuperação de Senha</h1>
                <p>Informe o e-mail vinculado à sua conta. Enviaremos um código de 6 dígitos válido por <strong>1 minuto e 30 segundos</strong>.</p>
            </div>

            <?php if (session('error')): ?>
                <div class="alert-msg alert-error"><?= esc((string) session('error')) ?></div>
            <?php endif; ?>

            <?php if (session('success')): ?>
                <div class="alert-msg alert-success"><?= esc((string) session('success')) ?></div>
            <?php endif; ?>

            <form id="forgotForm" action="<?= site_url('forgot-password') ?>" method="post">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email">E-mail cadastrado</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="seuemail@arabuta.sc.gov.br"
                        value="<?= esc((string) old('email')) ?>"
                        required
                        autofocus
                    >
                </div>

                <button type="submit" id="sendCodeBtn" class="btn-submit">Enviar código e continuar →</button>
            </form>

            <div class="auth-footer">
                <a href="<?= site_url('login') ?>">← Voltar para o Login</a>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('forgotForm');
            const btn = document.getElementById('sendCodeBtn');
            if (form && btn) {
                form.addEventListener('submit', function () {
                    btn.disabled = true;
                    btn.textContent = 'Enviando código...';
                });
            }
        })();
    </script>
</body>
</html>
