<?php
$pageTitle = 'إضافة منتج جديد';
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/auth/login.php');
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Get categories from database
try {
    $stmt = $db->prepare("SELECT * FROM category_list ORDER BY display_order ASC, name ASC");
    $stmt->execute();
    $availableCategories = $stmt->fetchAll();
} catch (PDOException $e) {
    // If table doesn't exist, use empty array
    $availableCategories = [];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    // Handle sizes and colors (they come as JSON strings from hidden inputs)
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

    $uploadDir = __DIR__ . '/../assets/images/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Handle main image upload
    $mainImage = '';
    $mainImageFile = null;
    
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $fileName = uniqid() . '_' . basename($_FILES['main_image']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['main_image']['tmp_name'], $targetFile)) {
            $mainImage = SITE_URL . '/assets/images/products/' . $fileName;
            $mainImageFile = $fileName; // حفظ اسم الملف للاستخدام لاحقاً
        }
    }
    
    // Handle other images
    $otherImages = [];
    if (isset($_FILES['other_images'])) {
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
    
    // إذا تم اختيار صورة من الصور الإضافية كصورة رئيسية
    // (يتم التعامل معها في JavaScript - الصورة تنتقل إلى main_image input)
    // لذلك لا حاجة لمعالجة إضافية هنا

    if (empty($name) || empty($description) || $price <= 0 || empty($mainImage)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة واختيار صورة رئيسية';
    } else {
        $stmt = $db->prepare("INSERT INTO products (name, description, price, mainImage, quantity, otherImages, sizes, colors, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([
            $name,
            $description,
            $price,
            $mainImage,
            $quantity,
            json_encode($otherImages),
            json_encode($sizes),
            json_encode($colors),
            $_SESSION['user_id']
        ])) {
            $productId = $db->lastInsertId();

            // Add category if selected
            if (!empty($category)) {
                $stmt = $db->prepare("INSERT INTO categories (name, product_id) VALUES (?, ?)");
                $stmt->execute([$category, $productId]);
            }

            $success = 'تم إضافة المنتج بنجاح';
            // Clear form
            $name = $description = $category = '';
            $price = $quantity = 0;
            $sizes = $colors = [];
        } else {
            $error = 'حدث خطأ في إضافة المنتج';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px; margin: 40px auto;">
    <h1 style="text-align: center; margin-bottom: 30px; color: var(--primary-color);">
        إضافة منتج جديد
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
                   value="<?php echo htmlspecialchars($name ?? ''); ?>"
                   style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="description" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">وصف المنتج *</label>
            <textarea id="description" name="description" required rows="4"
                      style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px; resize: vertical;"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="price" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">السعر (د.ع) *</label>
                <input type="number" id="price" name="price" required min="0" step="0.01"
                       value="<?php echo htmlspecialchars($price ?? 0); ?>"
                       style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
            </div>

            <div class="form-group">
                <label for="quantity" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">الكمية المتاحة *</label>
                <input type="number" id="quantity" name="quantity" required min="0"
                       value="<?php echo htmlspecialchars($quantity ?? 0); ?>"
                       style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
            </div>
        </div>

        <!-- حقل الفئة -->
        <div class="form-group" id="category-field-container" style="margin-bottom: 25px;">
            <label for="category" style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-color); font-size: 16px;">
                <i class="fas fa-tags" style="margin-left: 8px; color: #3b82f6;"></i>
                فئة المنتج
                <span style="color: #6b7280; font-weight: 400; font-size: 14px;">(اختياري)</span>
            </label>
            <div style="position: relative;">
                <!-- Hidden input for form submission -->
                <input type="hidden" name="category" id="category-input" value="<?php echo htmlspecialchars($category ?? ''); ?>">
                
                <!-- Custom dropdown -->
                <div id="category-dropdown" style="position: relative; width: 100%;">
                    <div id="category-select" 
                         onclick="toggleCategoryDropdown()"
                         style="width: 100%; padding: 14px 45px 14px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; background: white; color: #1f2937; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 10px;">
                        <span id="category-display" style="flex: 1; display: flex; align-items: center; gap: 8px;">
                            <?php if (!empty($category) && !empty($availableCategories)): ?>
                                <?php 
                                $selectedCat = array_filter($availableCategories, function($cat) use ($category) {
                                    return $cat['name'] === $category;
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
                        <i class="fas fa-chevron-down" style="font-size: 12px; color: #6b7280; transition: transform 0.3s ease;" id="category-arrow"></i>
                    </div>
                    
                    <!-- Dropdown options -->
                    <div id="category-options" 
                         style="display: none; position: absolute; top: 100%; right: 0; left: 0; background: white; border: 2px solid #e5e7eb; border-radius: 8px; margin-top: 5px; max-height: 300px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div class="category-option" 
                             onclick="selectCategory('', 'اختر فئة المنتج...', '')"
                             style="padding: 12px 16px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #f3f4f6; transition: background 0.2s ease;">
                            <span style="color: #9ca3af;">اختر فئة المنتج...</span>
                        </div>
                        <?php if (!empty($availableCategories)): ?>
                            <?php foreach ($availableCategories as $cat): ?>
                                <div class="category-option" 
                                     onclick="selectCategory('<?php echo htmlspecialchars($cat['name']); ?>', '<?php echo htmlspecialchars($cat['name']); ?>', '<?php echo htmlspecialchars($cat['icon']); ?>')"
                                     style="padding: 12px 16px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #f3f4f6; transition: background 0.2s ease; <?php echo ($category ?? '') === $cat['name'] ? 'background: #e0f2fe;' : ''; ?>">
                                    <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>" style="color: var(--primary-color); font-size: 18px; width: 24px; text-align: center;"></i>
                                    <span style="flex: 1;"><?php echo htmlspecialchars($cat['name']); ?></span>
                                    <?php if (($category ?? '') === $cat['name']): ?>
                                        <i class="fas fa-check" style="color: var(--primary-color);"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback categories -->
                            <div class="category-option" onclick="selectCategory('ملابس-رجالية', 'ملابس-رجالية', 'fa-tshirt')" style="padding: 12px 16px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #f3f4f6;">
                                <i class="fas fa-tshirt" style="color: var(--primary-color);"></i>
                                <span>ملابس-رجالية</span>
                            </div>
                            <div class="category-option" onclick="selectCategory('ملابس-نسائية', 'ملابس-نسائية', 'fa-female')" style="padding: 12px 16px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #f3f4f6;">
                                <i class="fas fa-female" style="color: var(--primary-color);"></i>
                                <span>ملابس-نسائية</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <small style="color: #6b7280; display: block; margin-top: 6px; font-size: 13px;">
                <i class="fas fa-info-circle" style="margin-left: 4px;"></i>
                اختيار الفئة يساعد العملاء في العثور على منتجك بسهولة
            </small>
        </div>

        <!-- الصورة الرئيسية -->
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="main_image" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">
                الصورة الرئيسية *
                <span style="color: #6b7280; font-weight: 400; font-size: 14px;">(يمكنك تغييرها من الصور الإضافية)</span>
            </label>
            <div id="main-image-preview-container" style="margin-bottom: 15px; display: none;">
                <div style="position: relative; display: inline-block;">
                    <img id="main-image-preview" src="" alt="معاينة الصورة الرئيسية" 
                         style="max-width: 300px; max-height: 300px; border-radius: 8px; border: 2px solid #e5e7eb; object-fit: cover;">
                    <span style="position: absolute; top: 10px; right: 10px; background: var(--primary-color); color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-star"></i> رئيسية
                    </span>
                </div>
            </div>
            <input type="file" id="main_image" name="main_image" accept="image/*" required
                   style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
            <input type="hidden" id="selected_main_image" name="selected_main_image" value="">
        </div>

        <!-- الصور الإضافية -->
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="other_images" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">صور إضافية</label>
            <input type="file" id="other_images" name="other_images[]" accept="image/*" multiple
                   style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 16px;">
            <small style="color: #6b7280; display: block; margin-top: 4px;">يمكنك اختيار عدة صور</small>
            
            <!-- معاينة الصور الإضافية -->
            <div id="other-images-preview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 20px;"></div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">الأحجام</label>
                <div id="sizes-container">
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="text" placeholder="أدخل الحجم" style="flex: 1; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        <button type="button" onclick="addSize()" style="background: var(--primary-color); color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">إضافة</button>
                    </div>
                    <div id="sizes-list"></div>
                </div>
            </div>

            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">الألوان</label>
                <div id="colors-container">
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="text" placeholder="أدخل اللون" style="flex: 1; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                        <button type="button" onclick="addColor()" style="background: var(--primary-color); color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">إضافة</button>
                    </div>
                    <div id="colors-list"></div>
                </div>
            </div>
        </div>

        <!-- Hidden inputs for sizes and colors -->
        <input type="hidden" name="sizes" id="sizes-input">
        <input type="hidden" name="colors" id="colors-input">

        <div style="text-align: center;">
            <button type="submit" style="background: var(--primary-color); color: white; border: none; padding: 15px 30px; border-radius: 6px; font-size: 16px; cursor: pointer; margin-right: 10px;">
                إضافة المنتج
            </button>
            <a href="<?php echo SITE_URL; ?>/admin/admin.php" style="background: #6b7280; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-size: 16px;">
                العودة للوحة التحكم
            </a>
        </div>
    </form>
</div>

<script>
let sizes = [];
let colors = [];

function addSize() {
    const input = document.querySelector('#sizes-container input');
    const value = input.value.trim();
    if (value && !sizes.includes(value)) {
        sizes.push(value);
        updateSizesDisplay();
        input.value = '';
    }
}

function addColor() {
    const input = document.querySelector('#colors-container input');
    const value = input.value.trim();
    if (value && !colors.includes(value)) {
        colors.push(value);
        updateColorsDisplay();
        input.value = '';
    }
}

function removeSize(index) {
    sizes.splice(index, 1);
    updateSizesDisplay();
}

function removeColor(index) {
    colors.splice(index, 1);
    updateColorsDisplay();
}

function updateSizesDisplay() {
    const list = document.getElementById('sizes-list');
    const input = document.getElementById('sizes-input');

    list.innerHTML = sizes.map((size, index) =>
        `<span style="display: inline-block; background: #e5e7eb; padding: 4px 8px; border-radius: 4px; margin: 2px; font-size: 14px;">
            ${size}
            <button type="button" onclick="removeSize(${index})" style="background: none; border: none; color: #ef4444; cursor: pointer; margin-left: 5px;">×</button>
        </span>`
    ).join('');

    input.value = JSON.stringify(sizes);
}

function updateColorsDisplay() {
    const list = document.getElementById('colors-list');
    const input = document.getElementById('colors-input');

    list.innerHTML = colors.map((color, index) =>
        `<span style="display: inline-block; background: #e5e7eb; padding: 4px 8px; border-radius: 4px; margin: 2px; font-size: 14px;">
            ${color}
            <button type="button" onclick="removeColor(${index})" style="background: none; border: none; color: #ef4444; cursor: pointer; margin-left: 5px;">×</button>
        </span>`
    ).join('');

    input.value = JSON.stringify(colors);
}

// Initialize displays
updateSizesDisplay();
updateColorsDisplay();

// Image preview functionality
let uploadedImages = []; // الصور الإضافية فقط
let mainImageData = null; // الصورة الرئيسية (منفصلة)

// Handle main image preview
document.getElementById('main_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            mainImageData = {
                src: e.target.result,
                name: file.name,
                file: file
            };
            updateMainImagePreview();
        };
        reader.readAsDataURL(file);
    }
});

// Handle other images preview
document.getElementById('other_images').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    
    files.forEach((file) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const imageData = {
                src: e.target.result,
                name: file.name,
                file: file
            };
            
            uploadedImages.push(imageData);
            updateImagesPreview();
        };
        reader.readAsDataURL(file);
    });
});

function updateMainImagePreview() {
    const preview = document.getElementById('main-image-preview');
    const container = document.getElementById('main-image-preview-container');
    const selectedMainImageInput = document.getElementById('selected_main_image');
    
    if (mainImageData) {
        preview.src = mainImageData.src;
        container.style.display = 'block';
        selectedMainImageInput.value = ''; // Reset - main image is from main_image input
    } else {
        container.style.display = 'none';
    }
}

function updateImagesPreview() {
    const previewContainer = document.getElementById('other-images-preview');
    
    if (uploadedImages.length === 0) {
        previewContainer.innerHTML = '';
        return;
    }
    
    previewContainer.innerHTML = uploadedImages.map((img, index) => {
        return `
            <div style="position: relative; border: 2px solid #e5e7eb; border-radius: 8px; overflow: hidden; background: white;">
                <img src="${img.src}" alt="معاينة ${index + 1}" 
                     style="width: 100%; height: 150px; object-fit: cover; display: block;">
                <div style="padding: 8px; background: white;">
                    <button type="button" onclick="setAsMainImage(${index})" 
                            style="width: 100%; background: var(--primary-color); color: white; border: none; padding: 6px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500;">
                        <i class="fas fa-star"></i> جعلها رئيسية
                    </button>
                    <button type="button" onclick="removeImage(${index})" 
                            style="width: 100%; background: #ef4444; color: white; border: none; padding: 6px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; margin-top: 5px;">
                        <i class="fas fa-trash"></i> حذف
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function setAsMainImage(index) {
    // حفظ الصورة الرئيسية الحالية (إن وجدت) لنقلها إلى الصور الإضافية
    const oldMainImage = mainImageData;
    
    // نقل الصورة المختارة من الصور الإضافية إلى الصورة الرئيسية
    const selectedImage = uploadedImages[index];
    mainImageData = selectedImage;
    
    // إزالة الصورة المختارة من الصور الإضافية
    uploadedImages.splice(index, 1);
    
    // إذا كانت هناك صورة رئيسية قديمة، أضفها إلى الصور الإضافية
    if (oldMainImage) {
        uploadedImages.push(oldMainImage);
    }
    
    // تحديث واجهة المستخدم
    updateMainImagePreview();
    updateImagesPreview();
    
    // تحديث حقل الصورة الرئيسية
    const mainImageInput = document.getElementById('main_image');
    const selectedMainImageInput = document.getElementById('selected_main_image');
    
    // إنشاء FileList جديد بالصورة المختارة
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(selectedImage.file);
    mainImageInput.files = dataTransfer.files;
    
    // تحديث ملفات الصور الإضافية
    const otherImagesInput = document.getElementById('other_images');
    const otherDataTransfer = new DataTransfer();
    uploadedImages.forEach(img => {
        otherDataTransfer.items.add(img.file);
    });
    otherImagesInput.files = otherDataTransfer.files;
    
    // تعيين الفهرس المحدد
    selectedMainImageInput.value = index;
}

function removeImage(index) {
    if (confirm('هل أنت متأكد من حذف هذه الصورة؟')) {
        uploadedImages.splice(index, 1);
        updateImagesPreview();
        
        // تحديث ملفات الصور الإضافية
        const otherImagesInput = document.getElementById('other_images');
        const dataTransfer = new DataTransfer();
        uploadedImages.forEach(img => {
            dataTransfer.items.add(img.file);
        });
        otherImagesInput.files = dataTransfer.files;
    }
}

// Disable cache for this page
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
        for(let registration of registrations) {
            registration.unregister();
        }
    });
}

// Category dropdown functionality
let categoryDropdownOpen = false;

function toggleCategoryDropdown() {
    const options = document.getElementById('category-options');
    const arrow = document.getElementById('category-arrow');
    const select = document.getElementById('category-select');
    
    categoryDropdownOpen = !categoryDropdownOpen;
    
    if (categoryDropdownOpen) {
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

function selectCategory(value, name, icon) {
    const input = document.getElementById('category-input');
    const display = document.getElementById('category-display');
    const options = document.querySelectorAll('.category-option');
    
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
    toggleCategoryDropdown();
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('category-dropdown');
    if (dropdown && !dropdown.contains(e.target) && categoryDropdownOpen) {
        toggleCategoryDropdown();
    }
});

// Add hover effects to options
document.addEventListener('DOMContentLoaded', function() {
    const options = document.querySelectorAll('.category-option');
    options.forEach(option => {
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
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
