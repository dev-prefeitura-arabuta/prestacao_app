<?php
$genSettings = \App\Models\GeneralSettingsModel::getSettings();
$appName     = $genSettings['app_name'] ?? 'Sistema Patrimonial';
$companyName = $genSettings['company_name'] ?? 'Município de Arabutã';
$companyCnpj = trim((string) ($genSettings['cnpj'] ?? ''));
$logoUrl     = site_url('app-logo') . (! empty($genSettings['app_logo']) ? '?v=' . md5((string) $genSettings['app_logo']) : '');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($appName) ?> - <?= esc($companyName) ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #0f172a 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            width: 100%;
            padding: 1.15rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
            background-color: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(8px);
        }

        .brand-wrap {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .brand-wrap img {
            height: 40px;
            width: auto;
            display: block;
        }

        .brand-name {
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.01em;
        }

        .btn-login-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 1.15rem;
            background-color: #dd4814;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 600;
            transition: background-color 0.15s ease;
        }

        .btn-login-nav:hover {
            background-color: #c23c0f;
        }

        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
        }

        .hero-card {
            width: 100%;
            max-width: 720px;
            text-align: center;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 12px;
            padding: 3.25rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
        }

        .hero-logo {
            max-width: 240px;
            width: 100%;
            height: auto;
            margin: 0 auto 1.75rem;
            display: block;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.35));
        }

        .hero-tag {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #fb923c;
            background-color: rgba(221, 72, 20, 0.16);
            border: 1px solid rgba(251, 146, 60, 0.3);
            padding: 0.3rem 0.8rem;
            border-radius: 999px;
            margin-bottom: 1.15rem;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin-bottom: 0.85rem;
        }

        .hero-subtitle {
            font-size: 1rem;
            color: #cbd5e1;
            line-height: 1.65;
            max-width: 540px;
            margin: 0 auto 2.25rem;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.85rem;
            flex-wrap: wrap;
        }

        .btn-primary-lg {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.85rem 2rem;
            background-color: #dd4814;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.96rem;
            font-weight: 700;
            transition: background-color 0.15s ease, transform 0.12s ease;
        }

        .btn-primary-lg:hover {
            background-color: #c23c0f;
            transform: translateY(-1px);
        }

        .footer {
            text-align: center;
            padding: 1.25rem;
            font-size: 0.8rem;
            color: #94a3b8;
            border-top: 1px solid rgba(148, 163, 184, 0.12);
        }

        @media (max-width: 640px) {
            .top-bar {
                padding: 1rem 1.25rem;
            }

            .hero-card {
                padding: 2.25rem 1.5rem;
            }

            .hero-title {
                font-size: 1.9rem;
            }
        }
    </style>
</head>

<body>
    <header class="top-bar">
        <div class="brand-wrap">
            <span class="brand-name"><?= esc($appName) ?></span>
        </div>
        <a href="<?= site_url('login') ?>" class="btn-login-nav">Entrar no Sistema</a>
    </header>

    <main class="hero">
        <section class="hero-card">
            <img src="<?= esc($logoUrl) ?>" alt="<?= esc($companyName) ?>" class="hero-logo">
            <span class="hero-tag">Portal Institucional</span>
            <h1 class="hero-title"><?= esc($appName) ?></h1>
            <p class="hero-subtitle">
                Plataforma oficial para gestão, controle documental, prestação de contas e auditoria patrimonial de <?= esc($companyName) ?>.
            </p>
            <div class="hero-actions">
                <a href="<?= site_url('login') ?>" class="btn-primary-lg">
                    Acessar Sistema →
                </a>
            </div>
        </section>
    </main>

    <footer class="footer">
        &copy; <?= date('Y') ?> <?= esc($companyName) ?><?= $companyCnpj !== '' ? ' • CNPJ: ' . esc($companyCnpj) : '' ?> — <?= esc($appName) ?>
    </footer>
</body>

</html>
