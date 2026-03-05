# spajxo.cz

Personal website built with Symfony 8.0, Tailwind CSS v4, and deployed on [Upsun](https://upsun.com).

## Requirements

- PHP 8.4+
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download)

## Setup

```bash
composer install
symfony serve
```

Site runs at http://127.0.0.1:8000.

## Development

Tailwind CSS watches for changes automatically in dev mode. For manual rebuild:

```bash
php bin/console tailwind:build --watch
```

## Stack

- **Symfony 8.0** — PHP framework
- **Tailwind CSS v4** — utility-first CSS (via [TailwindBundle](https://github.com/symfonycasts/tailwind-bundle), no Node.js needed)
- **AssetMapper** — native Symfony asset management (no Webpack)
- **Twig Components** — reusable UI components
- **Upsun** — hosting & deployment (auto-deploy on push to `main`)
