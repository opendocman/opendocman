/**
 * CSRF Helper for OpenDocMan
 * 
 * This JavaScript module provides helper functions to handle CSRF tokens
 * in AJAX requests and dynamic form submissions.
 * 
 * Copyright (C) 2000-2021. Stephen Lawrence
 */

(function(window) {
    'use strict';
    
    var CsrfHelper = {
        
        /**
         * Get CSRF token from the page
         * Looks for token in meta tags, hidden inputs, or global variables
         */
        getToken: function() {
            // First try to get from meta tag
            var metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                return metaToken.getAttribute('content');
            }
            
            // Try to get from hidden input in forms
            var hiddenInput = document.querySelector('input[name*="csrf"], input[name*="token"]');
            if (hiddenInput) {
                return hiddenInput.value;
            }
            
            // Try to get from global variable if set by Smarty
            if (window.csrf_token) {
                return window.csrf_token;
            }
            
            return null;
        },
        
        /**
         * Get CSRF token field name
         */
        getTokenName: function() {
            var hiddenInput = document.querySelector('input[name*="csrf"], input[name*="token"]');
            if (hiddenInput) {
                return hiddenInput.name;
            }
            
            // Default field name used by Paragonie Anti-CSRF
            return 'csrf_token';
        },
        
        /**
         * Add CSRF token to form data
         */
        addTokenToFormData: function(formData) {
            var token = this.getToken();
            var tokenName = this.getTokenName();
            
            if (token && tokenName) {
                if (formData instanceof FormData) {
                    formData.append(tokenName, token);
                } else if (typeof formData === 'object') {
                    formData[tokenName] = token;
                }
            }
            
            return formData;
        },
        
        /**
         * Add CSRF token to URL parameters
         */
        addTokenToUrl: function(url) {
            var token = this.getToken();
            var tokenName = this.getTokenName();
            
            if (token && tokenName) {
                var separator = url.indexOf('?') === -1 ? '?' : '&';
                url += separator + encodeURIComponent(tokenName) + '=' + encodeURIComponent(token);
            }
            
            return url;
        },
        
        /**
         * Enhanced AJAX wrapper that automatically includes CSRF token
         */
        ajax: function(options) {
            options = options || {};
            
            // Add CSRF token to data
            if (options.method && options.method.toUpperCase() !== 'GET') {
                if (options.data) {
                    if (typeof options.data === 'string') {
                        // Parse query string and add token
                        var params = new URLSearchParams(options.data);
                        var token = this.getToken();
                        var tokenName = this.getTokenName();
                        if (token && tokenName) {
                            params.append(tokenName, token);
                            options.data = params.toString();
                        }
                    } else {
                        // Add to object or FormData
                        options.data = this.addTokenToFormData(options.data);
                    }
                } else {
                    // Create new data object with token
                    var token = this.getToken();
                    var tokenName = this.getTokenName();
                    if (token && tokenName) {
                        options.data = {};
                        options.data[tokenName] = token;
                    }
                }
            }
            
            // Use native XMLHttpRequest or jQuery if available
            if (window.jQuery && window.jQuery.ajax) {
                return window.jQuery.ajax(options);
            } else {
                return this.nativeAjax(options);
            }
        },
        
        /**
         * Native XMLHttpRequest implementation
         */
        nativeAjax: function(options) {
            return new Promise(function(resolve, reject) {
                var xhr = new XMLHttpRequest();
                var method = options.method || options.type || 'GET';
                var url = options.url || '';
                var data = options.data || null;
                
                xhr.open(method.toUpperCase(), url, true);
                
                // Set headers
                if (options.headers) {
                    for (var header in options.headers) {
                        xhr.setRequestHeader(header, options.headers[header]);
                    }
                }
                
                // Set content type for POST requests
                if (method.toUpperCase() !== 'GET' && !(data instanceof FormData)) {
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                }
                
                // Handle response
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            var response = xhr.responseText;
                            try {
                                response = JSON.parse(response);
                            } catch (e) {
                                // Response is not JSON, keep as text
                            }
                            resolve(response);
                        } else {
                            reject(new Error('HTTP ' + xhr.status + ': ' + xhr.statusText));
                        }
                    }
                };
                
                // Handle network errors
                xhr.onerror = function() {
                    reject(new Error('Network error'));
                };
                
                // Send request
                if (data instanceof FormData) {
                    xhr.send(data);
                } else if (typeof data === 'object' && data !== null) {
                    var params = new URLSearchParams();
                    for (var key in data) {
                        params.append(key, data[key]);
                    }
                    xhr.send(params.toString());
                } else {
                    xhr.send(data);
                }
            });
        },
        
        /**
         * Automatically add CSRF tokens to all forms on the page
         */
        protectAllForms: function() {
            var forms = document.querySelectorAll('form[method="post"], form[method="POST"]');
            var token = this.getToken();
            var tokenName = this.getTokenName();
            
            if (!token || !tokenName) {
                console.warn('CSRF Helper: No token found, cannot protect forms');
                return;
            }
            
            for (var i = 0; i < forms.length; i++) {
                var form = forms[i];
                
                // Check if form already has a CSRF token
                var existingToken = form.querySelector('input[name="' + tokenName + '"]');
                if (existingToken) {
                    continue;
                }
                
                // Add hidden input with CSRF token
                var hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = tokenName;
                hiddenInput.value = token;
                
                form.appendChild(hiddenInput);
            }
        },
        
        /**
         * Handle CSRF token refresh (for long-running pages)
         */
        refreshToken: function(callback) {
            this.ajax({
                url: 'ajax/get-csrf-token', // You'll need to implement this endpoint
                method: 'GET',
                success: function(response) {
                    if (response && response.token) {
                        // Update token in forms
                        var inputs = document.querySelectorAll('input[name*="csrf"], input[name*="token"]');
                        for (var i = 0; i < inputs.length; i++) {
                            inputs[i].value = response.token;
                        }
                        
                        // Update meta tag if exists
                        var metaTag = document.querySelector('meta[name="csrf-token"]');
                        if (metaTag) {
                            metaTag.setAttribute('content', response.token);
                        }
                        
                        // Update global variable
                        window.csrf_token = response.token;
                        
                        if (callback) {
                            callback(response.token);
                        }
                    }
                },
                error: function(error) {
                    console.error('CSRF Helper: Failed to refresh token', error);
                }
            });
        },
        
        /**
         * Initialize CSRF helper
         */
        init: function() {
            // Protect all forms when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', this.protectAllForms.bind(this));
            } else {
                this.protectAllForms();
            }
            
            // Set up global AJAX error handler for CSRF failures
            var self = this;
            if (window.jQuery) {
                jQuery(document).ajaxError(function(event, xhr, settings) {
                    if (xhr.status === 403 && xhr.responseText.indexOf('CSRF') !== -1) {
                        console.warn('CSRF token validation failed, attempting refresh...');
                        self.refreshToken(function() {
                            // Optionally retry the failed request
                            console.log('CSRF token refreshed');
                        });
                    }
                });
            }
        }
    };
    
    // Expose to global scope
    window.CsrfHelper = CsrfHelper;
    
    // Auto-initialize
    CsrfHelper.init();
    
})(window);