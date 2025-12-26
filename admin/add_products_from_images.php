<?php
/**
 * Script to add products from available images
 * Each category will have 5 products
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// Check if admin user exists, if not create it
$stmt = $db->prepare("SELECT id FROM users WHERE isAdmin = 1 LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch();

if (!$admin) {
    // Create admin user
    $stmt = $db->prepare("INSERT INTO users (name, email, password, isAdmin) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Admin', 'admin@shop.com', password_hash('Admin@123', PASSWORD_DEFAULT), true]);
    $adminId = $db->lastInsertId();
} else {
    $adminId = $admin['id'];
}

$siteUrl = SITE_URL;

// Products data
$products = [
    // ملابس رجالية (5 products)
    [
        'name' => 'قميص رجالي كلاسيكي',
        'description' => 'قميص رجالي أنيق مصنوع من القطن عالي الجودة، مناسب للاستخدام اليومي والمناسبات',
        'price' => 45000,
        'mainImage' => $siteUrl . '/assets/images/product-1-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-1-2.jpg'],
        'quantity' => 15,
        'sizes' => ['S', 'M', 'L', 'XL'],
        'colors' => ['أبيض', 'أزرق', 'رمادي'],
        'category' => 'ملابس-رجالية'
    ],
    [
        'name' => 'بنطال جينز مريح',
        'description' => 'بنطال جينز عالي الجودة مريح ومناسب للاستخدام اليومي',
        'price' => 65000,
        'mainImage' => $siteUrl . '/assets/images/product-2-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-2-2.jpg'],
        'quantity' => 12,
        'sizes' => ['30', '32', '34', '36', '38'],
        'colors' => ['أزرق داكن', 'أزرق فاتح', 'أسود'],
        'category' => 'ملابس-رجالية'
    ],
    [
        'name' => 'حذاء رياضي',
        'description' => 'حذاء رياضي ومريح مصنوع من مواد عالية الجودة',
        'price' => 120000,
        'mainImage' => $siteUrl . '/assets/images/product-3-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-3-2.jpg'],
        'quantity' => 8,
        'sizes' => ['M', 'L', 'XL', 'XXL'],
        'colors' => ['أسود', 'رمادي', 'بني'],
        'category' => 'ملابس-رجالية'
    ],
    [
        'name' => 'تيشرت قطني',
        'description' => 'تيشرت قطني مريح ومناسب للاستخدام اليومي والرياضي',
        'price' => 25000,
        'mainImage' => $siteUrl . '/assets/images/product-4-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-4-2.jpg'],
        'quantity' => 20,
        'sizes' => ['S', 'M', 'L', 'XL'],
        'colors' => ['أبيض', 'أسود', 'أحمر', 'أزرق'],
        'category' => 'ملابس-رجالية'
    ],
    [
        'name' => 'بدلة رسمية أنيقة',
        'description' => 'بدلة رسمية أنيقة للمناسبات الرسمية والاجتماعات المهمة',
        'price' => 180000,
        'mainImage' => $siteUrl . '/assets/images/product-5-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-5-2.jpg'],
        'quantity' => 5,
        'sizes' => ['M', 'L', 'XL'],
        'colors' => ['أسود', 'رمادي', 'بني'],
        'category' => 'ملابس-رجالية'
    ],
    
    // ملابس نسائية (5 products)
    [
        'name' => 'قميص  صيفي أنيق',
        'description' => 'قميص صيفي أنيق ومريح مناسب للمناسبات والاستخدام اليومي',
        'price' => 55000,
        'mainImage' => $siteUrl . '/assets/images/product-6-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-6-2.jpg'],
        'quantity' => 18,
        'sizes' => ['S', 'M', 'L', 'XL'],
        'colors' => ['أحمر', 'أزرق', 'أخضر', 'وردي'],
        'category' => 'ملابس-نسائية'
    ],
    [
        'name' => 'بلوزة نسائية أنيقة',
        'description' => 'بلوزة نسائية أنيقة مصنوعة من مواد عالية الجودة',
        'price' => 35000,
        'mainImage' => $siteUrl . '/assets/images/product-7-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-7-2.jpg'],
        'quantity' => 15,
        'sizes' => ['S', 'M', 'L', 'XL'],
        'colors' => ['أبيض', 'وردي', 'أسود', 'أزرق'],
        'category' => 'ملابس-نسائية'
    ],
    [
        'name' => 'تنورة أنيقة',
        'description' => 'تنورة أنيقة ومريحة للاستخدام اليومي والمناسبات',
        'price' => 40000,
        'mainImage' => $siteUrl . '/assets/images/product-8-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-8-2.jpg'],
        'quantity' => 12,
        'sizes' => ['S', 'M', 'L'],
        'colors' => ['أسود', 'رمادي', 'أزرق', 'أخضر'],
        'category' => 'ملابس-نسائية'
    ],
    [
        'name' => 'عباية تقليدية',
        'description' => 'عباية تقليدية أنيقة مصنوعة يدوياً بجودة عالية',
        'price' => 75000,
        'mainImage' => $siteUrl . '/assets/images/product-9-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-9-2.jpg'],
        'quantity' => 10,
        'sizes' => ['M', 'L', 'XL'],
        'colors' => ['أسود', 'رمادي', 'بني', 'أزرق'],
        'category' => 'ملابس-نسائية'
    ],
    [
        'name' => 'فستان زفاف فاخر',
        'description' => 'فستان زفاف فاخر مزين بالتطريز اليدوي والتفاصيل الراقية',
        'price' => 250000,
        'mainImage' => $siteUrl . '/assets/images/product-10-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-10-2.jpg'],
        'quantity' => 3,
        'sizes' => ['S', 'M', 'L'],
        'colors' => ['أبيض', 'عاجي', 'ذهبي'],
        'category' => 'ملابس-نسائية'
    ],
    
    // أحذية (5 products)
    [
        'name' => 'حذاء رياضي مريح',
        'description' => 'حذاء رياضي مريح ومناسب للمشي والرياضة اليومية',
        'price' => 85000,
        'mainImage' => $siteUrl . '/assets/images/product-11-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-11-2.jpg'],
        'quantity' => 20,
        'sizes' => ['40', '41', '42', '43', '44', '45'],
        'colors' => ['أسود', 'أبيض', 'أزرق'],
        'category' => 'أحذية'
    ],
    [
        'name' => 'حذاء رسمي أنيق',
        'description' => 'حذاء رسمي أنيق للمناسبات الرسمية والاجتماعات',
        'price' => 95000,
        'mainImage' => $siteUrl . '/assets/images/product-12-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-12-2.jpg'],
        'quantity' => 15,
        'sizes' => ['40', '41', '42', '43', '44'],
        'colors' => ['أسود', 'بني'],
        'category' => 'أحذية'
    ],
    [
        'name' => 'صندل صيفي',
        'description' => 'صندل صيفي مريح ومناسب للاستخدام اليومي',
        'price' => 35000,
        'mainImage' => $siteUrl . '/assets/images/product-13-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/product-13-2.jpg'],
        'quantity' => 25,
        'sizes' => ['38', '39', '40', '41', '42'],
        'colors' => ['أسود', 'بني', 'أبيض'],
        'category' => 'أحذية'
    ],
    [
        'name' => 'حذاء نسائي أنيق',
        'description' => 'حذاء نسائي أنيق ومريح مناسب للمناسبات',
        'price' => 65000,
        'mainImage' => $siteUrl . '/assets/images/showcase-img-1.jpg',
        'otherImages' => [$siteUrl . '/assets/images/showcase-img-2.jpg'],
        'quantity' => 18,
        'sizes' => ['36', '37', '38', '39', '40'],
        'colors' => ['أسود', 'بني', 'أحمر'],
        'category' => 'أحذية'
    ],
    [
        'name' => 'حذاء رياضي نسائي',
        'description' => 'حذاء رياضي نسائي مريح ومناسب للرياضة والمشي',
        'price' => 75000,
        'mainImage' => $siteUrl . '/assets/images/showcase-img-3.jpg',
        'otherImages' => [$siteUrl . '/assets/images/showcase-img-4.jpg'],
        'quantity' => 22,
        'sizes' => ['36', '37', '38', '39', '40', '41'],
        'colors' => ['أبيض', 'وردي', 'أزرق'],
        'category' => 'أحذية'
    ],
    
    // إلكترونيات (5 products)
    [
        'name' => 'هاتف ذكي متطور',
        'description' => 'هاتف ذكي متطور مع كاميرا عالية الجودة وشاشة كبيرة',
        'price' => 450000,
        'mainImage' => $siteUrl . '/assets/images/deals-1.jpg',
        'otherImages' => [],
        'quantity' => 30,
        'sizes' => [],
        'colors' => ['أسود', 'أزرق', 'أحمر'],
        'category' => 'إلكترونيات'
    ],
    [
        'name' => 'لابتوب قوي',
        'description' => 'لابتوب قوي ومناسب للعمل والدراسة والألعاب',
        'price' => 650000,
        'mainImage' => $siteUrl . '/assets/images/deals-2.png',
        'otherImages' => [],
        'quantity' => 20,
        'sizes' => [],
        'colors' => ['أسود', 'رمادي'],
        'category' => 'إلكترونيات'
    ],
    [
        'name' => 'سماعات لاسلكية',
        'description' => 'سماعات لاسلكية عالية الجودة مع صوت واضح',
        'price' => 80000,
        'mainImage' => $siteUrl . '/assets/images/category-1.jpg',
        'otherImages' => [],
        'quantity' => 35,
        'sizes' => [],
        'colors' => ['أسود', 'أبيض', 'أزرق'],
        'category' => 'إلكترونيات'
    ],
    [
        'name' => 'تابلت تعليمي',
        'description' => 'تابلت تعليمي مع شاشة كبيرة ومناسبة للقراءة',
        'price' => 350000,
        'mainImage' => $siteUrl . '/assets/images/category-2.jpg',
        'otherImages' => [],
        'quantity' => 25,
        'sizes' => [],
        'colors' => ['أسود', 'أزرق'],
        'category' => 'إلكترونيات'
    ],
    [
        'name' => 'ساعة ذكية',
        'description' => 'ساعة ذكية مع تتبع اللياقة البدنية وإشعارات الهاتف',
        'price' => 150000,
        'mainImage' => $siteUrl . '/assets/images/category-3.jpg',
        'otherImages' => [],
        'quantity' => 40,
        'sizes' => [],
        'colors' => ['أسود', 'فضي', 'ذهبي'],
        'category' => 'إلكترونيات'
    ],
    
    // أجهزة منزلية (5 products)
    [
        'name' => 'غسالة أطباق',
        'description' => 'غسالة أطباق حديثة مع تقنيات متطورة',
        'price' => 550000,
        'mainImage' => $siteUrl . '/assets/images/category-4.jpg',
        'otherImages' => [],
        'quantity' => 10,
        'sizes' => [],
        'colors' => ['أبيض', 'فضي'],
        'category' => 'أجهزة-منزلية'
    ],
    [
        'name' => 'مكيف هواء',
        'description' => 'مكيف هواء قوي ومناسب للمنازل والمكاتب',
        'price' => 850000,
        'mainImage' => $siteUrl . '/assets/images/category-5.jpg',
        'otherImages' => [],
        'quantity' => 8,
        'sizes' => [],
        'colors' => ['أبيض'],
        'category' => 'أجهزة-منزلية'
    ],
    [
        'name' => 'فرن كهربائي',
        'description' => 'فرن كهربائي حديث مع تقنيات متطورة',
        'price' => 450000,
        'mainImage' => $siteUrl . '/assets/images/category-6.jpg',
        'otherImages' => [],
        'quantity' => 12,
        'sizes' => [],
        'colors' => ['أسود', 'فضي'],
        'category' => 'أجهزة-منزلية'
    ],
    [
        'name' => 'ثلاجة حديثة',
        'description' => 'ثلاجة حديثة مع تقنيات التبريد المتطورة',
        'price' => 1200000,
        'mainImage' => $siteUrl . '/assets/images/category-7.jpg',
        'otherImages' => [],
        'quantity' => 6,
        'sizes' => [],
        'colors' => ['أبيض', 'فضي'],
        'category' => 'أجهزة-منزلية'
    ],
    [
        'name' => 'غسالة ملابس',
        'description' => 'غسالة ملابس حديثة مع برامج متعددة',
        'price' => 650000,
        'mainImage' => $siteUrl . '/assets/images/category-8.jpg',
        'otherImages' => [],
        'quantity' => 10,
        'sizes' => [],
        'colors' => ['أبيض', 'فضي'],
        'category' => 'أجهزة-منزلية'
    ],
    
    // أثاث (5 products)
    [
        'name' => 'كنبة مريحة',
        'description' => 'كنبة مريحة وأنيقة مناسبة للصالة مع تصميم عصري',
        'price' => 850000,
        'mainImage' => $siteUrl . '/assets/images/showcase-img-5.jpg',
        'otherImages' => [],
        'quantity' => 8,
        'sizes' => [],
        'colors' => ['بني', 'رمادي', 'أسود'],
        'category' => 'أثاث'
    ],
    [
        'name' => 'طاولة قهوة',
        'description' => 'طاولة قهوة أنيقة مصنوعة من الخشب عالي الجودة',
        'price' => 250000,
        'mainImage' => $siteUrl . '/assets/images/showcase-img-6.jpg',
        'otherImages' => [],
        'quantity' => 12,
        'sizes' => [],
        'colors' => ['بني', 'أسود'],
        'category' => 'أثاث'
    ],
    [
        'name' => 'كرسي مكتب',
        'description' => 'كرسي مكتب مريح ومناسب للعمل لساعات طويلة',
        'price' => 180000,
        'mainImage' => $siteUrl . '/assets/images/showcase-img-7.jpg',
        'otherImages' => [],
        'quantity' => 15,
        'sizes' => [],
        'colors' => ['أسود', 'رمادي', 'أزرق'],
        'category' => 'أثاث'
    ],
    [
        'name' => 'خزانة ملابس',
        'description' => 'خزانة ملابس كبيرة مع أدراج متعددة',
        'price' => 650000,
        'mainImage' => $siteUrl . '/assets/images/showcase-img-8.jpg',
        'otherImages' => [],
        'quantity' => 6,
        'sizes' => [],
        'colors' => ['بني', 'أبيض'],
        'category' => 'أثاث'
    ],
    [
        'name' => 'سرير مريح',
        'description' => 'سرير مريح مع مرتبة عالية الجودة',
        'price' => 1200000,
        'mainImage' => $siteUrl . '/assets/images/showcase-img-9.jpg',
        'otherImages' => [],
        'quantity' => 5,
        'sizes' => [],
        'colors' => ['بني', 'أبيض', 'رمادي'],
        'category' => 'أثاث'
    ],
    
    // مستحضرات تجميل (5 products)
    [
        'name' => 'كريم مرطب للوجه',
        'description' => 'كريم مرطب للوجه بتركيبة طبيعية للبشرة الجافة',
        'price' => 35000,
        'mainImage' => $siteUrl . '/assets/images/avatar-1.jpg',
        'otherImages' => [],
        'quantity' => 50,
        'sizes' => [],
        'colors' => [],
        'category' => 'مستحضرات-تجميل'
    ],
    [
        'name' => 'ماسك للوجه',
        'description' => 'ماسك للوجه من الطين الطبيعي لتنظيف البشرة',
        'price' => 25000,
        'mainImage' => $siteUrl . '/assets/images/avatar-2.jpg',
        'otherImages' => [],
        'quantity' => 40,
        'sizes' => [],
        'colors' => [],
        'category' => 'مستحضرات-تجميل'
    ],
    [
        'name' => 'سيروم فيتامين سي',
        'description' => 'سيروم فيتامين سي لتفتيح البشرة وتوحيد اللون',
        'price' => 55000,
        'mainImage' => $siteUrl . '/assets/images/avatar-3.jpg',
        'otherImages' => [],
        'quantity' => 35,
        'sizes' => [],
        'colors' => [],
        'category' => 'مستحضرات-تجميل'
    ],
    [
        'name' => 'أحمر شفاه طويل الأمد',
        'description' => 'أحمر شفاه طويل الأمد مع تركيبة مرطبة',
        'price' => 20000,
        'mainImage' => $siteUrl . '/assets/images/category-1.jpg',
        'otherImages' => [],
        'quantity' => 60,
        'sizes' => [],
        'colors' => ['أحمر', 'وردي', 'بني', 'نبيتي'],
        'category' => 'مستحضرات-تجميل'
    ],
    [
        'name' => 'كونسيلر عالي التغطية',
        'description' => 'كونسيلر عالي التغطية لإخفاء العيوب والهالات',
        'price' => 30000,
        'mainImage' => $siteUrl . '/assets/images/category-2.jpg',
        'otherImages' => [],
        'quantity' => 45,
        'sizes' => [],
        'colors' => ['بيج فاتح', 'بيج متوسط', 'بيج داكن'],
        'category' => 'مستحضرات-تجميل'
    ],
    
    // عطور (5 products)
    [
        'name' => 'عطر رجالي فاخر',
        'description' => 'عطر رجالي فاخر برائحة خشبية ومسكية',
        'price' => 85000,
        'mainImage' => $siteUrl . '/assets/images/category-3.jpg',
        'otherImages' => [],
        'quantity' => 30,
        'sizes' => ['50 مل', '100 مل'],
        'colors' => [],
        'category' => 'عطور'
    ],
    [
        'name' => 'عطر نسائي زهري',
        'description' => 'عطر نسائي برائحة زهرية عطرية ومنعشة',
        'price' => 95000,
        'mainImage' => $siteUrl . '/assets/images/category-4.jpg',
        'otherImages' => [],
        'quantity' => 28,
        'sizes' => ['50 مل', '100 مل'],
        'colors' => [],
        'category' => 'عطور'
    ],
    [
        'name' => 'عطر عائلي',
        'description' => 'عطر عائلي برائحة منعشة ومناسبة للجميع',
        'price' => 65000,
        'mainImage' => $siteUrl . '/assets/images/category-5.jpg',
        'otherImages' => [],
        'quantity' => 35,
        'sizes' => ['50 مل', '100 مل'],
        'colors' => [],
        'category' => 'عطور'
    ],
    [
        'name' => 'عطر صيفي منعش',
        'description' => 'عطر صيفي منعش برائحة الحمضيات',
        'price' => 75000,
        'mainImage' => $siteUrl . '/assets/images/category-6.jpg',
        'otherImages' => [],
        'quantity' => 32,
        'sizes' => ['50 مل', '100 مل'],
        'colors' => [],
        'category' => 'عطور'
    ],
    [
        'name' => 'عطر فاخر للنساء',
        'description' => 'عطر فاخر للنساء برائحة فواكهية ومسكية',
        'price' => 120000,
        'mainImage' => $siteUrl . '/assets/images/category-7.jpg',
        'otherImages' => [],
        'quantity' => 25,
        'sizes' => ['50 مل', '100 مل'],
        'colors' => [],
        'category' => 'عطور'
    ],
    
    // ألعاب (5 products)
    [
        'name' => 'لعبة أطفال تعليمية',
        'description' => 'لعبة أطفال تعليمية تساعد في تطوير المهارات',
        'price' => 45000,
        'mainImage' => $siteUrl . '/assets/images/category-8.jpg',
        'otherImages' => [],
        'quantity' => 40,
        'sizes' => [],
        'colors' => ['متعدد الألوان'],
        'category' => 'ألعاب'
    ],
    [
        'name' => 'لعبة فيديو',
        'description' => 'لعبة فيديو حديثة مع رسومات عالية الجودة',
        'price' => 75000,
        'mainImage' => $siteUrl . '/assets/images/deals-1.jpg',
        'otherImages' => [],
        'quantity' => 50,
        'sizes' => [],
        'colors' => [],
        'category' => 'ألعاب'
    ],
    [
        'name' => 'دمية محشوة',
        'description' => 'دمية محشوة ناعمة ومريحة للأطفال',
        'price' => 30000,
        'mainImage' => $siteUrl . '/assets/images/deals-2.png',
        'otherImages' => [],
        'quantity' => 35,
        'sizes' => [],
        'colors' => ['بني', 'أبيض', 'وردي'],
        'category' => 'ألعاب'
    ],
    [
        'name' => 'سيارة أطفال',
        'description' => 'سيارة أطفال كهربائية للعب والمرح',
        'price' => 250000,
        'mainImage' => $siteUrl . '/assets/images/home-img.png',
        'otherImages' => [],
        'quantity' => 10,
        'sizes' => [],
        'colors' => ['أحمر', 'أزرق', 'أصفر'],
        'category' => 'ألعاب'
    ],
    [
        'name' => 'بازل تعليمي',
        'description' => 'بازل تعليمي لتنمية مهارات التفكير',
        'price' => 20000,
        'mainImage' => $siteUrl . '/assets/images/payment-method.png',
        'otherImages' => [],
        'quantity' => 45,
        'sizes' => [],
        'colors' => [],
        'category' => 'ألعاب'
    ],
    
    // كتب (5 products)
    [
        'name' => 'رواية عربية',
        'description' => 'رواية عربية حديثة من الأدب المعاصر',
        'price' => 25000,
        'mainImage' => $siteUrl . '/assets/images/logo.svg',
        'otherImages' => [],
        'quantity' => 30,
        'sizes' => [],
        'colors' => [],
        'category' => 'كتب'
    ],
    [
        'name' => 'كتاب تعليمي',
        'description' => 'كتاب تعليمي في البرمجة والتقنية',
        'price' => 35000,
        'mainImage' => $siteUrl . '/assets/images/search.png',
        'otherImages' => [],
        'quantity' => 25,
        'sizes' => [],
        'colors' => [],
        'category' => 'كتب'
    ],
    [
        'name' => 'موسوعة علمية',
        'description' => 'موسوعة علمية شاملة في مختلف المجالات',
        'price' => 85000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 15,
        'sizes' => [],
        'colors' => [],
        'category' => 'كتب'
    ],
    [
        'name' => 'كتاب طبخ',
        'description' => 'كتاب طبخ يحتوي على وصفات متنوعة',
        'price' => 40000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 20,
        'sizes' => [],
        'colors' => [],
        'category' => 'كتب'
    ],
    [
        'name' => 'كتاب تاريخ',
        'description' => 'كتاب تاريخ شامل عن الحضارات القديمة',
        'price' => 45000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 18,
        'sizes' => [],
        'colors' => [],
        'category' => 'كتب'
    ],
    
    // رياضة (5 products)
    [
        'name' => 'كرة قدم',
        'description' => 'كرة قدم عالية الجودة مناسبة للملاعب',
        'price' => 45000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 30,
        'sizes' => [],
        'colors' => ['أبيض', 'أزرق', 'أحمر'],
        'category' => 'رياضة'
    ],
    [
        'name' => 'مضرب تنس',
        'description' => 'مضرب تنس احترافي مع حقيبة',
        'price' => 85000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 20,
        'sizes' => [],
        'colors' => [],
        'category' => 'رياضة'
    ],
    [
        'name' => 'دراجة هوائية',
        'description' => 'دراجة هوائية مريحة ومناسبة للرياضة',
        'price' => 450000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 8,
        'sizes' => ['صغير', 'متوسط', 'كبير'],
        'colors' => ['أحمر', 'أزرق', 'أسود'],
        'category' => 'رياضة'
    ],
    [
        'name' => 'أوزان رياضية',
        'description' => 'مجموعة أوزان رياضية للتمرين في المنزل',
        'price' => 120000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 15,
        'sizes' => [],
        'colors' => [],
        'category' => 'رياضة'
    ],
    [
        'name' => 'سجادة يوغا',
        'description' => 'سجادة يوغا مريحة ومناسبة للتمارين',
        'price' => 35000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 40,
        'sizes' => [],
        'colors' => ['أزرق', 'وردي', 'أخضر'],
        'category' => 'رياضة'
    ],
    
    // صحة وتجميل (5 products)
    [
        'name' => 'مكمل غذائي',
        'description' => 'مكمل غذائي غني بالفيتامينات والمعادن',
        'price' => 55000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 50,
        'sizes' => [],
        'colors' => [],
        'category' => 'صحة-وتجميل'
    ],
    [
        'name' => 'شامبو طبيعي',
        'description' => 'شامبو طبيعي للشعر بتركيبة عضوية',
        'price' => 30000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 45,
        'sizes' => [],
        'colors' => [],
        'category' => 'صحة-وتجميل'
    ],
    [
        'name' => 'معجون أسنان',
        'description' => 'معجون أسنان بفلورايد لحماية الأسنان',
        'price' => 15000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 60,
        'sizes' => [],
        'colors' => [],
        'category' => 'صحة-وتجميل'
    ],
    [
        'name' => 'فرشاة أسنان كهربائية',
        'description' => 'فرشاة أسنان كهربائية مع تقنية تنظيف متطورة',
        'price' => 85000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 25,
        'sizes' => [],
        'colors' => ['أبيض', 'أزرق', 'وردي'],
        'category' => 'صحة-وتجميل'
    ],
    [
        'name' => 'ميزان ذكي',
        'description' => 'ميزان ذكي مع تطبيق لتتبع الوزن',
        'price' => 95000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 20,
        'sizes' => [],
        'colors' => ['أبيض', 'أسود'],
        'category' => 'صحة-وتجميل'
    ],
    
    // أدوات منزلية (5 products)
    [
        'name' => 'مجموعة سكاكين',
        'description' => 'مجموعة سكاكين احترافية للمطبخ',
        'price' => 75000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 30,
        'sizes' => [],
        'colors' => [],
        'category' => 'أدوات-منزلية'
    ],
    [
        'name' => 'طقم أواني طبخ',
        'description' => 'طقم أواني طبخ غير لاصقة عالية الجودة',
        'price' => 150000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 15,
        'sizes' => [],
        'colors' => [],
        'category' => 'أدوات-منزلية'
    ],
    [
        'name' => 'خلاط كهربائي',
        'description' => 'خلاط كهربائي قوي مع عدة سرعات',
        'price' => 85000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 20,
        'sizes' => [],
        'colors' => ['أبيض', 'أسود', 'أحمر'],
        'category' => 'أدوات-منزلية'
    ],
    [
        'name' => 'مكنسة كهربائية',
        'description' => 'مكنسة كهربائية قوية مع كيس قابل للإزالة',
        'price' => 180000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 12,
        'sizes' => [],
        'colors' => ['أحمر', 'أزرق'],
        'category' => 'أدوات-منزلية'
    ],
    [
        'name' => 'طقم أكواب',
        'description' => 'طقم أكواب زجاجية أنيقة للضيافة',
        'price' => 45000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 35,
        'sizes' => [],
        'colors' => [],
        'category' => 'أدوات-منزلية'
    ],
    
    // سيارات (5 products)
    [
        'name' => 'إطارات سيارات',
        'description' => 'إطارات سيارات عالية الجودة مناسبة لجميع الطرق',
        'price' => 450000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 20,
        'sizes' => ['15 بوصة', '16 بوصة', '17 بوصة'],
        'colors' => [],
        'category' => 'سيارات'
    ],
    [
        'name' => 'بطارية سيارة',
        'description' => 'بطارية سيارة قوية وطويلة الأمد',
        'price' => 250000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 15,
        'sizes' => [],
        'colors' => [],
        'category' => 'سيارات'
    ],
    [
        'name' => 'زيت محرك',
        'description' => 'زيت محرك عالي الجودة لحماية المحرك',
        'price' => 35000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 40,
        'sizes' => ['1 لتر', '4 لتر', '5 لتر'],
        'colors' => [],
        'category' => 'سيارات'
    ],
    [
        'name' => 'ممسحة زجاج',
        'description' => 'ممسحة زجاج أمامي عالية الجودة',
        'price' => 25000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 30,
        'sizes' => [],
        'colors' => [],
        'category' => 'سيارات'
    ],
    [
        'name' => 'غطاء سيارة',
        'description' => 'غطاء سيارة واقي من الشمس والمطر',
        'price' => 85000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 18,
        'sizes' => ['صغير', 'متوسط', 'كبير'],
        'colors' => ['أزرق', 'رمادي', 'أسود'],
        'category' => 'سيارات'
    ],
    
    // هواتف (5 products)
    [
        'name' => 'هاتف ذكي 128GB',
        'description' => 'هاتف ذكي مع ذاكرة 128GB وكاميرا عالية الجودة',
        'price' => 550000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 25,
        'sizes' => [],
        'colors' => ['أسود', 'أزرق', 'أحمر'],
        'category' => 'هواتف'
    ],
    [
        'name' => 'هاتف ذكي 256GB',
        'description' => 'هاتف ذكي مع ذاكرة 256GB وشاشة كبيرة',
        'price' => 750000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 20,
        'sizes' => [],
        'colors' => ['أسود', 'فضي', 'ذهبي'],
        'category' => 'هواتف'
    ],
    [
        'name' => 'هاتف ذكي اقتصادي',
        'description' => 'هاتف ذكي اقتصادي مع مواصفات جيدة',
        'price' => 250000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 35,
        'sizes' => [],
        'colors' => ['أسود', 'أزرق'],
        'category' => 'هواتف'
    ],
    [
        'name' => 'هاتف ذكي 512GB',
        'description' => 'هاتف ذكي فاخر مع ذاكرة 512GB وكاميرا احترافية',
        'price' => 1200000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 10,
        'sizes' => [],
        'colors' => ['أسود', 'فضي', 'ذهبي'],
        'category' => 'هواتف'
    ],
    [
        'name' => 'هاتف ذكي متوسط',
        'description' => 'هاتف ذكي متوسط المواصفات بسعر مناسب',
        'price' => 350000,
        'mainImage' => $siteUrl . '/assets/images/placeholder.svg',
        'otherImages' => [],
        'quantity' => 30,
        'sizes' => [],
        'colors' => ['أسود', 'أزرق', 'أحمر'],
        'category' => 'هواتف'
    ],
];

// Insert products
$successCount = 0;
$errorCount = 0;

try {
    $db->beginTransaction();
    
    foreach ($products as $product) {
        $stmt = $db->prepare("
            INSERT INTO products (name, description, price, mainImage, quantity, otherImages, sizes, colors, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $product['name'],
            $product['description'],
            $product['price'],
            $product['mainImage'],
            $product['quantity'],
            json_encode($product['otherImages']),
            json_encode($product['sizes']),
            json_encode($product['colors']),
            $adminId
        ]);
        
        if ($result) {
            $productId = $db->lastInsertId();
            
            // Add category
            $stmt = $db->prepare("INSERT INTO categories (name, product_id) VALUES (?, ?)");
            $stmt->execute([$product['category'], $productId]);
            
            $successCount++;
        } else {
            $errorCount++;
        }
    }
    
    $db->commit();
    
    echo "<h1>تم إضافة المنتجات بنجاح!</h1>";
    echo "<p>تم إضافة <strong>$successCount</strong> منتج بنجاح</p>";
    if ($errorCount > 0) {
        echo "<p>فشل إضافة <strong>$errorCount</strong> منتج</p>";
    }
    echo "<p><a href='" . SITE_URL . "/index.php'>العودة للصفحة الرئيسية</a></p>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<h1>حدث خطأ!</h1>";
    echo "<p>الخطأ: " . $e->getMessage() . "</p>";
}
?>

