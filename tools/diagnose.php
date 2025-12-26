<?php
/**
 * تشخيص وإصلاح مشاكل قاعدة البيانات والمدير
 */

require_once __DIR__ . '/../config/config.php';

echo "<h1>🔍 تشخيص النظام</h1>";
echo "<pre>";

// التحقق من إعدادات قاعدة البيانات
echo "=== إعدادات قاعدة البيانات ===\n";

echo "SITE_URL: " . SITE_URL . "\n";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'غير محدد') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'غير محدد') . "\n";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'غير محدد') . "\n\n";

try {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();

    echo "✅ الاتصال بقاعدة البيانات ناجح\n\n";

    // التحقق من الجداول
    echo "=== فحص الجداول ===\n";
    $tables = ['users', 'products', 'categories', 'cart', 'orders', 'order_addresses'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        echo "جدول $table: " . ($exists ? "✅ موجود" : "❌ غير موجود") . "\n";
    }
    echo "\n";

    // التحقق من حساب المدير
    echo "=== فحص حساب المدير ===\n";
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@shop.com']);
    $admin = $stmt->fetch();

    if ($admin) {
        echo "✅ حساب المدير موجود:\n";
        echo "   ID: {$admin['id']}\n";
        echo "   الاسم: {$admin['name']}\n";
        echo "   البريد: {$admin['email']}\n";
        echo "   مدير: " . ($admin['isAdmin'] ? 'نعم' : 'لا') . "\n";
        echo "   كلمة المرور مشفرة: " . (!empty($admin['password']) ? 'نعم' : 'لا') . "\n";

        // اختبار كلمة المرور
        $testPassword = password_verify('Admin@123', $admin['password']);
        echo "   اختبار كلمة المرور 'Admin@123': " . ($testPassword ? '✅ صحيح' : '❌ خاطئ') . "\n";
    } else {
        echo "❌ حساب المدير غير موجود\n";

        // إنشاء حساب المدير
        echo "\n🔧 إنشاء حساب المدير...\n";
        $hashedPassword = password_hash('Admin@123', PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, isAdmin, country)
            VALUES (?, ?, ?, TRUE, 'العراق')
        ");

        if ($stmt->execute(['مدير النظام', 'admin@shop.com', $hashedPassword])) {
            echo "✅ تم إنشاء حساب المدير بنجاح\n";
        } else {
            echo "❌ فشل في إنشاء حساب المدير\n";
        }
    }

    echo "\n";

    // فحص عدد المستخدمين والمنتجات
    echo "=== إحصائيات سريعة ===\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    echo "عدد المستخدمين: " . $stmt->fetch()['count'] . "\n";

    $stmt = $db->query("SELECT COUNT(*) as count FROM products");
    echo "عدد المنتجات: " . $stmt->fetch()['count'] . "\n";

    $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
    echo "عدد الطلبات: " . $stmt->fetch()['count'] . "\n";

} catch (Exception $e) {
    echo "❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "\n\n";

    // محاولة إنشاء قاعدة البيانات والجداول
    echo "🔧 محاولة إنشاء قاعدة البيانات...\n";

    try {
        // إنشاء اتصال بدون تحديد قاعدة بيانات
        $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // إنشاء قاعدة البيانات
        $pdo->exec("CREATE DATABASE IF NOT EXISTS shop_smart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ تم إنشاء قاعدة البيانات shop_smart\n";

        // الاتصال بقاعدة البيانات الجديدة
        $pdo->exec("USE shop_smart");

        // إنشاء الجداول
        $schemaSQL = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($schemaSQL);
        echo "✅ تم إنشاء الجداول\n";

        echo "🔄 يرجى تحديث الصفحة لإكمال التشخيص\n";

    } catch (Exception $createError) {
        echo "❌ فشل في إنشاء قاعدة البيانات: " . $createError->getMessage() . "\n";
        echo "\n💡 تأكد من:\n";
        echo "   1. تشغيل XAMPP (Apache و MySQL)\n";
        echo "   2. عدم وجود كلمة مرور لمستخدم root في MySQL\n";
        echo "   3. أو تحديث إعدادات قاعدة البيانات في config/database.php\n";
    }
}

echo "\n=== روابط مهمة ===\n";
echo "تسجيل الدخول: " . SITE_URL . "/auth/login.php\n";
echo "لوحة التحكم: " . SITE_URL . "/admin/admin.php\n";
echo "الصفحة الرئيسية: " . SITE_URL . "/pages/index.php\n";
echo "التشخيص: " . SITE_URL . "/tools/diagnose.php\n";

echo "</pre>";

// إضافة أزرار للتنقل
echo "<div style='margin-top: 20px;'>";
echo "<a href='" . SITE_URL . "/auth/login.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>تسجيل الدخول</a>";
echo "<a href='" . SITE_URL . "/admin/admin.php' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>لوحة التحكم</a>";
echo "<a href='" . SITE_URL . "/pages/index.php' style='background: #6b7280; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>الصفحة الرئيسية</a>";
echo "<a href='" . SITE_URL . "/tools/fix_admin_password.php' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>إصلاح كلمة المرور</a>";
echo "</div>";
?>
