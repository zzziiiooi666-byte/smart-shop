<?php
/**
 * Check and Create Admin Account
 * Access this file via browser to setup admin account
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$message = '';
$adminInfo = null;

try {
    $db = getDB();

    // Check if admin exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@shop.com']);
    $admin = $stmt->fetch();

    if ($admin) {
        $message = "✅ حساب المدير موجود";
        $adminInfo = $admin;
    } else {
        // Create admin account
        $hashedPassword = password_hash('Admin@123', PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, isAdmin, country)
            VALUES (?, ?, ?, TRUE, 'العراق')
        ");

        if ($stmt->execute(['مدير النظام', 'admin@shop.com', $hashedPassword])) {
            $message = "✅ تم إنشاء حساب المدير بنجاح";

            // Get the created admin
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute(['admin@shop.com']);
            $adminInfo = $stmt->fetch();
        } else {
            $message = "❌ فشل في إنشاء حساب المدير";
        }
    }

} catch (Exception $e) {
    $message = "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد حساب المدير</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .info { background: #dbeafe; color: #1e40af; border: 1px solid #60a5fa; }
        .admin-details {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .btn {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center; color: #1f2937; margin-bottom: 30px;">إعداد حساب المدير</h1>

        <div class="message <?php echo strpos($message, '✅') === 0 ? 'success' : (strpos($message, '❌') === 0 ? 'error' : 'info'); ?>">
            <?php echo $message; ?>
        </div>

        <?php if ($adminInfo): ?>
        <div class="admin-details">
            <h3>بيانات حساب المدير:</h3>
            <p><strong>الاسم:</strong> <?php echo htmlspecialchars($adminInfo['name']); ?></p>
            <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($adminInfo['email']); ?></p>
            <p><strong>كلمة المرور:</strong> Admin@123</p>
            <p><strong>صلاحيات مدير:</strong> <?php echo $adminInfo['isAdmin'] ? 'نعم' : 'لا'; ?></p>
            <p><strong>البلد:</strong> <?php echo htmlspecialchars($adminInfo['country'] ?? 'غير محدد'); ?></p>
            <p><strong>تاريخ التسجيل:</strong> <?php echo date('Y-m-d H:i:s', strtotime($adminInfo['created_at'])); ?></p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn">تسجيل الدخول</a>
            <a href="<?php echo SITE_URL; ?>/admin/admin.php" class="btn">لوحة التحكم</a>
            <a href="<?php echo SITE_URL; ?>/pages/index.php" class="btn" style="background: #6b7280;">الصفحة الرئيسية</a>
        </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding: 20px; background: #fefce8; border: 1px solid #fbbf24; border-radius: 5px;">
            <h4 style="color: #92400e; margin-top: 0;">نصائح لاستكشاف الأخطاء:</h4>
            <ul style="color: #92400e;">
                <li>تأكد من تشغيل XAMPP (Apache و MySQL)</li>
                <li>تأكد من إنشاء قاعدة البيانات <code>shop_smart</code></li>
                <li>شغل ملف <code>schema.sql</code> في phpMyAdmin</li>
                <li>جرب تسجيل الدخول باستخدام admin@shop.com و Admin@123</li>
            </ul>
        </div>
    </div>
</body>
</html>
