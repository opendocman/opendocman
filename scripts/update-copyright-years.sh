#!/bin/bash

# OpenDocMan Copyright Year Update Script
# Updates all copyright years from old ranges to 2000-2025
# This script safely updates copyright notices in source files and templates

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CURRENT_YEAR="2025"
NEW_COPYRIGHT_RANGE="2000-$CURRENT_YEAR"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}OpenDocMan Copyright Year Update Script${NC}"
echo -e "${BLUE}=====================================${NC}"
echo ""
echo "This script will update copyright years to: $NEW_COPYRIGHT_RANGE"
echo "Target directory: $PROJECT_ROOT"
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
        *)
            echo "$message"
            ;;
    esac
}

# Create backup directory
BACKUP_DIR="$PROJECT_ROOT/copyright-backup-$(date +%Y%m%d_%H%M%S)"
print_status "Creating backup directory: $BACKUP_DIR" "info"
mkdir -p "$BACKUP_DIR"

# Function to backup and update a file
update_file() {
    local file="$1"
    local pattern="$2"
    local replacement="$3"
    local relative_path="${file#$PROJECT_ROOT/}"
    
    # Skip if file doesn't exist
    if [[ ! -f "$file" ]]; then
        return
    fi
    
    # Check if file contains the pattern
    if grep -q "$pattern" "$file"; then
        print_status "Updating: $relative_path" "info"
        
        # Create backup directory structure
        backup_file="$BACKUP_DIR/$relative_path"
        mkdir -p "$(dirname "$backup_file")"
        
        # Backup original file
        cp "$file" "$backup_file"
        
        # Update the file
        sed -i.tmp "s/$pattern/$replacement/g" "$file"
        rm "$file.tmp"
        
        print_status "  ✓ Updated and backed up" "success"
    fi
}

# Function to update PHP source files
update_php_files() {
    print_status "Updating PHP source files..." "info"
    local count=0
    
    # Update various copyright patterns in PHP files
    while IFS= read -r -d '' file; do
        # Pattern 1: Copyright (C) 2000-2021. Stephen Lawrence
        if grep -q "Copyright (C) 2000-2021\. Stephen Lawrence" "$file"; then
            update_file "$file" "Copyright (C) 2000-2021\. Stephen Lawrence" "Copyright (C) $NEW_COPYRIGHT_RANGE. Stephen Lawrence"
            ((count++))
        fi
        
        # Pattern 2: Copyright (C) 2008-2021. Stephen Lawrence (for some helper files)
        if grep -q "Copyright (C) 2008-2021\. Stephen Lawrence" "$file"; then
            update_file "$file" "Copyright (C) 2008-2021\. Stephen Lawrence" "Copyright (C) 2008-$CURRENT_YEAR. Stephen Lawrence"
            ((count++))
        fi
        
    done < <(find "$PROJECT_ROOT/application" -name "*.php" -type f -print0)
    
    print_status "Updated $count PHP files" "success"
}

# Function to update template files
update_template_files() {
    print_status "Updating template files..." "info"
    local count=0
    
    while IFS= read -r -d '' file; do
        # Pattern: Copyright &copy; 2000-2021 Stephen Lawrence
        if grep -q "Copyright &copy; 2000-2021 Stephen Lawrence" "$file"; then
            update_file "$file" "Copyright &copy; 2000-2021 Stephen Lawrence" "Copyright \\&copy; $NEW_COPYRIGHT_RANGE Stephen Lawrence"
            ((count++))
        fi
    done < <(find "$PROJECT_ROOT/application/views" -name "*.tpl" -type f -print0)
    
    print_status "Updated $count template files" "success"
}

# Function to update documentation files
update_documentation() {
    print_status "Updating documentation files..." "info"
    local count=0
    
    # Update CREDITS.txt
    local credits_file="$PROJECT_ROOT/CREDITS.txt"
    if [[ -f "$credits_file" ]]; then
        if grep -q "All code copyright Stephen Lawrence Jr. 2002-2011" "$credits_file"; then
            update_file "$credits_file" "All code copyright Stephen Lawrence Jr. 2002-2011" "All code copyright Stephen Lawrence Jr. 2002-$CURRENT_YEAR"
            ((count++))
        fi
    fi
    
    print_status "Updated $count documentation files" "success"
}

# Function to show summary
show_summary() {
    print_status "Update Summary" "info"
    echo ""
    
    # Count files that still have old copyright
    local remaining_2021=$(find "$PROJECT_ROOT" -name "*.php" -o -name "*.tpl" | xargs grep -l "2000-2021" 2>/dev/null | wc -l || echo "0")
    local remaining_2011=$(find "$PROJECT_ROOT" -name "*.txt" | xargs grep -l "2002-2011" 2>/dev/null | wc -l || echo "0")
    
    if [[ "$remaining_2021" -eq 0 && "$remaining_2011" -eq 0 ]]; then
        print_status "All copyright years successfully updated to $CURRENT_YEAR!" "success"
    else
        print_status "Some files may still need manual review:" "warning"
        if [[ "$remaining_2021" -gt 0 ]]; then
            echo "  - $remaining_2021 files still contain '2000-2021'"
        fi
        if [[ "$remaining_2011" -gt 0 ]]; then
            echo "  - $remaining_2011 files still contain '2002-2011'"
        fi
    fi
    
    echo ""
    print_status "Backup location: $BACKUP_DIR" "info"
    echo ""
    echo "If you need to restore any files:"
    echo "  cp $BACKUP_DIR/path/to/file $PROJECT_ROOT/path/to/file"
}

# Function to verify updates
verify_updates() {
    print_status "Verifying updates..." "info"
    
    # Show sample of updated files
    echo ""
    echo "Sample of updated copyright notices:"
    echo "======================================"
    
    # Find a few updated files and show the new copyright
    local sample_php=$(find "$PROJECT_ROOT/application" -name "*.php" -type f | head -3)
    local sample_tpl=$(find "$PROJECT_ROOT/application/views" -name "*.tpl" -type f | head -2)
    
    for file in $sample_php; do
        local relative_path="${file#$PROJECT_ROOT/}"
        local copyright_line=$(grep -n "Copyright.*Stephen Lawrence" "$file" | head -1 | cut -d: -f2- | sed 's/^[[:space:]]*//')
        if [[ -n "$copyright_line" ]]; then
            echo "  $relative_path:"
            echo "    $copyright_line"
        fi
    done
    
    for file in $sample_tpl; do
        local relative_path="${file#$PROJECT_ROOT/}"
        local copyright_line=$(grep -n "Copyright.*Stephen Lawrence" "$file" | head -1 | cut -d: -f2- | sed 's/^[[:space:]]*//')
        if [[ -n "$copyright_line" ]]; then
            echo "  $relative_path:"
            echo "    $copyright_line"
        fi
    done
}

# Main execution
main() {
    # Change to project root
    cd "$PROJECT_ROOT"
    
    # Confirmation prompt
    echo -e "${YELLOW}This will modify files in your project.${NC}"
    echo -e "${YELLOW}A backup will be created at: $BACKUP_DIR${NC}"
    echo ""
    read -p "Do you want to continue? (y/N): " -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_status "Operation cancelled by user" "info"
        exit 0
    fi
    
    # Perform updates
    print_status "Starting copyright year updates..." "info"
    echo ""
    
    update_php_files
    echo ""
    
    update_template_files  
    echo ""
    
    update_documentation
    echo ""
    
    verify_updates
    echo ""
    
    show_summary
    
    print_status "Copyright update completed!" "success"
    echo ""
    echo "Next steps:"
    echo "  1. Review the changes: git diff"
    echo "  2. Test the application"
    echo "  3. Commit the changes: git add . && git commit -m 'Update copyright years to $CURRENT_YEAR'"
    echo ""
    echo "To set up automatic copyright updates, run:"
    echo "  ./scripts/setup-dynamic-copyright.sh"
}

# Check if running from correct location
if [[ ! -f "$PROJECT_ROOT/Makefile" ]]; then
    print_status "Error: This script must be run from within the OpenDocMan project" "error"
    print_status "Current directory: $(pwd)" "info"
    print_status "Project root should contain: Makefile" "info"
    exit 1
fi

# Run main function
main "$@"