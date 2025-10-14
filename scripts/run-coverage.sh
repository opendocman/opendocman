#!/bin/bash

# OpenDocMan Code Coverage Script
# This script runs PHPUnit tests with code coverage reporting

# Set the working directory to the project root (parent of scripts directory)
cd "$(dirname "$0")/.."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}OpenDocMan Code Coverage Report${NC}"
echo "=================================="
echo ""

# Check if Xdebug is available
if ! php -m | grep -q xdebug; then
    echo -e "${RED}Error: Xdebug is not installed.${NC}"
    echo "Please install it with: sudo apt install php8.2-xdebug"
    exit 1
fi

# Check if Xdebug coverage mode is enabled
if ! php -i | grep -q "Coverage => ✔ enabled"; then
    echo -e "${YELLOW}Warning: Xdebug coverage mode may not be enabled.${NC}"
    echo "Coverage will be forced using XDEBUG_MODE=coverage"
fi

echo -e "${GREEN}Running tests with code coverage...${NC}"
echo ""

# Set Xdebug mode to coverage
export XDEBUG_MODE=coverage

# Run PHPUnit with coverage based on the argument
case "$1" in
    "html")
        echo "Generating HTML coverage report..."
        COVERAGE_DIR="/tmp/opendocman-coverage-$(date +%s)"
        mkdir -p "$COVERAGE_DIR"
        
        { php -d xdebug.mode=coverage ./application/vendor/bin/phpunit --coverage-html "$COVERAGE_DIR" 2>&1 | grep -Ev 'User constructor - User not found for ID:|User constructor error: Undefined array key|Received Mockery_.*PDOStatement::fetchAll\(\), but no expectations were specified' ; status=${PIPESTATUS[0]}; }
        
        if [ $status -eq 0 ] && [ -f "$COVERAGE_DIR/index.html" ]; then
            echo ""
            echo -e "${GREEN}HTML coverage report generated successfully!${NC}"
            echo -e "Location: ${BLUE}$COVERAGE_DIR/index.html${NC}"
            echo ""
            echo "To view the report:"
            echo "  firefox $COVERAGE_DIR/index.html"
            echo "  # or"
            echo "  xdg-open $COVERAGE_DIR/index.html"
            echo ""
            echo "To copy to project directory:"
            echo "  cp -r $COVERAGE_DIR ./coverage-report-$(date +%Y%m%d)"
        else
            echo -e "${RED}Failed to generate HTML coverage report${NC}"
        fi
        ;;
    "xml")
        echo "Generating XML coverage report..."
        XML_FILE="/tmp/opendocman-coverage-$(date +%s).xml"
        
        { php -d xdebug.mode=coverage ./application/vendor/bin/phpunit --coverage-clover "$XML_FILE" 2>&1 | grep -Ev 'User constructor - User not found for ID:|User constructor error: Undefined array key|Received Mockery_.*PDOStatement::fetchAll\(\), but no expectations were specified' ; status=${PIPESTATUS[0]}; }
        
        if [ $status -eq 0 ] && [ -f "$XML_FILE" ]; then
            cp "$XML_FILE" ./coverage.xml
            echo ""
            echo -e "${GREEN}XML coverage report generated as coverage.xml${NC}"
            echo "Original file: $XML_FILE"
        else
            echo -e "${RED}Failed to generate XML coverage report${NC}"
        fi
        ;;
    "all")
        echo "Generating all coverage report formats..."
        COVERAGE_DIR="/tmp/opendocman-coverage-$(date +%s)"
        XML_FILE="$COVERAGE_DIR.xml"
        mkdir -p "$COVERAGE_DIR"
        
        php -d xdebug.mode=coverage ./application/vendor/bin/phpunit \
            --coverage-html "$COVERAGE_DIR" \
            --coverage-clover "$XML_FILE" \
            --coverage-text 2>&1 | grep -Ev 'User constructor - User not found for ID:|User constructor error: Undefined array key|Received Mockery_.*PDOStatement::fetchAll\(\), but no expectations were specified' ; status=${PIPESTATUS[0]}
        
        if [ $status -eq 0 ]; then
            echo ""
            echo -e "${GREEN}All coverage reports generated successfully!${NC}"
            [ -f "$COVERAGE_DIR/index.html" ] && echo -e "HTML: ${BLUE}$COVERAGE_DIR/index.html${NC}"
            if [ -f "$XML_FILE" ]; then
                cp "$XML_FILE" ./coverage.xml
                echo -e "XML: ${BLUE}./coverage.xml${NC}"
            fi
        else
            echo -e "${RED}Failed to generate coverage reports${NC}"
        fi
        ;;
    "text"|"")
        echo "Generating text coverage report..."
        { php -d xdebug.mode=coverage ./application/vendor/bin/phpunit --coverage-text 2>&1 | grep -Ev 'User constructor - User not found for ID:|User constructor error: Undefined array key|Received Mockery_.*PDOStatement::fetchAll\(\), but no expectations were specified' ; status=${PIPESTATUS[0]}; }
        ;;
    "help"|"-h"|"--help")
        echo -e "${BLUE}Usage:${NC}"
        echo "  $0 [option]"
        echo ""
        echo -e "${BLUE}Options:${NC}"
        echo "  text (default) - Generate text coverage report in terminal"
        echo "  html          - Generate HTML coverage report"
        echo "  xml           - Generate XML coverage report"
        echo "  all           - Generate all coverage report formats"
        echo "  help          - Show this help message"
        echo ""
        echo -e "${BLUE}Examples:${NC}"
        echo "  $0            # Text coverage report"
        echo "  $0 html       # HTML coverage report"
        echo "  $0 xml        # XML coverage report"
        echo "  $0 all        # All formats"
        exit 0
        ;;
    *)
        echo -e "${RED}Unknown option: $1${NC}"
        echo "Use '$0 help' for usage information"
        exit 1
        ;;
esac

echo ""
echo -e "${BLUE}Code Coverage Tips:${NC}"
echo "• To improve coverage, add more test methods that exercise different code paths"
echo "• Focus on testing the core business logic in your models and controllers"
echo "• Aim for at least 70-80% coverage for critical application components"