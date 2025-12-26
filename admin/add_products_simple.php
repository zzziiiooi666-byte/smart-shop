<?php
/**
 * سكريبت بسيط لإضافة 5 منتجات لكل فئة تلقائياً
 * استخدم هذا الملف لإضافة منتجات بسهولة
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

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إضافة منتجات بسهولة</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            direction: rtl;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
            border-right: 4px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
            border-right: 4px solid #ef4444;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
            border-right: 4px solid #3b82f6;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #4338ca;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0;
            color: #4f46e5;
            font-size: 32px;
        }
        .stat-card p {
            margin: 5px 0 0 0;
            color: #6b7280;
        }
    </style>
</head>
<body>
<div class='container'>";

// Get action
$action = $_GET['action'] ?? '';

if ($action === 'add') {
    // Products data for each category (5 products per category) - matching actual image names
    $productsData = [
        'ملابس-رجالية' => [
            ['name' => 'قميص رجالي ', 'description' => 'قميص رجالي أنيق من القطن', 'price' => 45000],
            ['name' => 'قميص صيفي رجالي ', 'description' => 'قميص صيفي مريح', 'price' => 40000],
            ['name' => 'جينز رجالي ', 'description' => 'بنطال جينز عالي الجودة', 'price' => 65000],
            ['name' => 'شورت رجالي ', 'description' => 'شورت رجالي مريح', 'price' => 35000],
            ['name' => 'ملابس رسميه رجاليه', 'description' => 'بدلة رسمية للمناسبات', 'price' => 180000],
        ],
        'ملابس-نسائية' => [
            ['name' => 'جبه نسائيه ', 'description' => 'جبه نسائي أنيق', 'price' => 55000],
            ['name' => 'ملابس رسميه نسائيه ', 'description' => 'ملابس رسمية أنيقة', 'price' => 85000],
            ['name' => 'ملابس شتويه نسائيه ', 'description' => 'ملابس شتوية دافئة', 'price' => 120000],
            ['name' => 'ملابس شتويه نسائيه 2', 'description' => 'ملابس شتوية أنيقة', 'price' => 110000],
            ['name' => 'ملابس شتويه نسائيه 3', 'description' => 'ملابس شتوية مريحة', 'price' => 100000],
        ],
        'أحذية' => [
            ['name' => 'حذاء ', 'description' => 'حذاء مريح وعالي الجودة', 'price' => 120000],
            ['name' => 'حذاء اديداس سامبا', 'description' => 'حذاء رياضي من أديداس', 'price' => 150000],
            ['name' => 'حذاء الشامواه', 'description' => 'حذاء من الجلد الشامواه', 'price' => 180000],
            ['name' => 'حذاء اللوفر', 'description' => 'حذاء أنيق من اللوفر', 'price' => 200000],
            ['name' => 'حذاء لوفر نسائي', 'description' => 'حذاء نسائي أنيق', 'price' => 190000],
        ],
        'إلكترونيات' => [
            ['name' => 'PC', 'description' => 'جهاز كمبيوتر شخصي', 'price' => 1500000],
            ['name' => 'hpلابتوب', 'description' => 'لابتوب من HP', 'price' => 1200000],
            ['name' => 'ierpood', 'description' => 'سماعات لاسلكية', 'price' => 250000],
            ['name' => 'ساعة ابل ', 'description' => 'ساعة ذكية من أبل', 'price' => 800000],
            ['name' => 'pc2', 'description' => 'جهاز كمبيوتر حديث', 'price' => 1800000],
        ],
        'أجهزة-منزلية' => [
            ['name' => 'مكنسة كهربائيه', 'description' => 'مكنسة كهربائية قوية', 'price' => 180000],
            ['name' => 'مكنسه شحن', 'description' => 'مكنسة لاسلكية', 'price' => 250000],
            ['name' => 'خلاط', 'description' => 'خلاط كهربائي قوي', 'price' => 85000],
            ['name' => 'جدر ضغط', 'description' => 'جهاز ضغط عالي', 'price' => 120000],
            ['name' => 'UV', 'description' => 'جهاز تنظيف بالأشعة فوق البنفسجية', 'price' => 150000],
        ],
        'أثاث' => [
            ['name' => 'طاوله', 'description' => 'طاولة أنيقة', 'price' => 250000],
            ['name' => 'تخم منزل', 'description' => 'تخم منزلي مريح', 'price' => 350000],
            ['name' => 'فرشة منزل', 'description' => 'فرشة منزلية عالية الجودة', 'price' => 450000],
            ['name' => 'فرشة منزل شتويه', 'description' => 'فرشة شتوية دافئة', 'price' => 500000],
            ['name' => 'فرشة منزل مع تخم ', 'description' => 'مجموعة فرشة مع تخم', 'price' => 750000],
        ],
        'مستحضرات-تجميل' => [
            ['name' => 'غسول وجه', 'description' => 'غسول للوجه بتركيبة طبيعية', 'price' => 35000],
            ['name' => 'واقي شمس', 'description' => 'واقي شمسي عالي الحماية', 'price' => 40000],
            ['name' => 'UV', 'description' => 'كريم واقي من الأشعة', 'price' => 45000],
            ['name' => 'اوتي', 'description' => 'مستحضر تجميل', 'price' => 30000],
            ['name' => 'كجول', 'description' => 'مستحضر تجميل', 'price' => 50000],
        ],
        'عطور' => [
            ['name' => 'عطور', 'description' => 'عطر فاخر', 'price' => 85000],
            ['name' => 'MIO DIOR', 'description' => 'عطر ديور', 'price' => 200000],
            ['name' => 'CUCCL FLORA', 'description' => 'عطر فلورا', 'price' => 180000],
            ['name' => 'YOU', 'description' => 'عطر أنت', 'price' => 120000],
            ['name' => 'JADORE', 'description' => 'عطر جادور', 'price' => 250000],
        ],
        'ألعاب' => [
            ['name' => 'دمى اطفال ', 'description' => 'دمى ناعمة للأطفال', 'price' => 30000],
            ['name' => 'لودو', 'description' => 'لعبة لودو', 'price' => 25000],
            ['name' => 'اونو', 'description' => 'لعبة أونو', 'price' => 20000],
            ['name' => 'لابوبو صغير', 'description' => 'لعبة لابوبو صغيرة', 'price' => 35000],
            ['name' => 'لابوبو', 'description' => 'لعبة لابوبو', 'price' => 40000],
        ],
        'كتب' => [
            ['name' => 'احببت وغدا', 'description' => 'رواية أحببت وغدا', 'price' => 25000],
            ['name' => 'فن الامبالاة', 'description' => 'كتاب فن الإمبالاة', 'price' => 30000],
            ['name' => 'كن لنفسك كل شيء', 'description' => 'كتاب تطوير الذات', 'price' => 35000],
            ['name' => 'SAUVAGE', 'description' => 'كتاب', 'price' => 40000],
            ['name' => 'MIO DIOR', 'description' => 'كتاب', 'price' => 45000],
        ],
        'رياضة' => [
            ['name' => 'كرة قدم', 'description' => 'كرة قدم عالية الجودة', 'price' => 45000],
            ['name' => 'كرة التنس', 'description' => 'كرة تنس احترافية', 'price' => 30000],
            ['name' => 'كرةسله', 'description' => 'كرة سلة', 'price' => 50000],
            ['name' => 'رياضه اثقال ', 'description' => 'مجموعة أوزان للتمرين', 'price' => 120000],
            ['name' => 'رياضه', 'description' => 'معدات رياضية', 'price' => 85000],
        ],
        'صحة-وتجميل' => [
            ['name' => 'غسول وجه', 'description' => 'غسول للوجه', 'price' => 35000],
            ['name' => 'واقي شمس', 'description' => 'واقي شمسي', 'price' => 40000],
            ['name' => 'UV', 'description' => 'كريم واقي', 'price' => 45000],
            ['name' => 'اوتي', 'description' => 'مستحضر صحي', 'price' => 30000],
            ['name' => 'كجول', 'description' => 'مستحضر تجميل', 'price' => 50000],
        ],
        'أدوات-منزلية' => [
            ['name' => 'مكنسة كهربائيه', 'description' => 'مكنسة كهربائية', 'price' => 180000],
            ['name' => 'مكنسه شحن', 'description' => 'مكنسة لاسلكية', 'price' => 250000],
            ['name' => 'خلاط', 'description' => 'خلاط كهربائي', 'price' => 85000],
            ['name' => 'جدر ضغط', 'description' => 'جهاز ضغط', 'price' => 120000],
            ['name' => 'UV', 'description' => 'جهاز تنظيف', 'price' => 150000],
        ],
        'سيارات' => [
            ['name' => 'سياره', 'description' => 'سيارة', 'price' => 5000000],
            ['name' => 'رولز رويس فانتوم', 'description' => 'سيارة رولز رويس فانتوم', 'price' => 15000000],
            ['name' => 'بوغاتي', 'description' => 'سيارة بوغاتي', 'price' => 20000000],
            ['name' => 'سنتافي', 'description' => 'سيارة سنتافي', 'price' => 8000000],
            ['name' => 'SAUVAGE', 'description' => 'سيارة', 'price' => 6000000],
        ],
        'هواتف' => [
            ['name' => 'ايفون', 'description' => 'هاتف آيفون', 'price' => 1200000],
            ['name' => 'ايفون 13', 'description' => 'هاتف آيفون 13', 'price' => 1500000],
            ['name' => 'ايفون17', 'description' => 'هاتف آيفون 17', 'price' => 2000000],
            ['name' => 'سامسونغ S24', 'description' => 'هاتف سامسونغ S24', 'price' => 1800000],
            ['name' => 'سامسونغS24 UITRA', 'description' => 'هاتف سامسونغ S24 Ultra', 'price' => 2500000],
        ],
    ];

    $totalAdded = 0;
    $totalUpdated = 0;
    $errors = [];

    try {
        $db->beginTransaction();

        foreach ($productsData as $categoryName => $products) {
            // Get current product count for this category
            $countStmt = $db->prepare("
                SELECT COUNT(*) as count FROM products p
                JOIN categories c ON p.id = c.product_id
                WHERE c.name = ?
            ");
            $countStmt->execute([$categoryName]);
            $currentCount = (int)$countStmt->fetch()['count'];

            // Calculate how many products to add
            $needed = max(0, 5 - $currentCount);

            if ($needed > 0) {
                // Add missing products
                for ($i = 0; $i < min($needed, count($products)); $i++) {
                    $product = $products[$i];
                    
                    // Use product name as image name (exact match with image files)
                    $imageName = $product['name'] . '.jpg';
                    $mainImage = $siteUrl . '/assets/images/' . $imageName;
                    
                    // For other images, use the same image
                    $otherImages = [$siteUrl . '/assets/images/' . $imageName];

                    $insertStmt = $db->prepare("
                        INSERT INTO products (name, description, price, mainImage, quantity, otherImages, sizes, colors, user_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    if ($insertStmt->execute([
                        $product['name'],
                        $product['description'],
                        $product['price'],
                        $mainImage,
                        20, // quantity
                        json_encode($otherImages),
                        json_encode([]),
                        json_encode([]),
                        $adminId
                    ])) {
                        $productId = $db->lastInsertId();
                        
                        // Add category
                        $catStmt = $db->prepare("INSERT INTO categories (name, product_id) VALUES (?, ?)");
                        $catStmt->execute([$categoryName, $productId]);
                        
                        $totalAdded++;
                        echo "<div class='success'>✓ تم إضافة: {$product['name']} إلى {$categoryName}</div>";
                    } else {
                        $errors[] = "فشل إضافة: {$product['name']}";
                    }
                }
            } else {
                echo "<div class='info'>ℹ️ فئة {$categoryName} تحتوي بالفعل على 5 منتجات أو أكثر</div>";
            }
        }

        $db->commit();
        
        echo "<div class='success' style='margin-top: 20px; font-size: 18px; font-weight: bold;'>
            ✅ تم بنجاح! تم إضافة {$totalAdded} منتج جديد
        </div>";

        if (!empty($errors)) {
            echo "<div class='error'><strong>أخطاء:</strong><ul>";
            foreach ($errors as $error) {
                echo "<li>{$error}</li>";
            }
            echo "</ul></div>";
        }

    } catch (Exception $e) {
        $db->rollBack();
        echo "<div class='error'>❌ حدث خطأ: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Get statistics
$stmt = $db->prepare("SELECT COUNT(*) as total FROM products");
$stmt->execute();
$totalProducts = $stmt->fetch()['total'];

$stmt = $db->prepare("
    SELECT c.name, COUNT(p.id) as count 
    FROM categories c
    LEFT JOIN products p ON c.product_id = p.id
    GROUP BY c.name
    ORDER BY c.name
");
$stmt->execute();
$categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>🚀 إضافة منتجات بسهولة</h1>";

echo "<div class='stats'>
    <div class='stat-card'>
        <h3>{$totalProducts}</h3>
        <p>إجمالي المنتجات</p>
    </div>
    <div class='stat-card'>
        <h3>" . count($categoryStats) . "</h3>
        <p>عدد الفئات</p>
    </div>
</div>";

echo "<h2>📊 إحصائيات الفئات:</h2>";
echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
    <thead>
        <tr style='background: #f8f9fa;'>
            <th style='padding: 12px; text-align: right; border: 1px solid #dee2e6;'>الفئة</th>
            <th style='padding: 12px; text-align: center; border: 1px solid #dee2e6;'>عدد المنتجات</th>
            <th style='padding: 12px; text-align: center; border: 1px solid #dee2e6;'>الحالة</th>
        </tr>
    </thead>
    <tbody>";

foreach ($categoryStats as $stat) {
    $count = (int)$stat['count'];
    $status = $count >= 5 ? '✅ مكتملة' : '⚠️ تحتاج ' . (5 - $count) . ' منتجات';
    $statusColor = $count >= 5 ? '#10b981' : '#f59e0b';
    
    echo "<tr>
        <td style='padding: 12px; border: 1px solid #dee2e6;'><strong>{$stat['name']}</strong></td>
        <td style='padding: 12px; text-align: center; border: 1px solid #dee2e6;'>{$count}</td>
        <td style='padding: 12px; text-align: center; border: 1px solid #dee2e6; color: {$statusColor};'>{$status}</td>
    </tr>";
}

echo "</tbody></table>";

echo "<div style='text-align: center; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;'>
    <h3>✨ إضافة منتجات تلقائياً</h3>
    <p style='color: #6b7280; margin: 15px 0;'>
        سيتم إضافة 5 منتجات لكل فئة تلقائياً (فقط للفئات التي تحتوي على أقل من 5 منتجات)
    </p>
    <a href='?action=add' class='btn btn-success' onclick='return confirm(\"هل أنت متأكد من إضافة المنتجات؟\")'>
        ➕ إضافة المنتجات الآن
    </a>
</div>";

echo "<div style='text-align: center; margin-top: 30px;'>
    <a href='" . SITE_URL . "/index.php' class='btn'>🏠 العودة للصفحة الرئيسية</a>
    <a href='" . SITE_URL . "/admin/admin.php' class='btn'>⚙️ لوحة التحكم</a>
</div>";

echo "</div></body></html>";
?>

