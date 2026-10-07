# SaaS Onboarding & Personalisation API

A Laravel 13 REST API that runs a guided onboarding journey for new users, tracks how they use the product, and turns that behaviour into personalised recommendations. Every significant action emits a domain event that can be streamed to Apache Kafka / Amazon MSK.

It is the backend for the Next.js web app in [`../saas-onboarding-web`](../saas-onboarding-web). It was built as a sample project to demonstrate API design, testing and cloud-ready delivery practices.

## What it demonstrates

| Area | Implementation |
| --- | --- |
| API design | Consistent JSON envelope, validation with field-level errors, consistent 4xx semantics (401, 404, 409, 422) |
| Authentication | Laravel Sanctum personal access tokens (register, login, logout, current user) |
| Domain logic | Thin controllers; business rules in `OnboardingService` and `PersonalizationService` |
| Event-driven design | `EventPublisher` with pluggable drivers (`log`, `kafka`, `null`); publishing never breaks a user request |
| Data | PostgreSQL in Docker and CI, SQLite for zero-setup local runs; Redis for cache and sessions |
| Quality | 29 PHPUnit tests (feature and unit), Laravel Pint code style, CI on PHP 8.3 and 8.4 against PostgreSQL |
| Delivery | Docker image, Docker Compose stack, GitHub Actions, Terraform for AWS (VPC, Aurora PostgreSQL, ElastiCache, MSK) |

## Architecture

```
Next.js frontend ──HTTPS + Bearer token──▶ Laravel API ──▶ PostgreSQL (users, onboarding, behaviour, recommendations)
                                               │
                                               ├──▶ Redis (cache, sessions)
                                               │
                                               └──▶ EventPublisher ──▶ log  (local / CI)
                                                                   └─▶ Kafka / Amazon MSK  (topics: user-events,
                                                                        onboarding-events, behavior-events,
                                                                        personalization-events)
```

**Onboarding.** A five-step journey (welcome, profile, goal, product tour, first action). Users can complete or skip steps but cannot jump ahead. Profile answers are copied onto the user record. Restarting is idempotent: one journey per user.

**Personalisation.** Behaviour events are stored per user. A rule-based engine segments users (`new_user`, `casual_user`, `power_user`, `at_risk_user`, `standard_user`) and produces feature, content, action and timing recommendations, cached in the database for a configurable window. The rules are intentionally simple and transparent; the service boundary is where a machine-learning model would plug in.

**Events.** Each event has a UUID, type, version, timestamp, data and metadata (user ID, IP, user agent). With the Kafka driver, the user ID is the message key so a user's events stay ordered within a partition.

## Quick start

### Option A: Docker (recommended)

Requires Docker Desktop.

```bash
docker compose up --build
```

This starts the API, PostgreSQL and Redis, runs migrations and seeds a demo account.

- API health check: http://localhost:8000/api/health
- Demo login: `demo@example.com` / `password`

### Option B: Run locally with PHP

Requires PHP 8.3+ (with `pdo_sqlite`) and Composer.

```bash
composer run setup   # installs dependencies, creates .env and an SQLite database, migrates and seeds
php artisan serve    # http://localhost:8000
```

To use PostgreSQL instead of SQLite, edit the `DB_*` values in `.env` (see the comments in `.env.example`).

## API reference

All endpoints are under `/api` and return JSON. Authenticated endpoints need `Authorization: Bearer <token>`.

| Method | Endpoint | Auth | Description |
| --- | --- | --- | --- |
| GET | `/health` | – | Health check |
| POST | `/auth/register` | – | Create an account; returns a token |
| POST | `/auth/login` | – | Exchange email and password for a token |
| GET | `/auth/me` | ✓ | Current user |
| POST | `/auth/logout` | ✓ | Revoke the current token |
| POST | `/onboarding/start` | ✓ | Start or restart onboarding |
| GET | `/onboarding/progress` | ✓ | Progress, completion percentage and next step |
| GET | `/onboarding/steps/{n}` | ✓ | Definition of one step |
| POST | `/onboarding/steps/{n}/complete` | ✓ | Complete a step (`step_data` object); returns refreshed recommendations |
| POST | `/onboarding/steps/{n}/skip` | ✓ | Skip a step |
| POST | `/personalization/behavior` | ✓ | Record a behaviour event (`action`, optional `feature`, `page_url`, `context`, …) |
| GET | `/personalization/behavior-history` | ✓ | Paginated history (`limit`, `offset`) |
| GET | `/personalization/recommendations` | ✓ | Current recommendations (generated if missing or stale) |
| POST | `/personalization/recommendations/generate` | ✓ | Force regeneration |
| POST | `/personalization/recommendations/interaction` | ✓ | Record clicked / dismissed / completed |

Example:

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"demo@example.com","password":"password"}' | jq -r .data.token)

curl -s -X POST http://localhost:8000/api/onboarding/start \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' | jq
```

Login and registration are rate-limited to 10 requests per minute per IP; other endpoints to 120 per minute per user.

## Testing

```bash
php artisan test          # SQLite in memory
vendor/bin/pint --test    # code style
```

CI (`.github/workflows/api.yml` at the repository root) runs the suite against PostgreSQL 16 on PHP 8.3 and 8.4, checks code style, validates the Terraform configuration and builds the Docker image.

## Configuration

Key settings in `.env` (full list in `.env.example`):

| Variable | Default | Purpose |
| --- | --- | --- |
| `EVENT_DRIVER` | `log` | `log`, `kafka` or `null` |
| `KAFKA_BROKERS` | – | Broker list for the Kafka driver (requires the `rdkafka` PHP extension) |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:3000` | Frontend origins allowed to call the API |
| `ONBOARDING_SKIP_ALLOWED` | `true` | Whether users may skip steps |
| `RECOMMENDATION_CACHE_HOURS` | `6` | How long generated recommendations are reused |

## AWS infrastructure (Terraform)

`infrastructure/terraform` describes a VPC across three availability zones with public, private and database subnets, Aurora PostgreSQL, ElastiCache for Redis (encrypted in transit and at rest) and Amazon MSK (TLS, KMS-encrypted). Defaults target `ap-southeast-2` (Sydney).

```bash
cd infrastructure/terraform
cp terraform.tfvars.example terraform.tfvars   # set passwords and region
cp backend.hcl.example backend.hcl             # point at your own state bucket
terraform init -backend-config=backend.hcl
terraform plan
```

CI runs `terraform validate` only. Applying this creates billable AWS resources (NAT gateways, Aurora, MSK); review the plan and costs first. Application hosting (for example ECS Fargate behind an Application Load Balancer) is not included.

## Project structure

```
app/
  Exceptions/OnboardingException.php      Business-rule errors rendered as JSON
  Http/Controllers/Api/                   Auth, Onboarding, Personalization controllers
  Models/                                 User, OnboardingProgress, UserBehavior, Recommendation
  Services/                               OnboardingService, PersonalizationService, EventPublisher
config/services.php                       Event, onboarding and personalisation settings
database/migrations, factories, seeders
routes/api.php
tests/Feature, tests/Unit
docker/entrypoint.sh, Dockerfile, docker-compose.yml
infrastructure/terraform/
```

## Possible next steps

- Move step definitions into the database so they can be edited without a deployment
- Publish events asynchronously through a queue (transactional outbox) for stronger delivery guarantees
- Replace the rule-based segmentation with a trained model behind the same service interface
- OpenAPI specification generated from the routes

## Licence

MIT. See [LICENSE](../LICENSE).
