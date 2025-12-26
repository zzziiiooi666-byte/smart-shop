<?php
/**
 * Redirect to pages/category.php
 */
header('Location: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/pages/category.php' . (isset($_GET['cat']) ? '?cat=' . urlencode($_GET['cat']) : ''));
exit;

