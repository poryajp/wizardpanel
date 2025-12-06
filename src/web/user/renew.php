<?php
require_once __DIR__ . '/session.php';
requireUserLogin();

require_once __DIR__ . '/../../includes/functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user = getCurrentUser();
$chat_id = $user['chat_id'];
$settings = getSettings();

// تعریف ثابت USER_INLINE_KEYBOARD برای جلوگیری از خطا
if (!defined('USER_INLINE_KEYBOARD')) {
    define('USER_INLINE_KEYBOARD', ($settings['inline_keyboard'] ?? 'on') === 'on');
}

// Check if renewal is enabled
if (($settings['renewal_status'] ?? 'off') !== 'on') {
    die('<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>خطا</title><link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container"><div class="card"><div style="text-align: center; padding: 20px; color: var(--danger-color);"><i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 16px;"></i><p>قابلیت تمدید سرویس در حال حاضر غیرفعال است.</p><a href="services.php" class="btn btn-primary">بازگشت</a></div></div></div></body></html>');
}

$username = $_GET['username'] ?? '';
if (empty($username)) {
    header('Location: services.php');
    exit();
}

// Validate service ownership
$stmt = pdo()->prepare("SELECT * FROM services WHERE marzban_username = ? AND owner_chat_id = ?");
$stmt->execute([$username, $chat_id]);
$service = $stmt->fetch();

if (!$service) {
    die('<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>خطا</title><link rel="stylesheet" href="assets/css/style.css"></head><body><div class="container"><div class="card"><div style="text-align: center; padding: 20px; color: var(--danger-color);"><i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 16px;"></i><p>سرویس یافت نشد.</p><a href="services.php" class="btn btn-primary">بازگشت</a></div></div></div></body></html>');
}

$step = 1;
if (isset($_GET['plan_id']))
    $step = 4;
elseif (isset($_GET['server_id']))
    $step = 3;
elseif (isset($_GET['category_id']))
    $step = 2;

// Auto-skip server selection if only one server exists
if ($step === 2) {
    $category_id = $_GET['category_id'];
    $stmt = pdo()->prepare("
        SELECT DISTINCT s.id 
        FROM servers s
        JOIN plans p ON s.id = p.server_id
        WHERE p.category_id = ? AND p.status = 'active' AND s.status = 'active'
    ");
    $stmt->execute([$category_id]);
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($servers) === 1) {
        $step = 3;
        $_GET['server_id'] = $servers[0]['id'];
    }
}

$error = '';
$success = '';

// Handle Renewal Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_renewal'])) {
    $plan_id = $_POST['plan_id'];
    $plan = getPlanById($plan_id);

    if ($plan) {
        $final_price = (float) $plan['price'];
        $user_balance = $user['balance'];

        if ($user_balance >= $final_price) {
            try {
                // Apply renewal
                $result = applyPlanRenewal($chat_id, $username, $plan_id, $final_price);
                if ($result['success']) {
                    // Redirect to services.php with success message
                    header('Location: services.php?success_msg=' . urlencode($result['message']));
                    exit();
                } else {
                    $error = $result['message'];
                }
            } catch (Throwable $e) {
                $error = 'خطای سیستمی: ' . $e->getMessage();
                error_log($e);
            }
        } else {
            $error = 'موجودی حساب شما کافی نیست.';
        }
    } else {
        $error = 'پلن انتخاب شده نامعتبر است.';
    }
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>تمدید سرویس</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="user-profile">
                <a href="services.php" style="color: var(--text-color); text-decoration: none; font-size: 1.2rem;">
                    <i class="fas fa-arrow-right"></i>
                </a>
                <h2 style="margin-right: 12px;">تمدید سرویس</h2>
            </div>
            <button class="theme-toggle" onclick="ThemeManager.toggle()" aria-label="تغییر تم">
                <i class="fas fa-moon"></i>
                <i class="fas fa-sun"></i>
            </button>
        </div>

        <?php if ($success): ?>
            <div class="card">
                <div style="text-align: center; padding: 30px 20px;">
                    <i class="fas fa-check-circle"
                        style="font-size: 48px; color: var(--success-color); margin-bottom: 16px;"></i>
                    <p style="margin-bottom: 20px; font-weight: bold;"><?php echo $success; ?></p>
                    <a href="services.php" class="btn btn-primary">بازگشت به سرویس‌ها</a>
                </div>
            </div>
        <?php elseif ($step === 4): // Confirmation Step ?>
            <?php
            $plan_id = $_GET['plan_id'];
            $plan = getPlanById($plan_id);
            if (!$plan) {
                echo '<div class="alert alert-danger">پلن یافت نشد.</div>';
            } else {
                $final_price = $plan['price'];
                $can_afford = $user['balance'] >= $final_price;
                ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">تایید تمدید</div>
                    </div>
                    <div style="padding: 15px;">
                        <div
                            style="background: var(--bg-secondary); padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="color: var(--text-muted);">سرویس:</span>
                                <span><?php echo htmlspecialchars($service['custom_name'] ?: $service['marzban_username']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="color: var(--text-muted);">پلن جدید:</span>
                                <span><?php echo htmlspecialchars($plan['name']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="color: var(--text-muted);">قیمت:</span>
                                <span><?php echo number_format($final_price); ?> تومان</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 10px;">
                                <span style="color: var(--text-muted);">موجودی شما:</span>
                                <span
                                    style="<?php echo $can_afford ? 'color: var(--success-color);' : 'color: var(--danger-color);'; ?>">
                                    <?php echo number_format($user['balance']); ?> تومان
                                </span>
                            </div>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($can_afford): ?>
                            <form method="POST"
                                action="?username=<?php echo urlencode($username); ?>&category_id=<?php echo urlencode($_GET['category_id']); ?>&server_id=<?php echo urlencode($_GET['server_id']); ?>&plan_id=<?php echo urlencode($plan_id); ?>">
                                <input type="hidden" name="plan_id" value="<?php echo $plan_id; ?>">
                                <button type="submit" name="confirm_renewal" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-check"></i> تایید و پرداخت
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                موجودی حساب شما کافی نیست. لطفاً ابتدا کیف پول خود را شارژ کنید.
                                <br>
                                مبلغ مورد نیاز: <b><?php echo number_format($final_price - $user['balance']); ?> تومان</b>
                            </div>
                            <a href="wallet.php" class="btn btn-primary"
                                style="width: 100%; text-decoration: none; display: block; text-align: center;">
                                <i class="fas fa-wallet"></i> شارژ کیف پول
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php } ?>

        <?php elseif ($step === 3): // Select Plan ?>
            <?php
            $category_id = $_GET['category_id'];
            $server_id = $_GET['server_id'];
            $stmt = pdo()->prepare("SELECT * FROM plans WHERE category_id = ? AND server_id = ? AND status = 'active' AND is_test_plan = 0");
            $stmt->execute([$category_id, $server_id]);
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">انتخاب پلن</div>
                </div>

                <div style="padding: 10px 10px 0 10px;">
                    <?php
                    $stmt_count = pdo()->prepare("SELECT COUNT(DISTINCT s.id) FROM servers s JOIN plans p ON s.id = p.server_id WHERE p.category_id = ? AND p.status = 'active' AND s.status = 'active' AND s.id IS NOT NULL");
                    $stmt_count->execute([$category_id]);
                    $server_count = $stmt_count->fetchColumn();

                    $back_link = ($server_count == 1) ? "?username=" . urlencode($username) : "?username=" . urlencode($username) . "&category_id=" . urlencode($category_id);
                    $back_text = ($server_count == 1) ? 'بازگشت به دسته‌بندی‌ها' : 'بازگشت به سرورها';
                    ?>
                    <a href="<?php echo $back_link; ?>"
                        style="text-decoration: none; color: var(--primary-color); font-size: 0.9rem;">
                        <i class="fas fa-chevron-right"></i> <?php echo $back_text; ?>
                    </a>
                </div>

                <div style="padding: 10px;">
                    <?php if (empty($plans)): ?>
                        <p style="text-align: center; color: var(--text-muted);">هیچ پلنی یافت نشد.</p>
                    <?php else: ?>
                        <?php foreach ($plans as $plan): ?>
                            <a href="?username=<?php echo $username; ?>&category_id=<?php echo $category_id; ?>&server_id=<?php echo $server_id; ?>&plan_id=<?php echo $plan['id']; ?>"
                                class="btn btn-outline"
                                style="display: block; margin-bottom: 10px; text-decoration: none; text-align: right;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span><?php echo htmlspecialchars($plan['name']); ?></span>
                                    <span class="badge badge-primary"><?php echo number_format($plan['price']); ?> تومان</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($step === 2): // Select Server ?>
            <?php
            $category_id = $_GET['category_id'];
            $stmt = pdo()->prepare("
                SELECT DISTINCT s.id, s.name 
                FROM servers s
                JOIN plans p ON s.id = p.server_id
                WHERE p.category_id = ? AND p.status = 'active' AND s.status = 'active'
            ");
            $stmt->execute([$category_id]);
            $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">انتخاب سرور (لوکیشن)</div>
                </div>
                <div style="padding: 10px;">
                    <?php if (empty($servers)): ?>
                        <p style="text-align: center; color: var(--text-muted);">هیچ سروری یافت نشد.</p>
                    <?php else: ?>
                        <?php foreach ($servers as $server): ?>
                            <a href="?username=<?php echo $username; ?>&category_id=<?php echo $category_id; ?>&server_id=<?php echo $server['id']; ?>"
                                class="btn btn-outline" style="display: block; margin-bottom: 10px; text-decoration: none;">
                                <i class="fas fa-server"></i> <?php echo htmlspecialchars($server['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: // Step 1: Select Category ?>
            <?php
            $categories = getCategories(true);
            ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">انتخاب دسته‌بندی</div>
                </div>
                <div style="padding: 10px;">
                    <?php if (empty($categories)): ?>
                        <p style="text-align: center; color: var(--text-muted);">هیچ دسته‌بندی یافت نشد.</p>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <a href="?username=<?php echo $username; ?>&category_id=<?php echo $category['id']; ?>"
                                class="btn btn-outline" style="display: block; margin-bottom: 10px; text-decoration: none;">
                                <i class="fas fa-folder"></i> <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="assets/js/app.js"></script>
    <script>
        const tg = window.Telegram.WebApp;
        tg.ready();
        tg.expand();

        // Theme is now handled by theme.js automatically
    </script>
</body>

</html>