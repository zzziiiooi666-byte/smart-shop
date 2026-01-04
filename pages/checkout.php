<?php
$pageTitle = 'إتمام الطلب';
require_once __DIR__ . '/../config/config.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
$db = getDB();
$userId = $_SESSION['user_id'];

// Get cart items
$stmt = $db->prepare("
    SELECT c.*, p.name, p.price, p.id as product_id
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    redirect(SITE_URL . '/pages/cart.php');
}

$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
$shipping = $total >= 250000 ? 0 : 5000; // شحن مجاني للطلبات فوق 250,000 د.ع
$grandTotal = $total + $shipping;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $zip = sanitize($_POST['zip'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash');

    if (empty($firstName) || empty($lastName) || empty($address) || empty($city) || empty($state) || empty($zip)) {
        $error = 'يرجى ملء جميع الحقول';
    } elseif (empty($paymentMethod)) {
        $error = 'يرجى اختيار طريقة الدفع';
    } else {
        try {
            $db->beginTransaction();

            // Get user name for notifications
            $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            $userName = $user['name'] ?? 'مستخدم';

            // Create order address
            $stmt = $db->prepare("INSERT INTO order_addresses (user_id, first_name, last_name, address, city, state, zip) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $firstName, $lastName, $address, $city, $state, $zip]);
            $addressId = $db->lastInsertId();

            // Create orders for each cart item
            $orderIds = [];
            $trackingNumbers = [];
            foreach ($cartItems as $item) {
                // Generate tracking number
                $trackingNumber = 'TRK' . strtoupper(uniqid());
                $trackingNumbers[] = $trackingNumber;
                
                // Map payment method to display name
                $paymentMethods = [
                    'cash' => 'نقد عند الاستلام',
                    'card' => 'بطاقة ائتمان',
                    'wallet' => 'محفظة إلكترونية',
                    'bank_transfer' => 'تحويل بنكي'
                ];
                $paymentMethodName = $paymentMethods[$paymentMethod] ?? 'نقد عند الاستلام';
                
                $stmt = $db->prepare("INSERT INTO orders (user_id, product_id, address_id, quantity, status, payment_method, tracking_number, shipping_status) VALUES (?, ?, ?, ?, 'PENDING', ?, ?, 'pending')");
                $stmt->execute([$userId, $item['product_id'], $addressId, $item['quantity'], $paymentMethodName, $trackingNumber]);
                $orderId = $db->lastInsertId();
                $orderIds[] = $orderId;
                
                // Create notification for order creation (for user)
                $stmt = $db->prepare("INSERT INTO notifications (user_id, order_id, title, message, type) VALUES (?, ?, ?, ?, 'order_created')");
                $productName = $item['name'] ?? 'منتج';
                $message = "تم إنشاء طلبك بنجاح! رقم التتبع: {$trackingNumber}";
                $stmt->execute([$userId, $orderId, "تم إنشاء طلب جديد", $message]);
            }

            // Send notification to all admin users about the new order
            try {
                $stmt = $db->prepare("SELECT id FROM users WHERE isAdmin = 1");
                $stmt->execute();
                $admins = $stmt->fetchAll();
                
                $itemsCount = count($cartItems);
                $firstTrackingNumber = $trackingNumbers[0] ?? '';
                $firstOrderId = $orderIds[0] ?? null;
                $message = "تم إنشاء طلب جديد من المستخدم: {$userName} ({$itemsCount} منتج) - الإجمالي: " . number_format($grandTotal, 2) . " د.ع - رقم التتبع: {$firstTrackingNumber}";
                
                foreach ($admins as $admin) {
                    $stmt = $db->prepare("INSERT INTO notifications (user_id, order_id, title, message, type) VALUES (?, ?, ?, ?, 'order_created')");
                    $stmt->execute([$admin['id'], $firstOrderId, 'طلب جديد', $message]);
                }
            } catch (Exception $e) {
                // Silently fail if notifications table doesn't exist or there's an error
                error_log("Admin notification error: " . $e->getMessage());
            }

            // Clear cart
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);

            $db->commit();
            $success = 'تم إنشاء الطلب بنجاح! سيتم إرسال إشعار عند شحن المنتج.';
            
            // Redirect after 2 seconds
            header('Refresh: 2; url=' . SITE_URL . '/pages/orders.php');
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'حدث خطأ أثناء إنشاء الطلب: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <h1 style="text-align: center; margin-bottom: 40px; font-size: 36px;">إتمام الطلب</h1>

    <?php if ($error): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($success); ?>
            <p>سيتم توجيهك إلى صفحة الطلبات...</p>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 40px;">
        <!-- Order Form -->
        <div>
            <h2 style="margin-bottom: 20px;">معلومات الشحن</h2>
            <form method="POST" class="form-container" style="max-width: 100%;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="first_name">الاسم الأول *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">اسم العائلة *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">العنوان *</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="city">المدينة *</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    <div class="form-group">
                        <label for="state">المنطقة *</label>
                        <input type="text" id="state" name="state" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="zip">الرمز البريدي *</label>
                    <input type="text" id="zip" name="zip" required>
                </div>

                <!-- Payment Method Selection -->
                <div class="form-group" style="margin-top: 20px;">
                    <label style="font-size: 18px; font-weight: 600; margin-bottom: 15px; display: block;">طريقة الدفع *</label>
                    <div style="display: grid; gap: 12px;">
                        <label style="display: flex; align-items: center; padding: 15px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.3s; background: white;" 
                               onmouseover="this.style.borderColor='var(--primary-color)'; this.style.background='#f0f9ff';" 
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='white';">
                            <input type="radio" name="payment_method" value="cash" checked onchange="togglePaymentDetails('cash')" style="margin-left: 10px; width: 18px; height: 18px; cursor: pointer;" required>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; margin-bottom: 5px;">
                                    <i class="fas fa-money-bill-wave" style="margin-left: 8px; color: #10b981;"></i>
                                    نقد عند الاستلام
                                </div>
                                <div style="font-size: 13px; color: #6b7280;">ادفع نقداً عند استلام الطلب - لا حاجة لمعلومات إضافية</div>
                            </div>
                        </label>
                        
                        <label style="display: flex; align-items: center; padding: 15px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.3s; background: white;" 
                               onmouseover="this.style.borderColor='var(--primary-color)'; this.style.background='#f0f9ff';" 
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='white';">
                            <input type="radio" name="payment_method" value="card" onchange="togglePaymentDetails('card')" style="margin-left: 10px; width: 18px; height: 18px; cursor: pointer;" required>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; margin-bottom: 5px;">
                                    <i class="fas fa-credit-card" style="margin-left: 8px; color: #3b82f6;"></i>
                                    بطاقة ائتمان
                                </div>
                                <div style="font-size: 13px; color: #6b7280;">ادفع باستخدام بطاقة الائتمان أو الخصم - Visa, Mastercard, Mada</div>
                            </div>
                        </label>
                        
                        <label style="display: flex; align-items: center; padding: 15px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.3s; background: white;" 
                               onmouseover="this.style.borderColor='var(--primary-color)'; this.style.background='#f0f9ff';" 
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='white';">
                            <input type="radio" name="payment_method" value="wallet" onchange="togglePaymentDetails('wallet')" style="margin-left: 10px; width: 18px; height: 18px; cursor: pointer;" required>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; margin-bottom: 5px;">
                                    <i class="fas fa-wallet" style="margin-left: 8px; color: #8b5cf6;"></i>
                                    محفظة إلكترونية
                                </div>
                                <div style="font-size: 13px; color: #6b7280;">ادفع باستخدام المحفظة الإلكترونية - Zain Cash, AsiaHawala, FastPay</div>
                            </div>
                        </label>
                        
                        <label style="display: flex; align-items: center; padding: 15px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.3s; background: white;" 
                               onmouseover="this.style.borderColor='var(--primary-color)'; this.style.background='#f0f9ff';" 
                               onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='white';">
                            <input type="radio" name="payment_method" value="bank_transfer" onchange="togglePaymentDetails('bank_transfer')" style="margin-left: 10px; width: 18px; height: 18px; cursor: pointer;" required>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; margin-bottom: 5px;">
                                    <i class="fas fa-university" style="margin-left: 8px; color: #f59e0b;"></i>
                                    تحويل بنكي
                                </div>
                                <div style="font-size: 13px; color: #6b7280;">تحويل مباشر من حسابك البنكي - جميع البنوك العراقية</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Payment Details Sections -->
                
                <!-- Credit Card Details -->
                <div id="card-details" class="payment-details" style="display: none; margin-top: 20px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 2px solid #e5e7eb;">
                    <h3 style="margin-bottom: 15px; color: var(--text-color); font-size: 16px;">
                        <i class="fas fa-credit-card" style="margin-left: 8px; color: #3b82f6;"></i>
                        تفاصيل بطاقة الائتمان
                    </h3>
                    <div class="form-group">
                        <label for="card_number">رقم البطاقة *</label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" 
                               oninput="formatCardNumber(this)" pattern="[0-9\s]{13,19}" style="font-family: monospace;">
                        <small style="color: #6b7280; font-size: 12px;">أدخل رقم البطاقة (13-19 رقم)</small>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="card_expiry">تاريخ الانتهاء *</label>
                            <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" maxlength="5" 
                                   oninput="formatExpiry(this)" pattern="[0-9]{2}/[0-9]{2}">
                            <small style="color: #6b7280; font-size: 12px;">شهر/سنة</small>
                        </div>
                        <div class="form-group">
                            <label for="card_cvv">CVV *</label>
                            <input type="text" id="card_cvv" name="card_cvv" placeholder="123" maxlength="4" 
                                   pattern="[0-9]{3,4}" style="font-family: monospace;">
                            <small style="color: #6b7280; font-size: 12px;">الرمز الأمني (3-4 أرقام)</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="card_name">اسم حامل البطاقة *</label>
                        <input type="text" id="card_name" name="card_name" placeholder="كما هو مكتوب على البطاقة">
                    </div>
                    <div style="background: #e0f2fe; padding: 12px; border-radius: 6px; margin-top: 10px; border-right: 3px solid #3b82f6;">
                        <p style="margin: 0; font-size: 13px; color: #0369a1;">
                            <i class="fas fa-shield-alt" style="margin-left: 5px;"></i>
                            <strong>آمن ومحمي:</strong> جميع معلومات الدفع مشفرة ومحمية. نحن لا نخزن بيانات بطاقتك الائتمانية.
                        </p>
                    </div>
                </div>

                <!-- Wallet Details -->
                <div id="wallet-details" class="payment-details" style="display: none; margin-top: 20px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 2px solid #e5e7eb;">
                    <h3 style="margin-bottom: 15px; color: var(--text-color); font-size: 16px;">
                        <i class="fas fa-wallet" style="margin-left: 8px; color: #8b5cf6;"></i>
                        تفاصيل المحفظة الإلكترونية
                    </h3>
                    <div class="form-group">
                        <label for="wallet_type">نوع المحفظة *</label>
                        <select id="wallet_type" name="wallet_type" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; width: 100%;">
                            <option value="">اختر نوع المحفظة</option>
                            <option value="zain_cash">Zain Cash</option>
                            <option value="asia_hawala">AsiaHawala</option>
                            <option value="fastpay">FastPay</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="wallet_phone">رقم الهاتف المرتبط بالمحفظة *</label>
                        <input type="tel" id="wallet_phone" name="wallet_phone" placeholder="07XX XXX XXXX" 
                               pattern="[0-9]{10,11}" maxlength="11">
                        <small style="color: #6b7280; font-size: 12px;">رقم الهاتف المستخدم في المحفظة الإلكترونية</small>
                    </div>
                    <div style="background: #f3e8ff; padding: 12px; border-radius: 6px; margin-top: 10px; border-right: 3px solid #8b5cf6;">
                        <p style="margin: 0; font-size: 13px; color: #6b21a8;">
                            <i class="fas fa-info-circle" style="margin-left: 5px;"></i>
                            <strong>ملاحظة:</strong> سيتم إرسال رابط الدفع إلى رقم هاتفك. تأكد من أن الرقم صحيح ومفعل.
                        </p>
                    </div>
                </div>

                <!-- Bank Transfer Details -->
                <div id="bank_transfer-details" class="payment-details" style="display: none; margin-top: 20px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 2px solid #e5e7eb;">
                    <h3 style="margin-bottom: 15px; color: var(--text-color); font-size: 16px;">
                        <i class="fas fa-university" style="margin-left: 8px; color: #f59e0b;"></i>
                        تفاصيل التحويل البنكي
                    </h3>
                    <div class="form-group">
                        <label for="bank_name">اسم البنك *</label>
                        <select id="bank_name" name="bank_name" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; width: 100%;">
                            <option value="">اختر البنك</option>
                            <option value="الرافدين">بنك الرافدين</option>
                            <option value="الرشيد">بنك الرشيد</option>
                            <option value="العراق">بنك العراق</option>
                            <option value="التجاري">البنك التجاري العراقي</option>
                            <option value="الاستثمار">بنك الاستثمار العراقي</option>
                            <option value="الخليج">بنك الخليج</option>
                            <option value="other">بنك آخر</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="account_number">رقم الحساب *</label>
                        <input type="text" id="account_number" name="account_number" placeholder="رقم الحساب البنكي" 
                               pattern="[0-9]+" maxlength="20">
                        <small style="color: #6b7280; font-size: 12px;">رقم الحساب الذي سيتم التحويل منه</small>
                    </div>
                    <div class="form-group">
                        <label for="iban">IBAN (اختياري)</label>
                        <input type="text" id="iban" name="iban" placeholder="IQXX XXXX XXXX XXXX XXXX XXXX" 
                               pattern="[A-Z]{2}[0-9]{2}[A-Z0-9]+" maxlength="34" style="font-family: monospace;">
                        <small style="color: #6b7280; font-size: 12px;">الرقم الدولي للحساب البنكي (إن وجد)</small>
                    </div>
                    <div style="background: #fef3c7; padding: 12px; border-radius: 6px; margin-top: 10px; border-right: 3px solid #f59e0b;">
                        <p style="margin: 0; font-size: 13px; color: #92400e;">
                            <i class="fas fa-exclamation-triangle" style="margin-left: 5px;"></i>
                            <strong>مهم:</strong> بعد إتمام الطلب، سيتم إرسال تفاصيل الحساب البنكي للمتجر لإتمام التحويل. يرجى الاحتفاظ بإيصال التحويل.
                        </p>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 18px; margin-top: 20px;" onclick="return validatePaymentDetails()">
                    إتمام الطلب
                </button>
            </form>
        </div>

        <!-- Order Summary -->
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow-lg); height: fit-content; position: sticky; top: 100px;">
            <h2 style="margin-bottom: 20px;">ملخص الطلب</h2>
            
            <div style="margin-bottom: 20px;">
                <?php foreach ($cartItems as $item): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                        <div>
                            <p style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></p>
                            <p style="color: var(--text-light); font-size: 14px;">
                                الكمية: <?php echo $item['quantity']; ?>
                            </p>
                        </div>
                        <p><?php echo number_format($item['price'] * $item['quantity'], 2); ?> د.ع</p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="border-top: 2px solid var(--border-color); padding-top: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>المجموع الفرعي:</span>
                    <span><?php echo number_format($total, 2); ?> د.ع</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>الشحن:</span>
                    <span><?php echo $shipping == 0 ? 'مجاني' : number_format($shipping, 2) . ' د.ع'; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--border-color);">
                    <span>الإجمالي:</span>
                    <span style="color: var(--primary-color);"><?php echo number_format($grandTotal, 2); ?> د.ع</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePaymentDetails(method) {
    // Hide all payment details sections
    const allDetails = document.querySelectorAll('.payment-details');
    allDetails.forEach(detail => {
        detail.style.display = 'none';
    });
    
    // Remove required attribute from all payment detail inputs
    const allInputs = document.querySelectorAll('.payment-details input, .payment-details select');
    allInputs.forEach(input => {
        input.removeAttribute('required');
    });
    
    // Show selected payment details
    if (method !== 'cash') {
        const selectedDetails = document.getElementById(method + '-details');
        if (selectedDetails) {
            selectedDetails.style.display = 'block';
            // Add required attribute to inputs in selected section
            const inputs = selectedDetails.querySelectorAll('input[type="text"], input[type="tel"], select');
            inputs.forEach(input => {
                if (input.id !== 'iban') { // IBAN is optional
                    input.setAttribute('required', 'required');
                }
            });
        }
    }
}

function formatCardNumber(input) {
    // Remove all non-digits
    let value = input.value.replace(/\D/g, '');
    
    // Add spaces every 4 digits
    let formatted = value.match(/.{1,4}/g);
    if (formatted) {
        input.value = formatted.join(' ');
    } else {
        input.value = value;
    }
}

function formatExpiry(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length >= 2) {
        input.value = value.substring(0, 2) + '/' + value.substring(2, 4);
    } else {
        input.value = value;
    }
}

function validatePaymentDetails() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    
    if (paymentMethod === 'card') {
        const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
        const cardExpiry = document.getElementById('card_expiry').value;
        const cardCvv = document.getElementById('card_cvv').value;
        const cardName = document.getElementById('card_name').value;
        
        if (cardNumber.length < 13 || cardNumber.length > 19) {
            alert('يرجى إدخال رقم بطاقة صحيح (13-19 رقم)');
            return false;
        }
        
        if (!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
            alert('يرجى إدخال تاريخ انتهاء صحيح (MM/YY)');
            return false;
        }
        
        if (cardCvv.length < 3 || cardCvv.length > 4) {
            alert('يرجى إدخال CVV صحيح (3-4 أرقام)');
            return false;
        }
        
        if (!cardName.trim()) {
            alert('يرجى إدخال اسم حامل البطاقة');
            return false;
        }
    } else if (paymentMethod === 'wallet') {
        const walletType = document.getElementById('wallet_type').value;
        const walletPhone = document.getElementById('wallet_phone').value;
        
        if (!walletType) {
            alert('يرجى اختيار نوع المحفظة');
            return false;
        }
        
        if (!/^07\d{9}$/.test(walletPhone)) {
            alert('يرجى إدخال رقم هاتف صحيح (07XX XXX XXXX)');
            return false;
        }
    } else if (paymentMethod === 'bank_transfer') {
        const bankName = document.getElementById('bank_name').value;
        const accountNumber = document.getElementById('account_number').value;
        
        if (!bankName) {
            alert('يرجى اختيار اسم البنك');
            return false;
        }
        
        if (!accountNumber || accountNumber.length < 5) {
            alert('يرجى إدخال رقم حساب صحيح');
            return false;
        }
    }
    
    return true;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const checkedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (checkedMethod) {
        togglePaymentDetails(checkedMethod.value);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

