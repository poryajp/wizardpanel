<?php
require_once __DIR__ . '/session.php';
requireUserLogin();

$user = getCurrentUser();
$services = getUserServices($user['chat_id']);

// Sort by expire date (active first)
usort($services, function ($a, $b) {
    $now = time();
    $a_expired = $a['expire_timestamp'] < $now;
    $b_expired = $b['expire_timestamp'] < $now;

    if ($a_expired != $b_expired) {
        return $a_expired ? 1 : -1;
    }
    return $b['expire_timestamp'] - $a['expire_timestamp'];
});
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
        </div>

        <?php if (empty($services)): ?>
            <div class="card">
                <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p style="margin-bottom: 16px;">شما هیچ سرویسی ندارید.</p>
                    <a href="shop.php" class="btn btn-primary" style="text-decoration: none; display: inline-block;">
                        <i class="fas fa-shopping-cart"></i> خرید سرویس
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php
            $now = time();
            foreach ($services as $service):
                $is_expired = $service['expire_timestamp'] < $now;
                $status_color = $is_expired ? 'var(--danger-color)' : 'var(--success-color)';
                $status_icon = $is_expired ? 'fa-times-circle' : 'fa-check-circle';
                $status_text = $is_expired ? 'منقضی' : 'فعال';
                $expire_date = date('Y/m/d', $service['expire_timestamp']);
                $days_left = ceil(($service['expire_timestamp'] - $now) / 86400);
                ?>
                <div class="card" style="margin-bottom: 12px;">
                    <div class="card-header">
                        <div class="card-title"><?php echo htmlspecialchars($service['custom_name']); ?></div>
                        <div style="color: <?php echo $status_color; ?>; font-size: 0.9rem;">
                            <i class="fas <?php echo $status_icon; ?>"></i>
                            <?php echo $status_text; ?>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">
                            <div style="margin-bottom: 4px;">
                                <i class="fas fa-tag"></i>
                                پلن: <?php echo htmlspecialchars($service['plan_name']); ?>
                            </div>
                            <div style="margin-bottom: 4px;">
                                <i class="fas fa-calendar"></i>
                                تاریخ انقضا: <?php echo $expire_date; ?>
                                <?php if (!$is_expired && $days_left > 0): ?>
                                    <span
                                        style="color: <?php echo $days_left <= 3 ? 'var(--danger-color)' : 'var(--text-muted)'; ?>;">
                                        (<?php echo $days_left; ?> روز مانده)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <i class="fas fa-hdd"></i>
                                حجم: <?php echo $service['volume_gb']; ?> GB
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($service['sub_url'])): ?>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                            <button class="btn btn-sm btn-outline"
                                onclick="copyLink('<?php echo htmlspecialchars($service['sub_url'], ENT_QUOTES); ?>')">
                                <i class="fas fa-copy"></i> کپی لینک
                            </button>
                            <button class="btn btn-sm btn-outline"
                                onclick="openLink('<?php echo htmlspecialchars($service['sub_url'], ENT_QUOTES); ?>')">
                                <i class="fas fa-external-link-alt"></i> باز کردن
                            </button>
                            <button class="btn btn-sm btn-outline"
                                onclick="showQR('<?php echo htmlspecialchars($service['sub_url'], ENT_QUOTES); ?>')">
                                <i class="fas fa-qrcode"></i> QR Code
                            </button>
                            <a href="renew.php?username=<?php echo htmlspecialchars($service['marzban_username'], ENT_QUOTES); ?>"
                                class="btn btn-sm btn-primary"
                                style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-sync-alt" style="margin-left: 5px;"></i> تمدید
                            </a>
                        </div>
                    <?php else: ?>
                        <div
                            style="text-align: center; padding: 8px; background: var(--bg-secondary); border-radius: var(--radius); font-size: 0.85rem; color: var(--text-muted);">
                            <i class="fas fa-exclamation-triangle"></i>
                            لینک اشتراک در دسترس نیست
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- QR Code Modal -->
    <div id="qr-modal" class="loading-overlay" style="display: none; background: rgba(0,0,0,0.5);">
        <div class="card" style="width: 90%; max-width: 350px;">
            <div class="card-header">
                <div class="card-title">QR Code اشتراک</div>
                <i class="fas fa-times" onclick="closeQRModal()" style="cursor: pointer;"></i>
            </div>
            <div id="qr-content" style="text-align: center; padding: 20px;">
                <!-- QR Code will be inserted here -->
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
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
    <script>
        const tg = window.Telegram.WebApp;
        tg.ready();
        tg.expand();

        function copyLink(url) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url)
                    .then(() => {
                        tg.showPopup({
                            message: '✓ لینک اشتراک کپی شد'
                        });
                    })
                    .catch(() => fallbackCopy(url));
            } else {
                fallbackCopy(url);
            }
        }

        function fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            try {
                textarea.select();
                const success = document.execCommand('copy');
                if (success) {
                    tg.showPopup({ message: '✓ لینک اشتراک کپی شد' });
                } else {
                    prompt('لینک اشتراک را کپی کنید:', text);
                }
            } finally {
                document.body.removeChild(textarea);
            }
        }

        function openLink(url) {
            try {
                tg.openLink(url);
            } catch (e) {
                // Fallback
                window.open(url, '_blank');
            }
        }

        function showQR(url) {
            const qrContent = document.getElementById('qr-content');
            qrContent.innerHTML = '<div id="qrcode"></div>';

            QRCode.toCanvas(url, {
                errorCorrectionLevel: 'M',
                width: 250,
                margin: 2
            }, (err, canvas) => {
                if (err) {
                    qrContent.innerHTML = '<p style="color: var(--danger-color);">خطا در ایجاد QR Code</p>';
                } else {
                    qrContent.innerHTML = '';
                    qrContent.appendChild(canvas);
                }
            });

            document.getElementById('qr-modal').style.display = 'flex';
        }

        function closeQRModal() {
            document.getElementById('qr-modal').style.display = 'none';
        }

        function showDetails(username) {
            // TODO: Navigate to service detail page
            tg.showAlert('صفحه جزئیات در حال توسعه است');
        }

        // Theme
        if (tg.colorScheme === 'dark') {
            document.body.classList.add('dark-theme');
        }

        // Check for success message in URL
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success_msg');
        if (successMsg) {
            tg.showAlert(successMsg);
            // Clean URL
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    </script>
</body>

</html>