<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Web App Auth Test</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f0f0f0;
            direction: rtl;
        }

        .info-box {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .info-box h3 {
            margin-top: 0;
            color: #333;
        }

        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            direction: ltr;
            text-align: left;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .warning {
            color: orange;
        }
    </style>
</head>

<body>
    <div class="info-box">
        <h3>تنظیمات Config</h3>
        <p><strong>BOT_TOKEN:</strong>
            <?php echo defined('BOT_TOKEN') ? (strlen(BOT_TOKEN) > 10 ? 'تنظیم شده (' . strlen(BOT_TOKEN) . ' کاراکتر)' : 'خیلی کوتاه!') : '<span class="error">تنظیم نشده!</span>'; ?>
        </p>
        <p><strong>BASE_URL:</strong>
            <?php echo defined('BASE_URL') ? BASE_URL : '<span class="error">تنظیم نشده!</span>'; ?></p>
    </div>

    <div class="info-box">
        <h3>اطلاعات Session</h3>
        <p><strong>Session Active:</strong>
            <?php echo isset($_SESSION) ? '<span class="success">بله</span>' : '<span class="error">خیر</span>'; ?></p>
        <p><strong>User Logged In:</strong>
            <?php echo isset($_SESSION['user_id']) ? '<span class="success">بله (ID: ' . $_SESSION['user_id'] . ')</span>' : '<span class="warning">خیر</span>'; ?>
        </p>
        <p><strong>Session Data:</strong></p>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>

    <div class="info-box">
        <h3>اطلاعات Telegram WebApp</h3>
        <p><strong>WebApp Loaded:</strong> <span id="webapp-status" class="warning">در حال بررسی...</span></p>
        <p><strong>initData:</strong></p>
        <pre id="init-data" style="word-break: break-all;">در حال بارگذاری...</pre>
        <p><strong>initDataUnsafe:</strong></p>
        <pre id="init-data-unsafe">در حال بارگذاری...</pre>
    </div>

    <div class="info-box">
        <h3>تست احراز هویت</h3>
        <button onclick="testAuth()"
            style="padding: 10px 20px; background: #0088cc; color: white; border: none; border-radius: 4px; cursor: pointer;">
            تست احراز هویت
        </button>
        <div id="auth-result" style="margin-top: 15px;"></div>
    </div>

    <div class="info-box">
        <h3>اطلاعات سرور</h3>
        <p><strong>Current URL:</strong> <?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></p>
        <p><strong>HTTPS:</strong>
            <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '<span class="success">بله</span>' : '<span class="error">خیر</span>'; ?>
        </p>
        <p><strong>User Agent:</strong> <?php echo $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'; ?></p>
    </div>

    <script>
        const tg = window.Telegram.WebApp;

        // Initialize WebApp
        tg.ready();
        tg.expand();

        // Display WebApp info
        document.getElementById('webapp-status').innerHTML = '<span class="success">بله</span>';
        document.getElementById('init-data').textContent = tg.initData || 'خالی است!';
        document.getElementById('init-data-unsafe').textContent = JSON.stringify(tg.initDataUnsafe, null, 2) || 'خالی است!';

        async function testAuth() {
            const resultDiv = document.getElementById('auth-result');
            resultDiv.innerHTML = '<p class="warning">در حال ارسال درخواست...</p>';

            if (!tg.initData) {
                resultDiv.innerHTML = '<p class="error">❌ خطا: initData خالی است! این صفحه باید از داخل تلگرام باز شود.</p>';
                return;
            }

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'initData=' + encodeURIComponent(tg.initData)
                });

                const data = await response.json();

                if (data.success) {
                    resultDiv.innerHTML = '<p class="success">✅ احراز هویت موفقیت‌آمیز بود! صفحه را رفرش کنید.</p>';
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    resultDiv.innerHTML = '<p class="error">❌ خطا در احراز هویت: ' + (data.error || 'نامشخص') + '</p>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<p class="error">❌ خطا در ارتباط با سرور: ' + error.message + '</p>';
                console.error('Auth error:', error);
            }
        }

        // Auto test if not logged in
        <?php if (!isset($_SESSION['user_id'])): ?>
            console.log('User not logged in, checking initData...');
            if (tg.initData) {
                console.log('initData found, attempting auto-login...');
                // Uncomment next line to auto-test
                // testAuth();
            } else {
                console.error('No initData available! Page must be opened from Telegram.');
            }
        <?php endif; ?>
    </script>
</body>

</html>