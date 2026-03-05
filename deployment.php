<?php

return [
    'spajxo.cz' => [
        'remote' => sprintf(
            'ftps://%s:%s@%s/',
            getenv('FTP_USER'),
            getenv('FTP_PASSWORD'),
            getenv('FTP_HOST'),
        ),
        'local' => '.',
        'passiveMode' => true,

        'ignore' => [
            '.git*',
            '.github/*',
            '.claude/*',
            '.editorconfig',
            'CLAUDE.md',
            'README.md',
            'deployment.php',
            'deployment.log',
            'composer.json',
            'composer.lock',
            'symfony.lock',
            'var/cache/*',
            'var/log/*',
            'tests/*',
            'phpunit*',
            'phpstan*',
            '.env',
            'node_modules/*',
            'maintenance.html',
            'importmap.php',
        ],

        'before' => [
            'local: cp maintenance.html public/maintenance.flag',
            'local: composer install --no-dev --classmap-authoritative --optimize-autoloader',
            'local: composer dump-env prod',
            'local: php bin/console tailwind:build --minify',
            'local: php bin/console asset-map:compile',
        ],

        'after' => [
            sprintf('http://spajxo.cz/maintenance-off.php?token=%s', getenv('APP_SECRET')),
            'local: rm -f public/maintenance.flag',
        ],

        'purge' => [
            'var/cache',
        ],

        'allowDelete' => true,
        'filePermissions' => '0644',
        'dirPermissions' => '0755',
    ],
];
