<?php
$pageTitle = 'الملف الشخصي';
require_once __DIR__ . '/../config/config.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
$db = getDB();
$userId = $_SESSION['user_id'];

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect(SITE_URL . '/index.php');
}

// Get user statistics
$stats = [];
$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['total_orders'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT SUM(o.quantity * p.price) as total FROM orders o JOIN products p ON o.product_id = p.id WHERE o.user_id = ? AND o.status = 'COMPLETED'");
$stmt->execute([$userId]);
$stats['total_spent'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$stats['last_order'] = $stmt->fetch();

$error = '';
$success = '';

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
                $profilePicture = $user['profile_picture'] ?? null;
                
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
                    // Update user
                    $stmt = $db->prepare("UPDATE users SET name = ?, country = ?, phone = ?, profile_picture = ? WHERE id = ?");
                    if ($stmt->execute([$name, $country, $phone, $profilePicture, $userId])) {
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_profile_picture'] = $profilePicture;
                        $user['name'] = $name;
                        $user['country'] = $country;
                        $user['phone'] = $phone;
                        $user['profile_picture'] = $profilePicture;
                        $success = 'تم تحديث الملف الشخصي بنجاح';
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
            } elseif (!password_verify($currentPassword, $user['password'])) {
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
                    } else {
                        $error = 'فشل في تحديث كلمة المرور';
                    }
                }
            }
        }
    }
}

// Refresh user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Refresh stats
$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['total_orders'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT SUM(o.quantity * p.price) as total FROM orders o JOIN products p ON o.product_id = p.id WHERE o.user_id = ? AND o.status = 'COMPLETED'");
$stmt->execute([$userId]);
$stats['total_spent'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$stats['last_order'] = $stmt->fetch();

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <h1 style="text-align: center; margin-bottom: 40px; color: var(--primary-color);">الملف الشخصي</h1>

    <!-- Success/Error Messages -->
    <div id="messageContainer"></div>

    <!-- Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
            <i class="fas fa-shopping-bag" style="font-size: 32px; margin-bottom: 10px;"></i>
            <h3 style="margin: 0; font-size: 28px;"><?php echo $stats['total_orders']; ?></h3>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">إجمالي الطلبات</p>
        </div>

        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
            <i class="fas fa-money-bill-wave" style="font-size: 32px; margin-bottom: 10px;"></i>
            <h3 style="margin: 0; font-size: 28px;"><?php echo number_format($stats['total_spent'], 0); ?> د.ع</h3>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">إجمالي المشتريات</p>
        </div>

        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 25px; border-radius: 12px; color: white; text-align: center;">
            <i class="fas fa-calendar-check" style="font-size: 32px; margin-bottom: 10px;"></i>
            <h3 style="margin: 0; font-size: 20px;">
                <?php echo $stats['last_order'] ? date('Y-m-d', strtotime($stats['last_order']['created_at'])) : 'لا يوجد'; ?>
            </h3>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">آخر طلب</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; margin-bottom: 40px;">
        <!-- Profile Info Card -->
        <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
            <div style="position: relative; display: inline-block; margin-bottom: 20px;">
                <img src="<?php echo !empty($user['profile_picture']) ? SITE_URL . '/' . htmlspecialchars($user['profile_picture']) : SITE_URL . '/assets/images/avatar-1.jpg'; ?>" 
                     alt="صورة الملف الشخصي" 
                     style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color);">
            </div>
            <h2 style="margin: 10px 0; color: var(--text-color);"><?php echo htmlspecialchars($user['name']); ?></h2>
            <p style="color: var(--text-light); margin: 5px 0;">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
            </p>
            <p style="color: var(--text-light); margin: 5px 0;">
                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['country'] ?? 'غير محدد'); ?>
            </p>
            <?php if (!empty($user['phone'])): ?>
            <p style="color: var(--text-light); margin: 5px 0;">
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?>
            </p>
            <?php endif; ?>
            <p style="color: var(--text-light); margin: 5px 0; font-size: 14px;">
                <i class="fas fa-calendar"></i> تاريخ التسجيل: <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
            </p>
        </div>

        <!-- Update Forms -->
        <div>
            <!-- Update Profile Form -->
            <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h3 style="margin-bottom: 20px; color: var(--text-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
                    <i class="fas fa-user-edit"></i> تحديث الملف الشخصي
                </h3>
                <form method="POST" enctype="multipart/form-data" id="profileForm">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label for="name">الاسم</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($user['name']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="country">البلد</label>
                        <select id="country" name="country" style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 6px;">
                            <option value="">اختر البلد</option>
                            <option value="العراق" <?php echo ($user['country'] ?? '') === 'العراق' ? 'selected' : ''; ?>>العراق</option>
                            <option value="السعودية" <?php echo ($user['country'] ?? '') === 'السعودية' ? 'selected' : ''; ?>>السعودية</option>
                            <option value="الإمارات" <?php echo ($user['country'] ?? '') === 'الإمارات' ? 'selected' : ''; ?>>الإمارات</option>
                            <option value="الكويت" <?php echo ($user['country'] ?? '') === 'الكويت' ? 'selected' : ''; ?>>الكويت</option>
                            <option value="قطر" <?php echo ($user['country'] ?? '') === 'قطر' ? 'selected' : ''; ?>>قطر</option>
                            <option value="البحرين" <?php echo ($user['country'] ?? '') === 'البحرين' ? 'selected' : ''; ?>>البحرين</option>
                            <option value="عمان" <?php echo ($user['country'] ?? '') === 'عمان' ? 'selected' : ''; ?>>عمان</option>
                            <option value="الأردن" <?php echo ($user['country'] ?? '') === 'الأردن' ? 'selected' : ''; ?>>الأردن</option>
                            <option value="لبنان" <?php echo ($user['country'] ?? '') === 'لبنان' ? 'selected' : ''; ?>>لبنان</option>
                            <option value="سوريا" <?php echo ($user['country'] ?? '') === 'سوريا' ? 'selected' : ''; ?>>سوريا</option>
                            <option value="فلسطين" <?php echo ($user['country'] ?? '') === 'فلسطين' ? 'selected' : ''; ?>>فلسطين</option>
                            <option value="مصر" <?php echo ($user['country'] ?? '') === 'مصر' ? 'selected' : ''; ?>>مصر</option>
                            <option value="تونس" <?php echo ($user['country'] ?? '') === 'تونس' ? 'selected' : ''; ?>>تونس</option>
                            <option value="الجزائر" <?php echo ($user['country'] ?? '') === 'الجزائر' ? 'selected' : ''; ?>>الجزائر</option>
                            <option value="المغرب" <?php echo ($user['country'] ?? '') === 'المغرب' ? 'selected' : ''; ?>>المغرب</option>
                            <option value="السودان" <?php echo ($user['country'] ?? '') === 'السودان' ? 'selected' : ''; ?>>السودان</option>
                            <option value="اليمن" <?php echo ($user['country'] ?? '') === 'اليمن' ? 'selected' : ''; ?>>اليمن</option>
                            <option value="ليبيا" <?php echo ($user['country'] ?? '') === 'ليبيا' ? 'selected' : ''; ?>>ليبيا</option>
                            <option value="موريتانيا" <?php echo ($user['country'] ?? '') === 'موريتانيا' ? 'selected' : ''; ?>>موريتانيا</option>
                            <option value="جيبوتي" <?php echo ($user['country'] ?? '') === 'جيبوتي' ? 'selected' : ''; ?>>جيبوتي</option>
                            <option value="الصومال" <?php echo ($user['country'] ?? '') === 'الصومال' ? 'selected' : ''; ?>>الصومال</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="phone">رقم الهاتف (اختياري)</label>
                        <input type="text" id="phone" name="phone" 
                               placeholder="مثال: 07501234567"
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
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
                            <!-- Upload Progress Bar -->
                            <div id="uploadProgress" style="display: none; margin-top: 15px;">
                                <div style="background: #e5e7eb; border-radius: 10px; height: 8px; overflow: hidden;">
                                    <div id="progressBar" style="background: var(--primary-color); height: 100%; width: 0%; transition: width 0.3s;"></div>
                                </div>
                                <p id="progressText" style="margin-top: 8px; font-size: 12px; color: var(--text-light);">0%</p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> حفظ التغييرات
                    </button>
                </form>
            </div>

            <!-- Update Password Form -->
            <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 20px; color: var(--text-color); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
                    <i class="fas fa-lock"></i> تغيير كلمة المرور
                </h3>
                <form method="POST" id="passwordForm">
                    <input type="hidden" name="action" value="update_password">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
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
                        <div id="passwordStrength" style="margin-top: 8px; font-size: 12px;"></div>
                        <small style="color: var(--text-light); font-size: 12px; display: block; margin-top: 5px;">
                            يجب أن تحتوي على: <?php echo PASSWORD_MIN_LENGTH; ?> أحرف على الأقل، حرف واحد، ورقم واحد
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">تأكيد كلمة المرور</label>
                        <div style="position: relative;">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   style="padding-right: 40px;">
                            <i class="fas fa-eye" id="confirmPasswordToggle" 
                               style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light);"></i>
                        </div>
                        <div id="passwordMatch" style="margin-top: 8px; font-size: 12px;"></div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-key"></i> تحديث كلمة المرور
                    </button>
                </form>
            </div>
        </div>
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

document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const toggles = [
        { toggle: 'currentPasswordToggle', input: 'current_password' },
        { toggle: 'newPasswordToggle', input: 'new_password' },
        { toggle: 'confirmPasswordToggle', input: 'confirm_password' }
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

    // Password strength checker
    const newPasswordInput = document.getElementById('new_password');
    const passwordStrength = document.getElementById('passwordStrength');
    if (newPasswordInput && passwordStrength) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = [];
            
            if (password.length >= <?php echo PASSWORD_MIN_LENGTH; ?>) strength++;
            else feedback.push('<?php echo PASSWORD_MIN_LENGTH; ?> أحرف على الأقل');
            
            if (/[a-zA-Z]/.test(password)) strength++;
            else feedback.push('حرف واحد على الأقل');
            
            if (/[0-9]/.test(password)) strength++;
            else feedback.push('رقم واحد على الأقل');
            
            if (password.length >= 12) strength++;
            
            const colors = ['#ef4444', '#f59e0b', '#10b981'];
            const labels = ['ضعيفة', 'متوسطة', 'قوية'];
            
            if (password.length > 0) {
                const level = Math.min(strength - 1, 2);
                passwordStrength.innerHTML = `
                    <span style="color: ${colors[level]}">
                        <i class="fas fa-${level === 2 ? 'check-circle' : 'exclamation-triangle'}"></i>
                        قوة كلمة المرور: ${labels[level]}
                        ${feedback.length > 0 ? '<br><small>' + feedback.join(', ') + '</small>' : ''}
                    </span>
                `;
            } else {
                passwordStrength.innerHTML = '';
            }
        });
    }

    // Password match checker
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordMatch = document.getElementById('passwordMatch');
    if (confirmPasswordInput && passwordMatch && newPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                if (this.value === newPasswordInput.value) {
                    passwordMatch.innerHTML = '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> كلمات المرور متطابقة</span>';
                } else {
                    passwordMatch.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-times-circle"></i> كلمات المرور غير متطابقة</span>';
                }
            } else {
                passwordMatch.innerHTML = '';
            }
        });
    }

    // Enhanced profile picture upload with progress
    const profilePictureInput = document.getElementById('profile_picture');
    const uploadArea = document.getElementById('uploadArea');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    const removeImageBtn = document.getElementById('removeImage');
    const profilePictureUpload = document.getElementById('profilePictureUpload');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = <?php echo MAX_FILE_SIZE; ?>;
                if (file.size > maxSize) {
                    showMessage('حجم الملف كبير جداً. الحد الأقصى ' + (maxSize / 1024 / 1024) + ' MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Simulate upload progress
                uploadProgress.style.display = 'block';
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 10;
                    if (progress <= 90) {
                        progressBar.style.width = progress + '%';
                        progressText.textContent = progress + '%';
                    } else {
                        clearInterval(interval);
                    }
                }, 100);
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    clearInterval(interval);
                    progressBar.style.width = '100%';
                    progressText.textContent = '100%';
                    
                    setTimeout(() => {
                        previewImage.src = e.target.result;
                        uploadArea.style.display = 'none';
                        imagePreview.style.display = 'block';
                        uploadProgress.style.display = 'none';
                        profilePictureUpload.style.borderColor = '#10b981';
                        profilePictureUpload.style.background = '#f0fdf4';
                        
                        const profileImg = document.querySelector('img[alt="صورة الملف الشخصي"]');
                        if (profileImg) {
                            profileImg.src = e.target.result;
                        }
                    }, 300);
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
                uploadProgress.style.display = 'none';
                profilePictureUpload.style.borderColor = 'var(--border-color)';
                profilePictureUpload.style.background = '#f9fafb';
            });
        }
    }

    // Form submission with loading state
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
            }
        });
    }
    
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحديث...';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
