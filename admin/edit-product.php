<?php
$pageTitle = 'تعديل المنتج';
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/auth/login.php');
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();

$product_id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

if (!$product_id) {
    redirect(SITE_URL . '/admin/admin.php');
}

// Get product data
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect(SITE_URL . '/admin/admin.php');
}

// Parse JSON data
$sizes = json_decode($product['sizes'] ?? '[]', true) ?: [];
$colors = json_decode($product['colors'] ?? '[]', true) ?: [];
$otherImages = json_decode($product['otherImages'] ?? '[]', true) ?: [];

// Get current category
$stmt = $db->prepare("SELECT name FROM categories WHERE product_id = ? LIMIT 1");
$stmt->execute([$product_id]);
$currentCategory = $stmt->fetchColumn() ?: '';

// Get categories from database
try {
    $stmt = $db->prepare("SELECT * FROM category_list ORDER BY display_order ASC, name ASC");
    $stmt->execute();
    $availableCategories = $stmt->fetchAll();
} catch (PDOException $e) {
    // If table doesn't exist, use empty array
    $availableCategories = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    // Handle sizes and colors (they come as JSON strings from hidden inputs or as arrays)
    $sizesJson = $_POST['sizes'] ?? '[]';
    $colorsJson = $_POST['colors'] ?? '[]';
    
    // If it's a string (JSON), decode it; if it's already an array, use it
    if (is_string($sizesJson)) {
        $sizes = json_decode($sizesJson, true) ?? [];
    } else {
        $sizes = is_array($sizesJson) ? $sizesJson : [];
    }
    
    if (is_string($colorsJson)) {
        $colors = json_decode($colorsJson, true) ?? [];
    } else {
        $colors = is_array($colorsJson) ? $colorsJson : [];
    }
    
    // Ensure they are arrays
    if (!is_array($sizes)) $sizes = [];
    if (!is_array($colors)) $colors = [];
    $category = sanitize($_POST['category'] ?? '');

    $mainImage = $product['mainImage'];

    // Handle main image upload
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/images/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['main_image']['name']);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['main_image']['tmp_name'], $targetFile)) {
            $mainImage = SITE_URL . '/assets/images/products/' . $fileName;
        }
    }

    // Handle other images (append to existing)
    if (isset($_FILES['other_images'])) {
        $uploadDir = __DIR__ . '/../assets/images/products/';
        for ($i = 0; $i < count($_FILES['other_images']['name']); $i++) {
            if ($_FILES['other_images']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = uniqid() . '_' . basename($_FILES['other_images']['name'][$i]);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['other_images']['tmp_name'][$i], $targetFile)) {
                    $otherImages[] = SITE_URL . '/assets/images/products/' . $fileName;
                }
            }
        }
    }

    if (empty($name) || empty($description) || $price <= 0) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        $stmt = $db->prepare("UPDATE products SET name = ?, description = ?, price = ?, mainImage = ?, quantity = ?, otherImages = ?, sizes = ?, colors = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if ($stmt->execute([
            $name,
            $description,
            $price,
            $mainImage,
            $quantity,
            json_encode($otherImages),
            json_encode($sizes),
            json_encode($colors),
            $product_id
        ])) {
            // Update category
            if (!empty($category)) {
                // Delete old categories for this product
                $stmt = $db->prepare("DELETE FROM categories WHERE product_id = ?");
                $stmt->execute([$product_id]);
                
                // Add new category
                $stmt = $db->prepare("INSERT INTO categories (name, product_id) VALUES (?, ?)");
                $stmt->execute([$category, $product_id]);
            }
            
            $success = 'تم تحديث المنتج بنجاح';
            // Refresh product data
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            $sizes = json_decode($product['sizes'] ?? '[]', true) ?: [];
            $colors = json_decode($product['colors'] ?? '[]', true) ?: [];
            $otherImages = json_decode($product['otherImages'] ?? '[]', true) ?: [];
            
            // Refresh current category
            $stmt = $db->prepare("SELECT name FROM categories WHERE product_id = ? LIMIT 1");
            $stmt->execute([$product_id]);
            $currentCategory = $stmt->fetchColumn() ?: '';
        } else {
            $error = 'حدث خطأ في تحديث المنتج';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px; margin: 40px auto;">
    <h1 style="text-align: center; margin-bottom: 30px; color: var(--primary-color);">
        تعديل المنتج: <?php echo htmlspecialchars($product['name']); ?>
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

    <form method="POST" enctype="multipart/form-data" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">اسم المنتج *</label>
            <input type="text" id="name" name="name" required
                   value="<?php echo htmlspecialchars($product['name']); ?>"
                   style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="description" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">وصف المنتج *</label>
            <textarea id="description" name="description" required rows="4"
                      style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px; resize: vertical;"><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="price" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">السعر (د.ع) *</label>
                <input type="number" id="price" name="price" required min="0" step="0.01"
                       value="<?php echo htmlspecialchars($product['price']); ?>"
                       style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
            </div>

            <div class="form-group">
                <label for="quantity" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">الكمية المتاحة *</label>
                <input type="number" id="quantity" name="quantity" required min="0"
                       value="<?php echo htmlspecialchars($product['quantity']); ?>"
                       style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
            </div>
        </div>

        <!-- حقل الفئة -->
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="category" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">
                <i class="fas fa-tags" style="margin-left: 8px; color: #3b82f6;"></i>
                فئة المنتج
                <span style="color: #6b7280; font-weight: 400; font-size: 14px;">(اختياري)</span>
            </label>
            <div style="position: relative;">
                <!-- Hidden input for form submission -->
                <input type="hidden" name="category" id="edit-category-input" value="<?php echo htmlspecialchars($currentCategory); ?>">
                
                <!-- Custom dropdown -->
                <div id="edit-category-dropdown" style="position: relative; width: 100%;">
                    <div id="edit-category-select" 
                         onclick="toggleEditCategoryDropdown()"
                         style="width: 100%; padding: 12px 45px 12px 16px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px; background: white; color: #1f2937; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 10px;">
                        <span id="edit-category-display" style="flex: 1; display: flex; align-items: center; gap: 8px;">
                            <?php if (!empty($currentCategory) && !empty($availableCategories)): ?>
                                <?php 
                                $selectedCat = array_filter($availableCategories, function($cat) use ($currentCategory) {
                                    return $cat['name'] === $currentCategory;
                                });
                                if (!empty($selectedCat)) {
                                    $selectedCat = reset($selectedCat);
                                    echo '<i class="fas ' . htmlspecialchars($selectedCat['icon']) . '" style="color: var(--primary-color);"></i>';
                                    echo '<span>' . htmlspecialchars($selectedCat['name']) . '</span>';
                                } else {
                                    echo '<span style="color: #9ca3af;">اختر فئة المنتج...</span>';
                                }
                                ?>
                            <?php else: ?>
                                <span style="color: #9ca3af;">اختر فئة المنتج...</span>
                            <?php endif; ?>
                        </span>
                        <i class="fas fa-chevron-down" style="font-size: 12px; color: #6b7280; transition: transform 0.3s ease;" id="edit-category-arrow"></i>
                    </div>
                    
                    <!-- Dropdown options -->
                    <div id="edit-category-options" 
                         style="display: none; position: absolute; top: 100%; right: 0; left: 0; background: white; border: 2px solid #e5e7eb; border-radius: 6px; margin-top: 5px; max-height: 300px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div class="edit-category-option" 
                             onclick="selectEditCategory('', 'اختر فئة المنتج...', '')"
                             style="padding: 12px 16px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #f3f4f6; transition: background 0.2s ease;">
                            <span style="color: #9ca3af;">اختر فئة المنتج...</span>
                        </div>
                        <?php if (!empty($availableCategories)): ?>
                            <?php foreach ($availableCategories as $cat): ?>
                                <div class="edit-category-option" 
                                     onclick="selectEditCategory('<?php echo htmlspecialchars($cat['name']); ?>', '<?php echo htmlspecialchars($cat['name']); ?>', '<?php echo htmlspecialchars($cat['icon']); ?>')"
                                     style="padding: 12px 16px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #f3f4f6; transition: background 0.2s ease; <?php echo $currentCategory === $cat['name'] ? 'background: #e0f2fe;' : ''; ?>">
                                    <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>" style="color: var(--primary-color); font-size: 18px; width: 24px; text-align: center;"></i>
                                    <span style="flex: 1;"><?php echo htmlspecialchars($cat['name']); ?></span>
                                    <?php if ($currentCategory === $cat['name']): ?>
                                        <i class="fas fa-check" style="color: var(--primary-color);"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <small style="color: #6b7280; display: block; margin-top: 6px; font-size: 13px;">
                <i class="fas fa-info-circle" style="margin-left: 4px;"></i>
                يمكنك تغيير فئة المنتج إذا كانت الفئة الحالية غير صحيحة
            </small>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">الصورة الرئيسية الحالية</label>
            <div style="margin-bottom: 10px;">
                <img src="<?php echo htmlspecialchars($product['mainImage']); ?>"
                     alt="Current main image"
                     style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;">
            </div>
            <label for="main_image" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">تغيير الصورة الرئيسية (اختياري)</label>
            <input type="file" id="main_image" name="main_image" accept="image/*"
                   style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">الصور الإضافية الحالية</label>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                <?php foreach ($otherImages as $image): ?>
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="Additional image"
                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;">
                <?php endforeach; ?>
            </div>
            <label for="other_images" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">إضافة صور إضافية (اختياري)</label>
            <input type="file" id="other_images" name="other_images[]" accept="image/*" multiple
                   style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
        </div>

        <!-- المقاسات -->
        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 12px; font-weight: 600; color: var(--text-color); font-size: 16px;">
                المقاسات <span style="color: #6b7280; font-weight: 400;">(اختياري)</span>:
            </label>
            <div id="sizes-buttons" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <?php if (!empty($sizes) && is_array($sizes) && count($sizes) > 0): ?>
                    <?php foreach ($sizes as $size): ?>
                        <?php 
                        $sizeValue = is_string($size) ? trim($size) : '';
                        if (!empty($sizeValue)): 
                        ?>
                            <div style="display: inline-flex; align-items: center; gap: 5px; background: white; border: 2px solid #d1d5db; border-radius: 6px; padding: 8px 15px; margin: 5px;">
                                <span style="font-weight: 500; color: var(--text-color);"><?php echo htmlspecialchars($sizeValue); ?></span>
                                <button type="button" 
                                        onclick="removeSize('<?php echo htmlspecialchars(addslashes($sizeValue)); ?>')"
                                        style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; margin: 0; font-size: 16px; line-height: 1; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; margin: 0;">لا توجد مقاسات محددة</p>
                <?php endif; ?>
            </div>
            
            <!-- إضافة مقاسات جديدة -->
            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                <input type="text" id="new-size-input" placeholder="أدخل مقاس جديد (مثل: 38, 39, 40...)" style="flex: 1; padding: 10px; border: 2px solid #d1d5db; border-radius: 6px; font-size: 15px;">
                <button type="button" onclick="addNewSize()" style="background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-plus"></i> إضافة
                </button>
            </div>
            <input type="hidden" name="sizes" id="sizes-input" value="<?php echo htmlspecialchars(json_encode($sizes)); ?>">
        </div>

        <!-- الألوان -->
        <div class="form-group" style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 12px; font-weight: 600; color: var(--text-color); font-size: 16px;">
                الألوان <span style="color: #6b7280; font-weight: 400;">(اختياري)</span>:
            </label>
            <div id="colors-buttons" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <?php if (!empty($colors) && is_array($colors) && count($colors) > 0): ?>
                    <?php foreach ($colors as $color): ?>
                        <?php 
                        $colorValue = is_string($color) ? trim($color) : '';
                        if (!empty($colorValue)): 
                        ?>
                            <div style="display: inline-flex; align-items: center; gap: 5px; background: white; border: 2px solid #d1d5db; border-radius: 6px; padding: 8px 15px; margin: 5px;">
                                <span style="font-weight: 500; color: var(--text-color);"><?php echo htmlspecialchars($colorValue); ?></span>
                                <button type="button" 
                                        onclick="removeColor('<?php echo htmlspecialchars(addslashes($colorValue)); ?>')"
                                        style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; margin: 0; font-size: 16px; line-height: 1; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px; margin: 0;">لا توجد ألوان محددة</p>
                <?php endif; ?>
            </div>
            
            <!-- إضافة ألوان جديدة -->
            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                <input type="text" id="new-color-input" placeholder="أدخل لون جديد (مثل: أسود، أحمر، أزرق...)" style="flex: 1; padding: 10px; border: 2px solid #d1d5db; border-radius: 6px; font-size: 15px;">
                <button type="button" onclick="addNewColor()" style="background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-plus"></i> إضافة
                </button>
            </div>
            <input type="hidden" name="colors" id="colors-input" value="<?php echo htmlspecialchars(json_encode($colors)); ?>">
        </div>

        <div style="text-align: center;">
            <button type="submit" style="background: var(--primary-color); color: white; border: none; padding: 15px 30px; border-radius: 6px; font-size: 16px; cursor: pointer; margin-right: 10px;">
                حفظ التغييرات
            </button>
            <a href="<?php echo SITE_URL; ?>/admin/admin.php" style="background: #6b7280; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-size: 16px;">
                العودة للوحة التحكم
            </a>
        </div>
    </form>
</div>

<script>
let sizes = <?php echo json_encode($sizes); ?>;
let colors = <?php echo json_encode($colors); ?>;

// Add new size
function addNewSize() {
    const input = document.getElementById('new-size-input');
    const value = input.value.trim();
    
    if (!value) {
        alert('يرجى إدخال مقاس');
        return;
    }
    
    if (sizes.includes(value)) {
        alert('هذا المقاس موجود بالفعل');
        input.value = '';
        return;
    }
    
    sizes.push(value);
    updateSizesDisplay();
    input.value = '';
}

// Add new color
function addNewColor() {
    const input = document.getElementById('new-color-input');
    const value = input.value.trim();
    
    if (!value) {
        alert('يرجى إدخال لون');
        return;
    }
    
    if (colors.includes(value)) {
        alert('هذا اللون موجود بالفعل');
        input.value = '';
        return;
    }
    
    colors.push(value);
    updateColorsDisplay();
    input.value = '';
}

// Remove size
function removeSize(sizeToRemove) {
    if (confirm(`هل أنت متأكد من حذف المقاس "${sizeToRemove}"؟`)) {
        sizes = sizes.filter(size => size !== sizeToRemove);
        updateSizesDisplay();
    }
}

// Remove color
function removeColor(colorToRemove) {
    if (confirm(`هل أنت متأكد من حذف اللون "${colorToRemove}"؟`)) {
        colors = colors.filter(color => color !== colorToRemove);
        updateColorsDisplay();
    }
}

// Update sizes display
function updateSizesDisplay() {
    const container = document.getElementById('sizes-buttons');
    const input = document.getElementById('sizes-input');
    
    if (!container || !input) return;
    
    if (sizes.length === 0) {
        container.innerHTML = '<p style="color: #6b7280; font-size: 14px; margin: 0;">لا توجد مقاسات محددة</p>';
    } else {
        container.innerHTML = sizes.map(size => {
            const escapedSize = size.replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\\/g, '\\\\');
            return `
                <div style="display: inline-flex; align-items: center; gap: 5px; background: white; border: 2px solid #d1d5db; border-radius: 6px; padding: 8px 15px; margin: 5px;">
                    <span style="font-weight: 500; color: var(--text-color);">${size}</span>
                    <button type="button" 
                            onclick="removeSize('${escapedSize}')"
                            style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; margin: 0; font-size: 16px; line-height: 1; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }).join('');
    }
    
    input.value = JSON.stringify(sizes);
}

// Update colors display
function updateColorsDisplay() {
    const container = document.getElementById('colors-buttons');
    const input = document.getElementById('colors-input');
    
    if (!container || !input) return;
    
    if (colors.length === 0) {
        container.innerHTML = '<p style="color: #6b7280; font-size: 14px; margin: 0;">لا توجد ألوان محددة</p>';
    } else {
        container.innerHTML = colors.map(color => {
            const escapedColor = color.replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\\/g, '\\\\');
            return `
                <div style="display: inline-flex; align-items: center; gap: 5px; background: white; border: 2px solid #d1d5db; border-radius: 6px; padding: 8px 15px; margin: 5px;">
                    <span style="font-weight: 500; color: var(--text-color);">${color}</span>
                    <button type="button" 
                            onclick="removeColor('${escapedColor}')"
                            style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; margin: 0; font-size: 16px; line-height: 1; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }).join('');
    }
    
    input.value = JSON.stringify(colors);
}

// Edit category dropdown functionality
let editCategoryDropdownOpen = false;

function toggleEditCategoryDropdown() {
    const options = document.getElementById('edit-category-options');
    const arrow = document.getElementById('edit-category-arrow');
    const select = document.getElementById('edit-category-select');
    
    editCategoryDropdownOpen = !editCategoryDropdownOpen;
    
    if (editCategoryDropdownOpen) {
        options.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
        select.style.borderColor = '#3b82f6';
        select.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
    } else {
        options.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
        select.style.borderColor = '#e5e7eb';
        select.style.boxShadow = 'none';
    }
}

function selectEditCategory(value, name, icon) {
    const input = document.getElementById('edit-category-input');
    const display = document.getElementById('edit-category-display');
    const options = document.querySelectorAll('.edit-category-option');
    
    // Update hidden input
    input.value = value;
    
    // Update display
    if (value) {
        display.innerHTML = `<i class="fas ${icon}" style="color: var(--primary-color);"></i><span>${name}</span>`;
    } else {
        display.innerHTML = '<span style="color: #9ca3af;">اختر فئة المنتج...</span>';
    }
    
    // Update selected state
    options.forEach(opt => {
        opt.style.background = '';
        const checkIcon = opt.querySelector('.fa-check');
        if (checkIcon) checkIcon.remove();
    });
    
    // Highlight selected option
    const selectedOption = Array.from(options).find(opt => {
        const optIcon = opt.querySelector('i.fas');
        return optIcon && optIcon.classList.contains(icon);
    });
    if (selectedOption) {
        selectedOption.style.background = '#e0f2fe';
        const checkIcon = document.createElement('i');
        checkIcon.className = 'fas fa-check';
        checkIcon.style.cssText = 'color: var(--primary-color); margin-right: auto;';
        selectedOption.appendChild(checkIcon);
    }
    
    // Close dropdown
    toggleEditCategoryDropdown();
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('edit-category-dropdown');
    if (dropdown && !dropdown.contains(e.target) && editCategoryDropdownOpen) {
        toggleEditCategoryDropdown();
    }
});

// Allow Enter key to add
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to edit category options
    const editOptions = document.querySelectorAll('.edit-category-option');
    editOptions.forEach(option => {
        option.addEventListener('mouseenter', function() {
            if (this.style.background !== 'rgb(224, 242, 254)') {
                this.style.background = '#f9fafb';
            }
        });
        option.addEventListener('mouseleave', function() {
            if (this.style.background !== 'rgb(224, 242, 254)') {
                this.style.background = '';
            }
        });
    });
    const newSizeInput = document.getElementById('new-size-input');
    const newColorInput = document.getElementById('new-color-input');
    
    if (newSizeInput) {
        newSizeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addNewSize();
            }
        });
    }
    
    if (newColorInput) {
        newColorInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addNewColor();
            }
        });
    }
    
    // Initialize displays
    updateSizesDisplay();
    updateColorsDisplay();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
