<?php

$env = @include dirname(__DIR__) . '/.env.local.php';

if (!$env || ($_GET['token'] ?? '') !== ($env['APP_SECRET'] ?? '')) {
    http_response_code(403);
    exit('Forbidden');
}

@unlink(__DIR__ . '/maintenance.flag');
echo 'OK';
