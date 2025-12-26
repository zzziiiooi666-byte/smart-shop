<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'يجب تسجيل الدخول'], 401);
}

$data = json_decode(file_get_contents('php://input'), true);
$cartId = intval($data['cart_id'] ?? 0);
$quantity = intval($data['quantity'] ?? 1);

if ($cartId <= 0 || $quantity < 1) {
    jsonResponse(['success' => false, 'message' => 'بيانات غير صحيحة'], 400);
}

require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
if ($stmt->execute([$quantity, $cartId, $_SESSION['user_id']])) {
    jsonResponse(['success' => true, 'message' => 'تم التحديث']);
} else {
    jsonResponse(['success' => false, 'message' => 'فشل التحديث'], 500);
}

