# OpenPush — API Contract (v1)

Single source of truth for backend, dashboard, and Android SDK. All parts build to THIS.

- Base URL (dev): `http://localhost:8000`
- API prefix: `/v1`
- Content-Type: `application/json`
- All timestamps ISO-8601 UTC. All IDs are UUID strings unless noted.
- Standard error shape:
```json
{ "error": { "code": "validation_error", "message": "Human readable", "details": { "field": ["msg"] } } }
```
- Success list shape: `{ "data": [...], "meta": { "page": 1, "per_page": 20, "total": 0 } }`
- Success single shape: `{ "data": { ... } }`

## Auth schemes
1. **SDK auth** (public, from Android devices): body/header carries `app_id` (public UUID). No secret. Header `X-OpenPush-App: <app_id>` OR `app_id` in body.
2. **REST API Key** (server-to-server send): header `Authorization: Bearer <rest_api_key>`. Key belongs to one application.
3. **Dashboard JWT** (admin users): header `Authorization: Bearer <jwt>`. Obtained from `/v1/auth/login`. Scoped to an account/tenant.

---

## A. Dashboard Auth (JWT)
- `POST /v1/auth/register` `{name,email,password}` → `{data:{token,user}}`
- `POST /v1/auth/login` `{email,password}` → `{data:{token,user}}`
- `POST /v1/auth/logout` (JWT) → `204`
- `GET  /v1/auth/me` (JWT) → `{data:{user}}`

## B. Applications (JWT)
- `GET    /v1/apps` → list applications for tenant
- `POST   /v1/apps` `{name,package_name,fcm_project_id,fcm_service_account(json)}` → `{data:app}`
- `GET    /v1/apps/{id}` → `{data:app}` (includes counts: devices, users)
- `PATCH  /v1/apps/{id}` → update
- `DELETE /v1/apps/{id}`
- app object: `{id,name,package_name,fcm_project_id,status,rate_limit,created_at, stats:{devices,users,sent_30d}}`

## C. API Keys (JWT)
- `GET    /v1/apps/{appId}/keys`
- `POST   /v1/apps/{appId}/keys` `{name}` → `{data:{id,name,key(shown once),prefix,created_at}}`
- `DELETE /v1/apps/{appId}/keys/{id}` (revoke)

## D. Devices — SDK (SDK auth) + Dashboard read (JWT)
- `POST   /v1/devices/register` `{app_id,fcm_token,external_id?,platform:"android",app_version,os_version,language,country,timezone,notification_permission}` → `{data:{device_id}}`
- `PATCH  /v1/devices/token` `{app_id,device_id,fcm_token}` → `204`
- `DELETE /v1/devices/{deviceId}` (SDK) → `204`
- `GET    /v1/apps/{appId}/devices?page&segment&search` (JWT) → paginated list
- device object: `{id,external_id,fcm_token(masked),app_version,os_version,language,country,timezone,last_active_at,notification_permission,created_at}`

## E. Users (SDK + JWT read)
- `POST /v1/users/login`  `{app_id,device_id,external_id}` → `{data:{user_id}}`
- `POST /v1/users/logout` `{app_id,device_id}` → `204`
- `GET  /v1/apps/{appId}/users?page&search` (JWT)

## F. Tags (SDK write, JWT read)
- `POST   /v1/tags`   `{app_id,device_id,tags:{key:value,...}}` → `204` (upsert)
- `DELETE /v1/tags`   `{app_id,device_id,keys:[...]}` → `204`

## G. Topics (SDK write, JWT manage)
- `POST /v1/topics/subscribe`   `{app_id,device_id,topic}` → `204`
- `POST /v1/topics/unsubscribe` `{app_id,device_id,topic}` → `204`
- `GET  /v1/apps/{appId}/topics` (JWT) → `[{name,subscribers}]`

## H. Segments (JWT)
- `GET/POST/PATCH/DELETE /v1/apps/{appId}/segments`
- segment: `{id,name,type:"dynamic|static",filters:[{field,op:"eq|neq|exists|contains",value}],count}`

## I. Notifications (REST API Key OR JWT)
- `POST /v1/notifications` body:
```json
{
  "app_id":"...",
  "title":"...","body":"...","image_url":null,"large_icon":null,"small_icon":null,
  "deep_link":null,"data":{},"ttl":2419200,"priority":"high|normal","collapse_key":null,"channel_id":null,
  "audience":{"type":"all|user_ids|device_ids|tags|segment|topic","value":...},
  "schedule":{"send_at":null,"timezone":null,"recurring":null}
}
```
  → `{data:{id,status:"queued|scheduled",estimated_recipients}}`
- `GET /v1/notifications/{id}` → `{data:{...,stats:{sent,delivered,failed,opened,ctr}}}`
- `GET /v1/apps/{appId}/notifications?page` (JWT) → list
- `POST /v1/notifications/{id}/cancel` (JWT)

## J. Analytics (JWT / REST key)
- `GET /v1/apps/{appId}/analytics?from&to` → `{data:{sent,delivered,failed,opened,ctr,timeseries:[{date,sent,delivered,opened}],by_country:[],by_os:[]}}`
- `GET /v1/apps/{appId}/analytics/overview` → dashboard KPI cards

## K. Delivery Logs (JWT)
- `GET /v1/apps/{appId}/notifications/{id}/logs?page&status` → `{data:[{device_id,status,fcm_message_id,error,retry_count,attempted_at}]}`

## L. Events / Tracking (SDK)
- `POST /v1/events` `{app_id,device_id,notification_id,type:"received|opened|clicked",occurred_at}` → `204`

---

## Notification lifecycle (backend)
1. `POST /v1/notifications` → validate → resolve audience count → create `notifications` row (status queued/scheduled) → dispatch `DispatchNotificationJob`.
2. Job fans out target devices into batches (≤500) → per-batch `SendFcmBatchJob` onto `push` queue.
3. Worker calls FCM HTTP v1 (`https://fcm.googleapis.com/v1/projects/{project}/messages:send`) using service-account OAuth token (cached in Redis). Writes `delivery_logs`.
4. Failures: exponential backoff retry (max 3). `UNREGISTERED`/`INVALID_ARGUMENT` → mark device inactive, no retry.
5. Scheduler (`schedule:run` every minute) promotes due `schedules` to dispatch.

## DB tables
applications, api_keys, accounts, admin_users, devices, users, tags, device_tags, topics, topic_subscriptions, notifications, notification_targets, delivery_logs, schedules, analytics_events, audit_logs.
