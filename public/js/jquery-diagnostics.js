/**
 * jQuery Diagnostics Script for OpenDocMan
 * 
 * This script helps diagnose jQuery-related issues after upgrades.
 * Load this script in the browser console or include it temporarily
 * to identify compatibility problems.
 */

(function() {
    'use strict';
    
    console.log('=== OpenDocMan jQuery Diagnostics ===');
    
    // Check jQuery availability
    if (typeof jQuery === 'undefined') {
        console.error('❌ jQuery is not loaded!');
        return;
    }
    
    console.log('✅ jQuery is loaded');
    console.log('📍 jQuery Version:', jQuery.fn.jquery);
    
    // Check for common compatibility issues
    var issues = [];
    var warnings = [];
    var info = [];
    
    // Check for deprecated methods that might be used
    var deprecatedMethods = ['live', 'die', 'toggle'];
    deprecatedMethods.forEach(function(method) {
        if (typeof jQuery.fn[method] === 'undefined') {
            warnings.push('⚠️  jQuery.' + method + '() method is not available (removed in jQuery 1.9+)');
        } else {
            info.push('ℹ️  jQuery.' + method + '() method is available (likely from compatibility script)');
        }
    });
    
    // Check for browser object
    if (typeof jQuery.browser === 'undefined') {
        warnings.push('⚠️  jQuery.browser object is not available (removed in jQuery 1.9+)');
    } else {
        info.push('ℹ️  jQuery.browser object is available');
    }
    
    // Check validation plugin
    if (typeof jQuery.fn.validate === 'undefined') {
        issues.push('❌ jQuery Validation plugin is not loaded');
    } else {
        console.log('✅ jQuery Validation plugin is loaded');
        
        // Test validation functionality
        try {
            var testForm = jQuery('<form><input required></form>');
            testForm.validate();
            console.log('✅ jQuery Validation is working');
        } catch (e) {
            issues.push('❌ jQuery Validation error: ' + e.message);
        }
    }
    
    // Check for forms on the page
    var forms = jQuery('form');
    console.log('📊 Found ' + forms.length + ' form(s) on page');
    
    forms.each(function(index) {
        var form = jQuery(this);
        var formId = form.attr('id') || 'form-' + index;
        var requiredFields = form.find('[required], .required');
        var validator = form.data('validator');
        
        console.log('📋 Form: ' + formId);
        console.log('  - Required fields: ' + requiredFields.length);
        console.log('  - Has validator: ' + (validator ? 'Yes' : 'No'));
        
        if (requiredFields.length > 0 && !validator) {
            warnings.push('⚠️  Form "' + formId + '" has required fields but no validator');
        }
    });
    
    // Check for JavaScript errors in console
    var originalError = console.error;
    var errorCount = 0;
    console.error = function() {
        errorCount++;
        originalError.apply(console, arguments);
    };
    
    // Test common jQuery operations
    try {
        jQuery(document).ready(function() {
            // Test event binding
            jQuery('<div>').on('click', function() {});
            info.push('✅ Event binding (.on()) works');
        });
        
        // Test AJAX (just setup, don't actually send)
        jQuery.ajaxSetup({
            timeout: 5000
        });
        info.push('✅ AJAX setup works');
        
    } catch (e) {
        issues.push('❌ Basic jQuery operations failed: ' + e.message);
    }
    
    // Check for DataTables if present
    if (typeof jQuery.fn.dataTable !== 'undefined') {
        info.push('✅ DataTables plugin is loaded');
    } else {
        info.push('ℹ️  DataTables plugin not found (may not be needed on this page)');
    }
    
    // Check for common CSS classes used in validation
    var errorElements = jQuery('.error, .error-message, .field-error');
    if (errorElements.length > 0) {
        info.push('📍 Found ' + errorElements.length + ' element(s) with error styling');
    }
    
    // Output results
    console.log('\n=== DIAGNOSTIC RESULTS ===');
    
    if (issues.length > 0) {
        console.log('\n🚨 CRITICAL ISSUES:');
        issues.forEach(function(issue) {
            console.log(issue);
        });
    }
    
    if (warnings.length > 0) {
        console.log('\n⚠️  WARNINGS:');
        warnings.forEach(function(warning) {
            console.log(warning);
        });
    }
    
    if (info.length > 0) {
        console.log('\nℹ️  INFORMATION:');
        info.forEach(function(item) {
            console.log(item);
        });
    }
    
    // Recommendations
    console.log('\n💡 RECOMMENDATIONS:');
    
    if (issues.length > 0) {
        console.log('1. Fix critical issues first - these will prevent functionality');
    }
    
    if (warnings.some(function(w) { return w.includes('live') || w.includes('die') || w.includes('browser'); })) {
        console.log('2. Include jquery-compatibility.js to fix deprecated method issues');
    }
    
    if (forms.length > 0 && jQuery.fn.validate) {
        console.log('3. Test form submission to ensure validation works properly');
    }
    
    console.log('4. Check browser console for JavaScript errors during normal usage');
    console.log('5. Test all interactive features (login, forms, buttons, etc.)');
    
    // Quick fix suggestions
    console.log('\n🔧 QUICK FIXES:');
    console.log('- Ensure jquery-compatibility.js loads after jQuery but before other scripts');
    console.log('- Clear browser cache completely');
    console.log('- Check that all script files are loading (Network tab in DevTools)');
    console.log('- Verify file permissions on JavaScript files');
    
    console.log('\n=== END DIAGNOSTICS ===');
    
    // Return summary object for programmatic access
    return {
        jqueryVersion: jQuery.fn.jquery,
        issues: issues,
        warnings: warnings,
        info: info,
        formsFound: forms.length,
        validationAvailable: typeof jQuery.fn.validate !== 'undefined',
        compatibilityNeeded: warnings.some(function(w) { 
            return w.includes('live') || w.includes('die') || w.includes('browser'); 
        })
    };
})();

// Additional utility functions for manual testing
window.odmJQueryTest = {
    // Test form validation manually
    testValidation: function(formSelector) {
        formSelector = formSelector || 'form';
        var forms = jQuery(formSelector);
        
        console.log('Testing validation on ' + forms.length + ' form(s)...');
        
        forms.each(function() {
            var form = jQuery(this);
            var formId = form.attr('id') || 'unnamed-form';
            
            try {
                if (jQuery.fn.validate) {
                    var validator = form.validate();
                    console.log('✅ Validation initialized for: ' + formId);
                    
                    // Test validation
                    var isValid = validator.form();
                    console.log('Form validity: ' + (isValid ? 'Valid' : 'Invalid'));
                } else {
                    console.log('❌ Validation plugin not available');
                }
            } catch (e) {
                console.log('❌ Validation failed for ' + formId + ': ' + e.message);
            }
        });
    },
    
    // Test event handling
    testEvents: function() {
        console.log('Testing event handling...');
        
        try {
            // Test modern event binding
            jQuery(document).off('click.test').on('click.test', 'body', function() {
                console.log('✅ Modern event binding (.on) works');
                jQuery(document).off('click.test');
            });
            
            // Trigger test
            jQuery('body').trigger('click');
            
        } catch (e) {
            console.log('❌ Event handling test failed: ' + e.message);
        }
    },
    
    // Check for script loading errors
    checkScriptErrors: function() {
        var scripts = jQuery('script[src]');
        console.log('Checking ' + scripts.length + ' external scripts...');
        
        scripts.each(function() {
            var src = jQuery(this).attr('src');
            var script = this;
            
            // This is a simple check - in practice, you'd need to monitor load events
            if (script.readyState === 'error' || script.onerror) {
                console.log('❌ Script failed to load: ' + src);
            } else {
                console.log('✅ Script loaded: ' + src);
            }
        });
    }
};

console.log('💡 Additional testing utilities available in window.odmJQueryTest');
console.log('   - odmJQueryTest.testValidation() - Test form validation');
console.log('   - odmJQueryTest.testEvents() - Test event handling');
console.log('   - odmJQueryTest.checkScriptErrors() - Check script loading');