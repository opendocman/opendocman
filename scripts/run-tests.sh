#!/bin/bash

# OpenDocMan Test Runner Script
# This script provides convenient commands for running tests

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory - go to parent directory (project root)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$SCRIPT_DIR"

# Function to print colored output
print_color() {
    printf "${1}${2}${NC}\n"
}

# Function to print help
print_help() {
    echo "OpenDocMan Test Runner"
    echo "Usage: $0 [command] [options]"
    echo ""
    echo "Commands:"
    echo "  all                 Run all tests"
    echo "  unit                Run only unit tests"
    echo "  integration         Run only integration tests"
    echo "  coverage            Generate code coverage report"
    echo "  install             Install test dependencies"
    echo "  class <ClassName>   Run tests for specific class"
    echo "  file <TestFile>     Run specific test file"
    echo "  quiet               Run all tests with minimal output"
    echo "  help                Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 all              # Run all tests"
    echo "  $0 unit             # Run unit tests only"
    echo "  $0 class User       # Run UserTest.php"
    echo "  $0 file CategoryTest # Run CategoryTest.php"
    echo "  $0 coverage         # Generate HTML coverage report"
    echo "  $0 quiet            # Run tests with clean, minimal output"
    echo ""
}

# Function to check if PHPUnit is installed
check_phpunit() {
    if [ ! -f "./application/vendor/bin/phpunit" ]; then
        print_color $RED "PHPUnit not found. Please run: $0 install"
        exit 1
    fi
}

# Function to install dependencies
install_dependencies() {
    print_color $BLUE "Installing test dependencies..."
    
    if ! command -v composer &> /dev/null; then
        print_color $RED "Composer not found. Please install Composer first."
        exit 1
    fi
    
    composer install --ignore-platform-reqs
    print_color $GREEN "Dependencies installed successfully!"
}

# Function to run all tests
run_all_tests() {
    check_phpunit
    print_color $BLUE "Running all tests..."
    { FORCE_COLOR=1 ./application/vendor/bin/phpunit --verbose 2>&1 | \
        grep -Ev 'stty:|User constructor - User not found for ID:|User constructor error: Undefined array key|Received Mockery_.*PDOStatement::fetchAll\(\), but no expectations were specified' ; status=${PIPESTATUS[0]}; }
    if [ $status -ne 0 ]; then exit $status; fi
}

# Function to run unit tests only
run_unit_tests() {
    check_phpunit
    print_color $BLUE "Running unit tests..."
    { FORCE_COLOR=1 ./application/vendor/bin/phpunit --testsuite Unit --verbose 2>&1 | \
        grep -Ev 'stty:|User constructor - User not found for ID:|User constructor error: Undefined array key|Received Mockery_.*PDOStatement::fetchAll\(\), but no expectations were specified' ; status=${PIPESTATUS[0]}; }
    if [ $status -ne 0 ]; then exit $status; fi
}

# Function to run integration tests only
run_integration_tests() {
    check_phpunit
    print_color $BLUE "Running integration tests..."
    { FORCE_COLOR=1 ./application/vendor/bin/phpunit --testsuite Integration --verbose 2>&1 | \
        grep -Ev 'stty:|User constructor - User not found for ID:|User constructor error: Undefined array key|Received Mockery_.*PDOStatement::fetchAll\(\), but no expectations were specified' ; status=${PIPESTATUS[0]}; }
    if [ $status -ne 0 ]; then exit $status; fi
}

# Function to generate coverage report
generate_coverage() {
    check_phpunit
    print_color $BLUE "Generating code coverage report..."
    
    # Check if xdebug is enabled (needed for coverage)
    if ! php -m | grep -q xdebug; then
        print_color $YELLOW "Warning: Xdebug not found. Coverage may not work properly."
        print_color $YELLOW "Install xdebug with: sudo apt-get install php-xdebug"
    fi
    
    { FORCE_COLOR=1 ./application/vendor/bin/phpunit --coverage-html coverage --coverage-text 2>&1 | \
        grep -Ev 'stty:|User constructor - User not found for ID:|User constructor error: Undefined array key|Received Mockery_.*PDOStatement::fetchAll\(\), but no expectations were specified' ; status=${PIPESTATUS[0]}; }
    if [ $status -ne 0 ]; then exit $status; fi
    
    if [ -d "coverage" ]; then
        print_color $GREEN "Coverage report generated in 'coverage/' directory"
        print_color $BLUE "Open coverage/index.html in your browser to view the report"
    fi
}

# Function to run tests for a specific class
run_class_tests() {
    check_phpunit
    local class_name="$1"
    
    if [ -z "$class_name" ]; then
        print_color $RED "Please specify a class name. Example: $0 class User"
        exit 1
    fi
    
    local test_file="tests/Unit/${class_name}Test.php"
    
    if [ ! -f "$test_file" ]; then
        # Try integration tests
        test_file="tests/Integration/${class_name}Test.php"
        if [ ! -f "$test_file" ]; then
            print_color $RED "Test file not found for class: $class_name"
            print_color $YELLOW "Looked for: tests/Unit/${class_name}Test.php and tests/Integration/${class_name}Test.php"
            exit 1
        fi
    fi
    
    print_color $BLUE "Running tests for class: $class_name"
    { FORCE_COLOR=1 ./application/vendor/bin/phpunit "$test_file" --verbose 2>&1 | \
        grep -Ev 'stty:|User constructor - User not found for ID:|User constructor error: Undefined array key|Received Mockery_.*PDOStatement::fetchAll\(\), but no expectations were specified' ; status=${PIPESTATUS[0]}; }
    if [ $status -ne 0 ]; then exit $status; fi
}

# Function to run a specific test file
run_test_file() {
    check_phpunit
    local file_name="$1"
    
    if [ -z "$file_name" ]; then
        print_color $RED "Please specify a test file name. Example: $0 file CategoryTest"
        exit 1
    fi
    
    # Add .php extension if not present
    if [[ "$file_name" != *.php ]]; then
        file_name="${file_name}.php"
    fi
    
    # Look for the file in tests directory
    local test_file=""
    if [ -f "tests/Unit/$file_name" ]; then
        test_file="tests/Unit/$file_name"
    elif [ -f "tests/Integration/$file_name" ]; then
        test_file="tests/Integration/$file_name"
    elif [ -f "tests/$file_name" ]; then
        test_file="tests/$file_name"
    else
        print_color $RED "Test file not found: $file_name"
        print_color $YELLOW "Looked in: tests/Unit/, tests/Integration/, tests/"
        exit 1
    fi
    
    print_color $BLUE "Running test file: $test_file"
    { FORCE_COLOR=1 ./application/vendor/bin/phpunit "$test_file" --verbose 2>&1 | \
        grep -Ev 'stty:|User constructor - User not found for ID:|User constructor error: Undefined array key|Received Mockery_.*PDOStatement::fetchAll\(\), but no expectations were specified' ; status=${PIPESTATUS[0]}; }
    if [ $status -ne 0 ]; then exit $status; fi
}

# Function to list available tests
list_tests() {
    print_color $BLUE "Available test files:"
    echo ""
    
    if [ -d "tests/Unit" ]; then
        print_color $YELLOW "Unit Tests:"
        find tests/Unit -name "*Test.php" -exec basename {} \; | sort
        echo ""
    fi
    
    if [ -d "tests/Integration" ]; then
        print_color $YELLOW "Integration Tests:"
        find tests/Integration -name "*Test.php" -exec basename {} \; | sort
        echo ""
    fi
}

# Function to watch tests (requires inotify-tools)
watch_tests() {
    check_phpunit
    
    if ! command -v inotifywait &> /dev/null; then
        print_color $YELLOW "inotifywait not found. Install with: sudo apt-get install inotify-tools"
        print_color $BLUE "Running tests once instead..."
        run_all_tests
        return
    fi
    
    print_color $BLUE "Watching for file changes... Press Ctrl+C to stop"
    
    while true; do
        inotifywait -r -e modify,create,delete --include=".*\.php$" tests/ application/ >/dev/null 2>&1
        print_color $YELLOW "Files changed, running tests..."
        FORCE_COLOR=1 ./application/vendor/bin/phpunit --stop-on-failure 2>&1 | \
            grep -v "stty:" | \
            grep -v "User constructor - User not found"
        print_color $GREEN "Waiting for changes..."
    done
}

# Function to run tests with minimal output
run_quiet_tests() {
    check_phpunit
    print_color $BLUE "Running tests (quiet mode)..."
    
    local output
    output=$(FORCE_COLOR=0 ./application/vendor/bin/phpunit 2>&1 | \
        grep -v "stty:" | \
        grep -v "User constructor - User not found" | \
        tail -n 3)
    
    echo "$output"
}

# Main script logic
case "${1:-help}" in
    "all"|"")
        run_all_tests
        ;;
    "unit")
        run_unit_tests
        ;;
    "integration")
        run_integration_tests
        ;;
    "coverage")
        generate_coverage
        ;;
    "install")
        install_dependencies
        ;;
    "class")
        run_class_tests "$2"
        ;;
    "file")
        run_test_file "$2"
        ;;
    "list")
        list_tests
        ;;
    "quiet")
        run_quiet_tests
        ;;
    "watch")
        watch_tests
        ;;
    "help"|"-h"|"--help")
        print_help
        ;;
    *)
        print_color $RED "Unknown command: $1"
        echo ""
        print_help
        exit 1
        ;;
esac