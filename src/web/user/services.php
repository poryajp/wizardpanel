<?php
require_once __DIR__ . '/session.php';
requireUserLogin();

$user = getCurrentUser();
$services = getUserServices($user['chat_id']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>سرویس‌های من</title>
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
                <h2 style="margin-right: 12px;">سرویس‌های من</h2>
            </div>
            <div class="wallet-badge">
                <span class="text-primary"><?php echo count($services); ?> سرویس</span>
            </div>
        </div>

        <?php if (empty($services)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
                <i class="fas fa-box-open" style="font-size: 64px; margin-bottom: 20px; opacity: 0.4;"></i>
                <h3 style="margin-bottom: 12px;">هیچ سرویسی ندارید</h3>
                <p style="margin-bottom: 24px;">برای خرید سرویس به فروشگاه مراجعه کنید.</p>
                <a href="shop.php" class="btn btn-primary" style="display: inline-block;">
                    <i class="fas fa-shopping-cart"></i> خرید سرویس
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($services as $service): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-server text-primary"></i> <?php echo htmlspecialchars($service['plan_name']); ?>
                        </div>
                        <span class="text-success" style="font-size: 0.85rem; font-weight: 500;">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i> فعال
                        </span>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <div
                            style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px; background: var(--bg-color); border-radius: 6px;">
                            <span class="text-muted" style="font-size: 0.9rem;">
                                <i class="fas fa-user"></i> نام کاربری:
                            </span>
                            <span style="font-family: monospace; font-weight: 500;">
                                <?php echo $service['marzban_username']; ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px;">
                            <span class="text-muted" style="font-size: 0.9rem;">
                                <i class="fas fa-calendar-alt"></i> تاریخ انقضا:
                            </span>
                            <span style="font-weight: 500;">
                                <?php echo $service['expire_timestamp'] ? date('Y/m/d', $service['expire_timestamp']) : 'نامحدود'; ?>
                            </span>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; padding: 8px; background: var(--bg-color); border-radius: 6px;">
                            <span class="text-muted" style="font-size: 0.9rem;">
                                <i class="fas fa-database"></i> حجم مصرفی:
                            </span>
                            <span style="font-weight: 500;">
                                <?php echo formatBytes($service['used_traffic'] ?? 0); ?> /
                                <?php echo $service['data_limit'] ? formatBytes($service['data_limit']) : 'نامحدود'; ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($service['sub_link'])): ?>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <button class="btn btn-primary"
                                onclick="copyLink('<?php echo htmlspecialchars($service['sub_link'], ENT_QUOTES); ?>')">
                                <i class="fas fa-copy"></i> کپی لینک
                            </button>
                            <button class="btn btn-outline"
                                onclick="showQr('<?php echo htmlspecialchars($service['sub_link'], ENT_QUOTES); ?>')">
                                <i class="fas fa-qrcode"></i> QR کد
                            </button>
                        </div>
                    <?php else: ?>
                        <div
                            style="padding: 12px; background: #fff3cd; border-radius: 8px; text-align: center; font-size: 0.9rem; color: #856404;">
                            <i class="fas fa-exclamation-triangle"></i> لینک اشتراک موجود نیست
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- QR Modal -->
    <div id="qr-modal" class="loading-overlay" style="display: none; background: rgba(0,0,0,0.9);">
        <div class="card" style="width: 90%; max-width: 320px; position: relative;">
            <i class="fas fa-times" onclick="closeQr()"
                style="position: absolute; top: 12px; right: 12px; cursor: pointer; font-size: 1.3rem; color: var(--text-muted); z-index: 10;"></i>
            <div style="text-align: center; padding: 24px 16px;">
                <h3 style="margin-bottom: 16px; font-size: 1.1rem;">
                    <i class="fas fa-qrcode text-primary"></i> QR Code
                </h3>
                <div style="background: white; padding: 16px; border-radius: 12px; display: inline-block;">
                    <img id="qr-image" src="" alt="QR Code" style="width: 250px; height: 250px; border-radius: 8px;">
                </div>
                <p style="margin-top: 16px; font-size: 0.9rem; color: var(--text-muted);">
                    <i class="fas fa-mobile-alt"></i> با تلفن همراه اسکن کنید
                </p>
            </div>
        </div>
    </div>

    <!-- Bottom Nav -->
    <div class="bottom-nav">
        <a href="index.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>خانه</span>
        </a>
        <a href="services.php" class="nav-item active">
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

        // Show back button
        if (tg.BackButton) {
            tg.BackButton.show();
            tg.BackButton.onClick(function () {
                window.location.href = 'index.php';
            });
        }

        function copyLink(link) {
            if (!link) {
                alert('لینک موجود نیست');
                return;
            }

            // Try multiple methods for better compatibility
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link)
                    .then(() => {
                        if (tg.showPopup) {
                            tg.showPopup({
                                title: 'موفقیت‌آمیز',
                                message: 'لینک اشتراک کپی شد',
                                buttons: [{ type: 'ok' }]
                            });
                        } else {
                            alert('لینک اشتراک کپی شد');
                        }
                    })
                    .catch(() => {
                        fallbackCopy(link);
                    });
            } else {
                fallbackCopy(link);
            }
        }

        function fallbackCopy(text) {
            // Create temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            try {
                textarea.select();
                textarea.setSelectionRange(0, 99999); // For mobile
                const success = document.execCommand('copy');

                if (success) {
                    if (tg.showPopup) {
                        tg.showPopup({
                            message: 'لینک اشتراک کپی شد ✓'
                        });
                    } else {
                        alert('لینک اشتراک کپی شد ✓');
                    }
                } else {
                    // Show link in alert as last resort
                    prompt('لینک اشتراک (کپی کنید):', text);
                }
            } catch (err) {
                prompt('لینک اشتراک (کپی کنید):', text);
            } finally {
                document.body.removeChild(textarea);
            }
        }

        function showQr(link) {
            if (!link) {
                alert('لینک موجود نیست');
                return;
            }

            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(link);
            document.getElementById('qr-image').src = qrUrl;
            document.getElementById('qr-modal').style.display = 'flex';
        }

        function closeQr() {
            document.getElementById('qr-modal').style.display = 'none';
        }

        // Close modal on background click
        document.getElementById('qr-modal')?.addEventListener('click', function (e) {
            if (e.target === this) {
                closeQr();
            }
        });
    </script>
</body>

</html>