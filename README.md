# Learn Car Driving API (Laravel 12, PHP 8.2)

Production-grade, secure, multi-tenant-ready API implementing auth, plans, requests, offers, payments, training tracking, payouts, ratings, referrals, and rewards.

## Architecture
- Structure: Services + Modules under `app/Modules/*` with thin Controllers, FormRequests, Resources, Services, Observers, and Policies.
- Auth: Sanctum personal tokens + custom refresh tokens table.
- Database: UUID primary keys; soft deletes where relevant; optimistic locking `version` column.
- Security: Route rate-limits, input sanitization, CORS, JSON envelope, correlation IDs, request/response logging.
- Docs: Static OpenAPI spec at `public/openapi.yaml` (can be served by Swagger UI).

## Setup
1) Install deps
   - `composer install`
2) Configure environment
   - `cp .env.example .env`
   - `php artisan key:generate`
   - Defaults use SQLite; to use Docker MySQL + Redis:
     - `docker compose up -d`
     - Update `.env` to match `.env.example` DB/Redis values
3) Migrate + seed
   - `php artisan migrate --seed`
4) Run app
   - `php artisan serve`

## Key Config
- `RESERVATION_FEE_MINOR` (default 1000)
- `APP_FEE_PERCENT` (default 10)
- `APP_LOCALE` (`en`/`ar`)
- `FIREBASE_CREDENTIALS` absolute path to your Firebase service account JSON
- `FIREBASE_PROJECT` project alias from `config/firebase.php`

## Firebase Push Notifications
1) Create a Firebase project and enable Cloud Messaging.
2) Create a service account in Firebase Console -> Project settings -> Service accounts -> Generate new private key.
3) Save the JSON file on the server outside the public directory, for example `storage/app/firebase/service-account.json`.
4) Set these environment variables:
   - `FIREBASE_PROJECT=app`
   - `FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json`
   - `FIREBASE_STORAGE_DEFAULT_BUCKET=<your-project-id>.firebasestorage.app` (optional unless you use Firebase Storage)
   - `FIREBASE_DATABASE_URL=https://<your-project-id>-default-rtdb.firebaseio.com` (optional unless you use Realtime Database)
   - `FCM_TOPIC_TRAINERS=trainers`
   - `FCM_TOPIC_TRAINEES=trainees`
5) Run `php artisan migrate`.
6) From the mobile app, call `POST /api/v1/notifications/devices` after login and whenever the FCM token changes.
7) On logout, call `DELETE /api/v1/notifications/devices` to remove the device token.

The backend now stores FCM registration tokens in `user_device_tokens` and sends existing Laravel notifications to both the database and Firebase Cloud Messaging.
Admin broadcasts to grouped audiences can also send directly to Firebase topics using the configured trainer and trainee topic names.

## Example Requests (curl)

Auth
- Register:
  - `curl -X POST http://localhost/api/v1/auth/register -H 'Content-Type: application/json' -d '{"name":"John Doe","phone_with_cc":"+201111111111","password":"password123","password_confirmation":"password123","type":"user"}'`
- Login:
  - `curl -X POST http://localhost/api/v1/auth/login -H 'Content-Type: application/json' -d '{"phone_with_cc":"+201111111111","password":"password123"}'`

Catalog
- `curl http://localhost/api/v1/countries`
- `curl 'http://localhost/api/v1/plans?country_id=...'`

User Requests
- Create request:
  - `curl -X POST http://localhost/api/v1/user-requests -H 'Authorization: Bearer <ACCESS_TOKEN>' -H 'Content-Type: application/json' -d '{"plan_id":"<PLAN_ID>","start_date":"2025-10-01","has_user_car":false,"wants_trainer_car":true,"needs_pickup":false}'`
- Get my requests:
  - `curl 'http://localhost/api/v1/user-requests?mine=1' -H 'Authorization: Bearer <ACCESS_TOKEN>'`

Offers
- Trainer submits offer:
  - `curl -X POST http://localhost/api/v1/trainer/offers -H 'Authorization: Bearer <TRAINER_TOKEN>' -H 'Content-Type: application/json' -d '{"user_request_id":"<REQ_ID>","price_minor":80000,"message":"Ready to help"}'`
- Accept offer:
  - `curl -X POST http://localhost/api/v1/offers/<OFFER_ID>/accept -H 'Authorization: Bearer <ACCESS_TOKEN>'`

Payments
- Reservation fee:
  - `curl -X POST http://localhost/api/v1/payments/reservation -H 'Authorization: Bearer <ACCESS_TOKEN>' -H 'Content-Type: application/json' -d '{"user_request_id":"<REQ_ID>"}'`
- Plan full payment:
  - `curl -X POST http://localhost/api/v1/payments/plan -H 'Authorization: Bearer <ACCESS_TOKEN>' -H 'Content-Type: application/json' -d '{"user_request_id":"<REQ_ID>"}'`

Training
- Submit training day (trainer):
  - `curl -X POST http://localhost/api/v1/training-days -H 'Authorization: Bearer <TRAINER_TOKEN>' -H 'Content-Type: application/json' -d '{"user_request_id":"<REQ_ID>","date":"2025-10-02","hours_done":2,"notes":"roundabouts"}'`
- Approve day (user):
  - `curl -X POST http://localhost/api/v1/training-days/<DAY_ID>/approve -H 'Authorization: Bearer <ACCESS_TOKEN>'`

Completion & Payouts
- Complete request:
  - `curl -X POST http://localhost/api/v1/user-requests/<REQ_ID>/complete -H 'Authorization: Bearer <ACCESS_TOKEN>'`

Ratings
- `curl -X POST http://localhost/api/v1/ratings -H 'Authorization: Bearer <ACCESS_TOKEN>' -H 'Content-Type: application/json' -d '{"trainer_id":"<TRAINER_ID>","user_request_id":"<REQ_ID>","stars":5,"comment":"Great!"}'`

Rewards
- `curl http://localhost/api/v1/rewards -H 'Authorization: Bearer <ACCESS_TOKEN>'`
- `curl -X POST http://localhost/api/v1/rewards/redeem -H 'Authorization: Bearer <ACCESS_TOKEN>' -H 'Content-Type: application/json' -d '{"reward_id":"<REWARD_ID>"}'`

Admin (requires ADMIN role)
- `curl http://localhost/api/v1/admin/payouts -H 'Authorization: Bearer <ADMIN_TOKEN>'`

## Notes
- JSON envelope shape: `{ success, data, meta: { request_id }, errors }` enforced by middleware.
- Correlation ID: `X-Request-Id` in request and response.
- This repo provides a Dummy payment provider; swap with real integrations.
