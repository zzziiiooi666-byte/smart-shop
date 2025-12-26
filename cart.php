<?php
/**
 * Redirect to pages/cart.php
 */
header('Location: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/pages/cart.php');
exit;

