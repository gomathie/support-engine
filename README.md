# PILOT Support Training Academy

An interactive onboarding platform for new support employees. Trainees work through structured curriculum tracks — 1st-line support (2 weeks), admin panel (3 days), and support skills modules — **ticking checkboxes** as they complete each lesson to show their progress in real time.

## What it does

| Feature | Description |
|---|---|
| **Training Tracker** | 4 curriculum tracks, 24 modules, 96 lessons with interactive checkbox completion |
| **Progress Dashboard** | SVG circular gauge, per-section progress bars, and completion counters — all driven by checked-off lessons |
| **Support Panel** | Diagnostic decision trees, priority matrix, and case-note generator for hands-on practice |
| **Skills Module** | Long-form reference documentation with sticky TOC, scroll-spy, and annotated "standard default" markers |
| **Quiz Engine** | Timed assessments with scoring, pass marks, and attempt history |
| **Certificates** | Auto-generated PDF certificates upon course completion |
| **Admin Portal** | Full Filament admin for managing courses, employees, departments, enrollments, and reporting |

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 (PHP 8.3) |
| Admin | Filament 5 |
| Frontend | Vue 3 + Inertia 3 |
| Styling | Tailwind CSS 4 (Vibrant Indigo + Deep Dark Mode & Glassmorphism) |
| Database | PostgreSQL 17 |
| Auth | Laravel Breeze + Spatie Permission (Roles: Admin, Manager, Employee) |
| PDF | barryvdh/laravel-dompdf |

## User Roles & Permissions

The application features exactly 3 roles with granular permissions:
1. **Admin**: Can create users, assign roles, define role permissions, and configure system-wide policies.
2. **Manager**: Granted content-management rights (by the Admin) to create/edit courses, lessons, and quizzes for training their employees.
3. **Employee**: Enrolls in courses, ticks off progress, takes quizzes, and views their dashboard.

## Quick Start (Docker)

```bash
# Build and start everything (app + PostgreSQL)
docker compose up --build -d

# Run migrations and seed the database
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed

# Open in browser
# http://localhost:8000
```

### Test Accounts (seeded)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@pilot.test` | `password` |
| Manager | `manager@pilot.test` | `password` |
| Employee | `employee@pilot.test` | `password` |
| Employee (Ops) | `operations@pilot.test` | `password` |

## Running Tests

Tests run against PostgreSQL (not SQLite) to match production behavior with JSONB columns and ILIKE queries.

```bash
# Inside Docker
docker compose exec app php artisan test

# Locally (requires PostgreSQL with pilot_lms_testing database)
cd platform
php artisan test
```

## Local Development (without Docker)

```bash
cd platform

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set DB_CONNECTION=pgsql and your PostgreSQL credentials in .env

# Migrate and seed
php artisan migrate
php artisan db:seed

# Start dev server (Laravel + Vite concurrently)
composer dev
```

## Project Structure

```
support-engine/
├── platform/              ← Laravel application
│   ├── app/
│   │   ├── Actions/       ← Business logic (RecalculateCourseProgress, GradeQuizAttempt, etc.)
│   │   ├── Enums/         ← Role, CourseStatus, etc.
│   │   ├── Filament/      ← Admin panel resources
│   │   ├── Http/          ← Controllers, Requests, Resources
│   │   ├── Jobs/          ← Queued jobs (certificate generation, notifications)
│   │   ├── Models/        ← Eloquent models (20 models)
│   │   └── Policies/      ← Authorization policies
│   ├── database/
│   │   ├── migrations/    ← 23 migrations
│   │   └── seeders/       ← Training content, users, roles, diagnostic trees
│   ├── resources/
│   │   ├── css/           ← Tailwind entry point
│   │   └── js/            ← Vue components + Inertia pages
│   └── tests/
│       └── Feature/       ← Auth, Authorization, Certificates, Enrollment, Progress, Quiz tests
├── pages/                 ← Original static prototype (visual reference)
├── docs/                  ← Implementation map + training plans
├── docker-compose.yml     ← App + PostgreSQL services
└── Dockerfile             ← PHP 8.3 + Node 22 image
```

## How Progress Tracking Works

1. Employee logs in and sees their assigned courses on the dashboard
2. Each course contains modules (day plans) with individual lessons (training materials)
3. Employee studies the material and **ticks the checkbox** next to each completed lesson
4. Checkbox toggle → `POST /courses/{c}/lessons/{l}/complete` → `LessonProgress` record created
5. `RecalculateCourseProgress` action updates section counters, progress bars, and the SVG gauge
6. Managers and admins can monitor employee progress through the Filament reporting panel

## System Documentation

- [PILOT GPS Platform Docs](https://docs.pilot-gps.com/)
- [Implementation Map](docs/IMPLEMENTATION_MAP.md) — full prototype audit and architecture mapping
- [1st-Line Support Training Plan](docs/PILOT%20System%20Training%20Plan%20for%201st-Line%20Support%20(2%20Weeks).md)
- [Admin Panel Training Plan](docs/Training%20Plan%20for%203%20Days_%20PILOT%20Administrative%20Panel.md)