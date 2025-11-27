<?php
/**
 * Session Management for Web Panel
 * Handles session initialization and security
 */

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['web_admin_logged_in']) && $_SESSION['web_admin_logged_in'] === true;
}

/**
 * Require user to be logged in, redirect to login if not
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

/**
 * Destroy session and logout
 */
function destroySession()
{
    $_SESSION = [];

    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    session_destroy();
}

/**
 * Regenerate session ID for security
 */
function regenerateSession()
{
    session_regenerate_id(true);
}
