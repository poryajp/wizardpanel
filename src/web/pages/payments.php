<?php
/**
 * Payment Requests Management Page - WITH APPROVE/REJECT
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

// Handle approve payment
if (isset($_GET['approve'])) {
    $payment_id = (int) $_GET['approve'];
    $stmt = pdo()->prepare("SELECT * FROM payment_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($payment) {
        // Update user balance
        updateUserBalance($payment['user_id'], $payment['amount'], 'add');

        // Update payment status
        $stmt = pdo()->prepare("UPDATE payment_requests SET status='approved', processed_at=NOW(), processed_by_admin_id=? WHERE id=?");
        $stmt->execute([ADMIN_CHAT_ID, $payment_id]);

        // Send notification to user via bot
        sendMessage($payment['user_id'], "✅ درخواست شارژ حساب شما به مبلغ " . number_format($payment['amount']) . " تومان تایید شد.\n\n💰 موجودی جدید: " . number_format(getUserBalance($payment['user_id'])) . " تومان");

        $success = 'درخواست تایید شد و موجودی کاربر افزایش یافت.';
    } else {
        $error = 'درخواست یافت نشد یا قبلاً پردازش شده است.';
    }
}

// Handle reject payment
if (isset($_GET['reject'])) {
    $payment_id = (int) $_GET['reject'];
    $stmt = pdo()->prepare("SELECT * FROM payment_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($payment) {
        $stmt = pdo()->prepare("UPDATE payment_requests SET status='rejected', processed_at=NOW(), processed_by_admin_id=? WHERE id=?");
        $stmt->execute([ADMIN_CHAT_ID, $payment_id]);

        // Send notification to user via bot
        sendMessage($payment['user_id'], "❌ درخواست شارژ حساب شما به مبلغ " . number_format($payment['amount']) . " تومان رد شد.\n\nلطفاً با پشتیبانی تماس بگیرید.");

        $success = 'درخواست رد شد.';
    } else {
        $error = 'درخواست یافت نشد یا قبلاً پردازش شده است.';
    }
}

// Get pending payment requests
$stmt = pdo()->query("
    SELECT pr.*, u.first_name 
    FROM payment_requests pr 
    JOIN users u ON pr.user_id = u.chat_id 
    WHERE pr.status = 'pending' 
    ORDER BY pr.created_at DESC
");
$pending_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get processed payment requests (last 50)
$stmt = pdo()->query("
    SELECT pr.*, u.first_name 
    FROM payment_requests pr 
    JOIN users u ON pr.user_id = u.chat_id 
    WHERE pr.status != 'pending' 
    ORDER BY pr.processed_at DESC 
    LIMIT 50
");
$processed_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get renewal requests
$stmt = pdo()->query("
    SELECT rr.*, u.first_name 
    FROM renewal_requests rr 
    JOIN users u ON rr.user_id = u.chat_id 
    WHERE rr.status = 'pending' 
    ORDER BY rr.created_at DESC
");
$renewal_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderHeader('مدیریت پرداخت‌ها و تمدیدها');
?>

<div class="layout">
    <?php renderSidebar('payments'); ?>

    <div class="main-content">
        <?php renderTopbar('💳 مدیریت پرداخت‌ها'); ?>

        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Pending Payments -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> درخواست‌های شارژ حساب در انتظار
                        (<?php echo count($pending_payments); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_payments)): ?>
                        <p class="text-muted">هیچ درخواستی در انتظار نیست.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>شناسه</th>
                                        <th>کاربر</th>
                                        <th>مبلغ</th>
                                        <th>تاریخ</th>
                                        <th>رسید</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_payments as $payment): ?>
                                        <tr style="background: rgba(255, 193, 7, 0.05);">
                                            <td><?php echo $payment['id']; ?></td>
                                            <td>
                                                </a>
                                                <a href="?reject=<?php echo $payment['id']; ?>" class="btn btn-danger"
                                                    style="padding: 6px 16px; font-size: 0.85rem;"
                                                    onclick="return confirm('آیا از رد این درخواست مطمئن هستید؟');">
                                                    <i class="fas fa-times"></i> رد
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Renewals -->
            <?php if (!empty($renewal_requests)): ?>
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><i class="fas fa-sync-alt"></i> درخواست‌های تمدید در انتظار
                            (<?php echo count($renewal_requests); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>کاربر</th>
                                        <th>سرویس</th>
                                        <th>روز/حجم</th>
                                        <th>مبلغ</th>
                                        <th>تاریخ</th>
                                        <th>رسید</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($renewal_requests as $renewal): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($renewal['first_name']); ?></td>
                                            <td><code><?php echo htmlspecialchars($renewal['service_username']); ?></code></td>
                                            <td><?php echo $renewal['days_to_add']; ?> روز /
                                                <?php echo $renewal['gb_to_add']; ?> GB
                                            </td>
                                            <td><?php echo number_format($renewal['total_cost']); ?> تومان</td>
                                            <td><?php echo date('Y/m/d H:i', strtotime($renewal['created_at'])); ?></td>
                                            <td>
                                                <?php if ($renewal['photo_file_id']): ?>
                                                    <a href="#" class="btn btn-primary"
                                                        style="padding: 4px 10px; font-size: 0.8rem;">
                                                        <i class="fas fa-image"></i> مشاهده
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted);">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info" style="margin-top: 15px;">
                            <strong>ℹ️ توجه:</strong> برای تایید/رد درخواست‌های تمدید، از ربات تلگرام استفاده کنید.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Processed Payments -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-check-circle"></i> تاریخچه پرداخت‌ها (50 مورد اخیر)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($processed_payments)): ?>
                        <p class="text-muted">هیچ درخواست پردازش شده‌ای وجود ندارد.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>کاربر</th>
                                        <th>مبلغ</th>
                                        <th>تاریخ ثبت</th>
                                        <th>تاریخ پردازش</th>
                                        <th>وضعیت</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($processed_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['first_name']); ?></td>
                                            <td><?php echo number_format($payment['amount']); ?> تومان</td>
                                            <td><?php echo date('Y/m/d H:i', strtotime($payment['created_at'])); ?></td>
                                            <td><?php echo $payment['processed_at'] ? date('Y/m/d H:i', strtotime($payment['processed_at'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <?php if ($payment['status'] === 'approved'): ?>
                                                    <span style="color: var(--success);">✅ تایید شده</span>
                                                <?php else: ?>
                                                    <span style="color: var(--danger);">❌ رد شده</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>