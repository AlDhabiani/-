<?php
/**
 * ============================================================
 *  تذييل الموقع العام — includes/footer.php
 * ============================================================
 */
if (!isset($conn)) {
    require_once __DIR__ . '/functions.php';
}

$footer_destinations = $conn->query("SELECT name, country FROM destinations ORDER BY id LIMIT 6");
?>
</main>

<footer class="site-footer mt-auto">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center mb-3">
                    <span class="brand-icon footer-brand-icon"><i class="fa-solid fa-umbrella-beach"></i></span>
                    <span class="brand-text text-white fs-4"><?= e(SITE_NAME) ?></span>
                </div>
                <p class="text-white-50 small">
                    منصة ذكية تربط مكاتب السياحة بالمسافرين — حجز سهل وآمن،
                    باقات متنوعة بأسعار تنافسية، وتجربة سفر ممتعة من بداية الرحلة حتى عودتك.
                </p>
                <div class="social-links">
                    <a href="#" aria-label="فيسبوك"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="انستغرام"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="إكس"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" aria-label="واتساب"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="footer-title">روابط سريعة</h6>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fa-solid fa-angle-left me-1"></i>الرئيسية</a></li>
                    <li><a href="packages.php"><i class="fa-solid fa-angle-left me-1"></i>الباقات السياحية</a></li>
                    <li><a href="register.php"><i class="fa-solid fa-angle-left me-1"></i>تسجيل حساب</a></li>
                    <li><a href="login.php"><i class="fa-solid fa-angle-left me-1"></i>تسجيل الدخول</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 col-6">
                <h6 class="footer-title">وجهات نختارها لك</h6>
                <ul class="footer-links">
                    <?php if ($footer_destinations && $footer_destinations->num_rows > 0): ?>
                        <?php while ($d = $footer_destinations->fetch_assoc()): ?>
                            <li>
                                <a href="packages.php?destination=<?= e($d['name']) ?>">
                                    <i class="fa-solid fa-location-dot me-1 text-gold"></i><?= e($d['name']) ?>، <?= e($d['country']) ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li><span class="text-white-50">لا توجد وجهات بعد</span></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-title">تواصل معنا</h6>
                <ul class="footer-links">
                    <li><span><i class="fa-solid fa-location-dot me-2 text-gold"></i>صنعاء — اليمن</span></li>
                    <li><span><i class="fa-solid fa-phone me-2 text-gold"></i><span dir="ltr">+967 7 000 0000</span></span></li>
                    <li><span><i class="fa-solid fa-envelope me-2 text-gold"></i>info@example.com</span></li>
                    <li><span><i class="fa-regular fa-clock me-2 text-gold"></i>السبت - الخميس: 9ص - 6م</span></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <small>© <?= date('Y') ?> <?= e(SITE_NAME) ?> — جميع الحقوق محفوظة</small>
            <small class="text-white-50">صُنع بإتقان <i class="fa-solid fa-heart text-danger"></i></small>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
