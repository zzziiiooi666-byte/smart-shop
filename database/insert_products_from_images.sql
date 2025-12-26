-- Insert Products from Available Images
-- Each category has 5 products
-- Prices are in Iraqi Dinar (IQD)

USE shop_smart;

-- Clear existing products (optional - comment out if you want to keep existing)
-- DELETE FROM categories;
-- DELETE FROM products;

-- ملابس رجالية (Men's Clothing) - 5 products
INSERT INTO products (name, description, price, mainImage, quantity, otherImages, sizes, colors, user_id) VALUES
('قميص رجالي كلاسيكي', 'قميص رجالي أنيق مصنوع من القطن عالي الجودة، مناسب للاستخدام اليومي والمناسبات', 45000, 'http://localhost/smart_markt/assets/images/product-1-1.jpg', 15, '["http://localhost/smart_markt/assets/images/product-1-2.jpg"]', '["S","M","L","XL"]', '["أبيض","أزرق","رمادي"]', 1),
('بنطال جينز مريح', 'بنطال جينز عالي الجودة مريح ومناسب للاستخدام اليومي', 65000, 'http://localhost/smart_markt/assets/images/product-2-1.jpg', 12, '["http://localhost/smart_markt/assets/images/product-2-2.jpg"]', '["30","32","34","36","38"]', '["أزرق داكن","أزرق فاتح","أسود"]', 1),
('جاكيت شتوي دافئ', 'جاكيت شتوي دافئ ومريح مصنوع من مواد عالية الجودة', 120000, 'http://localhost/smart_markt/assets/images/product-3-1.jpg', 8, '["http://localhost/smart_markt/assets/images/product-3-2.jpg"]', '["M","L","XL","XXL"]', '["أسود","رمادي","بني"]', 1),
('تيشرت قطني', 'تيشرت قطني مريح ومناسب للاستخدام اليومي والرياضي', 25000, 'http://localhost/smart_markt/assets/images/product-4-1.jpg', 20, '["http://localhost/smart_markt/assets/images/product-4-2.jpg"]', '["S","M","L","XL"]', '["أبيض","أسود","أحمر","أزرق"]', 1),
('بدلة رسمية أنيقة', 'بدلة رسمية أنيقة للمناسبات الرسمية والاجتماعات المهمة', 180000, 'http://localhost/smart_markt/assets/images/product-5-1.jpg', 5, '["http://localhost/smart_markt/assets/images/product-5-2.jpg"]', '["M","L","XL"]', '["أسود","رمادي","بني"]', 1);

-- Add categories for ملابس رجالية
INSERT INTO categories (name, product_id) VALUES
('ملابس-رجالية', LAST_INSERT_ID() - 4),
('ملابس-رجالية', LAST_INSERT_ID() - 3),
('ملابس-رجالية', LAST_INSERT_ID() - 2),
('ملابس-رجالية', LAST_INSERT_ID() - 1),
('ملابس-رجالية', LAST_INSERT_ID());

-- ملابس نسائية (Women's Clothing) - 5 products
INSERT INTO products (name, description, price, mainImage, quantity, otherImages, sizes, colors, user_id) VALUES
('فستان صيفي أنيق', 'فستان صيفي أنيق ومريح مناسب للمناسبات والاستخدام اليومي', 55000, 'http://localhost/smart_markt/assets/images/product-6-1.jpg', 18, '["http://localhost/smart_markt/assets/images/product-6-2.jpg"]', '["S","M","L","XL"]', '["أحمر","أزرق","أخضر","وردي"]', 1),
('بلوزة نسائية أنيقة', 'بلوزة نسائية أنيقة مصنوعة من مواد عالية الجودة', 35000, 'http://localhost/smart_markt/assets/images/product-7-1.jpg', 15, '["http://localhost/smart_markt/assets/images/product-7-2.jpg"]', '["S","M","L","XL"]', '["أبيض","وردي","أسود","أزرق"]', 1),
('تنورة أنيقة', 'تنورة أنيقة ومريحة للاستخدام اليومي والمناسبات', 40000, 'http://localhost/smart_markt/assets/images/product-8-1.jpg', 12, '["http://localhost/smart_markt/assets/images/product-8-2.jpg"]', '["S","M","L"]', '["أسود","رمادي","أزرق","أخضر"]', 1),
('عباية تقليدية', 'عباية تقليدية أنيقة مصنوعة يدوياً بجودة عالية', 75000, 'http://localhost/smart_markt/assets/images/product-9-1.jpg', 10, '["http://localhost/smart_markt/assets/images/product-9-2.jpg"]', '["M","L","XL"]', '["أسود","رمادي","بني","أزرق"]', 1),
('فستان زفاف فاخر', 'فستان زفاف فاخر مزين بالتطريز اليدوي والتفاصيل الراقية', 250000, 'http://localhost/smart_markt/assets/images/product-10-1.jpg', 3, '["http://localhost/smart_markt/assets/images/product-10-2.jpg"]', '["S","M","L"]', '["أبيض","عاجي","ذهبي"]', 1);

-- Add categories for ملابس نسائية
INSERT INTO categories (name, product_id) VALUES
('ملابس-نسائية', LAST_INSERT_ID() - 4),
('ملابس-نسائية', LAST_INSERT_ID() - 3),
('ملابس-نسائية', LAST_INSERT_ID() - 2),
('ملابس-نسائية', LAST_INSERT_ID() - 1),
('ملابس-نسائية', LAST_INSERT_ID());

-- أحذية (Shoes) - 5 products
INSERT INTO products (name, description, price, mainImage, quantity, otherImages, sizes, colors, user_id) VALUES
('حذاء رياضي مريح', 'حذاء رياضي مريح ومناسب للمشي والرياضة اليومية', 85000, 'http://localhost/smart_markt/assets/images/product-11-1.jpg', 20, '["http://localhost/smart_markt/assets/images/product-11-2.jpg"]', '["40","41","42","43","44","45"]', '["أسود","أبيض","أزرق"]', 1),
('حذاء رسمي أنيق', 'حذاء رسمي أنيق للمناسبات الرسمية والاجتماعات', 95000, 'http://localhost/smart_markt/assets/images/product-12-1.jpg', 15, '["http://localhost/smart_markt/assets/images/product-12-2.jpg"]', '["40","41","42","43","44"]', '["أسود","بني"]', 1),
('صندل صيفي', 'صندل صيفي مريح ومناسب للاستخدام اليومي', 35000, 'http://localhost/smart_markt/assets/images/product-13-1.jpg', 25, '["http://localhost/smart_markt/assets/images/product-13-2.jpg"]', '["38","39","40","41","42"]', '["أسود","بني","أبيض"]', 1),
('حذاء نسائي أنيق', 'حذاء نسائي أنيق ومريح مناسب للمناسبات', 65000, 'http://localhost/smart_markt/assets/images/showcase-img-1.jpg', 18, '["http://localhost/smart_markt/assets/images/showcase-img-2.jpg"]', '["36","37","38","39","40"]', '["أسود","بني","أحمر"]', 1),
('حذاء رياضي نسائي', 'حذاء رياضي نسائي مريح ومناسب للرياضة والمشي', 75000, 'http://localhost/smart_markt/assets/images/showcase-img-3.jpg', 22, '["http://localhost/smart_markt/assets/images/showcase-img-4.jpg"]', '["36","37","38","39","40","41"]', '["أبيض","وردي","أزرق"]', 1);

-- Add categories for أحذية
INSERT INTO categories (name, product_id) VALUES
('أحذية', LAST_INSERT_ID() - 4),
('أحذية', LAST_INSERT_ID() - 3),
('أحذية', LAST_INSERT_ID() - 2),
('أحذية', LAST_INSERT_ID() - 1),
('أحذية', LAST_INSERT_ID());

-- إلكترونيات (Electronics) - 5 products
INSERT INTO products (name, description, price, mainImage, quantity, otherImages, sizes, colors, user_id) VALUES
('هاتف ذكي متطور', 'هاتف ذكي متطور مع كاميرا عالية الجودة وشاشة كبيرة', 450000, 'http://localhost/smart_markt/assets/images/deals-1.jpg', 30, NULL, NULL, '["أسود","أزرق","أحمر"]', 1),
('لابتوب قوي', 'لابتوب قوي ومناسب للعمل والدراسة والألعاب', 650000, 'http://localhost/smart_markt/assets/images/deals-2.png', 20, NULL, NULL, '["أسود","رمادي"]', 1),
('سماعات لاسلكية', 'سماعات لاسلكية عالية الجودة مع صوت واضح', 80000, 'http://localhost/smart_markt/assets/images/category-1.jpg', 35, NULL, NULL, '["أسود","أبيض","أزرق"]', 1),
('تابلت تعليمي', 'تابلت تعليمي مع شاشة كبيرة ومناسبة للقراءة', 350000, 'http://localhost/smart_markt/assets/images/category-2.jpg', 25, NULL, NULL, '["أسود","أزرق"]', 1),
('ساعة ذكية', 'ساعة ذكية مع تتبع اللياقة البدنية وإشعارات الهاتف', 150000, 'http://localhost/smart_markt/assets/images/category-3.jpg', 40, NULL, NULL, '["أسود","فضي","ذهبي"]', 1);

-- Add categories for إلكترونيات
INSERT INTO categories (name, product_id) VALUES
('إلكترونيات', LAST_INSERT_ID() - 4),
('إلكترونيات', LAST_INSERT_ID() - 3),
('إلكترونيات', LAST_INSERT_ID() - 2),
('إلكترونيات', LAST_INSERT_ID() - 1),
('إلكترونيات', LAST_INSERT_ID());

-- أجهزة منزلية (Home Appliances) - 5 products
INSERT INTO products (name, description, price, mainImage, quantity, otherImages, sizes, colors, user_id) VALUES
('غسالة أطباق', 'غسالة أطباق حديثة مع تقنيات متطورة', 550000, 'http://localhost/smart_markt/assets/images/category-4.jpg', 10, NULL, NULL, '["أبيض","فضي"]', 1),
('مكيف هواء', 'مكيف هواء قوي ومناسب للمنازل والمكاتب', 850000, 'http://localhost/smart_markt/assets/images/category-5.jpg', 8, NULL, NULL, '["أبيض"]', 1),
('فرن كهربائي', 'فرن كهربائي حديث مع تقنيات متطورة', 450000, 'http://localhost/smart_markt/assets/images/category-6.jpg', 12, NULL, NULL, '["أسود","فضي"]', 1),
('ثلاجة حديثة', 'ثلاجة حديثة مع تقنيات التبريد المتطورة', 1200000, 'http://localhost/smart_markt/assets/images/category-7.jpg', 6, NULL, NULL, '["أبيض","فضي"]', 1),
('غسالة ملابس', 'غسالة ملابس حديثة مع برامج متعددة', 650000, 'http://localhost/smart_markt/assets/images/category-8.jpg', 10, NULL, NULL, '["أبيض","فضي"]', 1);

-- Add categories for أجهزة منزلية
INSERT INTO categories (name, product_id) VALUES
('أجهزة-منزلية', LAST_INSERT_ID() - 4),
('أجهزة-منزلية', LAST_INSERT_ID() - 3),
('أجهزة-منزلية', LAST_INSERT_ID() - 2),
('أجهزة-منزلية', LAST_INSERT_ID() - 1),
('أجهزة-منزلية', LAST_INSERT_ID());

