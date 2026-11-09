<?php
// includes/functions.php
// Small helper functions used by many pages.

// The web path of the project. If you rename the folder, change only this line.
if (!defined('BASE_URL')) {
    define('BASE_URL', '/student-job-portal/');
}

// Make text safe to print inside HTML. This stops XSS (script injection).
// Use it every time you print something a user typed or the database returned.
function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// Clean text typed by a user: remove spaces at the start and end.
function clean_input($value)
{
    return trim((string) $value);
}

// Send the visitor to another page of our site, then stop the script.
function redirect($path)
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

// Flash message: a one-time message that appears on the next page shown.
// $type can be: success, danger, warning, info (Bootstrap colours).
function set_flash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Print the flash message (if there is one) and then forget it.
function show_flash()
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    $allowed = ['success', 'danger', 'warning', 'info'];
    $type = $_SESSION['flash']['type'];
    if (!in_array($type, $allowed, true)) {
        $type = 'info';
    }

    echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
    echo e($_SESSION['flash']['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';

    unset($_SESSION['flash']);
}

// CSRF protection: a secret code placed in every form.
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// The hidden input to put inside every <form>.
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Did the submitted form send the correct secret code?
function csrf_check()
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $sent);
}

// Print the error message for one form field (if it has one).
function field_error($errors, $key)
{
    if (isset($errors[$key])) {
        echo '<div class="text-danger small mt-1">' . e($errors[$key]) . '</div>';
    }
}
// Turn an empty string into NULL (for optional database columns).
function null_if_empty($value)
{
    return $value === '' ? null : $value;
}