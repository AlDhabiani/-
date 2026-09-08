<?php
/**
 * ============================================================
 *  صفحة تفاصيل باقة سياحية — package_details.php
 *  عرض التفاصيل + معلومات الوكيل + التقييمات + نموذج التقييم
 * ============================================================
 */
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash_set('الباقة غير موجودة.', 'danger');
    redirect('packages.php');
}

/* ---------- جلب الباقة مع بيانات الوكيل ---------- */
$stmt = $conn->prepare("SELECT p.*, u.full_name AS agent_name, u.profile_image AS agent_image,
                               u.years_of_experience, u.license_number, u.bio AS agent_bio, u.phone AS agent_phone
                        FROM packages p
                        LEFT JOIN users u ON u.id = p.agent_id
                        WHERE p.id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();

if (!$package) {
    flash_set('الباقة غير موجودة أو تم حذفها.', 'danger');
    redirect('packages.php');
}

/* ---------- زيادة المشاهدات ---------- */
$upd = $conn->prepare("UPDATE packages SET views = views + 1 WHERE id = ?");
$upd->bind_param('i', $id);
$upd->execute();

$page_title = $package['title'];

/* ---------- معالجة إضافة تقييم (POST) ---------- */
$review_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {
    csrf_verify();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
        $review_error = 'يجب تسجيل الدخول كعميل لإضافة تقييم.';
    } else {
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = trim((string)($_POST['comment'] ?? ''));
        if ($rating < 1 || $rating > 5) {
            $review_error = 'يرجى اختيار عدد النجوم.';
        } elseif (mb_strlen($comment, 'UTF-8') < 5) {
            $review_error = 'اكتب تعليقاً لا يقل عن 5 أحرف.';
        } else {
            $exists = $conn->prepare("SELECT id FROM reviews WHERE package_id = ? AND client_id = ? LIMIT 1");
            $exists->bind_param('ii', $id, $_SESSION['user_id']);
            $exists->execute();
            if ($exists->get_result()->fetch_assoc()) {
                $review_error = 'لقد قيمت هذه الباقة من قبل — لكل عميل تقييم واحد فقط.';
            } else {
                $ins = $conn->prepare("INSERT INTO reviews (package_id, client_id, rating, comment) VALUES (?, ?, ?, ?)");
                $cid = $_SESSION['user_id'];
                $ins->bind_param('iiss', $id, $cid, $rating, $comment);
                if ($ins->execute()) {
                    flash_set('شكراً لك! تم نشر تقييمك.', 'success');
                    redirect('package_details.php?id=' . $id);
                }
            }
        }
    }
}

/* ---------- التقييمات ---------- */
$avg_rating = package_avg_rating($id);
$reviews = [];
if ($res = $conn->query("SELECT r.*, u.full_name FROM reviews r
                         JOIN users u ON u.id = r.client_id
                         WHERE r.package_id = " . $id . "
                         ORDER BY r.created_at DESC LIMIT 10")) {
    while ($row = $res->fetch_assoc()) {
        $reviews[] = $row;
    }
}

/* ---------- برامج الرحلة (أسطر → عناصر) ---------- */
$itinerary_lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$package['itinerary']))));
$includes_lines  = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|،|,/', (string)$package['includes']))));
$excludes_lines  = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|،|,/', (string)$package['excludes']))));

require 'includes/header.php';

$reviewed = false;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client') {
    $chk = $conn->prepare("SELECT id FROM reviews WHERE package_id = ? AND client_id = ? LIMIT 1");
    $chk->bind_param('ii', $id, $_SESSION['user_id']);
    $chk->execute();
    $reviewed = (bool)$chk->get_result()->fetch_assoc();
}
?>

<section class="py-4 py-lg-5">
    <div class="container">

        <!-- مسار التنقل -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="packages.php">الباقات</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($package['title']) ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- العمود الرئيسي -->
            <div class="col-lg-8">
                <div class="details-img mb-4">
                    <img src="<?= !empty($package['image']) ? 'uploads/packages/' . e($package['image']) : 'assets/img/placeholder.svg' ?>" alt="<?= e($package['title']) ?>">
                </div>

                <div class="card form-card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <span class="badge bg-primary"><?= e(getPackageType($package['type'])) ?></span>
                            <?= package_badge($package['status']) ?>
                            <span class="text-secondary small ms-auto"><i class="fa-regular fa-eye me-1"></i><?= (int)$package['views'] ?> مشاهدة</span>
                        </div>
                        <h3 class="fw-bold mb-3"><?= e($package['title']) ?></h3>

                        <div class="mb-3">
                            <span class="info-chip"><i class="fa-solid fa-location-dot"></i><?= e($package['destination']) ?></span>
                            <span class="info-chip"><i class="fa-regular fa-calendar"></i><?= (int)$package['duration'] ?> أيام</span>
                            <span class="info-chip"><i class="fa-solid fa-tags"></i><?= e(format_price($package['price'])) ?> / شخص</span>
                            <span class="info-chip"><i class="fa-solid fa-star text-warning"></i><?= number_format($avg_rating, 1) ?> (<?= count($reviews) ?> تقييم)</span>
                        </div>

                        <?php if (!empty($package['description'])): ?>
                            <h6 class="fw-bold mt-4"><i class="fa-solid fa-file-lines me-1 text-primary"></i>عن الباقة</h6>
                            <p class="text-secondary mb-4" style="white-space:pre-line;"><?= e($package['description']) ?></p>
                        <?php endif; ?>

                        <?php if (count($includes_lines) > 0): ?>
                            <h6 class="fw-bold"><i class="fa-solid fa-circle-check me-1 text-success"></i>ما تشمله الباقة</h6>
                            <ul class="mb-4">
                                <?php foreach ($includes_lines as $line): ?>
                                    <li class="mb-1"><i class="fa-solid fa-check text-success me-2 small"></i><?= e($line) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (count($excludes_lines) > 0): ?>
                            <h6 class="fw-bold"><i class="fa-solid fa-circle-xmark me-1 text-danger"></i>ما لا تشمله الباقة</h6>
                            <ul class="mb-4">
                                <?php foreach ($excludes_lines as $line): ?>
                                    <li class="mb-1"><i class="fa-solid fa-xmark text-danger me-2 small"></i><?= e($line) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (count($itinerary_lines) > 0): ?>
                            <h6 class="fw-bold"><i class="fa-solid fa-route me-1 text-primary"></i>برنامج الرحلة اليومي</h6>
                            <ul class="itinerary mb-2">
                                <?php foreach ($itinerary_lines as $i => $line): ?>
                                    <li>
                                        <span class="day-dot"><?= $i + 1 ?></span>
                                        <span><?= e($line) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- التقييمات -->
                <div class="card form-card">
                    <div class="card-body p-4">
                        <h5 class="fw-bold"><i class="fa-solid fa-star text-warning me-1"></i>تقييمات العملاء</h5>
                        <div class="d-flex align-items-center gap-3 mb-4 bg-light rounded p-3">
                            <div class="fs-1 fw-bold"><?= number_format($avg_rating, 1) ?></div>
                            <div>
                                <?= stars_html($avg_rating) ?>
                                <div class="small text-secondary">بناءً على <?= count($reviews) ?> تقييم</div>
                            </div>
                        </div>

                        <?php if ($review_error !== ''): ?>
                            <div class="alert alert-danger py-2"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($review_error) ?></div>
                        <?php endif; ?>

                        <!-- نموذج التقييم -->
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client' && !$reviewed): ?>
                            <form method="post" class="mb-4 p-3 rounded" style="background:#f8faf9;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add_review">
                                <h6 class="fw-bold mb-3">أضف تقييمك (بعد تجربتك للرحلة)</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">تقييمك</label>
                                        <select name="rating" class="form-select" required>
                                            <option value="">اختر...</option>
                                            <option value="5">5 نجوم — ممتاز</option>
                                            <option value="4">4 نجوم — جيد جداً</option>
                                            <option value="3">3 نجوم — جيد</option>
                                            <option value="2">2 نجوم — مقبول</option>
                                            <option value="1">1 نجم — ضعيف</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold">تعليقك</label>
                                        <input type="text" name="comment" class="form-control" placeholder="شاركنا تجربتك..." required minlength="5">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i>نشر التقييم</button>
                                    </div>
                                </div>
                            </form>
                        <?php elseif ($reviewed): ?>
                            <div class="alert alert-success py-2"><i class="fa-solid fa-circle-check me-2"></i>شكراً لك — لقد نشرت تقييمك لهذه الباقة.</div>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-light border py-2"><i class="fa-solid fa-circle-info me-2 text-primary"></i><a href="login.php?redirect=package_details.php?id=<?= $id ?>" class="fw-bold">سجّل الدخول</a> لإضافة تقييمك.</div>
                        <?php endif; ?>

                        <!-- قائمة التقييمات -->
                        <?php if (count($reviews) > 0): ?>
                            <div class="row g-3">
                                <?php foreach ($reviews as $rv): ?>
                                    <div class="col-md-6">
                                        <div class="card review-item h-100">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-bold"><?= e($rv['full_name']) ?></span>
                                                    <span class="review-rating-stars small"><?= stars_html($rv['rating']) ?></span>
                                                </div>
                                                <p class="small text-secondary mb-2" style="white-space:pre-line;"><?= e($rv['comment']) ?></p>
                                                <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= e(format_date($rv['created_at'])) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3 mb-0"><i class="fa-regular fa-comment-dots me-2"></i>لا توجد تقييمات بعد — كن أول المقيّمين!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- العمود الجانبي -->
            <div class="col-lg-4">
                <!-- بطاقة الحجز -->
                <div class="card form-card mb-4 sticky-lg-top" style="top: 84px;">
                    <div class="card-body p-4 text-center">
                        <div class="text-secondary small">السعر للشخص الواحد</div>
                        <div class="fs-2 fw-bold text-primary mb-3"><?= e(format_price($package['price'])) ?></div>

                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client' && $package['status'] === 'available'): ?>
                            <a href="client/new_booking.php?id=<?= (int)$package['id'] ?>" class="btn btn-gold w-100 btn-lg mb-2">
                                <i class="fa-solid fa-calendar-check me-2"></i>احجز الآن
                            </a>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <a href="login.php?redirect=client/new_booking.php?id=<?= (int)$package['id'] ?>" class="btn btn-gold w-100 btn-lg mb-2">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>سجّل الدخول للحجز
                            </a>
                        <?php elseif ($_SESSION['role'] !== 'client'): ?>
                            <div class="alert alert-light border py-2 small">الحجوزات متاحة لعملاء المنصة فقط.</div>
                        <?php else: ?>
                            <div class="alert alert-warning py-2 small">هذه الباقة غير متاحة للحجز حالياً.</div>
                        <?php endif; ?>

                        <ul class="list-unstyled small text-secondary mt-3 mb-0">
                            <li class="mb-1"><i class="fa-solid fa-shield-halved text-primary me-2"></i>حجز آمن وموثق</li>
                            <li class="mb-1"><i class="fa-solid fa-rotate-left text-primary me-2"></i>إلغاء مجاني أثناء الانتظار</li>
                            <li class="mb-0"><i class="fa-solid fa-headset text-primary me-2"></i>متابعة حالة الحجز لحظة بلحظة</li>
                        </ul>
                    </div>
                </div>

                <!-- بطاقة الوكيل -->
                <?php if ($package['agent_id']): ?>
                    <div class="card agent-card">
                        <div class="card-body p-4 text-center">
                            <div class="mb-2"><?= e(user_avatar([
                                'full_name'     => $package['agent_name'],
                                'profile_image' => $package['agent_image'],
                            ], 72)) ?></div>
                            <h5 class="fw-bold mb-0"><?= e($package['agent_name']) ?></h5>
                            <div class="text-gold small fw-bold mb-2"><i class="fa-solid fa-building-user me-1"></i>وكيل سياحة معتمد</div>
                            <?php if (!empty($package['agent_bio'])): ?>
                                <p class="small text-secondary mb-2" style="white-space:pre-line;"><?= e(mb_substr($package['agent_bio'], 0, 160, 'UTF-8')) ?><?= mb_strlen($package['agent_bio'], 'UTF-8') > 160 ? '...' : '' ?></p>
                            <?php endif; ?>
                            <div class="small text-secondary">
                                <?php if (!empty($package['years_of_experience'])): ?>
                                    <span class="d-block mb-1"><i class="fa-solid fa-briefcase me-1"></i><?= (int)$package['years_of_experience'] ?> سنة خبرة</span>
                                <?php endif; ?>
                                <?php if (!empty($package['agent_phone'])): ?>
                                    <span class="d-block mb-1"><i class="fa-solid fa-phone me-1"></i><span dir="ltr"><?= e($package['agent_phone']) ?></span></span>
                                <?php endif; ?>
                                <?php if (!empty($package['license_number'])): ?>
                                    <span class="d-block"><i class="fa-solid fa-id-card me-1"></i>ترخيص: <?= e($package['license_number']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
