<?php
/**
 * Categories Management Page
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/web_functions.php';

requireLogin();

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: ../index.php');
    exit();
}

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitizeInput($_POST['name']);

        if (!empty($name)) {
            $stmt = pdo()->prepare("INSERT INTO categories (name, status) VALUES (?, 'active')");
            if ($stmt->execute([$name])) {
                $success = 'دسته‌بندی با موفقیت اضافه شد.';
            } else {
                $error = 'خطا در افزودن دسته‌بندی.';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = pdo()->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'دسته‌بندی حذف شد.';
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = pdo()->prepare("UPDATE categories SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'وضعیت دسته‌بندی تغییر کرد.';
    }
}

// Get all categories
$categories = getCategories();

renderHeader('مدیریت دسته‌بندی‌ها');
?>

<div class="layout">
    <?php renderSidebar('categories'); ?>

    <div class="main-content">
        <?php renderTopbar('🗂 مدیریت دسته‌بندی‌ها'); ?>

        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Category Form -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-plus"></i> افزودن دسته‌بندی جدید</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">نام دسته‌بندی</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fas fa-plus"></i> افزودن
                        </button>
                    </form>
                </div>
            </div>

            <!-- Categories List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> لیست دسته‌بندی‌ها</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <p class="text-muted">هیچ دسته‌بندی‌ای یافت نشد.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>شناسه</th>
                                    <th>نام</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?php echo $cat['id']; ?></td>
                                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td>
                                            <?php if ($cat['status'] === 'active'): ?>
                                                <span style="color: var(--success);">✅ فعال</span>
                                            <?php else: ?>
                                                <span style="color: var(--danger);">❌ غیرفعال</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?toggle=<?php echo $cat['id']; ?>" class="btn btn-success"
                                                style="padding: 6px 12px; font-size: 0.85rem;">
                                                <?php echo $cat['status'] === 'active' ? 'غیرفعال کردن' : 'فعال کردن'; ?>
                                            </a>
                                            <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-danger"
                                                style="padding: 6px 12px; font-size: 0.85rem;"
                                                onclick="return confirm('آیا مطمئن هستید؟');">
                                                <i class="fas fa-trash"></i> حذف
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

<?php renderFooter(); ?>