<?php
require_once __DIR__ . '/session.php';
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
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>حساب کاربری</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="user-profile">
                <a href="index.php" style="color: var(--text-color); text-decoration: none; font-size: 1.2rem;">
                    <i class="fas fa-arrow-right"></i>
                </a>
                <h2 style="margin-right: 12px;">حساب کاربری</h2>
            </div>
        </div>

        <!-- User Profile -->
        <div class="card"
            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 16px;">
            <div style="padding: 24px; text-align: center;">
                <div
                    style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user" style="font-size: 2.5rem;"></i>
                </div>
                <h3 style="margin-bottom: 8px;"><?php echo htmlspecialchars($user['first_name']); ?></h3>
                <div style="opacity: 0.9; font-size: 0.9rem;">
                    <i class="fas fa-id-card"></i>
                    شناسه: <code
                        style="background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px;"><?php echo $user['chat_id']; ?></code>
                </div>
            </div>
        </div>

        <!-- Balance Info -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">اطلاعات مالی</div>
            </div>
            <div style="padding: 16px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; padding: 16px; background: var(--bg-secondary); border-radius: var(--radius);">
                    <div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px;">موجودی کیف پول
                        </div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: var(--success-color);">
                            <?php echo number_format($user['balance']); ?> <span style="font-size: 0.9rem;">تومان</span>
                        </div>
                    </div>
                    <a href="wallet.php" class="btn btn-primary" style="text-decoration: none;">
                        <i class="fas fa-plus"></i> شارژ
                    </a>
                </div>
            </div>
        </div>

        <!-- Services Stats -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">آمار سرویس‌ها</div>
            </div>
            <div style="padding: 16px;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                    <div
                        style="text-align: center; padding: 16px; background: var(--bg-secondary); border-radius: var(--radius);">
                        <div
                            style="font-size: 2rem; font-weight: bold; color: var(--primary-color); margin-bottom: 4px;">
                            <?php echo $total_services; ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">کل</div>
                    </div>
                    <div
                        style="text-align: center; padding: 16px; background: var(--bg-secondary); border-radius: var(--radius);">
                        <div
                            style="font-size: 2rem; font-weight: bold; color: var(--success-color); margin-bottom: 4px;">
                            <?php echo $active_services; ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">فعال</div>
                    </div>
                    <div
                        style="text-align: center; padding: 16px; background: var(--bg-secondary); border-radius: var(--radius);">
                        <div
                            style="font-size: 2rem; font-weight: bold; color: var(--danger-color); margin-bottom: 4px;">
                            <?php echo $expired_services; ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">منقضی</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Actions -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">عملیات</div>
            </div>
            <div style="padding: 16px; display: grid; grid-template-columns: 1fr; gap: 8px;">
                <a href="services.php" class="btn btn-outline"
                    style="text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                    <span>
                        <i class="fas fa-cube"></i> سرویس‌های من
                    </span>
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="shop.php" class="btn btn-outline"
                    style="text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                    <span>
                        <i class="fas fa-shopping-cart"></i> خرید سرویس جدید
                    </span>
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="support.php" class="btn btn-outline"
                    style="text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                    <span>
                        <i class="fas fa-headset"></i> پشتیبانی
                    </span>
                    <i class="fas fa-chevron-left"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Bottom Nav -->
    <div class="bottom-nav">
        <a href="index.php" class="nav-item">
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

        // Theme
        if (tg.colorScheme === 'dark') {
            document.body.classList.add('dark-theme');
        }
    </script>
</body>

</html>