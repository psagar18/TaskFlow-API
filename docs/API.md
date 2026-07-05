# API Reference

Base URL: `http://localhost:8000/api/v1`

All requests/responses are JSON. Authenticated routes require:

```
Authorization: Bearer <token>
Accept: application/json
```

## Response conventions

**Success (single resource)**

```json
{ "data": { "...": "..." } }
```

**Success (paginated collection)**

```json
{
  "data": [ { "...": "..." } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 }
}
```

**Error**

```json
{ "message": "The given data was invalid.", "errors": { "title": ["The title field is required."] } }
```

| Status | Meaning                                    |
|--------|---------------------------------------------|
| 200    | OK                                          |
| 201    | Created                                     |
| 204    | No Content (successful delete)              |
| 401    | Unauthenticated                             |
| 403    | Authenticated, but not authorized           |
| 404    | Resource not found                          |
| 422    | Validation failed                           |

## Authentication

### Register

`POST /auth/register` — public

```json
{ "name": "Jane Doe", "email": "jane@example.com", "password": "Password123", "password_confirmation": "Password123" }
```

→ `201` with the created user (role defaults to `member`).

### Login

`POST /auth/login` — public

```json
{ "email": "jane@example.com", "password": "Password123", "device_name": "postman" }
```

→ `200` with `{ "data": { user }, "token": "<plain-text sanctum token>" }`.
`device_name` is optional and only used to label the token.

### Current user

`GET /auth/me` — requires auth → `200` with the authenticated user.

### Logout

`POST /auth/logout` — requires auth. Revokes the token used for the request → `200`.

## Tasks

All task routes require authentication. Authorization rules (see
[ARCHITECTURE.md](ARCHITECTURE.md)):

| Role    | Create | View                              | Update                            | Delete      | Assign |
|---------|--------|------------------------------------|-------------------------------------|-------------|--------|
| admin   | ✅     | any task                           | any task                            | any task    | ✅     |
| manager | ✅     | any task                           | any task                            | own only    | ✅     |
| member  | ❌     | own (created or assigned) tasks    | own (created or assigned) tasks     | own created | ❌     |

### List tasks

`GET /tasks`

Query parameters (all optional):

| Param            | Type   | Notes                                                          |
|------------------|--------|------------------------------------------------------------------|
| `status`         | string | one of `pending`, `in_progress`, `completed`, `cancelled`        |
| `priority`       | string | one of `low`, `medium`, `high`, `urgent`                         |
| `assigned_to`    | int    | user id                                                          |
| `search`         | string | matches task title or description                               |
| `due_after`      | date   | ISO 8601 date/datetime                                           |
| `due_before`     | date   | ISO 8601 date/datetime                                           |
| `sort_by`        | string | `created_at` (default), `due_date`, `priority`, `status`, `title`|
| `sort_direction` | string | `asc` or `desc` (default)                                        |
| `per_page`       | int    | 1–100, default 15                                                |

→ `200` paginated collection of tasks.

### Create a task

`POST /tasks` — requires admin or manager role

```json
{ "title": "Ship the API", "description": "Optional", "priority": "high", "due_date": "2026-08-01", "assigned_to": 3 }
```

Only `title` is required. `priority` defaults to `medium`. `status` cannot be set on
creation (it's always `pending`).

→ `201` with the created task.

### View a task

`GET /tasks/{task}` → `200`, or `403`/`404`.

### Update a task

`PUT /tasks/{task}`

```json
{ "title": "Ship the API v2", "priority": "urgent", "status": "in_progress", "due_date": null, "assigned_to": null }
```

`title` and `priority` are required (this is a full replace, not a partial patch).
`status`, `due_date`, `assigned_to` are optional.

→ `200` with the updated task.

### Delete a task

`DELETE /tasks/{task}` — admin, or the task's creator → `204`. Soft-deleted.

### Assign a task

`PATCH /tasks/{task}/assign` — requires admin or manager role

```json
{ "assigned_to": 3 }
```

Pass `assigned_to: null` to unassign. → `200` with the updated task.

### Activity log for a task

`GET /tasks/{task}/activity-logs` — same view authorization as `GET /tasks/{task}`.

→ `200` paginated collection of activity log entries (`event`, `description`,
`properties`, `causer`, `created_at`), most recent first. Entries are created
automatically by `TaskObserver` whenever a task is created, its status changes, it's
(un)assigned, or otherwise updated/deleted.
