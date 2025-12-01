<?php
// غیرفعال کردن نمایش خطاها در خروجی برای جلوگیری از خراب شدن JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// لود فایل session اول (در همان پوشه)
require_once __DIR__ . '/session.php';
// لود کردن کاربر حاضر
$user = getCurrentUser();
requireUserLogin();
// لود فایل کانفیگ - مسیر صحیح بر اساس ساختار جدید (web در داخل wp)
require_once __DIR__ . '/../../includes/config.php';
// لود تنظیمات
$settings = getSettings();
// تعریف ثابت USER_INLINE_KEYBOARD برای جلوگیری از خطا
if (!defined('USER_INLINE_KEYBOARD')) {
    define('USER_INLINE_KEYBOARD', ($settings['inline_keyboard'] ?? 'on') === 'on');
}
// تعریف ثابت ADMIN_CHAT_ID اگر در کانفیگ وجود داشته باشد
if (!defined('ADMIN_CHAT_ID')) {
    $admin_chat_id = $config['telegram']['admin_chat_id'] ?? 12345678;
    define('ADMIN_CHAT_ID', $admin_chat_id);
}
// Handle photo upload for card-to-card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt']) && isset($_POST['action']) && $_POST['action'] === 'upload_receipt') {
    // پاک کردن بافر خروجی برای اطمینان از اینکه هیچ دیتای اضافی ارسال نمی‌شود
    while (ob_get_level())
        ob_end_clean();
    header('Content-Type: application/json');
    $amount = (int) $_POST['amount'];
    if ($amount < 1000) {
        echo json_encode(['success' => false, 'message' => 'مبلغ باید حداقل ۱۰۰۰ تومان باشد']);
        exit;
    }
    // Validate file
    $file = $_FILES['receipt'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'حجم فایل بیشتر از حد مجاز است',
            UPLOAD_ERR_FORM_SIZE => 'حجم فایل بیشتر از حد مجاز فرم است',
            UPLOAD_ERR_PARTIAL => 'فایل به صورت ناقص آپلود شده',
            UPLOAD_ERR_NO_FILE => 'هیچ فایلی انتخاب نشده',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت وجود ندارد',
            UPLOAD_ERR_CANT_WRITE => 'خطا در نوشتن فایل',
            UPLOAD_ERR_EXTENSION => 'آپلود توسط افزونه متوقف شده'
        ];
        $error = $error_messages[$file['error']] ?? 'خطای نامشخص';
        echo json_encode(['success' => false, 'message' => 'خطا در آپلود فایل: ' . $error]);
        exit;
    }
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'فقط فایل‌های JPG, PNG و WebP مجاز هستند']);
        exit;
    }
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
        echo json_encode(['success' => false, 'message' => 'حجم فایل نباید بیشتر از ۵ مگابایت باشد']);
        exit;
    }
    // Save photo - مسیر صحیح بر اساس ساختار جدید
    $upload_dir = __DIR__ . '/uploads/receipts/';
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'خطا در ایجاد پوشه آپلود: ' . $upload_dir]);
            exit;
        }
        chmod($upload_dir, 0777);
    }
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('receipt_') . '_' . time() . '.' . ($file_extension ?: 'jpg');
    $filepath = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Make file readable
        chmod($filepath, 0644);

        // Get all admins
        $admins = getAdmins() ?: [];
        $admins[ADMIN_CHAT_ID] = ['permissions' => ['manage_payment']]; // Add main admin

        // Insert into payment_requests table BEFORE sending to Telegram
        $stmt = pdo()->prepare("INSERT INTO payment_requests (user_id, amount, photo_file_id, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([$user['chat_id'], $amount, $filename]);
        $request_id = pdo()->lastInsertId();

        try {
            // Load Telegram functions - مسیر صحیح
            require_once __DIR__ . '/../../includes/functions.php';
            $caption = "💳 درخواست شارژ کیف پول\n" .
                "👤 کاربر: " . htmlspecialchars($user['first_name'] ?? 'ناشناس') . "\n" .
                "🆔 شناسه: <code>{$user['chat_id']}</code>\n" .
                "💰 مبلغ: " . number_format($amount) . " تومان";
            // Get all admins

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ تایید و شارژ', 'callback_data' => "approve_{$request_id}"],
                        ['text' => '❌ رد کردن', 'callback_data' => "reject_{$request_id}"]
                    ]
                ]
            ];
            $sent = false;
            $errors = [];
            foreach (array_keys($admins) as $admin_id) {
                // Check if admin has permission
                $has_permission = false;
                if (isset($admins[$admin_id]['permissions']) && is_array($admins[$admin_id]['permissions'])) {
                    $has_permission = in_array('manage_payment', $admins[$admin_id]['permissions']);
                } elseif (function_exists('hasPermission')) {
                    $has_permission = hasPermission($admin_id, 'manage_payment');
                }
                if ($has_permission) {
                    try {
                        $result = sendPhoto($admin_id, $filepath, $caption, $keyboard);
                        if ($result) {
                            $sent = true;
                        } else {
                            $errors[] = "عدم موفقیت در ارسال به ادمین {$admin_id}";
                        }
                    } catch (Exception $e) {
                        $errors[] = "خطا در ارسال به ادمین {$admin_id}: " . $e->getMessage();
                    }
                }
            }
            if ($sent) {
                echo json_encode(['success' => true, 'message' => 'رسید شما برای ادمین ارسال شد. پس از بررسی، کیف پول شما شارژ خواهد شد.']);
            } else {
                $error_message = !empty($errors) ? implode("\n", $errors) : 'خطا در ارسال به ادمین‌ها';
                echo json_encode(['success' => false, 'message' => 'رسید شما آپلود شد اما در ارسال به ادمین‌ها مشکلی پیش آمد. لطفاً با پشتیبانی تماس بگیرید.']);
            }
        } catch (Exception $e) {
            // Log error to server log
            error_log('خطای پردازش پس از آپلود: ' . $e->getMessage());
            // For security, don't expose full error details to user
            echo json_encode([
                'success' => true,
                'message' => 'رسید شما با موفقیت آپلود شد. پس از بررسی دستی توسط ادمین، کیف پول شما شارژ خواهد شد.'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره فایل. لطفاً دوباره تلاش کنید.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>کیف پول</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .dark-theme .loading-overlay {
            background: rgba(0, 0, 0, 0.8);
        }

        .dark-theme .spinner {
            border-color: #333;
            border-top-color: #764ba2;
        }
    </style>
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
                <h2 style="margin-right: 12px;">کیف پول</h2>
            </div>
        </div>
        <div class="card"
            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 16px;">
            <div style="padding: 24px; text-align: center;">
                <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">موجودی کیف پول</div>
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 4px;">
                    <?php echo number_format($user['balance']); ?>
                </div>
                <div style="font-size: 0.9rem; opacity: 0.8;">تومان</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">مبلغ شارژ</div>
            </div>
            <div style="padding: 16px;">
                <input type="number" id="amount" class="form-control" placeholder="مبلغ به تومان (حداقل ۱۰۰۰)"
                    style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: var(--radius); font-family: inherit; font-size: 1rem;">
                <div style="margin-top: 8px; font-size: 0.85rem; color: var(--text-muted);">
                    <i class="fas fa-info-circle"></i>
                    حداقل مبلغ شارژ: ۱,۰۰۰ تومان
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">روش پرداخت</div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr; gap: 12px; padding: 16px;">
                <?php $zarinpal_status = $settings['payment_gateway_status'] ?? 'off'; ?>
                <?php if ($zarinpal_status === 'on'): ?>
                    <button class="btn btn-primary" onclick="chargeZarinpal()">
                        <i class="fas fa-credit-card"></i>
                        پرداخت آنلاین (زرین‌پال)
                    </button>
                <?php endif; ?>
                <button class="btn btn-success" onclick="showCardInfo()">
                    <i class="fas fa-credit-card"></i>
                    کارت به کارت
                </button>
            </div>
        </div>
    </div>
    <div id="card-modal" class="loading-overlay" style="display: none; background: rgba(0,0,0,0.5);">
        <div class="card" style="width: 90%; max-width: 450px;">
            <div class="card-header">
                <div class="card-title">اطلاعات کارت</div>
                <i class="fas fa-times" onclick="closeCardModal()" style="cursor: pointer;"></i>
            </div>
            <div style="padding: 16px;">
                <?php
                $payment_method = $settings['payment_method'] ?? [];
                $card_number = $payment_method['card_number'] ?? 'شماره کارت تنظیم نشده';
                $card_holder = $payment_method['card_holder'] ?? 'صاحب حساب تنظیم نشده';
                ?>
                <div id="amount-reminder"
                    style="display: none; margin-bottom: 16px; padding: 12px; background: var(--bg-secondary); border-radius: var(--radius);">
                    <strong>مبلغ قابل پرداخت:</strong>
                    <span id="reminder-amount" style="color: var(--primary-color); font-weight: bold;"></span>
                </div>
                <div
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: var(--radius); margin-bottom: 16px;">
                    <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 8px;">شماره کارت</div>
                    <div
                        style="font-size: 1.3rem; font-weight: bold; letter-spacing: 2px; margin-bottom: 12px; direction: ltr;">
                        <?php echo $card_number; ?>
                    </div>
                    <div style="font-size: 0.85rem; opacity: 0.9;">صاحب حساب:
                        <?php echo htmlspecialchars($card_holder); ?>
                    </div>
                </div>
                <button class="btn btn-outline" onclick="copyCardNumber('<?php echo $card_number; ?>')"
                    style="width: 100%; margin-bottom: 12px;">
                    <i class="fas fa-copy"></i>
                    کپی شماره کارت
                </button>
                <div
                    style="background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: var(--radius); margin-bottom: 16px; color: #856404; font-size: 0.9rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>توجه:</strong> پس از واریز، حتماً رسید پرداخت را آپلود کنید.
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">آپلود رسید پرداخت:</label>
                    <input type="file" id="receipt-file" accept="image/*" style="display: none;"
                        onchange="handleFileSelect(this)">
                    <button class="btn btn-primary" onclick="document.getElementById('receipt-file').click()"
                        style="width: 100%;">
                        <i class="fas fa-camera"></i>
                        انتخاب عکس رسید
                    </button>
                    <div id="file-name" style="margin-top: 8px; font-size: 0.85rem; color: var(--text-muted);"></div>
                </div>
                <button id="upload-btn" class="btn btn-success" onclick="uploadReceipt()"
                    style="width: 100%; display: none;">
                    <i class="fas fa-upload"></i>
                    ارسال رسید
                </button>
                <a href="https://t.me/<?php echo trim($settings['support_username'] ?? 'support'); ?>"
                    class="btn btn-outline"
                    style="width: 100%; text-decoration: none; display: block; text-align: center; margin-top: 12px;">
                    <i class="fab fa-telegram"></i>
                    تماس با پشتیبانی
                </a>
            </div>
        </div>
    </div>
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
        <a href="wallet.php" class="nav-item active">
            <i class="fas fa-wallet"></i>
            <span>کیف پول</span>
        </a>
        <a href="support.php" class="nav-item">
            <i class="fas fa-headset"></i>
            <span>پشتیبانی</span>
        </a>
    </div>
    <script>
        // Check if Telegram WebApp is available
        const tg = window.Telegram?.WebApp || {
            ready: () => { },
            expand: () => { },
            showAlert: (message) => alert(message),
            showPopup: (options, callback) => {
                alert(options.message);
                if (callback) callback();
            },
            colorScheme: 'light'
        };
        tg.ready();
        tg.expand();
        let selectedFile = null;
        function formatPrice(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        function showCardInfo() {
            const amount = parseInt(document.getElementById('amount').value);
            if (!amount || amount < 1000) {
                tg.showAlert('لطفاً مبلغی حداقل ۱۰۰۰ تومان وارد کنید');
                return;
            }
            document.getElementById('amount-reminder').style.display = 'block';
            document.getElementById('reminder-amount').textContent = formatPrice(amount) + ' تومان';
            document.getElementById('card-modal').style.display = 'flex';
        }
        function chargeZarinpal() {
            const amount = parseInt(document.getElementById('amount').value);
            if (!amount || amount < 1000) {
                tg.showAlert('لطفاً مبلغی حداقل ۱۰۰۰ تومان وارد کنید');
                return;
            }
            document.getElementById('amount-reminder').style.display = 'block';
            document.getElementById('reminder-amount').textContent = formatPrice(amount) + ' تومان';
            document.getElementById('card-modal').style.display = 'flex';
            tg.showAlert('پرداخت آنلاین از طریق درگاه زرین‌پال در حال پیاده‌سازی است');
        }
        function closeCardModal() {
            document.getElementById('card-modal').style.display = 'none';
            selectedFile = null;
            document.getElementById('file-name').textContent = '';
            document.getElementById('upload-btn').style.display = 'none';
            document.getElementById('amount-reminder').style.display = 'none';
        }
        function copyCardNumber(cardNumber) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(cardNumber).then(() => {
                    tg.showPopup({ message: '✓ شماره کارت کپی شد' });
                }).catch(err => {
                    tg.showAlert('خطا در کپی شماره کارت');
                });
            } else {
                try {
                    const tempInput = document.createElement('input');
                    tempInput.value = cardNumber;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                    tg.showPopup({ message: '✓ شماره کارت کپی شد' });
                } catch (err) {
                    tg.showAlert('خطا در کپی شماره کارت');
                }
            }
        }
        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    tg.showAlert('حجم فایل نباید بیشتر از ۵ مگابایت باشد');
                    input.value = ''; // Clear input
                    return;
                }
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    tg.showAlert('فقط فایل‌های JPG, PNG و WebP مجاز هستند');
                    input.value = ''; // Clear input
                    return;
                }
                selectedFile = file;
                document.getElementById('file-name').textContent = '✓ ' + selectedFile.name;
                document.getElementById('upload-btn').style.display = 'block';
            }
        }
        function uploadReceipt() {
            const amount = parseInt(document.getElementById('amount').value);
            if (!amount || amount < 1000) {
                tg.showAlert('لطفاً ابتدا مبلغ را وارد کنید');
                return;
            }
            if (!selectedFile) {
                tg.showAlert('لطفاً عکس رسید را انتخاب کنید');
                return;
            }
            const formData = new FormData();
            formData.append('action', 'upload_receipt');
            formData.append('amount', amount);
            formData.append('receipt', selectedFile);
            showLoading();
            closeCardModal();
            fetch('wallet.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('خطا در دریافت پاسخ از سرور');
                    }
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        tg.showPopup({
                            title: 'موفقیت‌آمیز',
                            message: data.message,
                            buttons: [{ type: 'ok' }]
                        }, function () {
                            window.location.reload();
                        });
                    } else {
                        tg.showAlert(data.message || 'خطا در ارسال رسید');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    tg.showAlert('خطا در ارتباط با سرور: ' + error.message);
                });
        }
        function showLoading() {
            document.getElementById('loading').style.display = 'flex';
        }
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
        // Handle dark mode
        if (tg.colorScheme === 'dark') {
            document.body.classList.add('dark-theme');
        }
        // Handle offline status
        window.addEventListener('offline', () => {
            tg.showAlert('اتصال اینترنت شما قطع شده است. لطفاً اتصال را بررسی کنید.');
        });
    </script>
</body>

</html>