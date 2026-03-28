# =============================================================
# Makefile — Liberu Genealogy
# =============================================================
# All targets assume Docker Compose is running.
# On WSL, first run:  ./setup-wsl.sh
#
# Common workflow:
#   make setup    — first-time setup (build + migrate + seed)
#   make up       — start containers
#   make down     — stop containers
#   make shell    — open a shell in the app container
#   make artisan CMD='route:list'  — run any Artisan command
# =============================================================

# Detect "docker compose" vs legacy "docker-compose"
DOCKER_COMPOSE := $(shell \
  if docker compose version > /dev/null 2>&1; then \
    echo "docker compose"; \
  else \
    echo "docker-compose"; \
  fi)

# Host UID/GID (important on WSL so file ownership is correct)
WWWUSER ?= $(shell id -u)
WWWGROUP ?= $(shell id -g)
BUILD_ARGS := --build-arg WWWUSER=$(WWWUSER) --build-arg WWWGROUP=$(WWWGROUP)

.PHONY: help setup up down restart build rebuild logs ps \
        shell artisan migrate seed fresh npm-install npm-build \
        tinker test lint init worker

# ── Help ──────────────────────────────────────────────────────
help: ## Show this help message
	@echo ""
	@echo "Usage: make <target> [VARIABLE=value]"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"; printf "\033[1mTargets:\033[0m\n"} \
	  /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@echo ""

# ── Setup (first-time) ────────────────────────────────────────
setup: ## First-time setup: build images, start services, run migrations & seed
	@echo "Running first-time setup…"
	$(MAKE) build
	$(MAKE) up-infra
	$(MAKE) init
	$(MAKE) up
	@echo ""
	@echo "✔  Setup complete. Application: http://localhost:$${APP_PORT:-8000}"

init: ## Run key:generate + migrate + seed + storage:link inside init container
	$(DOCKER_COMPOSE) run --rm --no-deps init \
	    sh -c "php artisan key:generate --force && \
	           php artisan migrate --force && \
	           php artisan db:seed --force && \
	           php artisan storage:link"

# ── Lifecycle ─────────────────────────────────────────────────
build: ## Build Docker images
	$(DOCKER_COMPOSE) build $(BUILD_ARGS)

rebuild: ## Rebuild images from scratch (no cache)
	$(DOCKER_COMPOSE) build $(BUILD_ARGS) --no-cache

up: ## Start all services (app + mysql + redis + mailpit)
	$(DOCKER_COMPOSE) up -d

up-infra: ## Start only infrastructure services (mysql + redis)
	$(DOCKER_COMPOSE) up -d mysql redis

down: ## Stop and remove containers (volumes are preserved)
	$(DOCKER_COMPOSE) down --remove-orphans

restart: ## Restart the app container
	$(DOCKER_COMPOSE) restart app

# ── Logs & Status ─────────────────────────────────────────────
logs: ## Tail logs from all containers
	$(DOCKER_COMPOSE) logs -f

logs-app: ## Tail app container logs only
	$(DOCKER_COMPOSE) logs -f app

ps: ## Show container status
	$(DOCKER_COMPOSE) ps

# ── App Shell & Artisan ───────────────────────────────────────
shell: ## Open an interactive shell inside the app container
	$(DOCKER_COMPOSE) exec app sh

artisan: ## Run an Artisan command: make artisan CMD='route:list'
	$(DOCKER_COMPOSE) exec app php artisan $(CMD)

tinker: ## Open Laravel Tinker REPL
	$(DOCKER_COMPOSE) exec app php artisan tinker

# ── Database ──────────────────────────────────────────────────
migrate: ## Run pending migrations
	$(DOCKER_COMPOSE) exec app php artisan migrate

migrate-status: ## Show migration status
	$(DOCKER_COMPOSE) exec app php artisan migrate:status

seed: ## Run database seeders
	$(DOCKER_COMPOSE) exec app php artisan db:seed

fresh: ## Drop all tables, re-run migrations and seed (WARNING: destroys data)
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed

# ── Frontend ──────────────────────────────────────────────────
npm-install: ## Install Node dependencies inside the app container
	$(DOCKER_COMPOSE) exec app npm install

npm-build: ## Build frontend assets (Vite) inside the app container
	$(DOCKER_COMPOSE) exec app npm run build

npm-dev: ## Start Vite dev server inside the app container
	$(DOCKER_COMPOSE) exec app npm run dev

# ── Cache ─────────────────────────────────────────────────────
cache-clear: ## Clear all Laravel caches
	$(DOCKER_COMPOSE) exec app php artisan optimize:clear

cache-warm: ## Warm config / route / event caches
	$(DOCKER_COMPOSE) exec app sh -c "php artisan config:cache && php artisan route:cache && php artisan event:cache"

# ── Tests & Code Quality ──────────────────────────────────────
test: ## Run PHPUnit tests
	$(DOCKER_COMPOSE) exec app php artisan test

lint: ## Run PHP CS Fixer / Pint
	$(DOCKER_COMPOSE) exec app ./vendor/bin/pint

# ── Optional profiles ─────────────────────────────────────────
worker: ## Start the queue worker container (worker profile)
	$(DOCKER_COMPOSE) --profile worker up -d worker

worker-down: ## Stop the queue worker container
	$(DOCKER_COMPOSE) --profile worker down worker
