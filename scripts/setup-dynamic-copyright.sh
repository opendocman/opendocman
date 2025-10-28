#!/bin/bash

# OpenDocMan Dynamic Copyright System Setup
# This script sets up automatic copyright year updates that change dynamically
# based on the current year, eliminating the need for manual yearly updates

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CURRENT_YEAR=$(date +%Y)

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}OpenDocMan Dynamic Copyright System Setup${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""
echo "This script will set up automatic copyright year updates."
echo "Copyright years will automatically show the current year: $CURRENT_YEAR"
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
    esac
}

# Create backup directory
BACKUP_DIR="$PROJECT_ROOT/dynamic-copyright-backup-$(date +%Y%m%d_%H%M%S)"
print_status "Creating backup directory: $BACKUP_DIR" "info"
mkdir -p "$BACKUP_DIR"

# Create copyright helper class
create_copyright_helper() {
    print_status "Creating copyright helper class..." "info"
    
    local helper_file="$PROJECT_ROOT/application/controllers/helpers/copyright_helper.php"
    local helper_dir="$(dirname "$helper_file")"
    
    # Create directory if it doesn't exist
    mkdir -p "$helper_dir"
    
    cat > "$helper_file" << 'EOF'
<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Copyright Helper Class
 * Provides dynamic copyright year functionality for OpenDocMan
 * 
 * This class automatically generates copyright notices with current year,
 * eliminating the need for manual yearly updates.
 */
class CopyrightHelper
{
    /**
     * Get the current copyright year range
     * 
     * @param int $startYear The starting year (default: 2000)
     * @return string Copyright year range (e.g., "2000-2025")
     */
    public static function getYearRange($startYear = 2000)
    {
        $currentYear = (int)date('Y');
        
        if ($currentYear <= $startYear) {
            return (string)$startYear;
        }
        
        return $startYear . '-' . $currentYear;
    }
    
    /**
     * Get full copyright notice for display in UI
     * 
     * @param string $holder Copyright holder name
     * @param int $startYear Starting year
     * @return string Full copyright notice
     */
    public static function getNotice($holder = 'Stephen Lawrence Jr.', $startYear = 2000)
    {
        return 'Copyright &copy; ' . self::getYearRange($startYear) . ' ' . $holder;
    }
    
    /**
     * Get copyright notice for source code headers
     * 
     * @param string $holder Copyright holder name  
     * @param int $startYear Starting year
     * @return string Source code copyright notice
     */
    public static function getSourceNotice($holder = 'Stephen Lawrence', $startYear = 2000)
    {
        return 'Copyright (C) ' . self::getYearRange($startYear) . '. ' . $holder;
    }
    
    /**
     * Get copyright year for specific component with different start year
     * 
     * @param int $startYear Component-specific start year
     * @param string $holder Copyright holder name
     * @return string Copyright notice
     */
    public static function getComponentNotice($startYear, $holder = 'Stephen Lawrence')
    {
        return 'Copyright (C) ' . self::getYearRange($startYear) . '. ' . $holder;
    }
}

// Global functions for template use
if (!function_exists('get_copyright_years')) {
    /**
     * Global function to get copyright year range
     * Available in templates and throughout the application
     */
    function get_copyright_years($startYear = 2000) {
        return CopyrightHelper::getYearRange($startYear);
    }
}

if (!function_exists('get_copyright_notice')) {
    /**
     * Global function to get full copyright notice
     * Available in templates and throughout the application
     */
    function get_copyright_notice($holder = 'Stephen Lawrence Jr.', $startYear = 2000) {
        return CopyrightHelper::getNotice($holder, $startYear);
    }
}
EOF
    
    print_status "Copyright helper created at: application/controllers/helpers/copyright_helper.php" "success"
}

# Update footer templates to use dynamic copyright
update_footer_templates() {
    print_status "Updating footer templates with dynamic copyright..." "info"
    
    # Update default theme footer
    local default_footer="$PROJECT_ROOT/application/views/default/footer.tpl"
    if [[ -f "$default_footer" ]]; then
        print_status "Backing up and updating default footer template..." "info"
        cp "$default_footer" "$BACKUP_DIR/default_footer.tpl"
        
        # Replace static copyright with dynamic version
        sed -i.tmp 's/Copyright &copy; 2000-[0-9][0-9][0-9][0-9] Stephen Lawrence Jr\./<?php echo get_copyright_notice(); ?>/g' "$default_footer"
        rm "$default_footer.tmp"
        
        print_status "✓ Updated default theme footer" "success"
    fi
    
    # Update tweeter theme footer  
    local tweeter_footer="$PROJECT_ROOT/application/views/tweeter/footer.tpl"
    if [[ -f "$tweeter_footer" ]]; then
        print_status "Backing up and updating tweeter footer template..." "info"
        cp "$tweeter_footer" "$BACKUP_DIR/tweeter_footer.tpl"
        
        # Replace static copyright with dynamic version
        sed -i.tmp 's/<p>Copyright &copy; 2000-[0-9][0-9][0-9][0-9] Stephen Lawrence<\/p>/<p><?php echo get_copyright_notice("Stephen Lawrence"); ?><\/p>/g' "$tweeter_footer"
        rm "$tweeter_footer.tmp"
        
        print_status "✓ Updated tweeter theme footer" "success"
    fi
}

# Create initialization file to load copyright helper
create_copyright_init() {
    print_status "Creating copyright initialization..." "info"
    
    local init_file="$PROJECT_ROOT/application/includes/copyright_init.php"
    
    cat > "$init_file" << 'EOF'
<?php
/*
 * Copyright Initialization
 * Automatically loads the copyright helper for use throughout OpenDocMan
 */

// Load copyright helper
require_once dirname(__FILE__) . '/../controllers/helpers/copyright_helper.php';

// Make copyright functions available globally
$GLOBALS['copyright_helper'] = new CopyrightHelper();

// Set template variables for copyright
if (isset($GLOBALS['smarty'])) {
    $smarty = $GLOBALS['smarty'];
    $smarty->assign('copyright_years', get_copyright_years());
    $smarty->assign('copyright_notice', get_copyright_notice());
    $smarty->assign('current_year', date('Y'));
}
EOF
    
    print_status "Copyright initialization created" "success"
}

# Create Smarty plugin for template use
create_smarty_plugin() {
    print_status "Creating Smarty template plugin..." "info"
    
    local plugin_dir="$PROJECT_ROOT/application/views/common/plugins"
    local plugin_file="$plugin_dir/function.copyright.php"
    
    mkdir -p "$plugin_dir"
    
    cat > "$plugin_file" << 'EOF'
<?php
/*
 * Smarty plugin for dynamic copyright display
 * Usage in templates: {copyright} or {copyright holder="Custom Name" start="2010"}
 */

function smarty_function_copyright($params, $smarty) {
    // Load copyright helper if not already loaded
    if (!class_exists('CopyrightHelper')) {
        require_once dirname(__FILE__) . '/../../../controllers/helpers/copyright_helper.php';
    }
    
    // Get parameters with defaults
    $holder = isset($params['holder']) ? $params['holder'] : 'Stephen Lawrence Jr.';
    $startYear = isset($params['start']) ? (int)$params['start'] : 2000;
    $format = isset($params['format']) ? $params['format'] : 'html'; // html or text
    
    if ($format === 'text') {
        return CopyrightHelper::getSourceNotice($holder, $startYear);
    } else {
        return CopyrightHelper::getNotice($holder, $startYear);
    }
}
EOF
    
    print_status "Smarty copyright plugin created" "success"
}

# Update template files to use new dynamic system
update_templates_with_smarty() {
    print_status "Converting templates to use Smarty copyright plugin..." "info"
    
    # Update default footer with Smarty plugin
    local default_footer="$PROJECT_ROOT/application/views/default/footer.tpl"
    if [[ -f "$default_footer" ]]; then
        # Use Smarty plugin instead of PHP
        sed -i.tmp 's/<?php echo get_copyright_notice(); ?>/{copyright}/g' "$default_footer"
        rm "$default_footer.tmp"
        print_status "✓ Updated default footer to use Smarty plugin" "success"
    fi
    
    # Update tweeter footer with Smarty plugin
    local tweeter_footer="$PROJECT_ROOT/application/views/tweeter/footer.tpl"  
    if [[ -f "$tweeter_footer" ]]; then
        # Use Smarty plugin instead of PHP
        sed -i.tmp 's/<p><?php echo get_copyright_notice("Stephen Lawrence"); ?><\/p>/<p>{copyright holder="Stephen Lawrence"}<\/p>/g' "$tweeter_footer"
        rm "$tweeter_footer.tmp"
        print_status "✓ Updated tweeter footer to use Smarty plugin" "success"
    fi
}

# Create maintenance script for yearly updates (optional fallback)
create_maintenance_script() {
    print_status "Creating maintenance script..." "info"
    
    local maintenance_file="$PROJECT_ROOT/scripts/update-copyright-fallback.sh"
    
    cat > "$maintenance_file" << 'EOF'
#!/bin/bash
# Fallback script to update any remaining static copyright years
# Run this annually if needed, or set up as a cron job

CURRENT_YEAR=$(date +%Y)
PROJECT_ROOT="$(dirname "$(dirname "$(readlink -f "$0")")")"

echo "Updating any remaining static copyright years to $CURRENT_YEAR..."

# Update any missed PHP files with old static dates
find "$PROJECT_ROOT" -name "*.php" -type f -exec sed -i "s/Copyright (C) 2000-[0-9][0-9][0-9][0-9]\./Copyright (C) 2000-$CURRENT_YEAR./g" {} \;

# Update any missed template files  
find "$PROJECT_ROOT" -name "*.tpl" -type f -exec sed -i "s/Copyright &copy; 2000-[0-9][0-9][0-9][0-9]/Copyright \\&copy; 2000-$CURRENT_YEAR/g" {} \;

echo "Fallback copyright update completed for year $CURRENT_YEAR"
EOF
    
    chmod +x "$maintenance_file"
    print_status "Maintenance script created at: scripts/update-copyright-fallback.sh" "success"
}

# Create documentation for the dynamic system
create_documentation() {
    print_status "Creating documentation..." "info"
    
    local doc_file="$PROJECT_ROOT/docs/DYNAMIC_COPYRIGHT.md"
    mkdir -p "$(dirname "$doc_file")"
    
    cat > "$doc_file" << 'EOF'
# Dynamic Copyright System

OpenDocMan now uses a dynamic copyright system that automatically updates copyright years based on the current year, eliminating the need for manual yearly updates.

## How It Works

The system consists of several components:

### 1. Copyright Helper Class
- **File**: `application/controllers/helpers/copyright_helper.php`
- **Purpose**: Provides methods to generate dynamic copyright notices
- **Methods**:
  - `getYearRange($startYear)` - Returns year range (e.g., "2000-2025")
  - `getNotice($holder, $startYear)` - Returns HTML copyright notice
  - `getSourceNotice($holder, $startYear)` - Returns source code copyright notice

### 2. Smarty Template Plugin
- **File**: `application/views/common/plugins/function.copyright.php`
- **Purpose**: Allows dynamic copyright in Smarty templates
- **Usage**: `{copyright}` or `{copyright holder="Custom Name" start="2010"}`

### 3. Global Functions
Available throughout the application:
- `get_copyright_years($startYear)` - Get year range
- `get_copyright_notice($holder, $startYear)` - Get full notice

## Usage Examples

### In Templates (Smarty)
```smarty
{* Basic usage *}
{copyright}

{* Custom holder and start year *}
{copyright holder="Custom Company" start="2010"}

{* Text format for source code *}
{copyright format="text"}
```

### In PHP Code
```php
// Get current year range
$years = get_copyright_years();  // Returns "2000-2025"

// Get full notice
$notice = get_copyright_notice(); // Returns "Copyright © 2000-2025 Stephen Lawrence Jr."

// Component-specific copyright
$component_notice = CopyrightHelper::getComponentNotice(2008, 'Stephen Lawrence');
```

## Benefits

1. **Automatic Updates**: Copyright years update automatically each year
2. **Consistency**: Same format used throughout the application  
3. **Maintenance-Free**: No need for annual bulk updates
4. **Flexibility**: Easy to customize for different components
5. **Backward Compatible**: Existing static notices still work

## File Locations

- **Helper Class**: `application/controllers/helpers/copyright_helper.php`
- **Smarty Plugin**: `application/views/common/plugins/function.copyright.php`
- **Initialization**: `application/includes/copyright_init.php`
- **Templates**: Updated footer templates in `application/views/*/footer.tpl`

## Maintenance

The system is designed to be maintenance-free. However, a fallback script is available:

```bash
./scripts/update-copyright-fallback.sh
```

This can be run annually or set up as a cron job to catch any missed static copyright notices.

## Migration

Existing static copyright notices have been converted to use the dynamic system. The conversion process:

1. Backed up original files
2. Created dynamic copyright helper
3. Updated template files to use Smarty plugin
4. Created fallback maintenance script

## Testing

Test the dynamic copyright system:

1. Check footer displays current year
2. Verify copyright helper functions work
3. Test Smarty plugin in templates
4. Confirm year changes automatically (can simulate by changing system date)

## Reverting Changes

If you need to revert to static copyright notices:

1. Restore files from backup directory: `dynamic-copyright-backup-[timestamp]`
2. Remove dynamic copyright files:
   - `application/controllers/helpers/copyright_helper.php`
   - `application/views/common/plugins/function.copyright.php`
   - `application/includes/copyright_init.php`

EOF
    
    print_status "Documentation created at: docs/DYNAMIC_COPYRIGHT.md" "success"
}

# Show usage examples  
show_usage_examples() {
    print_status "Dynamic Copyright Usage Examples" "info"
    echo ""
    echo "Template Usage (Smarty):"
    echo "========================"
    echo "  Basic: {copyright}"
    echo "  Custom: {copyright holder=\"Custom Name\" start=\"2010\"}"
    echo "  Text format: {copyright format=\"text\"}"
    echo ""
    echo "PHP Usage:"
    echo "=========="
    echo "  \$years = get_copyright_years();  // \"2000-$CURRENT_YEAR\""
    echo "  \$notice = get_copyright_notice(); // Full HTML notice"
    echo "  \$source = CopyrightHelper::getSourceNotice();"
    echo ""
}

# Main execution
main() {
    cd "$PROJECT_ROOT"
    
    # Confirmation
    echo -e "${YELLOW}This will set up dynamic copyright that automatically updates each year.${NC}"
    echo -e "${YELLOW}Backup will be created at: $BACKUP_DIR${NC}"
    echo ""
    read -p "Do you want to continue? (y/N): " -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_status "Operation cancelled by user" "info"
        exit 0
    fi
    
    print_status "Setting up dynamic copyright system..." "info"
    echo ""
    
    # Create all components
    create_copyright_helper
    echo ""
    
    create_copyright_init
    echo ""
    
    create_smarty_plugin
    echo ""
    
    update_footer_templates
    echo ""
    
    update_templates_with_smarty
    echo ""
    
    create_maintenance_script
    echo ""
    
    create_documentation
    echo ""
    
    show_usage_examples
    
    print_status "Dynamic copyright system setup completed!" "success"
    echo ""
    echo "What was created:"
    echo "  ✓ Copyright helper class"
    echo "  ✓ Smarty template plugin"  
    echo "  ✓ Global copyright functions"
    echo "  ✓ Updated footer templates"
    echo "  ✓ Maintenance script (fallback)"
    echo "  ✓ Documentation"
    echo ""
    echo "The copyright will now automatically show: 2000-$CURRENT_YEAR"
    echo ""
    echo "Next steps:"
    echo "  1. Test the application footers"
    echo "  2. Review docs/DYNAMIC_COPYRIGHT.md" 
    echo "  3. Commit changes: git add . && git commit -m 'Add dynamic copyright system'"
    echo ""
    print_status "Copyright years will now update automatically each year!" "success"
}

# Check if running from correct location
if [[ ! -f "$PROJECT_ROOT/Makefile" ]]; then
    print_status "Error: This script must be run from within the OpenDocMan project" "error"
    exit 1
fi

# Run main function
main "$@"