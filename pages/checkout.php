<?php
$pageTitle = 'إتمام الطلب';
require_once __DIR__ . '/../config/config.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
$db = getDB();
$userId = $_SESSION['user_id'];

// Get cart items
$stmt = $db->prepare("
    SELECT c.*, p.name, p.price, p.id as product_id
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    redirect(SITE_URL . '/pages/cart.php');
}

$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
$shipping = $total >= 250000 ? 0 : 5000; // شحن مجاني للطلبات فوق 250,000 د.ع
$grandTotal = $total + $shipping;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $zip = sanitize($_POST['zip'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($address) || empty($city) || empty($state) || empty($zip)) {
        $error = 'يرجى ملء جميع الحقول';
    } else {
        try {
            $db->beginTransaction();

            // Create order address
            $stmt = $db->prepare("INSERT INTO order_addresses (user_id, first_name, last_name, address, city, state, zip) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $firstName, $lastName, $address, $city, $state, $zip]);
            $addressId = $db->lastInsertId();

            // Create orders for each cart item
            $orderIds = [];
            foreach ($cartItems as $item) {
                // Generate tracking number
                $trackingNumber = 'TRK' . strtoupper(uniqid());
                
                $stmt = $db->prepare("INSERT INTO orders (user_id, product_id, address_id, quantity, status, payment_method, tracking_number, shipping_status) VALUES (?, ?, ?, ?, 'PENDING', 'Cash', ?, 'pending')");
                $stmt->execute([$userId, $item['product_id'], $addressId, $item['quantity'], $trackingNumber]);
                $orderId = $db->lastInsertId();
                $orderIds[] = $orderId;
                
                // Create notification for order creation
                $stmt = $db->prepare("INSERT INTO notifications (user_id, order_id, title, message, type) VALUES (?, ?, ?, ?, 'order_created')");
                $productName = $item['name'] ?? 'منتج';
                $message = "تم إنشاء طلبك بنجاح! رقم التتبع: {$trackingNumber}";
                $stmt->execute([$userId, $orderId, "تم إنشاء طلب جديد", $message]);
            }

            // Clear cart
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);

            $db->commit();
            $success = 'تم إنشاء الطلب بنجاح! سيتم إرسال إشعار عند شحن المنتج.';
            
            // Redirect after 2 seconds
            header('Refresh: 2; url=' . SITE_URL . '/pages/orders.php');
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'حدث خطأ أثناء إنشاء الطلب: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <h1 style="text-align: center; margin-bottom: 40px; font-size: 36px;">إتمام الطلب</h1>

    <?php if ($error): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($success); ?>
            <p>سيتم توجيهك إلى صفحة الطلبات...</p>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 40px;">
        <!-- Order Form -->
        <div>
            <h2 style="margin-bottom: 20px;">معلومات الشحن</h2>
            <form method="POST" class="form-container" style="max-width: 100%;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="first_name">الاسم الأول *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">اسم العائلة *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">العنوان *</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="city">المدينة *</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    <div class="form-group">
                        <label for="state">المنطقة *</label>
                        <input type="text" id="state" name="state" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="zip">الرمز البريدي *</label>
                    <input type="text" id="zip" name="zip" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 18px;">
                    إتمام الطلب
                </button>
            </form>
        </div>

        <!-- Order Summary -->
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow-lg); height: fit-content; position: sticky; top: 100px;">
            <h2 style="margin-bottom: 20px;">ملخص الطلب</h2>
            
            <div style="margin-bottom: 20px;">
                <?php foreach ($cartItems as $item): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                        <div>
                            <p style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></p>
                            <p style="color: var(--text-light); font-size: 14px;">
                                الكمية: <?php echo $item['quantity']; ?>
                            </p>
                        </div>
                        <p><?php echo number_format($item['price'] * $item['quantity'], 2); ?> د.ع</p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="border-top: 2px solid var(--border-color); padding-top: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>المجموع الفرعي:</span>
                    <span><?php echo number_format($total, 2); ?> د.ع</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>الشحن:</span>
                    <span><?php echo $shipping == 0 ? 'مجاني' : number_format($shipping, 2) . ' د.ع'; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--border-color);">
                    <span>الإجمالي:</span>
                    <span style="color: var(--primary-color);"><?php echo number_format($grandTotal, 2); ?> د.ع</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

