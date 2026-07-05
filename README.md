# TaskFlow API

An enterprise-grade REST API for task management, built with Laravel 12 and PHP 8.3 as
a portfolio demonstration of production-quality backend architecture: clean layering,
SOLID principles, full test coverage of business rules, and a Docker-based deployment
story.

## Features

- **Authentication** — token-based auth via Laravel Sanctum (register, login, logout, `me`)
- **Task management** — full CRUD, assignment, status transitions, priority, due dates
- **Search, filtering, sorting, pagination** — composable query filters (Strategy pattern)
- **Role-based authorization** — admin / manager / member, enforced via Policies
- **Activity log** — automatic audit trail on every task change (Observer pattern)
- **Consistent JSON error handling** — validation, auth, and not-found errors all shaped the same way

## Tech stack

| Layer          | Choice                                    |
|----------------|--------------------------------------------|
| Language       | PHP 8.3 (strict types throughout)          |
| Framework      | Laravel 12                                 |
| Auth           | Laravel Sanctum                            |
| Database       | MySQL 8                                    |
| Testing        | PHPUnit (feature + unit)                   |
| Static analysis| PHPStan / Larastan (level 6)               |
| Code style     | Laravel Pint (PSR-12 based)                |
| Containers     | Docker + Docker Compose (app, nginx, mysql)|
| CI             | GitHub Actions (style, static analysis, tests) |

## Quick start

```bash
git clone <repository-url> taskflow-api && cd taskflow-api
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

API is live at `http://localhost:8000/api/v1`. Full setup details, troubleshooting, and
a non-Docker path: [docs/INSTALLATION.md](docs/INSTALLATION.md).

Try it:

```bash
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"admin@taskflow.test","password":"password"}'
```

## Documentation

| Document                                  | Contents                                             |
|--------------------------------------------|-------------------------------------------------------|
| [docs/INSTALLATION.md](docs/INSTALLATION.md) | Full setup (Docker & local), env vars, deployment    |
| [docs/API.md](docs/API.md)                 | Every endpoint, request/response shapes, auth rules  |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Layering, design patterns, and why each was chosen  |

## Project structure

```
app/
├── DTOs/               Immutable request→domain data carriers
├── Enums/              TaskStatus, TaskPriority, UserRole, ActivityEvent
├── Exceptions/         Domain exceptions
├── Filters/Task/       Composable query filters (Strategy pattern)
├── Http/
│   ├── Controllers/Api/V1
│   ├── Requests/       Validation + authorization
│   └── Resources/      JSON shaping
├── Models/              Task, User, ActivityLog
├── Observers/           TaskObserver → activity log
├── Policies/             TaskPolicy
├── Providers/            RepositoryServiceProvider (DI bindings)
├── Repositories/         Contracts + Eloquent implementations
└── Services/             Business logic (TaskService, AuthService, ActivityLogService)

database/
├── factories/          Model factories used by tests and seeders
├── migrations/          Normalized schema, FKs, indexes, soft deletes
└── seeders/             Demo admin/manager/member users + sample tasks

tests/
├── Feature/             HTTP-level tests: auth, CRUD, authorization, validation, filters
└── Unit/                Service and filter unit tests with mocked dependencies

docker/                  Nginx config
Dockerfile               Multi-stage: lean production image + dev target
docker-compose.yml        app + nginx + mysql, local dev orchestration
.github/workflows/ci.yml Pint, PHPStan, and PHPUnit on every push/PR
```

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the reasoning behind this layout,
including which design patterns are used where and why.

## Quality gates

```bash
docker compose exec app composer quality   # pint --test + phpstan + full test suite
```

Runs in CI on every push and pull request via `.github/workflows/ci.yml`.

## License

MIT.
# TaskFlow-API
