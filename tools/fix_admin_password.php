<?php
/**
 * إصلاح كلمة مرور المدير
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h1>🔧 إصلاح كلمة مرور المدير</h1>";
echo "<pre>";

try {
    $db = getDB();

    // التحقق من وجود حساب المدير
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@shop.com']);
    $admin = $stmt->fetch();

    if (!$admin) {
        echo "⚠️ حساب المدير غير موجود\n";
        echo "🔧 إنشاء حساب المدير...\n";
        
        $hashedPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, isAdmin, country)
            VALUES (?, ?, ?, TRUE, 'العراق')
        ");
        
        if ($stmt->execute(['مدير النظام', 'admin@shop.com', $hashedPassword])) {
            echo "✅ تم إنشاء حساب المدير بنجاح\n";
            
            // التحقق من الحساب الجديد
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute(['admin@shop.com']);
            $admin = $stmt->fetch();
        } else {
            echo "❌ فشل في إنشاء حساب المدير\n";
            throw new Exception("فشل في إنشاء حساب المدير");
        }
    } else {
        echo "✅ حساب المدير موجود:\n";
        echo "   ID: {$admin['id']}\n";
        echo "   الاسم: {$admin['name']}\n";
        echo "   البريد: {$admin['email']}\n";
        echo "   صلاحيات مدير: " . ($admin['isAdmin'] ? 'نعم ✅' : 'لا ❌') . "\n";
        
        // إذا لم يكن مديراً، جعله مديراً
        if (!$admin['isAdmin']) {
            echo "\n🔧 جعل المستخدم مديراً...\n";
            $stmt = $db->prepare("UPDATE users SET isAdmin = TRUE WHERE email = ?");
            $stmt->execute(['admin@shop.com']);
            echo "✅ تم تحديث صلاحيات المدير\n";
        }
        
        $currentPassword = $admin['password'];
        echo "\nكلمة المرور الحالية مشفرة: " . substr($currentPassword, 0, 20) . "...\n";

        // اختبار كلمات مرور مختلفة
        $testPasswords = ['Admin@123', 'admin123', 'password', 'admin', 'Admin123'];
        $foundPassword = false;

        echo "\n🔍 اختبار كلمات مرور مختلفة:\n";
        foreach ($testPasswords as $testPass) {
            $isValid = password_verify($testPass, $currentPassword);
            echo "   '$testPass': " . ($isValid ? '✅ صحيح' : '❌ خاطئ') . "\n";
            if ($isValid) {
                $foundPassword = true;
            }
        }

        // إعادة تعيين كلمة المرور إذا لم تكن صحيحة
        if (!$foundPassword) {
            echo "\n🔄 إعادة تعيين كلمة المرور إلى 'Admin@123'...\n";
            $newPassword = password_hash('Admin@123', PASSWORD_DEFAULT);

            $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $result = $stmt->execute([$newPassword, 'admin@shop.com']);

            if ($result) {
                echo "✅ تم تحديث كلمة مرور المدير بنجاح\n";

                // التحقق من التحديث
                $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
                $stmt->execute(['admin@shop.com']);
                $updatedPassword = $stmt->fetch()['password'];

                $testNewPassword = password_verify('Admin@123', $updatedPassword);
                echo "   اختبار كلمة المرور الجديدة 'Admin@123': " . ($testNewPassword ? '✅ صحيح' : '❌ خاطئ') . "\n";
            } else {
                echo "❌ فشل في تحديث كلمة المرور\n";
            }
        } else {
            echo "\n✅ كلمة المرور الحالية صحيحة!\n";
        }
    }

    echo "\n📧 بيانات تسجيل الدخول للمدير:\n";
    echo "   البريد الإلكتروني: admin@shop.com\n";
    echo "   كلمة المرور: Admin@123\n";

} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n=== روابط ===\n";
echo "تسجيل الدخول: " . SITE_URL . "/auth/login.php\n";
echo "التشخيص: " . SITE_URL . "/tools/diagnose.php\n";

echo "</pre>";

// أزرار التنقل
echo "<div style='margin-top: 20px;'>";
echo "<a href='" . SITE_URL . "/auth/login.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>تسجيل الدخول</a>";
echo "<a href='" . SITE_URL . "/tools/diagnose.php' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>التشخيص</a>";
echo "<a href='" . SITE_URL . "/pages/index.php' style='background: #6b7280; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>الصفحة الرئيسية</a>";
echo "</div>";
?>
