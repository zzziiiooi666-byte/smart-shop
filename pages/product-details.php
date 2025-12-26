<?php
$pageTitle = 'تفاصيل المنتج';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$productId = intval($_GET['id'] ?? 0);

if ($productId <= 0) {
    header('Location: ' . SITE_URL . '/pages/products.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . SITE_URL . '/pages/products.php');
    exit;
}

// Parse JSON fields
$otherImages = !empty($product['otherImages']) ? json_decode($product['otherImages'], true) : [];
$sizes = !empty($product['sizes']) ? json_decode($product['sizes'], true) : [];
$colors = !empty($product['colors']) ? json_decode($product['colors'], true) : [];

// Ensure arrays are valid
if (!is_array($sizes)) $sizes = [];
if (!is_array($colors)) $colors = [];
if (!is_array($otherImages)) $otherImages = [];

// Filter out empty values
$sizes = array_filter($sizes, function($size) {
    return !empty(trim($size));
});
$colors = array_filter($colors, function($color) {
    return !empty(trim($color));
});

// Get categories
$stmt = $db->prepare("SELECT name FROM categories WHERE product_id = ?");
$stmt->execute([$productId]);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">
        <!-- Product Images -->
        <div>
            <div style="position: relative; margin-bottom: 20px; background: #f9fafb; border-radius: 12px;">
                <img id="mainProductImage" 
                     src="<?php echo getProductImage($product['mainImage'], $product['name']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     style="width: 100%; height: 500px; object-fit: cover; border-radius: 12px; box-shadow: var(--shadow-lg);"
                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.svg'"
                     loading="eager">
            </div>
            <?php if (!empty($otherImages)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px;">
                    <?php foreach ($otherImages as $index => $image): ?>
                        <?php if (!empty($image)): ?>
                            <img src="<?php echo getProductImage($image, $product['name']); ?>" 
                                 alt="صورة <?php echo $index + 1; ?>"
                                 onclick="document.getElementById('mainProductImage').src = this.src"
                                 style="width: 100%; height: 100px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid transparent; transition: border-color 0.3s; background: #f9fafb;"
                                 onmouseover="this.style.borderColor='var(--primary-color)'"
                                 onmouseout="this.style.borderColor='transparent'"
                                 onerror="this.onerror=null; this.style.display='none'"
                                 loading="lazy">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div>
            <h1 style="font-size: 36px; margin-bottom: 15px; color: var(--text-color);">
                <?php echo htmlspecialchars($product['name']); ?>
            </h1>

            <?php if (!empty($categories)): ?>
                <div style="margin-bottom: 15px;">
                    <?php foreach ($categories as $cat): ?>
                        <span style="background: var(--bg-light); padding: 5px 15px; border-radius: 20px; font-size: 14px; margin-left: 10px;">
                            <?php echo htmlspecialchars($cat); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="font-size: 32px; font-weight: 700; color: var(--primary-color); margin: 30px 0;">
                <span id="price-display"><?php echo number_format($product['price'], 2); ?> د.ع</span>
                <button id="currency-toggle" style="background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; padding: 5px 10px; margin-left: 10px; cursor: pointer; font-size: 14px;">
                    USD
                </button>
            </div>

            <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h3 style="margin-bottom: 15px;">الوصف</h3>
                <p style="line-height: 1.8; color: var(--text-light);">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </p>
            </div>
            
            <?php 
            // عرض الميزات بشكل منفصل للسيارات
            $categories = $db->prepare("SELECT name FROM categories WHERE product_id = ?");
            $categories->execute([$productId]);
            $productCategories = $categories->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('سيارات', $productCategories) && strpos($product['description'], 'الميزات الرئيسية:') !== false):
                $descriptionParts = explode('الميزات الرئيسية:', $product['description']);
                if (count($descriptionParts) > 1):
                    $featuresText = trim($descriptionParts[1]);
                    $features = array_filter(array_map('trim', explode("\n", $featuresText)));
            ?>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; margin-bottom: 30px; color: white;">
                <h3 style="margin-bottom: 20px; color: white; font-size: 24px;">
                    <i class="fas fa-star" style="margin-left: 10px;"></i> الميزات الرئيسية
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <?php foreach ($features as $feature): ?>
                        <?php if (!empty(trim($feature)) && trim($feature) !== '•'): ?>
                            <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 8px; backdrop-filter: blur(10px);">
                                <i class="fas fa-check-circle" style="margin-left: 10px; color: #d1fae5;"></i>
                                <span><?php echo htmlspecialchars(str_replace('•', '', trim($feature))); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php 
                endif;
            endif; 
            ?>

            <?php if (isLoggedIn()): ?>
                <form id="addToCartForm" style="margin-bottom: 30px;">
                    <!-- المقاسات -->
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 12px; font-weight: 600; color: var(--text-color); font-size: 16px;">
                            المقاس <span style="color: #6b7280; font-weight: 400;">(اختياري)</span>:
                        </label>
                        <?php if (!empty($sizes) && is_array($sizes) && count($sizes) > 0): ?>
                            <div id="sizes-buttons" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                                <?php foreach ($sizes as $size): ?>
                                    <?php 
                                    $sizeValue = is_string($size) ? trim($size) : '';
                                    if (!empty($sizeValue)): 
                                    ?>
                                        <button type="button" 
                                                class="size-btn" 
                                                data-size="<?php echo htmlspecialchars($sizeValue); ?>"
                                                style="padding: 10px 20px; border: 2px solid #d1d5db; border-radius: 6px; background: white; color: var(--text-color); font-weight: 500; cursor: pointer; transition: all 0.3s ease; min-width: 60px;">
                                            <?php echo htmlspecialchars($sizeValue); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="size" id="productSize" value="">
                        <?php else: ?>
                            <input type="text" 
                                   name="size" 
                                   id="productSize" 
                                   placeholder="اكتب المقاس الذي تريده (مثل: M, L, XL...)"
                                   style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 6px; font-size: 15px;">
                        <?php endif; ?>
                    </div>

                    <!-- الألوان -->
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 12px; font-weight: 600; color: var(--text-color); font-size: 16px;">
                            اللون <span style="color: #6b7280; font-weight: 400;">(اختياري)</span>:
                        </label>
                        <?php if (!empty($colors) && is_array($colors) && count($colors) > 0): ?>
                            <div id="colors-buttons" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                                <?php foreach ($colors as $color): ?>
                                    <?php 
                                    $colorValue = is_string($color) ? trim($color) : '';
                                    if (!empty($colorValue)): 
                                    ?>
                                        <button type="button" 
                                                class="color-btn" 
                                                data-color="<?php echo htmlspecialchars($colorValue); ?>"
                                                style="padding: 10px 20px; border: 2px solid #d1d5db; border-radius: 6px; background: white; color: var(--text-color); font-weight: 500; cursor: pointer; transition: all 0.3s ease; min-width: 80px; position: relative;">
                                            <?php echo htmlspecialchars($colorValue); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="color" id="productColor" value="">
                        <?php else: ?>
                            <input type="text" 
                                   name="color" 
                                   id="productColor" 
                                   placeholder="اكتب اللون الذي تريده (مثل: أسود، أحمر، أزرق...)"
                                   style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 6px; font-size: 15px;">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>الكمية:</label>
                        <input type="number" name="quantity" id="productQuantity" value="1" min="1" max="<?php echo $product['quantity']; ?>"
                               style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 6px;">
                        <small style="color: var(--text-light);">
                            متوفر: <?php echo $product['quantity']; ?> قطعة
                        </small>
                    </div>

                    <button type="button" onclick="addProductToCart()" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 18px;">
                        <i class="fas fa-shopping-cart"></i> أضف إلى السلة
                    </button>
                </form>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 18px; text-align: center; text-decoration: none;">
                    تسجيل الدخول للشراء
                </a>
            <?php endif; ?>

            <div style="background: #d1fae5; padding: 15px; border-radius: 8px; margin-top: 20px;">
                <p style="margin: 0; color: #065f46;">
                    <i class="fas fa-check-circle"></i> شحن مجاني للطلبات فوق 250,000 د.ع
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Handle size button selection
document.addEventListener('DOMContentLoaded', function() {
    // Size buttons
    const sizeButtons = document.querySelectorAll('.size-btn');
    sizeButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Remove active class from all size buttons
            sizeButtons.forEach(b => {
                b.style.background = 'white';
                b.style.borderColor = '#d1d5db';
                b.style.color = 'var(--text-color)';
                b.classList.remove('active');
            });
            
            // Add active class to clicked button
            this.style.background = 'var(--primary-color)';
            this.style.borderColor = 'var(--primary-color)';
            this.style.color = 'white';
            this.classList.add('active');
            
            // Set hidden input value
            const sizeInput = document.getElementById('productSize');
            if (sizeInput) {
                sizeInput.value = this.dataset.size || '';
                console.log('Size selected:', sizeInput.value);
            }
        });
    });

    // Color buttons
    const colorButtons = document.querySelectorAll('.color-btn');
    colorButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Remove active class from all color buttons
            colorButtons.forEach(b => {
                b.style.background = 'white';
                b.style.borderColor = '#d1d5db';
                b.style.color = 'var(--text-color)';
                b.classList.remove('active');
            });
            
            // Add active class to clicked button
            this.style.background = 'var(--primary-color)';
            this.style.borderColor = 'var(--primary-color)';
            this.style.color = 'white';
            this.classList.add('active');
            
            // Set hidden input value
            const colorInput = document.getElementById('productColor');
            if (colorInput) {
                colorInput.value = this.dataset.color || '';
                console.log('Color selected:', colorInput.value);
            }
        });
    });
});

function addProductToCart() {
    const productId = <?php echo $product['id']; ?>;
    const quantity = parseInt(document.getElementById('productQuantity').value) || 1;
    
    // Get size value - check both hidden input and text input
    const sizeInput = document.getElementById('productSize');
    let size = null;
    if (sizeInput) {
        const sizeValue = sizeInput.value ? sizeInput.value.trim() : '';
        size = sizeValue !== '' ? sizeValue : null;
    }
    
    // Get color value - check both hidden input and text input
    const colorInput = document.getElementById('productColor');
    let color = null;
    if (colorInput) {
        const colorValue = colorInput.value ? colorInput.value.trim() : '';
        color = colorValue !== '' ? colorValue : null;
    }
    
    console.log('Adding to cart:', { productId, quantity, size, color }); // Debug log
    
    addToCart(productId, quantity, color, size);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

