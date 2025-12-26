<?php
$pageTitle = 'إدارة الفئات';
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/auth/login.php');
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Create category_list table if it doesn't exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS category_list (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        icon VARCHAR(100) NOT NULL,
        gradient_start VARCHAR(7) NOT NULL,
        gradient_end VARCHAR(7) NOT NULL,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Check if table is empty and insert default categories
    $stmt = $db->query("SELECT COUNT(*) as count FROM category_list");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        $defaultCategories = [
            ['ملابس-رجالية', 'fa-tshirt', '#667eea', '#764ba2', 1],
            ['ملابس-نسائية', 'fa-female', '#f093fb', '#f5576c', 2],
            ['أحذية', 'fa-shoe-prints', '#4facfe', '#00f2fe', 3],
            ['إلكترونيات', 'fa-mobile-alt', '#43e97b', '#38f9d7', 4],
            ['أجهزة-منزلية', 'fa-home', '#fa709a', '#fee140', 5],
            ['أثاث', 'fa-couch', '#8B4513', '#A0522D', 6],
            ['مستحضرات-تجميل', 'fa-spa', '#ff9a9e', '#fecfef', 7],
            ['عطور', 'fa-wind', '#ffecd2', '#fcb69f', 8],
            ['ألعاب', 'fa-gamepad', '#667eea', '#764ba2', 9],
            ['كتب', 'fa-book', '#f093fb', '#f5576c', 10],
            ['رياضة', 'fa-basketball-ball', '#4facfe', '#00f2fe', 11],
            ['صحة-وتجميل', 'fa-heartbeat', '#43e97b', '#38f9d7', 12],
            ['أدوات-منزلية', 'fa-tools', '#fa709a', '#fee140', 13],
            ['سيارات', 'fa-car', '#a8edea', '#fed6e3', 14],
            ['هواتف', 'fa-mobile-alt', '#ff9a9e', '#fecfef', 15]
        ];
        
        $stmt = $db->prepare("INSERT INTO category_list (name, icon, gradient_start, gradient_end, display_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($defaultCategories as $cat) {
            try {
                $stmt->execute($cat);
            } catch (PDOException $e) {
                // Ignore duplicate key errors
            }
        }
    }
} catch (PDOException $e) {
    // Table might already exist, continue
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $name = sanitize($_POST['name'] ?? '');
        $icon = sanitize($_POST['icon'] ?? 'fa-tag');
        $gradient_start = sanitize($_POST['gradient_start'] ?? '#667eea');
        $gradient_end = sanitize($_POST['gradient_end'] ?? '#764ba2');
        $display_order = (int)($_POST['display_order'] ?? 0);
        
        if (empty($name)) {
            $error = 'يرجى إدخال اسم الفئة';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO category_list (name, icon, gradient_start, gradient_end, display_order) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $icon, $gradient_start, $gradient_end, $display_order])) {
                    $success = 'تم إضافة الفئة بنجاح';
                } else {
                    $error = 'حدث خطأ أثناء إضافة الفئة';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'هذه الفئة موجودة بالفعل';
                } else {
                    $error = 'حدث خطأ: ' . $e->getMessage();
                }
            }
        }
    }
    
    elseif ($action === 'update_category') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $icon = sanitize($_POST['icon'] ?? 'fa-tag');
        $gradient_start = sanitize($_POST['gradient_start'] ?? '#667eea');
        $gradient_end = sanitize($_POST['gradient_end'] ?? '#764ba2');
        $display_order = (int)($_POST['display_order'] ?? 0);
        
        if (empty($name) || $id <= 0) {
            $error = 'بيانات غير صحيحة';
        } else {
            $stmt = $db->prepare("UPDATE category_list SET name = ?, icon = ?, gradient_start = ?, gradient_end = ?, display_order = ? WHERE id = ?");
            if ($stmt->execute([$name, $icon, $gradient_start, $gradient_end, $display_order, $id])) {
                $success = 'تم تحديث الفئة بنجاح';
            } else {
                $error = 'حدث خطأ أثناء تحديث الفئة';
            }
        }
    }
    
    elseif ($action === 'delete_category') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM category_list WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = 'تم حذف الفئة بنجاح';
            } else {
                $error = 'حدث خطأ أثناء حذف الفئة';
            }
        }
    }
}

// Get all categories
$stmt = $db->prepare("SELECT * FROM category_list ORDER BY display_order ASC, name ASC");
$stmt->execute();
$categories = $stmt->fetchAll();

// Common Font Awesome icons
$commonIcons = [
    'fa-tshirt', 'fa-female', 'fa-shoe-prints', 'fa-mobile-alt', 'fa-home',
    'fa-couch', 'fa-spa', 'fa-wind', 'fa-gamepad', 'fa-book',
    'fa-basketball-ball', 'fa-heartbeat', 'fa-tools', 'fa-car',
    'fa-laptop', 'fa-headphones', 'fa-camera', 'fa-watch', 'fa-gift',
    'fa-utensils', 'fa-hamburger', 'fa-pizza-slice', 'fa-coffee', 'fa-wine-glass',
    'fa-birthday-cake', 'fa-ice-cream', 'fa-baby', 'fa-dumbbell', 'fa-music',
    'fa-paint-brush', 'fa-gem', 'fa-shopping-bag', 'fa-tag', 'fa-store',
    'fa-bowl-food', 'fa-fish', 'fa-drumstick-bite', 'fa-cookie-bite'
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1200px; margin: 40px auto;">
    <h1 style="text-align: center; margin-bottom: 30px; color: var(--primary-color);">
        <i class="fas fa-tags" style="margin-left: 10px;"></i>
        إدارة الفئات
    </h1>

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

    <!-- Add Category Form -->
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px; color: var(--text-color);">
            <i class="fas fa-plus-circle" style="margin-left: 8px; color: var(--primary-color);"></i>
            إضافة فئة جديدة
        </h2>
        <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <input type="hidden" name="action" value="add_category">
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">اسم الفئة *</label>
                <input type="text" name="name" required
                       placeholder="مثال: ملابس-رجالية"
                       style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
            </div>
            
            <div style="grid-column: 1 / -1;">
                <label style="display: block; margin-bottom: 12px; font-weight: 600; color: var(--text-color);">الأيقونة *</label>
                <div style="border: 2px solid #e5e7eb; border-radius: 8px; padding: 15px; background: #f9fafb; max-height: 300px; overflow-y: auto;">
                    <div id="icon-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px;">
                        <?php foreach ($commonIcons as $icon): ?>
                            <div class="icon-option" 
                                 data-icon="<?php echo htmlspecialchars($icon); ?>"
                                 style="padding: 15px; border: 2px solid #e5e7eb; border-radius: 8px; text-align: center; cursor: pointer; background: white; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px;">
                                <i class="fas <?php echo htmlspecialchars($icon); ?>" style="font-size: 24px; color: var(--primary-color); margin-bottom: 5px;"></i>
                                <small style="font-size: 10px; color: #6b7280; word-break: break-word; text-align: center;"><?php echo htmlspecialchars(str_replace('fa-', '', $icon)); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <input type="hidden" name="icon" id="selected-icon" value="<?php echo htmlspecialchars($commonIcons[0]); ?>" required>
                <div style="margin-top: 10px; padding: 10px; background: #e0f2fe; border-radius: 6px; text-align: center;">
                    <small style="color: #0369a1; font-weight: 600;">الأيقونة المختارة:</small>
                    <div style="margin-top: 5px;">
                        <i class="fas" id="icon-preview" style="font-size: 32px; color: var(--primary-color);"></i>
                        <span id="icon-name" style="display: block; margin-top: 5px; color: #0369a1; font-size: 12px; font-weight: 600;"></span>
                    </div>
                </div>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">لون البداية *</label>
                <input type="color" name="gradient_start" value="#667eea" required
                       style="width: 100%; height: 50px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">لون النهاية *</label>
                <input type="color" name="gradient_end" value="#764ba2" required
                       style="width: 100%; height: 50px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">ترتيب العرض</label>
                <input type="number" name="display_order" value="0" min="0"
                       style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
            </div>
            
            <div style="grid-column: 1 / -1;">
                <button type="submit" style="background: var(--primary-color); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-plus" style="margin-left: 8px;"></i>
                    إضافة الفئة
                </button>
            </div>
        </form>
    </div>

    <!-- Categories List -->
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-bottom: 20px; color: var(--text-color);">
            <i class="fas fa-list" style="margin-left: 8px; color: var(--primary-color);"></i>
            قائمة الفئات (<?php echo count($categories); ?>)
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
            <?php foreach ($categories as $category): ?>
                <div style="border: 2px solid #e5e7eb; border-radius: 8px; padding: 20px; position: relative;">
                    <!-- Category Preview -->
                    <div style="background: linear-gradient(135deg, <?php echo htmlspecialchars($category['gradient_start']); ?> 0%, <?php echo htmlspecialchars($category['gradient_end']); ?> 100%); padding: 20px; border-radius: 8px; text-align: center; color: white; height: 120px; display: flex; flex-direction: column; justify-content: center; margin-bottom: 15px;">
                        <i class="fas <?php echo htmlspecialchars($category['icon']); ?>" style="font-size: 32px; margin-bottom: 8px;"></i>
                        <h4 style="font-size: 14px; margin: 0; font-weight: 600;"><?php echo htmlspecialchars($category['name']); ?></h4>
                    </div>
                    
                    <!-- Category Info -->
                    <div style="margin-bottom: 15px;">
                        <p style="margin: 5px 0; font-size: 13px; color: #6b7280;">
                            <strong>الأيقونة:</strong> <?php echo htmlspecialchars($category['icon']); ?>
                        </p>
                        <p style="margin: 5px 0; font-size: 13px; color: #6b7280;">
                            <strong>الترتيب:</strong> <?php echo $category['display_order']; ?>
                        </p>
                    </div>
                    
                    <!-- Actions -->
                    <div style="display: flex; gap: 8px;">
                        <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                style="flex: 1; background: #f59e0b; color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer; font-size: 13px;">
                            <i class="fas fa-edit"></i> تعديل
                        </button>
                        <form method="POST" style="flex: 1; display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه الفئة؟')">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                            <button type="submit" style="width: 100%; background: #ef4444; color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                <i class="fas fa-trash"></i> حذف
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; right: 0; bottom: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 style="margin-bottom: 20px; color: var(--text-color);">تعديل الفئة</h2>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="update_category">
            <input type="hidden" name="id" id="edit-id">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">اسم الفئة *</label>
                <input type="text" name="name" id="edit-name" required
                       style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 12px; font-weight: 600; color: var(--text-color);">الأيقونة *</label>
                <div style="border: 2px solid #e5e7eb; border-radius: 8px; padding: 15px; background: #f9fafb; max-height: 300px; overflow-y: auto;">
                    <div id="edit-icon-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px;">
                        <?php foreach ($commonIcons as $icon): ?>
                            <div class="edit-icon-option" 
                                 data-icon="<?php echo htmlspecialchars($icon); ?>"
                                 style="padding: 15px; border: 2px solid #e5e7eb; border-radius: 8px; text-align: center; cursor: pointer; background: white; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px;">
                                <i class="fas <?php echo htmlspecialchars($icon); ?>" style="font-size: 24px; color: var(--primary-color); margin-bottom: 5px;"></i>
                                <small style="font-size: 10px; color: #6b7280; word-break: break-word; text-align: center;"><?php echo htmlspecialchars(str_replace('fa-', '', $icon)); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <input type="hidden" name="icon" id="edit-selected-icon" value="" required>
                <div style="margin-top: 10px; padding: 10px; background: #e0f2fe; border-radius: 6px; text-align: center;">
                    <small style="color: #0369a1; font-weight: 600;">الأيقونة المختارة:</small>
                    <div style="margin-top: 5px;">
                        <i class="fas" id="edit-icon-preview" style="font-size: 32px; color: var(--primary-color);"></i>
                        <span id="edit-icon-name" style="display: block; margin-top: 5px; color: #0369a1; font-size: 12px; font-weight: 600;"></span>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">لون البداية *</label>
                    <input type="color" name="gradient_start" id="edit-gradient-start" required
                           style="width: 100%; height: 50px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">لون النهاية *</label>
                    <input type="color" name="gradient_end" id="edit-gradient-end" required
                           style="width: 100%; height: 50px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">ترتيب العرض</label>
                <input type="number" name="display_order" id="edit-display-order" min="0"
                       style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex: 1; background: var(--primary-color); color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    حفظ التغييرات
                </button>
                <button type="button" onclick="closeEditModal()" style="flex: 1; background: #6b7280; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer;">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Icon selection for add form
document.addEventListener('DOMContentLoaded', function() {
    // Initialize first icon as selected
    const firstIcon = document.querySelector('.icon-option');
    if (firstIcon) {
        selectIcon(firstIcon);
    }
    
    // Add click listeners to all icon options
    document.querySelectorAll('.icon-option').forEach(option => {
        option.addEventListener('click', function() {
            selectIcon(this);
        });
    });
    
    // Add hover effects
    document.querySelectorAll('.icon-option').forEach(option => {
        option.addEventListener('mouseenter', function() {
            if (!this.classList.contains('selected')) {
                this.style.borderColor = '#93c5fd';
                this.style.transform = 'scale(1.05)';
            }
        });
        option.addEventListener('mouseleave', function() {
            if (!this.classList.contains('selected')) {
                this.style.borderColor = '#e5e7eb';
                this.style.transform = 'scale(1)';
            }
        });
    });
});

function selectIcon(element) {
    // Remove selected class from all icons
    document.querySelectorAll('.icon-option').forEach(opt => {
        opt.classList.remove('selected');
        opt.style.borderColor = '#e5e7eb';
        opt.style.background = 'white';
        opt.style.transform = 'scale(1)';
    });
    
    // Add selected class to clicked icon
    element.classList.add('selected');
    element.style.borderColor = 'var(--primary-color)';
    element.style.background = '#e0f2fe';
    element.style.transform = 'scale(1.05)';
    
    // Update hidden input and preview
    const icon = element.dataset.icon;
    document.getElementById('selected-icon').value = icon;
    const preview = document.getElementById('icon-preview');
    preview.className = 'fas ' + icon;
    document.getElementById('icon-name').textContent = icon.replace('fa-', '');
}

// Icon selection for edit form
function initEditIconSelection() {
    // Add click listeners to all edit icon options
    document.querySelectorAll('.edit-icon-option').forEach(option => {
        option.addEventListener('click', function() {
            selectEditIcon(this);
        });
    });
    
    // Add hover effects
    document.querySelectorAll('.edit-icon-option').forEach(option => {
        option.addEventListener('mouseenter', function() {
            if (!this.classList.contains('selected')) {
                this.style.borderColor = '#93c5fd';
                this.style.transform = 'scale(1.05)';
            }
        });
        option.addEventListener('mouseleave', function() {
            if (!this.classList.contains('selected')) {
                this.style.borderColor = '#e5e7eb';
                this.style.transform = 'scale(1)';
            }
        });
    });
}

function selectEditIcon(element) {
    // Remove selected class from all icons
    document.querySelectorAll('.edit-icon-option').forEach(opt => {
        opt.classList.remove('selected');
        opt.style.borderColor = '#e5e7eb';
        opt.style.background = 'white';
        opt.style.transform = 'scale(1)';
    });
    
    // Add selected class to clicked icon
    element.classList.add('selected');
    element.style.borderColor = 'var(--primary-color)';
    element.style.background = '#e0f2fe';
    element.style.transform = 'scale(1.05)';
    
    // Update hidden input and preview
    const icon = element.dataset.icon;
    document.getElementById('edit-selected-icon').value = icon;
    const preview = document.getElementById('edit-icon-preview');
    preview.className = 'fas ' + icon;
    document.getElementById('edit-icon-name').textContent = icon.replace('fa-', '');
}

function editCategory(category) {
    document.getElementById('edit-id').value = category.id;
    document.getElementById('edit-name').value = category.name;
    document.getElementById('edit-selected-icon').value = category.icon;
    document.getElementById('edit-gradient-start').value = category.gradient_start;
    document.getElementById('edit-gradient-end').value = category.gradient_end;
    document.getElementById('edit-display-order').value = category.display_order;
    
    // Select the icon visually
    const iconOption = document.querySelector(`.edit-icon-option[data-icon="${category.icon}"]`);
    if (iconOption) {
        selectEditIcon(iconOption);
    } else {
        // Update preview even if icon not found
        const preview = document.getElementById('edit-icon-preview');
        preview.className = 'fas ' + category.icon;
        document.getElementById('edit-icon-name').textContent = category.icon.replace('fa-', '');
    }
    
    document.getElementById('editModal').style.display = 'flex';
    
    // Initialize icon selection after modal is shown
    setTimeout(initEditIconSelection, 100);
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

