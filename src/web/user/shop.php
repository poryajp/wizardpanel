<?php
require_once __DIR__ . '/session.php';
requireUserLogin();

$user = getCurrentUser();
$categories = getCategories(true); // Only active categories

// Handle Purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy') {
    $plan_id = (int) $_POST['plan_id'];
    $custom_name = trim($_POST['custom_name']);

    if (empty($custom_name)) {
        echo json_encode(['success' => false, 'message' => 'نام سرویس نمی‌تواند خالی باشد']);
        exit;
    }

    $plan = getPlanById($plan_id);
    if (!$plan || $plan['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'پلن یافت نشد یا غیرفعال است']);
        exit;
    }

    if ($plan['purchase_limit'] > 0 && $plan['purchase_count'] >= $plan['purchase_limit']) {
        echo json_encode(['success' => false, 'message' => 'ظرفیت خرید این پلن تکمیل شده است']);
        exit;
    }

    // Check balance
    if ($user['balance'] < $plan['price']) {
        echo json_encode([
            'success' => false,
            'need_charge' => true,
            'message' => 'موجودی کافی نیست',
            'required' => $plan['price'],
            'current' => $user['balance']
        ]);
        exit;
    }

    // Complete purchase
    $result = completePurchase($user['chat_id'], $plan_id, $custom_name, $plan['price'], null, null, false);

    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'خرید با موفقیت انجام شد']);
    } else {
        echo json_encode(['success' => false, 'message' => $result['error_message'] ?? 'خطا در انجام خرید']);
    }
    exit;
}

// Get URL parameters
$selected_cat_id = isset($_GET['cat_id']) ? (int) $_GET['cat_id'] : null;
$selected_server_id = isset($_GET['server_id']) ? (int) $_GET['server_id'] : null;

// Auto-select server if only one exists
if ($selected_cat_id && !$selected_server_id) {
    $stmt = pdo()->prepare("
        SELECT DISTINCT s.id 
        FROM servers s
        JOIN plans p ON s.id = p.server_id
        WHERE p.category_id = ? AND p.status = 'active' AND s.status = 'active'
    ");
    $stmt->execute([$selected_cat_id]);
    $active_servers_in_cat = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($active_servers_in_cat) === 1) {
        $selected_server_id = $active_servers_in_cat[0]['id'];
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>فروشگاه</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet"
        type="text/css" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div id="loading" class="loading-overlay" style="display: none;">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <div class="header">
            <div class="user-profile">
                <a href="index.php" style="color: var(--text-color); text-decoration: none; font-size: 1.2rem;">
                    <i class="fas fa-arrow-right"></i>
                </a>
                <h2 style="margin-right: 12px;">فروشگاه</h2>
            </div>
            <div class="wallet-badge">
                <span class="text-success">
                    <i class="fas fa-wallet"></i> <?php echo number_format($user['balance']); ?> تومان
                </span>
            </div>
        </div>

        <?php if ($selected_cat_id && $selected_server_id): ?>
            <!-- Plans List for Selected Category and Server -->
            <div style="margin-bottom: 16px;">
                <?php
                // Check server count for back link logic
                $stmt_count = pdo()->prepare("SELECT COUNT(DISTINCT s.id) FROM servers s JOIN plans p ON s.id = p.server_id WHERE p.category_id = ? AND p.status = 'active' AND s.status = 'active'");
                $stmt_count->execute([$selected_cat_id]);
                $server_count = $stmt_count->fetchColumn();

                $back_link = ($server_count == 1) ? 'shop.php' : 'shop.php?cat_id=' . $selected_cat_id;
                $back_text = ($server_count == 1) ? 'بازگشت به دسته‌بندی‌ها' : 'بازگشت به سرورها';
                ?>
                <a href="<?php echo $back_link; ?>"
                    style="text-decoration: none; color: var(--primary-color); font-size: 0.9rem;">
                    <i class="fas fa-chevron-right"></i> <?php echo $back_text; ?>
                </a>
            </div>

            <?php
            // Get plans for this category and server
            $all_plans = getPlans();
            $plans = array_filter($all_plans, function ($p) use ($selected_cat_id, $selected_server_id) {
                return $p['category_id'] == $selected_cat_id
                    && $p['server_id'] == $selected_server_id
                    && $p['status'] == 'active'
                    && $p['is_test_plan'] == 0;
            });

            // Get server info
            $server = getServerById($selected_server_id);
            ?>

            <?php if ($server): ?>
                <div class="card"
                    style="margin-bottom: 16px; background: linear-gradient(135deg, var(--primary-color) 0%, #667eea 100%); color: white;">
                    <div style="padding: 16px;">
                        <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 4px;">سرور انتخاب شده:</div>
                        <div style="font-weight: bold; font-size: 1.1rem;">
                            <i class="fas fa-server"></i> <?php echo htmlspecialchars($server['name']); ?>
                        </div>
                        <?php if (!empty($server['location'])): ?>
                            <div style="font-size: 0.85rem; opacity: 0.9; margin-top: 4px;">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($server['location']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($plans)): ?>
                <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p>هیچ پلنی برای این سرور موجود نیست.</p>
                </div>
            <?php else: ?>
                <?php foreach ($plans as $plan): ?>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><?php echo htmlspecialchars($plan['name']); ?></div>
                            <div class="text-primary" style="font-weight: bold;">
                                <?php echo number_format($plan['price']); ?> تومان
                            </div>
                        </div>
                        <div style="margin-bottom: 16px; font-size: 0.9rem; color: var(--text-muted);">
                            <div style="margin-bottom: 4px;">
                                <i class="fas fa-hdd"></i> حجم: <?php echo $plan['volume_gb']; ?> گیگابایت
                            </div>
                            <div style="margin-bottom: 4px;">
                                <i class="fas fa-clock"></i> مدت: <?php echo $plan['duration_days']; ?> روز
                            </div>
                            <?php if ($plan['description']): ?>
                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border-color);">
                                    <?php echo htmlspecialchars($plan['description']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-primary"
                            onclick="openBuyModal(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['name'], ENT_QUOTES); ?>', <?php echo $plan['price']; ?>)">
                            <i class="fas fa-shopping-cart"></i> خرید
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php elseif ($selected_cat_id): ?>
            <!-- Servers List for Selected Category -->
            <div style="margin-bottom: 16px;">
                <a href="shop.php" style="text-decoration: none; color: var(--primary-color); font-size: 0.9rem;">
                    <i class="fas fa-chevron-right"></i> بازگشت به دسته‌بندی‌ها
                </a>
            </div>

            <?php
            // Get active servers
            $servers = getServers();
            $active_servers = array_filter($servers, function ($s) {
                return ($s['status'] ?? 'inactive') === 'active';
            });
            ?>

            <?php if (empty($active_servers)): ?>
                <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <i class="fas fa-server" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p>هیچ سروری در حال حاضر فعال نیست.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                    <?php foreach ($active_servers as $server): ?>
                        <a href="shop.php?cat_id=<?php echo $selected_cat_id; ?>&server_id=<?php echo $server['id']; ?>"
                            class="card"
                            style="text-decoration: none; color: var(--text-color); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; margin-bottom: 4px;">
                                    <i class="fas fa-server text-primary"></i>
                                    <?php echo htmlspecialchars($server['name']); ?>
                                </div>
                                <?php if (!empty($server['location'])): ?>
                                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($server['location']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <i class="fas fa-chevron-left text-muted"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Categories List -->
            <?php if (empty($categories)): ?>
                <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <i class="fas fa-th-large" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p>هیچ دسته‌بندی فعالی وجود ندارد.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
                    <?php foreach ($categories as $cat): ?>
                        <a href="shop.php?cat_id=<?php echo $cat['id']; ?>" class="card"
                            style="text-decoration: none; color: var(--text-color); display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600;">
                                <i class="fas fa-folder text-primary"></i>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </span>
                            <i class="fas fa-chevron-left text-muted"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Buy Modal -->
    <div id="buy-modal" class="loading-overlay" style="display: none; background: rgba(0,0,0,0.5);">
        <div class="card" style="width: 90%; max-width: 400px;">
            <div class="card-header">
                <div class="card-title">تایید خرید</div>
                <i class="fas fa-times" onclick="closeBuyModal()" style="cursor: pointer;"></i>
            </div>
            <div style="margin-bottom: 16px;">
                <p id="modal-plan-name" style="font-weight: bold; margin-bottom: 8px;"></p>
                <p id="modal-plan-price" style="color: var(--primary-color); margin-bottom: 16px;"></p>

                <label style="display: block; margin-bottom: 8px; font-size: 0.9rem;">نام سرویس (دلخواه):</label>
                <input type="text" id="custom-name" class="form-control" placeholder="مثلا: گوشی من"
                    style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: var(--radius); font-family: inherit;">
            </div>
            <button class="btn btn-primary" onclick="confirmBuy()">
                <i class="fas fa-check"></i> تایید و پرداخت
            </button>
        </div>
    </div>

    <!-- Insufficient Balance Modal -->
    <div id="charge-modal" class="loading-overlay" style="display: none; background: rgba(0,0,0,0.5);">
        <div class="card" style="width: 90%; max-width: 400px;">
            <div class="card-header">
                <div class="card-title">موجودی ناکافی</div>
                <i class="fas fa-times" onclick="closeChargeModal()" style="cursor: pointer;"></i>
            </div>
            <div style="margin-bottom: 16px;">
                <p style="margin-bottom: 12px;">موجودی شما برای خرید این پلن کافی نیست.</p>
                <div
                    style="background: var(--bg-secondary); padding: 12px; border-radius: var(--radius); margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>موجودی فعلی:</span>
                        <span id="current-balance" style="font-weight: bold;"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>مبلغ مورد نیاز:</span>
                        <span id="required-balance" style="font-weight: bold; color: var(--primary-color);"></span>
                    </div>
                </div>
            </div>
            <a href="wallet.php" class="btn btn-success"
                style="text-decoration: none; display: block; text-align: center;">
                <i class="fas fa-wallet"></i> شارژ کیف پول
            </a>
        </div>
    </div>

    <!-- Bottom Nav -->
    <div class="bottom-nav">
        <a href="index.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>خانه</span>
        </a>
        <a href="services.php" class="nav-item">
            <i class="fas fa-cube"></i>
            <span>سرویس‌ها</span>
        </a>
        <a href="shop.php" class="nav-item active">
            <i class="fas fa-store"></i>
            <span>فروشگاه</span>
        </a>
        <a href="wallet.php" class="nav-item">
            <i class="fas fa-wallet"></i>
            <span>کیف پول</span>
        </a>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        const tg = window.Telegram.WebApp;
        tg.ready();
        tg.expand();

        // Theme
        if (tg.colorScheme === 'dark') {
            document.body.classList.add('dark-theme');
        }

        let selectedPlanId = null;

        function openBuyModal(planId, planName, planPrice) {
            selectedPlanId = planId;
            document.getElementById('modal-plan-name').textContent = planName;
            document.getElementById('modal-plan-price').textContent = new Intl.NumberFormat('fa-IR').format(planPrice) + ' تومان';
            document.getElementById('buy-modal').style.display = 'flex';
        }

        function closeBuyModal() {
            document.getElementById('buy-modal').style.display = 'none';
            selectedPlanId = null;
        }

        function closeChargeModal() {
            document.getElementById('charge-modal').style.display = 'none';
        }

        function confirmBuy() {
            if (!selectedPlanId) return;

            const customName = document.getElementById('custom-name').value;
            const loading = document.getElementById('loading');
            loading.style.display = 'flex';

            fetch('shop.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=buy&plan_id=${selectedPlanId}&custom_name=${encodeURIComponent(customName)}`
            })
                .then(response => response.json())
                .then(data => {
                    loading.style.display = 'none';
                    if (data.success) {
                        tg.showAlert(data.message, function () {
                            window.location.href = 'services.php';
                        });
                    } else {
                        if (data.need_charge) {
                            closeBuyModal();
                            document.getElementById('current-balance').textContent = new Intl.NumberFormat('fa-IR').format(data.current) + ' تومان';
                            document.getElementById('required-balance').textContent = new Intl.NumberFormat('fa-IR').format(data.required) + ' تومان';
                            document.getElementById('charge-modal').style.display = 'flex';
                        } else {
                            tg.showAlert(data.message);
                        }
                    }
                })
                .catch(error => {
                    loading.style.display = 'none';
                    tg.showAlert('خطا در برقراری ارتباط با سرور');
                });
        }
    </script>
</body>

</html>