<?php
require_once __DIR__ . '/session.php';

// Handle WebApp authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initData'])) {
    $userData = validateTelegramWebAppData($_POST['initData']);

    if ($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['first_name'] = $userData['first_name'] ?? 'کاربر';

        echo json_encode(['success' => true]);
        exit;
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid authentication']);
        exit;
    }
}

// Require login for viewing the page
requireUserLogin();

$user = getCurrentUser();
$services = getUserServices($user['chat_id']);

// Calculate stats
$total_services = count($services);
$active_services = 0;
$expired_services = 0;
$now = time();

foreach ($services as $service) {
    if ($service['expire_timestamp'] < $now) {
        $expired_services++;
    } else {
        $active_services++;
    }
}

// Get recent services (last 3)
$recent_services = array_slice($services, 0, 3);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>پنل کاربری</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Loading Overlay -->
    <div id="loading" class="loading-overlay" style="display: none;">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="user-profile">
                <h2><i class="fas fa-home"></i> خانه</h2>
            </div>
        </div>

        <!-- User Info Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">اطلاعات کاربری</div>
            </div>
            <div style="padding: 16px;">
                <div style="margin-bottom: 12px;">
                    <i class="fas fa-user text-primary"></i>
                    <strong>نام:</strong> <?php echo htmlspecialchars($user['first_name']); ?>
                </div>
                <div style="margin-bottom: 12px;">
                    <i class="fas fa-id-card text-primary"></i>
                    <strong>شناسه:</strong> <code><?php echo $user['chat_id']; ?></code>
                </div>
                <div style="margin-bottom: 0;">
                    <i class="fas fa-wallet text-success"></i>
                    <strong>موجودی:</strong>
                    <span class="text-success" style="font-size: 1.1rem; font-weight: bold;">
                        <?php echo number_format($user['balance']); ?> تومان
                    </span>
                </div>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">آمار سرویس‌ها</div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; padding: 16px;">
                <div style="text-align: center;">
                    <div style="font-size: 2rem; color: var(--primary-color); font-weight: bold;">
                        <?php echo $total_services; ?>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                        کل سرویس‌ها
                    </div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; color: var(--success-color); font-weight: bold;">
                        <?php echo $active_services; ?>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                        فعال
                    </div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; color: var(--danger-color); font-weight: bold;">
                        <?php echo $expired_services; ?>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                        منقضی
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">دسترسی سریع</div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 16px;">
                <a href="shop.php" class="btn btn-primary"
                    style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-shopping-cart"></i>
                    <span>خرید سرویس</span>
                </a>
                <a href="wallet.php" class="btn btn-success"
                    style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-wallet"></i>
                    <span>شارژ کیف پول</span>
                </a>
            </div>
        </div>

        <!-- Recent Services -->
        <?php if (!empty($recent_services)): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">سرویس‌های اخیر</div>
                    <a href="services.php" style="font-size: 0.85rem; color: var(--primary-color); text-decoration: none;">
                        مشاهده همه <i class="fas fa-chevron-left"></i>
                    </a>
                </div>
                <div style="padding: 12px;">
                    <?php foreach ($recent_services as $service):
                        $is_expired = $service['expire_timestamp'] < $now;
                        $status_color = $is_expired ? 'var(--danger-color)' : 'var(--success-color)';
                        $status_icon = $is_expired ? 'fa-times-circle' : 'fa-check-circle';
                        $status_text = $is_expired ? 'منقضی' : 'فعال';
                        $expire_date = date('Y-m-d', $service['expire_timestamp']);
                        ?>
                        <div
                            style="padding: 12px; margin-bottom: 8px; background: var(--bg-secondary); border-radius: var(--radius); border-right: 3px solid <?php echo $status_color; ?>;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <div style="font-weight: 600;">
                                    <?php echo htmlspecialchars($service['custom_name']); ?>
                                </div>
                                <div style="color: <?php echo $status_color; ?>; font-size: 0.85rem;">
                                    <i class="fas <?php echo $status_icon; ?>"></i>
                                    <?php echo $status_text; ?>
                                </div>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">
                                <i class="fas fa-calendar"></i>
                                انقضا: <?php echo $expire_date; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p>شما هنوز سرویسی خریداری نکرده‌اید.</p>
                    <a href="shop.php" class="btn btn-primary"
                        style="margin-top: 16px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-shopping-cart"></i> خرید اولین سرویس
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="index.php" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>خانه</span>
        </a>
        <a href="services.php" class="nav-item">
            <i class="fas fa-cube"></i>
            <span>سرویس‌ها</span>
        </a>
        <a href="shop.php" class="nav-item">
            <i class="fas fa-store"></i>
            <span>فروشگاه</span>
        </a>
        <a href="wallet.php" class="nav-item">
            <i class="fas fa-wallet"></i>
            <span>کیف پول</span>
        </a>
        <a href="support.php" class="nav-item">
            <i class="fas fa-headset"></i>
            <span>پشتیبانی</span>
        </a>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        const tg = window.Telegram.WebApp;
        tg.ready();
        tg.expand();

        // Check if we need to authenticate
        if (!<?php echo isUserLoggedIn() ? 'true' : 'false'; ?>) {
            // Try to authenticate with Telegram
            if (tg.initData) {
                showLoading();
                fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'initData=' + encodeURIComponent(tg.initData)
                })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            tg.showAlert('خطا در احراز هویت. لطفاً دوباره تلاش کنید.');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        console.error('Auth error:', error);
                        tg.showAlert('خطا در ارتباط با سرور');
                    });
            } else {
                tg.showAlert('لطفاً از طریق ربات تلگرام وارد شوید.');
            }
        }

        // Set theme
        if (tg.colorScheme === 'dark') {
            document.body.classList.add('dark-theme');
        }
    </script>
</body>

</html>