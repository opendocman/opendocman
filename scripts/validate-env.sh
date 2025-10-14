#!/bin/bash

# OpenDocMan Environment Validation Script
# This script validates your .env file configuration

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Validation status
VALIDATION_PASSED=true
WARNING_COUNT=0
ERROR_COUNT=0

echo -e "${BLUE}OpenDocMan Environment Validation${NC}"
echo -e "${BLUE}=================================${NC}"
echo ""

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ Error: .env file not found!${NC}"
    echo "Please create a .env file from .env.sample or run ./generate-env-secrets.sh"
    exit 1
fi

echo -e "${GREEN}✓ .env file found${NC}"

# Source the .env file
set -a
source .env
set +a

echo ""
echo -e "${BLUE}Validating configuration...${NC}"
echo ""

# Function to check if a variable is set and not empty
check_required() {
    local var_name=$1
    local var_value=$2
    local description=$3
    
    if [ -z "$var_value" ]; then
        echo -e "${RED}❌ Error: $var_name is not set or empty${NC}"
        echo "   Description: $description"
        VALIDATION_PASSED=false
        ERROR_COUNT=$((ERROR_COUNT + 1))
        return 1
    else
        echo -e "${GREEN}✓ $var_name is set${NC}"
        return 0
    fi
}

# Function to check for default/insecure values
check_secure() {
    local var_name=$1
    local var_value=$2
    local insecure_values=("$@")
    
    for insecure in "${insecure_values[@]:2}"; do
        if [ "$var_value" = "$insecure" ]; then
            echo -e "${YELLOW}⚠️  Warning: $var_name is using a default/insecure value: $insecure${NC}"
            WARNING_COUNT=$((WARNING_COUNT + 1))
            return 1
        fi
    done
    return 0
}

# Function to check password strength
check_password_strength() {
    local var_name=$1
    local password=$2
    local min_length=${3:-8}
    
    if [ ${#password} -lt $min_length ]; then
        echo -e "${YELLOW}⚠️  Warning: $var_name is shorter than recommended ($min_length characters)${NC}"
        WARNING_COUNT=$((WARNING_COUNT + 1))
        return 1
    fi
    return 0
}

# Function to check port availability
check_port() {
    local var_name=$1
    local port=$2
    
    if [ -z "$port" ]; then
        return 0
    fi
    
    if ! [[ "$port" =~ ^[0-9]+$ ]] || [ "$port" -lt 1 ] || [ "$port" -gt 65535 ]; then
        echo -e "${RED}❌ Error: $var_name ($port) is not a valid port number${NC}"
        VALIDATION_PASSED=false
        ERROR_COUNT=$((ERROR_COUNT + 1))
        return 1
    fi
    
    # Check if port is in use (Linux/macOS)
    if command -v netstat >/dev/null 2>&1; then
        if netstat -tuln 2>/dev/null | grep -q ":$port "; then
            echo -e "${YELLOW}⚠️  Warning: Port $port ($var_name) appears to be in use${NC}"
            WARNING_COUNT=$((WARNING_COUNT + 1))
        fi
    fi
    return 0
}

# Validate required database settings
echo -e "${BLUE}Database Configuration:${NC}"
check_required "MYSQL_DATABASE" "$MYSQL_DATABASE" "MySQL database name"
check_required "MYSQL_USER" "$MYSQL_USER" "MySQL database user"
check_required "MYSQL_PASSWORD" "$MYSQL_PASSWORD" "MySQL database password"
check_required "MYSQL_ROOT_PASSWORD" "$MYSQL_ROOT_PASSWORD" "MySQL root password"

# Check for insecure database passwords
check_secure "MYSQL_PASSWORD" "$MYSQL_PASSWORD" "changeme_db_password" "password" "admin" "opendocman"
check_secure "MYSQL_ROOT_PASSWORD" "$MYSQL_ROOT_PASSWORD" "changeme_root_password" "password" "admin" "root"

# Check password strength
if [ -n "$MYSQL_PASSWORD" ]; then
    check_password_strength "MYSQL_PASSWORD" "$MYSQL_PASSWORD" 12
fi
if [ -n "$MYSQL_ROOT_PASSWORD" ]; then
    check_password_strength "MYSQL_ROOT_PASSWORD" "$MYSQL_ROOT_PASSWORD" 12
fi

echo ""

# Validate application database settings
echo -e "${BLUE}Application Database Configuration:${NC}"
check_required "APP_DB_HOST" "$APP_DB_HOST" "Application database host"
check_required "APP_DB_NAME" "$APP_DB_NAME" "Application database name"
check_required "APP_DB_USER" "$APP_DB_USER" "Application database user"
check_required "APP_DB_PASS" "$APP_DB_PASS" "Application database password"

# Check consistency between database settings
if [ "$MYSQL_DATABASE" != "$APP_DB_NAME" ]; then
    echo -e "${YELLOW}⚠️  Warning: MYSQL_DATABASE and APP_DB_NAME don't match${NC}"
    WARNING_COUNT=$((WARNING_COUNT + 1))
fi

if [ "$MYSQL_USER" != "$APP_DB_USER" ]; then
    echo -e "${YELLOW}⚠️  Warning: MYSQL_USER and APP_DB_USER don't match${NC}"
    WARNING_COUNT=$((WARNING_COUNT + 1))
fi

if [ "$MYSQL_PASSWORD" != "$APP_DB_PASS" ]; then
    echo -e "${RED}❌ Error: MYSQL_PASSWORD and APP_DB_PASS must match${NC}"
    VALIDATION_PASSED=false
    ERROR_COUNT=$((ERROR_COUNT + 1))
fi

echo ""

# Validate security settings
echo -e "${BLUE}Security Configuration:${NC}"
check_required "ADMIN_PASSWORD" "$ADMIN_PASSWORD" "Initial admin password"
check_required "SESSION_SECRET" "$SESSION_SECRET" "Session encryption secret"

check_secure "ADMIN_PASSWORD" "$ADMIN_PASSWORD" "changeme_admin_password" "password" "admin"
check_secure "SESSION_SECRET" "$SESSION_SECRET" "changeme_session_secret_key"

if [ -n "$ADMIN_PASSWORD" ]; then
    check_password_strength "ADMIN_PASSWORD" "$ADMIN_PASSWORD" 8
fi

if [ -n "$SESSION_SECRET" ] && [ ${#SESSION_SECRET} -lt 32 ]; then
    echo -e "${YELLOW}⚠️  Warning: SESSION_SECRET should be at least 32 characters long${NC}"
    WARNING_COUNT=$((WARNING_COUNT + 1))
fi

echo ""

# Validate port configuration
echo -e "${BLUE}Port Configuration:${NC}"
check_port "HTTP_PORT" "$HTTP_PORT"
check_port "HTTPS_PORT" "$HTTPS_PORT"
check_port "DB_EXTERNAL_PORT" "$DB_EXTERNAL_PORT"
check_port "PHP_FPM_PORT" "$PHP_FPM_PORT"

echo ""

# Validate email configuration
echo -e "${BLUE}Email Configuration:${NC}"
if [ -n "$MAIL_FROM_ADDRESS" ]; then
    if [[ "$MAIL_FROM_ADDRESS" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
        echo -e "${GREEN}✓ MAIL_FROM_ADDRESS format looks valid${NC}"
    else
        echo -e "${YELLOW}⚠️  Warning: MAIL_FROM_ADDRESS doesn't look like a valid email${NC}"
        WARNING_COUNT=$((WARNING_COUNT + 1))
    fi
    
    if [[ "$MAIL_FROM_ADDRESS" == *"yourdomain.com"* ]]; then
        echo -e "${YELLOW}⚠️  Warning: MAIL_FROM_ADDRESS is using default domain${NC}"
        WARNING_COUNT=$((WARNING_COUNT + 1))
    fi
fi

echo ""

# Validate file paths and permissions
echo -e "${BLUE}File System Configuration:${NC}"
if [ -n "$HOST_DATA_PATH" ] && [ "$HOST_DATA_PATH" != "" ]; then
    if [ ! -d "$HOST_DATA_PATH" ]; then
        echo -e "${YELLOW}⚠️  Warning: HOST_DATA_PATH directory doesn't exist: $HOST_DATA_PATH${NC}"
        WARNING_COUNT=$((WARNING_COUNT + 1))
    elif [ ! -w "$HOST_DATA_PATH" ]; then
        echo -e "${YELLOW}⚠️  Warning: HOST_DATA_PATH is not writable: $HOST_DATA_PATH${NC}"
        WARNING_COUNT=$((WARNING_COUNT + 1))
    else
        echo -e "${GREEN}✓ HOST_DATA_PATH is accessible${NC}"
    fi
fi

if [ -n "$HOST_DB_PATH" ] && [ "$HOST_DB_PATH" != "" ]; then
    if [ ! -d "$HOST_DB_PATH" ]; then
        echo -e "${YELLOW}⚠️  Warning: HOST_DB_PATH directory doesn't exist: $HOST_DB_PATH${NC}"
        WARNING_COUNT=$((WARNING_COUNT + 1))
    elif [ ! -w "$HOST_DB_PATH" ]; then
        echo -e "${YELLOW}⚠️  Warning: HOST_DB_PATH is not writable: $HOST_DB_PATH${NC}"
        WARNING_COUNT=$((WARNING_COUNT + 1))
    else
        echo -e "${GREEN}✓ HOST_DB_PATH is accessible${NC}"
    fi
fi

echo ""

# Check for Docker and docker-compose
echo -e "${BLUE}Docker Environment:${NC}"
if command -v docker >/dev/null 2>&1; then
    echo -e "${GREEN}✓ Docker is installed${NC}"
    if ! docker info >/dev/null 2>&1; then
        echo -e "${YELLOW}⚠️  Warning: Docker daemon is not running${NC}"
        WARNING_COUNT=$((WARNING_COUNT + 1))
    fi
else
    echo -e "${RED}❌ Error: Docker is not installed${NC}"
    VALIDATION_PASSED=false
    ERROR_COUNT=$((ERROR_COUNT + 1))
fi

if command -v docker-compose >/dev/null 2>&1; then
    echo -e "${GREEN}✓ docker-compose is installed${NC}"
elif command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    echo -e "${GREEN}✓ docker compose (plugin) is available${NC}"
else
    echo -e "${RED}❌ Error: docker-compose is not installed${NC}"
    VALIDATION_PASSED=false
    ERROR_COUNT=$((ERROR_COUNT + 1))
fi

echo ""

# Summary
echo -e "${BLUE}Validation Summary:${NC}"
echo -e "${BLUE}==================${NC}"

if [ $VALIDATION_PASSED = true ]; then
    echo -e "${GREEN}✅ All critical validations passed!${NC}"
else
    echo -e "${RED}❌ Validation failed with $ERROR_COUNT error(s)${NC}"
fi

if [ $WARNING_COUNT -gt 0 ]; then
    echo -e "${YELLOW}⚠️  $WARNING_COUNT warning(s) found${NC}"
fi

echo ""

if [ $VALIDATION_PASSED = true ]; then
    echo -e "${GREEN}Your .env configuration looks good!${NC}"
    echo -e "${BLUE}You can now run: ${GREEN}docker-compose up -d --build${NC}"
    echo ""
    echo -e "${BLUE}Access your application at:${NC}"
    echo -e "• HTTP: ${GREEN}http://localhost:${HTTP_PORT:-8080}${NC}"
    if [ "$HTTPS_PORT" != "443" ]; then
        echo -e "• HTTPS: ${GREEN}https://localhost:${HTTPS_PORT:-443}${NC}"
    else
        echo -e "• HTTPS: ${GREEN}https://localhost${NC}"
    fi
    echo -e "• Admin login: ${GREEN}admin${NC} / ${GREEN}[your ADMIN_PASSWORD]${NC}"
    
    exit 0
else
    echo -e "${RED}Please fix the errors above before proceeding.${NC}"
    echo ""
    echo -e "${BLUE}Need help?${NC}"
    echo -e "• Run ${GREEN}./generate-env-secrets.sh${NC} to regenerate configuration"
    echo -e "• Check the .env.sample file for reference"
    echo -e "• Refer to the README.md for detailed instructions"
    
    exit 1
fi