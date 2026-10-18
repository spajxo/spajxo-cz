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
            // Kernel::getProjectDir() walks up to the first composer.json, so the root one must be on the server.
            '!/composer.json',
            'composer.lock',
            'symfony.lock',
            'var/cache/*',
            'var/log/*',
            'var/tailwind/*',
            'tests/*',
            '/phpunit*',
            '/phpstan*',
            '.env',
            '.env.local',
            'node_modules/*',
            '/importmap.php',
            'maintenance.flag',
        ],

        'before' => [
            'local: echo "APP_SECRET=${APP_SECRET}" > .env.local',
            'local: echo "APP_REVISION=${GITHUB_SHA}" >> .env.local',
            'local: echo "INSTAGRAM_APP_ID=${INSTAGRAM_APP_ID}" >> .env.local',
            'local: echo "INSTAGRAM_APP_SECRET=${INSTAGRAM_APP_SECRET}" >> .env.local',
            'local: composer dump-env prod',
            'local: composer install --no-dev --classmap-authoritative --optimize-autoloader',
            'local: php bin/console tailwind:build --minify',
            'local: php bin/console asset-map:compile',
            'local: touch maintenance.flag',
        ],

        // Requests hitting a half-renamed vendor/ can compile a broken container into var/cache,
        // so keep the site in maintenance from before the renames until after the cache purge.
        'afterUpload' => [
            'upload: maintenance.flag public/maintenance.flag',
        ],

        // upload: paths are relative to the remote dir, remote: paths to the FTP root; they match
        // only while the remote URL has no path.
        'after' => [
            'remote: unlink public/maintenance.flag',
        ],

        'purge' => [
            'var/cache',
        ],

        'allowDelete' => true,
        'filePermissions' => '0644',
        'dirPermissions' => '0755',
    ],
];
