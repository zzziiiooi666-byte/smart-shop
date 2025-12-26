<?php
$pageTitle = 'سلة التسوق';
require_once __DIR__ . '/../config/config.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
$db = getDB();

$userId = $_SESSION['user_id'];

// Get cart items
$stmt = $db->prepare("
    SELECT c.*, p.name, p.price, p.mainImage, p.quantity as stock_quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1 style="text-align: center; margin: 40px 0; font-size: 36px;">سلة التسوق</h1>

    <?php if (empty($cartItems)): ?>
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-shopping-cart" style="font-size: 64px; color: #d1d5db; margin-bottom: 20px;"></i>
            <h2 style="color: #6b7280; margin-bottom: 20px;">السلة فارغة</h2>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-primary">تسوق الآن</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 30px; margin-bottom: 40px;">
            <!-- Cart Items -->
            <div>
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item" style="background: white; padding: 20px; border-radius: 12px; box-shadow: var(--shadow); margin-bottom: 20px; display: flex; gap: 20px;">
                        <img src="<?php echo getProductImage($item['mainImage'], $item['name']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; background: #f9fafb;"
                             onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.svg'"
                             loading="lazy"
                        <div style="flex: 1;">
                            <h3 style="margin-bottom: 10px;"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p style="color: var(--text-light); margin-bottom: 10px;">
                                السعر: <?php echo number_format($item['price'], 2); ?> د.ع
                            </p>
                            <?php if ($item['color']): ?>
                                <p style="color: var(--text-light);">اللون: <?php echo htmlspecialchars($item['color']); ?></p>
                            <?php endif; ?>
                            <?php if ($item['size']): ?>
                                <p style="color: var(--text-light);">المقاس: <?php echo htmlspecialchars($item['size']); ?></p>
                            <?php endif; ?>
                            <div style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
                                <label>الكمية:</label>
                                <input type="number" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="<?php echo $item['stock_quantity']; ?>"
                                       onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)"
                                       style="width: 80px; padding: 8px; border: 2px solid var(--border-color); border-radius: 6px;">
                                <button onclick="removeFromCart(<?php echo $item['id']; ?>)" 
                                        style="background: var(--error-color); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer;">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </div>
                        </div>
                        <div style="text-align: left;">
                            <p style="font-size: 20px; font-weight: 700; color: var(--primary-color);">
                                <?php echo number_format($item['price'] * $item['quantity'], 2); ?> د.ع
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow-lg); height: fit-content; position: sticky; top: 100px;">
                <h2 style="margin-bottom: 20px; font-size: 24px;">ملخص الطلب</h2>
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid var(--border-color);">
                    <span>المجموع الفرعي:</span>
                    <span><?php echo number_format($total, 2); ?> د.ع</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid var(--border-color);">
                    <span>الشحن:</span>
                    <span><?php echo $total >= 250000 ? 'مجاني' : '5,000 د.ع'; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 20px; font-weight: 700;">
                    <span>الإجمالي:</span>
                    <span style="color: var(--primary-color);">
                        <?php echo number_format($total + ($total >= 250000 ? 0 : 5000), 2); ?> د.ع
                    </span>
                </div>
                <a href="<?php echo SITE_URL; ?>/pages/checkout.php" class="btn btn-primary" style="width: 100%; text-align: center; text-decoration: none;">
                    إتمام الطلب
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '<?php echo SITE_URL; ?>';

function updateQuantity(cartId, quantity) {
    fetch(siteUrl + '/api/cart/update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({cart_id: cartId, quantity: parseInt(quantity)})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'حدث خطأ');
        }
    });
}

function removeFromCart(cartId) {
    if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
        fetch(siteUrl + '/api/cart/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({cart_id: cartId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'حدث خطأ');
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

