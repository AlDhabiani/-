<?php
/**
 * ============================================================
 *  إدارة الحجوزات — admin/bookings.php
 *  إجراءات حسب الحالة: موافقة / تأكيد / إكمال / إلغاء / حذف
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);
$page_title = 'إدارة الحجوزات';
$active = 'bookings';

$f_status = (string)($_GET['status'] ?? '');
$f_q      = trim((string)($_GET['q'] ?? ''));
$valid_status = ['pending', 'approved', 'confirmed', 'cancelled', 'completed'];
if (!in_array($f_status, $valid_status, true)) $f_status = '';

$where  = [];
$params = [];
$types  = '';
if ($f_status !== '') {
    $where[] = 'b.status = ?';
    $params[] = $f_status;
    $types .= 's';
}
if ($f_q !== '') {
    $where[] = '(u.full_name LIKE ? OR p.title LIKE ?)';
    $like = '%' . $f_q . '%';
    array_push($params, $like, $like);
    $types .= 'ss';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT b.*, p.title AS package_title, p.image AS package_image,
               u.full_name AS client_name, u.profile_image AS client_image, u.phone AS client_phone,
               a.full_name AS agent_name
        FROM bookings b
        JOIN packages p ON p.id = b.package_id
        JOIN users u ON u.id = b.client_id
        LEFT JOIN users a ON a.id = p.agent_id
        $where_sql
        ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-ticket me-2 text-primary"></i>إدارة الحجوزات</h4>
</div>

<div class="card table-card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="q" value="<?= e($f_q) ?>" class="form-control" placeholder="بحث باسم العميل / الباقة...">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    <?php foreach ($valid_status as $s): ?>
                        <option value="<?= $s ?>" <?= $f_status === $s ? 'selected' : '' ?>><?= e(getBookingStatus($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i>فلترة</button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>العميل</th>
                        <th>الباقة</th>
                        <th>الوكيل</th>
                        <th>المسافرون</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                        <th>تاريخ الحجز</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 0; while ($b = $bookings->fetch_assoc()): $i++; ?>
                    <tr>
                        <td class="text-muted"><?= $i ?></td>
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
                        <td class="small"><?= e($b['agent_name'] ?: '—') ?></td>
                        <td class="fw-bold"><?= (int)$b['number_of_travelers'] ?></td>
                        <td class="fw-bold"><?= e(format_price($b['total_price'])) ?></td>
                        <td><?= booking_badge($b['status']) ?></td>
                        <td class="small text-muted"><?= e(format_date($b['booking_date'])) ?></td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <form method="post" action="approve_booking.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <input type="hidden" name="to" value="approved">
                                        <button class="btn btn-sm btn-primary action-btn" title="موافقة"><i class="fa-solid fa-check"></i> موافقة</button>
                                    </form>
                                <?php elseif ($b['status'] === 'approved'): ?>
                                    <form method="post" action="approve_booking.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <input type="hidden" name="to" value="confirmed">
                                        <button class="btn btn-sm btn-success action-btn" title="تأكيد"><i class="fa-solid fa-double-check"></i> تأكيد</button>
                                    </form>
                                <?php elseif ($b['status'] === 'confirmed'): ?>
                                    <form method="post" action="approve_booking.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <input type="hidden" name="to" value="completed">
                                        <button class="btn btn-sm btn-outline-success action-btn" title="إكمال"><i class="fa-solid fa-flag-checkered"></i> إكمال</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($b['status'], ['pending', 'approved', 'confirmed'], true)): ?>
                                    <form method="post" action="cancel_booking.php" class="d-inline" onsubmit="return confirm('إلغاء هذا الحجز؟');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <button class="btn btn-sm btn-outline-warning action-btn" title="إلغاء"><i class="fa-solid fa-ban"></i></button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" action="delete_booking.php" class="d-inline" onsubmit="return confirm('حذف هذا الحجز نهائياً؟');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger action-btn" title="حذف"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($i === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">لا توجد حجوزات</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
