<?php
$pageTitle = 'الملف الشخصي للمدير';
require_once __DIR__ . '/../config/config.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';
$db = getDB();
$userId = $_SESSION['user_id'];

// Get admin data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$admin = $stmt->fetch();

if (!$admin) {
    redirect(SITE_URL . '/pages/index.php');
}

$error = '';
$success = '';

// Get admin statistics
$adminStats = [];
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$adminStats['total_users'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM products");
$stmt->execute();
$adminStats['total_products'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$adminStats['total_orders'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT SUM(o.quantity * p.price) as total FROM orders o JOIN products p ON o.product_id = p.id WHERE o.status = 'COMPLETED'");
$stmt->execute();
$adminStats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// Get recent activity logs
try {
    $stmt = $db->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$userId]);
    $activityLogs = $stmt->fetchAll();
} catch (Exception $e) {
    $activityLogs = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'طلب غير صالح. يرجى المحاولة مرة أخرى.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $name = sanitize($_POST['name'] ?? '');
            $country = sanitize($_POST['country'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            
            if (empty($name)) {
                $error = 'الاسم مطلوب';
            } elseif (!empty($phone) && !validatePhone($phone)) {
                $error = 'رقم الهاتف غير صحيح. يرجى إدخال رقم عراقي صحيح (مثال: 07501234567)';
            } else {
            // Handle profile picture upload
            $profilePicture = $admin['profile_picture'] ?? null;
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                
                // Validate file
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = MAX_FILE_SIZE;
                
                if (!in_array($file['type'], $allowedTypes)) {
                    $error = 'نوع الملف غير مدعوم. يرجى رفع صورة (JPG, PNG, GIF, WEBP)';
                } elseif ($file['size'] > $maxSize) {
                    $error = 'حجم الملف كبير جداً. الحد الأقصى ' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB';
                } else {
                    // Create uploads directory if it doesn't exist
                    if (!file_exists(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0755, true);
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
                    $filepath = UPLOAD_DIR . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        // Delete old profile picture if exists
                        if ($profilePicture && file_exists(__DIR__ . '/' . $profilePicture)) {
                            @unlink(__DIR__ . '/' . $profilePicture);
                        }
                        
                        $profilePicture = 'uploads/' . $filename;
                    } else {
                        $error = 'فشل في رفع الصورة';
                    }
                }
            }
            
            if (empty($error)) {
                // Update admin
                $stmt = $db->prepare("UPDATE users SET name = ?, country = ?, phone = ?, profile_picture = ? WHERE id = ?");
                if ($stmt->execute([$name, $country, $phone, $profilePicture, $userId])) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_profile_picture'] = $profilePicture;
                    $admin['name'] = $name;
                    $admin['country'] = $country;
                    $admin['phone'] = $phone;
                    $admin['profile_picture'] = $profilePicture;
                    $success = 'تم تحديث الملف الشخصي بنجاح';
                    logActivity($userId, 'update_profile', 'تم تحديث الملف الشخصي', $db);
                } else {
                    $error = 'فشل في تحديث الملف الشخصي';
                }
            }
        }
    }
    
    elseif ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'يرجى ملء جميع الحقول';
        } elseif (!password_verify($currentPassword, $admin['password'])) {
            $error = 'كلمة المرور الحالية غير صحيحة';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'كلمات المرور غير متطابقة';
        } else {
            // Validate password strength
            $passwordErrors = validatePasswordStrength($newPassword);
            if (!empty($passwordErrors)) {
                $error = implode('<br>', $passwordErrors);
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashedPassword, $userId])) {
                    $success = 'تم تحديث كلمة المرور بنجاح';
                    logActivity($userId, 'update_password', 'تم تحديث كلمة المرور', $db);
                } else {
                    $error = 'فشل في تحديث كلمة المرور';
                }
            }
        }
    }
    
    elseif ($action === 'add_user') {
        $name = sanitize($_POST['new_user_name'] ?? '');
        $email = sanitize($_POST['new_user_email'] ?? '');
        $password = $_POST['new_user_password'] ?? '';
        $confirmPassword = $_POST['new_user_confirm_password'] ?? '';
        $country = sanitize($_POST['new_user_country'] ?? '');
        $phone = sanitize($_POST['new_user_phone'] ?? '');
        $isAdmin = isset($_POST['new_user_is_admin']) ? 1 : 0;
        
        // Validation
        if (empty($name) || empty($email) || empty($password) || empty($country)) {
            $error = 'يرجى ملء جميع الحقول المطلوبة';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'البريد الإلكتروني غير صحيح';
        } elseif (!empty($phone) && !validatePhone($phone)) {
            $error = 'رقم الهاتف غير صحيح. يرجى إدخال رقم عراقي صحيح (مثال: 07501234567)';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'كلمة المرور يجب أن تكون على الأقل ' . PASSWORD_MIN_LENGTH . ' أحرف';
        } elseif ($password !== $confirmPassword) {
            $error = 'كلمات المرور غير متطابقة';
        } else {
            // Check if email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'البريد الإلكتروني مستخدم بالفعل';
            } else {
                // Handle profile picture upload
                $profilePicture = null;
                if (isset($_FILES['new_user_profile_picture']) && $_FILES['new_user_profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['new_user_profile_picture'];
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $maxSize = MAX_FILE_SIZE;
                    
                    if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
                        if (!file_exists(UPLOAD_DIR)) {
                            mkdir(UPLOAD_DIR, 0755, true);
                        }
                        
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'profile_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                        $filepath = UPLOAD_DIR . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $profilePicture = 'uploads/' . $filename;
                        }
                    }
                }
                
                // Create user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, country, phone, isAdmin, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$name, $email, $hashedPassword, $country, $phone ?: null, $isAdmin, $profilePicture])) {
                    $newUserId = $db->lastInsertId();
                    logActivity($userId, 'add_user', 'تم إضافة مستخدم جديد: ' . $name . ' (' . $email . ')', $db);
                    $success = 'تم إضافة المستخدم بنجاح';
                    // Clear form by redirecting
                    header('Location: ' . SITE_URL . '/admin/admin-profile.php?success=user_added');
                    exit;
                } else {
                    $error = 'حدث خطأ أثناء إضافة المستخدم';
                }
            }
        }
    }
    }
}

// Refresh admin data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$admin = $stmt->fetch();

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] === 'user_added') {
    $success = 'تم إضافة المستخدم بنجاح';
}

// Refresh admin data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$admin = $stmt->fetch();

// Refresh stats
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$adminStats['total_users'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM products");
$stmt->execute();
$adminStats['total_products'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$adminStats['total_orders'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT SUM(o.quantity * p.price) as total FROM orders o JOIN products p ON o.product_id = p.id WHERE o.status = 'COMPLETED'");
$stmt->execute();
$adminStats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// Get recent activity logs
try {
    $stmt = $db->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$userId]);
    $activityLogs = $stmt->fetchAll();
} catch (Exception $e) {
    $activityLogs = [];
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <h1 style="text-align: center; margin-bottom: 40px; color: var(--primary-color);">
        <i class="fas fa-user-shield"></i> الملف الشخصي للمدير
    </h1>

    <!-- Success/Error Messages -->
    <div id="messageContainer"></div>

    <!-- Admin Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
            <i class="fas fa-users" style="font-size: 32px; margin-bottom: 10px;"></i>
            <h3 style="margin: 0; font-size: 28px;"><?php echo $adminStats['total_users']; ?></h3>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">إجمالي المستخدمين</p>
        </div>

        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
            <i class="fas fa-box" style="font-size: 32px; margin-bottom: 10px;"></i>
            <h3 style="margin: 0; font-size: 28px;"><?php echo $adminStats['total_products']; ?></h3>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">إجمالي المنتجات</p>
        </div>

        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
            <i class="fas fa-shopping-bag" style="font-size: 32px; margin-bottom: 10px;"></i>
            <h3 style="margin: 0; font-size: 28px;"><?php echo $adminStats['total_orders']; ?></h3>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">إجمالي الطلبات</p>
        </div>

        <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
            <i class="fas fa-money-bill-wave" style="font-size: 32px; margin-bottom: 10px;"></i>
            <h3 style="margin: 0; font-size: 24px;"><?php echo number_format($adminStats['total_revenue'], 0); ?> د.ع</h3>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">إجمالي الإيرادات</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
        <!-- Admin Profile Info Card -->
        <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
            <div style="position: relative; display: inline-block; margin-bottom: 20px;">
                <img src="<?php echo !empty($admin['profile_picture']) ? SITE_URL . '/' . htmlspecialchars($admin['profile_picture']) : SITE_URL . '/assets/images/avatar-1.jpg'; ?>" 
                     alt="صورةالملف الشخصي  " 
                     style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color);">
            </div>
            <h2 style="margin: 10px 0; color: var(--text-color);">
                <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin['name']); ?>
            </h2>
            <p style="color: var(--text-light); margin: 5px 0;">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin['email']); ?>
            </p>
            <p style="color: var(--text-light); margin: 5px 0;">
                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($admin['country'] ?? 'غير محدد'); ?>
            </p>
            <?php if (!empty($admin['phone'])): ?>
            <p style="color: var(--text-light); margin: 5px 0;">
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($admin['phone']); ?>
            </p>
            <?php endif; ?>
            <p style="color: var(--text-light); margin: 5px 0; font-size: 14px;">
                <i class="fas fa-calendar"></i> تاريخ التسجيل: <?php echo date('Y-m-d', strtotime($admin['created_at'])); ?>
            </p>
            <span style="display: inline-block; background: var(--primary-color); color: white; padding: 5px 15px; border-radius: 20px; margin-top: 10px; font-size: 14px;">
                <i class="fas fa-crown"></i> مدير النظام
            </span>
        </div>

        <!-- Update Profile Form -->
        <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 20px; color: var(--text-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
                <i class="fas fa-user-edit"></i> تحديث الملف الشخصي 
            </h3>
            <form method="POST" enctype="multipart/form-data" id="adminProfileForm">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="name">الاسم</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($admin['name']); ?>">
                </div>

                <div class="form-group">
                    <label for="country">البلد</label>
                    <select id="country" name="country" style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 6px;">
                        <option value="">اختر البلد</option>
                        <option value="العراق" <?php echo ($admin['country'] ?? '') === 'العراق' ? 'selected' : ''; ?>>العراق</option>
                        <option value="السعودية" <?php echo ($admin['country'] ?? '') === 'السعودية' ? 'selected' : ''; ?>>السعودية</option>
                        <option value="الإمارات" <?php echo ($admin['country'] ?? '') === 'الإمارات' ? 'selected' : ''; ?>>الإمارات</option>
                        <option value="الكويت" <?php echo ($admin['country'] ?? '') === 'الكويت' ? 'selected' : ''; ?>>الكويت</option>
                        <option value="قطر" <?php echo ($admin['country'] ?? '') === 'قطر' ? 'selected' : ''; ?>>قطر</option>
                        <option value="البحرين" <?php echo ($admin['country'] ?? '') === 'البحرين' ? 'selected' : ''; ?>>البحرين</option>
                        <option value="عمان" <?php echo ($admin['country'] ?? '') === 'عمان' ? 'selected' : ''; ?>>عمان</option>
                        <option value="الأردن" <?php echo ($admin['country'] ?? '') === 'الأردن' ? 'selected' : ''; ?>>الأردن</option>
                        <option value="لبنان" <?php echo ($admin['country'] ?? '') === 'لبنان' ? 'selected' : ''; ?>>لبنان</option>
                        <option value="سوريا" <?php echo ($admin['country'] ?? '') === 'سوريا' ? 'selected' : ''; ?>>سوريا</option>
                        <option value="فلسطين" <?php echo ($admin['country'] ?? '') === 'فلسطين' ? 'selected' : ''; ?>>فلسطين</option>
                        <option value="مصر" <?php echo ($admin['country'] ?? '') === 'مصر' ? 'selected' : ''; ?>>مصر</option>
                        <option value="تونس" <?php echo ($admin['country'] ?? '') === 'تونس' ? 'selected' : ''; ?>>تونس</option>
                        <option value="الجزائر" <?php echo ($admin['country'] ?? '') === 'الجزائر' ? 'selected' : ''; ?>>الجزائر</option>
                        <option value="المغرب" <?php echo ($admin['country'] ?? '') === 'المغرب' ? 'selected' : ''; ?>>المغرب</option>
                        <option value="السودان" <?php echo ($admin['country'] ?? '') === 'السودان' ? 'selected' : ''; ?>>السودان</option>
                        <option value="اليمن" <?php echo ($admin['country'] ?? '') === 'اليمن' ? 'selected' : ''; ?>>اليمن</option>
                        <option value="ليبيا" <?php echo ($admin['country'] ?? '') === 'ليبيا' ? 'selected' : ''; ?>>ليبيا</option>
                        <option value="موريتانيا" <?php echo ($admin['country'] ?? '') === 'موريتانيا' ? 'selected' : ''; ?>>موريتانيا</option>
                        <option value="جيبوتي" <?php echo ($admin['country'] ?? '') === 'جيبوتي' ? 'selected' : ''; ?>>جيبوتي</option>
                        <option value="الصومال" <?php echo ($admin['country'] ?? '') === 'الصومال' ? 'selected' : ''; ?>>الصومال</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phone">رقم الهاتف (اختياري)</label>
                    <input type="text" id="phone" name="phone" 
                           placeholder="مثال: 07501234567"
                           value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                    <small style="color: var(--text-light); font-size: 12px;">
                        رقم عراقي (مثال: 07501234567)
                    </small>
                </div>

                <div class="form-group">
                    <label for="profile_picture">صورة الملف الشخصي</label>
                    <div id="profilePictureUpload" style="border: 2px dashed var(--border-color); border-radius: 12px; padding: 30px; text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.3s ease; position: relative;">
                        <input type="file" id="profile_picture" name="profile_picture" 
                               accept="image/jpeg,image/png,image/gif,image/webp"
                               style="position: absolute; opacity: 0; width: 100%; height: 100%; top: 0; right: 0; cursor: pointer; z-index: 2;">
                        <div id="uploadArea" style="pointer-events: none;">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: var(--primary-color); margin-bottom: 15px; display: block;"></i>
                            <p style="color: var(--text-color); font-size: 16px; font-weight: 500; margin: 10px 0;">
                                <span style="color: var(--primary-color);">انقر للاختيار</span> أو اسحب الصورة هنا
                            </p>
                            <p style="color: var(--text-light); font-size: 12px; margin: 5px 0;">
                                الحد الأقصى: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?> MB
                            </p>
                            <p style="color: var(--text-light); font-size: 11px; margin-top: 5px;">
                                JPG, PNG, GIF, WEBP
                            </p>
                        </div>
                        <div id="imagePreview" style="display: none;">
                            <img id="previewImage" src="" alt="معاينة الصورة" 
                                 style="max-width: 200px; max-height: 200px; border-radius: 12px; object-fit: cover; border: 3px solid var(--primary-color); box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            <p style="color: var(--text-color); margin-top: 15px; font-size: 14px;">
                                <i class="fas fa-check-circle" style="color: #10b981;"></i> تم اختيار الصورة
                            </p>
                            <button type="button" id="removeImage" style="background: #ef4444; color: white; border: none; padding: 8px 20px; border-radius: 6px; margin-top: 10px; cursor: pointer; font-size: 14px;">
                                <i class="fas fa-times"></i> إزالة الصورة
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> حفظ التغييرات
                </button>
            </form>
        </div>
    </div>

    <!-- Update Password Form -->
    <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 40px;">
        <h3 style="margin-bottom: 20px; color: var(--text-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
            <i class="fas fa-lock"></i> تغيير كلمة المرور
        </h3>
        <form method="POST" id="adminPasswordForm">
            <input type="hidden" name="action" value="update_password">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label for="current_password">كلمة المرور الحالية</label>
                    <div style="position: relative;">
                        <input type="password" id="current_password" name="current_password" required 
                               style="padding-right: 40px;">
                        <i class="fas fa-eye" id="currentPasswordToggle" 
                           style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light);"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">كلمة المرور الجديدة</label>
                    <div style="position: relative;">
                        <input type="password" id="new_password" name="new_password" required 
                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" style="padding-right: 40px;">
                        <i class="fas fa-eye" id="newPasswordToggle" 
                           style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light);"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">تأكيد كلمة المرور</label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               style="padding-right: 40px;">
                        <i class="fas fa-eye" id="confirmPasswordToggle" 
                           style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light);"></i>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                <i class="fas fa-key"></i> تحديث كلمة المرور
            </button>
        </form>
    </div>

    <!-- Add User Form -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: white; border-bottom: 2px solid rgba(255,255,255,0.3); padding-bottom: 10px;">
            <i class="fas fa-user-plus"></i> إضافة مستخدم جديد
        </h3>
        <form method="POST" enctype="multipart/form-data" id="addUserForm">
            <input type="hidden" name="action" value="add_user">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label for="new_user_name" style="color: white;">الاسم</label>
                    <input type="text" id="new_user_name" name="new_user_name" required>
                </div>

                <div class="form-group">
                    <label for="new_user_email" style="color: white;">البريد الإلكتروني</label>
                    <input type="email" id="new_user_email" name="new_user_email" required>
                </div>

                <div class="form-group">
                    <label for="new_user_country" style="color: white;">البلد</label>
                    <select id="new_user_country" name="new_user_country" required style="width: 100%; padding: 12px; border: 2px solid rgba(255,255,255,0.3); border-radius: 6px; background: white;">
                        <option value="">اختر البلد</option>
                        <option value="العراق">العراق</option>
                        <option value="السعودية">السعودية</option>
                        <option value="الإمارات">الإمارات</option>
                        <option value="الكويت">الكويت</option>
                        <option value="قطر">قطر</option>
                        <option value="البحرين">البحرين</option>
                        <option value="عمان">عمان</option>
                        <option value="الأردن">الأردن</option>
                        <option value="لبنان">لبنان</option>
                        <option value="سوريا">سوريا</option>
                        <option value="فلسطين">فلسطين</option>
                        <option value="مصر">مصر</option>
                        <option value="تونس">تونس</option>
                        <option value="الجزائر">الجزائر</option>
                        <option value="المغرب">المغرب</option>
                        <option value="السودان">السودان</option>
                        <option value="اليمن">اليمن</option>
                        <option value="ليبيا">ليبيا</option>
                        <option value="موريتانيا">موريتانيا</option>
                        <option value="جيبوتي">جيبوتي</option>
                        <option value="الصومال">الصومال</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="new_user_phone" style="color: white;">رقم الهاتف (اختياري)</label>
                    <input type="text" id="new_user_phone" name="new_user_phone" 
                           placeholder="مثال: 07501234567"
                           style="width: 100%; padding: 12px; border: 2px solid rgba(255,255,255,0.3); border-radius: 6px; background: white;">
                    <small style="color: rgba(255,255,255,0.8); font-size: 11px; display: block; margin-top: 5px;">
                        رقم عراقي (مثال: 07501234567)
                    </small>
                </div>

                <div class="form-group">
                    <label for="new_user_profile_picture" style="color: white;">صورة الملف الشخصي</label>
                    <div id="newUserProfilePictureUpload" style="border: 2px dashed rgba(255,255,255,0.5); border-radius: 12px; padding: 25px; text-align: center; background: rgba(255,255,255,0.1); cursor: pointer; transition: all 0.3s ease; position: relative; backdrop-filter: blur(10px);">
                        <input type="file" id="new_user_profile_picture" name="new_user_profile_picture" 
                               accept="image/jpeg,image/png,image/gif,image/webp"
                               style="position: absolute; opacity: 0; width: 100%; height: 100%; top: 0; right: 0; cursor: pointer; z-index: 2;">
                        <div id="newUserUploadArea" style="pointer-events: none;">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 40px; color: white; margin-bottom: 12px; display: block;"></i>
                            <p style="color: white; font-size: 14px; font-weight: 500; margin: 8px 0;">
                                <span style="text-decoration: underline;">انقر للاختيار</span> أو اسحب الصورة
                            </p>
                            <p style="color: rgba(255,255,255,0.8); font-size: 11px; margin: 5px 0;">
                                الحد الأقصى: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?> MB
                            </p>
                        </div>
                        <div id="newUserImagePreview" style="display: none;">
                            <img id="newUserPreviewImage" src="" alt="معاينة الصورة" 
                                 style="max-width: 150px; max-height: 150px; border-radius: 12px; object-fit: cover; border: 3px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                            <p style="color: white; margin-top: 12px; font-size: 13px;">
                                <i class="fas fa-check-circle"></i> تم الاختيار
                            </p>
                            <button type="button" id="removeNewUserImage" style="background: rgba(255,255,255,0.9); color: #ef4444; border: none; padding: 6px 16px; border-radius: 6px; margin-top: 8px; cursor: pointer; font-size: 12px; font-weight: bold;">
                                <i class="fas fa-times"></i> إزالة
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_user_password" style="color: white;">كلمة المرور</label>
                    <div style="position: relative;">
                        <input type="password" id="new_user_password" name="new_user_password" required 
                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" style="padding-right: 40px;">
                        <i class="fas fa-eye" id="newUserPasswordToggle" 
                           style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light);"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_user_confirm_password" style="color: white;">تأكيد كلمة المرور</label>
                    <div style="position: relative;">
                        <input type="password" id="new_user_confirm_password" name="new_user_confirm_password" required 
                               style="padding-right: 40px;">
                        <i class="fas fa-eye" id="newUserConfirmPasswordToggle" 
                           style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light);"></i>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="color: white; display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" id="new_user_is_admin" name="new_user_is_admin" 
                           style="width: 20px; height: 20px; cursor: pointer;">
                    <span>جعل المستخدم مدير</span>
                </label>
            </div>

            <button type="submit" class="btn" style="width: 100%; background: white; color: var(--primary-color); font-weight: bold;">
                <i class="fas fa-user-plus"></i> إضافة المستخدم
            </button>
        </form>
    </div>

    <!-- Activity Log -->
    <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 40px;">
        <h3 style="margin-bottom: 20px; color: var(--text-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
            <i class="fas fa-history"></i> سجل الأنشطة
        </h3>
        <?php if (empty($activityLogs)): ?>
            <p style="text-align: center; color: var(--text-light); padding: 40px;">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                لا توجد أنشطة مسجلة بعد
            </p>
        <?php else: ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f9fafb; position: sticky; top: 0;">
                        <tr>
                            <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e5e7eb;">النشاط</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e5e7eb;">الوصف</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e5e7eb;">IP</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e5e7eb;">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activityLogs as $log): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 12px;">
                                <span style="background: var(--primary-color); color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td style="padding: 12px; color: var(--text-color);">
                                <?php echo htmlspecialchars($log['description'] ?? '-'); ?>
                            </td>
                            <td style="padding: 12px; color: var(--text-light); font-size: 12px;">
                                <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                            </td>
                            <td style="padding: 12px; color: var(--text-light); font-size: 12px;">
                                <?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Show messages
<?php if ($error): ?>
showMessage('<?php echo addslashes($error); ?>', 'error');
<?php endif; ?>
<?php if ($success): ?>
showMessage('<?php echo addslashes($success); ?>', 'success');
<?php endif; ?>

function showMessage(message, type) {
    const container = document.getElementById('messageContainer');
    const bgColor = type === 'error' ? '#fee2e2' : '#d1fae5';
    const textColor = type === 'error' ? '#991b1b' : '#065f46';
    const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
    
    container.innerHTML = `
        <div style="background: ${bgColor}; color: ${textColor}; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

// Password toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    // Admin password toggles
    const toggles = [
        { toggle: 'currentPasswordToggle', input: 'current_password' },
        { toggle: 'newPasswordToggle', input: 'new_password' },
        { toggle: 'confirmPasswordToggle', input: 'confirm_password' },
        { toggle: 'newUserPasswordToggle', input: 'new_user_password' },
        { toggle: 'newUserConfirmPasswordToggle', input: 'new_user_confirm_password' }
    ];

    toggles.forEach(({ toggle, input }) => {
        const toggleEl = document.getElementById(toggle);
        const inputEl = document.getElementById(input);
        if (toggleEl && inputEl) {
            toggleEl.addEventListener('click', function() {
                if (inputEl.type === 'password') {
                    inputEl.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    inputEl.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            });
        }
    });

    // Enhanced profile picture upload for admin
    const profilePictureInput = document.getElementById('profile_picture');
    const uploadArea = document.getElementById('uploadArea');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    const removeImageBtn = document.getElementById('removeImage');
    const profilePictureUpload = document.getElementById('profilePictureUpload');
    
    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = <?php echo MAX_FILE_SIZE; ?>;
                if (file.size > maxSize) {
                    alert('حجم الملف كبير جداً. الحد الأقصى ' + (maxSize / 1024 / 1024) + ' MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    uploadArea.style.display = 'none';
                    imagePreview.style.display = 'block';
                    profilePictureUpload.style.borderColor = '#10b981';
                    profilePictureUpload.style.background = '#f0fdf4';
                    
                    const profileImg = document.querySelector('img[alt="صورةالملف الشخصي  "]');
                    if (profileImg) {
                        profileImg.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
        
        profilePictureUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--primary-color)';
            this.style.background = '#f0f7ff';
        });
        
        profilePictureUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            if (!imagePreview.style.display || imagePreview.style.display === 'none') {
                this.style.borderColor = 'var(--border-color)';
                this.style.background = '#f9fafb';
            }
        });
        
        profilePictureUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                profilePictureInput.files = files;
                profilePictureInput.dispatchEvent(new Event('change'));
            }
        });
        
        if (removeImageBtn) {
            removeImageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                profilePictureInput.value = '';
                uploadArea.style.display = 'block';
                imagePreview.style.display = 'none';
                profilePictureUpload.style.borderColor = 'var(--border-color)';
                profilePictureUpload.style.background = '#f9fafb';
            });
        }
    }
    
    // Enhanced profile picture upload for new user
    const newUserProfilePictureInput = document.getElementById('new_user_profile_picture');
    const newUserUploadArea = document.getElementById('newUserUploadArea');
    const newUserImagePreview = document.getElementById('newUserImagePreview');
    const newUserPreviewImage = document.getElementById('newUserPreviewImage');
    const removeNewUserImageBtn = document.getElementById('removeNewUserImage');
    const newUserProfilePictureUpload = document.getElementById('newUserProfilePictureUpload');
    
    if (newUserProfilePictureInput) {
        newUserProfilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = <?php echo MAX_FILE_SIZE; ?>;
                if (file.size > maxSize) {
                    alert('حجم الملف كبير جداً. الحد الأقصى ' + (maxSize / 1024 / 1024) + ' MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    newUserPreviewImage.src = e.target.result;
                    newUserUploadArea.style.display = 'none';
                    newUserImagePreview.style.display = 'block';
                    newUserProfilePictureUpload.style.borderColor = 'rgba(255,255,255,0.8)';
                    newUserProfilePictureUpload.style.background = 'rgba(16,185,129,0.2)';
                };
                reader.readAsDataURL(file);
            }
        });
        
        newUserProfilePictureUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'rgba(255,255,255,0.9)';
            this.style.background = 'rgba(255,255,255,0.15)';
        });
        
        newUserProfilePictureUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            if (!newUserImagePreview.style.display || newUserImagePreview.style.display === 'none') {
                this.style.borderColor = 'rgba(255,255,255,0.5)';
                this.style.background = 'rgba(255,255,255,0.1)';
            }
        });
        
        newUserProfilePictureUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                newUserProfilePictureInput.files = files;
                newUserProfilePictureInput.dispatchEvent(new Event('change'));
            }
        });
        
        if (removeNewUserImageBtn) {
            removeNewUserImageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                newUserProfilePictureInput.value = '';
                newUserUploadArea.style.display = 'block';
                newUserImagePreview.style.display = 'none';
                newUserProfilePictureUpload.style.borderColor = 'rgba(255,255,255,0.5)';
                newUserProfilePictureUpload.style.background = 'rgba(255,255,255,0.1)';
            });
        }
    }

    // Form submission with loading state
    const adminProfileForm = document.getElementById('adminProfileForm');
    const adminPasswordForm = document.getElementById('adminPasswordForm');
    const addUserForm = document.getElementById('addUserForm');
    
    if (adminProfileForm) {
        adminProfileForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
            }
        });
    }
    
    if (adminPasswordForm) {
        adminPasswordForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحديث...';
            }
        });
    }
    
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإضافة...';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

