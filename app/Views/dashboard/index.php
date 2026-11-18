<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Sistema de Prestação</title>
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

        .alert-success {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.25rem;
        }

        .stat-card span {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
        }

        .stat-card strong {
            display: block;
            margin-top: 0.35rem;
            font-size: 1.5rem;
            color: #0f172a;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            width: 100%;
        }

        .card h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #0f172a;
        }

        .card p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .user-details {
            background: #f1f5f9;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            font-size: 0.9rem;
            display: grid;
            gap: 0.45rem;
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
    <?php $isAdmin = (($userRole ?? session()->get('user_role') ?? 'user') === 'admin'); ?>

    <div class="app-layout">
        <?= view('partials/sidebar_admin', [
            'userName'  => $userName ?? null,
            'userEmail' => $userEmail ?? null,
            'userRole'  => $userRole ?? null,
        ]) ?>

        <div class="main-wrapper">
            <?= view('partials/navbar', [
                'pageTitle' => $isAdmin ? 'Painel Administrativo' : 'Prestação App',
                'userName'  => $userName ?? null,
                'userEmail' => $userEmail ?? null,
                'userRole'  => $userRole ?? null,
            ]) ?>

            <main class="container">
                <?php if ($isAdmin): ?>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span>Prestações de Contas</span>
                            <strong>Ativas</strong>
                        </div>
                        <div class="stat-card">
                            <span>Histórico de Auditoria</span>
                            <strong>Monitorado</strong>
                        </div>
                        <div class="stat-card">
                            <span>Nível de Acesso</span>
                            <strong>Administrador</strong>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>

</html>