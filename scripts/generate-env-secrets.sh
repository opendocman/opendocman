#!/bin/bash

# OpenDocMan Environment Secrets Generator
# This script generates secure passwords and secrets for your .env file

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to generate a random password
generate_password() {
    local length=${1:-32}
    openssl rand -base64 $length | tr -d "=+/" | cut -c1-$length
}

# Function to generate a random hex string
generate_hex() {
    local length=${1:-32}
    openssl rand -hex $((length/2))
}

echo -e "${BLUE}OpenDocMan Environment Secrets Generator${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""

# Check if .env already exists
if [ -f ".env" ]; then
    echo -e "${YELLOW}Warning: .env file already exists!${NC}"
    read -p "Do you want to backup the existing .env file? (y/n): " backup_choice
    if [[ $backup_choice =~ ^[Yy]$ ]]; then
        cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
        echo -e "${GREEN}Backup created as .env.backup.$(date +%Y%m%d_%H%M%S)${NC}"
    fi
    echo ""
fi

# Check if .env.sample exists
if [ ! -f ".env.sample" ]; then
    echo -e "${RED}Error: .env.sample file not found!${NC}"
    echo "Please make sure you're running this script from the OpenDocMan root directory."
    exit 1
fi

echo -e "${BLUE}Generating secure passwords and secrets...${NC}"
echo ""

# Generate passwords and secrets
DB_PASSWORD=$(generate_password 24)
ROOT_PASSWORD=$(generate_password 32)
ADMIN_PASSWORD=$(generate_password 16)
SESSION_SECRET=$(generate_hex 64)

# Get user input for basic configuration
echo -e "${YELLOW}Please provide some basic configuration:${NC}"
echo ""

read -p "Database name [opendocman]: " db_name
db_name=${db_name:-opendocman}

read -p "Database username [opendocman]: " db_user
db_user=${db_user:-opendocman}

read -p "Application hostname [odm.local]: " hostname
hostname=${hostname:-odm.local}

read -p "HTTP port [8080]: " http_port
http_port=${http_port:-8080}

read -p "HTTPS port [443]: " https_port
https_port=${https_port:-443}

read -p "Database external port [3306]: " db_port
db_port=${db_port:-3306}

read -p "Email address for notifications [admin@$hostname]: " email
email=${email:-admin@$hostname}

read -p "Timezone [UTC]: " timezone
timezone=${timezone:-UTC}

echo ""
read -p "Do you want to use custom admin password? (y/n) [n]: " custom_admin
if [[ $custom_admin =~ ^[Yy]$ ]]; then
    while true; do
        read -s -p "Enter admin password (min 8 characters): " user_admin_password
        echo ""
        if [ ${#user_admin_password} -ge 8 ]; then
            read -s -p "Confirm admin password: " confirm_password
            echo ""
            if [ "$user_admin_password" = "$confirm_password" ]; then
                ADMIN_PASSWORD="$user_admin_password"
                break
            else
                echo -e "${RED}Passwords do not match. Please try again.${NC}"
            fi
        else
            echo -e "${RED}Password must be at least 8 characters. Please try again.${NC}"
        fi
    done
fi

# Copy .env.sample to .env and replace variables
cp .env.sample .env

# Use sed to replace the values (compatible with both Linux and macOS)
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s/MYSQL_DATABASE=opendocman/MYSQL_DATABASE=$db_name/" .env
    sed -i '' "s/MYSQL_USER=opendocman/MYSQL_USER=$db_user/" .env
    sed -i '' "s/MYSQL_PASSWORD=changeme_db_password/MYSQL_PASSWORD=$DB_PASSWORD/" .env
    sed -i '' "s/MYSQL_ROOT_PASSWORD=changeme_root_password/MYSQL_ROOT_PASSWORD=$ROOT_PASSWORD/" .env
    sed -i '' "s/APP_DB_NAME=opendocman/APP_DB_NAME=$db_name/" .env
    sed -i '' "s/APP_DB_USER=opendocman/APP_DB_USER=$db_user/" .env
    sed -i '' "s/APP_DB_PASS=changeme_db_password/APP_DB_PASS=$DB_PASSWORD/" .env
    sed -i '' "s/ODM_HOSTNAME=odm.local/ODM_HOSTNAME=$hostname/" .env
    sed -i '' "s/DB_EXTERNAL_PORT=3306/DB_EXTERNAL_PORT=$db_port/" .env
    sed -i '' "s/HTTP_PORT=8080/HTTP_PORT=$http_port/" .env
    sed -i '' "s/HTTPS_PORT=443/HTTPS_PORT=$https_port/" .env
    sed -i '' "s/ADMIN_PASSWORD=changeme_admin_password/ADMIN_PASSWORD=$ADMIN_PASSWORD/" .env
    sed -i '' "s/SESSION_SECRET=changeme_session_secret_key/SESSION_SECRET=$SESSION_SECRET/" .env
    sed -i '' "s/MAIL_FROM_ADDRESS=noreply@yourdomain.com/MAIL_FROM_ADDRESS=$email/" .env
    sed -i '' "s/TZ=UTC/TZ=$timezone/" .env
else
    # Linux
    sed -i "s/MYSQL_DATABASE=opendocman/MYSQL_DATABASE=$db_name/" .env
    sed -i "s/MYSQL_USER=opendocman/MYSQL_USER=$db_user/" .env
    sed -i "s/MYSQL_PASSWORD=changeme_db_password/MYSQL_PASSWORD=$DB_PASSWORD/" .env
    sed -i "s/MYSQL_ROOT_PASSWORD=changeme_root_password/MYSQL_ROOT_PASSWORD=$ROOT_PASSWORD/" .env
    sed -i "s/APP_DB_NAME=opendocman/APP_DB_NAME=$db_name/" .env
    sed -i "s/APP_DB_USER=opendocman/APP_DB_USER=$db_user/" .env
    sed -i "s/APP_DB_PASS=changeme_db_password/APP_DB_PASS=$DB_PASSWORD/" .env
    sed -i "s/ODM_HOSTNAME=odm.local/ODM_HOSTNAME=$hostname/" .env
    sed -i "s/DB_EXTERNAL_PORT=3306/DB_EXTERNAL_PORT=$db_port/" .env
    sed -i "s/HTTP_PORT=8080/HTTP_PORT=$http_port/" .env
    sed -i "s/HTTPS_PORT=443/HTTPS_PORT=$https_port/" .env
    sed -i "s/ADMIN_PASSWORD=changeme_admin_password/ADMIN_PASSWORD=$ADMIN_PASSWORD/" .env
    sed -i "s/SESSION_SECRET=changeme_session_secret_key/SESSION_SECRET=$SESSION_SECRET/" .env
    sed -i "s/MAIL_FROM_ADDRESS=noreply@yourdomain.com/MAIL_FROM_ADDRESS=$email/" .env
    sed -i "s/TZ=UTC/TZ=$timezone/" .env
fi

echo ""
echo -e "${GREEN}✓ .env file created successfully!${NC}"
echo ""
echo -e "${BLUE}Generated Configuration Summary:${NC}"
echo -e "${BLUE}===============================${NC}"
echo -e "Database Name: ${GREEN}$db_name${NC}"
echo -e "Database User: ${GREEN}$db_user${NC}"
echo -e "Database Password: ${GREEN}[Generated - 24 chars]${NC}"
echo -e "Root Password: ${GREEN}[Generated - 32 chars]${NC}"
echo -e "Admin Password: ${GREEN}[Generated/Custom - ${#ADMIN_PASSWORD} chars]${NC}"
echo -e "Session Secret: ${GREEN}[Generated - 64 chars]${NC}"
echo -e "Hostname: ${GREEN}$hostname${NC}"
echo -e "HTTP Port: ${GREEN}$http_port${NC}"
echo -e "HTTPS Port: ${GREEN}$https_port${NC}"
echo -e "Database Port: ${GREEN}$db_port${NC}"
echo -e "Email: ${GREEN}$email${NC}"
echo -e "Timezone: ${GREEN}$timezone${NC}"
echo ""
echo -e "${YELLOW}Important Notes:${NC}"
echo -e "• Your .env file contains sensitive information - do not commit it to version control"
echo -e "• Make sure to add .env to your .gitignore file"
echo -e "• Keep a secure backup of your .env file"
echo -e "• The admin username will be 'admin' with the generated/custom password"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo -e "1. Review and customize additional settings in .env if needed"
echo -e "2. Run: ${GREEN}docker-compose up -d --build${NC}"
echo -e "3. Access your application at: ${GREEN}http://localhost:$http_port${NC}"
echo -e "4. Login with username: ${GREEN}admin${NC} and your generated password"
echo ""
echo -e "${GREEN}Setup complete! 🚀${NC}"