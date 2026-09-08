<?php
/**
 * ============================================================
 *  حجوزات باقاتي — agent/bookings.php
 *  عرض + إجراءات: موافقة / تأكيد / إلغاء (على باقات الوكيل فقط)
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['agent']);
$page_title = 'حجوزات باقاتي';
$active = 'bookings';

$uid = (int)$user['id'];

/* ---------- معالجة الإجراءات (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');
    $bid    = (int)($_POST['id'] ?? 0);

    $stmt = $conn->prepare("SELECT b.*, p.agent_id FROM bookings b JOIN packages p ON p.id = b.package_id WHERE b.id = ? LIMIT 1");
    $stmt->bind_param('i', $bid);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking || (int)$booking['agent_id'] !== $uid) {
        flash_set('الحجز غير موجود أو خارج صلاحيتك.', 'danger');
        redirect('bookings.php');
    }

    if ($action === 'approve' && $booking['status'] === 'pending') {
        $up = $conn->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
        $up->bind_param('i', $bid);
        $up->execute();
        flash_set('تمت الموافقة على الحجز.', 'success');
    } elseif ($action === 'confirm' && $booking['status'] === 'approved') {
        $up = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
        $up->bind_param('i', $bid);
        $up->execute();
        flash_set('تم تأكيد الحجز — أصبح جاهزاً للتنفيذ.', 'success');
    } elseif ($action === 'cancel' && in_array($booking['status'], ['pending', 'approved', 'confirmed'], true)) {
        $up = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $up->bind_param('i', $bid);
        $up->execute();
        flash_set('تم إلغاء الحجز.', 'success');
    } else {
        flash_set('الإجراء غير متاح لهذه الحالة (' . getBookingStatus($booking['status']) . ').', 'warning');
    }
    redirect('bookings.php');
}

/* ---------- الفلاتر ---------- */
$f_status = (string)($_GET['status'] ?? '');
$valid_status = ['pending', 'approved', 'confirmed', 'cancelled', 'completed'];
if (!in_array($f_status, $valid_status, true)) $f_status = '';

$sql    = "SELECT b.*, p.title AS package_title, p.image AS package_image,
                  u.full_name AS client_name, u.profile_image AS client_image, u.phone AS client_phone
           FROM bookings b
           JOIN packages p ON p.id = b.package_id
           JOIN users u ON u.id = b.client_id
           WHERE p.agent_id = ? " . ($f_status !== '' ? "AND b.status = '$f_status' " : '') .
           "ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $uid);
$stmt->execute();
$bookings = $stmt->get_result();

require __DIR__ . '/../includes/sidebar_agent.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-ticket me-2 text-primary"></i>حجوزات باقاتي</h4>
</div>

<div class="card table-card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    <?php foreach ($valid_status as $s): ?>
                        <option value="<?= $s ?>" <?= $f_status === $s ? 'selected' : '' ?>><?= e(getBookingStatus($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i>فلترة</button>
            </div>
            <?php if ($f_status !== ''): ?>
                <div class="col-md-2"><a href="bookings.php" class="btn btn-outline-secondary w-100">مسح</a></div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>العميل</th>
                        <th>الباقة</th>
                        <th>المسافرون</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                        <th>تاريخ الرحلة</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 0; while ($b = $bookings->fetch_assoc()): $i++; ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?= e(user_avatar($b, 36)) ?>
                                <div>
                                    <div class="fw-bold small"><?= e($b['client_name']) ?></div>
                                    <div class="text-muted" style="font-size:.75rem;" dir="ltr"><?= e($b['client_phone'] ?: '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= !empty($b['package_image']) ? 'uploads/packages/' . e($b['package_image']) : 'assets/img/placeholder.svg' ?>" class="thumb" alt="">
                                <span class="small"><?= e($b['package_title']) ?></span>
                            </div>
                        </td>
                        <td class="fw-bold"><?= (int)$b['number_of_travelers'] ?></td>
                        <td class="fw-bold"><?= e(format_price($b['total_price'])) ?></td>
                        <td><?= booking_badge($b['status']) ?></td>
                        <td class="small text-muted"><?= e(format_date($b['booking_date'])) ?></td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <button class="btn btn-sm btn-primary action-btn"><i class="fa-solid fa-check me-1"></i>موافقة</button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('إلغاء هذا الحجز؟');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <button class="btn btn-sm btn-outline-warning action-btn"><i class="fa-solid fa-ban"></i></button>
                                    </form>
                                <?php elseif ($b['status'] === 'approved'): ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="confirm">
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <button class="btn btn-sm btn-success action-btn"><i class="fa-solid fa-double-check me-1"></i>تأكيد</button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('إلغاء هذا الحجز؟');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <button class="btn btn-sm btn-outline-warning action-btn"><i class="fa-solid fa-ban"></i></button>
                                    </form>
                                <?php elseif ($b['status'] === 'confirmed'): ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('إلغاء هذا الحجز المؤكد؟');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <button class="btn btn-sm btn-outline-warning action-btn"><i class="fa-solid fa-ban me-1"></i>إلغاء</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($i === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">لا توجد حجوزات</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
