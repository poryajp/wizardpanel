<?php
// Session security settings - must be set BEFORE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_lifetime', 0); // Session expires when browser closes
ini_set('session.gc_maxlifetime', 86400); // 24 hours

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';

// Check if user is logged in
function isUserLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['session_created']);
}

// Require login
function requireUserLogin()
{
    if (!isUserLoggedIn()) {
        // If it's an AJAX request, return 401
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // Otherwise, we might need to show a login page or error
        // But for Web App, we usually handle auth via JS on the index page
        // So we just exit or redirect to an error page
        die('Access Denied. Please open from Telegram.');
    }
}

// Get current user data
function getCurrentUser()
{
    if (isUserLoggedIn()) {
        return getUserData($_SESSION['user_id'], $_SESSION['first_name'] ?? 'کاربر');
    }
    return null;
}

// Login user with new session
function loginUser($userId, $firstName)
{
    // If there's an existing session with a different user, destroy it
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $userId) {
        // Clear all session data
        $_SESSION = array();

        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy the session
        session_destroy();

        // Start a new session
        session_start();
    }

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Set session data
    $_SESSION['user_id'] = $userId;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['session_created'] = time();
    $_SESSION['last_activity'] = time();
}

// Logout user
function logoutUser()
{
    // Clear all session data
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();
}
