<?php
/**
 * إضافة المنتجات الجديدة من الصور وتنظيمها حسب الفئات
 * كل فئة تحتوي على 5 منتجات
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h1>➕ إضافة المنتجات الجديدة حسب الفئات</h1>";
echo "<pre>";

try {
    $db = getDB();
    
    // الحصول على جميع الصور من المجلد
    $imagesDir = __DIR__ . '/../assets/images/';
    $allFiles = scandir($imagesDir);
    
    // استبعاد الأيقونات والملفات الخاصة
    $excludedFiles = [
        'placeholder.svg',
        'logo.svg',
        'menu-burger.svg',
        'icon-cart.svg',
        'icon-email.svg',
        'icon-facebook.svg',
        'icon-heart.svg',
        'icon-instagram.svg',
        'icon-pinterest.svg',
        'icon-twitter.svg',
        'icon-youtube.svg',
        'payment-method.png',
        'search.png',
        'avatar-1.jpg',
        'avatar-2.jpg',
        'avatar-3.jpg',
        'category-2.jpg',
        'category-6.jpg',
        'category-7.jpg',
        'category-8.jpg',
        'showcase-img-1.jpg',
        'showcase-img-2.jpg',
        'showcase-img-3.jpg',
        'showcase-img-4.jpg',
        'showcase-img-7.jpg',
        'showcase-img-8.jpg',
        'all.jpg',
        '69449b9cc2d39_soot.jpg',
        'photo_1_2025-12-19_18-29-38.jpg',
        'اعلان.jpg',
        'اعلان 2.jpg',
        'اعلان ادوات منزليه.jpg',
        'اعلان صحه وتجميل.jpg',
        '.',
        '..',
        'products'
    ];
    
    // تصنيف الصور حسب الفئات
    $categories = [
        'ملابس-رجالية' => [
            'keywords' => ['قميص رجالي', 'قميص صيفي', 'جينز رجالي', 'شورت رجالي', 'ملابس رسميه رجاليه', 'ملابس شتويه رجاليه'],
            'images' => [],
            'max' => 5
        ],
        'ملابس-نسائية' => [
            'keywords' => ['ملابس نسائيه', 'ملابس شتويه نسائيه', 'ملابس رسميه نسائيه', 'جبه نسائيه', '2بجامه نسائيه'],
            'images' => [],
            'max' => 5
        ],
        'أحذية' => [
            'keywords' => ['حذاء', 'حذاء اديداس', 'حذاء الشامواه', 'حذاء اللوفر', 'حذاء لوفر نسائي'],
            'images' => [],
            'max' => 5
        ],
        'إلكترونيات' => [
            'keywords' => ['ايفون', 'سامسونغ', 'شاومي', 'لابوبو', 'ساعة ابل', 'PC', 'hpلابتوب', 'ierpood', 'pc2'],
            'images' => [],
            'max' => 5
        ],
        'أجهزة-منزلية' => [
            'keywords' => ['مكنسة', 'خلاط', 'جدر ضغط', 'فرشة منزل', 'تخم منزل', 'طاوله', 'دريل', 'مفكات', 'مفك', 'رده باب'],
            'images' => [],
            'max' => 5
        ],
        'سيارات' => [
            'keywords' => ['سياره', 'بوغاتي', 'رولز رويس', 'سنتافي', 'كمارو', 'تاهو'],
            'images' => [],
            'max' => 5
        ],
        'صحة-وتجميل' => [
            'keywords' => ['عطور', 'SAUVAGE', 'JADORE', 'MIO DIOR', 'CUCCL FLORA', 'YOU', 'غسول', 'واقي شمس', 'UV', 'عقدك', 'قظية'],
            'images' => [],
            'max' => 5
        ],
        'رياضة' => [
            'keywords' => ['كرة', 'كرة قدم', 'كرة التنس', 'كرةسله', 'رياضه', 'رياضه اثقال'],
            'images' => [],
            'max' => 5
        ],
        'ألعاب' => [
            'keywords' => ['دمى', 'لودو', 'اونو', 'كجول'],
            'images' => [],
            'max' => 5
        ],
        'كتب' => [
            'keywords' => ['فن الامبالاة', 'احببت وغدا', 'كن لنفسك', 'silver crest'],
            'images' => [],
            'max' => 5
        ]
    ];
    
    // تصنيف الصور
    $unclassifiedImages = [];
    
    foreach ($allFiles as $file) {
        if (in_array($file, $excludedFiles)) {
            continue;
        }
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            continue;
        }
        
        $imageName = pathinfo($file, PATHINFO_FILENAME);
        $imageUrl = SITE_URL . '/assets/images/' . $file;
        $classified = false;
        
        // البحث عن الفئة المناسبة
        foreach ($categories as $categoryName => &$categoryData) {
            foreach ($categoryData['keywords'] as $keyword) {
                if (mb_stripos($imageName, $keyword) !== false) {
                    if (count($categoryData['images']) < $categoryData['max']) {
                        $categoryData['images'][] = [
                            'file' => $file,
                            'url' => $imageUrl,
                            'name' => $imageName
                        ];
                        $classified = true;
                        break 2;
                    }
                }
            }
        }
        
        if (!$classified) {
            // محاولة تصنيف الصور بدون أسماء (photo_X)
            if (preg_match('/^photo_\d+/', $imageName)) {
                $unclassifiedImages[] = [
                    'file' => $file,
                    'url' => $imageUrl,
                    'name' => $imageName
                ];
            }
        }
    }
    
    // توزيع الصور غير المصنفة على الفئات التي تحتاج منتجات
    foreach ($unclassifiedImages as $img) {
        foreach ($categories as $categoryName => &$categoryData) {
            if (count($categoryData['images']) < $categoryData['max']) {
                $categoryData['images'][] = $img;
                break;
            }
        }
    }
    
    // عرض التصنيف
    echo "📊 تصنيف الصور:\n\n";
    foreach ($categories as $categoryName => $categoryData) {
        echo "📁 {$categoryName}: " . count($categoryData['images']) . " صورة\n";
        foreach ($categoryData['images'] as $img) {
            echo "   - {$img['file']}\n";
        }
        echo "\n";
    }
    
    // الحصول على user_id (افتراضي: 1)
    $userId = 1;
    $userStmt = $db->query("SELECT id FROM users LIMIT 1");
    $user = $userStmt->fetch();
    if ($user) {
        $userId = $user['id'];
    }
    
    // إضافة المنتجات للفئات
    $addedCount = 0;
    
    foreach ($categories as $categoryName => $categoryData) {
        if (empty($categoryData['images'])) {
            continue;
        }
        
        echo "➕ إضافة منتجات لفئة: {$categoryName}\n";
        
        foreach ($categoryData['images'] as $index => $img) {
            // إنشاء اسم المنتج من اسم الصورة
            $productName = $img['name'];
            
            // تحسين الأسماء
            $productName = str_replace(['_', '-', 'photo_'], ' ', $productName);
            $productName = preg_replace('/\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}/', '', $productName);
            $productName = trim($productName);
            
            // إنشاء وصف تفصيلي حسب الفئة
            $description = "منتج عالي الجودة من فئة {$categoryName}";
            $price = 0;
            
            // تفاصيل خاصة للسيارات
            $carDetails = [
                'بوغاتي' => [
                    'name' => 'بوغاتي شيرون',
                    'description' => 'سيارة رياضية فاخرة من بوغاتي مع محرك W16 سعة 8.0 لتر بقوة 1500 حصان. تتميز بتسارع من 0 إلى 100 كم/س في 2.4 ثانية وسرعة قصوى 420 كم/س. تحتوي على نظام دفع رباعي، فرامل كربونية، ونظام تعليق متطور.',
                    'price' => 18000000,
                    'features' => ['محرك W16 بقوة 1500 حصان', 'سرعة قصوى 420 كم/س', 'تسارع 0-100 في 2.4 ثانية', 'نظام دفع رباعي', 'فرامل كربونية', 'نظام تعليق متطور', 'تصميم فريد ومميز']
                ],
                'رولز رويس' => [
                    'name' => 'رولز رويس فانتوم',
                    'description' => 'سيارة فاخرة من رولز رويس مع محرك V12 سعة 6.75 لتر. تتميز بالرفاهية القصوى والراحة الفائقة. تحتوي على نظام تعليق هوائي، مقاعد جلدية فاخرة، نظام صوتي متطور، وتقنيات أمان متقدمة.',
                    'price' => 12000000,
                    'features' => ['محرك V12 بقوة 563 حصان', 'نظام تعليق هوائي', 'مقاعد جلدية فاخرة', 'نظام صوتي متطور', 'تقنيات أمان متقدمة', 'تصميم كلاسيكي أنيق', 'راحة ورفاهية فائقة']
                ],
                'سنتافي' => [
                    'name' => 'هيونداي سنتافي',
                    'description' => 'سيارة دفع رباعي متعددة الاستخدامات من هيونداي. تتميز بمساحة واسعة، محرك قوي، وتقنيات حديثة. مناسبة للعائلات الكبيرة والرحلات الطويلة.',
                    'price' => 8500000,
                    'features' => ['محرك V6 بقوة 290 حصان', 'دفع رباعي', '7 مقاعد', 'مساحة شاسعة', 'نظام ملاحة متطور', 'كاميرات خلفية وجانبية', 'نظام أمان متقدم']
                ],
                'كمارو' => [
                    'name' => 'شيفروليه كمارو',
                    'description' => 'سيارة رياضية أمريكية كلاسيكية من شيفروليه. تتميز بمحرك V8 قوي، تصميم رياضي جذاب، وأداء عالي. مناسبة لمحبي السرعة والأداء.',
                    'price' => 6500000,
                    'features' => ['محرك V8 بقوة 455 حصان', 'تصميم رياضي جذاب', 'نظام تعليق رياضي', 'نظام صوتي قوي', 'مقاعد رياضية', 'نظام تحكم متقدم', 'أداء عالي']
                ],
                'تاهو' => [
                    'name' => 'شيفروليه تاهو',
                    'description' => 'سيارة دفع رباعي كبيرة من شيفروليه. تتميز بقوة وجرأة، مساحة واسعة، وتقنيات متقدمة. مناسبة للاستخدام اليومي والرحلات.',
                    'price' => 7500000,
                    'features' => ['محرك V8 بقوة 355 حصان', 'دفع رباعي', '8 مقاعد', 'مساحة شاسعة', 'نظام جر متقدم', 'نظام أمان شامل', 'تقنيات حديثة']
                ],
                'سياره' => [
                    'name' => 'سيارة فاخرة',
                    'description' => 'سيارة فاخرة عالية الجودة مع أحدث التقنيات والمواصفات. تتميز بالراحة والأمان والأداء المتميز.',
                    'price' => 5500000,
                    'features' => ['محرك قوي', 'نظام أمان متقدم', 'تقنيات حديثة', 'راحة فائقة', 'تصميم أنيق', 'أداء متميز']
                ]
            ];
            
            // تحديد السعر والوصف حسب الفئة والمنتج
            if ($categoryName == 'سيارات') {
                // البحث عن تفاصيل السيارة
                $carFound = false;
                foreach ($carDetails as $carKey => $carInfo) {
                    if (mb_stripos($img['name'], $carKey) !== false) {
                        $productName = $carInfo['name'];
                        $description = $carInfo['description'];
                        $price = $carInfo['price'];
                        $carFeatures = $carInfo['features'];
                        
                        // إضافة الميزات إلى الوصف
                        if (!empty($carFeatures)) {
                            $description .= "\n\nالميزات الرئيسية:\n";
                            foreach ($carFeatures as $feature) {
                                $description .= "• " . $feature . "\n";
                            }
                        }
                        
                        $carFound = true;
                        break;
                    }
                }
                
                if (!$carFound) {
                    // سيارة عامة
                    if (mb_strlen($productName) < 3) {
                        $productName = "سيارة فاخرة " . ($index + 1);
                    }
                    $price = rand(5000000, 10000000);
                    $description = "سيارة فاخرة عالية الجودة مع أحدث التقنيات والمواصفات. تتميز بالراحة والأمان والأداء المتميز.";
                }
            } else {
                // إذا كان الاسم فارغاً أو قصيراً جداً، استخدم اسم افتراضي
                if (mb_strlen($productName) < 3) {
                    $productName = "منتج {$categoryName} " . ($index + 1);
                }
                
                // تحديد السعر حسب الفئة
                $prices = [
                    'ملابس-رجالية' => [25000, 180000],
                    'ملابس-نسائية' => [30000, 250000],
                    'أحذية' => [35000, 120000],
                    'إلكترونيات' => [150000, 650000],
                    'أجهزة-منزلية' => [50000, 550000],
                    'صحة-وتجميل' => [20000, 150000],
                    'رياضة' => [25000, 450000],
                    'ألعاب' => [15000, 200000],
                    'كتب' => [5000, 55000]
                ];
                
                $priceRange = $prices[$categoryName] ?? [10000, 100000];
                $price = rand($priceRange[0], $priceRange[1]);
            }
            
            // تحديد المقاسات والألوان حسب الفئة
            $sizes = null;
            $colors = null;
            
            if (in_array($categoryName, ['ملابس-رجالية', 'ملابس-نسائية'])) {
                $sizes = json_encode(['S', 'M', 'L', 'XL'], JSON_UNESCAPED_UNICODE);
                $colors = json_encode(['أسود', 'أبيض', 'أزرق', 'رمادي'], JSON_UNESCAPED_UNICODE);
            } elseif ($categoryName == 'أحذية') {
                $sizes = json_encode(['38', '39', '40', '41', '42', '43', '44'], JSON_UNESCAPED_UNICODE);
                $colors = json_encode(['أسود', 'بني', 'أبيض'], JSON_UNESCAPED_UNICODE);
            }
            
            // إضافة المنتج
            try {
                $stmt = $db->prepare("
                    INSERT INTO products (name, description, price, mainImage, quantity, otherImages, sizes, colors, user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $quantity = rand(5, 30);
                $otherImages = null;
                
                $stmt->execute([
                    $productName,
                    $description,
                    $price,
                    $img['url'],
                    $quantity,
                    $otherImages,
                    $sizes,
                    $colors,
                    $userId
                ]);
                
                $productId = $db->lastInsertId();
                
                // إضافة الفئة
                $catStmt = $db->prepare("INSERT INTO categories (name, product_id) VALUES (?, ?)");
                $catStmt->execute([$categoryName, $productId]);
                
                echo "   ✅ تم إضافة: {$productName} (السعر: " . number_format($price, 2) . " د.ع)\n";
                $addedCount++;
                
            } catch (Exception $e) {
                echo "   ❌ خطأ في إضافة المنتج: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
    }
    
    // تحديث المنتجات الموجودة في فئة السيارات
    echo "🚗 تحديث تفاصيل فئة السيارات:\n";
    
    $carsStmt = $db->query("
        SELECT p.id, p.name, p.mainImage 
        FROM products p 
        JOIN categories c ON p.id = c.product_id 
        WHERE c.name = 'سيارات'
    ");
    $existingCars = $carsStmt->fetchAll();
    
    foreach ($existingCars as $car) {
        $imageName = pathinfo($car['mainImage'], PATHINFO_FILENAME);
        $imageName = basename($imageName);
        
        // البحث عن تفاصيل السيارة
        foreach ($carDetails as $carKey => $carInfo) {
            if (mb_stripos($imageName, $carKey) !== false || mb_stripos($car['name'], $carKey) !== false) {
                $newName = $carInfo['name'];
                $newDescription = $carInfo['description'];
                
                // إضافة الميزات إلى الوصف
                if (!empty($carInfo['features'])) {
                    $newDescription .= "\n\nالميزات الرئيسية:\n";
                    foreach ($carInfo['features'] as $feature) {
                        $newDescription .= "• " . $feature . "\n";
                    }
                }
                
                try {
                    $updateStmt = $db->prepare("UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?");
                    $updateStmt->execute([$newName, $newDescription, $carInfo['price'], $car['id']]);
                    echo "   ✅ تم تحديث: {$newName}\n";
                    echo "      السعر: " . number_format($carInfo['price'], 2) . " د.ع\n";
                    echo "      الميزات: " . count($carInfo['features']) . " ميزة\n";
                } catch (Exception $e) {
                    echo "   ❌ خطأ في تحديث السيارة: " . $e->getMessage() . "\n";
                }
                break;
            }
        }
    }
    
    echo "\n";
    
    // معالجة خاصة لفئة الأحذية - إضافة أحذية رجالية ونسائية
    echo "👟 معالجة خاصة لفئة الأحذية:\n";
    
    // البحث عن أحذية رجالية ونسائية
    $shoesImages = [];
    foreach ($allFiles as $file) {
        if (in_array($file, $excludedFiles)) continue;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;
        
        $imageName = pathinfo($file, PATHINFO_FILENAME);
        
        if (mb_stripos($imageName, 'حذاء') !== false) {
            $isWomen = mb_stripos($imageName, 'نسائي') !== false || mb_stripos($imageName, 'لوفر نسائي') !== false;
            $shoesImages[] = [
                'file' => $file,
                'url' => SITE_URL . '/assets/images/' . $file,
                'name' => $imageName,
                'isWomen' => $isWomen
            ];
        }
    }
    
    // تحديث المنتجات الموجودة في فئة الأحذية
    $shoesStmt = $db->query("
        SELECT p.id, p.name, p.mainImage 
        FROM products p 
        JOIN categories c ON p.id = c.product_id 
        WHERE c.name = 'أحذية'
        LIMIT 5
    ");
    $existingShoes = $shoesStmt->fetchAll();
    
    $menShoes = array_filter($shoesImages, function($img) { return !$img['isWomen']; });
    $womenShoes = array_filter($shoesImages, function($img) { return $img['isWomen']; });
    
    $menShoes = array_slice($menShoes, 0, 3); // 3 أحذية رجالية
    $womenShoes = array_slice($womenShoes, 0, 2); // 2 أحذية نسائية
    
    $allShoes = array_merge($menShoes, $womenShoes);
    
    foreach ($existingShoes as $index => $shoe) {
        if (isset($allShoes[$index])) {
            $img = $allShoes[$index];
            $productName = $img['name'];
            $productName = str_replace(['_', '-'], ' ', $productName);
            $productName = trim($productName);
            
            if (mb_strlen($productName) < 3) {
                $productName = $img['isWomen'] ? "حذاء نسائي " . ($index + 1) : "حذاء رجالي " . ($index + 1);
            }
            
            try {
                $updateStmt = $db->prepare("UPDATE products SET name = ?, mainImage = ? WHERE id = ?");
                $updateStmt->execute([$productName, $img['url'], $shoe['id']]);
                echo "   ✅ تم تحديث: {$productName}\n";
            } catch (Exception $e) {
                echo "   ❌ خطأ: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ تم إضافة {$addedCount} منتج جديد بنجاح!\n";
    echo "📁 تم تنظيم المنتجات في " . count($categories) . " فئة\n";
    echo "\n🎉 تم الانتهاء بنجاح!\n";
    echo "\n📋 الخطوات التالية:\n";
    echo "   1. أعد تحميل الصفحة الرئيسية: " . SITE_URL . "/pages/index.php\n";
    echo "   2. تحقق من أن كل فئة تحتوي على 5 منتجات\n";
    echo "   3. تحقق من فئة الأحذية (يجب أن تحتوي على أحذية رجالية ونسائية)\n";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
    echo "\n💡 تأكد من:\n";
    echo "   1. تشغيل MySQL في XAMPP\n";
    echo "   2. وجود قاعدة البيانات shop_smart\n";
    echo "   3. صحة إعدادات config/database.php\n";
}

echo "</pre>";

// إضافة أزرار للتنقل
echo "<div style='margin-top: 20px;'>";
echo "<a href='" . SITE_URL . "/pages/index.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>الصفحة الرئيسية</a>";
echo "<a href='" . SITE_URL . "/pages/products.php' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>جميع المنتجات</a>";
echo "<a href='" . SITE_URL . "/admin/admin.php' style='background: #6b7280; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>لوحة التحكم</a>";
echo "</div>";
?>

