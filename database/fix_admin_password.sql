-- إصلاح كلمة مرور المدير
-- كلمة المرور الجديدة: Admin@123

USE shop_smart;

-- تحديث كلمة مرور المدير
-- كلمة المرور: Admin@123 (مشفرة بـ bcrypt)
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@shop.com';

-- التأكد من أن المستخدم مدير
UPDATE users 
SET isAdmin = TRUE 
WHERE email = 'admin@shop.com';

-- التحقق من التحديث
SELECT id, name, email, isAdmin, 
       CASE 
           WHEN password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
           THEN '✅ تم التحديث'
           ELSE '❌ لم يتم التحديث'
       END as status
FROM users 
WHERE email = 'admin@shop.com';

