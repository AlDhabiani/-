<?php
/**
 * ============================================================
 *  حجوزاتي — client/my_bookings.php
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['client']);
$page_title = 'حجوزاتي';
$active = 'bookings';

$uid = (int)$user['id'];
$stmt = $conn->prepare("SELECT b.*, p.title AS package_title, p.image AS package_image, p.destination,
                               a.full_name AS agent_name
                        FROM bookings b
                        JOIN packages p ON p.id = b.package_id
                        LEFT JOIN users a ON a.id = p.agent_id
                        WHERE b.client_id = ?
                        ORDER BY b.created_at DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$bookings = $stmt->get_result();

require __DIR__ . '/../includes/sidebar_client.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-ticket me-2 text-primary"></i>حجوزاتي</h4>
    <a href="../packages.php" class="btn btn-primary"><i class="fa-solid fa-compass me-1"></i>البحث عن باقة</a>
</div>

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الباقة</th>
                        <th>الوجهة</th>
                        <th>وكيل السياحة</th>
                        <th>المسافرون</th>
                        <th>الإجمالي</th>
                        <th>تاريخ الرحلة</th>
                        <th>الحالة</th>
                        <th class="text-center">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 0; while ($b = $bookings->fetch_assoc()): $i++; ?>
                    <tr>
                        <td class="text-muted"><?= $i ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= !empty($b['package_image']) ? 'uploads/packages/' . e($b['package_image']) : 'assets/img/placeholder.svg' ?>" class="thumb" alt="">
                                <div>
                                    <div class="fw-bold small"><?= e($b['package_title']) ?></div>
                                    <a href="../package_details.php?id=<?= (int)$b['package_id'] ?>" class="text-primary" style="font-size:.75rem;">عرض الباقة <i class="fa-solid fa-up-left"></i></a>
                                </div>
                            </div>
                        </td>
                        <td class="small"><?= e($b['destination']) ?></td>
                        <td class="small"><?= e($b['agent_name'] ?: '—') ?></td>
                        <td class="fw-bold"><?= (int)$b['number_of_travelers'] ?></td>
                        <td class="fw-bold small"><?= e(format_price($b['total_price'])) ?></td>
                        <td class="small text-muted"><?= e(format_date($b['booking_date'])) ?></td>
                        <td><?= booking_badge($b['status']) ?></td>
                        <td class="text-center">
                            <?php if ($b['status'] === 'pending'): ?>
                                <form method="post" action="cancel_booking.php" class="d-inline" onsubmit="return confirm('إلغاء هذا الحجز؟ لا يمكن التراجع بعد الموافقة.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger action-btn"><i class="fa-solid fa-ban me-1"></i>إلغاء الحجز</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($i === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">
                        <div>
                            <i class="fa-solid fa-suitcase-rolling fa-2x mb-2 d-block"></i>
                            لم تقم بأي حجز بعد — <a href="../packages.php" class="text-primary fw-bold">تصفح الباقات السياحية</a>
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
