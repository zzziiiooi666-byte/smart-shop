# دليل الإعداد السريع

## خطوات الإعداد

### 1. متطلبات النظام
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- XAMPP (موصى به)

### 2. تثبيت المشروع

```bash
# Clone المشروع
git clone https://github.com/YOUR_USERNAME/smart_markt.git

# انتقل إلى المجلد
cd smart_markt
```

### 3. إعداد قاعدة البيانات

1. افتح phpMyAdmin: `http://localhost/phpmyadmin`
2. أنشئ قاعدة بيانات جديدة: `shop_smart`
3. استورد الملف: `database/schema.sql`

### 4. تعديل الإعدادات

#### ملف `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'shop_smart');
define('DB_USER', 'root');
define('DB_PASS', '');
```

#### ملف `config/config.php`:
```php
define('SITE_URL', 'http://localhost/smart_markt');
```

### 5. إنشاء حساب المدير

افتح المتصفح:
```
http://localhost/smart_markt/tools/fix_admin_password.php
```

**البيانات الافتراضية:**
- البريد: `admin@shop.com`
- كلمة المرور: `Admin@123`

### 6. تشغيل النظام

افتح المتصفح:
```
http://localhost/smart_markt/pages/index.php
```

## استكشاف الأخطاء

### مشكلة في الاتصال بقاعدة البيانات
- تحقق من إعدادات `config/database.php`
- تأكد من تشغيل MySQL
- تحقق من اسم قاعدة البيانات

### صفحة فارغة
- تحقق من سجلات الأخطاء في PHP
- تأكد من تفعيل `display_errors` في PHP

### مشكلة في الصور
- تحقق من صلاحيات مجلد `assets/images/`
- تأكد من وجود المجلدات المطلوبة

## الدعم

للمساعدة، افتح Issue على GitHub.

