<?php
$pageTitle = 'الفئات';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$category = sanitize($_GET['cat'] ?? '');

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

if (!empty($category)) {
    // Get total count for pagination
    $countStmt = $db->prepare("
        SELECT COUNT(*) as total FROM products p
        JOIN categories c ON p.id = c.product_id
        WHERE c.name = ? AND p.quantity > 0
    ");
    $countStmt->execute([$category]);
    $totalProducts = $countStmt->fetch()['total'];
    $totalPages = ceil($totalProducts / $perPage);
    
    // Get products with pagination
    $stmt = $db->prepare("
        SELECT p.* FROM products p
        JOIN categories c ON p.id = c.product_id
        WHERE c.name = ? AND p.quantity > 0
        ORDER BY p.created_at DESC
        LIMIT " . intval($perPage) . " OFFSET " . intval($offset) . "
    ");
    $stmt->execute([$category]);
    $products = $stmt->fetchAll();
    $pageTitle = 'فئة: ' . $category;
} else {
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE quantity > 0");
    $countStmt->execute();
    $totalProducts = $countStmt->fetch()['total'];
    $totalPages = ceil($totalProducts / $perPage);
    
    // Get products with pagination
    $stmt = $db->prepare("SELECT * FROM products WHERE quantity > 0 ORDER BY created_at DESC LIMIT " . intval($perPage) . " OFFSET " . intval($offset));
    $stmt->execute();
    $products = $stmt->fetchAll();
}
?>

<div class="container">
    <h1 style="text-align: center; margin: 40px 0; font-size: 36px;">
        <?php echo !empty($category) ? 'فئة: ' . htmlspecialchars($category) : 'جميع الفئات'; ?>
    </h1>

    <?php if (empty($products)): ?>
        <p class="text-center" style="color: #6b7280; font-size: 18px; padding: 60px 20px;">
            لا توجد منتجات في هذه الفئة
        </p>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <a href="<?php echo SITE_URL; ?>/pages/product-details.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                        <div style="position: relative; height: 300px; overflow: hidden; background: #f9fafb;">
                            <img src="<?php echo getProductImage($product['mainImage'], $product['name']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image-primary product-image"
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.svg'"
                                 loading="lazy">
                            <?php if (!empty($product['otherImages'])): ?>
                                <?php $otherImages = json_decode($product['otherImages'], true); ?>
                                <?php if (is_array($otherImages) && !empty($otherImages[0])): ?>
                                    <img src="<?php echo getProductImage($otherImages[0], $product['name']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-image-secondary product-image"
                                         style="width: 100%; height: 100%; object-fit: cover;"
                                         onerror="this.onerror=null; this.style.display='none'"
                                         loading="lazy">
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-price"><?php echo number_format($product['price'], 2); ?> د.ع</p>
                        </div>
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <button class="add-to-cart-btn" 
                                onclick="addToCart(<?php echo $product['id']; ?>)">
                            أضف إلى السلة
                        </button>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="add-to-cart-btn" style="text-decoration: none; display: block; text-align: center;">
                            تسجيل الدخول للشراء
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 10px; margin: 40px 0;">
                <?php if ($page > 1): ?>
                    <a href="?cat=<?php echo urlencode($category ?? ''); ?>&page=<?php echo $page - 1; ?>" 
                       class="btn btn-primary" style="text-decoration: none; padding: 10px 20px;">
                        السابق
                    </a>
                <?php endif; ?>
                
                <span style="padding: 10px 20px; color: var(--text-color);">
                    صفحة <?php echo $page; ?> من <?php echo $totalPages; ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?cat=<?php echo urlencode($category ?? ''); ?>&page=<?php echo $page + 1; ?>" 
                       class="btn btn-primary" style="text-decoration: none; padding: 10px 20px;">
                        التالي
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

