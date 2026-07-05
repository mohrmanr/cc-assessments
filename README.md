# Assessment Portal (Laravel)

Greenfield clinical assessment portal for Connections Counseling. See [CONTEXT.md](./CONTEXT.md) for domain terminology and [docs/adr/](./docs/adr/) for architecture decisions.

## Requirements

- PHP 8.2+ with extensions: `openssl`, `pdo_mysql`, `mbstring`, `curl`, `fileinfo`
- Composer
- Node.js (for Vite assets)
- MySQL database: `connectionscouns_assess` (separate from BPTI's `connectionscouns_bpti`)

## Local setup

Local development uses **SQLite** by default (no extra DB provisioning). Production uses **MySQL** database `connectionscouns_assess` — ask hosting to create it and grant the app user access.

```powershell
cd c:\Users\Rich\BPTI\public_html\assessment-portal
copy .env.example .env
php artisan key:generate
# SQLite: ensure database/database.sqlite exists (empty file)
php artisan migrate --seed
npm install
npm run build
php artisan serve --port=8081
```

Open **http://localhost:8081**

## Stakeholder demo

Open **http://localhost:8081/demo** for the full scripted walkthrough, or follow these steps:

1. **Screen** — `/screening` with defaults (auto-eligible) or lower scores (supervisor review)
2. **Activate** — Click the activation link on the result page; set a password
3. **Assess** — Participant dashboard → PCL-5 (rate items 3–4 to hit threshold)
4. **Review** — Log in as `clinician@connectionscounseling.test` → confirm recommendation

Use a **unique email** each demo run (the screening form pre-fills one with a timestamp).


| Role | Email | Password |
|------|-------|----------|
| Admin | admin@connectionscounseling.test | password |
| Clinical Supervisor | supervisor@connectionscounseling.test | password |
| Clinician | clinician@connectionscounseling.test | password |
| Participant | participant@connectionscounseling.test | password |

Public self-registration is disabled. Participants receive email invite links after eligibility screening (flow TBD).

## What's scaffolded

- Laravel 12 + Breeze (Blade) auth
- Role-based dashboards (`participant`, `clinician`, `admin`, `clinical_supervisor`)
- MySQL schema: screening, invitations, participants, instruments, assessment results, treatment tracks/recommendations, messaging
- Seed data: launch instruments (PCL-5, DES-II, ACE, GSE-10, ECR-R), PTSD/general tracks, demo users

## Next build steps

1. Interactive eligibility screening (MI-informed questionnaire)
2. Account invitation + password setup flow
3. Assessment delivery and scoring engine
4. Clinician/supervisor queues and messaging UI
