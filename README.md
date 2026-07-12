# OpenPush

Open-source, self-hostable **Android push-notification platform** (OneSignal-style, Android/FCM only). Multi-tenant SaaS: REST API, admin dashboard, and a Kotlin SDK, delivering through **Firebase Cloud Messaging HTTP v1**.

Built to the spec in [`OpenPush-Platform-PRD`](OpenPush-Platform-PRD.md) and the shared [`API_CONTRACT.md`](API_CONTRACT.md).

## Monorepo layout

| Path | What | Stack |
|------|------|-------|
| [`backend/`](backend) | REST API, auth, delivery engine, scheduler | Laravel 12 · PHP 8.2 · Sanctum · Redis/DB queue |
| [`dashboard/`](dashboard) | Admin dashboard (all screens) | Vite · React 18 · TS · Tailwind · TanStack Query |
| [`android-sdk/`](android-sdk) | Client SDK + sample app | Kotlin · FCM · OkHttp · WorkManager |
| [`infra/`](infra) | Docker, nginx, supervisor, Redis, CI/CD | docker-compose · GitHub Actions |
| [`openpush-docs/`](openpush-docs) | Documentation sections | Markdown |

## Architecture

```
Android app → Kotlin SDK → REST API (/v1)
                              │  auth: App ID (SDK) · API key (server) · Sanctum (dashboard)
                              ▼
        Application / Device / User / Tag / Topic / Segment services
                              ▼
      Notification → DispatchNotificationJob → SendPushBatchJob (push queue)
                              ▼
                 FCM HTTP v1  →  Android devices
                              ▼
         delivery_logs + analytics_events → Dashboard analytics
```

Delivery is fully queue-based: audience is resolved, devices fan out into ≤500-device batches, each batch delivered by a worker with exponential-backoff retries (3 attempts). `UNREGISTERED`/`INVALID_ARGUMENT` responses deactivate the device. A per-minute scheduler promotes due (and recurring) scheduled sends.

> **No Firebase credentials?** With `OPENPUSH_DRIVER=auto` (default) and no service account on an app, delivery is **simulated** end-to-end — so the whole platform runs and is testable without real FCM keys. Add a service account per-app to send for real.

## Quick start (local dev)

### 1. Backend (already bootstrapped)
```bash
cd backend
# .env is configured for SQLite (zero-friction dev). MySQL for prod via Docker.
php artisan migrate:fresh --seed          # demo account + 40 devices
php artisan serve --port=8000             # API at http://localhost:8000/v1
php artisan queue:work --queue=push       # delivery worker (separate shell)
php artisan schedule:work                 # scheduler (separate shell, optional)
```
Demo login: **demo@openpush.test** / **password**

### 2. Dashboard
```bash
cd dashboard
npm install
npm run dev                               # http://localhost:5173  (proxies /v1 → :8000)
```

### 3. Android SDK
```bash
cd android-sdk
gradle wrapper --gradle-version 8.7       # once, to generate the wrapper
# open in Android Studio; see android-sdk/README.md for Firebase + init
```

### 4. Full stack via Docker (prod-like)
```bash
cp .env.example .env                      # fill secrets
docker compose up --build                 # app, nginx, mysql, redis, queue, scheduler, dashboard
docker compose up --scale queue=4         # scale delivery workers
```

## API at a glance

`/v1` prefix. Envelope: `{ "data": … }` / `{ "data": [...], "meta": {...} }`; errors `{ "error": { code, message, details } }`.

- **Dashboard auth**: `POST /v1/auth/{register,login}` → bearer token
- **SDK (App ID)**: `/v1/devices/register`, `/v1/devices/token`, `/v1/users/{login,logout}`, `/v1/tags`, `/v1/topics/{subscribe,unsubscribe}`, `/v1/events`
- **Send (API key or session)**: `POST /v1/notifications`, `GET /v1/notifications/{id}`
- **Admin (session)**: `/v1/apps…`, `…/keys`, `…/devices`, `…/users`, `…/segments`, `…/topics`, `…/notifications`, `…/logs`, `…/analytics`

Full reference: [`API_CONTRACT.md`](API_CONTRACT.md).

## Tests
```bash
cd backend && php artisan test        # includes end-to-end push-pipeline feature test
cd dashboard && npm run build         # type-check + production build
```

## Security notes
FCM service accounts are **encrypted at rest** (Laravel `encrypted` cast). API keys are stored as SHA-256 hashes, shown once. HTTPS/HSTS/CSP + per-zone rate limiting at the nginx edge (see `infra/`). Every admin mutation writes an `audit_logs` entry.
