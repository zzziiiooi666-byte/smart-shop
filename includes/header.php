<?php
require_once __DIR__ . '/../config/config.php';
$isLoggedIn = isLoggedIn();
$isAdmin = isAdmin();
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Shop Smart</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="strict-origin-when-cross-origin">
    <script>
        // Define SITE_URL for JavaScript
        const SITE_URL = '<?php echo SITE_URL; ?>';
    </script>
</head>
<body>
    <!-- Top Banner -->
    <div class="top-banner">
        <span class="banner-text" id="bannerText">شحن مجاني للطلبات فوق 250,000 د.ع!</span>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="<?php echo SITE_URL; ?>/pages/index.php">
                    <span class="brand-name">Smart Shop</span>
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="nav-menu desktop-menu">
                <a href="<?php echo SITE_URL; ?>/index.php" class="nav-link">الرئيسية</a>
                <a href="<?php echo SITE_URL; ?>/pages/products.php" class="nav-link">المتجر</a>
                <a href="<?php echo SITE_URL; ?>/pages/category.php" class="nav-link">الفئات</a>
                <a href="<?php echo $isLoggedIn ? SITE_URL . '/pages/orders.php' : SITE_URL . '/auth/login.php'; ?>" class="nav-link">الطلبات</a>
                <?php if ($isAdmin): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/admin.php" class="nav-link">لوحة التحكم</a>
                <?php endif; ?>
            </div>

            <!-- User Actions -->
            <div class="nav-actions">
                <!-- Currency Toggle -->
                <button class="currency-toggle" id="globalCurrencyToggle" title="تبديل العملة">
                    <i class="fas fa-dollar-sign"></i>
                    <span id="currencyLabel">د.ع</span>
                </button>

                <?php if ($isLoggedIn): ?>
                    <?php
                    // Get unread notifications count
                    require_once __DIR__ . '/../config/database.php';
                    $db = getDB();
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
                    $stmt->execute([$_SESSION['user_id']]);
                    $unreadNotifications = $stmt->fetch()['count'] ?? 0;
                    ?>
                    <!-- Notifications Icon -->
                    <a href="<?php echo SITE_URL; ?>/user/notifications.php" 
                       class="cart-icon" 
                       title="الإشعارات" 
                       style="position: relative; margin-left: 10px; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px;">
                        <i class="fas fa-bell" style="font-size: 22px; color: var(--text-color);"></i>
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="cart-count" 
                                  style="background: #ef4444; 
                                         position: absolute; 
                                         top: -5px; 
                                         right: -5px; 
                                         min-width: 20px; 
                                         height: 20px; 
                                         display: flex; 
                                         align-items: center; 
                                         justify-content: center; 
                                         border-radius: 50%; 
                                         font-size: 11px; 
                                         font-weight: bold; 
                                         color: white; 
                                         padding: 0 4px;
                                         border: 2px solid white;
                                         box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                <?php echo $unreadNotifications > 99 ? '99+' : $unreadNotifications; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <!-- Cart Icon -->
                    <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="cart-icon" title="السلة">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count" id="cartCount">0</span>
                    </a>
                    <!-- User Profile Dropdown -->
                    <div class="user-profile-dropdown" style="position: relative; margin-left: 15px;">
                        <?php
                        $userProfilePicture = $_SESSION['user_profile_picture'] ?? null;
                        $profileImageSrc = !empty($userProfilePicture) ? SITE_URL . '/' . htmlspecialchars($userProfilePicture) : SITE_URL . '/assets/images/avatar-1.jpg';
                        ?>
                        <button class="user-profile-btn" id="userProfileBtn" style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; gap: 8px;">
                            <img src="<?php echo $profileImageSrc; ?>" 
                                 alt="صورة المستخدم" 
                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                            <span style="color: var(--text-color); font-weight: 500;"><?php echo htmlspecialchars($userName); ?></span>
                            <i class="fas fa-chevron-down" style="color: var(--text-light); font-size: 12px;"></i>
                        </button>
                        <div class="user-dropdown-menu" id="userDropdownMenu" style="display: none; position: absolute; top: 100%; left: 0; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 200px; margin-top: 10px; z-index: 1000; padding: 10px 0;">
                            <a href="<?php echo SITE_URL; ?>/<?php echo $isAdmin ? 'admin/admin-profile.php' : 'user/profile.php'; ?>" 
                               style="display: block; padding: 12px 20px; color: var(--text-color); text-decoration: none; transition: background 0.2s;">
                                <i class="fas fa-user" style="margin-left: 10px; color: var(--primary-color);"></i> الملف الشخصي
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/orders.php" 
                               style="display: block; padding: 12px 20px; color: var(--text-color); text-decoration: none; transition: background 0.2s;">
                                <i class="fas fa-shopping-bag" style="margin-left: 10px; color: var(--primary-color);"></i> طلباتي
                            </a>
                            <?php if ($isAdmin): ?>
                                <a href="<?php echo SITE_URL; ?>/admin/admin.php" 
                                   style="display: block; padding: 12px 20px; color: var(--text-color); text-decoration: none; transition: background 0.2s;">
                                    <i class="fas fa-tachometer-alt" style="margin-left: 10px; color: var(--primary-color);"></i> لوحة التحكم
                                </a>
                            <?php endif; ?>
                            <hr style="margin: 8px 0; border: none; border-top: 1px solid #e5e7eb;">
                            <a href="<?php echo SITE_URL; ?>/logout.php" 
                               style="display: block; padding: 12px 20px; color: #ef4444; text-decoration: none; transition: background 0.2s;">
                                <i class="fas fa-sign-out-alt" style="margin-left: 10px;"></i> تسجيل الخروج
                            </a>
                        </div>
                    </div>
                    <style>
                        .user-profile-btn:hover {
                            opacity: 0.8;
                        }
                        .user-dropdown-menu a:hover {
                            background: #f3f4f6;
                        }
                    </style>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const profileBtn = document.getElementById('userProfileBtn');
                            const dropdownMenu = document.getElementById('userDropdownMenu');
                            
                            if (profileBtn && dropdownMenu) {
                                profileBtn.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    dropdownMenu.style.display = dropdownMenu.style.display === 'none' ? 'block' : 'none';
                                });
                                
                                document.addEventListener('click', function(e) {
                                    if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                                        dropdownMenu.style.display = 'none';
                                    }
                                });
                            }
                        });
                    </script>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-primary">تسجيل الدخول</a>
                <?php endif; ?>
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <a href="<?php echo SITE_URL; ?>/index.php" class="nav-link">الرئيسية</a>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="nav-link">المتجر</a>
            <a href="<?php echo SITE_URL; ?>/pages/category.php" class="nav-link">الفئات</a>
            <a href="<?php echo $isLoggedIn ? SITE_URL . '/pages/orders.php' : SITE_URL . '/auth/login.php'; ?>" class="nav-link">الطلبات</a>
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo SITE_URL; ?>/<?php echo $isAdmin ? 'admin/admin-profile.php' : 'user/profile.php'; ?>" class="nav-link">الملف الشخصي</a>
                <a href="<?php echo SITE_URL; ?>/user/notifications.php" class="nav-link">الإشعارات</a>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
                <a href="<?php echo SITE_URL; ?>/admin/admin.php" class="nav-link">لوحة التحكم</a>
            <?php endif; ?>
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline">تسجيل الخروج</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="main-content">

