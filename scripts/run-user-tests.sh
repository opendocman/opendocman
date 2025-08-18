#!/bin/bash

# OpenDocMan User Tests Runner
# This script runs all the User-related tests

echo "======================================"
echo "OpenDocMan User Tests Runner"
echo "======================================"
echo ""

# Set script directory - go to parent directory (project root)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$SCRIPT_DIR"

# Check if PHPUnit exists
if [ ! -f "application/vendor/bin/phpunit" ]; then
    echo "❌ Error: PHPUnit not found at application/vendor/bin/phpunit"
    echo "Please run 'composer install' in the application directory first."
    exit 1
fi

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}Running User Model Unit Tests...${NC}"
echo "--------------------------------------"
application/vendor/bin/phpunit tests/Unit/UserModelTest.php --verbose
MODEL_RESULT=$?

echo ""
echo -e "${BLUE}Running User Controller Functions Tests...${NC}"
echo "------------------------------------------------"
application/vendor/bin/phpunit tests/Unit/UserControllerFunctionsTest.php --verbose
FUNCTIONS_RESULT=$?

echo ""
echo -e "${BLUE}Running User Integration Tests...${NC}"
echo "--------------------------------------"
application/vendor/bin/phpunit tests/Integration/UserControllerTest.php --verbose
INTEGRATION_RESULT=$?

echo ""
echo "======================================"
echo "Test Results Summary"
echo "======================================"

if [ $MODEL_RESULT -eq 0 ]; then
    echo -e "✅ ${GREEN}User Model Tests: PASSED${NC}"
else
    echo -e "❌ ${RED}User Model Tests: FAILED${NC}"
fi

if [ $FUNCTIONS_RESULT -eq 0 ]; then
    echo -e "✅ ${GREEN}Controller Functions Tests: PASSED${NC}"
else
    echo -e "❌ ${RED}Controller Functions Tests: FAILED${NC}"
fi

if [ $INTEGRATION_RESULT -eq 0 ]; then
    echo -e "✅ ${GREEN}Integration Tests: PASSED${NC}"
else
    echo -e "❌ ${RED}Integration Tests: FAILED${NC}"
fi

echo ""

# Overall result
if [ $MODEL_RESULT -eq 0 ] && [ $FUNCTIONS_RESULT -eq 0 ] && [ $INTEGRATION_RESULT -eq 0 ]; then
    echo -e "🎉 ${GREEN}All User tests PASSED!${NC}"
    echo ""
    echo -e "${YELLOW}Test Coverage Summary:${NC}"
    echo "- Model Unit Tests: 29 tests, 140+ assertions"
    echo "- Controller Functions Tests: 16 tests, 95+ assertions"
    echo "- Integration Tests: 11 tests, 50+ assertions"
    echo "- Total: 56 tests, 285+ assertions"
    exit 0
else
    echo -e "💥 ${RED}Some User tests FAILED!${NC}"
    echo "Please check the output above for details."
    exit 1
fi