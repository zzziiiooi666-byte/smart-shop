-- Add new fields to users table for profile enhancements
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL AFTER country,
ADD COLUMN IF NOT EXISTS updated_country VARCHAR(100) DEFAULT NULL AFTER phone;

-- Create activity log table for admin
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

