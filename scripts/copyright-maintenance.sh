#!/bin/bash

# OpenDocMan Copyright Maintenance Script
# Comprehensive solution for both dynamic and static copyright management
# Handles UI elements dynamically and source code comments with automated maintenance

set -e
set -o pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CURRENT_YEAR=$(date +%Y)
START_YEAR="2000"
COPYRIGHT_RANGE="$START_YEAR-$CURRENT_YEAR"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
CONFIG_FILE="$PROJECT_ROOT/.copyright-config"
LAST_UPDATE_FILE="$PROJECT_ROOT/.copyright-last-update"

echo -e "${BLUE}OpenDocMan Copyright Maintenance System${NC}"
echo -e "${BLUE}======================================${NC}"
echo ""
echo "Current Year: $CURRENT_YEAR"
echo "Copyright Range: $COPYRIGHT_RANGE"
echo ""

# Function to print status
print_status() {
    local message="$1"
    local status="$2"
    
    case "$status" in
        "info")
            echo -e "${BLUE}ℹ️  $message${NC}"
            ;;
        "success")
            echo -e "${GREEN}✅ $message${NC}"
            ;;
        "warning")
            echo -e "${YELLOW}⚠️  $message${NC}"
            ;;
        "error")
            echo -e "${RED}❌ $message${NC}"
            ;;
        "action")
            echo -e "${CYAN}🔧 $message${NC}"
            ;;
    esac
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [COMMAND] [OPTIONS]"
    echo ""
    echo "Commands:"
    echo "  check              Check copyright status across the codebase"
    echo "  update-static      Update static copyright comments in source files"
    echo "  update-dynamic     Set up/update dynamic copyright system"
    echo "  update-all         Update both static and dynamic copyright"
    echo "  auto-setup         Complete automated setup (recommended)"
    echo "  schedule           Set up automatic yearly maintenance"
    echo "  report             Generate detailed copyright report"
    echo ""
    echo "Options:"
    echo "  --year YYYY        Use specific year instead of current year"
    echo "  --dry-run          Show what would be changed without making changes"
    echo "  --force            Skip confirmation prompts"
    echo "  --verbose          Show detailed output"
    echo "  --help             Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 check"
    echo "  $0 update-all --dry-run"
    echo "  $0 auto-setup --force"
    echo "  $0 update-static --year 2026"
}

# Function to parse command line arguments
parse_args() {
    COMMAND=""
    DRY_RUN=false
    FORCE=false
    VERBOSE=false
    CUSTOM_YEAR=""
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            check|update-static|update-dynamic|update-all|auto-setup|schedule|report)
                COMMAND="$1"
                shift
                ;;
            --year)
                CUSTOM_YEAR="$2"
                shift 2
                ;;
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            --force)
                FORCE=true
                shift
                ;;
            --verbose)
                VERBOSE=true
                shift
                ;;
            --help)
                show_usage
                exit 0
                ;;
            *)
                print_status "Unknown option: $1" "error"
                show_usage
                exit 1
                ;;
        esac
    done
    
    # Use custom year if provided
    if [[ -n "$CUSTOM_YEAR" ]]; then
        if [[ "$CUSTOM_YEAR" =~ ^[0-9]{4}$ ]] && [[ "$CUSTOM_YEAR" -ge 2000 ]] && [[ "$CUSTOM_YEAR" -le 2099 ]]; then
            CURRENT_YEAR="$CUSTOM_YEAR"
            COPYRIGHT_RANGE="$START_YEAR-$CURRENT_YEAR"
        else
            print_status "Invalid year: $CUSTOM_YEAR (must be 2000-2099)" "error"
            exit 1
        fi
    fi
}

# Function to check copyright status
check_copyright_status() {
    print_status "Checking copyright status across codebase..." "info"
    echo ""
    
    # Count files by copyright year patterns (exclude backup directories and compiled templates)
    local php_files_2021=$(find "$PROJECT_ROOT/application" -name "*.php" -type f ! -path "*/templates_c/*" ! -path "*/copyright-backup*" | xargs grep -l "Copyright.*2000-2021" 2>/dev/null | wc -l | tr -d '\n' || echo "0")
    local php_files_current=$(find "$PROJECT_ROOT/application" -name "*.php" -type f ! -path "*/templates_c/*" ! -path "*/copyright-backup*" | xargs grep -l "Copyright.*2000-$CURRENT_YEAR" 2>/dev/null | wc -l | tr -d '\n' || echo "0")
    local tpl_files_2021=$(find "$PROJECT_ROOT/application/views" -name "*.tpl" -type f | xargs grep -l "2000-2021" 2>/dev/null | wc -l | tr -d '\n' || echo "0")
    local tpl_files_dynamic=$(find "$PROJECT_ROOT/application/views" -name "*.tpl" -type f | xargs grep -l "{copyright}" 2>/dev/null | wc -l | tr -d '\n' || echo "0")
    
    echo "📊 Copyright Status Report"
    echo "========================="
    echo ""
    echo "Static Source Code Comments (PHP files):"
    echo "  Outdated (2000-2021): $php_files_2021 files"
    echo "  Current (2000-$CURRENT_YEAR): $php_files_current files"
    echo ""
    echo "UI Templates:"
    echo "  Static outdated: $tpl_files_2021 files"  
    echo "  Dynamic system: $tpl_files_dynamic files"
    echo ""
    
    # Check if dynamic system is installed
    if [[ -f "$PROJECT_ROOT/application/controllers/helpers/copyright_helper.php" ]]; then
        print_status "Dynamic copyright system: INSTALLED" "success"
    else
        print_status "Dynamic copyright system: NOT INSTALLED" "warning"
    fi
    
    # Check last update
    if [[ -f "$LAST_UPDATE_FILE" ]]; then
        local last_update=$(cat "$LAST_UPDATE_FILE")
        echo "Last maintenance: $last_update"
    else
        echo "Last maintenance: Never"
    fi
    
    echo ""
    
    # Recommendations
    if [[ "$php_files_2021" -gt 0 ]]; then
        print_status "Recommendation: Update static source comments with 'update-static'" "action"
    fi
    
    if [[ ! -f "$PROJECT_ROOT/application/controllers/helpers/copyright_helper.php" ]]; then
        print_status "Recommendation: Install dynamic system with 'update-dynamic'" "action"
    fi
    
    if [[ "$tpl_files_2021" -gt 0 ]]; then
        print_status "Recommendation: Convert templates to dynamic with 'update-all'" "action"
    fi
}

# Function to update static copyright comments
update_static_copyright() {
    print_status "Updating static copyright comments..." "action"
    
    if [[ "$DRY_RUN" == true ]]; then
        print_status "DRY RUN MODE - No changes will be made" "warning"
    fi
    
    # Create backup
    local backup_dir="$PROJECT_ROOT/copyright-backup-$(date +%Y%m%d_%H%M%S)"
    if [[ "$DRY_RUN" != true ]]; then
        mkdir -p "$backup_dir"
        print_status "Backup directory: $backup_dir" "info"
    fi
    
    local count=0
    
    # Find and update PHP files (exclude compiled templates and backup directories)
    local php_files=()
    while IFS= read -r -d '' file; do
        php_files+=("$file")
    done < <(find "$PROJECT_ROOT/application" -name "*.php" -type f ! -path "*/templates_c/*" ! -path "*/copyright-backup*" -print0)
    
    for file in "${php_files[@]}"; do
        if [[ -f "$file" ]] && grep -q "Copyright (C) 2000-[0-9][0-9][0-9][0-9]\." "$file"; then
            if [[ "$DRY_RUN" == true ]]; then
                echo "  Would update: ${file#$PROJECT_ROOT/}"
            else
                # Backup and update
                local backup_file="$backup_dir/${file#$PROJECT_ROOT/}"
                mkdir -p "$(dirname "$backup_file")"
                cp "$file" "$backup_file"
                
                sed -i "s/Copyright (C) 2000-[0-9][0-9][0-9][0-9]\./Copyright (C) $COPYRIGHT_RANGE./g" "$file"
                
                if [[ "$VERBOSE" == true ]]; then
                    echo "  Updated: ${file#$PROJECT_ROOT/}"
                fi
            fi
            ((count++))
        fi
    done || true
    
    if [[ "$DRY_RUN" == true ]]; then
        print_status "Would update $count PHP files" "info"
    else
        print_status "Updated $count PHP files" "success"
        echo "$(date '+%Y-%m-%d %H:%M:%S') - Static copyright update to $CURRENT_YEAR" >> "$LAST_UPDATE_FILE"
    fi
}

# Function to set up dynamic copyright system
setup_dynamic_copyright() {
    print_status "Setting up dynamic copyright system..." "action"
    
    if [[ "$DRY_RUN" == true ]]; then
        print_status "DRY RUN MODE - Would create dynamic copyright system" "warning"
        return
    fi
    
    # Create copyright helper if it doesn't exist
    local helper_file="$PROJECT_ROOT/application/controllers/helpers/copyright_helper.php"
    if [[ ! -f "$helper_file" ]]; then
        mkdir -p "$(dirname "$helper_file")"
        
        cat > "$helper_file" << 'EOF'
<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

/**
 * Copyright Helper Class - Automatic Copyright Year Management
 * Provides dynamic copyright year functionality for OpenDocMan
 */
class CopyrightHelper
{
    private static $cache = [];
    
    public static function getYearRange($startYear = 2000)
    {
        $cacheKey = "range_$startYear";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        
        $currentYear = (int)date('Y');
        $result = ($currentYear <= $startYear) ? (string)$startYear : $startYear . '-' . $currentYear;
        
        self::$cache[$cacheKey] = $result;
        return $result;
    }
    
    public static function getNotice($holder = 'Stephen Lawrence Jr.', $startYear = 2000)
    {
        return 'Copyright &copy; ' . self::getYearRange($startYear) . ' ' . $holder;
    }
    
    public static function getSourceNotice($holder = 'Stephen Lawrence', $startYear = 2000)
    {
        return 'Copyright (C) ' . self::getYearRange($startYear) . '. ' . $holder;
    }
    
    public static function getApiNotice($startYear = 2000)
    {
        return [
            'copyright' => 'Copyright ' . self::getYearRange($startYear) . ' Stephen Lawrence',
            'year' => (int)date('Y'),
            'range' => self::getYearRange($startYear)
        ];
    }
}

// Global functions
if (!function_exists('get_copyright_years')) {
    function get_copyright_years($startYear = 2000) {
        return CopyrightHelper::getYearRange($startYear);
    }
}

if (!function_exists('get_copyright_notice')) {
    function get_copyright_notice($holder = 'Stephen Lawrence Jr.', $startYear = 2000) {
        return CopyrightHelper::getNotice($holder, $startYear);
    }
}
EOF
        
        print_status "Created copyright helper class" "success"
    fi
    
    # Create Smarty plugin
    local plugin_file="$PROJECT_ROOT/application/views/common/plugins/function.copyright.php"
    mkdir -p "$(dirname "$plugin_file")"
    
    cat > "$plugin_file" << 'EOF'
<?php
/*
 * Smarty Copyright Plugin
 * Usage: {copyright} or {copyright holder="Custom Name" start="2010"}
 */

function smarty_function_copyright($params, $smarty) {
    if (!class_exists('CopyrightHelper')) {
        require_once dirname(__FILE__) . '/../../../controllers/helpers/copyright_helper.php';
    }
    
    $holder = isset($params['holder']) ? $params['holder'] : 'Stephen Lawrence Jr.';
    $startYear = isset($params['start']) ? (int)$params['start'] : 2000;
    $format = isset($params['format']) ? $params['format'] : 'html';
    
    return ($format === 'text') 
        ? CopyrightHelper::getSourceNotice($holder, $startYear)
        : CopyrightHelper::getNotice($holder, $startYear);
}
EOF
    
    print_status "Created Smarty copyright plugin" "success"
    
    # Update footer templates
    local default_footer="$PROJECT_ROOT/application/views/default/footer.tpl"
    if [[ -f "$default_footer" ]] && ! grep -q "{copyright}" "$default_footer"; then
        sed -i.bak 's/Copyright &copy; 2000-[0-9][0-9][0-9][0-9] Stephen Lawrence Jr\./{copyright}/g' "$default_footer"
        print_status "Updated default theme footer" "success"
    fi
    
    local tweeter_footer="$PROJECT_ROOT/application/views/tweeter/footer.tpl"
    if [[ -f "$tweeter_footer" ]] && ! grep -q "{copyright}" "$tweeter_footer"; then
        sed -i.bak 's/<p>Copyright &copy; 2000-[0-9][0-9][0-9][0-9] Stephen Lawrence<\/p>/<p>{copyright holder="Stephen Lawrence"}<\/p>/g' "$tweeter_footer"
        print_status "Updated tweeter theme footer" "success"
    fi
    
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Dynamic copyright system installed" >> "$LAST_UPDATE_FILE"
}

# Function to generate detailed report
generate_report() {
    print_status "Generating detailed copyright report..." "info"
    
    local report_file="$PROJECT_ROOT/copyright-report-$(date +%Y%m%d_%H%M%S).txt"
    
    {
        echo "OpenDocMan Copyright Report"
        echo "Generated: $(date)"
        echo "Current Year: $CURRENT_YEAR"
        echo "========================================="
        echo ""
        
        echo "Source Files Analysis:"
        echo "======================"
        find "$PROJECT_ROOT/application" -name "*.php" -type f ! -path "*/templates_c/*" ! -path "*/copyright-backup*" | while read -r file; do
            if [[ -f "$file" ]] && grep -q "Copyright" "$file"; then
                echo "File: ${file#$PROJECT_ROOT/}"
                grep -n "Copyright" "$file" | head -1 || true
                echo ""
            fi
        done || true
        
        echo ""
        echo "Template Files Analysis:"
        echo "========================"
        find "$PROJECT_ROOT/application/views" -name "*.tpl" -type f ! -path "*/templates_c/*" | while read -r file; do
            if [[ -f "$file" ]] && grep -q -i "copyright" "$file"; then
                echo "File: ${file#$PROJECT_ROOT/}"
                grep -ni "copyright" "$file" || true
                echo ""
            fi
        done || true
        
        echo ""
        echo "Dynamic System Status:"
        echo "======================"
        if [[ -f "$PROJECT_ROOT/application/controllers/helpers/copyright_helper.php" ]]; then
            echo "✓ Copyright Helper: INSTALLED"
        else
            echo "✗ Copyright Helper: NOT INSTALLED"
        fi
        
        if [[ -f "$PROJECT_ROOT/application/views/common/plugins/function.copyright.php" ]]; then
            echo "✓ Smarty Plugin: INSTALLED"
        else
            echo "✗ Smarty Plugin: NOT INSTALLED"
        fi
        
    } > "$report_file"
    
    print_status "Report saved to: $report_file" "success"
}

# Function to set up scheduled maintenance
setup_scheduling() {
    print_status "Setting up automatic yearly maintenance..." "action"
    
    if [[ "$DRY_RUN" == true ]]; then
        print_status "DRY RUN MODE - Would set up cron job" "warning"
        return
    fi
    
    local cron_script="$PROJECT_ROOT/scripts/copyright-yearly-update.sh"
    
    cat > "$cron_script" << EOF
#!/bin/bash
# Automatic yearly copyright maintenance
# Generated by copyright-maintenance.sh

cd "$PROJECT_ROOT"
./scripts/copyright-maintenance.sh update-static --force
echo "\$(date): Automatic copyright maintenance completed" >> ./logs/copyright-maintenance.log
EOF
    
    chmod +x "$cron_script"
    
    echo ""
    print_status "Created yearly maintenance script: $cron_script" "success"
    print_status "To enable automatic updates, add this to your crontab:" "info"
    echo ""
    echo "# Run copyright maintenance on January 1st each year"
    echo "0 0 1 1 * $cron_script"
    echo ""
    echo "Add it with: crontab -e"
}

# Main command handlers
case_check() {
    check_copyright_status
}

case_update_static() {
    if [[ "$FORCE" != true ]] && [[ "$DRY_RUN" != true ]]; then
        echo -e "${YELLOW}This will update static copyright comments in source files.${NC}"
        read -p "Continue? (y/N): " -r
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_status "Operation cancelled" "info"
            exit 0
        fi
    fi
    update_static_copyright
}

case_update_dynamic() {
    if [[ "$FORCE" != true ]] && [[ "$DRY_RUN" != true ]]; then
        echo -e "${YELLOW}This will set up the dynamic copyright system.${NC}"
        read -p "Continue? (y/N): " -r
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_status "Operation cancelled" "info"
            exit 0
        fi
    fi
    setup_dynamic_copyright
}

case_update_all() {
    if [[ "$FORCE" != true ]] && [[ "$DRY_RUN" != true ]]; then
        echo -e "${YELLOW}This will update both static and dynamic copyright systems.${NC}"
        read -p "Continue? (y/N): " -r
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_status "Operation cancelled" "info"
            exit 0
        fi
    fi
    update_static_copyright
    echo ""
    setup_dynamic_copyright
}

case_auto_setup() {
    print_status "Running complete automated copyright setup..." "action"
    echo ""
    
    if [[ "$FORCE" != true ]] && [[ "$DRY_RUN" != true ]]; then
        echo -e "${YELLOW}This will set up complete copyright management:${NC}"
        echo "  • Update all static source code comments"
        echo "  • Install dynamic copyright system"  
        echo "  • Convert templates to use dynamic copyright"
        echo "  • Set up maintenance scripts"
        read -p "Continue? (y/N): " -r
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_status "Operation cancelled" "info"
            exit 0
        fi
    fi
    
    update_static_copyright
    echo ""
    setup_dynamic_copyright
    echo ""
    setup_scheduling
    echo ""
    check_copyright_status
    echo ""
    print_status "Complete automated setup finished!" "success"
    print_status "UI elements will now automatically show current year: $CURRENT_YEAR" "success"
}

case_schedule() {
    setup_scheduling
}

case_report() {
    generate_report
}

# Main execution
main() {
    # Change to project root
    cd "$PROJECT_ROOT"
    
    # Parse arguments
    parse_args "$@"
    
    # Show default if no command
    if [[ -z "$COMMAND" ]]; then
        show_usage
        exit 0
    fi
    
    # Execute command
    case "$COMMAND" in
        check)
            case_check
            ;;
        update-static)
            case_update_static
            ;;
        update-dynamic)
            case_update_dynamic
            ;;
        update-all)
            case_update_all
            ;;
        auto-setup)
            case_auto_setup
            ;;
        schedule)
            case_schedule
            ;;
        report)
            case_report
            ;;
        *)
            print_status "Unknown command: $COMMAND" "error"
            show_usage
            exit 1
            ;;
    esac
}

# Verify we're in the right directory
if [[ ! -f "$PROJECT_ROOT/Makefile" ]]; then
    print_status "Error: This script must be run from within the OpenDocMan project" "error"
    print_status "Current directory: $(pwd)" "info"
    print_status "Expected to find: Makefile" "info"
    exit 1
fi

# Run main function
main "$@"