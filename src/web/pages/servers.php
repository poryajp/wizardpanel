<?php
/**
 * Servers Management Page - FULL CRUD WITH PROTOCOLS AND SUB_HOST
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

// Handle add server
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_server'])) {
    $name = sanitizeInput($_POST['name']);
    $url = sanitizeInput($_POST['url']);
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password
    $type = sanitizeInput($_POST['type']);
    $sub_host = sanitizeInput($_POST['sub_host'] ?? '');

    // Handle protocols for marzban
    $protocols_json = null;
    if ($type === 'marzban') {
        if (isset($_POST['protocols']) && !empty($_POST['protocols'])) {
            $protocols = array_map('sanitizeInput', $_POST['protocols']);
            $protocols_json = json_encode(array_values($protocols));
        } else {
            // Default to VLESS if no protocols selected
            $protocols_json = json_encode(['vless']);
        }
    }

    if (!empty($name) && !empty($url) && !empty($username) && !empty($password)) {
        $stmt = pdo()->prepare("INSERT INTO servers (name, url, sub_host, marzban_protocols, username, password, type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        if ($stmt->execute([$name, $url, $sub_host, $protocols_json, $username, $password, $type])) {
            $success = 'سرور با موفقیت اضافه شد.';
        } else {
            $error = 'خطا در افزودن سرور.';
        }
    } else {
        $error = 'لطفاً تمام فیلدها را پر کنید.';
    }
}

// Handle edit server
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_server'])) {
    $id = (int) $_POST['server_id'];
    $name = sanitizeInput($_POST['name']);
    $url = sanitizeInput($_POST['url']);
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $type = sanitizeInput($_POST['type']);
    $sub_host = sanitizeInput($_POST['sub_host'] ?? '');

    // Handle protocols for marzban
    $protocols_json = null;
    if ($type === 'marzban') {
        if (isset($_POST['protocols']) && !empty($_POST['protocols'])) {
            $protocols = array_map('sanitizeInput', $_POST['protocols']);
            $protocols_json = json_encode(array_values($protocols));
        } else {
            // Default to VLESS if no protocols selected
            $protocols_json = json_encode(['vless']);
        }
    }

    // Only update password if provided
    if (!empty($password)) {
        $stmt = pdo()->prepare("UPDATE servers SET name=?, url=?, sub_host=?, marzban_protocols=?, username=?, password=?, type=? WHERE id=?");
        $success = $stmt->execute([$name, $url, $sub_host, $protocols_json, $username, $password, $type, $id]);
    } else {
        $stmt = pdo()->prepare("UPDATE servers SET name=?, url=?, sub_host=?, marzban_protocols=?, username=?, type=? WHERE id=?");
        $success = $stmt->execute([$name, $url, $sub_host, $protocols_json, $username, $type, $id]);
    }

    if ($success) {
        $success = 'سرور بهروزرسانی شد.';
    } else {
        $error = 'خطا در بهروزرسانی سرور.';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = pdo()->prepare("DELETE FROM servers WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'سرور حذف شد.';
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = pdo()->prepare("UPDATE servers SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = 'وضعیت سرور تغییر کرد.';
    }
}

// Get server for edit
$edit_server = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = pdo()->prepare("SELECT * FROM servers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_server = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all servers
$stmt = pdo()->query("SELECT * FROM servers ORDER BY id DESC");
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderHeader('مدیریت سرورها');
?>

<div class="layout">
    <?php renderSidebar('servers'); ?>

    <div class="main-content">
        <?php renderTopbar('🌐 مدیریت سرورها'); ?>

        <div class="content-area">
            <?php if ($success): ?>
                    <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                    <div class="alert alert-danger">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add/Edit Server Form -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3><i class="fas fa-plus"></i> <?php echo $edit_server ? 'ویرایش سرور' : 'افزودن سرور جدید'; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($edit_server): ?>
                                <input type="hidden" name="server_id" value="<?php echo $edit_server['id']; ?>">
                        <?php endif; ?>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label for="name">نام سرور *</label>
                                <input type="text" id="name" name="name"
                                    value="<?php echo $edit_server['name'] ?? ''; ?>" placeholder="مثال: سرور آلمان 1"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="type">نوع پنل *</label>
                                <select id="type" name="type" required onchange="toggleServerTypeFields()">
                                    <option value="marzban" <?php echo (isset($edit_server) && $edit_server['type'] == 'marzban') ? 'selected' : ''; ?>>🔷 Marzban</option>
                                    <option value="sanaei" <?php echo (isset($edit_server) && $edit_server['type'] == 'sanaei') ? 'selected' : ''; ?>>🔶 Sanaei</option>
                                    <option value="marzneshin" <?php echo (isset($edit_server) && $edit_server['type'] == 'marzneshin') ? 'selected' : ''; ?>>🔵 Marzneshin</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="url">آدرس پنل *</label>
                            <input type="text" id="url" name="url" value="<?php echo $edit_server['url'] ?? ''; ?>"
                                placeholder="https://panel.example.com" required>
                            <small style="color: var(--text-muted);">بدون / در انتها</small>
                        </div>
                        
                        <div class="form-group" id="sub_host_field" style="display: none;">
                            <label for="sub_host">آدرس اشتراک سفارشی (اختیاری)</label>
                            <input type="text" id="sub_host" name="sub_host" value="<?php echo $edit_server['sub_host'] ?? ''; ?>"
                                placeholder="https://custom.domain.com:2096">
                            <small style="color: var(--text-muted);">در صورت خالی بودن، از آدرس پنل استفاده می‌شود</small>
                        </div>
                        
                        <?php if ($edit_server): ?>
                                <?php
                                $existing_protocols = [];
                                if (!empty($edit_server['marzban_protocols'])) {
                                    $existing_protocols = json_decode($edit_server['marzban_protocols'], true) ?? [];
                                }
                                ?>
                        <?php endif; ?>
                        
                        <div class="form-group" id="protocols_field" style="display: none;">
                            <label>پروتکل‌های مرزبان (چک کنید)</label>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px; background: rgba(139, 92, 246, 0.05); border-radius: 6px;">
                                    <input type="checkbox" name="protocols[]" value="vmess" <?php echo ($edit_server && in_array('vmess', $existing_protocols ?? [])) ? 'checked' : ''; ?>>
                                    <span>VMess</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px; background: rgba(139, 92, 246, 0.05); border-radius: 6px;">
                                    <input type="checkbox" name="protocols[]" value="vless" <?php echo (!$edit_server || in_array('vless', $existing_protocols ?? [])) ? 'checked' : ''; ?>>
                                    <span>VLESS</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px; background: rgba(139, 92, 246, 0.05); border-radius: 6px;">
                                    <input type="checkbox" name="protocols[]" value="trojan" <?php echo ($edit_server && in_array('trojan', $existing_protocols ?? [])) ? 'checked' : ''; ?>>
                                    <span>Trojan</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px; background: rgba(139, 92, 246, 0.05); border-radius: 6px;">
                                    <input type="checkbox" name="protocols[]" value="shadowsocks" <?php echo ($edit_server && in_array('shadowsocks', $existing_protocols ?? [])) ? 'checked' : ''; ?>>
                                    <span>Shadowsocks</span>
                                </label>
                            </div>
                            <small style="color: var(--text-muted); display: block; margin-top: 8px;">پروتکل‌های انتخاب شده برای ساخت سرویس استفاده می‌شوند</small>
                        </div>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label for="username">نام کاربری *</label>
                                <input type="text" id="username" name="username"
                                    value="<?php echo $edit_server['username'] ?? ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="password">رمز عبور
                                    <?php echo $edit_server ? '(خالی بگذارید برای عدم تغییر)' : '*'; ?></label>
                                <input type="password" id="password" name="password"
                                    placeholder="<?php echo $edit_server ? 'برای تغییر وارد کنید' : ''; ?>" <?php echo $edit_server ? '' : 'required'; ?>>
                            </div>
                        </div>

                        <button type="submit" name="<?php echo $edit_server ? 'edit_server' : 'add_server'; ?>"
                            class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $edit_server ? 'بهروزرسانی' : 'افزودن'; ?>
                        </button>

                        <?php if ($edit_server): ?>
                                <a href="servers.php" class="btn btn-danger">
                                    <i class="fas fa-times"></i> لغو
                                </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Servers List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-server"></i> لیست سرورها (<?php echo count($servers); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($servers)): ?>
                            <p class="text-muted">هیچ سروری یافت نشد.</p>
                    <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>شناسه</th>
                                            <th>نام</th>
                                            <th>نوع</th>
                                            <th>آدرس</th>
                                            <th>پروتکل‌ها / ویژگی‌ها</th>
                                            <th>وضعیت</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($servers as $server): ?>
                                                <tr>
                                                    <td><?php echo $server['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($server['name']); ?></td>
                                                    <td>
                                                        <?php
                                                        $type_labels = [
                                                            'marzban' => '🔷 Marzban',
                                                            'sanaei' => '🔶 Sanaei',
                                                            'marzneshin' => '🔵 Marzneshin'
                                                        ];
                                                        echo $type_labels[$server['type']] ?? $server['type'];
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <code
                                                            style="font-size: 0.85rem;"><?php echo htmlspecialchars($server['url']); ?></code>
                                                    </td>
                                                    <td>
                                                        <?php if ($server['type'] === 'marzban' && !empty($server['marzban_protocols'])): ?>
                                                                <?php
                                                                $protocols = json_decode($server['marzban_protocols'], true) ?? [];
                                                                if (!empty($protocols)) {
                                                                    echo '<div style="display: flex; gap: 4px; flex-wrap: wrap;">';
                                                                    foreach ($protocols as $protocol) {
                                                                        echo '<span style="font-size: 0.75rem; padding: 2px 6px; background: rgba(139, 92, 246, 0.2); border-radius: 4px; color: var(--primary);">' . htmlspecialchars($protocol) . '</span>';
                                                                    }
                                                                    echo '</div>';
                                                                } else {
                                                                    echo '<span style="color: var(--text-muted); font-size: 0.85rem;">همه</span>';
                                                                }
                                                                ?>
                                                        <?php elseif (!empty($server['sub_host'])): ?>
                                                                <span style="font-size: 0.75rem; padding: 2px 6px; background: rgba(45, 212, 191, 0.2); border-radius: 4px; color: var(--success);">🔗 Custom Sub</span>
                                                        <?php else: ?>
                                                                <span style="color: var(--text-muted); font-size: 0.85rem;">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($server['status'] === 'active'): ?>
                                                                <span style="color: var(--success);">✅ فعال</span>
                                                        <?php else: ?>
                                                                <span style="color: var(--danger);">❌ غیرفعال</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="?edit=<?php echo $server['id']; ?>" class="btn btn-primary"
                                                            style="padding: 6px 12px; font-size: 0.85rem;">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?toggle=<?php echo $server['id']; ?>" class="btn btn-success"
                                                            style="padding: 6px 12px; font-size: 0.85rem;">
                                                            <?php echo $server['status'] === 'active' ? 'غیرفعال' : 'فعال'; ?>
                                                        </a>
                                                        <a href="?delete=<?php echo $server['id']; ?>" class="btn btn-danger"
                                                            style="padding: 6px 12px; font-size: 0.85rem;"
                                                            onclick="return confirm('آیا مطمئن هستید؟\nتوجه: تمام پلن‌های مربوط به این سرور باقی خواهند ماند.');">
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

<script>
function toggleServerTypeFields() {
    const serverType = document.getElementById('type').value;
    const protocolsField = document.getElementById('protocols_field');
    const subHostField = document.getElementById('sub_host_field');
    
    // Show protocols only for marzban
    if (serverType === 'marzban') {
        protocolsField.style.display = 'block';
    } else {
        protocolsField.style.display = 'none';
    }
    
    // Show sub_host for marzban and sanaei
    if (serverType === 'marzban' || serverType === 'sanaei') {
        subHostField.style.display = 'block';
    } else {
        subHostField.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleServerTypeFields();
});
</script>

<?php renderFooter(); ?>