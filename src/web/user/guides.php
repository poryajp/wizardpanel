<?php
require_once __DIR__ . '/session.php';
requireUserLogin();

$user = getCurrentUser();
$guides = pdo()->query("SELECT * FROM guides WHERE status = 'active' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>راهنماها</title>
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
                <h2 style="margin-right: 12px;">راهنماها</h2>
            </div>
        </div>

        <?php if (empty($guides)): ?>
            <div class="card">
                <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <i class="fas fa-book-open" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p>هیچ راهنمایی موجود نیست.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($guides as $guide): ?>
                <div class="card" style="margin-bottom: 12px; cursor: pointer;"
                    onclick="showGuide(<?php echo $guide['id']; ?>, '<?php echo htmlspecialchars($guide['button_name'], ENT_QUOTES); ?>', <?php echo htmlspecialchars(json_encode($guide['content']), ENT_QUOTES); ?>)">
                    <div style="padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px;">
                                <i class="fas fa-book text-primary"></i>
                                <?php echo htmlspecialchars($guide['button_name']); ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-left text-muted"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Guide Content Modal -->
    <div id="guide-modal" class="loading-overlay" style="display: none; background: rgba(0,0,0,0.5);">
        <div class="card" style="width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto;">
            <div class="card-header">
                <div class="card-title" id="guide-title"></div>
                <i class="fas fa-times" onclick="closeGuide()" style="cursor: pointer;"></i>
            </div>
            <div id="guide-content" style="padding: 16px; white-space: pre-wrap; line-height: 1.8;"></div>
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

        function showGuide(id, title, content) {
            document.getElementById('guide-title').textContent = title;
            document.getElementById('guide-content').textContent = content;
            document.getElementById('guide-modal').style.display = 'flex';
        }

        function closeGuide() {
            document.getElementById('guide-modal').style.display = 'none';
        }

        // Theme
        if (tg.colorScheme === 'dark') {
            document.body.classList.add('dark-theme');
        }
    </script>
</body>

</html>