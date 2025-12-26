// Banner Messages Rotation
const bannerMessages = [
    'شحن مجاني للطلبات فوق 250,000 د.ع!',
    'خصم 20% على أول عملية شراء!',
    'وصلات جديدة كل أسبوع!',
    'عروض حصرية للأعضاء فقط!',
    'تسوق أحدث الصيحات معنا!'
];

let currentBannerIndex = 0;
const bannerText = document.getElementById('bannerText');

if (bannerText) {
    setInterval(() => {
        currentBannerIndex = (currentBannerIndex + 1) % bannerMessages.length;
        bannerText.textContent = bannerMessages[currentBannerIndex];
    }, 2500);
}

// Mobile Menu Toggle
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const mobileMenu = document.getElementById('mobileMenu');

if (mobileMenuToggle && mobileMenu) {
    mobileMenuToggle.addEventListener('click', () => {
        mobileMenu.classList.toggle('active');
        const icon = mobileMenuToggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        }
    });
}

// Scroll to Top Button
const scrollTopBtn = document.querySelector('.scroll-top');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        scrollTopBtn.classList.add('show');
    } else {
        scrollTopBtn.classList.remove('show');
    }
});

if (scrollTopBtn) {
    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Update Cart Count
function updateCartCount() {
    // Get SITE_URL from global variable or construct it
    let siteUrl;
    if (typeof SITE_URL !== 'undefined' && SITE_URL) {
        siteUrl = SITE_URL;
    } else {
        // Fallback: construct from current location
        const pathParts = window.location.pathname.split('/').filter(p => p);
        const projectIndex = pathParts.indexOf('smart_markt');
        if (projectIndex !== -1) {
            siteUrl = window.location.origin + '/' + pathParts.slice(0, projectIndex + 1).join('/');
        } else {
            siteUrl = window.location.origin + '/smart_markt';
        }
    }
    
    fetch(siteUrl + '/api/cart/get-count.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const cartCount = document.getElementById('cartCount');
            if (cartCount && data.count !== undefined) {
                cartCount.textContent = data.count;
                cartCount.style.display = data.count > 0 ? 'flex' : 'none';
            }
        })
        .catch(error => {
            // Silently fail - user might not be logged in
            console.debug('Cart count update failed (this is normal if not logged in):', error);
        });
}

// Update cart count on page load
if (document.getElementById('cartCount')) {
    updateCartCount();
}

// Add to Cart Function
function addToCart(productId, quantity = 1, color = null, size = null) {
    // Get SITE_URL from global variable or construct it
    let siteUrl;
    if (typeof SITE_URL !== 'undefined' && SITE_URL) {
        siteUrl = SITE_URL;
    } else {
        // Fallback: construct from current location
        const pathParts = window.location.pathname.split('/').filter(p => p);
        const projectIndex = pathParts.indexOf('smart_markt');
        if (projectIndex !== -1) {
            siteUrl = window.location.origin + '/' + pathParts.slice(0, projectIndex + 1).join('/');
        } else {
            siteUrl = window.location.origin + '/smart_markt';
        }
    }
    
    fetch(siteUrl + '/api/cart/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity,
            color: color,
            size: size
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateCartCount();
            showNotification('تمت إضافة المنتج إلى السلة', 'success');
        } else {
            showNotification(data.message || 'حدث خطأ', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showNotification('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.', 'error');
    });
}

// Show Notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Currency Converter
const IQD_TO_USD_RATE = 0.00068; // 1 IQD = 0.00068 USD (approximately 1,470 IQD = 1 USD)

function formatCurrency(amount, currency) {
    if (currency === 'USD') {
        return '$' + (amount * IQD_TO_USD_RATE).toFixed(2);
    } else {
        return amount.toLocaleString() + ' د.ع';
    }
}

function toggleCurrency() {
    const priceElements = document.querySelectorAll('.product-price');
    const currencyToggle = document.getElementById('currency-toggle');
    const priceDisplay = document.getElementById('price-display');

    if (!priceElements.length && !currencyToggle && !priceDisplay) return;

    // Check current state
    const isUSD = localStorage.getItem('currency') === 'USD';
    const newCurrency = isUSD ? 'IQD' : 'USD';

    // Update localStorage
    localStorage.setItem('currency', newCurrency);

    // Update all price displays
    priceElements.forEach(element => {
        const text = element.textContent;
        const numericValue = parseFloat(text.replace(/[^\d.]/g, ''));
        element.textContent = formatCurrency(numericValue, newCurrency);
    });

    // Update single price display (product details page)
    if (priceDisplay) {
        const text = priceDisplay.textContent;
        const numericValue = parseFloat(text.replace(/[^\d.]/g, ''));
        priceDisplay.textContent = formatCurrency(numericValue, newCurrency);
    }

    // Update toggle button text
    if (currencyToggle) {
        currencyToggle.textContent = isUSD ? 'USD' : 'د.ع';
    }
}

// Initialize currency display on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedCurrency = localStorage.getItem('currency') || 'IQD';
    const currencyToggle = document.getElementById('currency-toggle');
    const globalCurrencyToggle = document.getElementById('globalCurrencyToggle');
    const currencyLabel = document.getElementById('currencyLabel');

    // Product details page toggle
    if (currencyToggle) {
        currencyToggle.textContent = savedCurrency === 'USD' ? 'د.ع' : 'USD';
        currencyToggle.addEventListener('click', toggleCurrency);
    }

    // Global header toggle
    if (globalCurrencyToggle && currencyLabel) {
        currencyLabel.textContent = savedCurrency === 'USD' ? 'USD' : 'د.ع';
        globalCurrencyToggle.addEventListener('click', function() {
            toggleCurrency();
            const newCurrency = localStorage.getItem('currency') || 'IQD';
            currencyLabel.textContent = newCurrency === 'USD' ? 'USD' : 'د.ع';
        });
    }

    // Apply saved currency preference
    if (savedCurrency === 'USD') {
        toggleCurrency();
    }
});

// Add CSS animations (only if not already added)
if (!document.getElementById('main-js-styles')) {
    const animationStyle = document.createElement('style');
    animationStyle.id = 'main-js-styles';
    animationStyle.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(-100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(animationStyle);
}

