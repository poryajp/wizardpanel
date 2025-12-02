<?php
/**
 * Stats and Reports Page
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/web_functions.php';

requireLogin();

if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: ../index.php');
    exit();
}

// Calculate statistics
$stats = [];

// Users stats
$stmt = pdo()->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

$stmt = pdo()->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
$stats['active_users'] = $stmt->fetchColumn();

$stmt = pdo()->query("SELECT COUNT(*) FROM users WHERE status = 'banned'");
$stats['banned_users'] = $stmt->fetchColumn();

// Services stats
$stmt = pdo()->query("SELECT COUNT(*) FROM services");
$stats['total_services'] = $stmt->fetchColumn();

$now = time();
$stmt = pdo()->prepare("SELECT COUNT(*) FROM services WHERE expire_timestamp > ?");
$stmt->execute([$now]);
$stats['active_services'] = $stmt->fetchColumn();

$stmt = pdo()->prepare("SELECT COUNT(*) FROM services WHERE expire_timestamp <= ?");
$stmt->execute([$now]);
$stats['expired_services'] = $stmt->fetchColumn();

// Income stats
// Calculate income stats (using payment_requests for stability)
$income_stats = [
    'today' => 0,
    'week' => 0,
    'month' => 0,
    'year' => 0
];

try {
    // Today
    $stmt = pdo()->prepare("SELECT SUM(amount) FROM payment_requests WHERE status = 'approved' AND DATE(processed_at) = CURDATE()");
    $stmt->execute();
    $income_stats['today'] = $stmt->fetchColumn() ?: 0;

    // Week
    $stmt = pdo()->prepare("SELECT SUM(amount) FROM payment_requests WHERE status = 'approved' AND YEARWEEK(processed_at, 1) = YEARWEEK(CURDATE(), 1)");
    $stmt->execute();
    $income_stats['week'] = $stmt->fetchColumn() ?: 0;

    // Month
    $stmt = pdo()->prepare("SELECT SUM(amount) FROM payment_requests WHERE status = 'approved' AND MONTH(processed_at) = MONTH(CURDATE()) AND YEAR(processed_at) = YEAR(CURDATE())");
    $stmt->execute();
    $income_stats['month'] = $stmt->fetchColumn() ?: 0;

    // Year
    $stmt = pdo()->prepare("SELECT SUM(amount) FROM payment_requests WHERE status = 'approved' AND YEAR(processed_at) = YEAR(CURDATE())");
    $stmt->execute();
    $income_stats['year'] = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    // Keep defaults
}

renderHeader('آمار و گزارشات');
?>

<div class="layout">
    <?php renderSidebar('stats'); ?>

    <div class="main-content">
        <?php renderTopbar('📊 آمار و گزارشات'); ?>

        <div class="content-area">
            <!-- Users Stats -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> آمار کاربران</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                                <div class="stat-label">کل کاربران</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo number_format($stats['active_users']); ?></div>
                                <div class="stat-label">کاربران فعال</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fas fa-user-slash"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo number_format($stats['banned_users']); ?></div>
                                <div class="stat-label">کاربران مسدود</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services Stats -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-box"></i> آمار سرویس‌ها</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon purple">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo number_format($stats['total_services']); ?></div>
                                <div class="stat-label">کل سرویس‌ها</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo number_format($stats['active_services']); ?></div>
                                <div class="stat-label">سرویس‌های فعال</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo number_format($stats['expired_services']); ?></div>
                                <div class="stat-label">سرویس‌های منقضی شده</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Income Stats -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> آمار درآمد</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo number_format($income_stats['today']); ?></div>
                                <div class="stat-label">درآمد امروز (تومان)</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo number_format($income_stats['week']); ?></div>
                                <div class="stat-label">درآمد هفته (تومان)</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon purple">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo number_format($income_stats['month']); ?></div>
                                <div class="stat-label">درآمد ماه (تومان)</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo number_format($income_stats['year']); ?></div>
                                <div class="stat-label">درآمد سال (تومان)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>