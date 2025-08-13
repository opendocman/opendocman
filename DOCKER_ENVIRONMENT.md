# OpenDocMan Docker Environment Setup

This document provides comprehensive information about the Docker environment features for OpenDocMan, including automatic admin password pre-population and advanced environment management.

## 🎯 Overview

OpenDocMan now includes a complete Docker environment management system that:
- **Automatically pre-populates** installation forms from environment variables
- **Generates secure passwords** and secrets automatically
- **Validates configuration** with detailed diagnostics
- **Provides management tools** for deployment and maintenance

## 🚀 Quick Start

### 1. Generate Environment Configuration
```bash
./generate-env-secrets.sh
```
This interactive script will:
- Generate secure passwords for database and admin user
- Create a `.env` file from the template
- Prompt for basic configuration (hostname, ports, email)
- Validate your setup automatically

### 2. Start the Application
```bash
make up
# OR
docker-compose up -d --build
```

### 3. Access Installation
- Navigate to `http://localhost:8080/install/setup-config.php`
- **Admin password will be pre-populated** from your `.env` file
- All database settings will be automatically filled
- Click through the installation process

## 🔧 Environment Management Tools

### Core Scripts

| Script | Purpose | Usage |
|--------|---------|-------|
| `generate-env-secrets.sh` | Interactive setup with secure password generation | `./generate-env-secrets.sh` |
| `validate-env.sh` | Validate `.env` configuration and security | `./validate-env.sh` |
| `Makefile` | 25+ management commands | `make help` |

### Makefile Commands

#### Setup & Management
```bash
make setup      # Complete setup: generate .env + validate + start
make up         # Start services with validation
make down       # Stop services
make restart    # Restart all services
make status     # Show service status and access URLs
```

#### Development & Debugging
```bash
make logs       # View all service logs
make logs-app   # View application logs only
make logs-db    # View database logs only
make shell      # Open shell in app container
make shell-db   # Open MySQL shell
```

#### Maintenance
```bash
make backup     # Create backup of database and files
make clean      # Remove all data (WARNING: destructive)
make rebuild    # Full rebuild of containers
make update     # Update to latest version
```

## 📋 Installation Form Auto-Population

When running in Docker, the installation form (`/install/setup-config.php`) automatically pre-populates:

### Database Configuration
- **Database Name**: From `APP_DB_NAME` (default: opendocman)
- **Username**: From `APP_DB_USER` (default: opendocman)
- **Password**: From `APP_DB_PASS` (matches `MYSQL_PASSWORD`)
- **Database Host**: From `APP_DB_HOST` (default: db)

### Application Configuration
- **Admin Password**: From `ADMIN_PASSWORD` ⭐ **KEY FEATURE**
- **Table Prefix**: From `DB_PREFIX` (default: odm_)
- **Data Directory**: From `ODM_DATA_DIR`

### Visual Indicators
- Docker environment detection banner
- "Pre-filled from environment" labels
- Show/hide password toggle for pre-populated admin password
- Link to environment diagnostics for troubleshooting

## 🔒 Security Features

### Automatic Password Generation
- **Database passwords**: 24-character secure strings
- **Root password**: 32-character secure strings
- **Admin password**: 16-character secure strings (or custom)
- **Session secret**: 64-character hex strings

### Security Validation
- Password strength checking (minimum lengths)
- Detection of default/insecure values
- Warnings for weak configurations
- Automatic masking of sensitive values in diagnostics

### Best Practices
- `.env` file excluded from version control
- Automatic backup creation with timestamps
- Environment variable validation before startup
- Secure defaults for all configuration options

## 📊 Environment Configuration

### Required Variables
```bash
# Database Configuration
MYSQL_DATABASE=opendocman
MYSQL_USER=opendocman
MYSQL_PASSWORD=your_secure_password
MYSQL_ROOT_PASSWORD=your_root_password

# Application Database (must match MySQL settings)
APP_DB_HOST=db
APP_DB_NAME=opendocman
APP_DB_USER=opendocman
APP_DB_PASS=your_secure_password

# Security
ADMIN_PASSWORD=your_admin_password
SESSION_SECRET=your_session_secret
```

### Optional Variables
```bash
# Port Configuration
HTTP_PORT=8080
HTTPS_PORT=443
DB_EXTERNAL_PORT=3306

# Application Settings
ODM_HOSTNAME=odm.local
DB_PREFIX=odm_
DEFAULT_LANGUAGE=english
DEFAULT_THEME=tweeter

# Email Configuration
SMTP_HOST=localhost
SMTP_PORT=587
MAIL_FROM_ADDRESS=admin@yourdomain.com

# Volume Mounts (optional)
HOST_DATA_PATH=/path/to/data
HOST_DB_PATH=/path/to/db
HOST_CONFIG_PATH=/path/to/configs
```

## 🛠️ Diagnostics & Troubleshooting

### Environment Diagnostics Page
Access: `/install/env-check.php`

Features:
- **Configuration completeness** percentage
- **Variable-by-variable** status checking
- **Security recommendations** and warnings
- **Database consistency** validation
- **Port availability** checking
- **System information** display

### Quick Test Script
Access: `/test-env.php?confirm=yes`

Features:
- **Rapid environment** variable verification
- **Installation form** preview with pre-populated values
- **Docker environment** detection
- **Critical missing** variable identification

### Common Issues & Solutions

| Issue | Symptoms | Solution |
|-------|----------|----------|
| Admin password not pre-populated | Installation form shows empty password field | Check `ADMIN_PASSWORD` in `.env` file |
| Database connection fails | Installation errors about database connection | Verify database variables match between `MYSQL_*` and `APP_DB_*` |
| Environment variables not available | Form shows default values instead of .env values | Ensure `IS_DOCKER=true` and restart containers |
| Port conflicts | Services fail to start | Check port availability with `validate-env.sh` |

## 📁 File Structure

### New Files Added
```
opendocman/
├── .env.sample                    # Environment template (140+ variables)
├── .env                          # Generated environment file (git-ignored)
├── generate-env-secrets.sh       # Interactive setup script
├── validate-env.sh               # Configuration validation
├── Makefile                      # Management commands
├── test-env.php                  # Quick test script (git-ignored)
├── DOCKER_ENVIRONMENT.md         # This documentation
└── application/controllers/install/
    └── env-check.php             # Comprehensive diagnostics
```

### Modified Files
```
├── docker-compose.yml            # Updated to use environment variables
├── database.sql                  # Updated to v1.4.0, removed hardcoded admin
├── README.md                     # Added Docker environment documentation
├── .gitignore                    # Added .env and test files
└── application/controllers/install/
    ├── setup-config.php          # Enhanced with auto-population
    ├── index.php                 # Enhanced completion message
    └── odm.php                   # Improved admin user creation
```

## 🔄 Upgrade Path

### From Previous Docker Setup
1. **Backup your current** `.env` file if it exists
2. **Run the generator**: `./generate-env-secrets.sh`
3. **Migrate settings** from old configuration
4. **Restart services**: `make restart`

### From Manual Installation
1. **Create environment**: `./generate-env-secrets.sh`
2. **Migrate data** using backup/restore tools
3. **Switch to Docker**: `make up`

## 🧪 Testing Your Setup

### Validation Checklist
- [ ] Run `./validate-env.sh` with no errors
- [ ] Access `/install/env-check.php` shows 80%+ configuration
- [ ] Installation form shows pre-populated admin password
- [ ] Database connection succeeds during installation
- [ ] Admin login works with generated password

### Performance Testing
```bash
make stats      # Show container resource usage
make logs       # Monitor for errors
make backup     # Verify backup functionality
```

## 🤝 Contributing

### Adding New Environment Variables
1. Add to `.env.sample` with description
2. Update `docker-compose.yml` if needed
3. Add validation to `validate-env.sh`
4. Update diagnostics in `env-check.php`
5. Document in this file

### Testing Changes
1. Use `test-env.php` for quick verification
2. Run full validation with `validate-env.sh`
3. Test installation flow with fresh containers
4. Verify backup/restore functionality

## 📞 Support

### Getting Help
- **Environment Issues**: Check `/install/env-check.php`
- **Configuration Problems**: Run `./validate-env.sh`
- **Container Issues**: Use `make logs` for diagnostics
- **General Setup**: Follow the Quick Start guide

### Debug Information
When reporting issues, include:
- Output from `validate-env.sh`
- Environment diagnostics report
- Container logs: `make logs`
- Docker version: `make version`

---

*This environment management system ensures secure, automated, and reliable Docker deployments for OpenDocMan with minimal manual configuration required.*