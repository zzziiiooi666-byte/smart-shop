<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'يجب تسجيل الدخول'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$productId = intval($data['product_id'] ?? 0);
$quantity = intval($data['quantity'] ?? 1);
$color = !empty($data['color']) ? sanitize($data['color']) : null;
$size = !empty($data['size']) ? sanitize($data['size']) : null;
$userId = $_SESSION['user_id'];

// Validation
if ($productId <= 0) {
    jsonResponse(['success' => false, 'message' => 'معرف المنتج غير صحيح'], 400);
}

if ($quantity <= 0 || $quantity > 100) {
    jsonResponse(['success' => false, 'message' => 'الكمية يجب أن تكون بين 1 و 100'], 400);
}

require_once __DIR__ . '/../../config/database.php';
$db = getDB();

// Check if product exists and has stock
$stmt = $db->prepare("SELECT id, name, quantity as stock FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    jsonResponse(['success' => false, 'message' => 'المنتج غير موجود'], 404);
}

// Get user name for notification
$stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$userName = $user['name'] ?? 'مستخدم';

// Check stock availability
if (isset($product['stock']) && $product['stock'] < $quantity) {
    jsonResponse(['success' => false, 'message' => 'الكمية المطلوبة غير متوفرة. المتاح: ' . $product['stock']], 400);
}

// Check if item already in cart
$stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
$stmt->execute([$userId, $productId, $color, $size]);
$existingItem = $stmt->fetch();

if ($existingItem) {
    // Update quantity
    $newQuantity = $existingItem['quantity'] + $quantity;
    
    // Check if new quantity exceeds stock
    if ($newQuantity > $product['stock']) {
        jsonResponse(['success' => false, 'message' => 'الكمية المطلوبة غير متوفرة. المتاح: ' . $product['stock']], 400);
    }
    
    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->execute([$newQuantity, $existingItem['id']]);
    
    // Send notification to all admin users when updating cart quantity
    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE isAdmin = 1");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        $productName = $product['name'] ?? 'منتج';
        $message = "تم تحديث كمية منتج في السلة: {$productName} (الكمية الجديدة: {$newQuantity}) من قبل المستخدم: {$userName}";
        
        foreach ($admins as $admin) {
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'general')");
            $stmt->execute([$admin['id'], 'تحديث السلة', $message]);
        }
    } catch (Exception $e) {
        // Silently fail if notifications table doesn't exist or there's an error
        error_log("Notification error: " . $e->getMessage());
    }
    
    jsonResponse(['success' => true, 'message' => 'تم تحديث الكمية']);
} else {
    // Add new item
    $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity, color, size) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $productId, $quantity, $color, $size]);
    
    // Send notification to all admin users
    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE isAdmin = 1");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        $productName = $product['name'] ?? 'منتج';
        $message = "تمت إضافة منتج إلى السلة: {$productName} (الكمية: {$quantity}) من قبل المستخدم: {$userName}";
        
        foreach ($admins as $admin) {
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'general')");
            $stmt->execute([$admin['id'], 'إضافة منتج إلى السلة', $message]);
        }
    } catch (Exception $e) {
        // Silently fail if notifications table doesn't exist or there's an error
        error_log("Notification error: " . $e->getMessage());
    }
    
    jsonResponse(['success' => true, 'message' => 'تمت إضافة المنتج إلى السلة']);
}

