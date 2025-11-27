<?php
/**
 * Broadcast Message Page - AJAX BATCH PROCESSING
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

// AJAX endpoint for batch processing
if (isset($_GET['action']) && $_GET['action'] === 'send_batch') {
    header('Content-Type: application/json');
    
    // Check if BOT_TOKEN is defined
    if (!defined('BOT_TOKEN')) {
        echo json_encode(['success' => false, 'error' => 'BOT_TOKEN تعریف نشده است. لطفاً فایل config.php را بررسی کنید.']);
        exit;
    }
    
    if (BOT_TOKEN === 'TOKEN' || empty(BOT_TOKEN)) {
        echo json_encode(['success' => false, 'error' => 'BOT_TOKEN در فایل config.php به درستی تنظیم نشده است.']);
        exit;
    }

    $offset = (int) ($_POST['offset'] ?? 0);
    $batch_size = 50; // Process 50 users at a time
    $message_text = $_POST['message_text'] ?? '';
    $target_group = $_POST['target_group'] ?? 'all';
    $photo_id = sanitizeInput($_POST['photo_id'] ?? '');

    if (empty($message_text)) {
        echo json_encode(['success' => false, 'error' => 'متن پیام خالی است']);
        exit;
    }

    // Get target users based on selection
    switch ($target_group) {
        case 'all':
            $stmt = pdo()->query("SELECT chat_id FROM users WHERE status = 'active' LIMIT $offset, $batch_size");
            $count_stmt = pdo()->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
            break;
        case 'with_service':
            $stmt = pdo()->query("
                SELECT DISTINCT u.chat_id 
                FROM users u 
                JOIN services s ON u.chat_id = s.owner_chat_id 
                WHERE u.status = 'active'
                LIMIT $offset, $batch_size
            ");
            $count_stmt = pdo()->query("SELECT COUNT(DISTINCT u.chat_id) FROM users u JOIN services s ON u.chat_id = s.owner_chat_id WHERE u.status = 'active'");
            break;
        case 'no_service':
            $stmt = pdo()->query("
                SELECT chat_id 
                FROM users 
                WHERE status = 'active' 
                AND chat_id NOT IN (SELECT DISTINCT owner_chat_id FROM services)
                LIMIT $offset, $batch_size
            ");
            $count_stmt = pdo()->query("SELECT COUNT(*) FROM users WHERE status = 'active' AND chat_id NOT IN (SELECT DISTINCT owner_chat_id FROM services)");
            break;
        case 'active_service':
            $now = time();
            $stmt = pdo()->prepare("
                SELECT DISTINCT u.chat_id 
                FROM users u 
                JOIN services s ON u.chat_id = s.owner_chat_id 
                WHERE u.status = 'active' 
                AND s.expire_timestamp > ?
                LIMIT $offset, $batch_size
            ");
            $stmt->execute([$now]);
            $count_stmt = pdo()->prepare("SELECT COUNT(DISTINCT u.chat_id) FROM users u JOIN services s ON u.chat_id = s.owner_chat_id WHERE u.status = 'active' AND s.expire_timestamp > ?");
            $count_stmt->execute([$now]);
            break;
        default:
            $stmt = pdo()->query("SELECT chat_id FROM users WHERE status = 'active' LIMIT $offset, $batch_size");
            $count_stmt = pdo()->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    }

    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $total_users = $count_stmt->fetchColumn();

    $sent = 0;
    $failed = 0;
    $error_details = [];

    // Send messages to this batch
    foreach ($users as $chat_id) {
        try {
            if (!empty($photo_id)) {
                // Send photo with caption using cURL for better error handling
                $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto";
                $data = [
                    'chat_id' => $chat_id,
                    'photo' => $photo_id,
                    'caption' => $message_text,
                    'parse_mode' => 'HTML'
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $result = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($result && $http_code == 200) {
                    $response = json_decode($result, true);
                    if ($response && isset($response['ok']) && $response['ok']) {
                        $sent++;
                    } else {
                        $failed++;
                        $error_details[] = "Chat $chat_id: " . ($response['description'] ?? 'Unknown error');
                    }
                } else {
                    $failed++;
                    $error_details[] = "Chat $chat_id: HTTP $http_code";
                }
            } else {
                // Send text message
                $response = sendMessage($chat_id, $message_text);
                $decoded = json_decode($response, true);
                if ($decoded && isset($decoded['ok']) && $decoded['ok']) {
                    $sent++;
                } else {
                    $failed++;
                    $error_details[] = "Chat $chat_id: " . ($decoded['description'] ?? 'sendMessage failed');
                }
            }

            // Small delay to avoid rate limiting
            usleep(30000); // 30ms delay
        } catch (Exception $e) {
            $failed++;
            $error_details[] = "Chat $chat_id: Exception - " . $e->getMessage();
        }
    }

    $response_data = [
        'success' => true,
        'sent' => $sent,
        'failed' => $failed,
        'total' => $total_users,
        'processed' => $offset + count($users),
        'has_more' => ($offset + count($users)) < $total_users
    ];
    
    // Include error details in debug mode (first batch only)
    if ($offset == 0 && !empty($error_details)) {
        $response_data['debug_errors'] = array_slice($error_details, 0, 5); // First 5 errors
    }
    
    echo json_encode($response_data);
    exit;
}

// Get statistics for display
$stats = [];
$stats['all'] = pdo()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$stats['with_service'] = pdo()->query("SELECT COUNT(DISTINCT owner_chat_id) FROM services s JOIN users u ON s.owner_chat_id = u.chat_id WHERE u.status = 'active'")->fetchColumn();
$stats['no_service'] = $stats['all'] - $stats['with_service'];
$now = time();
$stmt = pdo()->prepare("SELECT COUNT(DISTINCT s.owner_chat_id) FROM services s JOIN users u ON s.owner_chat_id = u.chat_id WHERE u.status = 'active' AND s.expire_timestamp > ?");
$stmt->execute([$now]);
$stats['active_service'] = $stmt->fetchColumn();

renderHeader('پیام همگانی');
?>

<div class="layout">
    <?php renderSidebar('broadcast'); ?>

    <div class="main-content">
        <?php renderTopbar('📣 ارسال پیام همگانی'); ?>

        <div class="content-area">
            <!-- Progress Area (hidden by default) -->
            <div id="progressArea" class="card mb-20" style="display: none;">
                <div class="card-header">
                    <h3><i class="fas fa-spinner fa-spin"></i> در حال ارسال پیام...</h3>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span id="progressText">در حال آماده‌سازی...</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div
                            style="background: rgba(148, 163, 184, 0.2); border-radius: 10px; height: 20px; overflow: hidden;">
                            <div id="progressBar"
                                style="background: linear-gradient(90deg, var(--primary), var(--primary-hover)); height: 100%; width: 0%; transition: width 0.3s;">
                            </div>
                        </div>
                    </div>
                    <div id="progressStats"
                        style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px;">
                        <div
                            style="text-align: center; padding: 10px; background: rgba(16, 185, 129, 0.1); border-radius: 8px;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);" id="sentCount">0
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.9rem;">ارسال موفق</div>
                        </div>
                        <div
                            style="text-align: center; padding: 10px; background: rgba(239, 68, 68, 0.1); border-radius: 8px;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--danger);" id="failedCount">0
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.9rem;">ارسال ناموفق</div>
                        </div>
                        <div
                            style="text-align: center; padding: 10px; background: rgba(59, 130, 246, 0.1); border-radius: 8px;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--info);" id="totalCount">0
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.9rem;">کل کاربران</div>
                        </div>
                    </div>
                    <div id="debugErrors" style="margin-top: 15px; color: var(--danger); font-size: 0.85rem;"></div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-grid mb-20">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['all']); ?></div>
                        <div class="stat-label">همه کاربران فعال</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['with_service']); ?></div>
                        <div class="stat-label">با سرویس</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['no_service']); ?></div>
                        <div class="stat-label">بدون سرویس</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['active_service']); ?></div>
                        <div class="stat-label">سرویس فعال</div>
                    </div>
                </div>
            </div>

            <!-- Broadcast Form -->
            <div class="card" id="broadcastCard">
                <div class="card-header">
                    <h3><i class="fas fa-paper-plane"></i> ارسال پیام جدید</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <strong>⚠️ هشدار:</strong> پیام به تمام کاربران گروه انتخابی ارسال خواهد شد. این عمل قابل برگشت
                        نیست!
                    </div>

                    <form id="broadcastForm" onsubmit="startBroadcast(event)">
                        <div class="form-group">
                            <label for="target_group">گروه هدف *</label>
                            <select id="target_group" name="target_group" required onchange="updateTargetCount()">
                                <option value="all">📢 همه کاربران فعال (<?php echo number_format($stats['all']); ?>
                                    نفر)</option>
                                <option value="with_service">📦 کاربران دارای سرویس
                                    (<?php echo number_format($stats['with_service']); ?> نفر)</option>
                                <option value="no_service">⭕ کاربران بدون سرویس
                                    (<?php echo number_format($stats['no_service']); ?> نفر)</option>
                                <option value="active_service">✅ کاربران با سرویس فعال
                                    (<?php echo number_format($stats['active_service']); ?> نفر)</option>
                            </select>
                            <div id="targetCount"
                                style="margin-top: 10px; padding: 10px; background: rgba(59, 130, 246, 0.1); border-radius: 6px; color: var(--info);">
                                📊 تعداد گیرندگان: <strong
                                    id="countValue"><?php echo number_format($stats['all']); ?></strong> نفر
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message_text">متن پیام *</label>
                            <textarea id="message_text" name="message_text" rows="8" required
                                placeholder="متن پیام همگانی خود را اینجا بنویسید...&#10;&#10;می‌توانید از تگ‌های HTML استفاده کنید:&#10;<b>متن ضخیم</b>&#10;<i>متن کج</i>&#10;<code>کد</code>&#10;<a href='url'>لینک</a>"></textarea>
                            <small style="color: var(--text-muted);">از HTML برای فرمت‌بندی استفاده کنید</small>
                        </div>

                        <div class="form-group">
                            <label for="photo_id">شناسه تصویر (اختیاری)</label>
                            <input type="text" id="photo_id" name="photo_id" placeholder="AgACAgQAAxkB...">
                            <small style="color: var(--text-muted);">برای ارسال پیام با تصویر، یک عکس به ربات ارسال کنید
                                و Photo ID آن را اینجا وارد کنید</small>
                        </div>

                        <div style="display: flex; gap: 15px; align-items: center; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary" id="sendBtn"
                                style="font-size: 1.1rem; padding: 15px 40px;">
                                <i class="fas fa-paper-plane"></i> ارسال پیام همگانی
                            </button>

                            <div style="color: var(--text-muted); font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> ارسال به صورت خودکار در پس‌زمینه انجام می‌شود
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview -->
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">
                    <h3><i class="fas fa-eye"></i> پیش‌نمایش</h3>
                </div>
                <div class="card-body">
                    <div id="preview"
                        style="background: rgba(139, 92, 246, 0.05); padding: 20px; border-radius: 12px; min-height: 100px; border-right: 4px solid var(--primary);">
                        <p style="color: var(--text-muted); text-align: center;">متن پیام خود را بنویسید تا اینجا نمایش
                            داده شود...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update target count
    function updateTargetCount() {
        const select = document.getElementById('target_group');
        const countValue = document.getElementById('countValue');
        const counts = {
            'all': '<?php echo number_format($stats['all']); ?>',
            'with_service': '<?php echo number_format($stats['with_service']); ?>',
            'no_service': '<?php echo number_format($stats['no_service']); ?>',
            'active_service': '<?php echo number_format($stats['active_service']); ?>'
        };
        countValue.textContent = counts[select.value];
    }

    // Live preview
    document.getElementById('message_text').addEventListener('input', function () {
        const preview = document.getElementById('preview');
        const text = this.value;

        if (text.trim() === '') {
            preview.innerHTML = '<p style="color: var(--text-muted); text-align: center;">متن پیام خود را بنویسید تا اینجا نمایش داده شود...</p>';
        } else {
            // Simple HTML rendering (tags are already in HTML format)
            preview.innerHTML = text.replace(/\n/g, '<br>');
        }
    });

    // Batch broadcast with AJAX
    let totalSent = 0;
    let totalFailed = 0;
    let totalUsers = 0;

    function startBroadcast(event) {
        event.preventDefault();

        const form = document.getElementById('broadcastForm');
        const messageText = document.getElementById('message_text').value;
        const targetGroup = document.getElementById('target_group').value;
        const photoId = document.getElementById('photo_id').value;
        const countText = document.getElementById('countValue').textContent.replace(/,/g, '');

        if (!confirm(`آیا از ارسال پیام همگانی به ${document.getElementById('countValue').textContent} نفر مطمئن هستید؟`)) {
            return;
        }

        // Reset counters
        totalSent = 0;
        totalFailed = 0;
        totalUsers = 0;

        // Show progress area and hide form
        document.getElementById('progressArea').style.display = 'block';
        document.getElementById('broadcastCard').style.display = 'none';
        document.getElementById('sendBtn').disabled = true;

        // Start batch processing
        sendBatch(0, messageText, targetGroup, photoId);
    }

    function sendBatch(offset, messageText, targetGroup, photoId) {
        const formData = new FormData();
        formData.append('offset', offset);
        formData.append('message_text', messageText);
        formData.append('target_group', targetGroup);
        formData.append('photo_id', photoId);

        fetch('?action=send_batch', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('خطا: ' + (data.error || 'مشکلی در ارسال پیش آمد'));
                    resetBroadcast();
                    return;
                }

                // Update counters
                totalSent += data.sent;
                totalFailed += data.failed;
                totalUsers = data.total;

                // Update UI
                document.getElementById('sentCount').textContent = totalSent.toLocaleString('fa-IR');
                document.getElementById('failedCount').textContent = totalFailed.toLocaleString('fa-IR');
                document.getElementById('totalCount').textContent = totalUsers.toLocaleString('fa-IR');

                const percent = Math.round((data.processed / data.total) * 100);
                document.getElementById('progressBar').style.width = percent + '%';
                document.getElementById('progressPercent').textContent = percent + '%';
                document.getElementById('progressText').textContent = `ارسال شده: ${data.processed.toLocaleString('fa-IR')} از ${data.total.toLocaleString('fa-IR')}`;

                // Show debug errors if any
                if (data.debug_errors && data.debug_errors.length > 0) {
                    document.getElementById('debugErrors').innerHTML = '<strong>جزئیات خطا:</strong><br>' + data.debug_errors.join('<br>');
                }

                // Continue if there are more users
                if (data.has_more) {
                    sendBatch(data.processed, messageText, targetGroup, photoId);
                } else {
                    // Finished!
                    document.getElementById('progressText').textContent = '✅ ارسال با موفقیت به پایان رسید!';
                    document.querySelector('#progressArea h3').innerHTML = '<i class="fas fa-check-circle"></i> ارسال کامل شد';

                    setTimeout(() => {
                        if (confirm('پیام به همه کاربران ارسال شد. آیا می‌خواهید پیام جدیدی ارسال کنید؟')) {
                            resetBroadcast();
                        }
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('خطا در ارتباط با سرور. لطفا دوباره تلاش کنید.\nجزئیات: ' + error.message);
                resetBroadcast();
            });
    }

    function resetBroadcast() {
        document.getElementById('progressArea').style.display = 'none';
        document.getElementById('broadcastCard').style.display = 'block';
        document.getElementById('sendBtn').disabled = false;
        document.getElementById('broadcastForm').reset();
        document.getElementById('debugErrors').innerHTML = '';
        updateTargetCount();
    }
</script>

<?php renderFooter(); ?>