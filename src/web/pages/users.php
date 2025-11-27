<?php
/**
 * Users Management Page - WITH COMPLETE USER LIST
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
$user_info = null;

// Get user stats
$stats = [];
$stats['total'] = pdo()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['active'] = pdo()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$stats['banned'] = pdo()->query("SELECT COUNT(*) FROM users WHERE status = 'banned'")->fetchColumn();

// Pagination for user list
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total users count
$total_users = $stats['total'];
$total_pages = ceil($total_users / $per_page);

// Get all users with pagination
$stmt = pdo()->prepare("
    SELECT u.*, 
           COUNT(DISTINCT s.id) as service_count
    FROM users u
    LEFT JOIN services s ON u.chat_id = s.owner_chat_id
    GROUP BY u.chat_id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$per_page, $offset]);
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search
if (isset($_GET['search'])) {
    $chat_id = $_GET['chat_id'] ?? '';

    if (!empty($chat_id) && is_numeric($chat_id)) {
        $stmt = pdo()->prepare("SELECT * FROM users WHERE chat_id = ?");
        $stmt->execute([$chat_id]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_info) {
            $error = 'کاربر یافت نشد.';
        }
    }
}

// Handle add balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_balance'])) {
    $chat_id = (int) $_POST['chat_id'];
    $amount = (int) $_POST['amount'];

    if ($amount != 0) {
        updateUserBalance($chat_id, abs($amount), $amount > 0 ? 'add' : 'subtract');
        $success = 'موجودی کاربر به‌روزرسانی شد.';

        // Refresh user info if viewing a user
        if (isset($_GET['search']) && isset($_GET['chat_id'])) {
            $stmt = pdo()->prepare("SELECT * FROM users WHERE chat_id = ?");
            $stmt->execute([$chat_id]);
            $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// Handle ban/unban
if (isset($_GET['ban'])) {
    $chat_id = (int) $_GET['ban'];
    setUserStatus($chat_id, 'banned');
    $success = 'کاربر مسدود شد.';
    header('Location: ?');
    exit;
}

if (isset($_GET['unban'])) {
    $chat_id = (int) $_GET['unban'];
    setUserStatus($chat_id, 'active');
    $success = 'کاربر آزاد شد.';
    header('Location: ?');
    exit;
}

renderHeader('مدیریت کاربران');
?>

<div class="layout">
    <?php renderSidebar('users'); ?>

    <div class="main-content">
        <?php renderTopbar('👥 مدیریت کاربران'); ?>

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
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                        <div class="stat-label">کل کاربران</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['active']); ?></div>
                        <div class="stat-label">کاربران فعال</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['banned']); ?></div>
                        <div class="stat-label">کاربران مسدود</div>
                    </div>
                </div>
            </div>

            <!-- Search User -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> جستجوی کاربر</h3>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div class="form-group">
                            <label for="chat_id">شناسه عددی کاربر (Chat ID)</label>
                            <input type="text" id="chat_id" name="chat_id" value="<?php echo $_GET['chat_id'] ?? ''; ?>"
                                required placeholder="مثال: 123456789">
                        </div>
                        <button type="submit" name="search" class="btn btn-primary">
                            <i class="fas fa-search"></i> جستجو
                        </button>
                    </form>
                </div>
            </div>

            <!-- User Info (if searched) -->
            <?php if ($user_info): ?>
                    <div class="card mb-20">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> اطلاعات کاربر</h3>
                        </div>
                        <div class="card-body">
                            <p><strong>نام:</strong> <?php echo htmlspecialchars($user_info['first_name']); ?></p>
                            <p><strong>شناسه:</strong> <code><?php echo $user_info['chat_id']; ?></code></p>
                            <p><strong>موجودی:</strong> <?php echo number_format($user_info['balance']); ?> تومان</p>
                            <p><strong>وضعیت:</strong>
                                <?php if ($user_info['status'] === 'active'): ?>
                                        <span style="color: var(--success);">✅ فعال</span>
                                <?php else: ?>
                                        <span style="color: var(--danger);">❌ مسدود</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>تاریخ ثبت‌نام:</strong> <?php echo $user_info['created_at']; ?></p>

                            <hr style="margin: 20px 0; border-color: var(--border-color);">

                            <!-- Actions -->
                            <form method="POST" style="display: inline-block; margin-left: 10px;">
                                <input type="hidden" name="chat_id" value="<?php echo $user_info['chat_id']; ?>">
                                <div class="form-group" style="display: inline-block; margin: 0;">
                                    <input type="number" name="amount" placeholder="مبلغ (+ یا -)"
                                        style="width: 150px; display: inline-block;" required>
                                </div>
                                <button type="submit" name="add_balance" class="btn btn-primary" style="display: inline-block;">
                                    <i class="fas fa-coins"></i> تغییر موجودی
                                </button>
                            </form>

                            <?php if ($user_info['status'] === 'active'): ?>
                                    <a href="?chat_id=<?php echo $user_info['chat_id']; ?>&search=1&ban=<?php echo $user_info['chat_id']; ?>"
                                        class="btn btn-danger" onclick="return confirm('آیا مطمئن هستید؟');">
                                        <i class="fas fa-ban"></i> مسدود کردن
                                    </a>
                            <?php else: ?>
                                    <a href="?chat_id=<?php echo $user_info['chat_id']; ?>&search=1&unban=<?php echo $user_info['chat_id']; ?>"
                                        class="btn btn-success">
                                        <i class="fas fa-check"></i> آزاد کردن
                                    </a>
                            <?php endif; ?>

                            <?php
                            // Get user services
                            $services = getUserServices($user_info['chat_id']);
                            ?>

                            <h4 style="margin-top: 30px;">سرویس‌های کاربر (<?php echo count($services); ?>)</h4>
                            <?php if (empty($services)): ?>
                                    <p class="text-muted">کاربر هیچ سرویسی ندارد.</p>
                            <?php else: ?>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>پلن</th>
                                                <th>نام سرویس</th>
                                                <th>تاریخ خرید</th>
                                                <th>انقضا</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($services as $service): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($service['plan_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($service['custom_name'] ?? $service['marzban_username']); ?>
                                                        </td>
                                                        <td><?php echo date('Y/m/d', strtotime($service['purchase_date'])); ?></td>
                                                        <td><?php echo $service['expire_timestamp'] ? date('Y/m/d', $service['expire_timestamp']) : 'نامحدود'; ?>
                                                        </td>
                                                    </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                            <?php endif; ?>
                        </div>
                    </div>
            <?php endif; ?>

            <!-- All Users List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> لیست تمام کاربران (<?php echo number_format($total_users); ?> نفر)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($all_users)): ?>
                            <p class="text-muted">هیچ کاربری یافت نشد.</p>
                    <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>نام</th>
                                            <th>Chat ID</th>
                                            <th>موجودی</th>
                                            <th>تعداد سرویس</th>
                                            <th>وضعیت</th>
                                            <th>تاریخ عضویت</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_users as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                                    <td><code><?php echo $user['chat_id']; ?></code></td>
                                                    <td><?php echo number_format($user['balance']); ?> تومان</td>
                                                    <td><?php echo $user['service_count']; ?></td>
                                                    <td>
                                                        <?php if ($user['status'] === 'active'): ?>
                                                                <span style="color: var(--success);">✅ فعال</span>
                                                        <?php else: ?>
                                                                <span style="color: var(--danger);">❌ مسدود</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
                                                    <td>
                                                        <a href="?search=1&chat_id=<?php echo $user['chat_id']; ?>" 
                                                            class="btn btn-primary" style="padding: 4px 10px; font-size: 0.85rem;">
                                                            <i class="fas fa-eye"></i> مشاهده
                                                        </a>
                                                    </td>
                                                </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                    <dic style="margin-top: 20px; text-align: center;">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <?php if ($i == $page): ?>
                                                        <span style="padding: 8px 12px; margin: 0 2px; background: var(--primary); color: #fff; border-radius: 4px; display: inline-block;">
                                                            <?php echo $i; ?>
                                                        </span>
                                                <?php else: ?>
                                                        <a href="?page=<?php echo $i; ?>" 
                                                            style="padding: 8px 12px; margin: 0 2px; background: rgba(139, 92, 246, 0.1); color: var(--primary); border-radius: 4px; display: inline-block; text-decoration: none;">
                                                            <?php echo $i; ?>
                                                        </a>
                                                <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                            <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>