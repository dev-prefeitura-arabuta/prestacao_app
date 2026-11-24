<?php
$genSettings      = \App\Models\GeneralSettings::getSettings();
$appName          = $genSettings['app_name'] ?? 'Sistema Patrimonial';
$companyName      = $genSettings['company_name'] ?? 'Município de Arabutã';
$logoUrl          = site_url('app-logo') . (! empty($genSettings['app_logo']) ? '?v=' . md5((string) $genSettings['app_logo']) : '');
$prefillEmail     = (string) ($email ?? old('email', ''));
$currentStep      = (int) ($step ?? 2);
$remainingSeconds = max(0, (int) ($remainingSeconds ?? 0));
$totalSeconds     = max(1, (int) ($totalSeconds ?? 90));
$oldCode          = preg_replace('/\D/', '', (string) old('code', ''));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Etapa <?= esc((string) $currentStep) ?> - <?= esc($appName) ?></title>
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
            max-width: 470px;
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
            margin-bottom: 1.65rem;
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
        .stepper-progress-line {
            position: absolute;
            top: 15px;
            left: 16%;
            height: 2px;
            background: #16a34a;
            z-index: 0;
            transition: width 0.3s ease;
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
        .step-item.done .step-circle {
            background: #16a34a;
            color: #ffffff;
            border-color: #16a34a;
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
        .step-item.done .step-label {
            color: #15803d;
        }
        .step-item.active .step-label {
            color: #0f172a;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 1.35rem;
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
            margin-bottom: 0.55rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .step-badge.verified {
            color: #15803d;
            background: #f0fdf4;
            border-color: #bbf7d0;
        }
        .auth-header h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.38rem;
        }
        .auth-header p {
            font-size: 0.85rem;
            color: #64748b;
            line-height: 1.5;
        }

        .email-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.58rem 0.85rem;
            margin-bottom: 1.15rem;
            font-size: 0.83rem;
            color: #334155;
        }
        .email-pill strong {
            color: #0f172a;
            word-break: break-all;
        }
        .email-pill a {
            color: #dd4814;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }
        .email-pill a:hover {
            text-decoration: underline;
        }

        /* Cronômetro de 1m30s */
        .timer-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.25rem;
            transition: background-color 0.25s ease, border-color 0.25s ease;
        }
        .timer-card.warning {
            background: #fffbeb;
            border-color: #fde68a;
        }
        .timer-card.expired {
            background: #fef2f2;
            border-color: #fecaca;
        }
        .timer-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.55rem;
            gap: 0.5rem;
        }
        .timer-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
        }
        .timer-card.warning .timer-label { color: #b45309; }
        .timer-card.expired .timer-label { color: #b91c1c; }
        .timer-clock {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 0.18rem 0.58rem;
            border-radius: 6px;
            letter-spacing: 0.05em;
        }
        .timer-card.warning .timer-clock {
            color: #b45309;
            border-color: #fcd34d;
        }
        .timer-card.expired .timer-clock {
            color: #dc2626;
            border-color: #fca5a5;
        }
        .timer-bar-track {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }
        .timer-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #dd4814 0%, #f97316 100%);
            border-radius: 999px;
            transition: width 1s linear, background 0.25s ease;
        }
        .timer-card.warning .timer-bar-fill {
            background: linear-gradient(90deg, #d97706 0%, #f59e0b 100%);
        }
        .timer-card.expired .timer-bar-fill {
            background: #dc2626;
        }

        /* 6 Caixinhas OTP */
        .otp-label {
            display: block;
            text-align: center;
            font-size: 0.83rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.65rem;
        }
        .otp-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 0.55rem;
            margin-bottom: 1.25rem;
        }
        .otp-box {
            width: 100%;
            height: 54px;
            text-align: center;
            font-size: 1.45rem;
            font-weight: 800;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #0f172a;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 9px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }
        .otp-box:focus {
            border-color: #dd4814;
            box-shadow: 0 0 0 3px rgba(221, 72, 20, 0.16);
        }
        .otp-box.filled {
            border-color: #0f172a;
            background-color: #f8fafc;
        }
        .otp-box:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-size: 0.84rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.38rem;
        }
        .password-wrap {
            position: relative;
        }
        .form-control {
            width: 100%;
            padding: 0.72rem 2.6rem 0.72rem 0.9rem;
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
        .toggle-pass-btn {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0.25rem 0.4rem;
        }
        .toggle-pass-btn:hover {
            color: #0f172a;
        }
        .strength-wrap {
            margin-top: 0.45rem;
        }
        .strength-track {
            height: 5px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 999px;
            transition: width 0.2s ease, background-color 0.2s ease;
        }
        .strength-text {
            font-size: 0.74rem;
            color: #64748b;
            margin-top: 0.28rem;
            font-weight: 600;
        }

        .alert-msg {
            border-radius: 8px;
            padding: 0.72rem 0.9rem;
            font-size: 0.83rem;
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
        .btn-submit:hover:not(:disabled) {
            box-shadow: 0 10px 20px -6px rgba(221, 72, 20, 0.55);
        }
        .btn-submit:active:not(:disabled) {
            transform: translateY(1px);
        }
        .btn-submit:disabled {
            background: #94a3b8;
            box-shadow: none;
            cursor: not-allowed;
        }

        .resend-box {
            margin-top: 0.9rem;
            text-align: center;
        }
        .btn-resend {
            width: 100%;
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f172a;
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-resend:hover:not(:disabled) {
            background: #f1f5f9;
            border-color: #94a3b8;
        }
        .btn-resend:disabled {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #e2e8f0;
            cursor: not-allowed;
        }

        .auth-footer {
            margin-top: 1.3rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 0.83rem;
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
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

        @media (max-width: 420px) {
            .auth-card { padding: 1.5rem 1.25rem; }
            .otp-box { height: 48px; font-size: 1.25rem; }
            .otp-grid { gap: 0.4rem; }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-brand">
            <img src="<?= esc($logoUrl) ?>" alt="<?= esc($companyName) ?>">
        </div>

        <div class="auth-card">
            <!-- Stepper de 3 Etapas -->
            <div class="stepper" aria-label="Progresso da recuperação de senha">
                <div class="stepper-progress-line" style="width: <?= $currentStep === 3 ? '68%' : '34%' ?>;"></div>
                <div class="step-item done">
                    <div class="step-circle">✓</div>
                    <span class="step-label">E-mail</span>
                </div>
                <div class="step-item <?= $currentStep === 3 ? 'done' : 'active' ?>">
                    <div class="step-circle"><?= $currentStep === 3 ? '✓' : '2' ?></div>
                    <span class="step-label">Código</span>
                </div>
                <div class="step-item <?= $currentStep === 3 ? 'active' : '' ?>">
                    <div class="step-circle">3</div>
                    <span class="step-label">Nova Senha</span>
                </div>
            </div>

            <?php if (session('error')): ?>
                <div class="alert-msg alert-error"><?= esc((string) session('error')) ?></div>
            <?php endif; ?>

            <?php if (session('success')): ?>
                <div class="alert-msg alert-success"><?= esc((string) session('success')) ?></div>
            <?php endif; ?>

            <?php if ($currentStep === 2): ?>
                <!-- ETAPA 2: VALIDAÇÃO DO CÓDIGO COM CRONÔMETRO DE 01:30 -->
                <div class="auth-header">
                    <span class="step-badge">Etapa 2 de 3</span>
                    <h1>Verificar Código</h1>
                    <p>Digite os 6 dígitos enviados para o seu e-mail e clique em <strong>Continuar</strong> para avançar.</p>
                </div>

                <div class="email-pill">
                    <span>Enviado para: <strong><?= esc($prefillEmail) ?></strong></span>
                    <a href="<?= site_url('forgot-password') ?>">Alterar</a>
                </div>

                <div id="timerCard" class="timer-card <?= $remainingSeconds <= 0 ? 'expired' : ($remainingSeconds <= 20 ? 'warning' : '') ?>">
                    <div class="timer-top">
                        <span id="timerLabel" class="timer-label">
                            <?= $remainingSeconds > 0 ? 'Tempo restante do código' : 'Código expirado — solicite um novo' ?>
                        </span>
                        <span id="timerClock" class="timer-clock">
                            <?= sprintf('%02d:%02d', intdiv($remainingSeconds, 60), $remainingSeconds % 60) ?>
                        </span>
                    </div>
                    <div class="timer-bar-track">
                        <div
                            id="timerBarFill"
                            class="timer-bar-fill"
                            style="width: <?= (int) round(($remainingSeconds / $totalSeconds) * 100) ?>%;">
                        </div>
                    </div>
                </div>

                <form id="verifyCodeForm" action="<?= site_url('reset-password/verify') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="email" value="<?= esc($prefillEmail) ?>">
                    <input type="hidden" id="fullCodeInput" name="code" value="<?= esc($oldCode) ?>">

                    <label class="otp-label" for="otp-0">Código de verificação (6 dígitos)</label>
                    <div class="otp-grid" id="otpGrid">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <input
                                type="text"
                                id="otp-<?= $i ?>"
                                class="otp-box <?= isset($oldCode[$i]) ? 'filled' : '' ?>"
                                maxlength="1"
                                inputmode="numeric"
                                autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
                                value="<?= isset($oldCode[$i]) ? esc($oldCode[$i]) : '' ?>"
                                <?= $remainingSeconds <= 0 ? 'disabled' : '' ?>
                                <?= $i === 0 ? 'autofocus' : '' ?>
                            >
                        <?php endfor; ?>
                    </div>

                    <button
                        type="submit"
                        id="continueStepBtn"
                        class="btn-submit"
                        <?= ($remainingSeconds <= 0 || strlen($oldCode) < 6) ? 'disabled' : '' ?>>
                        Validar código e ir para próxima etapa →
                    </button>
                </form>

                <div class="resend-box">
                    <form id="resendForm" action="<?= site_url('reset-password/resend') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="email" value="<?= esc($prefillEmail) ?>">
                        <button
                            type="submit"
                            id="resendBtn"
                            class="btn-resend"
                            <?= $remainingSeconds > 0 ? 'disabled' : '' ?>>
                            <?= $remainingSeconds > 0
                                ? 'Reenviar código disponível em ' . sprintf('%02d:%02d', intdiv($remainingSeconds, 60), $remainingSeconds % 60)
                                : '↻ Reenviar novo código de 6 dígitos' ?>
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <!-- ETAPA 3: DEFINIR NOVA SENHA -->
                <div class="auth-header">
                    <span class="step-badge verified">✓ Etapa 3 de 3 • Código Verificado</span>
                    <h1>Criar Nova Senha</h1>
                    <p>Sua identidade foi confirmada. Escolha uma nova senha segura para acessar sua conta.</p>
                </div>

                <div class="email-pill">
                    <span>Conta verificada: <strong><?= esc($prefillEmail) ?></strong></span>
                </div>

                <form id="newPasswordForm" action="<?= site_url('reset-password') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="password">Nova Senha</label>
                        <div class="password-wrap">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Mínimo de 6 caracteres"
                                minlength="6"
                                required
                                autofocus
                            >
                            <button type="button" class="toggle-pass-btn" data-target="password">Mostrar</button>
                        </div>
                        <div class="strength-wrap">
                            <div class="strength-track">
                                <div id="strengthFill" class="strength-fill"></div>
                            </div>
                            <div id="strengthText" class="strength-text">Use pelo menos 6 caracteres (letras e números)</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nova Senha</label>
                        <div class="password-wrap">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-control"
                                placeholder="Repita a nova senha"
                                minlength="6"
                                required
                            >
                            <button type="button" class="toggle-pass-btn" data-target="confirm_password">Mostrar</button>
                        </div>
                    </div>

                    <button type="submit" id="savePasswordBtn" class="btn-submit">Confirmar e Alterar Senha</button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <a href="<?= site_url('login') ?>">← Voltar para o Login</a>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const currentStep = <?= (int) $currentStep ?>;

            if (currentStep === 2) {
                let remaining = <?= (int) $remainingSeconds ?>;
                const totalSeconds = <?= (int) $totalSeconds ?>;

                const timerCard = document.getElementById('timerCard');
                const timerLabel = document.getElementById('timerLabel');
                const timerClock = document.getElementById('timerClock');
                const timerBarFill = document.getElementById('timerBarFill');
                const resendBtn = document.getElementById('resendBtn');
                const resendForm = document.getElementById('resendForm');
                const continueBtn = document.getElementById('continueStepBtn');
                const verifyForm = document.getElementById('verifyCodeForm');
                const fullCodeInput = document.getElementById('fullCodeInput');
                const otpBoxes = Array.from(document.querySelectorAll('.otp-box'));

                function formatMMSS(sec) {
                    const s = Math.max(0, sec);
                    const m = Math.floor(s / 60);
                    const r = s % 60;
                    return String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
                }

                function syncOtpValue() {
                    const code = otpBoxes.map(b => b.value.replace(/\D/g, '')).join('');
                    if (fullCodeInput) fullCodeInput.value = code;
                    otpBoxes.forEach(b => {
                        if (b.value) {
                            b.classList.add('filled');
                        } else {
                            b.classList.remove('filled');
                        }
                    });
                    if (continueBtn) {
                        continueBtn.disabled = (remaining <= 0 || code.length < 6);
                    }
                }

                otpBoxes.forEach(function (box, idx) {
                    box.addEventListener('input', function () {
                        const digit = this.value.replace(/\D/g, '').slice(-1);
                        this.value = digit;
                        if (digit && idx < otpBoxes.length - 1) {
                            otpBoxes[idx + 1].focus();
                        }
                        syncOtpValue();
                    });

                    box.addEventListener('keydown', function (e) {
                        if (e.key === 'Backspace' && !this.value && idx > 0) {
                            otpBoxes[idx - 1].focus();
                            otpBoxes[idx - 1].value = '';
                            syncOtpValue();
                        } else if (e.key === 'ArrowLeft' && idx > 0) {
                            otpBoxes[idx - 1].focus();
                        } else if (e.key === 'ArrowRight' && idx < otpBoxes.length - 1) {
                            otpBoxes[idx + 1].focus();
                        }
                    });

                    box.addEventListener('paste', function (e) {
                        e.preventDefault();
                        const pasted = (e.clipboardData || window.clipboardData)
                            .getData('text')
                            .replace(/\D/g, '')
                            .slice(0, 6);
                        if (!pasted) return;
                        for (let i = 0; i < otpBoxes.length; i++) {
                            otpBoxes[i].value = pasted[i] || '';
                        }
                        const focusIdx = Math.min(pasted.length, otpBoxes.length - 1);
                        otpBoxes[focusIdx].focus();
                        syncOtpValue();
                    });
                });

                function updateTimerUI() {
                    const formatted = formatMMSS(remaining);
                    if (timerClock) timerClock.textContent = formatted;

                    const pct = Math.max(0, Math.min(100, (remaining / totalSeconds) * 100));
                    if (timerBarFill) timerBarFill.style.width = pct + '%';

                    if (remaining <= 0) {
                        if (timerCard) {
                            timerCard.classList.remove('warning');
                            timerCard.classList.add('expired');
                        }
                        if (timerLabel) timerLabel.textContent = 'Código expirado — solicite um novo';
                        otpBoxes.forEach(b => { b.disabled = true; });
                        if (continueBtn) continueBtn.disabled = true;
                        if (resendBtn) {
                            resendBtn.disabled = false;
                            resendBtn.textContent = '↻ Reenviar novo código de 6 dígitos';
                        }
                    } else {
                        if (timerCard) {
                            timerCard.classList.toggle('warning', remaining <= 20);
                        }
                        if (resendBtn) {
                            resendBtn.disabled = true;
                            resendBtn.textContent = 'Reenviar código disponível em ' + formatted;
                        }
                    }
                }

                updateTimerUI();
                syncOtpValue();

                if (remaining > 0) {
                    const interval = setInterval(function () {
                        remaining--;
                        updateTimerUI();
                        if (remaining <= 0) {
                            clearInterval(interval);
                        }
                    }, 1000);
                }

                if (verifyForm && continueBtn) {
                    verifyForm.addEventListener('submit', function () {
                        syncOtpValue();
                        continueBtn.disabled = true;
                        continueBtn.textContent = 'Validando código...';
                    });
                }

                if (resendForm && resendBtn) {
                    resendForm.addEventListener('submit', function () {
                        resendBtn.disabled = true;
                        resendBtn.textContent = 'Reenviando código...';
                    });
                }
            } else {
                // Etapa 3: Mostrar/ocultar senha + força da senha
                document.querySelectorAll('.toggle-pass-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const target = document.getElementById(this.getAttribute('data-target'));
                        if (!target) return;
                        const isPass = target.type === 'password';
                        target.type = isPass ? 'text' : 'password';
                        this.textContent = isPass ? 'Ocultar' : 'Mostrar';
                    });
                });

                const passInput = document.getElementById('password');
                const strengthFill = document.getElementById('strengthFill');
                const strengthText = document.getElementById('strengthText');

                if (passInput && strengthFill && strengthText) {
                    passInput.addEventListener('input', function () {
                        const v = this.value;
                        let score = 0;
                        if (v.length >= 6) score++;
                        if (v.length >= 10) score++;
                        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
                        if (/\d/.test(v)) score++;
                        if (/[^A-Za-z0-9]/.test(v)) score++;

                        if (!v) {
                            strengthFill.style.width = '0%';
                            strengthText.textContent = 'Use pelo menos 6 caracteres (letras e números)';
                            strengthText.style.color = '#64748b';
                        } else if (score <= 2) {
                            strengthFill.style.width = '33%';
                            strengthFill.style.backgroundColor = '#dc2626';
                            strengthText.textContent = 'Força da senha: Fraca';
                            strengthText.style.color = '#dc2626';
                        } else if (score <= 3) {
                            strengthFill.style.width = '66%';
                            strengthFill.style.backgroundColor = '#d97706';
                            strengthText.textContent = 'Força da senha: Média';
                            strengthText.style.color = '#b45309';
                        } else {
                            strengthFill.style.width = '100%';
                            strengthFill.style.backgroundColor = '#16a34a';
                            strengthText.textContent = 'Força da senha: Forte';
                            strengthText.style.color = '#15803d';
                        }
                    });
                }

                const newPassForm = document.getElementById('newPasswordForm');
                const saveBtn = document.getElementById('savePasswordBtn');
                if (newPassForm && saveBtn) {
                    newPassForm.addEventListener('submit', function () {
                        saveBtn.disabled = true;
                        saveBtn.textContent = 'Salvando nova senha...';
                    });
                }
            }
        })();
    </script>
</body>
</html>
