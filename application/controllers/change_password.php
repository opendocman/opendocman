<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = $GLOBALS['pdo'];

if (!isset($_SESSION['uid'])) {
    redirect_visitor('index');
    exit;
}

$user_obj = new User($_SESSION['uid'], $pdo);

// Check if password change is actually required
if (!$user_obj->isPasswordChangeRequired()) {
    unset($_SESSION['pw_change_required']);
    redirect_visitor('out');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Validate CSRF token
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        $error = 'Security token validation failed. Please try again.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = msg('change_password_error_empty');
        } elseif ($new_password !== $confirm_password) {
            $error = msg('change_password_error_mismatch');
        } elseif ($new_password === $current_password) {
            $error = msg('change_password_error_same');
        } elseif (!$user_obj->validatePassword($current_password)) {
            $error = msg('change_password_error_current');
        } else {
            $user_obj->changePassword($new_password);
            unset($_SESSION['pw_change_required']);
            $_SESSION['last_message'] = msg('change_password_success');
            redirect_visitor('out?last_message=' . urlencode(msg('change_password_success')));
            exit;
        }
    }
}

$GLOBALS['smarty']->assign('site_title', $GLOBALS['CONFIG']['title']);
$GLOBALS['smarty']->assign('error', $error ?? '');
$csrf_data = $GLOBALS['csrf']->getTokenForTemplate('/change_password');
$GLOBALS['smarty']->assign('csrf_token_field', $csrf_data['field']);
$GLOBALS['smarty']->assign('csrf_token_value', $csrf_data['token']);
$GLOBALS['smarty']->assign('csrf_field_name', $csrf_data['field_name']);
$GLOBALS['smarty']->assign('csrf_index_name', $csrf_data['index_name']);
display_smarty_template('change_password.tpl');
