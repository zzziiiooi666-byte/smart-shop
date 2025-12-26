<?php
/**
 * Script to update products in database with images from assets/images
 * Adds products to empty categories using available images
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// Check if admin user exists
$stmt = $db->prepare("SELECT id FROM users WHERE isAdmin = 1 LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch();

if (!$admin) {
    die("❌ يجب إنشاء مستخدم admin أولاً");
}

$adminId = $admin['id'];
$siteUrl = SITE_URL;

// Get all categories and check which ones have products
$stmt = $db->prepare("
    SELECT DISTINCT c.name as category_name, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.product_id = p.id
    GROUP BY c.name
");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>تحديث المنتجات وإضافة الصور</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; direction: rtl; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    ul { list-style: none; padding: 0; }
    li { padding: 5px 0; }
</style>";

// Map categories to multiple images (each product gets a different image)
// Using avatar images and new image names
$categoryImages = [
    'ملابس-رجالية' => [
        $siteUrl . '/assets/images/product-1-1.jpg',
        $siteUrl . '/assets/images/product-1-2.jpg',
        $siteUrl . '/assets/images/product-2-1.jpg',
        $siteUrl . '/assets/images/product-2-2.jpg',
        $siteUrl . '/assets/images/product-4-1.jpg',
        $siteUrl . '/assets/images/product-4-2.jpg',
        $siteUrl . '/assets/images/product-5-1.jpg',
        $siteUrl . '/assets/images/product-5-2.jpg',
        $siteUrl . '/assets/images/avatar-1.jpg',
    ],
    'ملابس-نسائية' => [
        $siteUrl . '/assets/images/product-6-1.jpg',
        $siteUrl . '/assets/images/product-6-2.jpg',
        $siteUrl . '/assets/images/product-7-1.jpg',
        $siteUrl . '/assets/images/product-7-2.jpg',
        $siteUrl . '/assets/images/product-8-1.jpg',
        $siteUrl . '/assets/images/product-8-2.jpg',
        $siteUrl . '/assets/images/product-9-1.jpg',
        $siteUrl . '/assets/images/product-9-2.jpg',
        $siteUrl . '/assets/images/avatar-2.jpg',
    ],
    'أحذية' => [
        $siteUrl . '/assets/images/حذاء .jpg',
        $siteUrl . '/assets/images/product-3-1.jpg',
        $siteUrl . '/assets/images/product-3-2.jpg',
        $siteUrl . '/assets/images/product-10-1.jpg',
        $siteUrl . '/assets/images/product-10-2.jpg',
        $siteUrl . '/assets/images/product-11-1.jpg',
        $siteUrl . '/assets/images/product-11-2.jpg',
        $siteUrl . '/assets/images/product-12-1.jpg',
        $siteUrl . '/assets/images/product-12-2.jpg',
    ],
    'إلكترونيات' => [
        $siteUrl . '/assets/images/product-7-1.jpg',
        $siteUrl . '/assets/images/product-7-2.jpg',
        $siteUrl . '/assets/images/product-11-1.jpg',
        $siteUrl . '/assets/images/product-11-2.jpg',
        $siteUrl . '/assets/images/product-13-1.jpg',
        $siteUrl . '/assets/images/product-13-2.jpg',
        $siteUrl . '/assets/images/category-3.jpg',
        $siteUrl . '/assets/images/category-4.jpg',
        $siteUrl . '/assets/images/avatar-3.jpg',
    ],
    'أجهزة-منزلية' => [
        $siteUrl . '/assets/images/product-8-1.jpg',
        $siteUrl . '/assets/images/product-8-2.jpg',
        $siteUrl . '/assets/images/category-4.jpg',
        $siteUrl . '/assets/images/category-5.jpg',
        $siteUrl . '/assets/images/category-6.jpg',
        $siteUrl . '/assets/images/category-7.jpg',
        $siteUrl . '/assets/images/category-8.jpg',
        $siteUrl . '/assets/images/showcase-img-1.jpg',
        $siteUrl . '/assets/images/avatar-1.jpg',
    ],
    'أثاث' => [
        $siteUrl . '/assets/images/showcase-img-5.jpg',
        $siteUrl . '/assets/images/showcase-img-6.jpg',
        $siteUrl . '/assets/images/showcase-img-7.jpg',
        $siteUrl . '/assets/images/showcase-img-8.jpg',
        $siteUrl . '/assets/images/showcase-img-9.jpg',
        $siteUrl . '/assets/images/product-5-1.jpg',
        $siteUrl . '/assets/images/product-5-2.jpg',
        $siteUrl . '/assets/images/category-7.jpg',
        $siteUrl . '/assets/images/category-8.jpg',
    ],
    'مستحضرات-تجميل' => [
        $siteUrl . '/assets/images/category-1.jpg',
        $siteUrl . '/assets/images/category-2.jpg',
        $siteUrl . '/assets/images/avatar-1.jpg',
        $siteUrl . '/assets/images/avatar-2.jpg',
        $siteUrl . '/assets/images/avatar-3.jpg',
        $siteUrl . '/assets/images/product-6-1.jpg',
        $siteUrl . '/assets/images/product-6-2.jpg',
        $siteUrl . '/assets/images/category-6.jpg',
        $siteUrl . '/assets/images/showcase-img-1.jpg',
    ],
    'عطور' => [
        $siteUrl . '/assets/images/عطور.jpg',
        $siteUrl . '/assets/images/category-3.jpg',
        $siteUrl . '/assets/images/category-4.jpg',
        $siteUrl . '/assets/images/category-5.jpg',
        $siteUrl . '/assets/images/avatar-1.jpg',
        $siteUrl . '/assets/images/avatar-2.jpg',
        $siteUrl . '/assets/images/product-7-1.jpg',
        $siteUrl . '/assets/images/product-7-2.jpg',
        $siteUrl . '/assets/images/showcase-img-2.jpg',
    ],
    'ألعاب' => [
        $siteUrl . '/assets/images/deals-1.jpg',
        $siteUrl . '/assets/images/deals-2.png',
        $siteUrl . '/assets/images/showcase-img-1.jpg',
        $siteUrl . '/assets/images/showcase-img-2.jpg',
        $siteUrl . '/assets/images/product-10-1.jpg',
        $siteUrl . '/assets/images/product-10-2.jpg',
        $siteUrl . '/assets/images/product-11-1.jpg',
        $siteUrl . '/assets/images/product-11-2.jpg',
        $siteUrl . '/assets/images/category-1.jpg',
    ],
    'كتب' => [
        $siteUrl . '/assets/images/showcase-img-2.jpg',
        $siteUrl . '/assets/images/showcase-img-3.jpg',
        $siteUrl . '/assets/images/showcase-img-4.jpg',
        $siteUrl . '/assets/images/product-12-1.jpg',
        $siteUrl . '/assets/images/product-12-2.jpg',
        $siteUrl . '/assets/images/product-13-1.jpg',
        $siteUrl . '/assets/images/product-13-2.jpg',
        $siteUrl . '/assets/images/category-2.jpg',
        $siteUrl . '/assets/images/category-3.jpg',
    ],
    'رياضة' => [
        $siteUrl . '/assets/images/product-3-1.jpg',
        $siteUrl . '/assets/images/product-3-2.jpg',
        $siteUrl . '/assets/images/showcase-img-8.jpg',
        $siteUrl . '/assets/images/product-10-1.jpg',
        $siteUrl . '/assets/images/product-10-2.jpg',
        $siteUrl . '/assets/images/product-11-1.jpg',
        $siteUrl . '/assets/images/product-11-2.jpg',
        $siteUrl . '/assets/images/deals-1.jpg',
        $siteUrl . '/assets/images/deals-2.png',
    ],
    'صحة-وتجميل' => [
        $siteUrl . '/assets/images/avatar-2.jpg',
        $siteUrl . '/assets/images/avatar-3.jpg',
        $siteUrl . '/assets/images/category-6.jpg',
        $siteUrl . '/assets/images/category-1.jpg',
        $siteUrl . '/assets/images/category-2.jpg',
        $siteUrl . '/assets/images/product-6-1.jpg',
        $siteUrl . '/assets/images/product-6-2.jpg',
        $siteUrl . '/assets/images/showcase-img-4.jpg',
        $siteUrl . '/assets/images/showcase-img-5.jpg',
    ],
    'أدوات-منزلية' => [
        $siteUrl . '/assets/images/category-7.jpg',
        $siteUrl . '/assets/images/category-8.jpg',
        $siteUrl . '/assets/images/showcase-img-9.jpg',
        $siteUrl . '/assets/images/product-8-1.jpg',
        $siteUrl . '/assets/images/product-8-2.jpg',
        $siteUrl . '/assets/images/category-4.jpg',
        $siteUrl . '/assets/images/category-5.jpg',
        $siteUrl . '/assets/images/showcase-img-6.jpg',
        $siteUrl . '/assets/images/showcase-img-7.jpg',
    ],
    'سيارات' => [
        $siteUrl . '/assets/images/سياره.jpg',
        $siteUrl . '/assets/images/product-9-1.jpg',
        $siteUrl . '/assets/images/product-9-2.jpg',
        $siteUrl . '/assets/images/product-10-1.jpg',
        $siteUrl . '/assets/images/product-10-2.jpg',
        $siteUrl . '/assets/images/product-11-1.jpg',
        $siteUrl . '/assets/images/product-11-2.jpg',
        $siteUrl . '/assets/images/product-12-1.jpg',
        $siteUrl . '/assets/images/product-12-2.jpg',
    ],
    'هواتف' => [
        $siteUrl . '/assets/images/ايفون.jpg',
        $siteUrl . '/assets/images/product-7-1.jpg',
        $siteUrl . '/assets/images/product-7-2.jpg',
        $siteUrl . '/assets/images/product-11-1.jpg',
        $siteUrl . '/assets/images/product-11-2.jpg',
        $siteUrl . '/assets/images/product-13-1.jpg',
        $siteUrl . '/assets/images/product-13-2.jpg',
        $siteUrl . '/assets/images/category-3.jpg',
        $siteUrl . '/assets/images/category-4.jpg',
    ],
];

// Products to add for empty categories
$productsToAdd = [
    'أثاث' => [
        ['name' => 'كنبة مريحة', 'description' => 'كنبة أنيقة للصالة', 'price' => 850000, 'quantity' => 8],
        ['name' => 'طاولة قهوة', 'description' => 'طاولة قهوة خشبية', 'price' => 250000, 'quantity' => 12],
        ['name' => 'كرسي مكتب', 'description' => 'كرسي مكتب مريح', 'price' => 180000, 'quantity' => 15],
        ['name' => 'خزانة ملابس', 'description' => 'خزانة ملابس كبيرة', 'price' => 650000, 'quantity' => 6],
        ['name' => 'سرير مريح', 'description' => 'سرير مع مرتبة عالية الجودة', 'price' => 1200000, 'quantity' => 5],
    ],
    'مستحضرات-تجميل' => [
        ['name' => 'كريم مرطب للوجه', 'description' => 'كريم مرطب بتركيبة طبيعية', 'price' => 35000, 'quantity' => 50],
        ['name' => 'ماسك للوجه', 'description' => 'ماسك من الطين الطبيعي', 'price' => 25000, 'quantity' => 40],
        ['name' => 'سيروم فيتامين سي', 'description' => 'سيروم لتفتيح البشرة', 'price' => 55000, 'quantity' => 35],
        ['name' => 'أحمر شفاه طويل الأمد', 'description' => 'أحمر شفاه مع تركيبة مرطبة', 'price' => 20000, 'quantity' => 60],
        ['name' => 'كونسيلر عالي التغطية', 'description' => 'كونسيلر لإخفاء العيوب', 'price' => 30000, 'quantity' => 45],
    ],
    'عطور' => [
        ['name' => 'عطر رجالي فاخر', 'description' => 'عطر برائحة خشبية ومسكية', 'price' => 85000, 'quantity' => 30],
        ['name' => 'عطر نسائي زهري', 'description' => 'عطر برائحة زهرية عطرية', 'price' => 95000, 'quantity' => 28],
        ['name' => 'عطر عائلي', 'description' => 'عطر برائحة منعشة', 'price' => 65000, 'quantity' => 35],
        ['name' => 'عطر صيفي منعش', 'description' => 'عطر برائحة الحمضيات', 'price' => 75000, 'quantity' => 32],
        ['name' => 'عطر فاخر للنساء', 'description' => 'عطر برائحة فواكهية', 'price' => 120000, 'quantity' => 25],
    ],
    'ألعاب' => [
        ['name' => 'لعبة أطفال تعليمية', 'description' => 'لعبة لتطوير المهارات', 'price' => 45000, 'quantity' => 40],
        ['name' => 'لعبة فيديو', 'description' => 'لعبة فيديو حديثة', 'price' => 75000, 'quantity' => 50],
        ['name' => 'دمية محشوة', 'description' => 'دمية ناعمة للأطفال', 'price' => 30000, 'quantity' => 35],
        ['name' => 'سيارة أطفال', 'description' => 'سيارة كهربائية للأطفال', 'price' => 250000, 'quantity' => 10],
        ['name' => 'بازل تعليمي', 'description' => 'بازل لتنمية التفكير', 'price' => 20000, 'quantity' => 45],
    ],
    'كتب' => [
        ['name' => 'رواية عربية', 'description' => 'رواية من الأدب المعاصر', 'price' => 25000, 'quantity' => 30],
        ['name' => 'كتاب تعليمي', 'description' => 'كتاب في البرمجة والتقنية', 'price' => 35000, 'quantity' => 25],
        ['name' => 'موسوعة علمية', 'description' => 'موسوعة شاملة', 'price' => 85000, 'quantity' => 15],
        ['name' => 'كتاب طبخ', 'description' => 'كتاب وصفات متنوعة', 'price' => 40000, 'quantity' => 20],
        ['name' => 'كتاب تاريخ', 'description' => 'كتاب عن الحضارات القديمة', 'price' => 45000, 'quantity' => 18],
    ],
    'رياضة' => [
        ['name' => 'كرة قدم', 'description' => 'كرة قدم عالية الجودة', 'price' => 45000, 'quantity' => 30],
        ['name' => 'مضرب تنس', 'description' => 'مضرب تنس احترافي', 'price' => 85000, 'quantity' => 20],
        ['name' => 'دراجة هوائية', 'description' => 'دراجة هوائية مريحة', 'price' => 450000, 'quantity' => 8],
        ['name' => 'أوزان رياضية', 'description' => 'مجموعة أوزان للتمرين', 'price' => 120000, 'quantity' => 15],
        ['name' => 'سجادة يوغا', 'description' => 'سجادة يوغا مريحة', 'price' => 35000, 'quantity' => 40],
    ],
    'صحة-وتجميل' => [
        ['name' => 'مكمل غذائي', 'description' => 'مكمل غني بالفيتامينات', 'price' => 55000, 'quantity' => 50],
        ['name' => 'شامبو طبيعي', 'description' => 'شامبو بتركيبة عضوية', 'price' => 30000, 'quantity' => 45],
        ['name' => 'معجون أسنان', 'description' => 'معجون بفلورايد', 'price' => 15000, 'quantity' => 60],
        ['name' => 'فرشاة أسنان كهربائية', 'description' => 'فرشاة كهربائية متطورة', 'price' => 85000, 'quantity' => 25],
        ['name' => 'ميزان ذكي', 'description' => 'ميزان مع تطبيق', 'price' => 95000, 'quantity' => 20],
    ],
    'أدوات-منزلية' => [
        ['name' => 'مجموعة سكاكين', 'description' => 'سكاكين احترافية', 'price' => 75000, 'quantity' => 30],
        ['name' => 'طقم أواني طبخ', 'description' => 'أواني غير لاصقة', 'price' => 150000, 'quantity' => 15],
        ['name' => 'خلاط كهربائي', 'description' => 'خلاط قوي', 'price' => 85000, 'quantity' => 20],
        ['name' => 'مكنسة كهربائية', 'description' => 'مكنسة قوية', 'price' => 180000, 'quantity' => 12],
        ['name' => 'طقم أكواب', 'description' => 'أكواب زجاجية أنيقة', 'price' => 45000, 'quantity' => 35],
    ],
    'سيارات' => [
        ['name' => 'إطارات سيارات', 'description' => 'إطارات عالية الجودة', 'price' => 450000, 'quantity' => 20],
        ['name' => 'بطارية سيارة', 'description' => 'بطارية قوية', 'price' => 250000, 'quantity' => 15],
        ['name' => 'زيت محرك', 'description' => 'زيت عالي الجودة', 'price' => 35000, 'quantity' => 40],
        ['name' => 'ممسحة زجاج', 'description' => 'ممسحة زجاج أمامي', 'price' => 25000, 'quantity' => 30],
        ['name' => 'غطاء سيارة', 'description' => 'غطاء واقي', 'price' => 85000, 'quantity' => 18],
    ],
    'هواتف' => [
        ['name' => 'هاتف ذكي 128GB', 'description' => 'هاتف 128GB وكاميرا عالية', 'price' => 550000, 'quantity' => 25],
        ['name' => 'هاتف ذكي 256GB', 'description' => 'هاتف 256GB وشاشة كبيرة', 'price' => 750000, 'quantity' => 20],
        ['name' => 'هاتف ذكي اقتصادي', 'description' => 'هاتف اقتصادي', 'price' => 250000, 'quantity' => 35],
        ['name' => 'هاتف ذكي 512GB', 'description' => 'هاتف فاخر 512GB', 'price' => 1200000, 'quantity' => 10],
        ['name' => 'هاتف ذكي متوسط', 'description' => 'هاتف متوسط المواصفات', 'price' => 350000, 'quantity' => 30],
    ],
];

// Update existing products with images
echo "<h2>تحديث المنتجات الموجودة بالصور</h2>";
$updateCount = 0;

try {
    $db->beginTransaction();
    
    // Get all products with their categories
    $stmt = $db->prepare("
        SELECT p.id, p.name, c.name as category_name
        FROM products p
        INNER JOIN categories c ON c.product_id = p.id
        ORDER BY c.name, p.id
    ");
    $stmt->execute();
    $existingProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group products by category to assign different images
    $productsByCategory = [];
    foreach ($existingProducts as $product) {
        $categoryName = $product['category_name'];
        if (!isset($productsByCategory[$categoryName])) {
            $productsByCategory[$categoryName] = [];
        }
        $productsByCategory[$categoryName][] = $product;
    }
    
    foreach ($productsByCategory as $categoryName => $categoryProducts) {
        if (isset($categoryImages[$categoryName])) {
            $availableImages = $categoryImages[$categoryName];
            $imageIndex = 0;
            
            foreach ($categoryProducts as $product) {
                $mainImage = $availableImages[$imageIndex % count($availableImages)];
                $otherImages = [];
                
                // Add next images as other images
                for ($i = 1; $i <= 2 && ($imageIndex + $i) < count($availableImages); $i++) {
                    $otherImages[] = $availableImages[($imageIndex + $i) % count($availableImages)];
                }
                
                $updateStmt = $db->prepare("
                    UPDATE products 
                    SET mainImage = ?, 
                        otherImages = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $otherImagesJson = json_encode($otherImages);
                
                if ($updateStmt->execute([$mainImage, $otherImagesJson, $product['id']])) {
                    $updateCount++;
                    echo "<p class='success'>✓ تم تحديث: {$product['name']} ({$categoryName}) - صورة: " . basename($mainImage) . "</p>";
                }
                
                $imageIndex++;
            }
        }
    }
    
    $db->commit();
    echo "<p class='info'><strong>تم تحديث {$updateCount} منتج</strong></p>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<p class='error'>❌ خطأ في التحديث: " . $e->getMessage() . "</p>";
}

// Add products to empty categories
echo "<h2>إضافة منتجات للفئات الفارغة</h2>";
$addCount = 0;

try {
    $db->beginTransaction();
    
    foreach ($categories as $cat) {
        $categoryName = $cat['category_name'];
        $productCount = (int)$cat['product_count'];
        
        // If category has less than 5 products, add products
        if ($productCount < 5 && isset($productsToAdd[$categoryName])) {
            $availableImages = $categoryImages[$categoryName] ?? [$siteUrl . '/assets/images/placeholder.svg'];
            
            $products = $productsToAdd[$categoryName];
            $productsToInsert = array_slice($products, 0, 5 - $productCount);
            
            $imageIndex = $productCount; // Start from where existing products left off
            
            foreach ($productsToInsert as $productData) {
                $mainImage = $availableImages[$imageIndex % count($availableImages)];
                $otherImages = [];
                
                // Add next images as other images
                for ($i = 1; $i <= 2 && ($imageIndex + $i) < count($availableImages); $i++) {
                    $otherImages[] = $availableImages[($imageIndex + $i) % count($availableImages)];
                }
                
                $insertStmt = $db->prepare("
                    INSERT INTO products (name, description, price, mainImage, quantity, otherImages, sizes, colors, user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $otherImagesJson = json_encode($otherImages);
                
                if ($insertStmt->execute([
                    $productData['name'],
                    $productData['description'],
                    $productData['price'],
                    $mainImage,
                    $productData['quantity'],
                    $otherImagesJson,
                    json_encode([]),
                    json_encode([]),
                    $adminId
                ])) {
                    $productId = $db->lastInsertId();
                    
                    // Add category
                    $catStmt = $db->prepare("INSERT INTO categories (name, product_id) VALUES (?, ?)");
                    $catStmt->execute([$categoryName, $productId]);
                    
                    $addCount++;
                    echo "<p class='success'>✓ تم إضافة: {$productData['name']} إلى {$categoryName} - صورة: " . basename($mainImage) . "</p>";
                }
                
                $imageIndex++;
            }
        }
    }
    
    $db->commit();
    echo "<p class='info'><strong>تم إضافة {$addCount} منتج جديد</strong></p>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<p class='error'>❌ خطأ في الإضافة: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='" . SITE_URL . "/index.php'>العودة للصفحة الرئيسية</a></p>";
?>

