<?php
$pageTitle = 'تفاصيل الطلب';
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/auth/login.php');
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();

$order_id = (int)($_GET['id'] ?? 0);

if (!$order_id) {
    redirect(SITE_URL . '/admin/admin.php');
}

// Get order details
$stmt = $db->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email,
           oa.first_name, oa.last_name, oa.address, oa.city, oa.state, oa.zip,
           p.name as product_name, p.price, p.mainImage
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN order_addresses oa ON o.address_id = oa.id
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect(SITE_URL . '/admin/admin.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1000px; margin: 40px auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: var(--primary-color); margin: 0;">
            تفاصيل الطلب #<?php echo $order['id']; ?>
        </h1>
        <a href="<?php echo SITE_URL; ?>/admin/admin.php" style="background: #6b7280; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">
            العودة للوحة التحكم
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Order Information -->
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 20px; color: var(--text-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
                معلومات الطلب
            </h2>

            <div style="display: grid; gap: 15px;">
                <div style="display: flex; justify-content: space-between;">
                    <strong>رقم الطلب:</strong>
                    <span>#<?php echo $order['id']; ?></span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <strong>تاريخ الطلب:</strong>
                    <span><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <strong>الحالة:</strong>
                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; <?php
                        switch($order['status']) {
                            case 'PENDING':
                                echo 'background: #fef3c7; color: #92400e;';
                                break;
                            case 'COMPLETED':
                                echo 'background: #d1fae5; color: #065f46;';
                                break;
                            case 'CANCELLED':
                                echo 'background: #fee2e2; color: #991b1b;';
                                break;
                        }
                    ?>">
                        <?php
                        switch($order['status']) {
                            case 'PENDING':
                                echo 'في الانتظار';
                                break;
                            case 'COMPLETED':
                                echo 'مكتمل';
                                break;
                            case 'CANCELLED':
                                echo 'ملغي';
                                break;
                        }
                        ?>
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <strong>طريقة الدفع:</strong>
                    <span><?php echo htmlspecialchars($order['payment_method']); ?></span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <strong>الكمية:</strong>
                    <span><?php echo $order['quantity']; ?></span>
                </div>

                <?php if ($order['product_name']): ?>
                <div style="display: flex; justify-content: space-between;">
                    <strong>إجمالي السعر:</strong>
                    <span style="font-weight: bold; color: var(--primary-color);">
                        <?php echo number_format($order['quantity'] * $order['price'], 2); ?> د.ع
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customer Information -->
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 20px; color: var(--text-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
                معلومات العميل
            </h2>

            <div style="display: grid; gap: 15px;">
                <div style="display: flex; justify-content: space-between;">
                    <strong>الاسم:</strong>
                    <span><?php echo htmlspecialchars($order['user_name']); ?></span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <strong>البريد الإلكتروني:</strong>
                    <span><?php echo htmlspecialchars($order['user_email']); ?></span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <strong>اسم المستلم:</strong>
                    <span><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></span>
                </div>
            </div>
        </div>

        <!-- Shipping Address -->
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); grid-column: 1 / -1;">
            <h2 style="margin-bottom: 20px; color: var(--text-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
                عنوان الشحن
            </h2>

            <div style="background: #f9fafb; padding: 20px; border-radius: 6px;">
                <p style="margin: 0; line-height: 1.6; color: var(--text-color);">
                    <?php echo htmlspecialchars($order['address']); ?><br>
                    <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> <?php echo htmlspecialchars($order['zip']); ?>
                </p>
            </div>
        </div>

        <!-- Product Information -->
        <?php if ($order['product_name']): ?>
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); grid-column: 1 / -1;">
            <h2 style="margin-bottom: 20px; color: var(--text-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
                معلومات المنتج
            </h2>

            <div style="display: flex; gap: 20px; align-items: center;">
                <img src="<?php echo getProductImage($order['mainImage'], $order['product_name']); ?>"
                     alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                     style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb; background: #f9fafb;"
                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.svg'"
                     loading="lazy">

                <div style="flex: 1;">
                    <h3 style="margin: 0 0 10px 0; color: var(--text-color);">
                        <?php echo htmlspecialchars($order['product_name']); ?>
                    </h3>
                    <p style="margin: 0; color: #6b7280;">
                        الكمية: <?php echo $order['quantity']; ?> × <?php echo number_format($order['price'], 2); ?> د.ع
                    </p>
                    <p style="margin: 5px 0 0 0; font-weight: bold; color: var(--primary-color);">
                        المجموع: <?php echo number_format($order['quantity'] * $order['price'], 2); ?> د.ع
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Actions -->
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); grid-column: 1 / -1;">
            <h2 style="margin-bottom: 20px; color: var(--text-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
                إجراءات الطلب
            </h2>

            <form method="POST" action="<?php echo SITE_URL; ?>/admin.php" style="display: inline;">
                <input type="hidden" name="action" value="update_order_status">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">

                <div style="display: flex; gap: 10px; align-items: center;">
                    <label for="status" style="font-weight: 600; color: var(--text-color);">تغيير الحالة:</label>
                    <select name="status" id="status" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px;">
                        <option value="PENDING" <?php echo $order['status'] === 'PENDING' ? 'selected' : ''; ?>>في الانتظار</option>
                        <option value="COMPLETED" <?php echo $order['status'] === 'COMPLETED' ? 'selected' : ''; ?>>مكتمل</option>
                        <option value="CANCELLED" <?php echo $order['status'] === 'CANCELLED' ? 'selected' : ''; ?>>ملغي</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
