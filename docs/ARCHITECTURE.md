# Architecture Overview

TaskFlow API separates concerns into distinct layers so business logic never lives in
controllers or models. This document explains the layers, the design patterns used, and
the reasoning behind the non-obvious decisions.

## Layers

```
HTTP Request
    │
    ▼
Route (routes/api.php)
    │
    ▼
Form Request  ──► validates input + authorizes the action (policy check)
    │
    ▼
Controller    ──► thin: builds a DTO, calls a Service, wraps the result in a Resource
    │
    ▼
Service       ──► orchestrates business logic, talks only to Repository interfaces
    │
    ▼
Repository    ──► the only layer that talks to Eloquent
    │
    ▼
Model / DB
```

Each arrow is a dependency in one direction only. A Service never knows which Eloquent
methods a Repository uses internally; a Controller never builds a SQL query.

## Directory layout

```
app/
├── DTOs/                  Immutable data carriers between HTTP and the domain layer
├── Enums/                 Backed enums for status/priority/role — no magic strings
├── Exceptions/            Domain exceptions (e.g. InvalidCredentialsException)
├── Filters/Task/          One class per query filter (Strategy pattern, see below)
├── Http/
│   ├── Controllers/Api/V1 Thin controllers, versioned under /api/v1
│   ├── Requests/          Form Requests — validation AND authorization live here
│   └── Resources/         API Resources — the only place that shapes JSON output
├── Models/                Eloquent models: relationships, casts, tiny helpers only
├── Observers/             TaskObserver writes activity log entries on model events
├── Policies/              Authorization rules, one class per model
├── Providers/             RepositoryServiceProvider wires interfaces to implementations
├── Repositories/
│   ├── Contracts/         Interfaces the rest of the app depends on
│   └── Eloquent/          The only classes that import Eloquent query builders
└── Services/
    ├── Contracts/         Interfaces the controllers depend on
    └── *.php              Business logic implementations
```

## Design patterns, and why each one earns its place

**Repository pattern.** `TaskRepositoryInterface` / `UserRepositoryInterface` hide
Eloquent behind an interface. The Service layer depends on the interface, not
`Illuminate\Database\Eloquent\Model`. This is what makes `TaskServiceTest` possible
without touching a database — the repository is mocked.

**Service layer.** `TaskService` and `AuthService` hold the actual business rules
(e.g. "creating a task always attaches the authenticated user as `created_by`"). This
logic has nowhere else to live — not the controller (which would make it untestable in
isolation) and not the model (which would break single responsibility).

**DTOs (`App\DTOs`).** `TaskData`, `TaskFilterData`, `RegisterUserData`, `LoginUserData`
are `final readonly` classes with named constructors (`fromRequest`, `fromStoreRequest`,
`fromUpdateRequest`). They give Services strictly-typed input instead of passing
`array $validated` around, so a typo in a key name is a compile-time error, not a
runtime surprise.

**Dependency Inversion / DI.** Every constructor in `Services/*` and
`Http/Controllers/*` type-hints an interface. `RepositoryServiceProvider::$bindings`
is the single place that maps interfaces to concrete classes — swapping
`EloquentTaskRepository` for, say, a caching decorator means changing one line.

**Strategy pattern (`App\Filters\Task`).** Each query filter (`StatusFilter`,
`PriorityFilter`, `SearchFilter`, `AssignedToFilter`, `DueDateRangeFilter`,
`SortFilter`) is a small class implementing `TaskQueryFilter::handle(Builder, Closure)`.
`EloquentTaskRepository::paginate()` composes them through Laravel's `Pipeline`. Adding
a new filter (e.g. filter by overdue tasks) means adding one class — nothing else
changes. This was chosen over a single method with a dozen `if` statements because each
filter is independently unit-tested (see `tests/Unit/Filters`).

**Observer pattern (`App\Observers\TaskObserver`).** Activity logging is a cross-cutting
concern: every create/update/delete on a `Task` should produce an audit trail entry
regardless of which controller action triggered it. Registering `TaskObserver` on the
`Task` model (in `AppServiceProvider::boot()`) means the logging logic is never
duplicated across `store()`, `update()`, `assign()`, and `destroy()`.

**Policies.** `TaskPolicy` encodes "who can do what" (e.g. a member can only view/update
tasks assigned to or created by them; only admins and managers can create or assign
tasks). Form Requests call `$this->user()->can(...)` in `authorize()`, and controllers
call `$this->authorize(...)` for actions without a dedicated Form Request. Authorization
never leaks into a Service — a Service assumes the caller was already authorized.

## Why enums instead of string constants

`TaskStatus`, `TaskPriority`, `UserRole`, `ActivityEvent` are backed enums. They're cast
directly on the Eloquent models (`'status' => TaskStatus::class`), so `$task->status` is
always a `TaskStatus` instance in application code — never a raw string that could be
mistyped. Validation reuses `TaskStatus::values()` so the list of valid values and the
list of enum cases can never drift apart.

## Why `refresh()` after `create()` in the repositories

`EloquentTaskRepository::create()` and `EloquentUserRepository::create()` call
`->refresh()` after `Model::create()`. Several columns (`status`, `role`) have
database-level defaults that are intentionally *not* set by the DTO on creation. Without
the refresh, the in-memory model would report those attributes as `null` even though the
row in the database has the correct default — a real bug caught during manual testing of
this project (see the note in `TaskFilterData::fromRequest` about the `per_page`
type coercion for the same class of issue: values fresh from an HTTP request or a bare
`create()` call aren't automatically the type the rest of the app assumes).

## Consistent error responses

All exception-to-JSON mapping lives in `bootstrap/app.php`'s `withExceptions()`
callback: validation errors, authentication failures, authorization failures, "not
found", and the app's own `InvalidCredentialsException` all render as
`{"message": "...", ...}` with the correct HTTP status code. Controllers and Services
never construct error responses by hand.

## What was intentionally left out

- **No GraphQL / no gRPC** — the brief asked for a REST API; adding another protocol
  would be scope creep.
- **No queues/jobs** — nothing in the feature set is slow enough to need
  backgrounding. Adding a queue "for scalability" with nothing to put on it is
  overengineering.
- **No caching layer** — the dataset and access patterns described don't justify one
  yet. The Repository interfaces make adding a cache-aside decorator later a
  non-breaking change if it's ever needed.
- **Single `assigned_to` column instead of a pivot table** — the brief describes single
  assignment ("Task Assignment"), not many-to-many. A pivot table would be premature.
