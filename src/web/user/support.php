<?php
require_once __DIR__ . '/session.php';
requireUserLogin();

$user = getCurrentUser();
$settings = getSettings();

// تعریف ثابت USER_INLINE_KEYBOARD برای جلوگیری از خطا
if (!defined('USER_INLINE_KEYBOARD')) {
    define('USER_INLINE_KEYBOARD', ($settings['inline_keyboard'] ?? 'on') === 'on');
}

// تعریف ثابت ADMIN_CHAT_ID اگر در کانفیگ وجود داشته باشد
if (!defined('ADMIN_CHAT_ID')) {
    require_once __DIR__ . '/../../includes/config.php';
    $admin_chat_id = $config['telegram']['admin_chat_id'] ?? 12345678;
    define('ADMIN_CHAT_ID', $admin_chat_id);
}

// Handle ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_ticket') {
    header('Content-Type: application/json');

    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'موضوع و متن پیام نمی‌تواند خالی باشد']);
        exit;
    }

    // Send to admin via bot
    require_once __DIR__ . '/../../includes/functions.php';

    $ticket_message = "📨 تیکت پشتیبانی جدید\n\n" .
        "👤 کاربر: " . htmlspecialchars($user['first_name']) . "\n" .
        "🆔 شناسه: <code>{$user['chat_id']}</code>\n" .
        "📋 موضوع: " . htmlspecialchars($subject) . "\n\n" .
        "💬 پیام: \n" . htmlspecialchars($message);

    // Get all admins
    $admins = getAdmins();
    $admins[ADMIN_CHAT_ID] = [];

    // Insert into tickets table BEFORE sending
    $ticket_id = uniqid('ticket_', true);
    $stmt = pdo()->prepare("INSERT INTO tickets (id, user_id, user_name, subject, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())");
    $stmt->execute([$ticket_id, $user['chat_id'], $user['first_name'], $subject]);

    $stmt_conv = pdo()->prepare("INSERT INTO ticket_conversations (ticket_id, sender, message_text, sent_at) VALUES (?, 'user', ?, NOW())");
    $stmt_conv->execute([$ticket_id, $message]);

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '💬 پاسخ', 'callback_data' => "reply_ticket_{$ticket_id}"]
            ]
        ]
    ];

    $sent = false;
    foreach (array_keys($admins) as $admin_id) {
        if (hasPermission($admin_id, 'manage_users')) {
            $result = sendMessage($admin_id, $ticket_message, $keyboard);
            if ($result) {
                $sent = true;
            }
        }
    }

    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'پیام شما برای پشتیبانی ارسال شد']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در ارسال پیام. لطفاً با آیدی تلگرام تماس بگیرید.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>پشتیبانی</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
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
                <h2 style="margin-right: 12px;">پشتیبانی</h2>
            </div>
            <button class="theme-toggle" onclick="ThemeManager.toggle()" aria-label="تغییر تم">
                <i class="fas fa-moon"></i>
                <i class="fas fa-sun"></i>
            </button>
        </div>

        <!-- Contact Info Card -->
        <div class="card"
            style="margin-bottom: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div style="padding: 20px; text-align: center;">
                <i class="fas fa-headset" style="font-size: 3rem; margin-bottom: 12px; opacity: 0.9;"></i>
                <h3 style="margin-bottom: 12px;">پشتیبانی ۲۴ ساعته</h3>
                <?php if (!empty($settings['support_username'])): ?>
                    <a href="https://t.me/<?php echo $settings['support_username']; ?>" class="btn"
                        style="background: white; color: #667eea; text-decoration: none; display: inline-block; margin-top: 8px;">
                        <i class="fab fa-telegram"></i>
                        @<?php echo $settings['support_username']; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ticket Form -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">ارسال تیکت پشتیبانی</div>
            </div>
            <div style="padding: 16px;">
                <form id="ticket-form" onsubmit="sendTicket(event)">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">موضوع:</label>
                        <input type="text" id="subject" class="form-control" placeholder="موضوع تیکت را وارد کنید"
                            required
                            style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: var(--radius); font-family: inherit;">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">پیام:</label>
                        <textarea id="message" class="form-control" placeholder="پیام خود را بنویسید..." required
                            rows="6"
                            style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: var(--radius); font-family: inherit; resize: vertical;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i>
                        ارسال تیکت
                    </button>
                </form>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">سوالات متداول</div>
            </div>
            <div style="padding: 16px;">
                <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color);">
                    <div style="font-weight: 600; margin-bottom: 8px;">
                        <i class="fas fa-question-circle text-primary"></i>
                        چگونه سرویس خریداری کنم؟
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted);">
                        از بخش فروشگاه، دسته‌بندی، سرور و پلن مورد نظر را انتخاب کنید و پس از پرداخت، سرویس برای شما
                        ساخته می‌شود.
                    </div>
                </div>

                <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color);">
                    <div style="font-weight: 600; margin-bottom: 8px;">
                        <i class="fas fa-question-circle text-primary"></i>
                        چگونه کیف پول را شارژ کنم؟
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted);">
                        از بخش کیف پول می‌توانید با درگاه آنلاین یا کارت به کارت، حساب خود را شارژ کنید.
                    </div>
                </div>

                <div>
                    <div style="font-weight: 600; margin-bottom: 8px;">
                        <i class="fas fa-question-circle text-primary"></i>
                        لینک اشتراک چیست؟
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted);">
                        لینک اشتراک (Subscription) را در برنامه‌های V2Ray خود وارد کنید تا به سرویس متصل شوید.
                    </div>
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
        tg.ready();
        tg.expand();

        function sendTicket(event) {
            event.preventDefault();

            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();

            if (!subject || !message) {
                tg.showAlert('لطفاً تمام فیلدها را پر کنید');
                return;
            }

            showLoading();

            fetch('support.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=send_ticket&subject=${encodeURIComponent(subject)}&message=${encodeURIComponent(message)}`
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
                            document.getElementById('ticket-form').reset();
                        });
                    } else {
                        tg.showAlert(data.message || 'خطا در ارسال تیکت');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    tg.showAlert('خطا در ارتباط با سرور');
                });
        }

        // Theme is now handled by theme.js automatically
    </script>
</body>

</html>