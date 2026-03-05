# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Personal website (spajxo.cz) built with Symfony 8.0, Tailwind CSS v4, and deployed on Upsun (Platform.sh). No database, no API — static content rendered server-side with Twig.

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

## Deployment (Upsun)

- Config: `.upsun/config.yaml` — PHP runtime, OPcache tuning, static asset caching, route cache
- Runtime env: `.environment` — sets `APP_ENV=prod`, `APP_DEBUG=0`, generates `APP_SECRET` from `PLATFORM_PROJECT_ENTROPY`
- GitHub integration: push to `main` triggers automatic deploy
- Build hook runs: `composer install --no-dev`, `tailwind:build --minify`, `asset-map:compile`
- `UXToolkitBundle` is registered for `dev`/`test` only — production builds must use `APP_ENV=prod` to avoid ClassNotFoundError

## Key Conventions

- Routes defined via PHP attributes (`#[Route]`) on controllers
- Templates follow `templates/<controller>/<action>.html.twig` with partials prefixed `_`
- Twig components live in `templates/components/` (anonymous, no backing PHP class needed)
