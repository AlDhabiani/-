<?php
/**
 * ============================================================
 *  حجز باقة جديدة — client/new_booking.php
 *  (حساب السعر يتم خادماً — JavaScript للعرض فقط)
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['client']);
$page_title = 'حجز جديد';
$active = 'bookings';

$uid = (int)$user['id'];
$id  = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT p.*, u.full_name AS agent_name, u.profile_image AS agent_image
                        FROM packages p
                        LEFT JOIN users u ON u.id = p.agent_id
                        WHERE p.id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();

if (!$package || $package['status'] !== 'available') {
    flash_set('الباقة غير متاحة للحجز حالياً.', 'danger');
    redirect('my_bookings.php');
}

/* منع حجز مكرر معلق لنفس الباقة */
$chk = $conn->prepare("SELECT id FROM bookings WHERE client_id = ? AND package_id = ? AND status = 'pending' LIMIT 1");
$chk->bind_param('ii', $uid, $id);
$chk->execute();
if ($chk->get_result()->fetch_assoc()) {
    flash_set('لديك طلب حجز معلق على هذه الباقة بالفعل — تابعه من صفحة حجوزاتك.', 'warning');
    redirect('my_bookings.php');
}

$errors = [];
$old = ['booking_date' => date('Y-m-d'), 'number_of_travelers' => 1, 'special_requests' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['booking_date']       = (string)($_POST['booking_date'] ?? '');
    $old['number_of_travelers'] = (int)($_POST['number_of_travelers'] ?? 0);
    $old['special_requests']   = trim((string)($_POST['special_requests'] ?? ''));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['booking_date'])) {
        $errors[] = 'اختر تاريخ الرحلة.';
    } else {
        $ts = strtotime($old['booking_date']);
        if ($ts < strtotime(date('Y-m-d'))) {
            $errors[] = 'لا يمكن اختيار تاريخ في الماضي.';
        }
    }
    if ($old['number_of_travelers'] < 1 || $old['number_of_travelers'] > 50) {
        $errors[] = 'عدد المسافرين يجب أن يكون بين 1 و 50.';
    }

    if (count($errors) === 0) {
        /* السعر الخادُمي (لا يُعتمد على JavaScript) */
        $total = (float)$package['price'] * $old['number_of_travelers'];

        $ins = $conn->prepare("INSERT INTO bookings (package_id, client_id, booking_date, number_of_travelers, total_price, status, special_requests)
                               VALUES (?, ?, ?, ?, ?, 'pending', ?)");
        $ins->bind_param('iisids', $id, $uid, $old['booking_date'], $old['number_of_travelers'], $total, $old['special_requests']);
        if ($ins->execute() && $ins->affected_rows > 0) {
            flash_set('تم إرسال طلب الحجز بنجاح! سيصلك التأكيد بعد موافقة مكتب السياحة.', 'success');
            redirect('my_bookings.php');
        }
        $errors[] = 'تعذر حفظ الحجز — حاول مرة أخرى.';
    }
}

require __DIR__ . '/../includes/sidebar_client.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-calendar-check me-2 text-primary"></i>حجز جديد</h4>
    <a href="../package_details.php?id=<?= (int)$id ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-right me-1"></i>عودة للباقة</a>
</div>

<div class="row g-4">
    <!-- ملخص الباقة -->
    <div class="col-lg-5">
        <div class="card form-card">
            <div class="card-body p-0">
                <div style="height:210px; overflow:hidden;">
                    <img src="<?= !empty($package['image']) ? 'uploads/packages/' . e($package['image']) : 'assets/img/placeholder.svg' ?>" alt="<?= e($package['title']) ?>" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge bg-primary"><?= e(getPackageType($package['type'])) ?></span>
                        <span class="info-chip"><i class="fa-solid fa-location-dot"></i><?= e($package['destination']) ?></span>
                        <span class="info-chip"><i class="fa-regular fa-calendar"></i><?= (int)$package['duration'] ?> أيام</span>
                    </div>
                    <h5 class="fw-bold"><?= e($package['title']) ?></h5>
                    <?php if (!empty($package['agent_name'])): ?>
                        <div class="small text-secondary mb-3"><i class="fa-solid fa-building-user me-1"></i>بواسطة: <?= e($package['agent_name']) ?></div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <span class="text-secondary">السعر للشخص</span>
                        <span class="fs-5 fw-bold text-primary"><?= e(format_price($package['price'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- نموذج الحجز -->
    <div class="col-lg-7">
        <div class="card form-card">
            <div class="card-body p-4">
                <?php if (count($errors) > 0): ?>
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-4"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الرحلة <span class="text-danger">*</span></label>
                            <input type="date" name="booking_date" class="form-control" value="<?= e($old['booking_date']) ?>" min="<?= e(date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">عدد المسافرين <span class="text-danger">*</span></label>
                            <input type="number" name="number_of_travelers" id="travelers" min="1" max="50" class="form-control" value="<?= e($old['number_of_travelers']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">طلبات خاصة (اختياري)</label>
                            <textarea name="special_requests" rows="3" class="form-control" placeholder="مثال: غرفة عائلية، طعام نباتي، كرسٍ لطفل رضيع..."><?= e($old['special_requests']) ?></textarea>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background:#eef4f3;">
                                <span class="fw-bold"><i class="fa-solid fa-calculator me-1 text-primary"></i>الإجمالي التقديري</span>
                                <span class="fs-4 fw-bold text-primary" id="totalPrice"><?= e(format_price($package['price'] * (int)$old['number_of_travelers'])) ?></span>
                            </div>
                            <div class="form-text mt-1">يُحسب السعر نهائياً من النظام ويظهر عند التأكيد.</div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-gold btn-lg w-100"><i class="fa-solid fa-paper-plane me-2"></i>تأكيد طلب الحجز</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var price = <?= (float)$package['price'] ?>;
    var travelers = document.getElementById('travelers');
    var totalEl = document.getElementById('totalPrice');
    function fmt(n) {
        return Math.round(n).toLocaleString('en-US') + ' <?= e(CURRENCY) ?>';
    }
    function update() {
        var t = parseInt(travelers.value, 10) || 1;
        totalEl.textContent = fmt(price * t);
    }
    travelers.addEventListener('input', update);
    update();
})();
</script>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
