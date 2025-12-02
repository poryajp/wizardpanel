<?php
/**
 * Dashboard - Main Admin Panel Page
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/web_functions.php';

// Require login
requireLogin();

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: index.php');
    exit();
}

// Get statistics
$stats = [];

try {
    // Total users
    $stmt = pdo()->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();

    // Active users
    $stmt = pdo()->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $stats['active_users'] = $stmt->fetchColumn();

    // Total services
    $stmt = pdo()->query("SELECT COUNT(*) FROM services");
    $stats['total_services'] = $stmt->fetchColumn();

    // Today income
    $stmt = pdo()->prepare("SELECT SUM(amount) FROM payment_requests WHERE status = 'approved' AND DATE(processed_at) = CURDATE()");
    $stmt->execute();
    $stats['today_income'] = $stmt->fetchColumn() ?: 0;

    // Month income
    $stmt = pdo()->prepare("SELECT SUM(amount) FROM payment_requests WHERE status = 'approved' AND MONTH(processed_at) = MONTH(CURDATE()) AND YEAR(processed_at) = YEAR(CURDATE())");
    $stmt->execute();
    $stats['month_income'] = $stmt->fetchColumn() ?: 0;

    // Total servers
    $stmt = pdo()->query("SELECT COUNT(*) FROM servers WHERE status = 'active'");
    $stats['total_servers'] = $stmt->fetchColumn();

    // Pending payments
    try {
        $stmt = pdo()->query("SELECT COUNT(*) FROM payment_requests WHERE status = 'pending'");
        $stats['pending_payments'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['pending_payments'] = 0;
    }

    // Recent services (last 5)
    $stmt = pdo()->query("
        SELECT s.*, p.name as plan_name, u.first_name 
        FROM services s 
        JOIN plans p ON s.plan_id = p.id 
        JOIN users u ON s.owner_chat_id = u.chat_id 
        ORDER BY s.id DESC 
        LIMIT 5
    ");
    $recent_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error loading dashboard: " . $e->getMessage());
}

renderHeader('داشبورد');
?>

<div class="layout">
    <?php renderSidebar('dashboard'); ?>

    <div class="main-content">
        <?php renderTopbar('📊 داشبورد'); ?>

        <div class="content-area">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">کل کاربران</div>
                        <div class="stat-sub"><?php echo number_format($stats['active_users']); ?> فعال</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total_services']); ?></div>
                        <div class="stat-label">کل سرویس‌ها</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-money-bill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['today_income']); ?></div>
                        <div class="stat-label">درآمد امروز (تومان)</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['month_income']); ?></div>
                        <div class="stat-label">درآمد ماه (تومان)</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon teal">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total_servers']); ?></div>
                        <div class="stat-label">سرورهای فعال</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['pending_payments']); ?></div>
                        <div class="stat-label">پرداخت‌های در انتظار</div>
                    </div>
                </div>
            </div>

            <!-- Recent Services -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> آخرین سرویس‌ها</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_services)): ?>
                        <p class="text-muted">هیچ سرویسی یافت نشد.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>کاربر</th>
                                    <th>پلن</th>
                                    <th>نام سرویس</th>
                                    <th>تاریخ خرید</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_services as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($service['plan_name']); ?></td>
                                        <td><?php echo htmlspecialchars($service['custom_name'] ?? $service['marzban_username']); ?>
                                        </td>
                                        <td><?php echo date('Y/m/d H:i', strtotime($service['purchase_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>