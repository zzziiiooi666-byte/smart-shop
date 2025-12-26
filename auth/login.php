<?php
$pageTitle = 'تسجيل الدخول';
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/pages/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        require_once __DIR__ . '/../config/database.php';
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = (bool)$user['isAdmin'];
            $_SESSION['user_profile_picture'] = $user['profile_picture'] ?? null;

            redirect(SITE_URL . '/pages/index.php');
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 30px; color: var(--text-color);">تسجيل الدخول</h2>

    <?php if ($error): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">البريد الإلكتروني</label>
            <input type="email" id="email" name="email" required 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="password">كلمة المرور</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" required style="padding-right: 40px;">
                <i class="fas fa-eye" id="passwordToggle" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light);"></i>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">تسجيل الدخول</button>
    </form>

    <p style="text-align: center; margin-top: 20px; color: var(--text-light);">
        ليس لديك حساب؟ <a href="<?php echo SITE_URL; ?>/auth/register.php" style="color: var(--primary-color);">سجل الآن</a>
    </p>

    <!--<div style="margin-top: 30px; padding: 20px; background: #f3f4f6; border-radius: 6px;">
        <h4 style="margin-bottom: 10px;">حساب تجريبي:</h4>
        <p><strong>البريد:</strong> admin@shop.com</p>
        <p><strong>كلمة المرور:</strong> Admin@123</p>
    </div>-->
</div>

<script>
// Password toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
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
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

