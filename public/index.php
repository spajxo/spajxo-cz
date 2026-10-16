<?php

use App\Kernel;

// Uploaded by deployment.php before files are swapped in, removed after var/cache is purged.
if (is_file(__DIR__.'/maintenance.flag')) {
    http_response_code(503);
    header('Retry-After: 300');
    header('Cache-Control: no-store');
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="cs"><meta charset="utf-8"><title>spajxo.cz</title><p>Probíhá aktualizace webu, zkuste to prosím za pár minut.</p></html>';

    return;
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
