<?php
/**
 * ============================================================
 *  تدرّج حالة الحجز — admin/approve_booking.php (POST فقط)
 *  - to=approved    : معلق ← موافق عليه
 *  - to=confirmed   : موافق عليه ← مؤكد
 *  - to=completed   : مؤكد ← مكتمل
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('bookings.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$to = (string)($_POST['to'] ?? '');

$transitions = [
    'approved'  => ['from' => 'pending',   'label' => 'الموافقة'],
    'confirmed' => ['from' => 'approved',  'label' => 'التأكيد'],
    'completed' => ['from' => 'confirmed', 'label' => 'الإكمال'],
];

if (!isset($transitions[$to])) {
    flash_set('عملية غير صالحة.', 'danger');
    redirect('bookings.php');
}

$stmt = $conn->prepare("SELECT status, total_price, package_id FROM bookings WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
if (!$booking) {
    flash_set('الحجز غير موجود.', 'danger');
    redirect('bookings.php');
}

if ($booking['status'] !== $transitions[$to]['from']) {
    flash_set('لا يمكن تنفيذ ' . $transitions[$to]['label'] . ' على حجز بحالة «' . getBookingStatus($booking['status']) . '».', 'warning');
    redirect('bookings.php');
}

$up = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$up->bind_param('si', $to, $id);
if ($up->execute()) {
    flash_set('تمت ' . $transitions[$to]['label'] . ' للحجز بنجاح — الحالة الآن: ' . getBookingStatus($to) . '.', 'success');
} else {
    flash_set('تعذر تحديث حالة الحجز.', 'danger');
}
redirect('bookings.php');
