<?php
require_once __DIR__ . '/session.php';
requireUserLogin();

$stmt = pdo()->query("SELECT * FROM guides WHERE status = 'active' ORDER BY id DESC");
$guides = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>آموزش‌ها</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="user-profile">
                <a href="index.php" style="color: var(--text-color); text-decoration: none; font-size: 1.2rem;">
                    <i class="fas fa-arrow-right"></i>
                </a>
                <h2 style="margin-right: 12px;">آموزش‌ها</h2>
            </div>
        </div>

        <?php if (empty($guides)): ?>
            <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                <i class="fas fa-book-open" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <p>هیچ آموزشی یافت نشد.</p>
            </div>
        <?php else: ?>
            <?php foreach ($guides as $guide): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><?php echo htmlspecialchars($guide['title']); ?></div>
                    </div>
                    <div style="white-space: pre-wrap; font-size: 0.9rem; line-height: 1.8;">
                        <?php echo htmlspecialchars($guide['message_text']); ?></div>

                    <?php if ($guide['content_type'] === 'photo' && $guide['photo_id']): ?>
                        <div style="margin-top: 12px; font-size: 0.8rem; color: var(--text-muted);">
                            <i class="fas fa-image"></i> این آموزش شامل تصویر است که در ربات قابل مشاهده است.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
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
        <a href="guides.php" class="nav-item active">
            <i class="fas fa-book"></i>
            <span>آموزش</span>
        </a>
    </div>

    <script src="assets/js/app.js"></script>
</body>

</html>