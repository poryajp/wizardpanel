<?php
session_start();
require_once __DIR__ . '/auth.php';

// Check if user is logged in
function isUserLoggedIn()
{
    return isset($_SESSION['user_id']);
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
