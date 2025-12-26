<?php
/**
 * Redirect to tools/fix_admin_password.php
 */
header('Location: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/tools/fix_admin_password.php');
exit;

