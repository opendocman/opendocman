# OpenDocMan

Free PHP Document Management System DMS

OpenDocMan is a web based document management system (DMS) written in PHP designed to comply with ISO 17025 and OIE standard for document management. It features fine grained control of access to files, and automated install and upgrades.

Features

    * Upload files using web browser
    * Control access to files based on department or individual user permissions
    * Track revisions of documents
    * Option to send new and updated files through review process
    * Installs on most web servers with PHP
    * Set up a review process for all new files

# License
- GPL 2.0

# Technologies
- PHP 8.2
- Database: MySQL 8+, MariaDB 10.0+
- PHP Capable web server

# Support
- [Discord Server](https://discord.gg/c92ksu4x)
- [Discorse Server](https://discourse.opendocman.com/)

# Installation

## Setting Up The Database

- Login to your mysql server
- `create database opendocman;`
- `CREATE USER 'opendocman'@'localhost' IDENTIFIED WITH mysql_native_password BY 'YOURPASSWORDHERE';`
- `GRANT ALL PRIVILEGES ON opendocman.* TO 'opendocman'@'YOURDBHOSTNAME';`

## Deploying The App

### Installing via Docker

Installing via Docker is the easiest option. This will run the database and app inside a docker-compose deployment.
The docker-configs folder and the files-data folder will be created to persist when you stop/start/update your
docker-compose deployment.

#### Quick Setup (Recommended)

For the fastest setup with automatically generated secure passwords:

1. **Generate environment configuration:**
   ```bash
   ./generate-env-secrets.sh
   ```
   This script will:
   - Create a `.env` file from the template
   - Generate secure passwords for database and admin user
   - Prompt you for basic configuration (ports, hostname, email)
   - Validate your configuration

2. **Start the application:**
   ```bash
   make up
   # OR
   docker-compose up -d --build
   ```

3. **Access your application:**
   - URL: `http://localhost:8080` (or your configured port)
   - Username: `admin`
   - Password: (shown during setup or check your `.env` file)

#### Manual Environment Configuration

If you prefer to configure manually:

1. **Copy the environment template:**
   ```bash
   cp .env.sample .env
   ```

2. **Edit the `.env` file:**
   ```bash
   nano .env
   ```

   **Required settings to change:**
   - `MYSQL_PASSWORD` - Set a secure database password
   - `MYSQL_ROOT_PASSWORD` - Set a secure root password
   - `APP_DB_PASS` - Must match `MYSQL_PASSWORD`
   - `ADMIN_PASSWORD` - Set the initial admin user password
   - `SESSION_SECRET` - Set a random string for session encryption

3. **Validate your configuration:**
   ```bash
   ./validate-env.sh
   ```

#### Environment Management Tools

Several helper scripts are provided for managing your Docker environment:

- **`./generate-env-secrets.sh`** - Interactive setup with secure password generation
- **`./validate-env.sh`** - Validate your `.env` configuration
- **`make help`** - Show all available Makefile commands
- **`make setup`** - Complete setup (generate + validate + start)
- **`make status`** - Show service status and access information
- **`make backup`** - Create backup of database and files
- **`make logs`** - View application logs

#### Environment Diagnostics

If you encounter issues with your Docker setup, use the built-in diagnostics:

- **Environment Diagnostics Page**: Visit `/install/env-check.php` to view a comprehensive report of your environment configuration
- **During Installation**: Look for the "🔧 View Environment Diagnostics" link on the setup page
- **Troubleshooting**: The diagnostics page shows configuration status, missing variables, and security recommendations

#### Makefile Commands

Use these convenient commands for managing your installation:

```bash
make setup      # Complete setup from scratch
make up         # Start services
make down       # Stop services
make restart    # Restart services
make logs       # View logs
make status     # Show status and access URLs
make backup     # Create backup
make clean      # Remove all data (WARNING: destructive)
```

#### Environment Variables Reference

The `.env` file supports comprehensive configuration across these categories:

- **Database Configuration**: MySQL/MariaDB settings and connection parameters
- **Application Configuration**: OpenDocMan-specific settings
- **Port Configuration**: External port mappings for web and database services
- **Security Configuration**: Passwords, secrets, and file upload limits
- **Email Configuration**: SMTP settings for notifications
- **SSL/TLS Configuration**: Certificate paths and SSL settings
- **Volume Mount Paths**: Optional host directory mappings
- **Development Settings**: Debug mode and logging configuration
- **Backup Configuration**: Automated backup settings

For a complete list of available variables and their descriptions, see the `.env.sample` file.

#### Installation Form Auto-Population

When running in Docker, the installation form at `/install/setup-config.php` will automatically pre-populate fields with values from your `.env` file:

- **Database settings** (host, name, user, password)
- **Admin password** (from `ADMIN_PASSWORD`)
- **Table prefix** (from `DB_PREFIX`)
- **Data directory** (from `ODM_DATA_DIR`)

This eliminates manual entry and reduces configuration errors during setup.

#### Docker Environment Features Summary

This OpenDocMan installation includes comprehensive Docker environment management with the following features:

**🔧 Environment Management Tools:**
- **`.env.sample`** - Template with 140+ configuration options across 8 categories
- **`generate-env-secrets.sh`** - Interactive setup with automatic secure password generation
- **`validate-env.sh`** - Configuration validation with security checks and recommendations
- **`Makefile`** - 25+ commands for container management, backup, and maintenance

**🔒 Security Features:**
- Automatic generation of secure passwords (24-32 characters)
- Session secret generation (64-character hex)
- Password strength validation and security warnings
- Environment variable masking in diagnostics
- `.env` file automatically excluded from version control

**📋 Installation Auto-Population:**
- Database configuration pre-filled from environment variables
- Admin password automatically populated from `ADMIN_PASSWORD`
- Table prefix, data directory, and other settings pre-configured
- Docker environment detection with helpful UI indicators

**🛠️ Diagnostics and Troubleshooting:**
- **Environment Diagnostics** (`/install/env-check.php`) - Comprehensive configuration analysis
- **Test Script** (`/test-env.php`) - Quick environment variable verification
- Real-time validation during installation process
- Detailed status reporting and recommendations

**📦 Configuration Categories:**
- Database settings (MySQL/MariaDB)
- Security configuration (passwords, secrets, file limits)
- Port configuration (HTTP, HTTPS, database, PHP-FPM)
- Email/SMTP settings for notifications
- SSL/TLS certificate configuration
- Volume mount paths (optional host directory mapping)
- Development settings (debug mode, logging)
- Backup and maintenance configuration

**🚀 Management Commands:**
```bash
make setup      # Complete setup with secure password generation
make up         # Start services with environment validation
make status     # Show service status and access URLs
make backup     # Create database and file backups
make logs       # View application logs
make clean      # Remove all data (with confirmation)
```

#### Security Notes

- Your `.env` file contains sensitive passwords and secrets
- Never commit `.env` to version control (it's in `.gitignore`)
- Keep a secure backup of your `.env` file
- Use the validation script to check for common security issues
- Environment diagnostics automatically mask sensitive values
- Generated passwords meet security best practices

### Installing to a web server (Automatic)

1. Untar/Unzip files into any dir in your web server document home folder
1. Configure your web server "document root" to point to the /public folder
1. Create a MySQL database/username/password
1. Make a directory for the uploaded documents to be stored that is accessible
   to the web server but not available by browsing. Ensure the
   permissions are correct on this folder to allow for the web
   server to write to it. Refer to the help text in the installer
   for more information.
   ex.  $>`mkdir /var/www/document_repository`
1. Load the opendocman index.php page in your web browser and follow the prompts.
1. Enjoy!


### Installing to a web server (Manual)

1. Untar/Unzip files into any dir in your web server document home folder
1. Configure your web server "document root" to point to the /public folder
1. Create a MySQL database/username/password
1. Make a directory for the uploaded documents to be stored that is accessible
   to the web server but not available by browsing. Ensure the
   permissions are correct on this folder to allow for the web
   server to write to it. Refer to the help text in the installer
   for more information.
   ex.  $>`mkdir /var/www/document_repository`
1. Copy the application/configs/config-sample.php to application/configs/config.php
1. Edit the config.php to include your database parameters
1. Edit the database.sql file. You need to change the values set in the odm_settings table, and odm_user tables,
   specifically the dataDir value, and the password used for the admin user creation
1. Import your database.sql file into your database
1. Visit the URL for your installation and login as admin/admin. Change your admin password once you login.

## Update Procedure

To update your current version to the latest release:

1. Rename your current opendocman folder.
1. Unarchive opendocman into a new folder and rename it to the original folder name
1. Copy your original config.php file into the new folder
1. Load the opendocman /install address in your web browser ( ex. http://www.example.com/install )
1. Follow the prompts for installation.

## Developer Notes

### The automated installation and upgrade:
There is a folder named "application/controllers/install" which contains
files use by the setup script. This is an automated web-based
update/installation process. Here is how it works:

The user loads the public/index.php into their browser. They can either
select the new installation link, or one of the upgrade links. For a new
installation, the user will be prompted to enter a priviledged mysql username
and password. This is for the database creation and grant assignments. The
script will then proceed to install all the necessary data structures and
default data entries for the most current version of ODM.

For updates, the user will be shown their current version (which comes from
configs/config.php), and they would then click on the appropriate upgrade
link. For example, if their version number is 1.0, they would click on the
"Upgrade from 1.0" link. This will apply all necessary database changes to
their current database.

For developers, when there is a new version release, a few new files need
to be created current files modified:

1. upgrade_x.php - where x is the release name. This file should follow the
   same format as the other upgrade_x.php files and is used for upgrades only.
   This should be built from the output of a program like mysqldiff.pl and
   is the difference between the new version, and the version before it.
2. setup-config.php - This is where we convert user input during the install
   into a config.php file
3. index.php - add a new function for the new version upgrade
   (ex. "do_update_x()") where x is the release name.
   1. Inside this new function, you must "include" each previous upgrade
      file in succession (see upgrade_10.php for an example).
   2. Add a new case statement for the new upgrade call
4. odm.php - This file contains all the current SQL commands needed to install
5. database.sql  - This should contain the same sql commands as odm.php,
   only in a mysqldump format for users that need to manually install the
   program for some reason.

These files MUST be kept syncronized for each release!

## Contributing

Anyone is welcome to contribute to OpenDocMan's codebase! If you have a fix or code change, feel free to submit it as a pull request directly to the "master" branch. In cases where the change is relatively small or does not affect other parts of the codebase, it may be merged in immediately by any one of the collaborators. On the other hand, if the change is particularly large or complex, it is expected that it will be discussed at length either well in advance of the pull request being submitted, or even directly on the pull request.

## Donate
Donations to the general fund are used to pay for the infrastructure costs that are currently covered by sponsors.

General Fund
You can donate Monero:

Monero:
495vHjEsWegDydtAvo18DU8B1Y4c3xH1SdHdGLsKwZo2j9JxrcZDZ85aVeiSjSGRGBj3dvKKf1Aj4XcLk9bpKR5R6jj5WuF
