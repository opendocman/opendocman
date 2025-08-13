# OpenDocMan Docker Management Makefile
# Provides convenient commands for development and deployment

.PHONY: help setup env-generate env-validate build up down restart logs clean rebuild install status backup restore

# Default target
help: ## Show this help message
	@echo "OpenDocMan Docker Management Commands"
	@echo "===================================="
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Environment Variables:"
	@echo "  COMPOSE_PROJECT_NAME  - Set custom project name (default: opendocman)"
	@echo "  COMPOSE_FILE          - Override docker-compose file"
	@echo "  ODM_ENV               - Set environment (dev, prod, test)"

# Environment setup
setup: ## Complete setup: generate .env, validate, and start services
	@echo "🚀 Setting up OpenDocMan..."
	@$(MAKE) env-generate
	@$(MAKE) env-validate
	@$(MAKE) up
	@echo "✅ Setup complete!"

env-generate: ## Generate .env file from template with secure passwords
	@echo "🔑 Generating environment configuration..."
	@./generate-env-secrets.sh

env-validate: ## Validate .env file configuration
	@echo "🔍 Validating environment configuration..."
	@./validate-env.sh

env-check: ## Check if .env file exists
	@if [ ! -f .env ]; then \
		echo "❌ .env file not found. Run 'make env-generate' first."; \
		exit 1; \
	fi

# Docker operations
build: env-check ## Build Docker images
	@echo "🏗️  Building Docker images..."
	@docker-compose build

up: env-check ## Start services in background
	@echo "🚀 Starting OpenDocMan services..."
	@docker-compose up -d
	@echo "✅ Services started!"
	@$(MAKE) status

down: ## Stop and remove containers
	@echo "🛑 Stopping OpenDocMan services..."
	@docker-compose down
	@echo "✅ Services stopped!"

restart: ## Restart all services
	@echo "🔄 Restarting OpenDocMan services..."
	@docker-compose restart
	@$(MAKE) status

logs: ## View logs from all services
	@docker-compose logs -f

logs-app: ## View application logs only
	@docker-compose logs -f app

logs-db: ## View database logs only
	@docker-compose logs -f db

status: ## Show status of services
	@echo "📊 Service Status:"
	@docker-compose ps
	@echo ""
	@echo "🌐 Access URLs:"
	@echo "   HTTP:  http://localhost:$$(grep HTTP_PORT .env 2>/dev/null | cut -d'=' -f2 || echo '8080')"
	@echo "   HTTPS: https://localhost:$$(grep HTTPS_PORT .env 2>/dev/null | cut -d'=' -f2 || echo '443')"
	@echo ""
	@echo "🔧 Admin Credentials:"
	@echo "   Username: admin"
	@echo "   Password: $$(grep ADMIN_PASSWORD .env 2>/dev/null | cut -d'=' -f2 || echo '[check .env file]')"

# Development commands
dev: ## Start in development mode with live reload
	@echo "🧪 Starting in development mode..."
	@ODM_ENV=dev docker-compose up

shell: ## Open shell in running app container
	@docker-compose exec app /bin/bash

shell-db: ## Open MySQL shell in database container
	@docker-compose exec db mysql -u$$(grep MYSQL_USER .env | cut -d'=' -f2) -p$$(grep MYSQL_PASSWORD .env | cut -d'=' -f2) $$(grep MYSQL_DATABASE .env | cut -d'=' -f2)

# Maintenance commands
clean: ## Stop containers and remove volumes (DATA LOSS WARNING!)
	@echo "⚠️  WARNING: This will delete all data including uploaded files and database!"
	@read -p "Are you sure? Type 'yes' to continue: " confirm && [ "$$confirm" = "yes" ] || exit 1
	@docker-compose down -v
	@docker system prune -f
	@echo "🧹 Cleanup complete!"

rebuild: ## Rebuild and restart everything
	@echo "🔧 Rebuilding OpenDocMan..."
	@docker-compose down
	@docker-compose build --no-cache
	@docker-compose up -d
	@$(MAKE) status

# Installation and updates
install: ## Fresh installation (removes existing data)
	@echo "📦 Fresh OpenDocMan installation..."
	@$(MAKE) down
	@$(MAKE) clean-volumes
	@$(MAKE) env-generate
	@$(MAKE) up

update: ## Update to latest version
	@echo "⬆️  Updating OpenDocMan..."
	@git pull
	@docker-compose pull
	@$(MAKE) rebuild

# Backup and restore
backup: ## Create backup of database and files
	@echo "💾 Creating backup..."
	@mkdir -p backups
	@BACKUP_DATE=$$(date +%Y%m%d_%H%M%S) && \
	docker-compose exec -T db mysqldump -u$$(grep MYSQL_USER .env | cut -d'=' -f2) -p$$(grep MYSQL_PASSWORD .env | cut -d'=' -f2) $$(grep MYSQL_DATABASE .env | cut -d'=' -f2) > backups/db_$$BACKUP_DATE.sql && \
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
	@docker-compose exec -T db mysql -u$$(grep MYSQL_USER .env | cut -d'=' -f2) -p$$(grep MYSQL_PASSWORD .env | cut -d'=' -f2) $$(grep MYSQL_DATABASE .env | cut -d'=' -f2) < $(BACKUP_FILE)
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

# Utility commands
clean-volumes: ## Remove all Docker volumes (DATA LOSS WARNING!)
	@echo "⚠️  WARNING: This will delete all data!"
	@read -p "Are you sure? Type 'yes' to continue: " confirm && [ "$$confirm" = "yes" ] || exit 1
	@docker volume rm opendocman_odm-files-data opendocman_odm-db-data opendocman_odm-docker-configs 2>/dev/null || true
	@echo "🧹 Volumes removed!"

ps: ## Show running containers
	@docker-compose ps

top: ## Show running processes in containers
	@docker-compose top

stats: ## Show container resource usage
	@docker stats $$(docker-compose ps -q)

# Testing commands
test: ## Run tests
	@echo "🧪 Running tests..."
	@docker-compose exec app php vendor/bin/phpunit

test-coverage: ## Run tests with coverage report
	@echo "🧪 Running tests with coverage..."
	@docker-compose exec app php vendor/bin/phpunit --coverage-html coverage-report

# Security commands
security-scan: ## Run security scan on containers
	@echo "🔒 Running security scan..."
	@docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
		-v $$(pwd):/src aquasec/trivy image --exit-code 0 --severity HIGH,CRITICAL \
		opendocman_app:latest || echo "Security scan completed with findings"

# Quick commands
start: up ## Alias for 'up'
stop: down ## Alias for 'down'
reset: ## Reset everything (clean + setup)
	@$(MAKE) clean
	@$(MAKE) setup

# Information commands
version: ## Show version information
	@echo "OpenDocMan Docker Environment"
	@echo "============================="
	@echo "Docker version:"
	@docker --version
	@echo "Docker Compose version:"
	@docker-compose --version || docker compose version
	@echo ""
	@if [ -f .env ]; then \
		echo "Current configuration:"; \
		echo "  Database: $$(grep MYSQL_DATABASE .env | cut -d'=' -f2)"; \
		echo "  HTTP Port: $$(grep HTTP_PORT .env | cut -d'=' -f2)"; \
		echo "  Hostname: $$(grep ODM_HOSTNAME .env | cut -d'=' -f2)"; \
	else \
		echo "No .env file found. Run 'make env-generate' first."; \
	fi

config: ## Show current Docker Compose configuration
	@docker-compose config

# Default environment
export COMPOSE_PROJECT_NAME ?= opendocman

# Include local overrides if they exist
-include Makefile.local