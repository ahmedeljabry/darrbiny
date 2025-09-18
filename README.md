# Learn Car Driving API (Laravel 12, PHP 8.2)

Production-grade, secure, multi-tenant-ready API implementing OTP auth, plans, requests, offers, payments, training tracking, payouts, ratings, referrals, and rewards.

## Architecture
- Structure: Services + Modules under `app/Modules/*` with thin Controllers, FormRequests, Resources, Services, Observers, and Policies.
- Auth: Sanctum personal tokens + custom refresh tokens table. OTP via pluggable WhatsApp driver.
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

## Example Requests (curl)

Auth
- Request OTP:
  - `curl -X POST http://localhost/api/v1/auth/request-otp -H 'Content-Type: application/json' -d '{"phone_with_cc":"+201111111111"}'`
- Verify OTP (replace 000000 with real code):
  - `curl -X POST http://localhost/api/v1/auth/verify-otp -H 'Content-Type: application/json' -d '{"phone_with_cc":"+201111111111","otp":"000000"}'`

Catalog
- `curl http://localhost/api/v1/countries`
- `curl 'http://localhost/api/v1/plans?country_id=...&city_id=...'`

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
- This repo provides a Dummy payment provider and stub WhatsApp driver; swap with real integrations.

