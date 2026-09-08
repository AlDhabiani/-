<?php
/**
 * ============================================================
 *  الصفحة التعريفية (لاندينغ بيج) — index.php
 * ============================================================
 */
$page_title = 'الصفحة الرئيسية';
require_once 'includes/header.php';

/* ---------- الإحصائيات ---------- */
$stats = [
    'destinations' => 0,
    'agents'       => 0,
    'packages'     => 0,
    'bookings'     => 0,
];
if ($res = $conn->query("SELECT
        (SELECT COUNT(*) FROM destinations) AS destinations,
        (SELECT COUNT(*) FROM users WHERE role = 'agent' AND status = 'active') AS agents,
        (SELECT COUNT(*) FROM packages WHERE status = 'available') AS packages,
        (SELECT COUNT(*) FROM bookings WHERE status IN ('approved','confirmed','completed')) AS bookings")) {
    $stats = $res->fetch_assoc();
}

/* ---------- الباقات المميزة (الأكثر مشاهدة) ---------- */
$featured = [];
if ($res = $conn->query("SELECT p.*, u.full_name AS agent_name
                         FROM packages p
                         LEFT JOIN users u ON u.id = p.agent_id
                         WHERE p.status = 'available'
                         ORDER BY p.views DESC, p.created_at DESC
                         LIMIT 3")) {
    while ($row = $res->fetch_assoc()) {
        $featured[] = $row;
    }
}
?>

<!-- ============ القسم الترحيبي (Hero) ============ -->
<section class="hero">
    <div class="container position-relative">
        <span class="hero-badge mb-3 d-inline-block"><i class="fa-solid fa-plane-departure me-2"></i>منصة حجز الرحلات السياحية الأولى</span>
        <h1 class="display-5 mb-3">اكتشف وجهتك القادمة...<br>وابدأ رحلتك بثقة</h1>
        <p class="lead mb-4">
            نربطك بمكاتب سياحة موثوقة، ونوفر لك باقات رحلات متنوعة بأسعار تنافسية —
            من العائلات الهادئة إلى المغامرات الجريئة. احجز رحلتك بخطوات بسيطة وآمنة.
        </p>
        <div class="d-flex flex-wrap gap-3">
            <a href="packages.php" class="btn btn-gold btn-lg px-4"><i class="fa-solid fa-compass me-2"></i>تصفح الباقات</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn btn-outline-light btn-lg px-4"><i class="fa-solid fa-user-plus me-2"></i>أنشئ حسابك مجاناً</a>
            <?php else: ?>
                <a href="<?= e(['admin' => 'admin/dashboard.php', 'agent' => 'agent/dashboard.php', 'client' => 'client/dashboard.php'][$_SESSION['role']] ?? 'packages.php') ?>" class="btn btn-outline-light btn-lg px-4"><i class="fa-solid fa-gauge-high me-2"></i>لوحتي</a>
            <?php endif; ?>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><b><?= (int)$stats['destinations'] ?>+</b><span>وجهة سياحية</span></div>
            <div class="hero-stat"><b><?= (int)$stats['agents'] ?></b><span>مكتب سياحة موثوق</span></div>
            <div class="hero-stat"><b><?= (int)$stats['packages'] ?></b><span>باقة متاحة</span></div>
            <div class="hero-stat"><b><?= (int)$stats['bookings'] ?></b><span>رحلة ناجحة</span></div>
        </div>
    </div>
</section>

<!-- ============ من نحن ============ -->
<section id="about" class="py-5" style="background:#fff;">
    <div class="container py-3">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <span class="text-primary fw-bold"><i class="fa-solid fa-circle-info me-1"></i>من نحن</span>
                <h2 class="section-title mt-2">منصة <span class="text-primary"><?= e(SITE_NAME) ?></span> تخدمك أينما كنت</h2>
                <div class="title-line mb-3"></div>
                <p class="text-secondary">
                    «<?= e(SITE_NAME) ?>» منصة إلكترونية ذكية تربط بين مكاتب السياحة والمسافرين،
                    وتوفر تجربة حجز سهلة وآمنة. نحرص على اعتماد مكاتب سياحة موثوقة بعد التحقق من
                    ترخيصاتها ووثائقها، لنضمن لك رحلات مريحة من الحجز حتى العودة.
                </p>
                <ul class="list-unstyled mt-3">
                    <li class="mb-2"><i class="fa-solid fa-circle-check text-primary me-2"></i>مكاتب سياحة مرخّصة وموثقة</li>
                    <li class="mb-2"><i class="fa-solid fa-circle-check text-primary me-2"></i>حجز إلكتروني آمن ومتابعة لحظية لحالتك</li>
                    <li class="mb-2"><i class="fa-solid fa-circle-check text-primary me-2"></i>تقييمات حقيقية من مسافرين سبقوك</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6"><div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div><div class="fw-bold">حجز آمن</div><small class="text-secondary">حماية كاملة لبياناتك وطلبك</small></div></div>
                    <div class="col-6"><div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-tags"></i></div><div class="fw-bold">أسعار تنافسية</div><small class="text-secondary">باقات متنوعة لكل الميزانيات</small></div></div>
                    <div class="col-6"><div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-headset"></i></div><div class="fw-bold">دعم متواصل</div><small class="text-secondary">فريق جاهز لمساعدتك</small></div></div>
                    <div class="col-6"><div class="feature-card"><div class="feature-icon"><i class="fa-solid fa-star"></i></div><div class="fw-bold">تقييمات صادقة</div><small class="text-secondary">آراء حقيقية قبل كل قرار</small></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ المميزات ============ -->
<section id="features" class="py-5">
    <div class="container py-3">
        <div class="text-center mb-4">
            <h2 class="section-title">لماذا تختارنا؟</h2>
            <div class="title-line mx-auto mb-3"></div>
            <p class="text-secondary">ستة أسباب تجعل رحلتك القادمة أسهل مما تتخيل</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                    <h6 class="fw-bold mb-2">وجهات متعددة</h6>
                    <small class="text-secondary">اختر من بين وجهات داخلية متنوعة — من العاصمة التاريخية إلى السواحل الخلابة.</small>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-building-user"></i></div>
                    <h6 class="fw-bold mb-2">مكاتب سياحة موثوقة</h6>
                    <small class="text-secondary">كل مكتب يمر بمراجعة واعتماد من إدارتنا بعد التحقق من الترخيص والوثائق.</small>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                    <h6 class="fw-bold mb-2">باقات لكل الذوق</h6>
                    <small class="text-secondary">عائلي، مغامرات، ثقافي، شاطئي، ديني، عملي — اختر ما يناسبك بالضبط.</small>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-timeline"></i></div>
                    <h6 class="fw-bold mb-2">متابعة لحظية</h6>
                    <small class="text-secondary">تابع حالة حجزك لحظة بلحظة: معلق، موافق، مؤكد، مكتمل.</small>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-star"></i></div>
                    <h6 class="fw-bold mb-2">تقييمات حقيقية</h6>
                    <small class="text-secondary">قارن قبل الحجز عبر تقييمات المسافرين السابقين على كل باقة.</small>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <h6 class="fw-bold mb-2">إلغاء مرن</h6>
                    <small class="text-secondary">يمكنك إلغاء حجزك أثناء مرحلة الانتظار قبل موافقة المكتب.</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ الباقات المميزة ============ -->
<section class="py-5" style="background:#fff;">
    <div class="container py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
            <div>
                <h2 class="section-title mb-1">الباقات المميزة</h2>
                <div class="title-line"></div>
            </div>
            <a href="packages.php" class="btn btn-outline-primary"><i class="fa-solid fa-list me-1"></i>عرض جميع الباقات</a>
        </div>

        <?php if (count($featured) > 0): ?>
            <div class="row g-4">
                <?php foreach ($featured as $p): ?>
                    <?= package_card($p, $p['agent_name']) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-compass fa-3x mb-3 text-primary"></i>
                <h5 class="fw-bold">لا توجد باقات متاحة حالياً</h5>
                <p class="mb-0">سوف تظهر هنا أحدث الباقات فور اعتمادها. كن أول من يعرف عن جديدنا!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ إحصائيات المنصة ============ -->
<section class="py-5" style="background: linear-gradient(135deg, var(--primary-darker), var(--primary-dark)); color:#fff;">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-3 col-6">
                <div class="fs-1 fw-bold text-gold"><?= (int)$stats['destinations'] ?>+</div>
                <div class="opacity-75">وجهة سياحية</div>
            </div>
            <div class="col-md-3 col-6">
                <div class="fs-1 fw-bold text-gold"><?= (int)$stats['agents'] ?></div>
                <div class="opacity-75">مكتب سياحة معتمد</div>
            </div>
            <div class="col-md-3 col-6">
                <div class="fs-1 fw-bold text-gold"><?= (int)$stats['packages'] ?>+</div>
                <div class="opacity-75">باقة سفر متنوعة</div>
            </div>
            <div class="col-md-3 col-6">
                <div class="fs-1 fw-bold text-gold"><?= (int)$stats['bookings'] ?>+</div>
                <div class="opacity-75">رحلة مكتملة بنجاح</div>
            </div>
        </div>
    </div>
</section>

<!-- ============ آراء العملاء ============ -->
<section class="py-5">
    <div class="container py-3">
        <div class="text-center mb-4">
            <h2 class="section-title">ماذا قال مسافرونا؟</h2>
            <div class="title-line mx-auto mb-3"></div>
            <p class="text-secondary">تجارب حقيقية من عملاء وثقوا بنا</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card testimonial-card">
                    <div class="card-body p-4">
                        <i class="fa-solid fa-quote-right quote-icon"></i>
                        <p class="mt-3 mb-3 text-secondary">«حجزت باقة صنعاء القديمة لعائلتي وكان كل شيء منظماً من البداية للنهاية. تواصلوا معنا قبل الرحلة بيومين لتأكيد التفاصيل. تجربة راقية.»</p>
                        <div class="d-flex align-items-center">
                            <span class="avatar-initial me-2" style="width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;font-weight:700;">أ</span>
                            <div>
                                <div class="fw-bold">أحمد م. العريقي</div>
                                <div class="stars small"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card testimonial-card">
                    <div class="card-body p-4">
                        <i class="fa-solid fa-quote-right quote-icon"></i>
                        <p class="mt-3 mb-3 text-secondary">«أعجبني نظام متابعة الحجز — عرفت حالتي لحظة بلحظة. رحلة عدن كانت أجمل من التوقع، والشاطئ لم أصدقه. أنصح بهم كل من يسافر مع أطفال.»</p>
                        <div class="d-flex align-items-center">
                            <span class="avatar-initial me-2" style="width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;font-weight:700;">س</span>
                            <div>
                                <div class="fw-bold">سارة الحيمي</div>
                                <div class="stars small"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card testimonial-card">
                    <div class="card-body p-4">
                        <i class="fa-solid fa-quote-right quote-icon"></i>
                        <p class="mt-3 mb-3 text-secondary">«أستثمر منصات حجز كثيرة، لكن هذه أول مرة أتعامل مع مكاتب مرخصة وموثقة بشكل واضح. باقة سيئون كانت هادئة وجميلة، وسأعود بالتأكيد.»</p>
                        <div class="d-flex align-items-center">
                            <span class="avatar-initial me-2" style="width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;font-weight:700;">خ</span>
                            <div>
                                <div class="fw-bold">خالد الشرعبي</div>
                                <div class="stars small"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ دعوة للتسجيل ============ -->
<section class="py-5">
    <div class="container">
        <div class="cta-band p-5 text-center position-relative overflow-hidden">
            <i class="fa-solid fa-suitcase-rolling position-absolute" style="font-size:9rem; opacity:.08; left:4%; top:10%; transform:rotate(-15deg);"></i>
            <h2 class="fw-bold mb-3">جاهز لرحلتك القادمة؟</h2>
            <p class="opacity-75 mb-4 mx-auto" style="max-width:520px;">
                أنشئ حسابك الآن مجاناً وابدأ حجز رحلتك الأولى — يستغرق أقل من دقيقة واحدة.
            </p>
            <a href="register.php" class="btn btn-gold btn-lg px-5"><i class="fa-solid fa-user-plus me-2"></i>سجّل الآن مجاناً</a>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
