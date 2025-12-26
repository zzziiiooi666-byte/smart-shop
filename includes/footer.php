    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>عن المتجر</h3>
                <p>متجرك الذكي للتسوق الإلكتروني</p>
            </div>
            <div class="footer-section">
                <h3>روابط سريعة</h3>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/index.php">الرئيسية</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/products.php">المنتجات</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/category.php">الفئات</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>معلومات</h3>
                <ul>
                    <li>شحن مجاني للطلبات فوق 250,000 د.ع</li>
                    <li>إرجاع مجاني خلال 14 يوم</li>
                    <li>ضمان الجودة</li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>تابعنا</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Shop Smart. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="<?php echo SITE_URL; ?>/assets/js/main.js?v=<?php echo time(); ?>"></script>
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo SITE_URL . '/assets/js/' . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

