<?php
$pageTitle = 'طلباتي';
require_once __DIR__ . '/../config/config.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
$db = getDB();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT o.*, p.name as product_name, p.mainImage, oa.first_name, oa.last_name, oa.address, oa.city, oa.state, oa.zip
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    JOIN order_addresses oa ON o.address_id = oa.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <h1 style="text-align: center; margin-bottom: 40px; font-size: 36px;">طلباتي</h1>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-box-open" style="font-size: 64px; color: #d1d5db; margin-bottom: 20px;"></i>
            <h2 style="color: #6b7280; margin-bottom: 20px;">لا توجد طلبات</h2>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-primary">تسوق الآن</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($orders as $order): ?>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
                    <div style="display: grid; grid-template-columns: 150px 1fr auto; gap: 20px; align-items: start;">
                        <?php if ($order['product_name']): ?>
                            <img src="<?php echo htmlspecialchars($order['mainImage']); ?>" 
                                 alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                 style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px;"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.svg'"
                        <?php else: ?>
                            <div style="width: 150px; height: 150px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-box" style="font-size: 48px; color: #9ca3af;"></i>
                            </div>
                        <?php endif; ?>

                        <div>
                            <?php if ($order['product_name']): ?>
                                <h3 style="margin-bottom: 10px; font-size: 20px;"><?php echo htmlspecialchars($order['product_name']); ?></h3>
                            <?php endif; ?>
                            <p style="color: var(--text-light); margin-bottom: 5px;">
                                <strong>الكمية:</strong> <?php echo $order['quantity']; ?>
                            </p>
                            <p style="color: var(--text-light); margin-bottom: 5px;">
                                <strong>الحالة:</strong> 
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
                            <?php if (!empty($order['tracking_number'])): ?>
                                <p style="color: var(--text-light); margin-bottom: 5px;">
                                    <strong>رقم التتبع:</strong> 
                                    <a href="<?php echo SITE_URL; ?>/pages/track-order.php?tracking=<?php echo htmlspecialchars($order['tracking_number']); ?>" 
                                       style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                        <?php echo htmlspecialchars($order['tracking_number']); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <p style="color: var(--text-light); margin-bottom: 5px;">
                                <strong>تاريخ الطلب:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?>
                            </p>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                                <p style="font-size: 14px; color: var(--text-light);"><strong>عنوان الشحن:</strong></p>
                                <p style="font-size: 14px;">
                                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br>
                                    <?php echo htmlspecialchars($order['address']); ?><br>
                                    <?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['zip']); ?>
                                </p>
                            </div>
                        </div>

                        <div style="text-align: left;">
                            <p style="font-size: 24px; font-weight: 700; color: var(--primary-color);">
                                #<?php echo $order['id']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

