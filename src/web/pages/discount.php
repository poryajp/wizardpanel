<?php
/**
 * Discount Codes Management Page - FULL CRUD
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

// Handle add discount
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_discount'])) {
    $code = strtoupper(sanitizeInput($_POST['code']));
    $type = sanitizeInput($_POST['type']);
    $value = (float) $_POST['value'];
    $max_usage = (int) $_POST['max_usage'];

    if (!empty($code) && $value > 0 && $max_usage > 0) {
        // Check if code already exists
        $stmt = pdo()->prepare("SELECT COUNT(*) FROM discount_codes WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'این کد تخفیف قبلاً ثبت شده است.';
        } else {
            $stmt = pdo()->prepare("INSERT INTO discount_codes (code, type, value, max_usage, usage_count, status) VALUES (?, ?, ?, ?, 0, 'active')");
            if ($stmt->execute([$code, $type, $value, $max_usage])) {
                $success = 'کد تخفیف با موفقیت اضافه شد.';
            } else {
                $error = 'خطا در افزودن کد تخفیف.';
            }
        }
    } else {
        $error = 'لطفاً تمام فیلدها را به درستی پر کنید.';
    }
}

// Handle edit discount
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_discount'])) {
    $id = (int) $_POST['discount_id'];
    $code = strtoupper(sanitizeInput($_POST['code']));
    $type = sanitizeInput($_POST['type']);
    $value = (float) $_POST['value'];
    $max_usage = (int) $_POST['max_usage'];

    $stmt = pdo()->prepare("UPDATE discount_codes SET code=?, type=?, value=?, max_usage=? WHERE id=?");
    if ($stmt->execute([$code, $type, $value, $max_usage, $id])) {
        $success = 'کد تخفیف بهروزرسانی شد.';
    } else {
        $error = 'خطا در بهروزرسانی کد تخفیف.';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = pdo()->prepare("DELETE FROM discount_codes WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'کد تخفیف حذف شد.';
    }
}

// Handle toggle
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = pdo()->prepare("UPDATE discount_codes SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'وضعیت کد تخفیف تغییر کرد.';
    }
}

// Get discount for edit
$edit_discount = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = pdo()->prepare("SELECT * FROM discount_codes WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_discount = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all discount codes
$stmt = pdo()->query("SELECT * FROM discount_codes ORDER BY id DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderHeader('مدیریت کدهای تخفیف');
?>

<div class="layout">
    <?php renderSidebar('discount'); ?>

    <div class="main-content">
        <?php renderTopbar('🎁 مدیریت کدهای تخفیف'); ?>

        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add/Edit Discount Form -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-plus"></i>
                        <?php echo $edit_discount ? 'ویرایش کد تخفیف' : 'افزودن کد تخفیف جدید'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_discount): ?>
                            <input type="hidden" name="discount_id" value="<?php echo $edit_discount['id']; ?>">
                        <?php endif; ?>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label for="code">کد تخفیف *</label>
                                <input type="text" id="code" name="code"
                                    value="<?php echo $edit_discount['code'] ?? ''; ?>" placeholder="SUMMER2024"
                                    style="text-transform: uppercase;" required>
                                <small style="color: var(--text-muted);">فقط حروف انگلیسی و اعداد</small>
                            </div>

                            <div class="form-group">
                                <label for="type">نوع تخفیف *</label>
                                <select id="type" name="type" required>
                                    <option value="percent" <?php echo (isset($edit_discount) && $edit_discount['type'] == 'percent') ? 'selected' : ''; ?>>📊 درصدی</option>
                                    <option value="fixed" <?php echo (isset($edit_discount) && $edit_discount['type'] == 'fixed') ? 'selected' : ''; ?>>💰 مبلغ ثابت</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="value">مقدار تخفیف *</label>
                                <input type="number" id="value" name="value"
                                    value="<?php echo $edit_discount['value'] ?? ''; ?>" min="1" step="0.01" required>
                                <small style="color: var(--text-muted);" id="value-hint">درصد (1-100) یا تومان</small>
                            </div>

                            <div class="form-group">
                                <label for="max_usage">حداکثر تعداد استفاده *</label>
                                <input type="number" id="max_usage" name="max_usage"
                                    value="<?php echo $edit_discount['max_usage'] ?? ''; ?>" min="1" required>
                            </div>
                        </div>

                        <button type="submit" name="<?php echo $edit_discount ? 'edit_discount' : 'add_discount'; ?>"
                            class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $edit_discount ? 'بهروزرسانی' : 'افزودن'; ?>
                        </button>

                        <?php if ($edit_discount): ?>
                            <a href="discount.php" class="btn btn-danger">
                                <i class="fas fa-times"></i> لغو
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Discount Codes List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-gift"></i> لیست کدهای تخفیف (<?php echo count($codes); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($codes)): ?>
                        <p class="text-muted">هیچ کد تخفیفی یافت نشد.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>کد</th>
                                    <th>نوع</th>
                                    <th>مقدار</th>
                                    <th>استفاده</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($codes as $code): ?>
                                    <tr>
                                        <td>
                                            <code style="font-size: 1.1rem; font-weight: 600; color: var(--primary);">
                                                        <?php echo htmlspecialchars($code['code']); ?>
                                                    </code>
                                        </td>
                                        <td>
                                            <?php echo $code['type'] === 'percent' ? '📊 درصدی' : '💰 مبلغی'; ?>
                                        </td>
                                        <td>
                                            <strong>
                                                <?php
                                                echo number_format($code['value']);
                                                echo $code['type'] === 'percent' ? '%' : ' تومان';
                                                ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span
                                                style="<?php echo $code['usage_count'] >= $code['max_usage'] ? 'color: var(--danger);' : ''; ?>">
                                                <?php echo $code['usage_count']; ?> / <?php echo $code['max_usage']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($code['status'] === 'active'): ?>
                                                <span style="color: var(--success);">✅ فعال</span>
                                            <?php else: ?>
                                                <span style="color: var(--danger);">❌ غیرفعال</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?php echo $code['id']; ?>" class="btn btn-primary"
                                                style="padding: 6px 12px; font-size: 0.85rem;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?toggle=<?php echo $code['id']; ?>" class="btn btn-success"
                                                style="padding: 6px 12px; font-size: 0.85rem;">
                                                <?php echo $code['status'] === 'active' ? 'غیرفعال' : 'فعال'; ?>
                                            </a>
                                            <a href="?delete=<?php echo $code['id']; ?>" class="btn btn-danger"
                                                style="padding: 6px 12px; font-size: 0.85rem;"
                                                onclick="return confirm('آیا مطمئن هستید؟');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update hint based on discount type
    document.getElementById('type').addEventListener('change', function () {
        const hint = document.getElementById('value-hint');
        if (this.value === 'percent') {
            hint.textContent = 'درصد (1-100)';
        } else {
            hint.textContent = 'مبلغ به تومان';
        }
    });
</script>

<?php renderFooter(); ?>