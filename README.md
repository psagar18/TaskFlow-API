# TaskFlow API

An enterprise-grade REST API for task management, built with Laravel 12 and PHP 8.3 as
a portfolio demonstration of production-quality backend architecture: clean layering,
SOLID principles, full test coverage of business rules, and a real Docker-based
deployment to Render.

## Features

- **Authentication** — token-based auth via Laravel Sanctum (register, login, logout, `me`)
- **Task management** — full CRUD, assignment, status transitions, priority, due dates
- **Search, filtering, sorting, pagination** — composable query filters (Strategy pattern)
- **Role-based authorization** — admin / manager / member, enforced via Policies
- **Activity log** — automatic audit trail on every task change (Observer pattern)
- **Consistent JSON error handling** — validation, auth, and not-found errors all shaped the same way

## Live demo

Deployed on Render's free tier: `https://taskflow-api-648g.onrender.com/api/v1`
(spins down after ~15 min idle — first request after that can take 50s+ to wake up).

## Tech stack

| Layer          | Choice                                    |
|----------------|--------------------------------------------|
| Language       | PHP 8.3 (strict types throughout)          |
| Framework      | Laravel 12                                 |
| Auth           | Laravel Sanctum                            |
| Database       | SQLite                                     |
| Testing        | PHPUnit (feature + unit)                   |
| Static analysis| PHPStan / Larastan (level 6)               |
| Code style     | Laravel Pint (PSR-12 based)                |
| Deployment     | Docker on Render (see `Dockerfile.render`) |
| CI             | GitHub Actions (style, static analysis, tests) |

## Try it

There's no local dev environment for this project — it runs on Render only (see
[docs/RENDER_DEPLOYMENT_STEPS.md](docs/RENDER_DEPLOYMENT_STEPS.md) for how it's deployed).
Register an account against the live API:

```bash
curl -s -X POST https://taskflow-api-648g.onrender.com/api/v1/auth/register \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"password","password_confirmation":"password"}'
```

(First request after ~15 min of inactivity can take 50s+ — the free instance spins
down when idle.)

## Documentation

| Document                                  | Contents                                             |
|--------------------------------------------|-------------------------------------------------------|
| [docs/RENDER_DEPLOYMENT_STEPS.md](docs/RENDER_DEPLOYMENT_STEPS.md) | How this API is deployed, env vars, redeploying |
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

deploy/render/           Render entrypoint script (migrate + serve)
Dockerfile.render        Standalone image Render builds and deploys
render.yaml              Render Blueprint (service + env config)
.github/workflows/ci.yml Pint, PHPStan, and PHPUnit on every push/PR
```

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the reasoning behind this layout,
including which design patterns are used where and why.

## Quality gates

```bash
composer quality   # pint --test + phpstan + full test suite
```

Runs in CI on every push and pull request via `.github/workflows/ci.yml`.

## License

MIT.
# TaskFlow-API
