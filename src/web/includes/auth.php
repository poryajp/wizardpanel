<?php
/**
 * Authentication Functions for Web Panel
 */

require_once __DIR__ . '/../../includes/config.php';

/**
 * Authenticate user with username and password
 * @param string $username
 * @param string $password
 * @return bool
 */
function authenticateUser($username, $password)
{
    // Check if username matches
    if ($username !== WEB_USERNAME) {
        return false;
    }

    // Verify password against hash
    return password_verify($password, WEB_PASSWORD_HASH);
}

/**
 * Login user and create session
 * @param string $username
 * @param string $password
 * @return bool
 */
function loginUser($username, $password)
{
    if (authenticateUser($username, $password)) {
        $_SESSION['web_admin_logged_in'] = true;
        $_SESSION['web_admin_username'] = $username;
        $_SESSION['web_admin_login_time'] = time();

        // Regenerate session ID for security
        regenerateSession();

        return true;
    }

    return false;
}

/**
 * Logout user
 */
function logoutUser()
{
    destroySession();
}
