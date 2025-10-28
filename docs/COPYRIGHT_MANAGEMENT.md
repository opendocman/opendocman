# Copyright Management Guide

This guide explains how to manage copyright years in OpenDocMan, providing both manual update and automatic dynamic copyright solutions.

## Overview

OpenDocMan includes comprehensive copyright management tools that handle:
- **Bulk updates** of copyright years across all source files
- **Dynamic copyright** that automatically updates each year
- **Maintenance scripts** for ongoing copyright management

## Current Status

As of 2025, the codebase has mixed copyright years:
- **Outdated**: 93+ files still show "2000-2021"
- **Current**: Few files show "2000-2025" 
- **UI Display**: Footer templates show "2000-2021"

## Quick Start

### Check Current Copyright Status
```bash
make copyright-check
```

### Option 1: Bulk Update to 2025
```bash
make copyright-update
```

### Option 2: Set Up Dynamic Copyright (Recommended)
```bash
make copyright-dynamic
```

## Solution 1: Bulk Update Script

### What It Does
The bulk update script (`scripts/update-copyright-years.sh`) will:
- ✅ Update all PHP source files from "2000-2021" to "2000-2025"
- ✅ Update template files in both themes
- ✅ Update documentation files
- ✅ Create automatic backups before changes
- ✅ Provide detailed verification

### Usage
```bash
# Interactive update with confirmation
./scripts/update-copyright-years.sh

# Or use the Make target
make copyright-update
```

### What Gets Updated
- **93+ PHP files**: `Copyright (C) 2000-2021` → `Copyright (C) 2000-2025`
- **Template files**: `Copyright &copy; 2000-2021` → `Copyright &copy; 2000-2025`
- **Documentation**: `2002-2011` → `2002-2025`

### Safety Features
- 🛡️ **Automatic backups** in `copyright-backup-[timestamp]/`
- 🛡️ **Confirmation prompts** before making changes
- 🛡️ **Verification** of updates
- 🛡️ **Rollback instructions** provided

## Solution 2: Dynamic Copyright System (Recommended)

### What It Does
The dynamic copyright system automatically updates copyright years based on the current year, eliminating future manual updates.

### Usage
```bash
# Set up dynamic system
./scripts/setup-dynamic-copyright.sh

# Or use the Make target  
make copyright-dynamic
```

### Components Created

#### 1. Copyright Helper Class
**File**: `application/controllers/helpers/copyright_helper.php`

Provides dynamic copyright functionality:
```php
// Get current year range
$years = CopyrightHelper::getYearRange(2000);  // "2000-2025"

// Get full HTML notice
$notice = CopyrightHelper::getNotice('Stephen Lawrence Jr.');

// Get source code notice
$source = CopyrightHelper::getSourceNotice('Stephen Lawrence');
```

#### 2. Smarty Template Plugin
**File**: `application/views/common/plugins/function.copyright.php`

Allows dynamic copyright in templates:
```smarty
{* Basic usage *}
{copyright}

{* Custom holder and start year *}
{copyright holder="Custom Company" start="2010"}

{* Text format for source code *}
{copyright format="text"}
```

#### 3. Global Functions
Available throughout the application:
```php
$years = get_copyright_years(2000);     // "2000-2025"
$notice = get_copyright_notice();       // Full HTML notice
```

### Benefits of Dynamic System
- 🔄 **Automatic updates** - Changes every year automatically
- 🎯 **Consistency** - Same format throughout application
- 🚀 **Maintenance-free** - No manual yearly updates needed
- 🔧 **Flexible** - Easy to customize per component
- ⚡ **Performance** - Minimal overhead

## File Locations

### Copyright Management Scripts
```
scripts/
├── update-copyright-years.sh     # Bulk update to 2025
├── setup-dynamic-copyright.sh    # Set up dynamic system
└── update-copyright-fallback.sh  # Fallback maintenance (created by dynamic setup)
```

### Dynamic System Files (Created by setup)
```
application/
├── controllers/helpers/copyright_helper.php    # Core helper class
├── includes/copyright_init.php                 # Initialization
└── views/common/plugins/function.copyright.php # Smarty plugin
```

### Updated Templates
```
application/views/
├── default/footer.tpl     # Updated with dynamic copyright
└── tweeter/footer.tpl     # Updated with dynamic copyright
```

## Make Commands

| Command | Description |
|---------|-------------|
| `make copyright-check` | Check current copyright status |
| `make copyright-update` | Bulk update to 2025 |
| `make copyright-dynamic` | Set up automatic system |

## Best Practices

### For New Development
1. **Use dynamic functions** in new code:
   ```php
   $notice = get_copyright_notice();
   ```

2. **Use Smarty plugin** in templates:
   ```smarty
   {copyright}
   ```

3. **Component-specific copyright**:
   ```php
   $custom = CopyrightHelper::getComponentNotice(2008, 'Custom Author');
   ```

### For Maintenance
1. **Run annual check** (if not using dynamic system):
   ```bash
   make copyright-check
   ```

2. **Use fallback script** if needed:
   ```bash
   ./scripts/update-copyright-fallback.sh
   ```

## Comparison: Manual vs Dynamic

| Feature | Manual Updates | Dynamic System |
|---------|---------------|----------------|
| **Maintenance** | Annual manual work | Automatic |
| **Consistency** | Risk of missed files | Guaranteed consistency |
| **Performance** | No runtime cost | Minimal runtime calculation |
| **Flexibility** | Static, needs editing | Configurable parameters |
| **Future-proof** | Requires yearly attention | Self-maintaining |

## Troubleshooting

### Script Permissions
If scripts don't run:
```bash
chmod +x scripts/update-copyright-years.sh
chmod +x scripts/setup-dynamic-copyright.sh
```

### Reverting Changes
Both scripts create backups:
```bash
# Find backup directories
ls -la *copyright*backup*

# Restore specific file
cp copyright-backup-20251014/path/to/file path/to/file
```

### Dynamic System Issues
If dynamic copyright doesn't work:
1. Check if helper class is loaded
2. Verify Smarty plugin exists
3. Test with PHP functions directly
4. Check template syntax

### Mixed Copyright Years
If you have both systems:
```bash
# Check what needs updating
make copyright-check

# Clean up with fallback script
./scripts/update-copyright-fallback.sh
```

## Implementation Timeline

### Immediate (Choose One)
- **Quick Fix**: Run `make copyright-update` (updates to 2025)
- **Long-term**: Run `make copyright-dynamic` (automatic system)

### Recommended Approach
1. **First**: Set up dynamic system (`make copyright-dynamic`)
2. **Then**: Test footers show current year
3. **Finally**: Commit changes and enjoy automatic updates

## Testing

### Test Bulk Updates
```bash
# Check before
make copyright-check

# Run update  
make copyright-update

# Verify after
make copyright-check
```

### Test Dynamic System
```bash
# Set up system
make copyright-dynamic

# Check templates show current year
curl -s http://localhost:8080 | grep -i copyright

# Test helper functions
php -r "require 'application/controllers/helpers/copyright_helper.php'; echo CopyrightHelper::getYearRange();"
```

## Support

### Documentation
- **This guide**: `docs/COPYRIGHT_MANAGEMENT.md`
- **Dynamic system**: `docs/DYNAMIC_COPYRIGHT.md` (created by setup)
- **Script help**: `./scripts/update-copyright-years.sh --help`

### Common Issues
- **Permission errors**: Check script execute permissions
- **Backup space**: Ensure sufficient disk space for backups
- **Template caching**: Clear Smarty cache after changes
- **Version conflicts**: Test in development environment first

## Conclusion

The dynamic copyright system is the recommended long-term solution as it:
- Eliminates yearly maintenance tasks
- Ensures consistent copyright notices
- Automatically adapts to future years
- Provides flexibility for different components

For immediate needs, the bulk update script provides a quick fix to bring all copyright years current to 2025.

Both solutions include comprehensive backups and safety measures to ensure your codebase remains stable throughout the update process.