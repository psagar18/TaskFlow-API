# Render Deployment — Step-by-Step

Companion to [`DEPLOYMENT_DISCUSSION.md`](./DEPLOYMENT_DISCUSSION.md) (the "why").
This file is the actual "what to click" checklist, kept up to date as we go
through the real deploy.

## Prerequisites (already done)

- [x] Render account created (no card required for free tier).
- [x] `Dockerfile.render`, `deploy/render/entrypoint.sh`, and `render.yaml`
      added to the repo (deploy-only files, separate from local dev setup).

## Steps

1. **Choose service type → Blueprint** (not "Web Service" directly). Render's
   Blueprint flow reads `render.yaml` from the repo and provisions the service
   with the correct settings automatically, instead of re-entering them by hand.
2. **Connect GitHub.** When prompted, make sure the GitHub account you connect
   is the one that owns/has access to `psagar18/TaskFlow-API` — not a company
   account, if you have more than one GitHub login. ✅ Done — connected
   successfully.
3. **Select the repo** (`TaskFlow-API`) so Render can read `render.yaml`.
4. **Review the Blueprint preview.** Render shows the service it's about to
   create (`taskflow-api`, Docker runtime, `Dockerfile.render`, free plan, and
   all env vars from `render.yaml`). Confirm it matches, then apply/create.
5. **Set the two manual secrets** (Render can't set these from a blueprint —
   marked `sync: false` in `render.yaml`):
   - `APP_KEY` — generate locally with `php artisan key:generate --show` and
     paste the output (including the `base64:` prefix) into Render's
     Environment tab for this service.
   - `APP_URL` — the `https://xxxx.onrender.com` URL Render assigns this
     service. Render shows this at the top of the service page once created.
6. **Trigger a deploy** (first deploy usually starts automatically after
   Blueprint apply; if you only just added `APP_KEY`/`APP_URL`, trigger a
   manual redeploy so the new env vars take effect).
7. **Watch the build logs** in Render's dashboard for the service. It runs:
   `composer install` → copies app → `entrypoint.sh` (`config:clear` →
   `migrate --force` → `php artisan serve`).
8. **Verify it's live**: open the assigned `https://xxxx.onrender.com` URL —
   should show the Laravel welcome page (this is also the health check path).
   Then test an API route, e.g.:
   ```
   curl -s -X POST https://xxxx.onrender.com/api/v1/auth/register \
     -H "Content-Type: application/json" \
     -d '{"name":"Test","email":"test@example.com","password":"password","password_confirmation":"password"}'
   ```

## After first successful deploy

- **Base URL updated** — `README.md` and `docs/API.md` now reference the live
  Render URL (`https://taskflow-api-648g.onrender.com`) instead of `localhost:8000`.
  `docs/INSTALLATION.md` was deleted (it only ever described local setup paths).
- **Known limitation, already accepted for this demo**: free plan has no
  persistent disk, so the SQLite DB resets to empty on every container
  restart (deploys, and the free tier's spin-down after ~15 min idle).
- **Seeding not enabled on the deployed instance** — `entrypoint.sh` only runs
  `migrate --force`, not `db:seed`, so the demo admin/manager accounts from
  `DatabaseSeeder.php` don't exist on Render yet. Registering via `/auth/register`
  works today (confirmed in Postman); ask if you want `db:seed --force` added to
  the entrypoint so the demo accounts exist after every restart too.

## Status log

- 2026-07-05 — Blueprint step 1 (choose service) completed, GitHub connected
  successfully.
- 2026-07-05 — First deploy live at `https://taskflow-api-648g.onrender.com`;
  register/login confirmed working via Postman.
- 2026-07-05 — Local Docker dev environment removed entirely (see
  `DEPLOYMENT_DISCUSSION.md`); Render is now the only way to run/test this app.
