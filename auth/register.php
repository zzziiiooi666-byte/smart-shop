<?php
$pageTitle = 'إنشاء حساب';
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/pages/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'طلب غير صالح. يرجى المحاولة مرة أخرى.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $country = sanitize($_POST['country'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

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
            // Validate password strength
            $passwordErrors = validatePasswordStrength($password);
            if (!empty($passwordErrors)) {
                $error = implode('<br>', $passwordErrors);
            } else {
                require_once __DIR__ . '/../config/database.php';
                $db = getDB();

                // Check if email exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'البريد الإلكتروني مستخدم بالفعل';
                } else {
            // Handle profile picture upload
            $profilePicture = null;
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
                    $filename = 'profile_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                    $filepath = UPLOAD_DIR . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $profilePicture = 'uploads/' . $filename;
                    } else {
                        $error = 'فشل في رفع الصورة';
                    }
                }
            }
            
                    if (empty($error)) {
                        // Create user
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("INSERT INTO users (name, email, password, country, phone, profile_picture) VALUES (?, ?, ?, ?, ?, ?)");

                        if ($stmt->execute([$name, $email, $hashedPassword, $country, $phone, $profilePicture])) {
                            $success = 'تم إنشاء الحساب بنجاح! يمكنك تسجيل الدخول الآن.';
                            // Auto login
                            $userId = $db->lastInsertId();
                            $_SESSION['user_id'] = $userId;
                            $_SESSION['user_name'] = $name;
                            $_SESSION['user_email'] = $email;
                            $_SESSION['is_admin'] = false;
                            $_SESSION['user_profile_picture'] = $profilePicture;

                            header('Refresh: 2; url=' . SITE_URL . '/index.php');
                        } else {
                            $error = 'حدث خطأ أثناء إنشاء الحساب';
                        }
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 30px; color: var(--text-color);">إنشاء حساب جديد</h2>

    <?php if ($error): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($success); ?>
            <p style="margin-top: 10px;">سيتم توجيهك تلقائياً...</p>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" id="registerForm">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <div class="form-group">
            <label for="name">الاسم</label>
            <input type="text" id="name" name="name" required 
                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="email">البريد الإلكتروني</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="country">البلد</label>
            <select id="country" name="country" required style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 6px;">
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
            <label for="phone">رقم الهاتف (اختياري)</label>
            <input type="text" id="phone" name="phone" 
                   placeholder="مثال: 07501234567"
                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            <small style="color: var(--text-light); font-size: 12px;">
                رقم عراقي (مثال: 07501234567)
            </small>
        </div>

        <div class="form-group">
            <label for="profile_picture">صورة الملف الشخصي (اختياري)</label>
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

        <div class="form-group">
            <label for="password">كلمة المرور</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" required
                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" style="padding-right: 40px;">
                <i class="fas fa-eye" id="passwordToggle" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light); z-index: 10; pointer-events: auto;"></i>
            </div>
            <div id="passwordStrength" style="margin-top: 8px; font-size: 12px;"></div>
            <small style="color: var(--text-light); font-size: 12px; display: block; margin-top: 5px;">
                يجب أن تحتوي على: <?php echo PASSWORD_MIN_LENGTH; ?> أحرف على الأقل، حرف واحد، ورقم واحد
            </small>
        </div>

        <div class="form-group">
            <label for="confirm_password">تأكيد كلمة المرور</label>
            <div style="position: relative;">
                <input type="password" id="confirm_password" name="confirm_password" required style="padding-right: 40px;">
                <i class="fas fa-eye" id="confirmPasswordToggle" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light); z-index: 10; pointer-events: auto;"></i>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">إنشاء الحساب</button>
    </form>

    <p style="text-align: center; margin-top: 20px; color: var(--text-light);">
        لديك حساب بالفعل؟ <a href="<?php echo SITE_URL; ?>/login.php" style="color: var(--primary-color);">سجل الدخول</a>
    </p>
</div>

<script>
// Password toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');
    const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
    const confirmPasswordInput = document.getElementById('confirm_password');

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    }

    if (confirmPasswordToggle && confirmPasswordInput) {
        confirmPasswordToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                confirmPasswordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    }

    // Password strength checker
    const passwordStrength = document.getElementById('passwordStrength');
    if (passwordInput && passwordStrength) {
        passwordInput.addEventListener('input', function() {
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

    // Enhanced profile picture upload
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
                profilePictureUpload.style.borderColor = 'var(--border-color)';
                profilePictureUpload.style.background = '#f9fafb';
            });
        }
    }

    // Form submission with loading state
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري إنشاء الحساب...';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

