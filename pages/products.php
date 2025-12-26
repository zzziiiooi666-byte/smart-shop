<?php
$pageTitle = 'المنتجات';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$search = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$query = "SELECT * FROM products WHERE quantity > 0";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($category)) {
    $query .= " AND id IN (SELECT product_id FROM categories WHERE name = ?)";
    $params[] = $category;
}

// Get total count
$countQuery = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalProducts = $countStmt->fetch()['total'];
$totalPages = ceil($totalProducts / $perPage);

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="container">
    <h1 style="text-align: center; margin: 40px 0; font-size: 36px;">جميع المنتجات</h1>

    <!-- Search and Filter -->
    <div style="margin-bottom: 30px; display: flex; gap: 15px; flex-wrap: wrap;">
        <form method="GET" style="flex: 1; min-width: 200px;">
            <input type="text" name="search" placeholder="ابحث عن منتج..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 6px;">
        </form>
        <select name="category" onchange="window.location.href='?category='+this.value" 
                style="padding: 12px; border: 2px solid var(--border-color); border-radius: 6px;">
            <option value="">جميع الفئات</option>
            <option value="man" <?php echo $category === 'man' ? 'selected' : ''; ?>>رجالي</option>
            <option value="woman" <?php echo $category === 'woman' ? 'selected' : ''; ?>>نسائي</option>
            <option value="children" <?php echo $category === 'children' ? 'selected' : ''; ?>>أطفال</option>
        </select>
    </div>

    <!-- Products Grid -->
    <div class="products-grid">
        <?php if (empty($products)): ?>
            <p class="text-center" style="grid-column: 1/-1; color: #6b7280; font-size: 18px;">
                لا توجد منتجات متاحة
            </p>
        <?php else: ?>
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
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin: 40px 0; flex-wrap: wrap;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?>" 
                   class="btn btn-outline">السابق</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?>" 
                   class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?>" 
                   class="btn btn-outline">التالي</a>
            <?php endif; ?>
        </div>
        <p style="text-align: center; color: #6b7280; margin-top: 20px;">
            عرض <?php echo count($products); ?> من <?php echo $totalProducts; ?> منتج (صفحة <?php echo $page; ?> من <?php echo $totalPages; ?>)
        </p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

