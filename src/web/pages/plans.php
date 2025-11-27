<?php
/**
 * Plans Management Page - FULL CRUD
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

// Handle add plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plan'])) {
    $server_id = (int) $_POST['server_id'];
    $category_id = (int) $_POST['category_id'];
    $name = sanitizeInput($_POST['name']);
    $price = (float) $_POST['price'];
    $volume_gb = (int) $_POST['volume_gb'];
    $duration_days = (int) $_POST['duration_days'];
    $description = sanitizeInput($_POST['description']);
    $show_sub_link = isset($_POST['show_sub_link']) ? 1 : 0;
    $show_conf_links = isset($_POST['show_conf_links']) ? 1 : 0;
    $is_test_plan = isset($_POST['is_test_plan']) ? 1 : 0;

    if (!empty($name) && $server_id > 0 && $category_id > 0) {
        $stmt = pdo()->prepare("INSERT INTO plans (server_id, category_id, name, price, volume_gb, duration_days, description, show_sub_link, show_conf_links, is_test_plan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        if ($stmt->execute([$server_id, $category_id, $name, $price, $volume_gb, $duration_days, $description, $show_sub_link, $show_conf_links, $is_test_plan])) {
            $success = 'پلن با موفقیت اضافه شد.';
        } else {
            $error = 'خطا در افزودن پلن.';
        }
    } else {
        $error = 'لطفاً تمام فیلدهای الزامی را پر کنید.';
    }
}

// Handle edit plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_plan'])) {
    $id = (int) $_POST['plan_id'];
    $server_id = (int) $_POST['server_id'];
    $category_id = (int) $_POST['category_id'];
    $name = sanitizeInput($_POST['name']);
    $price = (float) $_POST['price'];
    $volume_gb = (int) $_POST['volume_gb'];
    $duration_days = (int) $_POST['duration_days'];
    $description = sanitizeInput($_POST['description']);
    $show_sub_link = isset($_POST['show_sub_link']) ? 1 : 0;
    $show_conf_links = isset($_POST['show_conf_links']) ? 1 : 0;

    $stmt = pdo()->prepare("UPDATE plans SET server_id=?, category_id=?, name=?, price=?, volume_gb=?, duration_days=?, description=?, show_sub_link=?, show_conf_links=? WHERE id=?");
    if ($stmt->execute([$server_id, $category_id, $name, $price, $volume_gb, $duration_days, $description, $show_sub_link, $show_conf_links, $id])) {
        $success = 'پلن بهروزرسانی شد.';
    } else {
        $error = 'خطا در بهروزرسانی پلن.';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = pdo()->prepare("DELETE FROM plans WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'پلن حذف شد.';
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = pdo()->prepare("UPDATE plans SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'وضعیت پلن تغییر کرد.';
    }
}

// Get plan for edit
$edit_plan = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = pdo()->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_plan = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get servers and categories for form
$servers = getServers();
$categories = getCategories();

// Get all plans with server info
$stmt = pdo()->query("
    SELECT p.*, s.name as server_name, s.type as server_type, c.name as category_name 
    FROM plans p 
    LEFT JOIN servers s ON p.server_id = s.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.is_test_plan DESC, p.id ASC
");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderHeader('مدیریت پلن‌ها');
?>

<div class="layout">
    <?php renderSidebar('plans'); ?>

    <div class="main-content">
        <?php renderTopbar('📝 مدیریت پلن‌ها'); ?>

        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add/Edit Plan Form -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-plus"></i> <?php echo $edit_plan ? 'ویرایش پلن' : 'افزودن پلن جدید'; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_plan): ?>
                            <input type="hidden" name="plan_id" value="<?php echo $edit_plan['id']; ?>">
                        <?php endif; ?>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label for="name">نام پلن *</label>
                                <input type="text" id="name" name="name" value="<?php echo $edit_plan['name'] ?? ''; ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="server_id">سرور *</label>
                                <select id="server_id" name="server_id" required>
                                    <option value="">انتخاب کنید...</option>
                                    <?php foreach ($servers as $server): ?>
                                        <option value="<?php echo $server['id']; ?>" <?php echo (isset($edit_plan) && $edit_plan['server_id'] == $server['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($server['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="category_id">دسته‌بندی *</label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">انتخاب کنید...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($edit_plan) && $edit_plan['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="price">قیمت (تومان) *</label>
                                <input type="number" id="price" name="price"
                                    value="<?php echo $edit_plan['price'] ?? ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="volume_gb">حجم (گیگابایت) *</label>
                                <input type="number" id="volume_gb" name="volume_gb"
                                    value="<?php echo $edit_plan['volume_gb'] ?? ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="duration_days">مدت (روز) *</label>
                                <input type="number" id="duration_days" name="duration_days"
                                    value="<?php echo $edit_plan['duration_days'] ?? ''; ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">توضیحات</label>
                            <textarea id="description" name="description"
                                rows="3"><?php echo $edit_plan['description'] ?? ''; ?></textarea>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="show_sub_link" <?php echo (isset($edit_plan) && $edit_plan['show_sub_link']) ? 'checked' : ''; ?>>
                                نمایش لینک اشتراک
                            </label>

                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="show_conf_links" <?php echo (isset($edit_plan) && $edit_plan['show_conf_links']) ? 'checked' : ''; ?>>
                                نمایش لینک‌های کانفیگ
                            </label>

                            <?php if (!$edit_plan): ?>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="is_test_plan">
                                    پلن تست
                                </label>
                            <?php endif; ?>
                        </div>

                        <button type="submit" name="<?php echo $edit_plan ? 'edit_plan' : 'add_plan'; ?>"
                            class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $edit_plan ? 'بهروزرسانی' : 'افزودن'; ?>
                        </button>

                        <?php if ($edit_plan): ?>
                            <a href="plans.php" class="btn btn-danger">
                                <i class="fas fa-times"></i> لغو
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Plans List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> لیست پلن‌ها (<?php echo count($plans); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($plans)): ?>
                        <p class="text-muted">هیچ پلنی یافت نشد.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>شناسه</th>
                                        <th>نام</th>
                                        <th>سرور</th>
                                        <th>دسته‌بندی</th>
                                        <th>قیمت</th>
                                        <th>حجم/مدت</th>
                                        <th>وضعیت</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plans as $plan): ?>
                                        <tr>
                                            <td><?php echo $plan['id']; ?></td>
                                            <td>
                                                <?php if ($plan['is_test_plan']): ?>
                                                    <span style="color: var(--warning);">🧪 </span>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($plan['name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($plan['server_name'] ?? 'نامشخص'); ?></td>
                                            <td><?php echo htmlspecialchars($plan['category_name'] ?? 'نامشخص'); ?></td>
                                            <td><?php echo number_format($plan['price']); ?> تومان</td>
                                            <td><?php echo $plan['volume_gb']; ?>GB / <?php echo $plan['duration_days']; ?> روز
                                            </td>
                                            <td>
                                                <?php if ($plan['status'] === 'active'): ?>
                                                    <span style="color: var(--success);">✅ فعال</span>
                                                <?php else: ?>
                                                    <span style="color: var(--danger);">❌ غیرفعال</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?edit=<?php echo $plan['id']; ?>" class="btn btn-primary"
                                                    style="padding: 6px 12px; font-size: 0.85rem;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?toggle=<?php echo $plan['id']; ?>" class="btn btn-success"
                                                    style="padding: 6px 12px; font-size: 0.85rem;">
                                                    <?php echo $plan['status'] === 'active' ? 'غیرفعال' : 'فعال'; ?>
                                                </a>
                                                <a href="?delete=<?php echo $plan['id']; ?>" class="btn btn-danger"
                                                    style="padding: 6px 12px; font-size: 0.85rem;"
                                                    onclick="return confirm('آیا مطمئن هستید؟');">
                                                    <i class="fas fa-trash"></i>
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
        </div>
    </div>
</div>

<?php renderFooter(); ?>