<?php
/**
 * Main Configuration File
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Site configuration
define('SITE_URL', 'http://localhost/smart_markt');
define('SITE_NAME', 'Shop Smart');

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 4 * 1024 * 1024); // 4MB

// Security
define('PASSWORD_MIN_LENGTH', 8);

// Timezone
date_default_timezone_set('Asia/Riyadh');

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Require admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

/**
 * Sanitize input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect function
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * JSON Response
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'كلمة المرور يجب أن تكون على الأقل ' . PASSWORD_MIN_LENGTH . ' أحرف';
    }
    
    if (!preg_match('/[A-Z]/', $password) && !preg_match('/[a-z]/', $password)) {
        $errors[] = 'كلمة المرور يجب أن تحتوي على حرف واحد على الأقل';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل';
    }
    
    // Check for common passwords
    $commonPasswords = ['password', '12345678', 'qwerty', 'admin123', 'password123'];
    if (in_array(strtolower($password), $commonPasswords)) {
        $errors[] = 'كلمة المرور ضعيفة جداً، يرجى اختيار كلمة مرور أقوى';
    }
    
    return $errors;
}

/**
 * Validate phone number (Iraq format)
 */
function validatePhone($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check if it's a valid Iraqi phone number
    // Iraqi numbers: 07XX XXX XXXX or +964 7XX XXX XXXX
    if (preg_match('/^(\+964|0)?7[0-9]{9}$/', $phone)) {
        return true;
    }
    
    return false;
}

/**
 * Log activity for admin
 */
function logActivity($userId, $action, $description = '', $db = null) {
    if (!$db) {
        require_once __DIR__ . '/database.php';
        $db = getDB();
    }
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    try {
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $description, $ipAddress]);
    } catch (Exception $e) {
        // Silently fail if table doesn't exist yet
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Get user IP address
 */
function getUserIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Get safe image URL with fallback
 * Returns placeholder if image doesn't exist or is invalid
 */
function getImageUrl($imagePath, $fallback = null) {
    if (empty($imagePath)) {
        return $fallback ?? SITE_URL . '/assets/images/placeholder.svg';
    }
    
    // If it's already a full URL, return it
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return $imagePath;
    }
    
    // If it's a relative path, make it absolute
    if (strpos($imagePath, '/') === 0) {
        return SITE_URL . $imagePath;
    }
    
    // If it's a relative path without leading slash
    if (strpos($imagePath, 'http') !== 0) {
        return SITE_URL . '/' . ltrim($imagePath, '/');
    }
    
    return $imagePath;
}

/**
 * Check if image exists (for local files)
 */
function imageExists($imagePath) {
    if (empty($imagePath)) {
        return false;
    }
    
    // For external URLs, we can't check, so assume true
    if (filter_var($imagePath, FILTER_VALIDATE_URL) && strpos($imagePath, 'localhost') === false) {
        return true;
    }
    
    // For local files, check if file exists
    $localPath = str_replace(SITE_URL, __DIR__ . '/..', $imagePath);
    $localPath = str_replace('http://localhost/smart_markt', __DIR__ . '/..', $localPath);
    
    if (file_exists($localPath)) {
        return true;
    }
    
    return false;
}

/**
 * Get product image with fallback
 */
function getProductImage($imagePath, $productName = '') {
    $url = getImageUrl($imagePath);
    
    // If it's a placeholder path and we have a product name, try to find a better image
    if (strpos($url, 'placeholder') !== false && !empty($productName)) {
        // Could add logic here to search for images by product name
    }
    
    return $url;
}

