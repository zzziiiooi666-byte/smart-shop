<?php
$pageTitle = 'الرئيسية';
require_once __DIR__ . '/../includes/header.php';

// Get products from database
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Get latest products with limit (زيادة العدد لعرض المزيد من الصور)
$stmt = $db->prepare("SELECT * FROM products WHERE quantity > 0 ORDER BY created_at DESC LIMIT 20");
$stmt->execute();
$products = $stmt->fetchAll();

// Create category_list table if it doesn't exist and insert default categories
try {
    $db->exec("CREATE TABLE IF NOT EXISTS category_list (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        icon VARCHAR(100) NOT NULL,
        gradient_start VARCHAR(7) NOT NULL,
        gradient_end VARCHAR(7) NOT NULL,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Check if table is empty and insert default categories
    $stmt = $db->query("SELECT COUNT(*) as count FROM category_list");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        $defaultCategories = [
            ['ملابس-رجالية', 'fa-tshirt', '#667eea', '#764ba2', 1],
            ['ملابس-نسائية', 'fa-female', '#f093fb', '#f5576c', 2],
            ['أحذية', 'fa-shoe-prints', '#4facfe', '#00f2fe', 3],
            ['إلكترونيات', 'fa-mobile-alt', '#43e97b', '#38f9d7', 4],
            ['أجهزة-منزلية', 'fa-home', '#fa709a', '#fee140', 5],
            ['أثاث', 'fa-couch', '#8B4513', '#A0522D', 6],
            ['مستحضرات-تجميل', 'fa-spa', '#ff9a9e', '#fecfef', 7],
            ['عطور', 'fa-wind', '#ffecd2', '#fcb69f', 8],
            ['ألعاب', 'fa-gamepad', '#667eea', '#764ba2', 9],
            ['كتب', 'fa-book', '#f093fb', '#f5576c', 10],
            ['رياضة', 'fa-basketball-ball', '#4facfe', '#00f2fe', 11],
            ['صحة-وتجميل', 'fa-heartbeat', '#43e97b', '#38f9d7', 12],
            ['أدوات-منزلية', 'fa-tools', '#fa709a', '#fee140', 13],
            ['سيارات', 'fa-car', '#a8edea', '#fed6e3', 14],
            ['هواتف', 'fa-mobile-alt', '#ff9a9e', '#fecfef', 15]
        ];
        
        $stmt = $db->prepare("INSERT INTO category_list (name, icon, gradient_start, gradient_end, display_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($defaultCategories as $cat) {
            try {
                $stmt->execute($cat);
            } catch (PDOException $e) {
                // Ignore duplicate key errors
            }
        }
    }
} catch (PDOException $e) {
    // Table might already exist, continue
}

// Get categories from database
try {
    $stmt = $db->prepare("SELECT * FROM category_list ORDER BY display_order ASC, name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // If table doesn't exist, use empty array
    $categories = [];
}
?>

<!-- Hero Section -->
<section class="hero" style="position: relative; overflow: hidden;">
    <div class="container" style="position: relative; z-index: 2;">
        <div style="display: flex; align-items: center; justify-content: center; gap: 30px; flex-wrap: wrap;">
            <!-- Avatar decoration left -->
            <div style="flex-shrink: 0;">
                <img src="<?php echo getImageUrl(SITE_URL . '/assets/images/avatar-1.jpg'); ?>" 
                     alt="Avatar" 
                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3); box-shadow: 0 8px 20px rgba(0,0,0,0.2); animation: float 3s ease-in-out infinite;"
                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.svg'">
            </div>
            
            <!-- Main content -->
            <div style="text-align: center; flex: 1; min-width: 300px;">
                <h1>مرحباً بك في Shop Smart</h1>
                <p>اكتشف أحدث المنتجات والعروض الحصرية</p>
                <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-primary" style="margin-top: 20px;">تسوق الآن</a>
            </div>
            
            <!-- Avatar decoration right -->
            <div style="flex-shrink: 0;">
                <img src="<?php echo getImageUrl(SITE_URL . '/assets/images/avatar-2.jpg'); ?>" 
                     alt="Avatar" 
                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3); box-shadow: 0 8px 20px rgba(0,0,0,0.2); animation: float 3s ease-in-out infinite; animation-delay: 1.5s;"
                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.svg'">
            </div>
        </div>
    </div>
    
    <style>
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        
        @media (max-width: 768px) {
            .hero img {
                width: 80px !important;
                height: 80px !important;
            }
        }
    </style>
</section>

<!-- Categories Section -->
<section class="container mb-20">
    <h2 class="text-center mb-20" style="font-size: 32px; font-weight: 700;">
        الفئات
    </h2>
    <div class="categories-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $category): ?>
                <a href="<?php echo SITE_URL; ?>/pages/category.php?cat=<?php echo urlencode($category['name']); ?>" 
                   class="category-card" 
                   style="text-decoration: none; color: inherit;">
                    <div style="background: linear-gradient(135deg, <?php echo htmlspecialchars($category['gradient_start']); ?> 0%, <?php echo htmlspecialchars($category['gradient_end']); ?> 100%); padding: 30px; border-radius: 12px; text-align: center; color: white; height: 140px; display: flex; flex-direction: column; justify-content: center;">
                        <i class="fas <?php echo htmlspecialchars($category['icon']); ?>" style="font-size: 36px; margin-bottom: 10px;"></i>
                        <h4 style="font-size: 14px; margin: 0; font-weight: 600;"><?php echo htmlspecialchars($category['name']); ?></h4>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Fallback: Default categories if table doesn't exist -->
            <p style="text-align: center; color: #6b7280; grid-column: 1 / -1;">
                لا توجد فئات متاحة. يرجى إضافة فئات من لوحة التحكم.
            </p>
        <?php endif; ?>
    </div>
</section>

<style>
/* تأثيرات hover على الفئات */
.category-card {
    transition: all 0.3s ease;
    display: block;
}

.category-card:hover {
    transform: translateY(-8px) scale(1.05);
    filter: brightness(1.1);
}

.category-card:hover div {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    transform: scale(1.05);
}

.category-card div {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.category-card div::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(-100%);
    transition: transform 0.4s ease;
}

.category-card:hover div::before {
    transform: translateX(0);
}

.category-card:hover i {
    transform: scale(1.2) rotate(5deg);
    animation: bounce 0.6s ease;
}

.category-card i {
    transition: all 0.3s ease;
    display: inline-block;
}

.category-card:hover h4 {
    transform: translateY(-3px);
    font-weight: 700;
}

.category-card h4 {
    transition: all 0.3s ease;
}

@keyframes bounce {
    0%, 100% {
        transform: scale(1.2) rotate(5deg) translateY(0);
    }
    50% {
        transform: scale(1.2) rotate(5deg) translateY(-8px);
    }
}

/* تأثير إضافي للظل */
.category-card div {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* تأثير على الأيقونة */
.category-card:hover div i {
    text-shadow: 0 0 20px rgba(255, 255, 255, 0.8);
}

/* تأثير على النص */
.category-card:hover div h4 {
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    letter-spacing: 1px;
}
</style>

<!-- Products Section -->
<section class="container">
    <h2 class="text-center mb-20" style="font-size: 32px; font-weight: 700;">
        منتجات <span style="color: #4f46e5;">جديدة</span>
    </h2>
    <div class="products-grid">
        <?php if (empty($products)): ?>
            <p class="text-center" style="grid-column: 1/-1; color: #6b7280; font-size: 18px;">
                لا توجد منتجات متاحة حالياً
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
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

