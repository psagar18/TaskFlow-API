# Installation Guide

## Requirements

- Docker & Docker Compose (recommended — no local PHP/MySQL needed)
- **or**, for a local (non-Docker) setup: PHP 8.3+, Composer, MySQL 8

## Option A — Docker (recommended)

```bash
git clone <repository-url> taskflow-api
cd taskflow-api
cp .env.example .env
```

Build and start the stack (app, nginx, MySQL):

```bash
docker compose up -d --build
```

Install dependencies, generate the app key, run migrations and seed demo data:

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

The API is now available at **http://localhost:8000/api/v1**.

Seeded accounts (password for all: `password`):

| Email                  | Role    |
|-------------------------|---------|
| admin@taskflow.test     | admin   |
| manager@taskflow.test   | manager |

> If port `8000` or the internal MySQL port is already in use on your machine, override
> `APP_PORT` in `.env` before starting the stack. MySQL itself isn't published to the
> host by default — see the comment in `docker-compose.yml` if you need direct access.

### Running tests / linting / static analysis inside Docker

```bash
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint --test      # check code style
docker compose exec app vendor/bin/pint             # auto-fix code style
docker compose exec app vendor/bin/phpstan analyse   # static analysis
```

Tests run against an in-memory SQLite database (configured in `phpunit.xml`) so they
never touch your MySQL data.

### Common issues

- **"Permission denied" writing to `storage/` or `bootstrap/cache`** — the container's
  `www-data` user needs write access to these host-mounted directories:
  `chmod -R ug+rwx storage bootstrap/cache` (or `777` if your host user's group doesn't
  match the container's).
- **Port already in use** — another process (often a system MySQL/MariaDB install) may
  already be bound to a port you tried to map. Change the relevant `*_PORT` variable in
  `.env`.

## Option B — Local PHP (no Docker)

```bash
git clone <repository-url> taskflow-api
cd taskflow-api
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` and point `DB_HOST`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` at a MySQL
instance you control, then:

```bash
php artisan migrate --seed
php artisan serve
```

The API is now available at **http://localhost:8000/api/v1**.

## Environment variables

See `.env.example` for the full list. The ones you're most likely to change:

| Variable          | Purpose                                             |
|-------------------|------------------------------------------------------|
| `APP_PORT`        | Host port nginx binds to (Docker only)               |
| `DB_DATABASE`     | Database name                                        |
| `DB_USERNAME`     | Database user                                        |
| `DB_PASSWORD`     | Database password                                    |
| `DB_ROOT_PASSWORD`| MySQL root password (Docker only)                    |

## Production deployment

The `Dockerfile`'s default (`base`) build target produces a lean, production-ready
image: dependencies installed with `--no-dev`, config cached, running as `www-data`
behind PHP-FPM. Point any container platform (ECS, Cloud Run, a VPS with
docker-compose, etc.) at that target — do not use the `development` target (used by
`docker-compose.yml`) in production, since it keeps dev dependencies and skips config
caching.

At minimum, override these in your production environment rather than relying on any
committed `.env` file:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate with `php artisan key:generate --show`>
DB_* (pointed at your managed database)
```
