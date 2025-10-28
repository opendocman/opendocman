/**
 * jQuery Compatibility Shim for OpenDocMan
 * Fixes compatibility issues between jQuery 1.7.1 and 1.12.4
 * 
 * This file should be loaded after jQuery but before jquery.validate.min.js
 */

(function($) {
    'use strict';
    
    // Fix for jQuery Validate plugin compatibility
    if ($.fn.validate) {
        // Store original validate method
        var originalValidate = $.fn.validate;
        
        // Override validate method to handle compatibility issues
        $.fn.validate = function(options) {
            // Default options for better compatibility
            var defaults = {
                errorClass: 'error',
                validClass: 'valid',
                errorElement: 'label',
                focusInvalid: true,
                errorContainer: $([]),
                errorLabelContainer: $([]),
                onsubmit: true,
                ignore: ':hidden',
                onfocusin: function(element) {
                    this.lastActive = element;
                    
                    // Remove error styling on focus
                    if (this.settings.focusCleanup && !this.blockFocusCleanup) {
                        if (this.settings.unhighlight) {
                            this.settings.unhighlight.call(this, element, this.settings.errorClass, this.settings.validClass);
                        }
                        this.addWrapper(this.errorsFor(element)).hide();
                    }
                },
                onfocusout: function(element) {
                    if (!this.checkable(element) && (element.name in this.submitted || !this.optional(element))) {
                        this.element(element);
                    }
                },
                onkeyup: function(element, event) {
                    if (event.which === 9 && this.elementValue(element) === '') {
                        return;
                    } else if (element.name in this.submitted || element === this.lastElement) {
                        this.element(element);
                    }
                }
            };
            
            // Merge with user options
            options = $.extend(true, {}, defaults, options || {});
            
            // Call original validate method
            return originalValidate.call(this, options);
        };
    }
    
    // Fix for live() method which was removed in jQuery 1.9+
    if (!$.fn.live) {
        $.fn.live = function(events, handler) {
            $(document).on(events, this.selector, handler);
            return this;
        };
    }
    
    // Fix for die() method which was removed in jQuery 1.9+
    if (!$.fn.die) {
        $.fn.die = function(events, handler) {
            $(document).off(events, this.selector, handler);
            return this;
        };
    }
    
    // Fix for browser detection which was removed in jQuery 1.9+
    if (!$.browser) {
        $.browser = {};
        $.browser.mozilla = /mozilla/.test(navigator.userAgent.toLowerCase()) && !/webkit/.test(navigator.userAgent.toLowerCase());
        $.browser.webkit = /webkit/.test(navigator.userAgent.toLowerCase());
        $.browser.opera = /opera/.test(navigator.userAgent.toLowerCase());
        $.browser.msie = /msie/.test(navigator.userAgent.toLowerCase());
        
        // Version detection
        var match = /(chrome)[ \/]([\w.]+)/.exec(navigator.userAgent.toLowerCase()) ||
                   /(webkit)[ \/]([\w.]+)/.exec(navigator.userAgent.toLowerCase()) ||
                   /(opera)(?:.*version|)[ \/]([\w.]+)/.exec(navigator.userAgent.toLowerCase()) ||
                   /(msie) ([\w.]+)/.exec(navigator.userAgent.toLowerCase()) ||
                   navigator.userAgent.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(navigator.userAgent.toLowerCase()) ||
                   [];
                   
        $.browser.version = match[2] || "0";
        $.browser[match[1] || ""] = true;
    }
    
    // Fix for toggle() event method which was removed in jQuery 1.9+
    if (!$.fn.toggle || $.fn.toggle.length < 2) {
        $.fn.toggle = function(fn1, fn2) {
            // If no arguments, use the original toggle for show/hide
            if (arguments.length === 0) {
                return this.each(function() {
                    var $this = $(this);
                    if ($this.is(':hidden')) {
                        $this.show();
                    } else {
                        $this.hide();
                    }
                });
            }
            
            // Store the functions and add click handler
            return this.each(function() {
                var $this = $(this);
                var data = $this.data('toggle-state') || 0;
                
                $this.off('click.toggle').on('click.toggle', function(e) {
                    var currentState = $this.data('toggle-state') || 0;
                    if (currentState === 0) {
                        fn1.call(this, e);
                        $this.data('toggle-state', 1);
                    } else {
                        fn2.call(this, e);
                        $this.data('toggle-state', 0);
                    }
                });
            });
        };
    }
    
    // Fix for $.sub() which was removed in jQuery 1.9+
    if (!$.sub) {
        $.sub = function() {
            function jQuerySub(selector, context) {
                return new jQuerySub.fn.init(selector, context);
            }
            jQuery.extend(true, jQuerySub, this);
            jQuerySub.superclass = this;
            jQuerySub.fn = jQuerySub.prototype = this();
            jQuerySub.fn.constructor = jQuerySub;
            jQuerySub.fn.init = function init(selector, context) {
                if (context && context instanceof jQuery && !(context instanceof jQuerySub)) {
                    context = jQuerySub(context);
                }
                return jQuery.fn.init.call(this, selector, context, rootjQuerySub);
            };
            jQuerySub.fn.init.prototype = jQuerySub.fn;
            var rootjQuerySub = jQuerySub(document);
            return jQuerySub;
        };
    }
    
    // Ensure proper form validation setup
    $(document).ready(function() {
        // Re-initialize any existing forms with validation
        $('form[data-validate="true"], form.validate').each(function() {
            var $form = $(this);
            if ($form.data('validator')) {
                // Remove existing validator
                $form.removeData('validator');
            }
            // Re-apply validation
            if ($.fn.validate) {
                $form.validate();
            }
        });
        
        // Fix for forms that might not have proper validation setup
        $('form').each(function() {
            var $form = $(this);
            
            // Add basic validation for required fields if no validator exists
            if (!$form.data('validator') && $form.find('[required], .required').length > 0) {
                $form.on('submit', function(e) {
                    var hasErrors = false;
                    
                    $form.find('[required], .required').each(function() {
                        var $field = $(this);
                        var value = $field.val();
                        
                        if (!value || value.trim() === '') {
                            hasErrors = true;
                            $field.addClass('error');
                            
                            // Add error message if it doesn't exist
                            if ($field.next('.error-message').length === 0) {
                                $field.after('<span class="error-message" style="color: red; font-size: 12px;">This field is required</span>');
                            }
                        } else {
                            $field.removeClass('error');
                            $field.next('.error-message').remove();
                        }
                    });
                    
                    if (hasErrors) {
                        e.preventDefault();
                        return false;
                    }
                });
                
                // Remove error styling on input
                $form.find('[required], .required').on('input change', function() {
                    var $field = $(this);
                    if ($field.val().trim() !== '') {
                        $field.removeClass('error');
                        $field.next('.error-message').remove();
                    }
                });
            }
        });
    });
    
    // Add some basic CSS for error styling if it doesn't exist
    if ($('style#jquery-compat-css').length === 0) {
        $('<style id="jquery-compat-css">')
            .text(`
                .error {
                    border-color: #e74c3c !important;
                    background-color: #fdf2f2 !important;
                }
                .error-message {
                    display: block;
                    color: #e74c3c;
                    font-size: 12px;
                    margin-top: 2px;
                }
                .valid {
                    border-color: #27ae60 !important;
                }
            `)
            .appendTo('head');
    }
    
})(jQuery);