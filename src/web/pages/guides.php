<?php
/**
 * Guides Management Page - FULL CRUD
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

// Handle add guide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_guide'])) {
    $button_name = sanitizeInput($_POST['button_name']);
    $content_type = sanitizeInput($_POST['content_type']);
    $message_text = $_POST['message_text'] ?? '';
    $photo_id = sanitizeInput($_POST['photo_id'] ?? '');

    if (!empty($button_name)) {
        $stmt = pdo()->prepare("INSERT INTO guides (button_name, content_type, message_text, photo_id, status) VALUES (?, ?, ?, ?, 'active')");
        if ($stmt->execute([$button_name, $content_type, $message_text, $photo_id])) {
            $success = 'راهنما با موفقیت اضافه شد.';
        } else {
            $error = 'خطا در افزودن راهنما.';
        }
    } else {
        $error = 'لطفاً نام دکمه را وارد کنید.';
    }
}

// Handle edit guide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_guide'])) {
    $id = (int) $_POST['guide_id'];
    $button_name = sanitizeInput($_POST['button_name']);
    $content_type = sanitizeInput($_POST['content_type']);
    $message_text = $_POST['message_text'] ?? '';
    $photo_id = sanitizeInput($_POST['photo_id'] ?? '');

    $stmt = pdo()->prepare("UPDATE guides SET button_name=?, content_type=?, message_text=?, photo_id=? WHERE id=?");
    if ($stmt->execute([$button_name, $content_type, $message_text, $photo_id, $id])) {
        $success = 'راهنما بهروزرسانی شد.';
    } else {
        $error = 'خطا در بهروزرسانی راهنما.';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = pdo()->prepare("DELETE FROM guides WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'راهنما حذف شد.';
    }
}

// Handle toggle
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = pdo()->prepare("UPDATE guides SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'وضعیت راهنما تغییر کرد.';
    }
}

// Get guide for edit
$edit_guide = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = pdo()->prepare("SELECT * FROM guides WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_guide = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all guides
$stmt = pdo()->query("SELECT * FROM guides ORDER BY id DESC");
$guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderHeader('مدیریت راهنما');
?>

<div class="layout">
    <?php renderSidebar('guides'); ?>

    <div class="main-content">
        <?php renderTopbar('📚 مدیریت راهنما'); ?>

        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add/Edit Guide Form -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-plus"></i> <?php echo $edit_guide ? 'ویرایش راهنما' : 'افزودن راهنمای جدید'; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_guide): ?>
                            <input type="hidden" name="guide_id" value="<?php echo $edit_guide['id']; ?>">
                        <?php endif; ?>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label for="button_name">عنوان دکمه *</label>
                                <input type="text" id="button_name" name="button_name"
                                    value="<?php echo $edit_guide['button_name'] ?? ''; ?>"
                                    placeholder="مثال: نحوه اتصال اندروید" required>
                            </div>

                            <div class="form-group">
                                <label for="content_type">نوع محتوا *</label>
                                <select id="content_type" name="content_type" required>
                                    <option value="text" <?php echo (isset($edit_guide) && $edit_guide['content_type'] == 'text') ? 'selected' : ''; ?>>📝 متن</option>
                                    <option value="photo" <?php echo (isset($edit_guide) && $edit_guide['content_type'] == 'photo') ? 'selected' : ''; ?>>🖼 تصویر + متن
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message_text">متن پیام</label>
                            <textarea id="message_text" name="message_text" rows="5"
                                placeholder="متن راهنما..."><?php echo $edit_guide['message_text'] ?? ''; ?></textarea>
                            <small style="color: var(--text-muted);">از Markdown برای فرمت‌بندی استفاده کنید</small>
                        </div>

                        <div class="form-group" id="photo_field"
                            style="<?php echo (isset($edit_guide) && $edit_guide['content_type'] == 'text') ? 'display:none;' : ''; ?>">
                            <label for="photo_id">شناسه تصویر تلگرام (Photo ID)</label>
                            <input type="text" id="photo_id" name="photo_id"
                                value="<?php echo $edit_guide['photo_id'] ?? ''; ?>" placeholder="AgACAgQAAxkB...">
                            <small style="color: var(--text-muted);">برای دریافت: یک تصویر به ربات ارسال کنید</small>
                        </div>

                        <button type="submit" name="<?php echo $edit_guide ? 'edit_guide' : 'add_guide'; ?>"
                            class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $edit_guide ? 'بهروزرسانی' : 'افزودن'; ?>
                        </button>

                        <?php if ($edit_guide): ?>
                            <a href="guides.php" class="btn btn-danger">
                                <i class="fas fa-times"></i> لغو
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Guides List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> لیست راهنماها (<?php echo count($guides); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($guides)): ?>
                        <p class="text-muted">هیچ راهنمایی یافت نشد.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>شناسه</th>
                                    <th>عنوان دکمه</th>
                                    <th>نوع محتوا</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guides as $guide): ?>
                                    <tr>
                                        <td><?php echo $guide['id']; ?></td>
                                        <td><?php echo htmlspecialchars($guide['button_name']); ?></td>
                                        <td>
                                            <?php
                                            echo $guide['content_type'] === 'text' ? '📝 متن' : '🖼 تصویر';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($guide['status'] === 'active'): ?>
                                                <span style="color: var(--success);">✅ فعال</span>
                                            <?php else: ?>
                                                <span style="color: var(--danger);">❌ غیرفعال</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?php echo $guide['id']; ?>" class="btn btn-primary"
                                                style="padding: 6px 12px; font-size: 0.85rem;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?toggle=<?php echo $guide['id']; ?>" class="btn btn-success"
                                                style="padding: 6px 12px; font-size: 0.85rem;">
                                                <?php echo $guide['status'] === 'active' ? 'غیرفعال' : 'فعال'; ?>
                                            </a>
                                            <a href="?delete=<?php echo $guide['id']; ?>" class="btn btn-danger"
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
    // Show/hide photo field based on content type
    document.getElementById('content_type').addEventListener('change', function () {
        const photoField = document.getElementById('photo_field');
        if (this.value === 'photo') {
            photoField.style.display = 'block';
        } else {
            photoField.style.display = 'none';
        }
    });
</script>

<?php renderFooter(); ?>