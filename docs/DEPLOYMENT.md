# Deployment Guide

This guide covers every way to get **Liberu Genealogy** running — from a 60-second Docker spin-up on Windows WSL to a production deployment behind a load balancer.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Option A — Windows WSL + Docker (recommended for local dev)](#2-option-a--windows-wsl--docker)
3. [Option B — Linux / macOS + Docker](#3-option-b--linux--macos--docker)
4. [Option C — Manual (bare-metal / VPS)](#4-option-c--manual-bare-metal--vps)
5. [Environment Variables Reference](#5-environment-variables-reference)
6. [Running Database Migrations](#6-running-database-migrations)
7. [Scheduled Tasks & Queue Workers](#7-scheduled-tasks--queue-workers)
8. [Production Checklist](#8-production-checklist)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Prerequisites

| Tool | Minimum version | Notes |
|---|---|---|
| Docker Desktop | 4.x | WSL2 backend required on Windows |
| Docker Compose | v2 (`docker compose`) or v1 (`docker-compose`) | Bundled with Docker Desktop |
| Git | Any recent | Needed to clone the repo |
| PHP | 8.3+ | Only required for Option C |
| Composer | 2.x | Only required for Option C |
| Node.js / npm | 20+ | Only required for Option C |
| MySQL / MariaDB | 8.0 / 10.6 | Provided by Docker in Options A/B |

---

## 2. Option A — Windows WSL + Docker

> **Recommended for Windows users.** Clone inside WSL (not under `/mnt/c/`) for best performance.

### 2.1 One-time Windows setup

1. Install [Docker Desktop for Windows](https://docs.docker.com/desktop/install/windows-install/).
2. Docker Desktop → **Settings → General** → tick **"Use the WSL 2 based engine"**.
3. Docker Desktop → **Settings → Resources → WSL Integration** → enable your distro (e.g. Ubuntu).
4. Open your WSL terminal and verify: `docker info` should show server info without errors.

### 2.2 Clone inside WSL

```bash
# ✅ Do this — inside WSL filesystem (fast)
cd ~
git clone https://github.com/faliqadlan/laravel-family-tree.git
cd laravel-family-tree

# ❌ Avoid this — Windows path (slow I/O, inotify issues)
# cd /mnt/c/Users/YourName/projects/...
```

### 2.3 Run the WSL bootstrap script

```bash
chmod +x setup-wsl.sh
./setup-wsl.sh
```

The script will:
- Detect and validate Docker
- Copy `.env.docker` → `.env` (pre-configured for Docker service names)
- Inject your WSL UID/GID (`WWWUSER` / `WWWGROUP`) so file ownership is correct
- Build Docker images
- Start MySQL and Redis, wait for health checks
- Generate `APP_KEY`, run migrations, seed demo data
- Start the app, worker and Mailpit

When it finishes:

| Service | URL |
|---|---|
| Application | http://localhost:8000 |
| Mailpit (email testing) | http://localhost:8025 |

### 2.4 Day-to-day workflow

```bash
make up             # Start everything
make down           # Stop everything
make logs           # Tail all logs (Ctrl+C to exit)
make shell          # Interactive shell inside the app container
make artisan CMD='migrate:status'
make migrate        # Run pending migrations
make fresh          # ⚠️  Wipe DB, migrate fresh, re-seed
make test           # Run PHPUnit
make help           # All available targets
```

---

## 3. Option B — Linux / macOS + Docker

```bash
git clone https://github.com/faliqadlan/laravel-family-tree.git
cd laravel-family-tree

# Copy the Docker-ready env file
cp .env.docker .env

# Build images (uses your UID/GID automatically via Makefile)
make setup
```

`make setup` builds images, starts infrastructure, runs migrations and seeds, then starts the application. All in one step.

---

## 4. Option C — Manual (bare-metal / VPS)

Use this when you have PHP installed natively (e.g. a VPS, shared hosting, or local PHP installation without Docker).

### 4.1 Install system dependencies

```bash
# Ubuntu / Debian example
sudo apt update && sudo apt install -y \
    php8.3-cli php8.3-fpm php8.3-mysql php8.3-redis \
    php8.3-gd php8.3-mbstring php8.3-xml php8.3-zip \
    php8.3-bcmath php8.3-intl php8.3-pcntl \
    mysql-server redis-server nodejs npm \
    composer git
```

### 4.2 Clone and install

```bash
git clone https://github.com/faliqadlan/laravel-family-tree.git
cd laravel-family-tree

composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 4.3 Configure environment

```bash
cp .env.example .env
```

Edit `.env` and set at minimum:

```dotenv
APP_KEY=          # leave blank; generated in next step
DB_HOST=127.0.0.1
DB_DATABASE=liberu_genealogy
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
REDIS_HOST=127.0.0.1
MAIL_HOST=your_smtp_host
```

### 4.4 Initialise

```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize
```

### 4.5 Serve

**Development:**

```bash
php artisan serve         # http://localhost:8000
```

**Production (nginx + PHP-FPM):**

Configure nginx to point `root` to `public/`, set `FASTCGI_PASS` to your PHP-FPM socket, and serve with a standard Laravel vhost. A full example:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/laravel-family-tree/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 5. Environment Variables Reference

The table below lists the most important variables. For a complete Docker-ready example see `.env.docker`; for a manual-install example see `.env.example`.

| Variable | Default | Description |
|---|---|---|
| `APP_KEY` | *(empty)* | 32-byte encryption key — generate with `php artisan key:generate` |
| `APP_ENV` | `local` | `local`, `staging` or `production` |
| `APP_DEBUG` | `true` | Set `false` in production |
| `APP_URL` | `http://localhost:8000` | Full public URL |
| `DB_HOST` | `127.0.0.1` | Use `mysql` when running inside Docker |
| `DB_DATABASE` | `liberu` | Database name |
| `DB_USERNAME` | `liberu` | Database user |
| `DB_PASSWORD` | `secret` | Database password |
| `REDIS_HOST` | `127.0.0.1` | Use `redis` when running inside Docker |
| `CACHE_STORE` | `file` | Use `redis` in Docker for best performance |
| `SESSION_DRIVER` | `file` | Use `redis` in Docker |
| `QUEUE_CONNECTION` | `sync` | Use `redis` (+ `make worker`) for async jobs |
| `MAIL_HOST` | `mailpit` | SMTP host (`mailpit` in Docker, real SMTP in prod) |
| `OCTANE_SERVER` | `roadrunner` | `roadrunner` (Docker) or `swoole`/`frankenphp` |
| `WITH_SCHEDULER` | `true` | Runs `schedule:run` every minute via supercronic |
| `RUNNING_MIGRATIONS_AND_SEEDERS` | `false` | Set `true` only on first boot (init container) |
| `WWWUSER` | `1000` | UID for container process — auto-set by `setup-wsl.sh` |
| `WWWGROUP` | `1000` | GID for container process — auto-set by `setup-wsl.sh` |

---

## 6. Running Database Migrations

### Inside Docker

```bash
make migrate                          # pending only
make fresh                            # wipe + migrate + seed  ⚠️  destroys data
make artisan CMD='migrate:rollback'   # roll back last batch
make artisan CMD='migrate:status'     # show status
```

### Bare-metal

```bash
php artisan migrate
php artisan migrate:fresh --seed
```

---

## 7. Scheduled Tasks & Queue Workers

### Scheduler

The scheduler runs `php artisan schedule:run` every minute. Registered tasks include:

| Task | Schedule | Description |
|---|---|---|
| `ScanForDuplicatePersons` | Daily | Find duplicate person records |
| `gatherings:send-reminders` | Daily 08:00 | Send gathering reminders 3 days before event |

**Docker:** The scheduler is managed by **supercronic** inside the `app` container (`WITH_SCHEDULER=true`).  
**Bare-metal:** Add a cron entry: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

### Queue Worker

**Docker:**

```bash
make worker            # Start the worker container (docker compose --profile worker up -d)
make worker-down       # Stop it
```

**Bare-metal:**

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Use a process supervisor (Supervisor, systemd) to keep the worker alive in production.

---

## 8. Production Checklist

Before going live, tick off every item below:

- [ ] `APP_ENV=production`, `APP_DEBUG=false` in `.env`
- [ ] `APP_KEY` is set and backed up securely
- [ ] HTTPS / SSL certificate configured (Let's Encrypt recommended)
- [ ] `APP_URL` points to your production domain with `https://`
- [ ] Database password changed from the default `secret`
- [ ] `QUEUE_CONNECTION=redis` and queue worker is running under a supervisor
- [ ] `SESSION_DRIVER=redis`, `CACHE_STORE=redis` for stateless horizontal scaling
- [ ] `MAIL_MAILER` configured to a real transactional email provider (Mailgun, SES, etc.)
- [ ] Stripe keys replaced with live keys (`STRIPE_KEY`, `STRIPE_SECRET`)
- [ ] `php artisan optimize` run after every deployment
- [ ] `php artisan migrate --force` included in deployment pipeline
- [ ] Log rotation configured for `storage/logs/`
- [ ] Docker image pushed to a private registry (not `liberu-genealogy:latest` from dev)
- [ ] Health check endpoint `/up` monitored by uptime service

---

## 9. Troubleshooting

### Docker Desktop not accessible from WSL

```
Cannot connect to the Docker daemon at unix:///var/run/docker.sock
```

1. Open Docker Desktop on Windows.
2. Settings → Resources → WSL Integration → enable your distro.
3. Restart Docker Desktop, then reopen your WSL terminal.

### Permission denied on storage / cache directories

```bash
# Inside the app container:
make shell
chmod -R 775 storage bootstrap/cache
chown -R octane:octane storage bootstrap/cache
```

### "Class not found" or autoloader errors

```bash
make shell
composer dump-autoload
php artisan optimize:clear
```

### Migrations fail: "Unknown column" or "Table already exists"

```bash
make artisan CMD='migrate:status'    # see what's run/pending
make artisan CMD='migrate --pretend' # dry-run to see SQL
```

If a conflict exists you can roll back specific batches:

```bash
make artisan CMD='migrate:rollback --step=1'
```

### WSL file watching is slow (Vite)

Add to `.env`:

```dotenv
CHOKIDAR_USEPOLLING=true
```

Then run `make npm-dev`.

### RoadRunner binary not found

The binary is downloaded automatically by Octane on first start. If it fails (offline environment):

```bash
make shell
php artisan octane:install --server=roadrunner
```

### MySQL container keeps restarting

```bash
docker compose logs mysql | tail -30
```

Common cause: existing volume has a root password mismatch. Fix:

```bash
make down
docker volume rm laravel-family-tree_mysql   # ⚠️  destroys all data
make setup
```
