-- جدول الفئات العامة مع الأيقونات والألوان
CREATE TABLE IF NOT EXISTS category_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    icon VARCHAR(100) NOT NULL,
    gradient_start VARCHAR(7) NOT NULL,
    gradient_end VARCHAR(7) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج الفئات الحالية
INSERT INTO category_list (name, icon, gradient_start, gradient_end, display_order) VALUES
('ملابس-رجالية', 'fa-tshirt', '#667eea', '#764ba2', 1),
('ملابس-نسائية', 'fa-female', '#f093fb', '#f5576c', 2),
('أحذية', 'fa-shoe-prints', '#4facfe', '#00f2fe', 3),
('إلكترونيات', 'fa-mobile-alt', '#43e97b', '#38f9d7', 4),
('أجهزة-منزلية', 'fa-home', '#fa709a', '#fee140', 5),
('أثاث', 'fa-couch', '#8B4513', '#A0522D', 6),
('مستحضرات-تجميل', 'fa-spa', '#ff9a9e', '#fecfef', 7),
('عطور', 'fa-wind', '#ffecd2', '#fcb69f', 8),
('ألعاب', 'fa-gamepad', '#667eea', '#764ba2', 9),
('كتب', 'fa-book', '#f093fb', '#f5576c', 10),
('رياضة', 'fa-basketball-ball', '#4facfe', '#00f2fe', 11),
('صحة-وتجميل', 'fa-heartbeat', '#43e97b', '#38f9d7', 12),
('أدوات-منزلية', 'fa-tools', '#fa709a', '#fee140', 13),
('سيارات', 'fa-car', '#a8edea', '#fed6e3', 14),
('هواتف', 'fa-mobile-alt', '#ff9a9e', '#fecfef', 15)
ON DUPLICATE KEY UPDATE name=name;

