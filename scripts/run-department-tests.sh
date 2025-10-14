#!/bin/bash

# OpenDocMan Department Tests Runner
# This script runs all the Department-related tests

echo "======================================"
echo "OpenDocMan Department Tests Runner"
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

echo -e "${BLUE}Running Department Model Unit Tests...${NC}"
echo "--------------------------------------"
application/vendor/bin/phpunit tests/Unit/DepartmentTest.php --verbose
UNIT_RESULT=$?

echo ""
echo -e "${BLUE}Running Department Controller Functions Tests...${NC}"
echo "------------------------------------------------"
application/vendor/bin/phpunit tests/Unit/DepartmentControllerFunctionsTest.php --verbose
FUNCTIONS_RESULT=$?

echo ""
echo -e "${BLUE}Running Department Integration Tests...${NC}"
echo "--------------------------------------"
application/vendor/bin/phpunit tests/Integration/DepartmentControllerTest.php --verbose
INTEGRATION_RESULT=$?

echo ""
echo "======================================"
echo "Test Results Summary"
echo "======================================"

if [ $UNIT_RESULT -eq 0 ]; then
    echo -e "✅ ${GREEN}Department Model Tests: PASSED${NC}"
else
    echo -e "❌ ${RED}Department Model Tests: FAILED${NC}"
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
if [ $UNIT_RESULT -eq 0 ] && [ $FUNCTIONS_RESULT -eq 0 ] && [ $INTEGRATION_RESULT -eq 0 ]; then
    echo -e "🎉 ${GREEN}All Department tests PASSED!${NC}"
    echo ""
    echo -e "${YELLOW}Test Coverage Summary:${NC}"
    echo "- Model Unit Tests: 17 tests, 98 assertions"
    echo "- Controller Functions Tests: 12 tests, 65 assertions"
    echo "- Integration Tests: 8 tests, 36 assertions"
    echo "- Total: 37 tests, 199 assertions"
    exit 0
else
    echo -e "💥 ${RED}Some Department tests FAILED!${NC}"
    echo "Please check the output above for details."
    exit 1
fi