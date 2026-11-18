<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Rápido - Município de Arabutã</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .login-brand {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .login-brand img {
            max-width: 260px;
            width: 100%;
            height: auto;
            display: block;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.35));
        }
        .login-card {
            background: #ffffff;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.15);
            padding: 2.25rem 2rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 1.65rem;
        }
        .login-header h1 {
            font-size: 1.45rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }
        .login-header p {
            font-size: 0.88rem;
            color: #64748b;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .alert ul {
            margin-left: 1.1rem;
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
            padding: 0.72rem 0.9rem;
            font-size: 0.92rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background-color: #ffffff;
            color: #0f172a;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            outline: none;
        }
        .form-control:focus {
            border-color: #dd4814;
            box-shadow: 0 0 0 3px rgba(221, 72, 20, 0.15);
        }
        .invalid-credentials-msg {
            color: #dc2626;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 0.6rem 0.85rem;
            font-size: 0.84rem;
            font-weight: 600;
            margin-top: -0.35rem;
            margin-bottom: 0.95rem;
            text-align: center;
        }
        .btn-submit {
            width: 100%;
            padding: 0.82rem 1rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
            background-color: #dd4814;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.15s ease;
            margin-top: 0.35rem;
        }
        .btn-submit:hover {
            background-color: #c23c0f;
        }
        .quick-hint {
            margin-top: 1.35rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.8rem;
            color: #64748b;
            text-align: center;
            line-height: 1.5;
        }
        .quick-hint code {
            background: #f1f5f9;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            color: #0f172a;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-brand">
            <img src="<?= base_url('assets/images/logo-arabuta.png') ?>" alt="Município de Arabutã">
        </div>

        <div class="login-card">
            <div class="login-header">
                <h1>Login Rápido</h1>
                <p>Selecione o usuário abaixo para acessar o sistema</p>
            </div>

            <form action="<?= site_url('login') ?>" method="post" id="quickLoginForm">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="login">Selecionar Usuário</label>
                    <select id="login" name="login" class="form-control" required autofocus>
                        <option value="">-- Escolha uma conta --</option>
                        <?php foreach (($users ?? []) as $u): ?>
                            <?php
                            $uEmail = $u['email'] ?? '';
                            $uName  = $u['name'] ?? $uEmail;
                            $uRole  = ($u['role'] ?? 'user') === 'admin' ? 'ADMIN' : 'USER';
                            $selected = (old('login') === $uEmail) ? 'selected' : '';
                            ?>
                            <option value="<?= esc($uEmail) ?>" <?= $selected ?>>
                                [<?= esc($uRole) ?>] <?= esc($uName) ?> (<?= esc($uEmail) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Preenchida automaticamente no login rápido"
                        value="123456"
                    >
                </div>

                <?php if (session()->getFlashdata('error') || session()->getFlashdata('errors')): ?>
                    <div class="invalid-credentials-msg">
                        <?= esc((string) (session()->getFlashdata('error') ?: 'Credenciais inválidas.')) ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-submit">Entrar no Sistema</button>
            </form>

            <div class="quick-hint">
                Modo de acesso rápido ativo — senha padrão <code>123456</code>
            </div>
        </div>
    </div>

    <script>
        const selectEl = document.getElementById('login');
        const passwordEl = document.getElementById('password');

        if (selectEl && passwordEl) {
            selectEl.addEventListener('change', function () {
                if (this.value && !passwordEl.value) {
                    passwordEl.value = '123456';
                }
            });
        }
    </script>
</body>
</html>
