<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página não encontrada</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: radial-gradient(circle at top, #111827 0%, #05070c 60%, #000000 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }

        .notfound-card {
            width: 100%;
            max-width: 560px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .notfound-art-wrap {
            width: 280px;
            max-width: 82vw;
            margin-bottom: 1.5rem;
            border-radius: 16px;
            overflow: hidden;
        }

        .notfound-art-wrap img {
            width: 100%;
            height: auto;
            display: block;
            mix-blend-mode: lighten;
        }

        .error-code {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #f97316;
            margin-bottom: 0.5rem;
        }

        .error-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.65rem;
        }

        .error-message {
            font-size: 0.94rem;
            color: #94a3b8;
            line-height: 1.6;
            max-width: 440px;
            margin-bottom: 1.75rem;
        }

        .error-detail {
            font-size: 0.78rem;
            color: #cbd5e1;
            background-color: rgba(30, 41, 59, 0.75);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 6px;
            padding: 0.55rem 0.9rem;
            margin-bottom: 1.75rem;
            max-width: 100%;
            word-break: break-word;
        }

        .actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.75rem 1.6rem;
            background-color: #dd4814;
            color: #ffffff;
            text-decoration: none;
            border-radius: 7px;
            font-size: 0.9rem;
            font-weight: 700;
            transition: background-color 0.15s ease, transform 0.12s ease;
        }

        .btn-primary:hover {
            background-color: #c23c0f;
            transform: translateY(-1px);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.75rem 1.35rem;
            background-color: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(148, 163, 184, 0.28);
            color: #e2e8f0;
            text-decoration: none;
            border-radius: 7px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.12);
        }
    </style>
</head>

<body>
    <?php
    $isLoggedIn = (bool) (function_exists('session') && session('isLoggedIn'));
    $homeUrl    = function_exists('site_url')
        ? site_url($isLoggedIn ? 'accountability' : '/')
        : '/';
    $artUrl     = function_exists('base_url')
        ? base_url('assets/images/notfound.png')
        : '/assets/images/notfound.png';
    ?>
    <main class="notfound-card">
        <div class="notfound-art-wrap">
            <img src="<?= esc($artUrl) ?>" alt="Página não encontrada">
        </div>

        <div class="error-code">404</div>
        <h1 class="error-title">Ops! Página não encontrada</h1>
        <p class="error-message">
            A página ou endereço que você tentou acessar não existe ou foi movido.
        </p>


        <div class="actions">
            <a href="<?= esc($homeUrl) ?>" class="btn-primary">
                Voltar para o Início
            </a>
        </div>
    </main>
</body>

</html>
