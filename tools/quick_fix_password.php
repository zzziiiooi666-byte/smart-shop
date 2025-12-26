<?php
/**
 * إصلاح سريع لكلمة مرور المدير
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h1>🔧 إصلاح سريع لكلمة مرور المدير</h1>";
echo "<pre>";

try {
    $db = getDB();
    
    echo "🔄 إعادة تعيين كلمة المرور...\n\n";
    
    // إعادة تعيين كلمة المرور
    $newPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password = ?, isAdmin = TRUE WHERE email = ?");
    $result = $stmt->execute([$newPassword, 'admin@shop.com']);
    
    if ($result) {
        echo "✅ تم تحديث كلمة المرور بنجاح!\n\n";
        
        // التحقق
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['admin@shop.com']);
        $admin = $stmt->fetch();
        
        if ($admin) {
            $testPassword = password_verify('Admin@123', $admin['password']);
            echo "📧 بيانات تسجيل الدخول:\n";
            echo "   البريد: admin@shop.com\n";
            echo "   كلمة المرور: Admin@123\n";
            echo "   اختبار كلمة المرور: " . ($testPassword ? '✅ صحيح' : '❌ خاطئ') . "\n";
            echo "   صلاحيات مدير: " . ($admin['isAdmin'] ? '✅ نعم' : '❌ لا') . "\n";
        }
    } else {
        echo "❌ فشل في تحديث كلمة المرور\n";
    }
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='" . SITE_URL . "/auth/login.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>تسجيل الدخول الآن</a>";
echo "<a href='" . SITE_URL . "/tools/diagnose.php' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>التشخيص</a>";
echo "</div>";
?>

