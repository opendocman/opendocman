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

require_once __DIR__ . '/../../vendor/autoload.php';

use ParagonIE\AntiCSRF\AntiCSRF;

/**
 * CSRF Protection Helper Class
 * 
 * This class provides a wrapper around Paragonie Anti-CSRF library
 * to integrate CSRF protection seamlessly into the OpenDocMan application.
 */
class CsrfProtection
{
    private static $instance = null;
    private $csrf;
    
    /**
     * Singleton pattern to ensure only one instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to initialize AntiCSRF
     */
    private function __construct()
    {
        // Configure session for Docker environment if needed (only if session not started)
        if (getenv('IS_DOCKER') && session_status() == PHP_SESSION_NONE) {
            ini_set('session.cookie_domain', '');
            ini_set('session.cookie_path', '/');
            ini_set('session.cookie_secure', false);
            ini_set('session.cookie_httponly', true);
            ini_set('session.use_strict_mode', true);
        }
        
        // Start session if not already started (avoid headers already sent issues)
        if (session_status() == PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_start();
            } else {
                // Fallback: Ensure $_SESSION exists as an array so AntiCSRF can use native session storage
                if (!isset($_SESSION) || !is_array($_SESSION)) {
                    $_SESSION = [];
                }
            }
        }
        
        // Initialize AntiCSRF with default settings
        $this->csrf = new AntiCSRF();
    }
    
    /**
     * Generate and return a CSRF token HTML input field
     * 
     * @param string $action Optional action name for the token
     * @return string HTML input field with CSRF token
     */
    public function getTokenField($action = '')
    {
        try {
            return $this->csrf->insertToken($action, false);
        } catch (Exception $e) {
            error_log('CSRF Token Generation Error: ' . $e->getMessage());
            return '<input type="hidden" name="csrf_error" value="token_generation_failed">';
        }
    }
    
    /**
     * Get just the token value (for AJAX requests)
     * 
     * @param string $action Optional action name for the token
     * @return string The CSRF token value
     */
    public function getToken($action = '')
    {
        try {
            $tokenArray = $this->csrf->getTokenArray($action);
            return $tokenArray[$this->csrf->getFormToken()];
        } catch (Exception $e) {
            error_log('CSRF Token Retrieval Error: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Validate CSRF token from request
     * 
     * @param array $data Request data ($_POST, $_GET, etc.)
     * @param string $action Optional action name to validate against
     * @return bool True if token is valid, false otherwise
     */
    public function validateToken($data = null, $action = '')
    {
        // Use $_POST by default if no data provided
        if ($data === null) {
            $data = $_POST;
        }
        
        try {
            return $this->csrf->validateRequest($action);
        } catch (Exception $e) {
            error_log('CSRF Token Validation Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if current request is a POST request that needs CSRF validation
     * 
     * @return bool True if validation is needed
     */
    public function needsValidation()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH']);
    }
    
    /**
     * Middleware function to automatically validate CSRF tokens
     * Call this early in your request processing for forms that modify data
     * 
     * @param string $action Optional action name
     * @param bool $die Whether to die on validation failure (default: true)
     * @return bool True if validation passes or not needed
     */
    public function protect($action = '', $die = true)
    {
        // Only validate POST, PUT, DELETE, PATCH requests
        if (!$this->needsValidation()) {
            return true;
        }
        
        // Skip validation for certain paths that don't need it
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $skipPaths = [
            '/install/',
            '/setup-config',
            '/logout'
        ];
        
        foreach ($skipPaths as $path) {
            if (strpos($requestUri, $path) !== false) {
                return true;
            }
        }
        
        $isValid = $this->validateToken($_POST, $action);
        
        if (!$isValid) {
            error_log('CSRF Protection: Invalid token for action: ' . $action . ' from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            
            if ($die) {
                $this->handleInvalidToken();
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Handle invalid CSRF token
     * This method is called when a token validation fails
     */
    private function handleInvalidToken()
    {
        // Log the security event
        error_log('CSRF Protection: Token validation failed. Possible CSRF attack detected.');
        
        // Return appropriate response based on request type
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // AJAX request
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'error' => 'CSRF token validation failed',
                'message' => 'Security token expired or invalid. Please refresh the page and try again.'
            ]);
        } else {
            // Regular HTTP request
            http_response_code(403);
            echo '<!DOCTYPE html>
<html>
<head>
    <title>Security Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .error-box { 
            border: 1px solid #d00; 
            background: #fee; 
            padding: 20px; 
            border-radius: 5px; 
            max-width: 600px;
        }
        .error-title { color: #d00; font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .back-link { margin-top: 15px; }
        .back-link a { color: #0066cc; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-title">Security Token Error</div>
        <p>The security token for this request was invalid or expired. This could be due to:</p>
        <ul>
            <li>Your session has expired</li>
            <li>You submitted the form twice</li>
            <li>You opened the form in multiple browser tabs</li>
            <li>A potential security attack was detected</li>
        </ul>
        <p>Please go back and try again, or refresh the page to get a new security token.</p>
        <div class="back-link">
            <a href="javascript:history.back()">← Go Back</a> | 
            <a href="' . ($_SERVER['HTTP_REFERER'] ?? '/') . '">↻ Refresh Page</a>
        </div>
    </div>
</body>
</html>';
        }
        exit;
    }
    
    /**
     * Get CSRF token for Smarty template assignment
     * This method can be called from controllers to assign the token to Smarty
     * 
     * @param string $action Optional action name
     * @return array Array with token field and raw token
     */
    public function getTokenForTemplate($action = '')
    {
        return [
            'field' => $this->getTokenField($action),
            'token' => $this->getToken($action),
            'field_name' => $this->csrf->getFormToken(),
            'index_name' => $this->csrf->getFormIndex()
        ];
    }
    
    /**
     * Clean old tokens to prevent session bloat
     * Call this occasionally to clean up expired tokens
     */
    public function cleanOldTokens()
    {
        try {
            // AntiCSRF handles token cleanup internally during validation
            // No manual cleanup is needed with this library
        } catch (Exception $e) {
            error_log('CSRF Token Cleanup Error: ' . $e->getMessage());
        }
    }
}