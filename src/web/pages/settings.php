<?php
/**
 * Settings Management Page - COMPLETE & FIXED
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/web_functions.php';

requireLogin();

if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: ../index.php');
    exit();
}

$success = '';
$error = '';

// Handle reset test config count
if (isset($_GET['reset_test_count'])) {
    require_once __DIR__ . '/../../includes/functions.php';
    $affected_rows = resetAllUsersTestCount();
    $success = "\u0634\u0645\u0627\u0631\u0646\u062f\u0647 \u062f\u0631\u06cc\u0627\u0641\u062a \u06a9\u0627\u0646\u0641\u06cc\u06af \u062a\u0633\u062a \u0628\u0631\u0627\u06cc {$affected_rows} \u06a9\u0627\u0631\u0628\u0631 \u0631\u06cc\u0633\u062a \u0634\u062f.";
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $settings_to_update = [];

    // Bot status
    $settings_to_update['bot_status'] = $_POST['bot_status'] ?? 'off';
    $settings_to_update['sales_status'] = $_POST['sales_status'] ?? 'off';
    $settings_to_update['inline_keyboard'] = $_POST['inline_keyboard'] ?? 'off';

    // Channel settings
    $settings_to_update['join_channel_status'] = $_POST['join_channel_status'] ?? 'off';
    $settings_to_update['join_channel_id'] = sanitizeInput($_POST['join_channel_id'] ?? '');

    // Welcome gift
    $settings_to_update['welcome_gift_balance'] = (int) ($_POST['welcome_gift_balance'] ?? 0);

    // Payment gateway
    $settings_to_update['payment_gateway_status'] = $_POST['payment_gateway_status'] ?? 'off';
    $settings_to_update['zarinpal_merchant_id'] = sanitizeInput($_POST['zarinpal_merchant_id'] ?? '');

    // Card payment method - save as JSON array as functions.php expects
    $settings_to_update['payment_method'] = [
        'card_number' => sanitizeInput($_POST['card_number'] ?? ''),
        'card_holder' => sanitizeInput($_POST['card_owner_name'] ?? ''),
        'copy_enabled' => true
    ];

    // Verification
    $settings_to_update['verification_method'] = $_POST['verification_method'] ?? 'off';
    $settings_to_update['verification_iran_only'] = $_POST['verification_iran_only'] ?? 'off';

    // Test config
    $settings_to_update['test_config_usage_limit'] = (int) ($_POST['test_config_usage_limit'] ?? 1);

    // Notifications
    $settings_to_update['notification_expire_status'] = $_POST['notification_expire_status'] ?? 'off';
    $settings_to_update['notification_expire_days'] = (int) ($_POST['notification_expire_days'] ?? 3);
    $settings_to_update['notification_expire_gb'] = (int) ($_POST['notification_expire_gb'] ?? 1);
    $settings_to_update['notification_inactive_status'] = $_POST['notification_inactive_status'] ?? 'off';
    $settings_to_update['notification_inactive_days'] = (int) ($_POST['notification_inactive_days'] ?? 30);

    // Renewal
    $settings_to_update['renewal_status'] = $_POST['renewal_status'] ?? 'off';
    $settings_to_update['renewal_price_per_day'] = (int) ($_POST['renewal_price_per_day'] ?? 1000);
    $settings_to_update['renewal_price_per_gb'] = (int) ($_POST['renewal_price_per_gb'] ?? 2000);

    saveSettings($settings_to_update);
    $success = 'تنظیمات با موفقیت ذخیره شد.';
}

// Get current settings
$settings = getSettings();

// Extract card info from payment_method
$card_number = $settings['payment_method']['card_number'] ?? '';
$card_owner_name = $settings['payment_method']['card_holder'] ?? '';

renderHeader('تنظیمات');
?>

<div class="layout">
    <?php renderSidebar('settings'); ?>
    
    <div class="main-content">
        <?php renderTopbar('⚙️ تنظیمات کلی'); ?>
        
        <div class="content-area">
            <?php if ($success): ?>
                    <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                    <div class="alert alert-danger">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <!-- Bot Settings -->
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><i class="fas fa-robot"></i> تنظیمات ربات</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label>وضعیت ربات</label>
                                <select name="bot_status">
                                    <option value="on" <?php echo $settings['bot_status'] === 'on' ? 'selected' : ''; ?>>✅ فعال</option>
                                    <option value="off" <?php echo $settings['bot_status'] === 'off' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>وضعیت فروش</label>
                                <select name="sales_status">
                                    <option value="on" <?php echo $settings['sales_status'] === 'on' ? 'selected' : ''; ?>>✅ فعال</option>
                                    <option value="off" <?php echo $settings['sales_status'] === 'off' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>دکمه‌های Inline</label>
                                <select name="inline_keyboard">
                                    <option value="on" <?php echo $settings['inline_keyboard'] === 'on' ? 'selected' : ''; ?>>✅ فعال</option>
                                    <option value="off" <?php echo $settings['inline_keyboard'] === 'off' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Channel Settings -->
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><i class="fas fa-broadcast-tower"></i> کانال اجباری</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label>وضعیت عضویت اجباری</label>
                                <select name="join_channel_status">
                                    <option value="on" <?php echo $settings['join_channel_status'] === 'on' ? 'selected' : ''; ?>>✅ فعال</option>
                                    <option value="off" <?php echo $settings['join_channel_status'] === 'off' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>آیدی کانال (با @)</label>
                                <input type="text" name="join_channel_id" value="<?php echo htmlspecialchars($settings['join_channel_id']); ?>" placeholder="@channel_username">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Card Settings -->
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><i class="fas fa-credit-card"></i> اطلاعات کارت بانکی</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label>شماره کارت</label>
                                <input type="text" name="card_number" value="<?php echo htmlspecialchars($card_number); ?>" placeholder="6037-9977-XXXX-XXXX" maxlength="19">
                                <small style="color: var(--text-muted);">برای پرداخت دستی</small>
                            </div>
                            
                            <div class="form-group">
                                <label>نام صاحب کارت</label>
                                <input type="text" name="card_owner_name" value="<?php echo htmlspecialchars($card_owner_name); ?>" placeholder="علی احمدی">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Welcome Gift -->
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><i class="fas fa-gift"></i> هدیه خوش‌آمدگویی</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>مبلغ هدیه (تومان)</label>
                            <input type="number" name="welcome_gift_balance" value="<?php echo $settings['welcome_gift_balance']; ?>" min="0">
                        </div>
                    </div>
                </div>
                
                <!-- Payment Gateway -->
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><i class="fas fa-money-check-alt"></i> درگاه پرداخت</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label>وضعیت درگاه زرین‌پال</label>
                                <select name="payment_gateway_status">
                                    <option value="on" <?php echo isset($settings['payment_gateway_status']) && $settings['payment_gateway_status'] === 'on' ? 'selected' : ''; ?>>✅ فعال</option>
                                    <option value="off" <?php echo !isset($settings['payment_gateway_status']) || $settings['payment_gateway_status'] === 'off' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Merchant ID زرین‌پال</label>
                                <input type="text" name="zarinpal_merchant_id" value="<?php echo htmlspecialchars($settings['zarinpal_merchant_id'] ?? ''); ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Verification -->
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><i class="fas fa-shield-alt"></i> احراز هویت</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label>روش احراز هویت</label>
                                <select name="verification_method">
                                    <option value="off" <?php echo $settings['verification_method'] === 'off' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                                    <option value="phone" <?php echo $settings['verification_method'] === 'phone' ? 'selected' : ''; ?>>📱 شماره تلفن</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>فقط شماره‌های ایران</label>
                                <select name="verification_iran_only">
                                    <option value="on" <?php echo $settings['verification_iran_only'] === 'on' ? 'selected' : ''; ?>>✅ بله</option>
                                    <option value="off" <?php echo $settings['verification_iran_only'] === 'off' ? 'selected' : ''; ?>>❌ خیر</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Test Config -->
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><i class="fas fa-flask"></i> کانفیگ تست</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>تعداد دفعات مجاز دریافت (هر کاربر)</label>
                            <input type="number" name="test_config_usage_limit" value="<?php echo $settings['test_config_usage_limit'] ?? 1; ?>" min="0">
                        </div>
                        <div class="form-group" style="margin-top: 20px;">
                            <label>ریست کردن دریافت‌ها</label>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 10px;">با کلیک روی دکمه زیر، شمارنده دریافت کانفیگ تست برای تمام کاربران به صفر بازنشانی می‌شود.</p>
                            <a href="?reset_test_count=1" class="btn btn-danger" style="padding: 10px 20px;" onclick="return confirm('آیا از ریست کردن شمارنده دریافت کانفیگ تست برای تمام کاربران مطمئن هستید?');">
                                <i class="fas fa-redo"></i> ریست کردن دریافت‌های تمام کاربران
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> اعلان‌ها</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label>اعلان انقضا سرویس</label>
                                <select name="notification_expire_status">
                                    <option value="on" <?php echo $settings['notification_expire_status'] === 'on' ? 'selected' : ''; ?>>✅ فعال</option>
                                    <option value="off" <?php echo $settings['notification_expire_status'] === 'off' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>تعداد روز قبل از انقضا</label>
                                <input type="number" name="notification_expire_days" value="<?php echo $settings['notification_expire_days']; ?>" min="1">
                            </div>
                            
                            <div class="form-group">
                                <label>حجم باقیمانده (GB)</label>
                                <input type="number" name="notification_expire_gb" value="<?php echo $settings['notification_expire_gb']; ?>" min="1">
                            </div>
                        </div>
                        
                        <hr style="margin: 20px 0; border-color: var(--border-color);">
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label>اعلان عدم فعالیت</label>
                                <select name="notification_inactive_status">
                                    <option value="on" <?php echo $settings['notification_inactive_status'] === 'on' ? 'selected' : ''; ?>>✅ فعال</option>
                                    <option value="off" <?php echo $settings['notification_inactive_status'] === 'off' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>تعداد روز عدم فعالیت</label>
                                <input type="number" name="notification_inactive_days" value="<?php echo $settings['notification_inactive_days']; ?>" min="1">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Renewal -->
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><i class="fas fa-sync-alt"></i> تمدید سرویس</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label>وضعیت تمدید</label>
                                <select name="renewal_status">
                                    <option value="on" <?php echo isset($settings['renewal_status']) && $settings['renewal_status'] === 'on' ? 'selected' : ''; ?>>✅ فعال</option>
                                    <option value="off" <?php echo !isset($settings['renewal_status']) || $settings['renewal_status'] === 'off' ? 'selected' : ''; ?>>❌ غیرفعال</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>قیمت هر روز (تومان)</label>
                                <input type="number" name="renewal_price_per_day" value="<?php echo $settings['renewal_price_per_day'] ?? 1000; ?>" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label>قیمت هر گیگابایت (تومان)</label>
                                <input type="number" name="renewal_price_per_gb" value="<?php echo $settings['renewal_price_per_gb'] ?? 2000; ?>" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="update_settings" class="btn btn-primary" style="font-size: 1.1rem; padding: 15px 40px;">
                    <i class="fas fa-save"></i> ذخیره تمام تنظیمات
                </button>
            </form>
        </div>
    </div>
</div>

<?php renderFooter(); ?>