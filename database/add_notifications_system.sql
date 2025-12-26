-- إضافة نظام الإشعارات والتتبع للطلبات

-- إضافة حقول جديدة لجدول orders
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS shipping_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending';

-- إنشاء جدول الإشعارات
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('order_created', 'order_shipped', 'order_delivered', 'order_cancelled', 'general') DEFAULT 'general',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء فهرس لتحسين الأداء
CREATE INDEX idx_user_notifications ON notifications(user_id, is_read);
CREATE INDEX idx_order_notifications ON notifications(order_id);

