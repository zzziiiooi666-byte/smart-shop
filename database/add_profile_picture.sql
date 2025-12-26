-- Add profile_picture column to users table
-- Run this in phpMyAdmin or MySQL command line

-- Check if column exists before adding (for MySQL 5.7+)
-- For older versions, you can run the ALTER TABLE directly (it will fail if column exists, which is safe)

ALTER TABLE users ADD COLUMN profile_picture VARCHAR(500) DEFAULT NULL AFTER country;

