<?php
$pageTitle = 'الإشعارات';
require_once __DIR__ . '/../config/config.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
$db = getDB();
$userId = $_SESSION['user_id'];

// تحديث حالة القراءة عند فتح الصفحة
if (isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    redirect(SITE_URL . '/notifications.php');
}

// تحديث إشعار محدد كمقروء
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notificationId = (int)$_GET['mark_read'];
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
    redirect(SITE_URL . '/notifications.php');
}

// الحصول على الإشعارات
$stmt = $db->prepare("
    SELECT n.*, o.tracking_number, o.shipping_status
    FROM notifications n
    LEFT JOIN orders o ON n.order_id = o.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// عدد الإشعارات غير المقروءة
$stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadCount = $stmt->fetch()['count'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="font-size: 36px;">الإشعارات</h1>
        <?php if ($unreadCount > 0): ?>
            <a href="?mark_read=all" class="btn btn-primary" style="text-decoration: none;">
                تحديد الكل كمقروء (<?php echo $unreadCount; ?>)
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: var(--shadow);">
            <i class="fas fa-bell-slash" style="font-size: 64px; color: #d1d5db; margin-bottom: 20px;"></i>
            <h2 style="color: #6b7280; margin-bottom: 20px;">لا توجد إشعارات</h2>
            <p style="color: #9ca3af;">ستظهر الإشعارات هنا عند تحديث حالة طلباتك</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($notifications as $notification): ?>
                <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: var(--shadow); 
                    <?php echo !$notification['is_read'] ? 'border-right: 4px solid var(--primary-color);' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 20px;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <?php if (!$notification['is_read']): ?>
                                    <span style="width: 10px; height: 10px; background: var(--primary-color); border-radius: 50%; display: inline-block;"></span>
                                <?php endif; ?>
                                <h3 style="margin: 0; font-size: 18px; color: <?php echo !$notification['is_read'] ? 'var(--text-color)' : '#6b7280'; ?>;">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                </h3>
                            </div>
                            <p style="color: #6b7280; margin-bottom: 10px; line-height: 1.6;">
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </p>
                            
                            <!-- Action buttons for admin notifications -->
                            <?php if (isAdmin() && $notification['order_id'] && $notification['type'] === 'order_created'): ?>
                                <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                                    <a href="<?php echo SITE_URL; ?>/admin/admin.php?tab=orders" 
                                       style="background: var(--primary-color); color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 14px; display: inline-block;">
                                        <i class="fas fa-shopping-cart" style="margin-left: 5px;"></i> الذهاب إلى إدارة المبيعات
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/pages/order-details.php?id=<?php echo $notification['order_id']; ?>" 
                                       style="background: #10b981; color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 14px; display: inline-block;">
                                        <i class="fas fa-eye" style="margin-left: 5px;"></i> عرض تفاصيل الطلب
                                    </a>
                                </div>
                            <?php elseif ($notification['order_id'] && !isAdmin()): ?>
                                <div style="margin-top: 15px;">
                                    <a href="<?php echo SITE_URL; ?>/pages/order-details.php?id=<?php echo $notification['order_id']; ?>" 
                                       style="background: var(--primary-color); color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 14px; display: inline-block;">
                                        <i class="fas fa-eye" style="margin-left: 5px;"></i> عرض تفاصيل الطلب
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($notification['tracking_number']): ?>
                                <div style="background: #f3f4f6; padding: 10px; border-radius: 6px; margin-top: 10px;">
                                    <p style="margin: 0; font-size: 14px;">
                                        <strong>رقم التتبع:</strong> 
                                        <a href="<?php echo SITE_URL; ?>/pages/track-order.php?tracking=<?php echo htmlspecialchars($notification['tracking_number']); ?>&notification_id=<?php echo $notification['id']; ?>" 
                                           style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                            <?php echo htmlspecialchars($notification['tracking_number']); ?>
                                        </a>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <p style="color: #9ca3af; font-size: 14px; margin-top: 10px; margin-bottom: 0;">
                                <i class="far fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?>
                            </p>
                        </div>
                        <?php if (!$notification['is_read']): ?>
                            <a href="?mark_read=<?php echo $notification['id']; ?>" 
                               style="color: var(--primary-color); text-decoration: none; padding: 5px 10px; border: 1px solid var(--primary-color); border-radius: 6px; font-size: 14px;">
                                تحديد كمقروء
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

