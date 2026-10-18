# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Personal website (spajxo.cz) built with Symfony 8.1, Tailwind CSS v4, and deployed on shared hosting via FTPS. No database, no API — static content rendered server-side with Twig.

## Commands

```bash
# Local development
symfony serve                              # Start local web server (http://127.0.0.1:8000)
composer install                           # Install dependencies

# Tailwind CSS
php bin/console tailwind:build --watch     # Watch mode for development
php bin/console tailwind:build --minify    # Production build

# Assets
php bin/console asset-map:compile          # Compile assets for production
php bin/console debug:asset-map            # List all mapped assets

# Cache
php bin/console cache:clear                # Clear Symfony cache

# Routing
php bin/console debug:router               # List all registered routes
```

## Architecture

- **Symfony 8.1** with PHP 8.5, standard MVC structure
- **Tailwind CSS v4** via `symfonycasts/tailwind-bundle` (downloads binary automatically, no Node.js needed)
- **AssetMapper** for frontend assets (no Webpack/Encore) — configured in `importmap.php`
- **Twig Components** (`symfony/ux-twig-component`) for reusable UI pieces in `templates/components/`
- **UX Toolkit** (`symfony/ux-toolkit`) is dev-only — provides component scaffolding, not used in production
- **twig-tailwind-extra** provides `tw_merge()` for merging Tailwind classes in components

## Deployment

- Hosted on shared hosting (PHP 8.5), deployed via `dg/ftp-deployment` (pseudo-atomic FTPS deploy)
- Configuration: `deployment.php` in project root — PHP config with before/afterUpload/after jobs, ignore rules, and purge
- Workflow: `.github/workflows/deploy.yml` — installs deps, runs `php vendor/bin/deployment deployment.php`
- Push to `main` triggers deployment; `workflow_dispatch` supports `test_mode` for dry runs
- Deploys are queued (`concurrency: deploy`), never run in parallel; pushes touching only `README.md`, `CLAUDE.md`, `.claude/` or `ci.yml` don't deploy
- `deployment.php` writes `APP_REVISION=$GITHUB_SHA` into `.env.local`; `base.html.twig` renders it as `<meta name="revision">` and the workflow's last step fails unless https://spajxo.cz/ serves the pushed commit
- Before hooks (run by deployment tool): write `APP_SECRET` to `.env.local`, `composer dump-env prod`, `composer install --no-dev`, `tailwind:build --minify`, `asset-map:compile`
- Incremental sync via `.htdeployment` file (MD5 hashes), only changed files are uploaded
- Maintenance mode: an `afterUpload` job uploads `public/maintenance.flag` before the uploaded files are renamed into place, and an `after` job deletes it once `var/cache` is purged; `public/index.php` answers 503 while the flag exists. Without it, a request during the ~10-minute rename/purge window can hit a half-updated `vendor/` (seen on the Symfony 8.0 → 8.1 deploy). If a deploy fails midway, the flag stays and the site keeps returning 503 until the next successful deploy
- No tests; `.github/workflows/ci.yml` runs on pull requests: composer audit, Symfony linters, the production build from `deployment.php` and an HTTP smoke test of `/`, `/enyaq`, `/garden` and a 404
- Document root set to `public/`
- GitHub Secrets required: `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`, `APP_SECRET`
- `UXToolkitBundle` is registered for `dev`/`test` only — production builds must use `APP_ENV=prod` to avoid ClassNotFoundError

## Key Conventions

- Routes defined via PHP attributes (`#[Route]`) on controllers
- `/enyaq` and `/garden` are private utility pages, not linked from the homepage; `/garden` sends `noindex` (header + meta), fetches Open-Meteo in the browser and keeps its coordinates rounded to 0.1°
- Templates follow `templates/<controller>/<action>.html.twig` with partials prefixed `_`
- Twig components live in `templates/components/` (anonymous, no backing PHP class needed)
- Error pages (prod only): one template, `templates/bundles/TwigBundle/Exception/error.html.twig`, with a message per status code; preview in dev at `/_error/{code}`
