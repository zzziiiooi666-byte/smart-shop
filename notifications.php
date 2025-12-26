<?php
/**
 * Redirect to user/notifications.php
 */
header('Location: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/user/notifications.php');
exit;

