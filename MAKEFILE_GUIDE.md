# OpenDocMan Makefile Guide

This guide explains how to use the consolidated Makefile and script structure for OpenDocMan development and testing.

## Overview

The Makefile has been reorganized to serve as the single source of truth for all development tasks. Shell scripts have been moved to the `scripts/` directory and are integrated through the Makefile.

## Quick Start

```bash
# Complete setup
make setup

# Run all tests
make test

# Start services
make up

# View all available commands
make help
```

## Project Structure

```
opendocman/
├── Makefile              # Main task runner (source of truth)
├── scripts/              # Shell scripts (organized by functionality)
│   ├── run-tests.sh      # Comprehensive test runner
│   ├── run-coverage.sh   # Code coverage reporting
│   ├── run-user-tests.sh # User-specific tests
│   ├── run-department-tests.sh # Department-specific tests
│   ├── generate-env-secrets.sh # Environment setup
│   └── validate-env.sh   # Environment validation
└── ...
```

## Command Categories

### Environment Setup
- `make env-generate` - Generate secure .env file
- `make env-validate` - Validate .env configuration
- `make setup` - Complete setup (generate + validate + start)

### Docker Operations
- `make up` - Start services
- `make down` - Stop services
- `make build` - Build Docker images
- `make restart` - Restart all services
- `make status` - Show service status
- `make logs` - View all logs

### Testing Commands
- `make test` - Run all tests
- `make test-unit` - Run unit tests only
- `make test-integration` - Run integration tests only
- `make test-user` - Run all user-related tests
- `make test-department` - Run all department-related tests
- `make test-class CLASS=ClassName` - Run tests for specific class
- `make test-file FILE=TestFile` - Run specific test file
- `make test-list` - List available test files
- `make test-quiet` - Run tests with minimal output
- `make test-watch` - Watch files and auto-run tests
- `make test-install` - Install test dependencies

### Code Coverage
- `make coverage` - Generate text coverage report
- `make coverage-html` - Generate HTML coverage report
- `make coverage-xml` - Generate XML coverage report
- `make coverage-all` - Generate all coverage formats

### Development
- `make dev` - Start in development mode
- `make shell` - Open shell in app container
- `make shell-db` - Open MySQL shell

### Maintenance
- `make clean` - Remove containers and volumes
- `make rebuild` - Rebuild everything
- `make backup` - Create backup
- `make security-scan` - Run security scan

## Testing Examples

### Basic Testing
```bash
# Run all tests
make test

# Run only unit tests
make test-unit

# Run integration tests
make test-integration
```

### Component-Specific Testing
```bash
# Test user functionality
make test-user

# Test department functionality
make test-department
```

### Targeted Testing
```bash
# Test specific class
make test-class CLASS=User

# Test specific file
make test-file FILE=CategoryTest

# List available tests
make test-list
```

### Coverage Reports
```bash
# Quick text coverage
make coverage

# HTML report for detailed viewing
make coverage-html

# XML report for CI/CD
make coverage-xml

# All formats
make coverage-all
```

### Development Workflow
```bash
# Watch for file changes and auto-run tests
make test-watch

# Run tests quietly (minimal output)
make test-quiet
```

## Migration from Old Scripts

### Before (scattered scripts)
```bash
./run-tests.sh unit
./run-coverage.sh html
./run-user-tests.sh
./generate-env-secrets.sh
```

### After (consolidated through Makefile)
```bash
make test-unit
make coverage-html
make test-user
make env-generate
```

## Advanced Usage

### Using Variables
```bash
# Test specific class
make test-class CLASS=User

# Test specific file
make test-file FILE=CategoryTest

# Restore from backup
make restore-db BACKUP_FILE=backups/db_20241201_120000.sql
```

### Environment Variables
```bash
# Set custom project name
COMPOSE_PROJECT_NAME=my-odm make up

# Use different environment
ODM_ENV=dev make up
```

### Direct Script Access
If you need to use scripts directly with advanced options:
```bash
# Advanced test runner options
./scripts/run-tests.sh help

# Coverage script options
./scripts/run-coverage.sh help

# View script help through Makefile
make scripts-help
```

## Benefits of the New Structure

1. **Single Source of Truth**: All tasks go through the Makefile
2. **Organized Scripts**: Scripts are in a dedicated directory
3. **Consistent Interface**: All commands follow `make <action>` pattern
4. **Better Documentation**: Help system shows all available commands
5. **Easier Discovery**: `make help` shows everything you can do
6. **Clean Root Directory**: Fewer files in the project root

## Troubleshooting

### Test Command Issues
If `make test` fails but `./scripts/run-tests.sh` works:
1. Check that scripts are executable: `chmod +x scripts/*.sh`
2. Verify scripts work from project root
3. Check for path issues in script directories

### Docker vs Native Testing
- Makefile test commands run tests natively (faster)
- Docker test commands are available but may have TTY issues
- Use native testing for development, Docker for CI/CD

### Coverage Reports
- Requires Xdebug for coverage reports
- Install with: `sudo apt install php8.2-xdebug`
- Coverage reports are generated in temporary directories by default

## Best Practices

1. **Use Makefile commands** instead of calling scripts directly
2. **Start with `make help`** to see all available commands
3. **Use `make setup`** for initial project setup
4. **Use `make test`** for comprehensive testing
5. **Use `make coverage-html`** for detailed coverage analysis
6. **Keep scripts in the `scripts/` directory** for organization

## Future Enhancements

The Makefile structure is designed to be extensible. Future additions might include:
- Linting commands (`make lint`)
- Documentation generation (`make docs`)
- Deployment commands (`make deploy`)
- Database migration commands (`make migrate`)

---

For more help, run `make help` or `make scripts-help` to see all available commands and their descriptions.