-- إصلاح الصور المفقودة في قاعدة البيانات
-- استبدال الصور المفقودة بصور موجودة أو placeholder

USE shop_smart;

-- استبدال الصور المفقودة بصور موجودة
UPDATE products 
SET mainImage = REPLACE(mainImage, 'product-1-1.jpg', 'product-5-1.jpg')
WHERE mainImage LIKE '%product-1-1.jpg%';

UPDATE products 
SET mainImage = REPLACE(mainImage, 'product-2-1.jpg', 'product-6-1.jpg')
WHERE mainImage LIKE '%product-2-1.jpg%';

UPDATE products 
SET mainImage = REPLACE(mainImage, 'product-3-1.jpg', 'product-8-1.jpg')
WHERE mainImage LIKE '%product-3-1.jpg%';

UPDATE products 
SET mainImage = REPLACE(mainImage, 'product-4-1.jpg', 'product-10-1.jpg')
WHERE mainImage LIKE '%product-4-1.jpg%';

-- تحديث الصور الإضافية (otherImages)
UPDATE products 
SET otherImages = REPLACE(otherImages, 'product-1-2.jpg', 'product-5-2.jpg')
WHERE otherImages LIKE '%product-1-2.jpg%';

UPDATE products 
SET otherImages = REPLACE(otherImages, 'product-2-2.jpg', 'product-7-1.jpg')
WHERE otherImages LIKE '%product-2-2.jpg%';

UPDATE products 
SET otherImages = REPLACE(otherImages, 'product-4-2.jpg', 'product-10-2.jpg')
WHERE otherImages LIKE '%product-4-2.jpg%';

UPDATE products 
SET otherImages = REPLACE(otherImages, 'product-6-2.jpg', 'product-11-2.jpg')
WHERE otherImages LIKE '%product-6-2.jpg%';

-- إذا كنت تريد استخدام placeholder للصور المفقودة بدلاً من ذلك، استخدم:
-- UPDATE products 
-- SET mainImage = 'http://localhost/smart_markt/assets/images/placeholder.svg'
-- WHERE mainImage LIKE '%product-1-1.jpg%' 
--    OR mainImage LIKE '%product-2-1.jpg%'
--    OR mainImage LIKE '%product-3-1.jpg%'
--    OR mainImage LIKE '%product-4-1.jpg%';

