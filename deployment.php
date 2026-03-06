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
            'var/tailwind/*',
            'tests/*',
            'phpunit*',
            'phpstan*',
            '.env',
            '.env.local',
            'node_modules/*',
            'importmap.php',
        ],

        'before' => [
            'local: echo "APP_SECRET=${APP_SECRET}" > .env.local',
            'local: composer dump-env prod',
            'local: composer install --no-dev --classmap-authoritative --optimize-autoloader',
            'local: php bin/console tailwind:build --minify',
            'local: php bin/console asset-map:compile',
        ],

        'purge' => [
            'var/cache',
        ],

        'allowDelete' => true,
        'filePermissions' => '0644',
        'dirPermissions' => '0755',
    ],
];
