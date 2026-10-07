# SaaS Onboarding Platform

A sample full-stack SaaS platform that guides new users through onboarding and personalises their experience from how they use the product.

| Folder | What it is | Stack |
| --- | --- | --- |
| [`saas-onboarding-api`](saas-onboarding-api) | REST API: authentication, onboarding journey, behaviour tracking, recommendations, domain events | Laravel 13, PHP 8.3+, PostgreSQL, Redis, Kafka / Amazon MSK, Terraform for AWS |
| [`saas-onboarding-web`](saas-onboarding-web) | Web app: sign-up, guided onboarding, personalised dashboard | Next.js 16, React 19, TypeScript, Tailwind CSS |

## How it fits together

```
Browser ──▶ saas-onboarding-web (Next.js) ──HTTPS + Bearer token──▶ saas-onboarding-api (Laravel)
                                                                        ├──▶ PostgreSQL
                                                                        ├──▶ Redis
                                                                        └──▶ Domain events ──▶ log or Kafka / Amazon MSK
```

Every action a user takes (completing a step, opening a feature) is recorded by the API, which re-segments the user and updates their recommendations. The dashboard shows the change straight away.

## Run it locally

1. **API**: in `saas-onboarding-api`, run `docker compose up --build`, or with PHP installed, `composer run setup` then `php artisan serve`. The API runs at http://localhost:8000.
2. **Web app**: in `saas-onboarding-web`, run `npm install` then `npm run dev`, and open http://localhost:3000.
3. Log in with the demo account `demo@example.com` / `password`, or register a new user to start onboarding from step 1.

Each folder's README has full details.

## Continuous integration

GitHub Actions runs a separate pipeline for each project, triggered only when that folder changes:

- **API** (`.github/workflows/api.yml`): code style, tests against PostgreSQL on PHP 8.3 and 8.4, Terraform validation, Docker image build
- **Web** (`.github/workflows/web.yml`): lint, type-check, tests, production build, Docker image build

## Licence

MIT. See [LICENSE](LICENSE).
