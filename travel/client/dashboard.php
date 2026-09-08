<?php
/**
 * ============================================================
 *  لوحة العميل — client/dashboard.php
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['client']);
$page_title = 'لوحة حسابي';
$active = 'dashboard';

$uid = (int)$user['id'];

$stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0];
$stmt = $conn->prepare("SELECT
        (SELECT COUNT(*) FROM bookings WHERE client_id = ?) AS total,
        (SELECT COUNT(*) FROM bookings WHERE client_id = ? AND status = 'pending') AS pending,
        (SELECT COUNT(*) FROM bookings WHERE client_id = ? AND status IN ('approved','confirmed')) AS confirmed,
        (SELECT COUNT(*) FROM bookings WHERE client_id = ? AND status = 'completed') AS completed");
$stmt->bind_param('iiii', $uid, $uid, $uid, $uid);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$latest_bookings = [];
$stmt = $conn->prepare("SELECT b.*, p.title AS package_title, p.image AS package_image
                        FROM bookings b
                        JOIN packages p ON p.id = b.package_id
                        WHERE b.client_id = ?
                        ORDER BY b.created_at DESC LIMIT 5");
$stmt->bind_param('i', $uid);
$stmt->execute();
while ($row = $stmt->get_result()->fetch_assoc()) {
    $latest_bookings[] = $row;
}

require __DIR__ . '/../includes/sidebar_client.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-gauge-high me-2 text-primary"></i>أهلاً <?= e($user['full_name']) ?> 👋</h4>
    <a href="../packages.php" class="btn btn-primary"><i class="fa-solid fa-compass me-1"></i>تصفح الباقات</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-6">
        <div class="card stat-card stat-teal h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-ticket"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['total'] ?></div>
                    <div class="stat-label">إجمالي الحجوزات</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="card stat-card stat-violet h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['pending'] ?></div>
                    <div class="stat-label">بانتظار الموافقة</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="card stat-card stat-sky h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['confirmed'] ?></div>
                    <div class="stat-label">مؤكدة / موافق عليها</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="card stat-card stat-rose h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-flag-checkered"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['completed'] ?></div>
                    <div class="stat-label">رحلات مكتملة</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i>آخر 5 حجوزات</h6>
            <a href="my_bookings.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>الباقة</th><th>تاريخ الرحلة</th><th>المسافرون</th><th>الإجمالي</th><th>الحالة</th></tr>
                </thead>
                <tbody>
                    <?php if (count($latest_bookings) > 0): ?>
                        <?php foreach ($latest_bookings as $b): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= !empty($b['package_image']) ? 'uploads/packages/' . e($b['package_image']) : 'assets/img/placeholder.svg' ?>" class="thumb" alt="">
                                        <span class="small fw-bold"><?= e($b['package_title']) ?></span>
                                    </div>
                                </td>
                                <td class="small text-muted"><?= e(format_date($b['booking_date'])) ?></td>
                                <td class="fw-bold"><?= (int)$b['number_of_travelers'] ?></td>
                                <td class="fw-bold small"><?= e(format_price($b['total_price'])) ?></td>
                                <td><?= booking_badge($b['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">
                            <div>
                                <i class="fa-solid fa-suitcase-rolling fa-2x mb-2 d-block"></i>
                                لا توجد حجوزات بعد — <a href="../packages.php" class="text-primary fw-bold">ابدأ رحلتك الأولى الآن</a>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
