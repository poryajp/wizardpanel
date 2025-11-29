<?php
require_once __DIR__ . '/session.php';

// Handle Telegram Web App Login
if (isset($_POST['initData'])) {
    $userData = validateTelegramWebAppData($_POST['initData']);
    if ($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['first_name'] = $userData['first_name'];
        $_SESSION['username'] = $userData['username'] ?? '';

        // Ensure user exists in DB
        $dbUser = getUserData($userData['id'], $userData['first_name']);

        echo json_encode(['success' => true]);
        exit;
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>پنل کاربری</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Loading Screen -->
    <div id="loading" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <!-- Login Screen -->
    <div id="login-screen" style="display: none; height: 100vh; align-items: center; justify-content: center; text-align: center;">
        <div>
            <i class="fas fa-shield-alt" style="font-size: 48px; color: var(--primary-color); margin-bottom: 20px;"></i>
            <h3>در حال احراز هویت...</h3>
        </div>
    </div>

    <!-- Main Content -->
    <div id="main-content" style="display: none;">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <div class="user-profile">
                    <div class="avatar">
                        <?php echo mb_substr($user['first_name'] ?? 'U', 0, 1); ?>
                    </div>
                    <div class="user-info">
                        <h2><?php echo htmlspecialchars($user['first_name'] ?? 'کاربر'); ?></h2>
                        <p><?php echo $user['chat_id'] ?? ''; ?></p>
                    </div>
                </div>
                <div class="wallet-badge">
                    <span class="text-success"><i class="fas fa-wallet"></i>
                        <?php echo number_format($user['balance'] ?? 0); ?> تومان</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?php echo count(getUserServices($user['chat_id'] ?? 0)); ?></div>
                    <div class="stat-label">سرویس‌های فعال</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($user['balance'] ?? 0); ?></div>
                    <div class="stat-label">موجودی کیف پول</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-bolt text-warning"></i> دسترسی سریع
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <a href="shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> خرید سرویس
                    </a>
                    <a href="wallet.php" class="btn btn-outline">
                        <i class="fas fa-plus-circle"></i> افزایش موجودی
                    </a>
                </div>
            </div>

            <!-- Recent Services -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-server text-primary"></i> سرویس‌های اخیر
                    </div>
                    <a href="services.php" style="font-size: 0.85rem; text-decoration: none; color: var(--primary-color);">مشاهده همه</a>
                </div>
                <?php
                $services = getUserServices($user['chat_id'] ?? 0);
                $recent_services = array_slice($services, 0, 3);
                if (empty($recent_services)):
                    ?>
                    <div style="text-align: center; padding: 20px; color: var(--text-muted);">
                        <i class="fas fa-box-open" style="font-size: 32px; margin-bottom: 8px; opacity: 0.5;"></i>
                        <p>هیچ سرویسی ندارید</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_services as $service): ?>
                        <div style="padding: 12px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($service['plan_name']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <?php echo $service['marzban_username']; ?></div>
                            </div>
                            <span class="text-success" style="font-size: 0.85rem;">فعال</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bottom Nav -->
        <div class="bottom-nav">
            <a href="index.php" class="nav-item active">
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
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                const tg = window.Telegram?.WebApp;
                
                <?php if (!isUserLoggedIn()): ?>
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('login-screen').style.display = 'flex';

                    if (!tg) {
                        alert('خطا: این برنامه فقط از طریق تلگرام قابل دسترسی است.');
                        return;
                    }

                    const initData = tg.initData;
                    
                    if (!initData || initData.length === 0) {
                        alert('خطا: اطلاعات ورود یافت نشد. لطفاً دوباره از ربات وارد شوید.');
                        return;
                    }

                    fetch('index.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'initData=' + encodeURIComponent(initData)
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('HTTP ' + response.status);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('خطا در احراز هویت: ' + (data.error || 'خطای ناشناخته'));
                            }
                        })
                        .catch(error => {
                            alert('خطا در ارتباط با سرور: ' + error.message);
                        });
                <?php else: ?>
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('main-content').style.display = 'block';
                    
                    if (tg) {
                        tg.expand();
                    }
                <?php endif; ?>
            }, 500);
        });
    </script>
</body>
</html>