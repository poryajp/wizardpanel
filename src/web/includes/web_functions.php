<?php
/**
 * Web Panel Helper Functions
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Define USER_INLINE_KEYBOARD constant needed by functions.php
if (!defined('USER_INLINE_KEYBOARD')) {
    try {
        $settings = getSettings();
        define('USER_INLINE_KEYBOARD', ($settings['inline_keyboard'] ?? 'on') === 'on');
    } catch (Exception $e) {
        // Default to 'on' if settings cannot be loaded
        define('USER_INLINE_KEYBOARD', true);
    }
}

/**
 * Render page header
 */
function renderHeader($pageTitle = 'پنل مدیریت')
{
    // Detect if we're in a subdirectory
    $isInSubdir = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
    $baseUrl = $isInSubdir ? '../' : '';
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($pageTitle); ?> - پنل مدیریت</title>
        <link href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
    </head>

    <body>
        <?php
}

/**
 * Render page footer
 */
function renderFooter()
{
    $isInSubdir = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
    $baseUrl = $isInSubdir ? '../' : '';
    ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script src="<?php echo $baseUrl; ?>assets/js/main.js"></script>
    </body>

    </html>
    <?php
}

/**
 * Render sidebar navigation
 */
function renderSidebar($activePage = '')
{
    $isInSubdir = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
    $baseUrl = $isInSubdir ? '../' : '';
    $username = $_SESSION['web_admin_username'] ?? 'Admin';
    ?>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>🎯 پنل مدیریت</h2>
            <span class="username">👤 <?php echo htmlspecialchars($username); ?></span>
        </div>

        <nav class="sidebar-nav">
            <a href="<?php echo $baseUrl; ?>dashboard.php"
                class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> داشبورد
            </a>
            <a href="<?php echo $baseUrl; ?>pages/categories.php"
                class="<?php echo $activePage === 'categories' ? 'active' : ''; ?>">
                <i class="fas fa-folder"></i> دسته‌بندی‌ها
            </a>
            <a href="<?php echo $baseUrl; ?>pages/plans.php" class="<?php echo $activePage === 'plans' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> پلن‌ها
            </a>
            <a href="<?php echo $baseUrl; ?>pages/users.php" class="<?php echo $activePage === 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> کاربران
            </a>
            <a href="<?php echo $baseUrl; ?>pages/servers.php"
                class="<?php echo $activePage === 'servers' ? 'active' : ''; ?>">
                <i class="fas fa-server"></i> سرورها
            </a>
            <a href="<?php echo $baseUrl; ?>pages/stats.php" class="<?php echo $activePage === 'stats' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> آمار و گزارشات
            </a>
            <a href="<?php echo $baseUrl; ?>pages/payments.php"
                class="<?php echo $activePage === 'payments' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i> پرداخت‌ها
            </a>
            <a href="<?php echo $baseUrl; ?>pages/broadcast.php"
                class="<?php echo $activePage === 'broadcast' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> پیام همگانی
            </a>
            <a href="<?php echo $baseUrl; ?>pages/discount.php"
                class="<?php echo $activePage === 'discount' ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i> کدهای تخفیف
            </a>
            <a href="<?php echo $baseUrl; ?>pages/guides.php"
                class="<?php echo $activePage === 'guides' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> راهنما
            </a>
            <a href="<?php echo $baseUrl; ?>pages/admins.php"
                class="<?php echo $activePage === 'admins' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> ادمین‌ها
            </a>
            <a href="<?php echo $baseUrl; ?>pages/settings.php"
                class="<?php echo $activePage === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> تنظیمات
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="?logout=1" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> خروج
            </a>
        </div>
    </div>
    <?php
}

/**
 * Render topbar
 */
function renderTopbar($pageTitle = '')
{
    ?>
    <div class="topbar">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    <?php
}

/**
 * Show success message
 */
function showSuccess($message)
{
    echo '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

/**
 * Show error message
 */
function showError($message)
{
    echo '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

/**
 * Sanitize input
 */
function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}
