<?php
/**
 * Script to delete products from database
 * Use with caution - this will permanently delete products
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// Check if admin user exists
$stmt = $db->query("SELECT id FROM users WHERE isAdmin = 1 LIMIT 1");
$admin = $stmt->fetch();

if (!$admin) {
    die("❌ يجب إنشاء مستخدم admin أولاً");
}

echo "<h1>حذف المنتجات من قاعدة البيانات</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; direction: rtl; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .warning { color: orange; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; }
    button { padding: 10px 20px; margin: 5px; cursor: pointer; font-size: 16px; }
    .btn-danger { background: #dc3545; color: white; border: none; border-radius: 5px; }
    .btn-danger:hover { background: #c82333; }
    .btn-primary { background: #007bff; color: white; border: none; border-radius: 5px; }
    .btn-primary:hover { background: #0056b3; }
</style>";

// Get action from URL
$action = $_GET['action'] ?? '';

if ($action === 'delete_all') {
    // Delete all products
    try {
        $db->beginTransaction();
        
        // Delete all cart items first (foreign key constraint)
        $stmt = $db->prepare("DELETE FROM cart");
        $stmt->execute();
        $cartDeleted = $stmt->rowCount();
        
        // Delete all categories (will cascade delete products if foreign key is set)
        $stmt = $db->prepare("DELETE FROM categories");
        $stmt->execute();
        $categoriesDeleted = $stmt->rowCount();
        
        // Delete all products
        $stmt = $db->prepare("DELETE FROM products");
        $stmt->execute();
        $productsDeleted = $stmt->rowCount();
        
        $db->commit();
        
        echo "<div class='success'>";
        echo "<h2>✓ تم الحذف بنجاح!</h2>";
        echo "<p>تم حذف <strong>{$productsDeleted}</strong> منتج</p>";
        echo "<p>تم حذف <strong>{$categoriesDeleted}</strong> فئة</p>";
        echo "<p>تم حذف <strong>{$cartDeleted}</strong> عنصر من السلة</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        $db->rollBack();
        echo "<div class='error'>";
        echo "<h2>❌ حدث خطأ!</h2>";
        echo "<p>الخطأ: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
} elseif ($action === 'delete_by_category') {
    // Delete products by category
    $category = $_GET['category'] ?? '';
    
    if (empty($category)) {
        echo "<div class='error'><p>❌ يجب تحديد الفئة</p></div>";
    } else {
        try {
            $db->beginTransaction();
            
            // Get product IDs from this category
            $stmt = $db->prepare("SELECT product_id FROM categories WHERE name = ?");
            $stmt->execute([$category]);
            $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($productIds)) {
                echo "<div class='info'><p>لا توجد منتجات في هذه الفئة</p></div>";
            } else {
                // Delete from cart
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $stmt = $db->prepare("DELETE FROM cart WHERE product_id IN ($placeholders)");
                $stmt->execute($productIds);
                $cartDeleted = $stmt->rowCount();
                
                // Delete categories
                $stmt = $db->prepare("DELETE FROM categories WHERE name = ?");
                $stmt->execute([$category]);
                $categoriesDeleted = $stmt->rowCount();
                
                // Delete products
                $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                $stmt->execute($productIds);
                $productsDeleted = $stmt->rowCount();
                
                $db->commit();
                
                echo "<div class='success'>";
                echo "<h2>✓ تم الحذف بنجاح!</h2>";
                echo "<p>تم حذف <strong>{$productsDeleted}</strong> منتج من فئة: <strong>{$category}</strong></p>";
                echo "<p>تم حذف <strong>{$categoriesDeleted}</strong> فئة</p>";
                echo "<p>تم حذف <strong>{$cartDeleted}</strong> عنصر من السلة</p>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "<div class='error'>";
            echo "<h2>❌ حدث خطأ!</h2>";
            echo "<p>الخطأ: " . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
} elseif ($action === 'delete_by_id') {
    // Delete product by ID
    $productId = intval($_GET['id'] ?? 0);
    
    if ($productId <= 0) {
        echo "<div class='error'><p>❌ معرف المنتج غير صحيح</p></div>";
    } else {
        try {
            $db->beginTransaction();
            
            // Delete from cart
            $stmt = $db->prepare("DELETE FROM cart WHERE product_id = ?");
            $stmt->execute([$productId]);
            $cartDeleted = $stmt->rowCount();
            
            // Delete categories
            $stmt = $db->prepare("DELETE FROM categories WHERE product_id = ?");
            $stmt->execute([$productId]);
            $categoriesDeleted = $stmt->rowCount();
            
            // Delete product
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $productsDeleted = $stmt->rowCount();
            
            $db->commit();
            
            if ($productsDeleted > 0) {
                echo "<div class='success'>";
                echo "<h2>✓ تم الحذف بنجاح!</h2>";
                echo "<p>تم حذف المنتج برقم: <strong>{$productId}</strong></p>";
                echo "<p>تم حذف <strong>{$categoriesDeleted}</strong> فئة</p>";
                echo "<p>تم حذف <strong>{$cartDeleted}</strong> عنصر من السلة</p>";
                echo "</div>";
            } else {
                echo "<div class='info'><p>المنتج غير موجود</p></div>";
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "<div class='error'>";
            echo "<h2>❌ حدث خطأ!</h2>";
            echo "<p>الخطأ: " . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
}

// Get current products count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM products");
$stmt->execute();
$totalProducts = $stmt->fetch()['count'];

$stmt = $db->prepare("SELECT COUNT(DISTINCT name) as count FROM categories");
$stmt->execute();
$totalCategories = $stmt->fetch()['count'];

echo "<div class='info'>";
echo "<h2>إحصائيات قاعدة البيانات</h2>";
echo "<p>إجمالي المنتجات: <strong>{$totalProducts}</strong></p>";
echo "<p>إجمالي الفئات: <strong>{$totalCategories}</strong></p>";
echo "</div>";

// Get categories list
$stmt = $db->prepare("SELECT DISTINCT name, COUNT(*) as count FROM categories GROUP BY name ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($categories)) {
    echo "<h2>الفئات الموجودة:</h2>";
    echo "<ul>";
    foreach ($categories as $cat) {
        echo "<li><strong>{$cat['name']}</strong> - {$cat['count']} منتج</li>";
    }
    echo "</ul>";
}
?>

<div class="warning">
    <h3>⚠️ تحذير!</h3>
    <p>الحذف نهائي ولا يمكن التراجع عنه. تأكد من عمل نسخة احتياطية قبل الحذف.</p>
</div>

<h2>خيارات الحذف:</h2>

<div style="margin: 20px 0;">
    <h3>1. حذف جميع المنتجات:</h3>
    <button class="btn-danger" onclick="if(confirm('⚠️ هل أنت متأكد من حذف جميع المنتجات؟ هذا الإجراء لا يمكن التراجع عنه!')) { window.location.href='?action=delete_all'; }">
        حذف جميع المنتجات
    </button>
</div>

<div style="margin: 20px 0;">
    <h3>2. حذف منتجات فئة محددة:</h3>
    <?php if (!empty($categories)): ?>
        <form method="GET" style="display: inline-block;">
            <input type="hidden" name="action" value="delete_by_category">
            <select name="category" required style="padding: 8px; margin: 5px;">
                <option value="">اختر الفئة</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                        <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['count']; ?> منتج)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-danger" onclick="return confirm('⚠️ هل أنت متأكد من حذف جميع منتجات هذه الفئة؟')">
                حذف منتجات الفئة
            </button>
        </form>
    <?php else: ?>
        <p>لا توجد فئات</p>
    <?php endif; ?>
</div>

<div style="margin: 20px 0;">
    <h3>3. حذف منتج برقم محدد:</h3>
    <form method="GET" style="display: inline-block;">
        <input type="hidden" name="action" value="delete_by_id">
        <input type="number" name="id" placeholder="رقم المنتج" required style="padding: 8px; margin: 5px;">
        <button type="submit" class="btn-danger" onclick="return confirm('⚠️ هل أنت متأكد من حذف هذا المنتج؟')">
            حذف المنتج
        </button>
    </form>
</div>

<hr>
<p><a href="<?php echo SITE_URL; ?>/index.php" class="btn-primary" style="text-decoration: none; display: inline-block; padding: 10px 20px;">العودة للصفحة الرئيسية</a></p>

