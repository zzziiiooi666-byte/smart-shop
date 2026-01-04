<?php
$pageTitle = 'لوحة تحكم المدير';
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/auth/login.php');
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Get statistics
$stats = [];

// Total users
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$stats['total_users'] = $stmt->fetch()['total'];

// Total products
$stmt = $db->prepare("SELECT COUNT(*) as total FROM products");
$stmt->execute();
$stats['total_products'] = $stmt->fetch()['total'];

// Total orders
$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$stats['total_orders'] = $stmt->fetch()['total'];

// Recent orders
$stmt = $db->prepare("SELECT o.*, u.name as user_name, p.name as product_name
                   FROM orders o
                   JOIN users u ON o.user_id = u.id
                   LEFT JOIN products p ON o.product_id = p.id
                   ORDER BY o.created_at DESC LIMIT 50");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Recent users
$stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$recent_users = $stmt->fetchAll();

// Handle actions
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        if ($user_id !== $_SESSION['user_id']) { // Don't delete yourself
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $message = 'تم حذف المستخدم بنجاح';
            } else {
                $error = 'فشل في حذف المستخدم';
            }
        } else {
            $error = 'لا يمكنك حذف حسابك الخاص';
        }
    }

    elseif ($action === 'delete_product' && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$product_id])) {
            $message = 'تم حذف المنتج بنجاح';
        } else {
            $error = 'فشل في حذف المنتج';
        }
    }

    elseif ($action === 'delete_selected_products' && isset($_POST['selected_products']) && is_array($_POST['selected_products'])) {
        $selected_products = array_map('intval', $_POST['selected_products']);
        $selected_products = array_filter($selected_products, function($id) {
            return $id > 0;
        });
        
        if (!empty($selected_products)) {
            try {
                $db->beginTransaction();
                
                // Delete categories first (foreign key constraint)
                $placeholders = implode(',', array_fill(0, count($selected_products), '?'));
                $stmt = $db->prepare("DELETE FROM categories WHERE product_id IN ($placeholders)");
                $stmt->execute($selected_products);
                
                // Delete cart items
                $stmt = $db->prepare("DELETE FROM cart WHERE product_id IN ($placeholders)");
                $stmt->execute($selected_products);
                
                // Delete products
                $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                if ($stmt->execute($selected_products)) {
                    $db->commit();
                    $deletedCount = count($selected_products);
                    $message = "تم حذف $deletedCount منتج بنجاح";
                } else {
                    $db->rollBack();
                    $error = 'فشل في حذف المنتجات المحددة';
                }
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'حدث خطأ أثناء حذف المنتجات: ' . $e->getMessage();
            }
        } else {
            $error = 'لم يتم تحديد أي منتج صحيح';
        }
    }

    elseif ($action === 'update_order_status' && isset($_POST['order_id'])) {
        $order_id = (int)$_POST['order_id'];
        $status = $_POST['status'] ?? 'PENDING';
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $order_id])) {
            $message = 'تم تحديث حالة الطلب بنجاح';
        } else {
            $error = 'فشل في تحديث حالة الطلب';
        }
    }

    elseif ($action === 'update_shipping_status' && isset($_POST['order_id'])) {
        $order_id = (int)$_POST['order_id'];
        $shipping_status = $_POST['shipping_status'] ?? 'pending';
        
        // Get order info
        $stmt = $db->prepare("SELECT user_id, tracking_number FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if ($order) {
            $stmt = $db->prepare("UPDATE orders SET shipping_status = ? WHERE id = ?");
            if ($stmt->execute([$shipping_status, $order_id])) {
                // Create notification based on shipping status
                $notificationMessages = [
                    'processing' => ['title' => 'جاري معالجة طلبك', 'message' => 'تم بدء معالجة طلبك وسيتم شحنه قريباً. رقم التتبع: ' . $order['tracking_number']],
                    'shipped' => ['title' => 'تم شحن طلبك', 'message' => 'تم شحن طلبك بنجاح! يمكنك تتبع شحنتك باستخدام رقم التتبع: ' . $order['tracking_number']],
                    'delivered' => ['title' => 'تم تسليم طلبك', 'message' => 'تم تسليم طلبك بنجاح! نأمل أن تكون راضياً عن منتجك. رقم التتبع: ' . $order['tracking_number']],
                    'cancelled' => ['title' => 'تم إلغاء طلبك', 'message' => 'تم إلغاء طلبك. رقم التتبع: ' . $order['tracking_number']]
                ];
                
                if (isset($notificationMessages[$shipping_status])) {
                    $notificationType = 'order_' . $shipping_status;
                    $notification = $notificationMessages[$shipping_status];
                    
                    $stmt = $db->prepare("INSERT INTO notifications (user_id, order_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$order['user_id'], $order_id, $notification['title'], $notification['message'], $notificationType]);
                }
                
                $message = 'تم تحديث حالة الشحن وإرسال إشعار للعميل بنجاح';
            } else {
                $error = 'فشل في تحديث حالة الشحن';
            }
        } else {
            $error = 'الطلب غير موجود';
        }
    }

    elseif ($action === 'toggle_admin' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $stmt = $db->prepare("UPDATE users SET isAdmin = NOT isAdmin WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = 'تم تحديث صلاحيات المستخدم بنجاح';
        } else {
            $error = 'فشل في تحديث صلاحيات المستخدم';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="margin-top: 30px;">
    <h1 style="text-align: center; margin-bottom: 40px; color: var(--primary-color);">
        لوحة تحكم المدير
    </h1>

    <?php if ($message): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 12px; color: white; text-align: center;">
            <h3 style="margin: 0; font-size: 36px;"><?php echo $stats['total_users']; ?></h3>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">إجمالي المستخدمين</p>
        </div>

        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 30px; border-radius: 12px; color: white; text-align: center;">
            <h3 style="margin: 0; font-size: 36px;"><?php echo $stats['total_products']; ?></h3>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">إجمالي المنتجات</p>
        </div>

        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 30px; border-radius: 12px; color: white; text-align: center;">
            <h3 style="margin: 0; font-size: 36px;"><?php echo $stats['total_orders']; ?></h3>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">إجمالي الطلبات</p>
        </div>

        <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 30px; border-radius: 12px; color: white; text-align: center;">
            <h3 style="margin: 0; font-size: 36px;">
                <?php
                $revenue = 0;
                $stmt = $db->prepare("SELECT o.quantity, p.price FROM orders o JOIN products p ON o.product_id = p.id WHERE o.status = 'COMPLETED'");
                $stmt->execute();
                while ($row = $stmt->fetch()) {
                    $revenue += $row['quantity'] * $row['price'];
                }
                echo number_format($revenue, 0) . ' د.ع';
                ?>
            </h3>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">إجمالي الإيرادات</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?php echo SITE_URL; ?>/admin/add-product.php" style="background: var(--primary-color); color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: 500;">
            <i class="fas fa-plus" style="margin-left: 5px;"></i> إضافة منتج
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/manage-categories.php" style="background: #10b981; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: 500;">
            <i class="fas fa-tags" style="margin-left: 5px;"></i> إدارة الفئات
        </a>
    </div>

    <!-- Navigation Tabs -->
    <div style="margin-bottom: 30px;">
        <div style="display: flex; gap: 10px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
            <button onclick="showTab('users', this)" class="tab-btn active" style="padding: 10px 20px; border: none; background: var(--primary-color); color: white; border-radius: 6px; cursor: pointer;">المستخدمون</button>
            <button onclick="showTab('products', this)" class="tab-btn" style="padding: 10px 20px; border: none; background: #f3f4f6; color: var(--text-color); border-radius: 6px; cursor: pointer;">المنتجات</button>
            <button onclick="showTab('orders', this)" class="tab-btn" style="padding: 10px 20px; border: none; background: #f3f4f6; color: var(--text-color); border-radius: 6px; cursor: pointer;">المبيعات</button>
        </div>
    </div>

    <!-- Users Tab -->
    <div id="users-tab" class="tab-content">
        <h2 style="margin-bottom: 20px; color: var(--text-color);">إدارة المستخدمين</h2>
        <div style="background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">الاسم</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">البريد الإلكتروني</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">البلد</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">النوع</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">تاريخ التسجيل</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 15px;"><?php echo htmlspecialchars($user['name']); ?></td>
                        <td style="padding: 15px;"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td style="padding: 15px;"><?php echo htmlspecialchars($user['country'] ?? 'غير محدد'); ?></td>
                        <td style="padding: 15px;">
                            <?php if ($user['isAdmin']): ?>
                                <span style="background: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">مدير</span>
                            <?php else: ?>
                                <span style="background: #6b7280; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">مستخدم</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                        <td style="padding: 15px;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_admin">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" style="background: #f59e0b; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-right: 5px;">
                                    <?php echo $user['isAdmin'] ? 'إلغاء المدير' : 'جعل مدير'; ?>
                                </button>
                            </form>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                                    حذف
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Products Tab -->
    <div id="products-tab" class="tab-content" style="display: none;">
        <h2 style="margin-bottom: 20px; color: var(--text-color);">إدارة المنتجات</h2>
        <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="<?php echo SITE_URL; ?>/admin/add-product.php" style="background: var(--primary-color); color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">
                <i class="fas fa-plus" style="margin-left: 5px;"></i> إضافة منتج جديد
            </a>
            <button type="button" id="selectAllBtn" onclick="toggleSelectAll()" style="background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                <i class="fas fa-check-square" style="margin-left: 5px;"></i> تحديد الكل
            </button>
            <button type="button" id="deleteSelectedBtn" onclick="deleteSelectedProducts()" style="background: #ef4444; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; display: none;">
                <i class="fas fa-trash" style="margin-left: 5px;"></i> حذف المحدد (<span id="selectedCount">0</span>)
            </button>
        </div>
        <div style="background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 15px; text-align: center; border-bottom: 1px solid #e5e7eb; width: 50px;">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()" style="width: 18px; height: 18px; cursor: pointer;">
                        </th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">الصورة</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">الاسم</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">الفئة</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">السعر</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">الكمية</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get all products with their categories
                    $stmt = $db->prepare("
                        SELECT p.*, GROUP_CONCAT(c.name SEPARATOR ', ') as categories
                        FROM products p
                        LEFT JOIN categories c ON p.id = c.product_id
                        GROUP BY p.id
                        ORDER BY p.created_at DESC
                    ");
                    $stmt->execute();
                    $products = $stmt->fetchAll();
                    
                    // Get total count for display
                    $totalProducts = count($products);
                    
                    if (empty($products)):
                    ?>
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: #6b7280;">
                            <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                            لا توجد منتجات حالياً
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr style="background: #f0f9ff; border-bottom: 2px solid #3b82f6;">
                        <td colspan="7" style="padding: 12px 15px; text-align: right; font-weight: 600; color: #1e40af;">
                            <i class="fas fa-info-circle" style="margin-left: 8px;"></i>
                            إجمالي المنتجات: <?php echo $totalProducts; ?> منتج
                        </td>
                    </tr>
                    <?php
                    foreach ($products as $product):
                    ?>
                    <tr style="border-bottom: 1px solid #f3f4f6;" class="product-row">
                        <td style="padding: 15px; text-align: center;">
                            <input type="checkbox" class="product-checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>" onchange="updateSelectedCount()" style="width: 18px; height: 18px; cursor: pointer;">
                        </td>
                        <td style="padding: 15px;">
                            <img src="<?php echo htmlspecialchars($product['mainImage']); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        </td>
                        <td style="padding: 15px;"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td style="padding: 15px;">
                            <?php if (!empty($product['categories'])): ?>
                                <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; display: inline-block;">
                                    <i class="fas fa-tag" style="margin-left: 4px;"></i>
                                    <?php echo htmlspecialchars($product['categories']); ?>
                                </span>
                            <?php else: ?>
                                <span style="background: #f3f4f6; color: #6b7280; padding: 4px 12px; border-radius: 12px; font-size: 12px; display: inline-block;">
                                    <i class="fas fa-times-circle" style="margin-left: 4px;"></i>
                                    بدون فئة
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;"><?php echo number_format($product['price'], 2); ?> د.ع</td>
                        <td style="padding: 15px;"><?php echo $product['quantity']; ?></td>
                        <td style="padding: 15px;">
                            <a href="<?php echo SITE_URL; ?>/pages/product-details.php?id=<?php echo $product['id']; ?>"
                               style="background: #3b82f6; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; margin-right: 5px;">عرض</a>
                            <a href="<?php echo SITE_URL; ?>/admin/edit-product.php?id=<?php echo $product['id']; ?>"
                               style="background: #f59e0b; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; margin-right: 5px;">تعديل</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                                    حذف
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php 
                    endforeach;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Orders Tab -->
    <div id="orders-tab" class="tab-content" style="display: none;">
        <h2 style="margin-bottom: 20px; color: var(--text-color);">إدارة المبيعات</h2>
        <div style="background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">رقم الطلب</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">العميل</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">المنتج</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">الكمية</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">رقم التتبع</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">حالة الشحن</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">الحالة</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">التاريخ</th>
                        <th style="padding: 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 15px;">#<?php echo $order['id']; ?></td>
                        <td style="padding: 15px;"><?php echo htmlspecialchars($order['user_name']); ?></td>
                        <td style="padding: 15px;"><?php echo htmlspecialchars($order['product_name'] ?? 'غير محدد'); ?></td>
                        <td style="padding: 15px;"><?php echo $order['quantity']; ?></td>
                        <td style="padding: 15px;">
                            <?php if (!empty($order['tracking_number'])): ?>
                                <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <?php echo htmlspecialchars($order['tracking_number']); ?>
                                </code>
                            <?php else: ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_shipping_status">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="shipping_status" onchange="this.form.submit()" style="padding: 4px; border-radius: 4px; border: 1px solid #d1d5db;">
                                    <option value="pending" <?php echo ($order['shipping_status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                                    <option value="processing" <?php echo ($order['shipping_status'] ?? '') === 'processing' ? 'selected' : ''; ?>>قيد المعالجة</option>
                                    <option value="shipped" <?php echo ($order['shipping_status'] ?? '') === 'shipped' ? 'selected' : ''; ?>>تم الشحن</option>
                                    <option value="delivered" <?php echo ($order['shipping_status'] ?? '') === 'delivered' ? 'selected' : ''; ?>>تم التسليم</option>
                                    <option value="cancelled" <?php echo ($order['shipping_status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                                </select>
                            </form>
                        </td>
                        <td style="padding: 15px;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" onchange="this.form.submit()" style="padding: 4px; border-radius: 4px; border: 1px solid #d1d5db;">
                                    <option value="PENDING" <?php echo $order['status'] === 'PENDING' ? 'selected' : ''; ?>>في الانتظار</option>
                                    <option value="COMPLETED" <?php echo $order['status'] === 'COMPLETED' ? 'selected' : ''; ?>>مكتمل</option>
                                    <option value="CANCELLED" <?php echo $order['status'] === 'CANCELLED' ? 'selected' : ''; ?>>ملغي</option>
                                </select>
                            </form>
                        </td>
                        <td style="padding: 15px;"><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                        <td style="padding: 15px;">
                            <a href="<?php echo SITE_URL; ?>/pages/order-details.php?id=<?php echo $order['id']; ?>"
                               style="background: #3b82f6; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; margin-left: 5px;">تفاصيل</a>
                            <?php if (!empty($order['tracking_number'])): ?>
                                <a href="<?php echo SITE_URL; ?>/pages/track-order.php?tracking=<?php echo htmlspecialchars($order['tracking_number']); ?>"
                                   style="background: #10b981; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px;">تتبع</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showTab(tabName, clickedButton) {
    // Hide all tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.style.display = 'none');

    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = '#f3f4f6';
        btn.style.color = 'var(--text-color)';
    });

    // Show selected tab
    const targetTab = document.getElementById(tabName + '-tab');
    if (targetTab) {
        targetTab.style.display = 'block';
    }

    // Add active class to clicked button or find it
    if (clickedButton) {
        clickedButton.classList.add('active');
        clickedButton.style.background = 'var(--primary-color)';
        clickedButton.style.color = 'white';
    } else {
        // Find the button by onclick attribute
        buttons.forEach(btn => {
            if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(tabName)) {
                btn.classList.add('active');
                btn.style.background = 'var(--primary-color)';
                btn.style.color = 'white';
            }
        });
    }
}

// Add CSS for active tab button (only if not already added)
if (!document.getElementById('admin-tab-styles')) {
    const adminTabStyle = document.createElement('style');
    adminTabStyle.id = 'admin-tab-styles';
    adminTabStyle.textContent = `
        .tab-btn.active {
            background: var(--primary-color) !important;
            color: white !important;
        }
        .product-row:hover {
            background: #f9fafb !important;
        }
        .product-row.selected {
            background: #eff6ff !important;
        }
    `;
    document.head.appendChild(adminTabStyle);
}

// Products selection functions
let allSelected = false;

function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const selectAllBtn = document.getElementById('selectAllBtn');
    
    allSelected = !allSelected;
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = allSelected;
        const row = checkbox.closest('.product-row');
        if (row) {
            if (allSelected) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        }
    });
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allSelected;
    }
    
    if (selectAllBtn) {
        selectAllBtn.innerHTML = allSelected 
            ? '<i class="fas fa-square" style="margin-left: 5px;"></i> إلغاء التحديد'
            : '<i class="fas fa-check-square" style="margin-left: 5px;"></i> تحديد الكل';
    }
    
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const count = checkboxes.length;
    const selectedCount = document.getElementById('selectedCount');
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    if (selectedCount) {
        selectedCount.textContent = count;
    }
    
    if (deleteBtn) {
        if (count > 0) {
            deleteBtn.style.display = 'inline-block';
        } else {
            deleteBtn.style.display = 'none';
        }
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.product-checkbox');
    if (selectAllCheckbox && allCheckboxes.length > 0) {
        selectAllCheckbox.checked = count === allCheckboxes.length;
        selectAllCheckbox.indeterminate = count > 0 && count < allCheckboxes.length;
    }
    
    // Update row styles
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('.product-row');
        if (row) {
            row.classList.add('selected');
        }
    });
    
    document.querySelectorAll('.product-checkbox:not(:checked)').forEach(checkbox => {
        const row = checkbox.closest('.product-row');
        if (row) {
            row.classList.remove('selected');
        }
    });
}

function deleteSelectedProducts() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const selectedIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert('⚠️ لم يتم تحديد أي منتج للحذف');
        return;
    }
    
    // Show confirmation with product names
    const selectedRows = Array.from(checkboxes).map(cb => {
        const row = cb.closest('.product-row');
        const nameCell = row ? row.querySelector('td:nth-child(3)') : null;
        return nameCell ? nameCell.textContent.trim() : 'منتج';
    });
    
    let confirmMessage = `هل أنت متأكد من حذف ${selectedIds.length} منتج؟\n\n`;
    if (selectedRows.length <= 5) {
        confirmMessage += 'المنتجات المحددة:\n';
        selectedRows.forEach((name, index) => {
            confirmMessage += `${index + 1}. ${name}\n`;
        });
    } else {
        confirmMessage += `أول 5 منتجات:\n`;
        selectedRows.slice(0, 5).forEach((name, index) => {
            confirmMessage += `${index + 1}. ${name}\n`;
        });
        confirmMessage += `... و ${selectedRows.length - 5} منتج آخر\n`;
    }
    confirmMessage += '\n⚠️ هذا الإجراء لا يمكن التراجع عنه!';
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Disable button to prevent double submission
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    if (deleteBtn) {
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left: 5px;"></i> جاري الحذف...';
    }
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    // Add CSRF token if available
    const csrfToken = document.querySelector('input[name="csrf_token"]');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = csrfToken.value;
        form.appendChild(csrfInput);
    }
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'delete_selected_products';
    form.appendChild(actionInput);
    
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_products[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
    
    // Add change event listeners to all checkboxes
    document.querySelectorAll('.product-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('.product-row');
            if (row) {
                if (this.checked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            }
            updateSelectedCount();
        });
    });
    
    // Check URL parameter for tab navigation
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam && ['users', 'products', 'orders'].includes(tabParam)) {
        // Use the showTab function to switch to the requested tab
        showTab(tabParam);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
