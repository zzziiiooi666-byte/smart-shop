<?php
$pageTitle = 'تتبع الطلب';
require_once __DIR__ . '/../config/config.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
$db = getDB();
$userId = $_SESSION['user_id'];

$trackingNumber = $_GET['tracking'] ?? '';

if (empty($trackingNumber)) {
    redirect(SITE_URL . '/pages/orders.php');
}

// الحصول على معلومات الطلب
$stmt = $db->prepare("
    SELECT o.*, p.name as product_name, p.mainImage, 
           oa.first_name, oa.last_name, oa.address, oa.city, oa.state, oa.zip
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    JOIN order_addresses oa ON o.address_id = oa.id
    WHERE o.tracking_number = ? AND o.user_id = ?
");
$stmt->execute([$trackingNumber, $userId]);
$order = $stmt->fetch();

if (!$order) {
    redirect(SITE_URL . '/pages/orders.php');
}

// تحديد حالة الشحن بالعربية
$shippingStatuses = [
    'pending' => ['name' => 'قيد الانتظار', 'icon' => 'fa-clock', 'color' => '#f59e0b'],
    'processing' => ['name' => 'قيد المعالجة', 'icon' => 'fa-cog', 'color' => '#3b82f6'],
    'shipped' => ['name' => 'تم الشحن', 'icon' => 'fa-shipping-fast', 'color' => '#8b5cf6'],
    'delivered' => ['name' => 'تم التسليم', 'icon' => 'fa-check-circle', 'color' => '#10b981'],
    'cancelled' => ['name' => 'ملغي', 'icon' => 'fa-times-circle', 'color' => '#ef4444']
];

$currentStatus = $shippingStatuses[$order['shipping_status']] ?? $shippingStatuses['pending'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <h1 style="text-align: center; margin-bottom: 40px; font-size: 36px;">تتبع الطلب</h1>

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: var(--shadow-lg); margin-bottom: 30px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background: <?php echo $currentStatus['color']; ?>; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                <i class="fas <?php echo $currentStatus['icon']; ?>" style="font-size: 36px; color: white;"></i>
            </div>
            <h2 style="margin: 0; color: <?php echo $currentStatus['color']; ?>;">
                <?php echo $currentStatus['name']; ?>
            </h2>
            <p style="color: #6b7280; margin-top: 10px;">
                رقم التتبع: <strong><?php echo htmlspecialchars($order['tracking_number']); ?></strong>
            </p>
        </div>

        <!-- شريط التقدم -->
        <div style="position: relative; margin: 40px 0;">
            <?php
            $steps = [
                ['status' => 'pending', 'name' => 'قيد الانتظار'],
                ['status' => 'processing', 'name' => 'قيد المعالجة'],
                ['status' => 'shipped', 'name' => 'تم الشحن'],
                ['status' => 'delivered', 'name' => 'تم التسليم']
            ];
            
            $statusOrder = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3];
            $currentStep = $statusOrder[$order['shipping_status']] ?? 0;
            ?>
            
            <div style="display: flex; justify-content: space-between; position: relative;">
                <!-- خط التقدم -->
                <div style="position: absolute; top: 20px; right: 0; left: 0; height: 3px; background: #e5e7eb; z-index: 0;"></div>
                <div style="position: absolute; top: 20px; right: 0; width: <?php echo ($currentStep / 3) * 100; ?>%; height: 3px; background: var(--primary-color); z-index: 1; transition: width 0.5s;"></div>
                
                <?php foreach ($steps as $index => $step): ?>
                    <?php
                    $isActive = $index <= $currentStep;
                    $isCurrent = $index == $currentStep;
                    ?>
                    <div style="position: relative; z-index: 2; text-align: center; flex: 1;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $isActive ? 'var(--primary-color)' : '#e5e7eb'; ?>; 
                            color: white; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px; font-weight: bold;">
                            <?php if ($isActive && !$isCurrent): ?>
                                <i class="fas fa-check" style="font-size: 18px;"></i>
                            <?php else: ?>
                                <?php echo $index + 1; ?>
                            <?php endif; ?>
                        </div>
                        <p style="font-size: 14px; color: <?php echo $isActive ? 'var(--text-color)' : '#9ca3af'; ?>; margin: 0;">
                            <?php echo $step['name']; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- معلومات الطلب -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
            <h3 style="margin-bottom: 20px; font-size: 20px;">معلومات المنتج</h3>
            <?php if ($order['product_name']): ?>
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <img src="<?php echo htmlspecialchars($order['mainImage']); ?>" 
                         alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;"
                         onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.svg'">
                    <div>
                        <p style="font-weight: 600; margin-bottom: 5px;"><?php echo htmlspecialchars($order['product_name']); ?></p>
                        <p style="color: #6b7280; font-size: 14px;">الكمية: <?php echo $order['quantity']; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            <div style="padding-top: 15px; border-top: 1px solid var(--border-color);">
                <p style="margin-bottom: 5px;"><strong>رقم الطلب:</strong> #<?php echo $order['id']; ?></p>
                <p style="margin-bottom: 5px;"><strong>تاريخ الطلب:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></p>
                <p style="margin-bottom: 0;"><strong>حالة الطلب:</strong> 
                    <span style="padding: 5px 15px; border-radius: 20px; font-size: 14px; 
                        background: <?php 
                            echo $order['status'] === 'COMPLETED' ? '#d1fae5' : 
                                ($order['status'] === 'CANCELLED' ? '#fee2e2' : '#fef3c7'); 
                        ?>; 
                        color: <?php 
                            echo $order['status'] === 'COMPLETED' ? '#065f46' : 
                                ($order['status'] === 'CANCELLED' ? '#991b1b' : '#92400e'); 
                        ?>;">
                        <?php 
                            echo $order['status'] === 'COMPLETED' ? 'مكتمل' : 
                                ($order['status'] === 'CANCELLED' ? 'ملغي' : 'قيد الانتظار'); 
                        ?>
                    </span>
                </p>
            </div>
        </div>

        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
            <h3 style="margin-bottom: 20px; font-size: 20px;">عنوان الشحن</h3>
            <p style="margin-bottom: 5px;">
                <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
            </p>
            <p style="color: #6b7280; line-height: 1.8; margin-bottom: 0;">
                <?php echo htmlspecialchars($order['address']); ?><br>
                <?php echo htmlspecialchars($order['city'] . ', ' . $order['state']); ?><br>
                <?php echo htmlspecialchars($order['zip']); ?>
            </p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="<?php echo SITE_URL; ?>/pages/orders.php" class="btn btn-primary" style="text-decoration: none;">
            العودة إلى الطلبات
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

