<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Nova Página') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }
        .app-layout { display: flex; min-height: 100vh; width: 100%; }
        .main-wrapper { flex: 1; display: flex; flex-direction: column; min-width: 0; width: 100%; }
        .container { width: 100%; max-width: 100%; padding: 1.75rem 2rem; }
        .blank-card {
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 3.5rem 2rem;
            text-align: center;
            color: #64748b;
        }
        .blank-card h1 {
            font-size: 1.35rem;
            color: #0f172a;
            margin-bottom: 0.45rem;
        }
        .blank-card p {
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <?= view('partials.sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials.navbar', [
                'pageTitle' => $pageTitle ?? 'Nova Página',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <div class="blank-card">
                    <h1><?= esc($pageTitle ?? 'Nova Página') ?></h1>
                    <p>Página reservada — aguardando definição de dados e conteúdo.</p>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
