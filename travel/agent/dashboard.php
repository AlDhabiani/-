<?php
/**
 * ============================================================
 *  لوحة وكيل السياحة — agent/dashboard.php
 *  إحصائيات خاصة بباقات الوكيل
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['agent']);
$page_title = 'لوحة تحكم الوكيل';
$active = 'dashboard';

$uid = (int)$user['id'];

$stats = ['packages' => 0, 'bookings' => 0, 'pending' => 0, 'completed' => 0, 'revenue' => 0];
$stmt = $conn->prepare("SELECT
        (SELECT COUNT(*) FROM packages WHERE agent_id = ? AND status <> 'cancelled') AS packages,
        (SELECT COUNT(*) FROM bookings b WHERE b.package_id IN (SELECT id FROM packages WHERE agent_id = ?)) AS bookings,
        (SELECT COUNT(*) FROM bookings b WHERE b.status = 'pending' AND b.package_id IN (SELECT id FROM packages WHERE agent_id = ?)) AS pending,
        (SELECT COUNT(*) FROM bookings b WHERE b.status = 'completed' AND b.package_id IN (SELECT id FROM packages WHERE agent_id = ?)) AS completed,
        (SELECT COALESCE(SUM(b.total_price), 0) FROM bookings b WHERE b.status IN ('approved','confirmed','completed') AND b.package_id IN (SELECT id FROM packages WHERE agent_id = ?)) AS revenue");
$stmt->bind_param('iiiii', $uid, $uid, $uid, $uid, $uid);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$latest_bookings = [];
$stmt = $conn->prepare("SELECT b.*, p.title AS package_title, u.full_name AS client_name, u.profile_image AS client_image
                        FROM bookings b
                        JOIN packages p ON p.id = b.package_id
                        JOIN users u ON u.id = b.client_id
                        WHERE p.agent_id = ?
                        ORDER BY b.created_at DESC LIMIT 5");
$stmt->bind_param('i', $uid);
$stmt->execute();
while ($row = $stmt->get_result()->fetch_assoc()) {
    $latest_bookings[] = $row;
}

require __DIR__ . '/../includes/sidebar_agent.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-gauge-high me-2 text-primary"></i>أهلاً <?= e($user['full_name']) ?> 👋</h4>
    <a href="add_package.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>إضافة باقة</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-4 col-6">
        <div class="card stat-card stat-teal h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['packages'] ?></div>
                    <div class="stat-label">باقاتي النشطة</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-6">
        <div class="card stat-card stat-indigo h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-ticket"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['bookings'] ?></div>
                    <div class="stat-label">إجمالي الحجوزات</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-6">
        <div class="card stat-card stat-violet h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['pending'] ?></div>
                    <div class="stat-label">بانتظار موافقتك</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-6">
        <div class="card stat-card stat-rose h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div>
                    <div class="stat-value" style="font-size:1.15rem;"><?= e(format_price($stats['revenue'])) ?></div>
                    <div class="stat-label">إيرادات مؤكدة</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ((int)$stats['pending'] > 0): ?>
    <div class="alert alert-warning d-flex align-items-center justify-content-between">
        <span><i class="fa-solid fa-bell me-2"></i>لديك <?= (int)$stats['pending'] ?> حجز جديد بانتظار موافقتك</span>
        <a href="bookings.php" class="btn btn-sm btn-warning">معالجة الآن</a>
    </div>
<?php endif; ?>

<div class="card table-card">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i>آخر 5 حجوزات على باقاتك</h6>
            <a href="bookings.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>العميل</th><th>الباقة</th><th>المسافرون</th><th>الإجمالي</th><th>الحالة</th></tr>
                </thead>
                <tbody>
                    <?php if (count($latest_bookings) > 0): ?>
                        <?php foreach ($latest_bookings as $b): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?= e(user_avatar($b, 34)) ?>
                                        <span class="small fw-bold"><?= e($b['client_name']) ?></span>
                                    </div>
                                </td>
                                <td class="small"><?= e($b['package_title']) ?></td>
                                <td class="fw-bold"><?= (int)$b['number_of_travelers'] ?></td>
                                <td class="fw-bold small"><?= e(format_price($b['total_price'])) ?></td>
                                <td><?= booking_badge($b['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">لا توجد حجوزات بعد — أضف باقاتك الأولى!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
