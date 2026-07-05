# Deployment Discussion (Draft — Not Implemented)

This document is a discussion of options only. Nothing here has been set up or
implemented. Any action item below requires explicit go-ahead before we touch
CI/CD, infra, or hosting.

## Current State

- **API hosting:** none. The GitHub Actions workflow (`.github/workflows/ci.yml`)
  only runs Pint, PHPStan, and PHPUnit on push/PR to `main`. There is no deploy
  job, no image registry push, no server target.
- **Local dev:** `docker-compose.yml` spins up `nginx` + `app` (PHP-FPM) + `mysql`
  on your machine only (default port 8000). This is dev-only, not production infra.
- **UI/website:** does not exist yet.

## Deploying the API (when ready)

Options, roughly ordered by effort/cost:

1. **PaaS (Laravel-friendly, least ops work)**
   - Laravel Forge + a VPS (DigitalOcean/Linode/Hetzner) — classic Laravel path,
     handles deploy scripts, queues, SSL, zero-downtime deploys.
   - Laravel Cloud / Vapor (serverless on AWS) — scales to zero, no server mgmt,
     but pricier and adds vendor lock-in.
   - Render / Railway — simplest to wire up from a Dockerfile, good free/low tiers
     for a project this size.

2. **Container-based (reuses the existing Dockerfile)**
   - Since a production-ready multi-stage `Dockerfile` already exists, the same
     image could be pushed to a registry (GHCR/Docker Hub/ECR) and run on:
     - A single VPS via `docker compose` (closest to current local setup).
     - A managed container service (AWS ECS/Fargate, Google Cloud Run, Fly.io).
   - Fly.io / Cloud Run are attractive here — cheap, low-ops, and Cloud Run scales
     to zero like Vapor without the Laravel-specific lock-in.

3. **Traditional VPS, manual/scripted**
   - Full control, most ops burden (patching, SSL renewal, process manager,
     zero-downtime deploy scripting).

### What CI/CD would need to add
A `deploy` job (only on `main`, likely gated on the existing test/style/analysis
jobs passing) that either:
- builds & pushes the Docker image to a registry, then triggers the host to pull it, or
- SSHes in and runs a deploy script (`git pull && composer install && migrate`).

This needs secrets (SSH key or registry token) added to the GitHub repo — a
step we'd do together when you're ready.

## Deploying a future UI/website

Depends on what the UI turns out to be:

- **SPA (React/Vue/etc.) consuming the API:** static hosting — Vercel, Netlify,
  Cloudflare Pages, or S3+CloudFront. Deploy is just "push to main → auto build →
  CDN," essentially zero server ops. CORS on the API would need the site's domain
  allow-listed.
- **Server-rendered (Next.js/Nuxt) or a separate Laravel/Blade frontend:** needs
  an actual runtime, so it follows the same hosting options as the API above
  (PaaS/container/VPS), just as a second service.
- **Same repo vs. separate repo:** if the UI lives in its own repo, it gets its
  own CI/CD pipeline entirely; if it's added to this repo (e.g. `resources/js`
  build via Vite, already partially scaffolded), it could piggyback on the
  existing pipeline with an added build/deploy job.

## Decisions (2026-07-05)

1. **Target environment:** Render.com (PaaS, free web service tier). Chosen for
   first-time deploy — no server/SSL/process-manager management, free tier
   needs no card, deploys straight from GitHub on push to `main`.
   - Trade-off: free tier spins down after ~15 min idle, ~30-60s cold start on
     next request. Acceptable for a demo project.
   - Alternative considered: Railway.app (no cold starts, but free tier is a
     one-time trial credit, then requires a card).
2. **UI location:** inside this repo (Vite build, same Laravel app serves both
   API and frontend). No separate SPA repo.
3. **Cloud account:** none yet. No payment required for Render's free web
   service tier — signup needs no card.
4. **Environment:** demo only, no staging — deploy straight to a single
   "production" instance.

### Simplification to avoid a paid DB add-on
For this demo deploy, use **SQLite** instead of MySQL (already proven to work —
CI runs the test suite on SQLite). This means Render only needs one free web
service, no separate database service to pay for or expire. MySQL stays as-is
for local dev via `docker-compose.yml`; this is a deploy-time-only choice.

### Signup steps (user does this, not Claude)
1. Go to render.com, sign up (GitHub sign-in or email+password) — no card needed.
2. Verify email if not using GitHub sign-in.

### Deploy steps (once approved, not yet done)
1. Render dashboard → "New Web Service" → connect this GitHub repo (user
   authorizes Render's GitHub App directly — not done by Claude).
2. Render builds from the existing `Dockerfile`.
3. Set env vars in Render dashboard: `APP_KEY`, `APP_ENV=production`,
   `DB_CONNECTION=sqlite`, etc.
4. Build step runs `npm install && npm run build` so compiled Vite assets are
   included; Laravel serves both UI and API from one service.
5. Push to `main` → auto-build & deploy.

## Implemented (2026-07-05)

The following files were added to the working tree only — nothing was committed
or pushed (no `git` commands were run):

- `Dockerfile.render` — standalone image for Render, separate from the local
  dev `Dockerfile`/`docker-compose.yml`. Serves via `php artisan serve` on
  `$PORT` (no nginx), uses SQLite (no separate DB service needed).
- `deploy/render/entrypoint.sh` — clears the build-time config cache (so env
  vars set in Render's dashboard actually take effect), runs migrations, then
  starts the server.
- `render.yaml` — Render Blueprint defining the web service, free plan, health
  check on `/`, and the env vars from the "Simplification" section above.
  `APP_KEY` and `APP_URL` are marked `sync: false` — those two must be entered
  by hand in Render's dashboard after the service is first created (Render
  blueprints can't set secret values).

**Known limitation:** the free plan has no persistent disk, so the SQLite file
resets to freshly-migrated-empty on every container restart (including the
free tier's spin-down after ~15 min idle). Fine for a demo; flagging so it's
not a surprise.

### Still needs you, not Claude
1. Create the Render account (see signup steps above).
2. In Render: "New Blueprint" → connect this GitHub repo → Render reads
   `render.yaml` and provisions the service. This step requires *you* to
   authorize Render's GitHub App — not done by Claude.
3. After first deploy, set `APP_KEY` (from `php artisan key:generate --show`)
   and `APP_URL` in the Render dashboard, then trigger a manual redeploy.

No GitHub access, no CI changes, no account setup has been performed by Claude.

## Local dev environment removed (2026-07-05)

Once the Render deployment was confirmed working, the local Docker dev setup was
removed entirely — Render is now the only way to run/test this app (no more local
server for day-to-day coding):

- Deleted: `docker-compose.yml`, `Dockerfile` (local multi-stage), `docker/` (nginx
  config), `dev.sh`.
- `.env.example` switched to `DB_CONNECTION=sqlite` (MySQL-specific vars removed) to
  match what Render actually runs.
- `docs/INSTALLATION.md` (Docker & local-PHP setup guide) deleted — no longer applicable.
- `README.md` and `docs/API.md` now only reference the deployed URL, not `localhost`.

`Dockerfile.render`, `deploy/render/entrypoint.sh`, and `render.yaml` are unaffected —
those are Render-specific and were never part of the local setup.
