<?php
/**
 * ============================================================
 *  إلغاء حجز — client/cancel_booking.php (POST فقط)
 *  (حجوزاتي فقط + حالة معلق فقط)
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['client']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my_bookings.php');
}
csrf_verify();

$uid = (int)$user['id'];
$id  = (int)($_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND client_id = ? LIMIT 1");
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    flash_set('الحجز غير موجود أو خارج صلاحيتك.', 'danger');
    redirect('my_bookings.php');
}
if ($booking['status'] !== 'pending') {
    flash_set('يمكنك الإلغاء فقط أثناء حالة «معلق» (قبل موافقة المكتب).', 'warning');
    redirect('my_bookings.php');
}

$up = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND client_id = ?");
$up->bind_param('ii', $id, $uid);
if ($up->execute()) {
    flash_set('تم إلغاء حجزك بنجاح.', 'success');
} else {
    flash_set('تعذر إلغاء الحجز.', 'danger');
}
redirect('my_bookings.php');
