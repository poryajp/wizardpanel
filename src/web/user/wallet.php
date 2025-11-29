<?php
require_once __DIR__ . '/session.php';
requireUserLogin();

$user = getCurrentUser();
$settings = getSettings();

// Handle Payment Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = (int) $_POST['amount'];
    $method = $_POST['method'];

    if ($amount < 1000) {
        echo json_encode(['success' => false, 'message' => 'مبلغ باید حداقل ۱۰۰۰ تومان باشد']);
        exit;
    }

    if ($method === 'zarinpal') {
        if (($settings['payment_gateway_status'] ?? 'off') !== 'on') {
            echo json_encode(['success' => false, 'message' => 'درگاه پرداخت غیرفعال است']);
            exit;
        }

        $result = createZarinpalLink($user['chat_id'], $amount, "شارژ کیف پول کاربر {$user['chat_id']}");
        if ($result['success']) {
            echo json_encode(['success' => true, 'url' => $result['url']]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['error']]);
        }
        exit;
    }
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
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="user-profile">
                <a href="index.php" style="color: var(--text-color); text-decoration: none; font-size: 1.2rem;">
                    <i class="fas fa-arrow-right"></i>
                </a>
                <h2 style="margin-right: 12px;">کیف پول</h2>
            </div>
        </div>

        <!-- Balance Card -->
        <div class="card"
            style="text-align: center; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div style="font-size: 0.9rem; margin-bottom: 8px; opacity: 0.9;">موجودی فعلی</div>
            <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;">
                <?php echo number_format($user['balance']); ?>
            </div>
            <div style="font-size: 1rem; opacity: 0.9;">تومان</div>
        </div>

        <!-- Add Balance -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-plus-circle text-primary"></i> افزایش موجودی
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">مبلغ به تومان:</label>
                <input type="number" id="amount" class="form-control" placeholder="مثلا: 50000"
                    style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: var(--radius); font-family: inherit; font-size: 1.1rem;">
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">حداقل مبلغ: ۱۰۰۰ تومان</div>
            </div>

            <div style="display: grid; gap: 12px;">
                <?php if (($settings['payment_gateway_status'] ?? 'off') === 'on'): ?>
                    <button class="btn btn-primary" onclick="pay('zarinpal')">
                        <i class="fas fa-globe"></i> پرداخت آنلاین (زرین‌پال)
                    </button>
                <?php endif; ?>

                <?php if (!empty($settings['payment_method']['card_number'])): ?>
                    <button class="btn btn-outline" onclick="showCardInfo()">
                        <i class="fas fa-credit-card"></i> کارت به کارت
                    </button>
                <?php else: ?>
                    <div
                        style="padding: 12px; background: var(--bg-color); border-radius: var(--radius); text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> درگاه کارت به کارت تنظیم نشده است
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Guide -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-info-circle text-warning"></i> راهنمای شارژ کیف پول
                </div>
            </div>
            <div style="font-size: 0.9rem; line-height: 1.8;">
                <div style="margin-bottom: 8px;">
                    <i class="fas fa-check text-success"></i> مبلغ مورد نظر خود را وارد کنید
                </div>
                <div style="margin-bottom: 8px;">
                    <i class="fas fa-check text-success"></i> روش پرداخت را انتخاب کنید
                </div>
                <div>
                    <i class="fas fa-check text-success"></i> پس از پرداخت، موجودی شما بروز می‌شود
                </div>
            </div>
        </div>

    </div>

    <!-- Card Info Modal -->
    <div id="card-modal" class="loading-overlay" style="display: none; background: rgba(0,0,0,0.9);">
        <div class="card" style="width: 90%; max-width: 400px;">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-credit-card text-primary"></i> کارت به کارت
                </div>
                <i class="fas fa-times" onclick="closeCardModal()" style="cursor: pointer; font-size: 1.2rem;"></i>
            </div>
            <div style="margin-bottom: 20px;">
                <div id="amount-reminder"
                    style="display: none; padding: 12px; background: var(--bg-color); border-radius: var(--radius); margin-bottom: 12px; text-align: center;">
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px;">مبلغ درخواستی:</div>
                    <div id="reminder-amount"
                        style="font-size: 1.3rem; font-weight: bold; color: var(--primary-color);"></div>
                </div>

                <p style="margin-bottom: 16px; font-size: 0.95rem;">لطفا مبلغ مورد نظر را به کارت زیر واریز کنید و تصویر
                    رسید را برای پشتیبانی ارسال کنید.</p>

                <div
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px; border-radius: 12px; margin-bottom: 16px;">
                    <div
                        style="text-align: center; margin-bottom: 12px; color: white; font-size: 0.85rem; opacity: 0.9;">
                        شماره کارت</div>
                    <div
                        style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(10px);">
                        <span id="card-number"
                            style="font-family: monospace; font-size: 1.15rem; color: white; letter-spacing: 2px;"><?php echo $settings['payment_method']['card_number']; ?></span>
                        <i class="fas fa-copy"
                            onclick="copyCardNumber('<?php echo $settings['payment_method']['card_number']; ?>')"
                            style="cursor: pointer; color: white; font-size: 1.2rem;"></i>
                    </div>
                    <?php if (!empty($settings['payment_method']['card_holder'])): ?>
                        <div style="text-align: center; color: white; font-size: 0.9rem; margin-top: 8px; opacity: 0.95;">
                            <i class="fas fa-user"></i> <?php echo $settings['payment_method']['card_holder']; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div
                    style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                    <div style="font-size: 0.85rem; color: #856404;">
                        <i class="fas fa-exclamation-triangle"></i> <strong>مهم:</strong> حتماً رسید پرداخت را برای
                        پشتیبانی ارسال کنید تا موجودی شما شارژ شود.
                    </div>
                </div>

                <button class="btn btn-primary" onclick="openSupport()">
                    <i class="fab fa-telegram"></i> ارسال رسید به پشتیبانی
                </button>
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
        <a href="wallet.php" class="nav-item active">
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

        function pay(method) {
            const amount = document.getElementById('amount').value;
            if (!amount || amount < 1000) {
                alert('لطفا مبلغ معتبر وارد کنید (حداقل ۱۰۰۰ تومان)');
                return;
            }

            if (method === 'zarinpal') {
                showLoading();
                fetch('wallet.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `amount=${amount}&method=zarinpal`
                })
                    .then(res => res.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            tg.openLink(data.url);
                        } else {
                            alert(data.message || 'خطا در ایجاد لینک پرداخت');
                        }
                    })
                    .catch(() => {
                        hideLoading();
                        alert('خطا در ارتباط با سرور');
                    });
            }
        }

        function showCardInfo() {
            const amount = document.getElementById('amount').value;

            // Show amount reminder if entered
            if (amount && amount >= 1000) {
                document.getElementById('amount-reminder').style.display = 'block';
                document.getElementById('reminder-amount').textContent = formatPrice(amount);
            } else {
                document.getElementById('amount-reminder').style.display = 'none';
            }

            document.getElementById('card-modal').style.display = 'flex';
        }

        function closeCardModal() {
            document.getElementById('card-modal').style.display = 'none';
        }

        function copyCardNumber(cardNumber) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(cardNumber).then(() => {
                    tg.showPopup({
                        message: 'شماره کارت کپی شد'
                    });
                }).catch(() => {
                    fallbackCopy(cardNumber);
                });
            } else {
                fallbackCopy(cardNumber);
            }
        }

        function fallbackCopy(text) {
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            alert('شماره کارت کپی شد');
        }

        function openSupport() {
            <?php if (!empty($settings['support_id'])): ?>
                const support_username = '<?php echo str_replace('@', '', $settings['support_id']); ?>';
                tg.openTelegramLink('https://t.me/' + support_username);
            <?php else: ?>
                alert('اطلاعات پشتیبانی تنظیم نشده است');
            <?php endif; ?>
        }
    </script>
</body>

</html>