<?php
require_once __DIR__ . '/session.php';
requireUserLogin();

$user = getCurrentUser();
$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>پشتیبانی</title>
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
                <h2 style="margin-right: 12px;">پشتیبانی</h2>
            </div>
        </div>

        <!-- Support Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-headset text-primary"></i> تماس با پشتیبانی
                </div>
            </div>

            <div style="padding: 20px; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 20px;">
                    <i class="fab fa-telegram" style="color: #0088cc;"></i>
                </div>

                <p style="margin-bottom: 20px; color: var(--text-muted);">
                    برای دریافت پشتیبانی، ارسال رسید پرداخت یا گزارش مشکل، با ما در تلگرام در ارتباط باشید.
                </p>

                <button class="btn btn-primary" onclick="openSupport()">
                    <i class="fab fa-telegram"></i> ارتباط با پشتیبانی
                </button>
            </div>
        </div>

        <!-- User Info Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-user text-success"></i> اطلاعات شما
                </div>
            </div>

            <div style="padding: 15px;">
                <div style="margin-bottom: 12px;">
                    <strong>نام:</strong> <?php echo htmlspecialchars($user['first_name']); ?>
                </div>
                <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <strong>شناسه کاربری:</strong>
                    <div>
                        <code
                            style="background: var(--bg-color); padding: 4px 8px; border-radius: 4px;"><?php echo $user['chat_id']; ?></code>
                        <i class="fas fa-copy text-primary" onclick="copyText('<?php echo $user['chat_id']; ?>')"
                            style="cursor: pointer; margin-right: 8px;"></i>
                    </div>
                </div>
                <div>
                    <strong>موجودی:</strong> <span class="text-success"><?php echo number_format($user['balance']); ?>
                        تومان</span>
                </div>
                <p style="margin-top: 15px; font-size: 0.85rem; color: var(--text-muted);">
                    💡 لطفاً شناسه کاربری خود را هنگام تماس با پشتیبانی ارسال کنید.
                </p>
            </div>
        </div>

        <!-- FAQ -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-question-circle text-warning"></i> سوالات متداول
                </div>
            </div>

            <div style="padding: 15px;">
                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                    <strong>چگونه سرویس خریداری کنم؟</strong>
                    <p style="margin-top: 8px; color: var(--text-muted); font-size: 0.9rem;">
                        از بخش فروشگاه، دسته‌بندی و پلن مورد نظر را انتخاب کنید و پس از پرداخت، سرویس شما فعال می‌شود.
                    </p>
                </div>

                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                    <strong>چگونه موجودی کیف پول را شارژ کنم؟</strong>
                    <p style="margin-top: 8px; color: var(--text-muted); font-size: 0.9rem;">
                        از بخش کیف پول، مبلغ مورد نظر را وارد کرده و روش پرداخت را انتخاب کنید.
                    </p>
                </div>

                <div>
                    <strong>چگونه از سرویس خریداری شده استفاده کنم؟</strong>
                    <p style="margin-top: 8px; color: var(--text-muted); font-size: 0.9rem;">
                        از بخش "سرویس‌های من" می‌توانید لینک اشتراک و QR Code سرویس خود را مشاهده کنید.
                    </p>
                </div>
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
        <a href="support.php" class="nav-item active">
            <i class="fas fa-headset"></i>
            <span>پشتیبانی</span>
        </a>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        const tg = window.Telegram.WebApp;

        function openSupport() {
            <?php if (!empty($settings['support_id'])): ?>
                const support_username = '<?php echo str_replace('@', '', $settings['support_id']); ?>';
                tg.openTelegramLink('https://t.me/' + support_username);
            <?php else: ?>
                alert('اطلاعات پشتیبانی تنظیم نشده است');
            <?php endif; ?>
        }

        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                tg.showPopup({ message: 'کپی شد' });
            }).catch(() => {
                alert('شناسه کاربری: ' + text);
            });
        }
    </script>
</body>

</html>