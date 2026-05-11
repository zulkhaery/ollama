<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Pluto') ?></title>
    <style>
        :root {
            --bg-color: #0f172a;
            --text-color: #e2e8f0;
            --accent-color: #38bdf8;
            --card-bg: #1e293b;
            --border-color: #334155;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            width: 90%;
            text-align: center;
        }

        .logo {
            font-size: 4rem;
            margin-bottom: 0.1rem;
             background: linear-gradient(135deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        h1 {
            font-size: 2.5rem;
            margin-top:0;
            margin-bottom: 0.5rem;
            color: var(--accent-color);
        }

        .subtitle {
            font-size: 1.2rem;
            color: #94a3b8;
            margin-bottom: 2rem;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .info-item h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-item p {
            margin: 0;
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--accent-color);
        }

        .footer {
            margin-top: 3rem;
            font-size: 0.9rem;
            color: #64748b;
        }

        .footer a {
            color: var(--accent-color);
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 600px) {
            h1 { font-size: 2rem; }
            .card { padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="logo ">✦</div>
        <h1><?= htmlspecialchars($title) ?></h1>
        <p class="subtitle">Lightweight PHP Boilerplate</p>

        <div class="card">
            <p style="margin-top: 0;">
                This is your starting point for building fast, clean, and bloat-free PHP applications.<br />
                Ready to build? Start from <code>app/routes.php</code>
            </p>

            <div class="info-grid">
                <div class="info-item">
                    <h3>PHP Version</h3>
                    <p><?= htmlspecialchars($phpVersion) ?></p>
                </div>
                <div class="info-item">
                    <h3>Server Time</h3>
                    <p><?= htmlspecialchars($serverTime) ?></p>
                </div>
                <div class="info-item">
                    <h3>Environment</h3>
                    <p><?= htmlspecialchars(env('APP_ENV', 'local')) ?></p>
                </div>
                <div class="info-item">
                    <h3>Debug Mode</h3>
                    <p><?= env('APP_DEBUG', false) ? '✅ On' : ' Off' ?></p>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; <?= date('Y') ?> Zulkhaery. Built with hope ✦ and Pluto.</p>
        </div>
    </div>

</body>
</html>