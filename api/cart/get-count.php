<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

// Allow both GET and POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['count' => 0, 'error' => 'Method not allowed'], 405);
}

if (!isLoggedIn()) {
    jsonResponse(['count' => 0]);
}

require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$result = $stmt->fetch();

jsonResponse(['count' => intval($result['total'] ?? 0)]);

