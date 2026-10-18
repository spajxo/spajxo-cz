<?php

use App\Kernel;

// Uploaded by deployment.php before files are swapped in, removed after var/cache is purged.
// Ignored after 30 minutes so a deploy that dies midway does not keep the site down.
$maintenanceFlag = __DIR__.'/maintenance.flag';
if (is_file($maintenanceFlag) && filemtime($maintenanceFlag) > time() - 1800) {
    http_response_code(503);
    header('Retry-After: 300');
    header('Cache-Control: no-store');
    header('Content-Type: text/html; charset=UTF-8');
    // Standalone copy of the 503 error page: vendor/ and compiled assets may be half-updated here.
    echo <<<'HTML'
        <!doctype html>
        <html lang="cs">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex">
            <meta http-equiv="refresh" content="30">
            <title>503 Probíhá údržba | spajxo.cz</title>
            <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>S</text></svg>">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@700&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
            <style>
                * { margin: 0; box-sizing: border-box; }
                body {
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0 1.5rem;
                    font-family: "Outfit", ui-sans-serif, system-ui, sans-serif;
                    background-color: oklch(0.13 0.01 240);
                    color: oklch(0.97 0.005 240);
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                    text-align: center;
                }
                main > * + * { margin-top: 1.5rem; }
                .code {
                    font-family: "JetBrains Mono", ui-monospace, monospace;
                    font-size: 4.5rem;
                    line-height: 1;
                    font-weight: 700;
                    letter-spacing: -0.025em;
                    color: inherit;
                }
                .code span { margin-left: -0.15em; color: oklch(0.72 0.14 220); animation: pulse 2s ease-in-out infinite; }
                h1 { font-size: 1.5rem; line-height: 2rem; font-weight: 700; letter-spacing: -0.025em; }
                p { color: oklch(0.60 0.02 240); }
                h1 + p { margin-top: 0.5rem; }
                .hint { font-size: 0.875rem; }
                @keyframes pulse { 50% { opacity: 0.3; } }
                @media (min-width: 40rem) { .code { font-size: 8rem; } }
                @media (prefers-reduced-motion: reduce) { .code span { animation: none; } }
            </style>
        </head>
        <body>
            <main>
                <p class="code">503<span>.</span></p>
                <div>
                    <h1>Probíhá údržba</h1>
                    <p>Web se právě aktualizuje. Zkuste to prosím za pár minut.</p>
                </div>
                <p class="hint">Stránka se sama obnoví.</p>
            </main>
        </body>
        </html>
        HTML;

    return;
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
