<?php
/**
 * إصلاح الصور المفقودة في قاعدة البيانات
 * يستبدل الصور المفقودة بصور موجودة أو placeholder
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h1>🔧 إصلاح الصور المفقودة</h1>";
echo "<pre>";

try {
    $db = getDB();
    
    // قائمة الصور المفقودة والبدائل
    $imageReplacements = [
        // الصور المفقودة → الصور البديلة الموجودة
        'product-1-1.jpg' => 'product-5-1.jpg',
        'product-1-2.jpg' => 'product-5-2.jpg',
        'product-2-1.jpg' => 'product-6-1.jpg',
        'product-2-2.jpg' => 'product-7-1.jpg',
        'product-3-1.jpg' => 'product-8-1.jpg',
        'product-4-1.jpg' => 'product-10-1.jpg',
        'product-4-2.jpg' => 'product-10-2.jpg',
        'product-6-2.jpg' => 'product-11-2.jpg',
    ];
    
    // الحصول على جميع المنتجات
    $stmt = $db->query("SELECT id, mainImage, otherImages FROM products");
    $products = $stmt->fetchAll();
    
    $updatedCount = 0;
    $errors = [];
    
    foreach ($products as $product) {
        $needsUpdate = false;
        $newMainImage = $product['mainImage'];
        $newOtherImages = $product['otherImages'];
        
        // التحقق من الصورة الرئيسية
        foreach ($imageReplacements as $missing => $replacement) {
            if (strpos($product['mainImage'], $missing) !== false) {
                $newMainImage = str_replace($missing, $replacement, $product['mainImage']);
                $needsUpdate = true;
                echo "✅ المنتج #{$product['id']}: استبدال mainImage من {$missing} إلى {$replacement}\n";
                break;
            }
        }
        
        // التحقق من الصور الإضافية
        if (!empty($product['otherImages'])) {
            $otherImages = json_decode($product['otherImages'], true);
            if (is_array($otherImages)) {
                $updatedOtherImages = [];
                foreach ($otherImages as $image) {
                    $updated = false;
                    foreach ($imageReplacements as $missing => $replacement) {
                        if (strpos($image, $missing) !== false) {
                            $updatedOtherImages[] = str_replace($missing, $replacement, $image);
                            $updated = true;
                            echo "✅ المنتج #{$product['id']}: استبدال otherImage من {$missing} إلى {$replacement}\n";
                            break;
                        }
                    }
                    if (!$updated) {
                        $updatedOtherImages[] = $image;
                    }
                }
                if ($updated) {
                    $newOtherImages = json_encode($updatedOtherImages, JSON_UNESCAPED_UNICODE);
                    $needsUpdate = true;
                }
            }
        }
        
        // تحديث قاعدة البيانات إذا لزم الأمر
        if ($needsUpdate) {
            try {
                $updateStmt = $db->prepare("UPDATE products SET mainImage = ?, otherImages = ? WHERE id = ?");
                $updateStmt->execute([$newMainImage, $newOtherImages, $product['id']]);
                $updatedCount++;
            } catch (Exception $e) {
                $errors[] = "خطأ في تحديث المنتج #{$product['id']}: " . $e->getMessage();
            }
        }
    }
    
    // استبدال الصور المفقودة بالـ placeholder إذا لم توجد بدائل
    echo "\n🔄 البحث عن صور مفقودة أخرى...\n";
    
    $stmt = $db->query("SELECT id, mainImage, otherImages FROM products");
    $allProducts = $stmt->fetchAll();
    
    $placeholderUrl = SITE_URL . '/assets/images/placeholder.svg';
    
    foreach ($allProducts as $product) {
        $needsUpdate = false;
        $newMainImage = $product['mainImage'];
        $newOtherImages = $product['otherImages'];
        
        // التحقق من وجود الصورة الرئيسية
        if (!empty($product['mainImage'])) {
            $imagePath = str_replace(SITE_URL, __DIR__ . '/..', $product['mainImage']);
            $imagePath = str_replace('http://localhost/smart_markt', __DIR__ . '/..', $imagePath);
            
            if (!file_exists($imagePath) && strpos($product['mainImage'], 'placeholder') === false) {
                $newMainImage = $placeholderUrl;
                $needsUpdate = true;
                echo "⚠️ المنتج #{$product['id']}: استبدال mainImage المفقودة بـ placeholder\n";
            }
        }
        
        // التحقق من الصور الإضافية
        if (!empty($product['otherImages'])) {
            $otherImages = json_decode($product['otherImages'], true);
            if (is_array($otherImages)) {
                $updatedOtherImages = [];
                foreach ($otherImages as $image) {
                    if (empty($image)) {
                        continue;
                    }
                    
                    $imagePath = str_replace(SITE_URL, __DIR__ . '/..', $image);
                    $imagePath = str_replace('http://localhost/smart_markt', __DIR__ . '/..', $imagePath);
                    
                    if (!file_exists($imagePath) && strpos($image, 'placeholder') === false) {
                        $updatedOtherImages[] = $placeholderUrl;
                        $needsUpdate = true;
                        echo "⚠️ المنتج #{$product['id']}: استبدال otherImage المفقودة بـ placeholder\n";
                    } else {
                        $updatedOtherImages[] = $image;
                    }
                }
                if ($needsUpdate) {
                    $newOtherImages = json_encode($updatedOtherImages, JSON_UNESCAPED_UNICODE);
                }
            }
        }
        
        // تحديث قاعدة البيانات
        if ($needsUpdate) {
            try {
                $updateStmt = $db->prepare("UPDATE products SET mainImage = ?, otherImages = ? WHERE id = ?");
                $updateStmt->execute([$newMainImage, $newOtherImages, $product['id']]);
                $updatedCount++;
            } catch (Exception $e) {
                $errors[] = "خطأ في تحديث المنتج #{$product['id']}: " . $e->getMessage();
            }
        }
    }
    
    echo "\n✅ تم تحديث {$updatedCount} منتج بنجاح!\n";
    
    if (!empty($errors)) {
        echo "\n❌ الأخطاء:\n";
        foreach ($errors as $error) {
            echo "   - {$error}\n";
        }
    }
    
    echo "\n🎉 تم إصلاح الصور المفقودة بنجاح!\n";
    echo "\n📋 الخطوات التالية:\n";
    echo "   1. أعد تحميل الصفحة الرئيسية\n";
    echo "   2. تحقق من أن الصور تظهر بشكل صحيح\n";
    echo "   3. إذا كانت هناك صور placeholder، يمكنك استبدالها لاحقاً\n";
    
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
echo "<a href='" . SITE_URL . "/tools/diagnose.php' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>التشخيص</a>";
echo "<a href='" . SITE_URL . "/admin/admin.php' style='background: #6b7280; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>لوحة التحكم</a>";
echo "</div>";
?>

