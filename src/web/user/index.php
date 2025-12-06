<?php
require_once __DIR__ . '/session.php';

// Handle WebApp authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initData'])) {
    $userData = validateTelegramWebAppData($_POST['initData']);

    if ($userData) {
        // Use the new loginUser function which handles session regeneration
        loginUser($userData['id'], $userData['first_name'] ?? 'کاربر');

        echo json_encode(['success' => true]);
        exit;
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid authentication']);
        exit;
    }
}

// Check if user is logged in
$isLoggedIn = isUserLoggedIn();

// If logged in, get user data
if ($isLoggedIn) {
    $user = getCurrentUser();
    $services = getUserServices($user['chat_id']);

    // Calculate stats
    $total_services = count($services);
    $active_services = 0;
    $expired_services = 0;
    $now = time();

    foreach ($services as $service) {
        if ($service['expire_timestamp'] < $now) {
            $expired_services++;
        } else {
            $active_services++;
        }
    }

    // Get recent services (last 3)
    $recent_services = array_slice($services, 0, 3);
} else {
    // Set defaults for non-logged users (will be handled by JS)
    $user = ['first_name' => 'کاربر', 'chat_id' => 0, 'balance' => 0];
    $services = [];
    $total_services = 0;
    $active_services = 0;
    $expired_services = 0;
    $recent_services = [];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>پنل کاربری</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
</head>

<body>
    <!-- Loading Overlay -->
    <div id="loading" class="loading-overlay"
        style="display: <?php echo $isLoggedIn ? 'none' : 'flex'; ?>; justify-content: center; align-items: center; flex-direction: column;">
        <div class="spinner"></div>
        <p style="margin-top: 20px; color: white;">در حال احراز هویت...</p>
    </div>

    <div class="container" style="<?php echo $isLoggedIn ? '' : 'display: none;'; ?>" id="main-content">
        <!-- Header -->
        <div class="header">
            <div class="user-profile">
                <h2><i class="fas fa-home"></i> خانه</h2>
            </div>
            <button class="theme-toggle" onclick="ThemeManager.toggle()" aria-label="تغییر تم">
                <i class="fas fa-moon"></i>
                <i class="fas fa-sun"></i>
            </button>
        </div>

        <!-- User Info Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">اطلاعات کاربری</div>
            </div>
            <div style="padding: 16px;">
                <div style="margin-bottom: 12px;">
                    <i class="fas fa-user text-primary"></i>
                    <strong>نام:</strong> <?php echo htmlspecialchars($user['first_name']); ?>
                </div>
                <div style="margin-bottom: 12px;">
                    <i class="fas fa-id-card text-primary"></i>
                    <strong>شناسه:</strong> <code><?php echo $user['chat_id']; ?></code>
                </div>
                <div style="margin-bottom: 0;">
                    <i class="fas fa-wallet text-success"></i>
                    <strong>موجودی:</strong>
                    <span class="text-success" style="font-size: 1.1rem; font-weight: bold;">
                        <?php echo number_format($user['balance']); ?> تومان
                    </span>
                </div>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">آمار سرویس‌ها</div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; padding: 16px;">
                <div style="text-align: center;">
                    <div style="font-size: 2rem; color: var(--primary-color); font-weight: bold;">
                        <?php echo $total_services; ?>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                        کل سرویس‌ها
                    </div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; color: var(--success-color); font-weight: bold;">
                        <?php echo $active_services; ?>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                        فعال
                    </div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; color: var(--danger-color); font-weight: bold;">
                        <?php echo $expired_services; ?>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                        منقضی
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">دسترسی سریع</div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 16px;">
                <a href="shop.php" class="btn btn-primary"
                    style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-shopping-cart"></i>
                    <span>خرید سرویس</span>
                </a>
                <a href="wallet.php" class="btn btn-success"
                    style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-wallet"></i>
                    <span>شارژ کیف پول</span>
                </a>
            </div>
        </div>

        <!-- Recent Services -->
        <?php if (!empty($recent_services)): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">سرویس‌های اخیر</div>
                    <a href="services.php" style="font-size: 0.85rem; color: var(--primary-color); text-decoration: none;">
                        مشاهده همه <i class="fas fa-chevron-left"></i>
                    </a>
                </div>
                <div style="padding: 12px;">
                    <?php
                    $now = time();
                    foreach ($recent_services as $service):
                        $is_expired = $service['expire_timestamp'] < $now;
                        $status_color = $is_expired ? 'var(--danger-color)' : 'var(--success-color)';
                        $status_icon = $is_expired ? 'fa-times-circle' : 'fa-check-circle';
                        $status_text = $is_expired ? 'منقضی' : 'فعال';
                        $expire_date = date('Y-m-d', $service['expire_timestamp']);
                        ?>
                        <div
                            style="padding: 12px; margin-bottom: 8px; background: var(--bg-secondary); border-radius: var(--radius); border-right: 3px solid <?php echo $status_color; ?>;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <div style="font-weight: 600;">
                                    <?php echo htmlspecialchars($service['custom_name']); ?>
                                </div>
                                <div style="color: <?php echo $status_color; ?>; font-size: 0.85rem;">
                                    <i class="fas <?php echo $status_icon; ?>"></i>
                                    <?php echo $status_text; ?>
                                </div>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">
                                <i class="fas fa-calendar"></i>
                                انقضا: <?php echo $expire_date; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($isLoggedIn): ?>
            <div class="card">
                <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p>شما هنوز سرویسی خریداری نکرده‌اید.</p>
                    <a href="shop.php" class="btn btn-primary"
                        style="margin-top: 16px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-shopping-cart"></i> خرید اولین سرویس
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation -->
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

    <script src="assets/js/app.js"></script>
    <script>
        const tg = window.Telegram.WebApp;
        tg.ready();
        tg.expand();

        // Server-side state
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        const serverUserId = <?php echo $isLoggedIn && isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;

        // Function to handle authentication
        function authenticate() {
            if (tg.initData) {
                console.log('Authenticating...');
                document.getElementById('loading').style.display = 'flex';
                document.getElementById('main-content').style.display = 'none';

                fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'initData=' + encodeURIComponent(tg.initData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Authentication successful, reloading...');
                            window.location.reload();
                        } else {
                            document.getElementById('loading').innerHTML = '<div style="text-align: center; color: white;"><i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px;"></i><p>خطا در احراز هویت</p><p style="font-size: 0.9rem; margin-top: 8px;">لطفاً دوباره تلاش کنید</p></div>';
                            console.error('Auth failed:', data.error);
                        }
                    })
                    .catch(error => {
                        document.getElementById('loading').innerHTML = '<div style="text-align: center; color: white;"><i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px;"></i><p>خطا در ارتباط با سرور</p><p style="font-size: 0.9rem; margin-top: 8px;">' + error.message + '</p></div>';
                        console.error('Auth error:', error);
                    });
            } else {
                document.getElementById('loading').innerHTML = '<div style="text-align: center; color: white;"><i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px;"></i><p>دسترسی از طریق تلگرام مجاز است</p><p style="font-size: 0.9rem; margin-top: 8px;">لطفاً از داخل ربات تلگرام وارد شوید</p></div>';
                console.error('No initData available!');
            }
        }

        // Main logic
        if (!isLoggedIn) {
            // Case 1: Not logged in at all -> Authenticate
            authenticate();
        } else {
            // Case 2: Logged in, check if user matches
            const telegramUserId = tg.initDataUnsafe?.user?.id;

            if (telegramUserId && serverUserId != telegramUserId) {
                console.log('User mismatch detected (Server: ' + serverUserId + ', TG: ' + telegramUserId + '). Re-authenticating...');
                authenticate();
            } else {
                // Case 3: Logged in and user matches (or can't verify) -> Show content
                document.getElementById('main-content').style.display = 'block';
                document.getElementById('loading').style.display = 'none';
            }
        }

        // Theme is now handled by theme.js automatically
    </script>
</body>

</html>