<?php
require_once __DIR__ . '/session.php';
requireUserLogin();

$user = getCurrentUser();
$test_plan = getTestPlan();
$settings = getSettings();
$usage_limit = (int) ($settings['test_config_usage_limit'] ?? 1);

// Handle test config request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_test') {
    if (!$test_plan) {
        echo json_encode(['success' => false, 'message' => 'کانفیگ تست غیرفعال است']);
        exit;
    }

    if ($user['test_config_count'] >= $usage_limit) {
        echo json_encode(['success' => false, 'message' => 'شما قبلاً از حداکثر تعداد کانفیگ تست استفاده کرده‌اید']);
        exit;
    }

    // Create test service
    $result = completePurchase($user['chat_id'], $test_plan['id'], 'تست رایگان', 0, null, null, false);

    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'کانفیگ تست با موفقیت ساخته شد']);
    } else {
        echo json_encode(['success' => false, 'message' => $result['error_message'] ?? 'خطا در ساخت کانفیگ']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>کانفیگ تست</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div id="loading" class="loading-overlay" style="display: none;">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <div class="header">
            <div class="user-profile">
                <a href="index.php" style="color: var(--text-color); text-decoration: none; font-size: 1.2rem;">
                    <i class="fas fa-arrow-right"></i>
                </a>
                <h2 style="margin-right: 12px;">کانفیگ تست</h2>
            </div>
        </div>

        <?php if (!$test_plan): ?>
            <div class="card">
                <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <i class="fas fa-ban" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p>دریافت کانفیگ تست در حال حاضر غیرفعال است.</p>
                </div>
            </div>
        <?php elseif ($user['test_config_count'] >= $usage_limit): ?>
            <div class="card">
                <div style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-check-circle"
                        style="font-size: 48px; margin-bottom: 16px; color: var(--success-color);"></i>
                    <h3 style="margin-bottom: 12px;">شما قبلاً از کانفیگ تست استفاده کرده‌اید</h3>
                    <p style="color: var(--text-muted); margin-bottom: 16px;">
                        هر کاربر فقط <?php echo $usage_limit; ?> بار می‌تواند کانفیگ تست دریافت کند.
                    </p>
                    <a href="shop.php" class="btn btn-primary" style="text-decoration: none; display: inline-block;">
                        <i class="fas fa-shopping-cart"></i>
                        خرید سرویس
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 16px;">
                <div style="padding: 24px; text-align: center;">
                    <i class="fas fa-gift" style="font-size: 3rem; margin-bottom: 12px;"></i>
                    <h3 style="margin-bottom: 8px;">کانفیگ تست رایگان</h3>
                    <p style="opacity: 0.9; font-size: 0.9rem;">برای آزمایش سرعت و کیفیت سرویس</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">مشخصات کانفیگ تست</div>
                </div>
                <div style="padding: 16px;">
                    <div style=" margin-bottom: 12px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-secondary); border-radius: var(--radius); margin-bottom: 8px;">
                            <span><i class="fas fa-tag text-primary"></i> نام پلن</span>
                            <strong><?php echo htmlspecialchars($test_plan['name']); ?></strong>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-secondary); border-radius: var(--radius); margin-bottom: 8px;">
                            <span><i class="fas fa-hdd text-primary"></i> حجم</span>
                            <strong><?php echo $test_plan['volume_gb']; ?> GB</strong>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-secondary); border-radius: var(--radius);">
                            <span><i class="fas fa-clock text-primary"></i> مدت اعتبار</span>
                            <strong><?php echo $test_plan['duration_days']; ?> روز</strong>
                        </div>
                    </div>

                    <div
                        style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 12px; border-radius: var(--radius); margin-bottom: 16px; color: #0c5460; font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i>
                        <strong>توجه:</strong> هر کاربر فقط <?php echo $usage_limit; ?> بار می‌تواند کانفیگ تست دریافت کند.
                    </div>

                    <button class="btn btn-success" onclick="getTestConfig()" style="width: 100%;">
                        <i class="fas fa-download"></i>
                        دریافت رایگان
                    </button>
                </div>
            </div>
        <?php endif; ?>
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

        function getTestConfig() {
            showLoading();

            fetch('test-config.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_test'
            })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        tg.showPopup({
                            title: 'موفقیت‌آمیز',
                            message: data.message,
                            buttons: [{ type: 'ok' }]
                        }, function () {
                            window.location.href = 'services.php';
                        });
                    } else {
                        tg.showAlert(data.message || 'خطا در دریافت کانفیگ');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    tg.showAlert('خطا در ارتباط با سرور');
                });
        }

        // Theme
        if (tg.colorScheme === 'dark') {
            document.body.classList.add('dark-theme');
        }
    </script>
</body>

</html>