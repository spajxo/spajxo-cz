# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Personal website (spajxo.cz) built with Symfony 8.0, Tailwind CSS v4, and deployed on shared hosting via FTPS. No database, no API — static content rendered server-side with Twig.

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

- **Symfony 8.0** with PHP 8.5, standard MVC structure
- **Tailwind CSS v4** via `symfonycasts/tailwind-bundle` (downloads binary automatically, no Node.js needed)
- **AssetMapper** for frontend assets (no Webpack/Encore) — configured in `importmap.php`
- **Twig Components** (`symfony/ux-twig-component`) for reusable UI pieces in `templates/components/`
- **UX Toolkit** (`symfony/ux-toolkit`) is dev-only — provides component scaffolding, not used in production
- **twig-tailwind-extra** provides `tw_merge()` for merging Tailwind classes in components

## Deployment

- Hosted on shared hosting (PHP 8.5), deployed via `dg/ftp-deployment` (pseudo-atomic FTPS deploy)
- Configuration: `deployment.php` in project root — PHP config with before/after hooks, ignore rules, and purge
- Workflow: `.github/workflows/deploy.yml` — installs deps, runs `php vendor/bin/deployment deployment.php`
- Push to `main` triggers deployment; `workflow_dispatch` supports `test_mode` for dry runs
- Before hooks (run by deployment tool): `composer install --no-dev`, `composer dump-env prod`, `tailwind:build --minify`, `asset-map:compile`
- Incremental sync via `.htdeployment` file (MD5 hashes), only changed files are uploaded
- Maintenance mode: `public/index.php` checks for `maintenance.flag`; enabled before deploy, disabled via HTTP call to `maintenance-off.php` after deploy
- Document root set to `public/`
- GitHub Secrets required: `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`, `APP_SECRET`
- `UXToolkitBundle` is registered for `dev`/`test` only — production builds must use `APP_ENV=prod` to avoid ClassNotFoundError

## Key Conventions

- Routes defined via PHP attributes (`#[Route]`) on controllers
- Templates follow `templates/<controller>/<action>.html.twig` with partials prefixed `_`
- Twig components live in `templates/components/` (anonymous, no backing PHP class needed)
