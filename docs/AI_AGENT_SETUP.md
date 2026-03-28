# AI Agent Setup Guide

This document is written for **AI coding agents** (GitHub Copilot in VS Code / Agent mode, Cursor, Codeium, Aider, etc.) working on the **Liberu Genealogy** codebase. It covers how to spin the stack up, understand the project structure, and execute the most common development tasks correctly.

---

## Table of Contents

1. [Project Identity](#1-project-identity)
2. [Tech Stack Versions](#2-tech-stack-versions)
3. [Local Environment Setup (Docker + WSL)](#3-local-environment-setup-docker--wsl)
4. [Codebase Map — Where Things Live](#4-codebase-map--where-things-live)
5. [Coding Conventions](#5-coding-conventions)
6. [Running & Verifying Changes](#6-running--verifying-changes)
7. [Common Tasks with Correct Commands](#7-common-tasks-with-correct-commands)
8. [Key Domain Models](#8-key-domain-models)
9. [Feature Modules Reference](#9-feature-modules-reference)
10. [Testing Guide](#10-testing-guide)
11. [Gotchas & Known Issues](#11-gotchas--known-issues)

---

## 1. Project Identity

- **Repository**: `faliqadlan/laravel-family-tree`  
- **Working directory** (inside WSL): `~/projects/laravel-family-tree` (never `/mnt/c/…`)  
- **Purpose**: Full-featured open-source genealogy platform with social networking, event management, DNA matching, facial recognition and AI-assisted research.

---

## 2. Tech Stack Versions

| Layer | Technology | Version |
|---|---|---|
| Language | PHP | 8.3 (Docker) / 8.5 (native) |
| Framework | Laravel | 12 |
| Admin UI | FilamentPHP | 5 |
| Reactive UI | Livewire | 4 |
| CSS | TailwindCSS | 3 |
| HTTP server | Laravel Octane + RoadRunner | latest |
| Database | MySQL | 8.0 |
| Cache / Queue | Redis | 7 |
| Auth | Laravel Fortify + Jetstream | — |
| Roles | Spatie Laravel Permission | — |
| Multi-tenancy | Teams (Jetstream) via `BelongsToTenant` trait | — |
| Payments | Laravel Cashier (Stripe) | — |

---

## 3. Local Environment Setup (Docker + WSL)

### Prerequisites (verify these before doing anything else)

```bash
docker info                 # Must succeed — Docker daemon is running
docker compose version      # Must show v2.x
```

If either fails → ask the user to start Docker Desktop and enable WSL integration.

### First-time bootstrap (run once)

```bash
cd ~/projects/laravel-family-tree   # or wherever it's cloned
./setup-wsl.sh                      # builds, migrates, seeds, starts stack
```

### Verify the stack is healthy

```bash
docker compose ps               # all containers should show "Up" or "healthy"
curl -s http://localhost:8000/up | grep -q OK && echo "App is up"
```

### Start / stop during development

```bash
make up          # start all containers
make down        # stop all containers
make logs        # tail logs (Ctrl+C to exit)
```

---

## 4. Codebase Map — Where Things Live

```
app/
├── Console/
│   └── Commands/               # Artisan commands (e.g. SendGatheringReminders.php)
├── Console/Kernel.php          # Scheduled task registration
├── Events/                     # Laravel events (AchievementUnlocked, etc.)
├── Filament/App/
│   ├── Pages/                  # Full Filament pages (NetworkingHubPage, PrivateMessagingPage, …)
│   └── Resources/              # Filament CRUD resources (PersonResource, GatheringResource, …)
│       └── <ResourceName>/
│           ├── Pages/          # ListXxx, CreateXxx, EditXxx, ViewXxx
│           └── RelationManagers/
├── Http/Controllers/           # Standard Laravel controllers
├── Jobs/                       # Queued jobs (DnaMatching, GedcomImport, …)
├── Livewire/                   # Livewire components (GatheringDetailPage, charts, …)
├── Models/                     # ~90 Eloquent models
├── Modules/                    # Domain modules (see §9)
│   ├── Core/
│   ├── Person/
│   ├── Family/
│   ├── Tree/
│   ├── DNA/
│   ├── Events/
│   └── …
├── Notifications/              # Laravel notification classes
├── Observers/                  # Model observers
├── Policies/                   # Authorization policies (one per model)
├── Providers/                  # Service providers
├── Services/                   # Business logic services
└── Traits/
    └── BelongsToTenant.php     # Applied to all tenant-scoped models

database/
├── migrations/                 # 170+ migration files
├── seeders/                    # Database seeders
└── factories/                  # Model factories

resources/views/
├── filament/app/pages/         # Blade views for Filament pages
└── livewire/                   # Blade views for Livewire components

routes/
├── web.php                     # Web routes
├── api.php                     # API routes
└── console.php                 # Artisan closures

docs/
├── DEPLOYMENT.md               # Deployment guide for humans
└── AI_AGENT_SETUP.md           # This file
```

---

## 5. Coding Conventions

### PHP / Laravel

- **Namespace root**: `App\`  
- **Models**: `app/Models/ModelName.php` → `App\Models\ModelName`  
- **Filament resources**: `app/Filament/App/Resources/` — extend `AppResource` (not the generic `Resource`)  
- **Filament pages**: `app/Filament/App/Pages/` — extend `Filament\Pages\Page`  
- **Livewire components**: `app/Livewire/ComponentName.php` — extend `Livewire\Component`  
- **Policies**: one per model, method names match Laravel conventions (`viewAny`, `view`, `create`, `update`, `delete`)  
- **Services**: in `app/Services/`; injected via constructor DI; contain business logic, NOT HTTP/Filament concerns  
- **Notifications**: extend `Illuminate\Notifications\Notification`; always implement `toArray()` for database channel  
- **Migrations**: timestamp prefix `YYYY_MM_DD_HHMMSS_`; use `Blueprint` helpers; add indexes for FK and status columns  
- **Multi-tenancy**: models that are team-scoped must `use BelongsToTenant;` — this auto-applies a `team_id` global scope

### Filament v5 specifics

- Form schemas use `Filament\Schemas\Schema` (not `Filament\Forms\Form` — there's a class alias in `bootstrap/app.php`)  
- The `form(Schema $schema)` signature is correct  
- `Section::make(...)` is imported from `Filament\Schemas\Components\Section`  
- Navigation icons use Heroicons v2 string names: `'heroicon-o-...'`, `'heroicon-s-...'`  
- Relation managers extend `Filament\Resources\RelationManagers\RelationManager`

### Database

- **Primary keys**: `$table->id()` (BIGINT UNSIGNED)  
- **Foreign keys**: `$table->foreignId('user_id')->constrained()->cascadeOnDelete()`  
- **Soft deletes**: add `$table->softDeletes()` + `use SoftDeletes` in model when appropriate  
- **JSON columns**: use `json('column_name')` + cast to `array` in model  
- **Enum columns**: `$table->enum('status', ['a', 'b', 'c'])` + matching constants in model

---

## 6. Running & Verifying Changes

### After editing PHP files

```bash
# Clear opcode cache (Octane caches classes in memory)
make cache-clear
# or inside the container:
make shell
php artisan optimize:clear
```

### After adding a migration

```bash
make migrate
# Verify:
make artisan CMD='migrate:status'
```

### After adding a new model / policy / notification

```bash
# Ensure autoloader picks it up (usually not needed, but after class renames):
make shell
composer dump-autoload
```

### After editing a Filament resource (form / table changes)

```bash
make cache-clear
# Then refresh http://localhost:8000 in the browser
```

### After editing `.env`

```bash
make restart   # restarts the app container to reload config
```

### After editing Blade / Livewire views

Changes are picked up on next page load automatically (no container restart needed).

### After adding a Livewire component

Register it in `routes/web.php` if it's a full-page component:

```php
Route::get('/my-path', \App\Livewire\MyComponent::class)->name('my.route');
```

---

## 7. Common Tasks with Correct Commands

| Task | Command |
|---|---|
| Open a shell in the running app container | `make shell` |
| Run any Artisan command | `make artisan CMD='route:list'` |
| Tail application logs | `make logs-app` |
| Create a new Eloquent model | `make artisan CMD='make:model ModelName -m'` |
| Create a new Filament resource | `make artisan CMD='make:filament-resource ModelName --generate'` |
| Create a new Livewire component | `make artisan CMD='make:livewire ComponentName'` |
| Create a new notification | `make artisan CMD='make:notification NotificationName'` |
| Create a new policy | `make artisan CMD='make:policy ModelNamePolicy --model=ModelName'` |
| Rollback last migration | `make artisan CMD='migrate:rollback'` |
| View scheduled tasks | `make artisan CMD='schedule:list'` |
| Run tests | `make test` |
| Run a single test file | `make artisan CMD='test --filter=MyTest'` |

---

## 8. Key Domain Models

### User (`app/Models/User.php`)

Central to everything. Key relationships:

```php
$user->teams                   // HasMany Team
$user->sentConnections         // HasMany UserConnection (as requester)
$user->receivedConnections     // HasMany UserConnection (as receiver)
$user->privacySetting          // HasOne UserPrivacySetting
$user->organizedGatherings     // HasMany Gathering
$user->gatheringInvitations    // HasMany GatheringInvitation
$user->conversations           // via Conversation (user_one / user_two)
$user->achievements            // HasMany UserAchievement
```

Helper methods:
```php
$user->isConnectedTo(int $userId): bool
$user->getPrivacySetting(): UserPrivacySetting
$user->isPremium(): bool
```

### Person (`app/Models/Person.php`)

The core genealogy entity. Uses `BelongsToTenant`.

Key fields: `givn`, `surn`, `sex`, `birthday`, `birth_year`, `deathday`, `photo_url`, `tree_id`  
Key relationships: `families()`, `events()`, `photoTags()`, `faceEncodings()`

### Gathering (`app/Models/Gathering.php`)

Family events. Key relationships: `organizer()`, `tree()`, `person()`, `invitations()`, `contributions()`, `comments()`  
Key methods: `generateIcs()`, `getTotalContributions()`, `getFundingProgress()`

### UserConnection (`app/Models/UserConnection.php`)

The social graph. Status constants: `STATUS_PENDING`, `STATUS_APPROVED`, `STATUS_REJECTED`, `STATUS_BLOCKED`  
Key static: `UserConnection::getConnectionBetween(int $a, int $b): ?UserConnection`

### UserPrivacySetting (`app/Models/UserPrivacySetting.php`)

Per-user visibility control. Visibility constants: `VISIBILITY_PUBLIC`, `VISIBILITY_CONNECTIONS`, `VISIBILITY_PRIVATE`, `VISIBILITY_MASKED`  
Key method: `maskValue(string $field, mixed $value, bool $isConnected): mixed`

---

## 9. Feature Modules Reference

Modules live in `app/Modules/<Name>/`. Each has a `ServiceProvider` and one or more `Services/`.

| Module | Key Service | Responsibility |
|---|---|---|
| `Core` | `GedcomService`, `TreeService` | GEDCOM parsing, tree operations |
| `Person` | `PersonService` | Person CRUD, name handling |
| `Family` | `FamilyService` | Family relationship management |
| `Tree` | `TreeBuilderService` | Tree traversal (ancestors, descendants) |
| `DNA` | `DNAService`, `DNAMatchService` | DNA upload, matching, triangulation |
| `Events` | `EventsService` | Family event management |
| `Media` | `MediaService` | Photo / media handling |
| `Places` | `PlacesService`, `GeocodingService` | Geographic location lookup |
| `Sources` | `SourcesService` | Source citation management |
| `Notes` | `NotesService` | Notes CRUD |
| `Admin` | `AdminService` | Administrative tasks |

**Top-level services** (not in a module):

| Service | Responsibility |
|---|---|
| `ConnectionService` | User connection lifecycle (send, approve, reject, block, mask) |
| `GatheringService` | Gathering CRUD, invitations, RSVP, contributions, comments, reminders |
| `VideoConferencingService` | Virtual event platform integrations (Zoom, etc.) |

---

## 10. Testing Guide

### Test structure

```
tests/
├── Feature/            # Integration tests (HTTP, database)
├── Unit/               # Pure unit tests (services, models)
└── Filament/Resources/ # Filament resource tests (form/table schema assertions)
```

### Run all tests

```bash
make test
# or:
make artisan CMD='test'
```

### Run a specific test

```bash
make artisan CMD='test --filter=GatheringResourceTest'
make artisan CMD='test tests/Feature/ConnectionServiceTest.php'
```

### Test database

Tests use the database defined in `.env.testing` (`liberu_genealogy_testing`). It uses `RefreshDatabase` — each test runs in a transaction that is rolled back.

### Writing tests for a new feature

1. Create `tests/Feature/MyFeatureTest.php` (or `Unit/`) extending `Tests\TestCase`.
2. Use `RefreshDatabase` trait if the test touches the database.
3. Use `actingAs($user)` for authenticated requests.
4. Check existing tests in `tests/Filament/Resources/` for Filament resource test patterns.

---

## 11. Gotchas & Known Issues

### 1. Octane caches classes in memory

Octane keeps the application bootstrapped between requests. After PHP code changes, you **must** clear the opcode cache:

```bash
make cache-clear
```

If changes still don't appear, restart the container:

```bash
make restart
```

### 2. `Filament\Forms\Form` vs `Filament\Schemas\Schema`

Filament v5 renamed `Form` to `Schema`. The alias is set up in `bootstrap/app.php`:

```php
class_alias(\Filament\Schemas\Schema::class, \Filament\Forms\Form::class);
```

Use `Schema $schema` in new resources. Both work at runtime.

### 3. `Section` import

In Filament v5 resources, `Section` must be imported from `Filament\Schemas\Components\Section`, not `Filament\Forms\Components\Section`.

### 4. Multi-tenancy `BelongsToTenant`

Any model that is team-scoped automatically has a `team_id` column and a global scope that filters by the currently authenticated team. When writing seeders or tests, set `\App\Models\Team::$current` or use `withoutGlobalScope`.

### 5. Permission checks in policies

Most existing policies call `$user->checkPermissionTo('view ModelName')` via Spatie Permission. New policies should follow the same pattern for consistency, while the new social-graph policies (`UserConnectionPolicy`, `GatheringPolicy`) use direct model checks instead because they are relationship-based.

### 6. RoadRunner binary

The `.rr.yaml` config file in the project root is required. RoadRunner downloads its binary to `~/.rr` on first `artisan octane:start --server=roadrunner`. Inside Docker this happens automatically. If it's missing outside Docker:

```bash
php artisan octane:install --server=roadrunner
```

### 7. Line endings

`.gitattributes` enforces `eol=lf` for all files. Never convert files to CRLF. On Windows, configure Git:

```bash
git config --global core.autocrlf input
```

### 8. Cloning on Windows

Clone inside the WSL filesystem (`~/…`), not a Windows path (`/mnt/c/…`). Performance on `/mnt/c/` is 10–50× slower due to the 9P filesystem bridge.
