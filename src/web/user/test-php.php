<?php
// تست ساده بدون نیاز به session یا database
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تست PHP - OpenLiteSpeed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            direction: rtl;
        }

        .container {
            background: white;
            color: #2d3748;
            padding: 30px;
            border-radius: 16px;
            max-width: 800px;
            margin: 20px auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #48bb78;
            border-bottom: 3px solid #48bb78;
            padding-bottom: 10px;
        }

        .info-box {
            background: #f7fafc;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-right: 4px solid #4299e1;
        }

        .success {
            background: #c6f6d5;
            border-right-color: #48bb78;
        }

        .warning {
            background: #fef5e7;
            border-right-color: #f6ad55;
        }

        .error {
            background: #fed7d7;
            border-right-color: #fc8181;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #4299e1;
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background: #f7fafc;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4299e1;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 5px;
            font-weight: bold;
        }

        .btn:hover {
            background: #3182ce;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>✅ عالی! PHP کار می‌کند</h1>

        <div class="info-box success">
            <h3>🎉 PHP با موفقیت اجرا شد!</h3>
            <p>نسخه PHP: <strong><?php echo PHP_VERSION; ?></strong></p>
            <p>سرور: <strong><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'نامشخص'; ?></strong></p>
        </div>

        <h2>اطلاعات سرور</h2>
        <table>
            <tr>
                <th>پارامتر</th>
                <th>مقدار</th>
            </tr>
            <tr>
                <td>Document Root</td>
                <td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>Script Filename</td>
                <td><?php echo $_SERVER['SCRIPT_FILENAME'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>Request URI</td>
                <td><?php echo $_SERVER['REQUEST_URI'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>HTTP Host</td>
                <td><?php echo $_SERVER['HTTP_HOST'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>HTTPS</td>
                <td><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '✅ فعال' : '❌ غیرفعال'; ?>
                </td>
            </tr>
            <tr>
                <td>Server Software</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td>
            </tr>
        </table>

        <h2>بررسی مسیرها</h2>
        <div class="info-box">
            <p><strong>مسیر فعلی:</strong> <?php echo __DIR__; ?></p>
            <p><strong>فایل فعلی:</strong> <?php echo __FILE__; ?></p>
        </div>

        <?php
        // بررسی فایل config
        $configPath = __DIR__ . '/../../includes/config.php';
        ?>
        <div class="info-box <?php echo file_exists($configPath) ? 'success' : 'error'; ?>">
            <h3>فایل Config</h3>
            <p><strong>مسیر:</strong> <?php echo $configPath; ?></p>
            <p><strong>وضعیت:</strong> <?php echo file_exists($configPath) ? '✅ موجود است' : '❌ یافت نشد!'; ?></p>
            <?php if (file_exists($configPath)): ?>
                <p><strong>قابل خواندن:</strong>
                    <?php echo is_readable($configPath) ? '✅ بله' : '❌ خیر - مجوزها را بررسی کنید!'; ?></p>
            <?php endif; ?>
        </div>

        <?php
        // بررسی session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        ?>
        <div class="info-box success">
            <h3>Session</h3>
            <p><strong>وضعیت:</strong> ✅ فعال</p>
            <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
            <p><strong>Save Path:</strong> <?php echo session_save_path(); ?></p>
        </div>

        <h2>تنظیمات PHP</h2>
        <table>
            <tr>
                <th>تنظیم</th>
                <th>مقدار</th>
            </tr>
            <tr>
                <td>max_execution_time</td>
                <td><?php echo ini_get('max_execution_time'); ?> ثانیه</td>
            </tr>
            <tr>
                <td>memory_limit</td>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
            <tr>
                <td>upload_max_filesize</td>
                <td><?php echo ini_get('upload_max_filesize'); ?></td>
            </tr>
            <tr>
                <td>post_max_size</td>
                <td><?php echo ini_get('post_max_size'); ?></td>
            </tr>
        </table>

        <h2>مرحله بعدی</h2>
        <div class="info-box warning">
            <p>اگر این صفحه را می‌بینید، یعنی PHP به درستی کار می‌کند. حالا باید صفحه اصلی را تست کنید:</p>
            <a href="index.php" class="btn">رفتن به صفحه اصلی</a>
            <a href="test-telegram-auth.php" class="btn" style="background: #ed8936;">تست احراز هویت تلگرام</a>
        </div>

        <div class="info-box">
            <h3>📋 دستورات مفید برای OpenLiteSpeed</h3>
            <p>اگر هنوز مشکل دارید، این دستورات را در سرور اجرا کنید:</p>
            <pre
                style="background: #2d3748; color: #48bb78; padding: 15px; border-radius: 8px; overflow-x: auto; direction: ltr; text-align: left;">
# بررسی مجوزها
ls -la <?php echo __DIR__; ?>

# تنظیم مجوزهای صحیح
find <?php echo dirname(dirname(__DIR__)); ?> -type f -exec chmod 644 {} \;
find <?php echo dirname(dirname(__DIR__)); ?> -type d -exec chmod 755 {} \;

# راه‌اندازی مجدد OpenLiteSpeed
systemctl restart lsws
# یا
/usr/local/lsws/bin/lswsctrl restart
            </pre>
        </div>
    </div>
</body>

</html>