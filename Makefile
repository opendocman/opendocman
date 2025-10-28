# OpenDocMan Management Makefile
# Provides convenient commands for development, testing, and deployment
#
# Docker Compose Compatibility:
# This Makefile automatically detects and uses the appropriate Docker Compose command:
# - Prefers 'docker-compose' (standalone binary) if available
# - Falls back to 'docker compose' (Docker CLI plugin) if docker-compose is not found
# - Provides clear error message if neither is available
# Use 'make docker-compose-version' to see which version is being used

# Docker Compose command - automatically detect available version
DOCKER_COMPOSE := $(shell \
	if command -v docker-compose >/dev/null 2>&1; then \
		echo "docker-compose"; \
	elif docker compose version >/dev/null 2>&1; then \
		echo "docker compose"; \
	else \
		echo "echo 'Error: Neither docker-compose nor docker compose found. Please install Docker Compose.' && exit 1"; \
	fi)

.PHONY: help setup env-generate env-validate build up down restart logs clean rebuild install status backup restore
.PHONY: test test-unit test-integration test-user test-department test-class test-file test-list test-quiet test-watch test-install
.PHONY: coverage coverage-html coverage-xml coverage-all test-coverage
.PHONY: dev shell shell-db clean-volumes ps top stats security-scan version config serve-local serve-local-stop
.PHONY: start stop reset scripts-help logs-app logs-db update restore-db restore-files env-check docker-compose-version
.PHONY: copyright-update copyright-dynamic copyright-check

# Default target
help: ## Show this help message
	@echo "OpenDocMan Management Commands"
	@echo "=============================="
	@echo ""
	@echo "🚀 Quick Start:"
	@echo "  make setup     - Complete setup: generate .env, validate, and start services"
	@echo "  make test      - Run all tests"
	@echo "  make up        - Start services"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Environment Variables:"
	@echo "  COMPOSE_PROJECT_NAME  - Set custom project name (default: opendocman)"
	@echo "  COMPOSE_FILE          - Override docker-compose file"
	@echo "  ODM_ENV               - Set environment (dev, prod, test)"
	@echo ""
	@echo "Docker Compose:"
	@echo "  Auto-detects 'docker-compose' or 'docker compose' command"
	@echo "  Use 'make docker-compose-version' to see which version is being used"
	@echo ""
	@echo "Copyright Management:"
	@echo "  make copyright-check        - Check current copyright status"
	@echo "  make copyright-update       - Update static copyright years to 2025"  
	@echo "  make copyright-dynamic      - Set up automatic yearly copyright updates"
	@echo "  make copyright-all          - Complete copyright setup (static + dynamic)"
	@echo "  make copyright-auto         - Automated setup with maintenance scheduling"
	@echo "  make copyright-report       - Generate detailed copyright report"

# =============================================================================
# Environment Setup
# =============================================================================

setup: ## Complete setup: generate .env, validate, and start services
	@echo "🚀 Setting up OpenDocMan..."
	@$(MAKE) env-generate
	@$(MAKE) env-validate
	@$(MAKE) up
	@echo "✅ Setup complete!"

env-generate: ## Generate .env file from template with secure passwords
	@echo "🔑 Generating environment configuration..."
	@./scripts/generate-env-secrets.sh

env-validate: ## Validate .env file configuration
	@echo "🔍 Validating environment configuration..."
	@./scripts/validate-env.sh

env-check: ## Check if .env file exists
	@if [ ! -f .env ]; then \
		echo "❌ .env file not found. Run 'make env-generate' first."; \
		exit 1; \
	fi

# =============================================================================
# Docker Operations
# =============================================================================

build: env-check ## Build Docker images
	@echo "🏗️  Building Docker images..."
	@$(DOCKER_COMPOSE) build

up: env-check ## Start services in background
	@echo "🚀 Starting OpenDocMan services..."
	@$(DOCKER_COMPOSE) up -d --build
	@echo "✅ Services started!"
	@$(MAKE) status

down: ## Stop and remove containers
	@echo "🛑 Stopping OpenDocMan services..."
	@$(DOCKER_COMPOSE) down
	@echo "✅ Services stopped!"

restart: ## Restart all services
	@echo "🔄 Restarting OpenDocMan services..."
	@$(DOCKER_COMPOSE) restart
	@$(MAKE) status

logs: ## View logs from all services
	@$(DOCKER_COMPOSE) logs -f

logs-app: ## View application logs only
	@$(DOCKER_COMPOSE) logs -f app

logs-db: ## View database logs only
	@$(DOCKER_COMPOSE) logs -f db

status: ## Show status of services
	@echo "📊 Service Status:"
	@$(DOCKER_COMPOSE) ps
	@echo ""
	@echo "🌐 Access URLs:"
	@echo "   HTTP:  http://localhost:$$(grep HTTP_PORT .env 2>/dev/null | cut -d'=' -f2 || echo '8080')"
	@echo "   HTTPS: https://localhost:$$(grep HTTPS_PORT .env 2>/dev/null | cut -d'=' -f2 || echo '443')"
	@echo ""
	@echo "🔧 Admin Credentials:"
	@echo "   Username: admin"
	@echo "   Password: $$(grep ADMIN_PASSWORD .env 2>/dev/null | cut -d'=' -f2 || echo '[check .env file]')"

# =============================================================================
# Testing Commands
# =============================================================================

test: ## Run all tests
	@echo "🧪 Running all tests..."
	@./scripts/run-tests.sh all

test-unit: ## Run only unit tests
	@echo "🧪 Running unit tests..."
	@./scripts/run-tests.sh unit

test-integration: ## Run only integration tests
	@echo "🧪 Running integration tests..."
	@./scripts/run-tests.sh integration

test-user: ## Run all user-related tests
	@echo "🧪 Running user tests..."
	@./scripts/run-user-tests.sh

test-department: ## Run all department-related tests
	@echo "🧪 Running department tests..."
	@./scripts/run-department-tests.sh

test-class: ## Run tests for specific class (usage: make test-class CLASS=User)
	@if [ -z "$(CLASS)" ]; then \
		echo "❌ Please specify CLASS. Example: make test-class CLASS=User"; \
		exit 1; \
	fi
	@echo "🧪 Running tests for class: $(CLASS)"
	@./scripts/run-tests.sh class $(CLASS)

test-file: ## Run specific test file (usage: make test-file FILE=CategoryTest)
	@if [ -z "$(FILE)" ]; then \
		echo "❌ Please specify FILE. Example: make test-file FILE=CategoryTest"; \
		exit 1; \
	fi
	@echo "🧪 Running test file: $(FILE)"
	@./scripts/run-tests.sh file $(FILE)

test-list: ## List all available test files
	@echo "📋 Available test files:"
	@./scripts/run-tests.sh list

test-quiet: ## Run all tests with minimal output
	@echo "🧪 Running tests (quiet mode)..."
	@./scripts/run-tests.sh quiet

test-watch: ## Watch files and run tests on changes (requires inotify-tools)
	@echo "👀 Watching for file changes..."
	@./scripts/run-tests.sh watch

test-install: ## Install test dependencies
	@echo "📦 Installing test dependencies..."
	@./scripts/run-tests.sh install

# =============================================================================
# Code Coverage
# =============================================================================

coverage: ## Generate text coverage report
	@echo "📊 Generating code coverage report..."
	@./scripts/run-coverage.sh text

coverage-html: ## Generate HTML coverage report
	@echo "📊 Generating HTML coverage report..."
	@./scripts/run-coverage.sh html

coverage-xml: ## Generate XML coverage report
	@echo "📊 Generating XML coverage report..."
	@./scripts/run-coverage.sh xml

coverage-all: ## Generate all coverage report formats
	@echo "📊 Generating all coverage formats..."
	@./scripts/run-coverage.sh all

# Legacy aliases for backward compatibility
test-coverage: coverage-html ## Alias for coverage-html

# =============================================================================
# Development Commands
# =============================================================================

dev: ## Start in development mode with live reload
	@echo "🧪 Starting in development mode..."
	@ODM_ENV=dev $(DOCKER_COMPOSE) up

shell: ## Open shell in running app container
	@$(DOCKER_COMPOSE) exec app /bin/bash

shell-db: ## Open MySQL shell in database container
	@$(DOCKER_COMPOSE) exec db mysql -u$$(grep MYSQL_USER .env | cut -d'=' -f2) -p$$(grep MYSQL_PASSWORD .env | cut -d'=' -f2) $$(grep MYSQL_DATABASE .env | cut -d'=' -f2)

serve-local: env-check ## Run local PHP server on /public with Docker DB service
	@echo "🚀 Starting local development environment..."
	@echo ""
	@echo "📦 Starting database service..."
	@$(DOCKER_COMPOSE) up -d db
	@echo ""
	@echo "⏳ Waiting for database to be ready..."
	@sleep 5
	@echo ""
	@echo "✅ Database service started!"
	@echo ""
	@echo "📊 Database Connection Info:"
	@echo "  Host:     127.0.0.1"
	@echo "  Port:     $$(grep DB_EXTERNAL_PORT .env 2>/dev/null | cut -d'=' -f2 || echo '3306')"
	@echo "  Database: $$(grep MYSQL_DATABASE .env 2>/dev/null | cut -d'=' -f2 || echo 'opendocman')"
	@echo "  Username: $$(grep MYSQL_USER .env 2>/dev/null | cut -d'=' -f2 || echo 'opendocman')"
	@echo "  Password: $$(grep MYSQL_PASSWORD .env 2>/dev/null | cut -d'=' -f2 || echo '[check .env file]')"
	@echo ""
	@echo "🌐 Starting PHP development server..."
	@echo "   URL: http://localhost:8000"
	@echo ""
	@echo "Press Ctrl+C to stop the server"
	@echo ""
	@export APP_DB_HOST="127.0.0.1;port=$$(grep DB_EXTERNAL_PORT .env 2>/dev/null | cut -d'=' -f2 || echo '3306')" && \
	export APP_DB_NAME="$$(grep MYSQL_DATABASE .env 2>/dev/null | cut -d'=' -f2 || echo 'opendocman')" && \
	export APP_DB_USER="$$(grep MYSQL_USER .env 2>/dev/null | cut -d'=' -f2 || echo 'opendocman')" && \
	export APP_DB_PASS="$$(grep MYSQL_PASSWORD .env 2>/dev/null | cut -d'=' -f2)" && \
	cd public && php -S localhost:8000

serve-local-quiet: env-check ## Run local PHP server with minimal logging (access logs hidden)
	@echo "🚀 Starting local development environment (quiet mode)..."
	@echo ""
	@echo "📦 Starting database service..."
	@$(DOCKER_COMPOSE) up -d db
	@echo ""
	@echo "⏳ Waiting for database to be ready..."
	@sleep 5
	@echo ""
	@echo "✅ Database service started!"
	@echo ""
	@echo "📊 Database Connection Info:"
	@echo "  Host:     127.0.0.1"
	@echo "  Port:     $$(grep DB_EXTERNAL_PORT .env 2>/dev/null | cut -d'=' -f2 || echo '3306')"
	@echo "  Database: $$(grep MYSQL_DATABASE .env 2>/dev/null | cut -d'=' -f2 || echo 'opendocman')"
	@echo "  Username: $$(grep MYSQL_USER .env 2>/dev/null | cut -d'=' -f2 || echo 'opendocman')"
	@echo "  Password: $$(grep MYSQL_PASSWORD .env 2>/dev/null | cut -d'=' -f2 || echo '[check .env file]')"
	@echo ""
	@echo "🌐 Starting PHP development server (logs redirected to php-server.log)..."
	@echo "   URL: http://localhost:8000"
	@echo ""
	@echo "💡 Tip: Run 'tail -f php-server.log' in another terminal to view logs"
	@echo ""
	@echo "Press Ctrl+C to stop the server"
	@echo ""
	@export APP_DB_HOST="127.0.0.1;port=$$(grep DB_EXTERNAL_PORT .env 2>/dev/null | cut -d'=' -f2 || echo '3306')" && \
	export APP_DB_NAME="$$(grep MYSQL_DATABASE .env 2>/dev/null | cut -d'=' -f2 || echo 'opendocman')" && \
	export APP_DB_USER="$$(grep MYSQL_USER .env 2>/dev/null | cut -d'=' -f2 || echo 'opendocman')" && \
	export APP_DB_PASS="$$(grep MYSQL_PASSWORD .env 2>/dev/null | cut -d'=' -f2)" && \
	export DISABLE_CSRF="true" && \
	cd public && php -d display_errors=Off -d error_reporting=0 -S localhost:8000 >> ../php-server.log 2>&1

serve-local-stop: ## Stop local PHP server and database service
	@echo "🛑 Stopping local development environment..."
	@$(DOCKER_COMPOSE) stop db
	@echo "✅ Database service stopped!"
	@echo "💡 Tip: Use 'make serve-local' to start again"

# =============================================================================
# Maintenance Commands
# =============================================================================

clean: ## Stop containers and remove volumes (DATA LOSS WARNING!)
	@echo "⚠️  WARNING: This will delete all data including uploaded files and database!"
	@read -p "Are you sure? Type 'yes' to continue: " confirm && [ "$$confirm" = "yes" ] || exit 1
	@$(DOCKER_COMPOSE) down -v
	@docker system prune -f
	@echo "🧹 Cleanup complete!"

rebuild: ## Rebuild and restart everything
	@echo "🔧 Rebuilding OpenDocMan..."
	@$(DOCKER_COMPOSE) down
	@$(DOCKER_COMPOSE) build --no-cache
	@$(DOCKER_COMPOSE) up -d
	@$(MAKE) status

# =============================================================================
# Installation and Updates
# =============================================================================

install: ## Fresh installation (removes existing data)
	@echo "📦 Fresh OpenDocMan installation..."
	@$(MAKE) down
	@$(MAKE) clean-volumes
	@$(MAKE) env-generate
	@$(MAKE) up

update: ## Update to latest version
	@echo "⬆️  Updating OpenDocMan..."
	@git pull
	@$(DOCKER_COMPOSE) pull
	@$(MAKE) rebuild

# =============================================================================
# Backup and Restore
# =============================================================================

backup: ## Create backup of database and files
	@echo "💾 Creating backup..."
	@mkdir -p backups
	@BACKUP_DATE=$$(date +%Y%m%d_%H%M%S) && \
	$(DOCKER_COMPOSE) exec -T db mysqldump -u$$(grep MYSQL_USER .env | cut -d'=' -f2) -p$$(grep MYSQL_PASSWORD .env | cut -d'=' -f2) $$(grep MYSQL_DATABASE .env | cut -d'=' -f2) > backups/db_$$BACKUP_DATE.sql && \
	docker run --rm -v opendocman_odm-files-data:/data -v $$(pwd)/backups:/backup alpine tar czf /backup/files_$$BACKUP_DATE.tar.gz -C /data . && \
	echo "✅ Backup created: backups/db_$$BACKUP_DATE.sql and backups/files_$$BACKUP_DATE.tar.gz"

restore-db: ## Restore database from backup file (specify BACKUP_FILE=filename)
	@if [ -z "$(BACKUP_FILE)" ]; then \
		echo "❌ Please specify BACKUP_FILE=filename"; \
		echo "Available backups:"; \
		ls -la backups/db_*.sql 2>/dev/null || echo "No database backups found"; \
		exit 1; \
	fi
	@echo "📥 Restoring database from $(BACKUP_FILE)..."
	@$(DOCKER_COMPOSE) exec -T db mysql -u$$(grep MYSQL_USER .env | cut -d'=' -f2) -p$$(grep MYSQL_PASSWORD .env | cut -d'=' -f2) $$(grep MYSQL_DATABASE .env | cut -d'=' -f2) < $(BACKUP_FILE)
	@echo "✅ Database restored!"

restore-files: ## Restore files from backup (specify BACKUP_FILE=filename)
	@if [ -z "$(BACKUP_FILE)" ]; then \
		echo "❌ Please specify BACKUP_FILE=filename"; \
		echo "Available backups:"; \
		ls -la backups/files_*.tar.gz 2>/dev/null || echo "No file backups found"; \
		exit 1; \
	fi
	@echo "📥 Restoring files from $(BACKUP_FILE)..."
	@docker run --rm -v opendocman_odm-files-data:/data -v $$(pwd)/backups:/backup alpine tar xzf /backup/$$(basename $(BACKUP_FILE)) -C /data
	@echo "✅ Files restored!"

# =============================================================================
# Utility Commands
# =============================================================================

clean-volumes: ## Remove all Docker volumes (DATA LOSS WARNING!)
	@echo "⚠️  WARNING: This will delete all data!"
	@read -p "Are you sure? Type 'yes' to continue: " confirm && [ "$$confirm" = "yes" ] || exit 1
	@docker volume rm opendocman_odm-files-data opendocman_odm-db-data opendocman_odm-docker-configs 2>/dev/null || true
	@echo "🧹 Volumes removed!"

ps: ## Show running containers
	@$(DOCKER_COMPOSE) ps

top: ## Show running processes in containers
	@$(DOCKER_COMPOSE) top

stats: ## Show container resource usage
	@docker stats $$($(DOCKER_COMPOSE) ps -q)

# =============================================================================
# Security Commands
# =============================================================================

security-scan: ## Run security scan on containers
	@echo "🔒 Running security scan..."
	@docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
		-v $$(pwd):/src aquasec/trivy image --exit-code 0 --severity HIGH,CRITICAL \
		opendocman_app:latest || echo "Security scan completed with findings"

# =============================================================================
# Information Commands
# =============================================================================

version: ## Show version information
	@echo "OpenDocMan Docker Environment"
	@echo "============================="
	@echo "Docker version:"
	@docker --version
	@echo "Docker Compose version:"
	@$(DOCKER_COMPOSE) version
	@echo ""
	@if [ -f .env ]; then \
		echo "Current configuration:"; \
		echo "  Database: $$(grep MYSQL_DATABASE .env | cut -d'=' -f2)"; \
		echo "  HTTP Port: $$(grep HTTP_PORT .env | cut -d'=' -f2)"; \
		echo "  Hostname: $$(grep ODM_HOSTNAME .env | cut -d'=' -f2)"; \
	else \
		echo "No .env file found. Run 'make env-generate' first."; \
	fi

docker-compose-version: ## Show which Docker Compose command is being used
	@echo "Docker Compose Command Detection:"
	@echo "================================="
	@echo "Using: $(DOCKER_COMPOSE)"
	@echo ""
	@if echo "$(DOCKER_COMPOSE)" | grep -q "docker-compose"; then \
		echo "✅ Using legacy docker-compose command"; \
	elif echo "$(DOCKER_COMPOSE)" | grep -q "docker compose"; then \
		echo "✅ Using modern docker compose command"; \
	else \
		echo "❌ Docker Compose command detection failed"; \
	fi
	@echo ""
	@echo "Version information:"
	@$(DOCKER_COMPOSE) version

config: ## Show current Docker Compose configuration
	@$(DOCKER_COMPOSE) config

# =============================================================================
# Copyright Management Commands
# =============================================================================

copyright-check: ## Check current copyright status
	@echo "🔍 Checking copyright status..."
	@./scripts/copyright-maintenance.sh check

copyright-update: ## Update static copyright years to 2025
	@echo "📅 Updating static copyright years to 2025..."
	@./scripts/copyright-maintenance.sh update-static

copyright-dynamic: ## Set up automatic yearly copyright updates  
	@echo "🔄 Setting up dynamic copyright system..."
	@./scripts/copyright-maintenance.sh update-dynamic

copyright-all: ## Complete copyright setup (static + dynamic)
	@echo "🎯 Setting up complete copyright management..."
	@./scripts/copyright-maintenance.sh update-all

copyright-auto: ## Automated setup with maintenance scheduling
	@echo "🤖 Running automated copyright setup..."
	@./scripts/copyright-maintenance.sh auto-setup

copyright-report: ## Generate detailed copyright report
	@echo "📊 Generating copyright report..."
	@./scripts/copyright-maintenance.sh report

# =============================================================================
# Quick Aliases
# =============================================================================

start: up ## Alias for 'up'
stop: down ## Alias for 'down'
reset: ## Reset everything (clean + setup)
	@$(MAKE) clean
	@$(MAKE) setup

# =============================================================================
# Script Management
# =============================================================================

scripts-help: ## Show help for individual scripts
	@echo "📋 Individual Script Help:"
	@echo "=========================="
	@echo ""
	@echo "Testing Scripts:"
	@echo "  ./scripts/run-tests.sh help        - Main test runner help"
	@echo "  ./scripts/run-coverage.sh help     - Coverage script help"
	@echo ""
	@echo "Environment Scripts:"
	@echo "  ./scripts/generate-env-secrets.sh  - Generate .env file"
	@echo "  ./scripts/validate-env.sh          - Validate .env file"
	@echo ""
	@echo "Component-specific Scripts:"
	@echo "  ./scripts/run-user-tests.sh        - User component tests"
	@echo "  ./scripts/run-department-tests.sh  - Department component tests"

# Default environment
export COMPOSE_PROJECT_NAME ?= opendocman

# Include local overrides if they exist
-include Makefile.local
