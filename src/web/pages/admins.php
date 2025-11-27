<?php
/**
 * Admins Management Page - Full CRUD
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

// Handle add admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $chat_id = sanitizeInput($_POST['admin_chat_id']);
    $first_name = sanitizeInput($_POST['admin_name']);
    $permissions = $_POST['permissions'] ?? [];

    if (empty($chat_id) || empty($first_name)) {
        $error = 'لطفا تمام فیلدهای مورد نیاز را پر کنید.';
    } elseif (!is_numeric($chat_id)) {
        $error = 'Chat ID باید عدد باشد.';
    } else {
        if (addAdmin($chat_id, $first_name, $permissions)) {
            $success = 'ادمین با موفقیت اضافه شد.';
        } else {
            $error = 'خطا در افزودن ادمین. ممکن است قبلا وجود داشته باشد.';
        }
    }
}

// Handle update permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $chat_id = sanitizeInput($_POST['edit_chat_id']);
    $permissions = $_POST['edit_permissions'] ?? [];

    if (updateAdminPermissions($chat_id, $permissions)) {
        $success = 'دسترسی‌های ادمین به‌روزرسانی شد.';
    } else {
        $error = 'خطا در به‌روزرسانی دسترسی‌ها.';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $chat_id = $_GET['delete'];
    if (removeAdmin($chat_id)) {
        $success = 'ادمین با موفقیت حذف شد.';
    } else {
        $error = 'خطا در حذف ادمین.';
    }
}

// Get all admins
$admins = getAdmins();
$permission_map = getPermissionMap();

// Get total admin count including super admin
$total_admins = count($admins) + 1; // +1 for super admin

renderHeader('مدیریت ادمین‌ها');
?>

<div class="layout">
    <?php renderSidebar('admins'); ?>

    <div class="main-content">
        <?php renderTopbar('👨‍💼 مدیریت ادمین‌ها'); ?>

        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid mb-20">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($total_admins); ?></div>
                        <div class="stat-label">کل ادمین‌ها</div>
                        <div class="stat-sub">شامل سوپر ادمین</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format(count($admins)); ?></div>
                        <div class="stat-label">ادمین‌های عادی</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format(count($permission_map)); ?></div>
                        <div class="stat-label">انواع دسترسی</div>
                    </div>
                </div>
            </div>

            <!-- Add New Admin Form -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus"></i> افزودن ادمین جدید</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <label for="admin_chat_id">Chat ID *</label>
                                <input type="text" id="admin_chat_id" name="admin_chat_id" required
                                    placeholder="مثال: 123456789">
                                <small style="color: var(--text-muted);">شناسه کاربر تلگرام</small>
                            </div>

                            <div class="form-group" style="flex: 1;">
                                <label for="admin_name">نام ادمین *</label>
                                <input type="text" id="admin_name" name="admin_name" required
                                    placeholder="نام و نام خانوادگی">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>دسترسی‌ها</label>
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; margin-top: 10px;">
                                <?php foreach ($permission_map as $key => $label): ?>
                                    <label
                                        style="display: flex; align-items: center; gap: 8px; padding: 10px; background: rgba(139, 92, 246, 0.05); border-radius: 8px; cursor: pointer;">
                                        <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>"
                                            style="width: 18px; height: 18px;">
                                        <span><?php echo $label; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small style="color: var(--text-muted); margin-top: 10px; display: block;">
                                همه دسترسی‌های مورد نیاز را انتخاب کنید
                            </small>
                        </div>

                        <button type="submit" name="add_admin" class="btn btn-primary">
                            <i class="fas fa-plus"></i> افزودن ادمین
                        </button>
                    </form>
                </div>
            </div>

            <!-- Admins List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-shield"></i> لیست ادمین‌ها (<?php echo count($admins); ?> نفر)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($admins)): ?>
                        <p class="text-muted" style="text-align: center; padding: 30px;">
                            <i class="fas fa-info-circle"
                                style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                            هیچ ادمینی یافت نشد. از فرم بالا برای افزودن ادمین جدید استفاده کنید.
                        </p>
                    <?php else: ?>
                        <?php foreach ($admins as $admin_id => $admin): ?>
                            <div class="card mb-20"
                                style="background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2);">
                                <div class="card-body">
                                    <div
                                        style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                        <div>
                                            <h4 style="margin: 0 0 5px 0;">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($admin['first_name']); ?>
                                            </h4>
                                            <p style="margin: 0; color: var(--text-muted);">
                                                <strong>Chat ID:</strong> <code
                                                    style="background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 4px;"><?php echo $admin_id; ?></code>
                                            </p>
                                        </div>
                                        <a href="?delete=<?php echo $admin_id; ?>" class="btn btn-danger"
                                            style="padding: 8px 16px; font-size: 0.9rem;"
                                            onclick="return confirm('آیا از حذف این ادمین مطمئن هستید؟');">
                                            <i class="fas fa-trash"></i> حذف
                                        </a>
                                    </div>

                                    <!-- Permissions Display and Edit -->
                                    <details style="margin-top: 15px;">
                                        <summary
                                            style="cursor: pointer; font-weight: 600; padding: 10px; background: rgba(139, 92, 246, 0.1); border-radius: 6px; margin-bottom: 10px;">
                                            <i class="fas fa-key"></i> دسترسی‌ها
                                            <span style="color: var(--text-muted); font-weight: normal;">
                                                (<?php echo count($admin['permissions']); ?> مورد)
                                            </span>
                                        </summary>

                                        <form method="POST" style="margin-top: 15px;">
                                            <input type="hidden" name="edit_chat_id" value="<?php echo $admin_id; ?>">

                                            <div
                                                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; margin-bottom: 15px;">
                                                <?php foreach ($permission_map as $key => $label): ?>
                                                    <label
                                                        style="display: flex; align-items: center; gap: 8px; padding: 8px; background: rgba(255,255,255,0.5); border-radius: 6px; cursor: pointer;">
                                                        <input type="checkbox" name="edit_permissions[]" value="<?php echo $key; ?>"
                                                            <?php echo in_array($key, $admin['permissions']) ? 'checked' : ''; ?>
                                                            style="width: 18px; height: 18px;">
                                                        <span style="font-size: 0.9rem;"><?php echo $label; ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>

                                            <button type="submit" name="update_permissions" class="btn btn-primary"
                                                style="font-size: 0.9rem;">
                                                <i class="fas fa-save"></i> ذخیره تغییرات
                                            </button>
                                        </form>
                                    </details>

                                    <!-- Current Permissions Display -->
                                    <div
                                        style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(139, 92, 246, 0.2);">
                                        <strong style="display: block; margin-bottom: 10px;">دسترسی‌های فعلی:</strong>
                                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                            <?php if (empty($admin['permissions'])): ?>
                                                <span style="color: var(--text-muted); font-style: italic;">هیچ دسترسی ندارد</span>
                                            <?php else: ?>
                                                <?php foreach ($admin['permissions'] as $perm): ?>
                                                    <span
                                                        style="background: rgba(16, 185, 129, 0.2); color: var(--success); padding: 5px 12px; border-radius: 6px; font-size: 0.85rem;">
                                                        <?php echo $permission_map[$perm] ?? $perm; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-row {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    details summary::-webkit-details-marker {
        display: none;
    }

    details[open] summary {
        margin-bottom: 15px;
    }
</style>

<?php renderFooter(); ?>