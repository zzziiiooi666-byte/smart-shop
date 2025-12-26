<?php
/**
 * إعادة إنشاء قاعدة البيانات من الصفر
 */

require_once __DIR__ . '/../config/config.php';

echo "<h1>🔄 إعادة إنشاء قاعدة البيانات</h1>";
echo "<pre>";

try {
    // إنشاء اتصال بدون تحديد قاعدة بيانات
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ الاتصال بـ MySQL ناجح\n";

    // حذف قاعدة البيانات إذا كانت موجودة
    $pdo->exec("DROP DATABASE IF EXISTS shop_smart");
    echo "🗑️ تم حذف قاعدة البيانات shop_smart (إن وجدت)\n";

    // إنشاء قاعدة البيانات الجديدة
    $pdo->exec("CREATE DATABASE shop_smart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ تم إنشاء قاعدة البيانات shop_smart\n";

    // الاتصال بقاعدة البيانات الجديدة
    $pdo->exec("USE shop_smart");

    // إنشاء الجداول
    $schemaSQL = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($schemaSQL);
    echo "✅ تم إنشاء الجداول\n";

    // إضافة حساب المدير
    $hashedPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, isAdmin, country)
        VALUES (?, ?, ?, TRUE, 'العراق')
    ");
    $stmt->execute(['مدير النظام', 'admin@shop.com', $hashedPassword]);
    echo "✅ تم إنشاء حساب المدير\n";

    // إضافة المنتجات (اختياري)
    echo "\n🔄 إضافة المنتجات...\n";
    $productsSQL = file_get_contents(__DIR__ . '/../database/insert_products.sql');
    $pdo->exec($productsSQL);
    echo "✅ تم إضافة المنتجات\n";

    echo "\n🎉 تم إعادة إنشاء قاعدة البيانات بنجاح!\n\n";

    echo "📧 بيانات تسجيل الدخول للمدير:\n";
    echo "   البريد الإلكتروني: admin@shop.com\n";
    echo "   كلمة المرور: Admin@123\n\n";

    echo "🌐 روابط مهمة:\n";
    echo "   تسجيل الدخول: http://localhost/smart_markt/auth/login.php\n";
    echo "   لوحة التحكم: http://localhost/smart_markt/admin/admin.php\n";

} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n\n";

    echo "💡 تأكد من:\n";
    echo "   1. تشغيل XAMPP (خاصة MySQL)\n";
    echo "   2. عدم وجود كلمة مرور لمستخدم root\n";
    echo "   3. أن MySQL يعمل على المنفذ 3306\n";
}

echo "</pre>";

// إضافة أزرار للتنقل
echo "<div style='margin-top: 20px;'>";
echo "<a href='" . SITE_URL . "/auth/login.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>تسجيل الدخول</a>";
echo "<a href='" . SITE_URL . "/tools/diagnose.php' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>التشخيص</a>";
echo "<a href='" . SITE_URL . "/pages/index.php' style='background: #6b7280; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>الصفحة الرئيسية</a>";
echo "</div>";
?>
