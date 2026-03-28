#!/usr/bin/env bash
# =============================================================
# setup-wsl.sh — One-command bootstrap for Windows WSL + Docker
# =============================================================
# Prerequisites (install once in WSL):
#   1. Docker Desktop for Windows with "WSL 2 backend" enabled
#      https://docs.docker.com/desktop/install/windows-install/
#   2. Git for WSL: sudo apt install git
#
# Usage:
#   chmod +x setup-wsl.sh
#   ./setup-wsl.sh
#
# What it does:
#   1. Verifies Docker is running and accessible from WSL
#   2. Sets host UID/GID so container file-ownership matches yours
#   3. Copies .env.docker → .env (if .env is absent)
#   4. Builds Docker images
#   5. Starts MySQL + Redis, then runs migrations + seeds
#   6. Starts the full application stack
#   7. Prints URLs and useful commands
# =============================================================

set -euo pipefail

# ── Colours ──────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Colour

info()    { echo -e "${BLUE}ℹ  $*${NC}"; }
success() { echo -e "${GREEN}✔  $*${NC}"; }
warn()    { echo -e "${YELLOW}⚠  $*${NC}"; }
error()   { echo -e "${RED}✖  $*${NC}" >&2; }
header()  { echo -e "\n${BOLD}${CYAN}══ $* ══${NC}\n"; }

# ── Helpers ───────────────────────────────────────────────────
need_cmd() {
    if ! command -v "$1" &>/dev/null; then
        error "Required command not found: $1"
        echo "  Install hint: $2"
        exit 1
    fi
}

docker_compose() {
    if command -v "docker-compose" &>/dev/null; then
        docker-compose "$@"
    else
        docker compose "$@"
    fi
}

# ── Step 0: Preflight checks ─────────────────────────────────
header "Preflight checks"

need_cmd docker  "Install Docker Desktop (WSL2 backend): https://docs.docker.com/desktop/install/windows-install/"
need_cmd git     "sudo apt install git"

if ! docker info &>/dev/null 2>&1; then
    error "Docker daemon is not running."
    echo ""
    echo "  Make sure Docker Desktop is:"
    echo "    1. Installed on Windows"
    echo "    2. Started (look for the whale icon in the system tray)"
    echo "    3. Settings → General → 'Use the WSL 2 based engine' is ticked"
    echo "    4. Settings → Resources → WSL Integration → your distro is enabled"
    echo ""
    exit 1
fi
success "Docker is running"

# ── Step 1: Environment file ──────────────────────────────────
header "Environment configuration"

if [ ! -f .env ]; then
    if [ -f .env.docker ]; then
        cp .env.docker .env
        success "Copied .env.docker → .env"
    elif [ -f .env.example ]; then
        cp .env.example .env
        warn "Copied .env.example → .env — review DB_HOST/REDIS_HOST (should be 'mysql'/'redis')"
    else
        error "No .env.docker or .env.example found."
        exit 1
    fi
else
    info ".env already exists — skipping copy"
fi

# Inject host UID/GID so container file ownership matches WSL user.
# This prevents "permission denied" errors when editing files from WSL.
HOST_UID=$(id -u)
HOST_GID=$(id -g)

if grep -q "^WWWUSER=" .env; then
    sed -i "s/^WWWUSER=.*/WWWUSER=${HOST_UID}/" .env
else
    echo "WWWUSER=${HOST_UID}" >> .env
fi

if grep -q "^WWWGROUP=" .env; then
    sed -i "s/^WWWGROUP=.*/WWWGROUP=${HOST_GID}/" .env
else
    echo "WWWGROUP=${HOST_GID}" >> .env
fi

export WWWUSER="${HOST_UID}"
export WWWGROUP="${HOST_GID}"

success "Set WWWUSER=${HOST_UID}, WWWGROUP=${HOST_GID}"

# ── Step 2: Build images ──────────────────────────────────────
header "Building Docker images (this may take a few minutes)"

docker_compose build \
    --build-arg WWWUSER="${HOST_UID}" \
    --build-arg WWWGROUP="${HOST_GID}"

success "Images built"

# ── Step 3: Start infrastructure (DB + Redis) ─────────────────
header "Starting MySQL and Redis"

docker_compose up -d mysql redis

info "Waiting for MySQL to become healthy…"
RETRIES=30
until docker_compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; do
    RETRIES=$((RETRIES - 1))
    if [ "${RETRIES}" -le 0 ]; then
        error "MySQL did not become healthy in time."
        docker_compose logs mysql | tail -20
        exit 1
    fi
    sleep 2
done
success "MySQL is healthy"

# ── Step 4: Generate key + migrate + seed ─────────────────────
header "Initialising database"

# Run the init profile which generates the key, migrates and seeds.
docker_compose run --rm \
    --no-deps \
    -e RUNNING_MIGRATIONS_AND_SEEDERS=false \
    init || true

# Explicitly run each step so we can show progress.
docker_compose run --rm --no-deps init \
    sh -c "php artisan key:generate --force && echo 'Key generated'"

docker_compose run --rm --no-deps init \
    sh -c "php artisan migrate --force && echo 'Migrations done'"

docker_compose run --rm --no-deps init \
    sh -c "php artisan db:seed --force && echo 'Seeding done'"

docker_compose run --rm --no-deps init \
    sh -c "php artisan storage:link && echo 'Storage linked'"

success "Database initialised"

# ── Step 5: Start mailpit ─────────────────────────────────────
header "Starting Mailpit (email catcher)"
docker_compose up -d mailpit
success "Mailpit started — web UI: http://localhost:8025"

# ── Step 6: Start the application ────────────────────────────
header "Starting application"
docker_compose up -d app
success "Application started"

# ── Done ──────────────────────────────────────────────────────
APP_PORT=$(grep -E "^APP_PORT=" .env 2>/dev/null | cut -d= -f2 || echo "8000")
APP_PORT="${APP_PORT:-8000}"

echo ""
echo -e "${BOLD}${GREEN}══════════════════════════════════════════════${NC}"
echo -e "${BOLD}${GREEN}   Liberu Genealogy is up and running! 🎉    ${NC}"
echo -e "${BOLD}${GREEN}══════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${BOLD}Application${NC}   http://localhost:${APP_PORT}"
echo -e "  ${BOLD}Mailpit UI${NC}    http://localhost:8025"
echo ""
echo -e "${BOLD}Useful commands:${NC}"
echo "  make logs          — tail all container logs"
echo "  make shell         — open a shell inside the app container"
echo "  make artisan CMD='migrate:status'  — run an Artisan command"
echo "  make down          — stop all containers"
echo "  make fresh         — wipe the database and re-seed"
echo ""
echo -e "See ${BOLD}docs/DEPLOYMENT.md${NC} for a full reference."
