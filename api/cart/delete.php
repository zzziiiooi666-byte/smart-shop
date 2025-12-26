<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'يجب تسجيل الدخول'], 401);
}

$data = json_decode(file_get_contents('php://input'), true);
$cartId = intval($data['cart_id'] ?? 0);

if ($cartId <= 0) {
    jsonResponse(['success' => false, 'message' => 'معرف غير صحيح'], 400);
}

require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
if ($stmt->execute([$cartId, $_SESSION['user_id']])) {
    jsonResponse(['success' => true, 'message' => 'تم الحذف']);
} else {
    jsonResponse(['success' => false, 'message' => 'فشل الحذف'], 500);
}

