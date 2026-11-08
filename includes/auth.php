<?php
// includes/auth.php
// Starts the session and provides the login / role checks.

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,                       // JavaScript cannot read the cookie
        'samesite' => 'Lax',                      // helps against cross-site requests
        'secure'   => !empty($_SERVER['HTTPS']),  // HTTPS-only cookie when the site uses HTTPS
    ]);
    session_start();
}

// Is someone logged in?
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// The role of the logged-in user: 'student', 'employer', 'admin' (or null).
function current_role()
{
    return $_SESSION['role'] ?? null;
}

// Pages for logged-in users only.
function require_login()
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
}

// Pages for one role only, e.g. require_role('student');
function require_role($role)
{
    require_login();

    if (current_role() !== $role) {
        set_flash('danger', 'You do not have permission to open that page.');
        redirect('index.php');
    }
}