# SaaS Onboarding & Personalisation — Frontend

A Next.js 16 (React 19, TypeScript, Tailwind CSS) web app for the Laravel API in [`../saas-onboarding-api`](../saas-onboarding-api). Users sign up, work through a guided onboarding journey, and land on a dashboard whose recommendations change as they use the product.

It was built as a sample project to demonstrate front-end architecture, API integration and testing.

## Features

- **Authentication**: register, log in and log out against Laravel Sanctum; the token is persisted in the browser and attached to every API call. Protected pages redirect to the login page.
- **Guided onboarding**: five steps (welcome, profile form, goal selection, product tour, first action) with progress tracking, validation, skipping and restart.
- **Personalised dashboard**: feature, content and action recommendations, the user's segment and the best time to reach them.
- **Behaviour tracking loop**: opening a feature sends a behaviour event to the API and the recommendations refresh straight away, so the personalisation is visible.
- **Clear error handling**: Laravel validation errors are shown inline, and a specific message appears if the API cannot be reached.

## Tech stack

| Concern | Choice |
| --- | --- |
| Framework | Next.js 16 (App Router), React 19, TypeScript (strict) |
| Styling | Tailwind CSS 3 with small, local UI components |
| State | Zustand (auth session, persisted to localStorage) |
| HTTP | Axios with request and response interceptors |
| Testing | Jest 30, React Testing Library |
| Quality | ESLint (Next.js core-web-vitals and TypeScript rules), `tsc --noEmit` |
| Delivery | Multi-stage Docker image (Next.js standalone output), GitHub Actions CI |

## Getting started

Requires Node.js 20.9 or later, and the backend running at `http://localhost:8000` (see the [API README](../saas-onboarding-api/README.md); `docker compose up` there is the quickest route).

```bash
npm install
cp .env.example .env.local   # optional: only needed if the API is not at http://localhost:8000/api
npm run dev
```

Open http://localhost:3000 and log in with the demo account `demo@example.com` / `password`, or create a new account to see onboarding from the start.

## Scripts

| Command | Purpose |
| --- | --- |
| `npm run dev` | Development server |
| `npm run build` / `npm start` | Production build and server |
| `npm run lint` | ESLint |
| `npm run type-check` | TypeScript checks |
| `npm test` | Unit and component tests |

CI (`.github/workflows/web.yml` at the repository root) runs lint, type-check, tests and a production build on every push and pull request, then builds the Docker image.

## Docker

```bash
docker build --build-arg NEXT_PUBLIC_API_URL=http://localhost:8000/api -t saas-onboarding-web .
docker run -p 3000:3000 saas-onboarding-web
```

`NEXT_PUBLIC_API_URL` is compiled into the browser bundle, so it is passed at build time.

## Project structure

```
app/                    Routes: / (landing), /login, /register, /onboarding, /dashboard
components/
  auth/AuthForm.tsx     Shared login and registration form
  onboarding/           OnboardingFlow and per-step rendering
  dashboard/            Recommendations dashboard and feature explorer
  layout/SiteHeader.tsx
  ui/                   Button, Card, Input, Progress
lib/
  api/                  Typed API clients (auth, onboarding, personalisation) and error helper
  hooks/                useOnboarding, usePersonalization, useRequireAuth
  store/authStore.ts    Persisted auth session
__tests__/              Jest and React Testing Library tests
```

## Design notes

- **Token in localStorage.** This keeps the sample simple and works across origins. A production build would more likely use Sanctum's cookie-based SPA authentication (HttpOnly cookies, CSRF protection) with the frontend and API on the same parent domain.
- **Client-side route protection.** Protected pages check for a session in the browser and the API enforces authorisation on every request. Next.js middleware could add a server-side redirect once cookie auth is in place.
- **Tracking never blocks the user.** Behaviour and interaction calls swallow their own errors and only log a warning.

## Licence

MIT. See [LICENSE](../LICENSE).
