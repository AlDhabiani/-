<?php
/**
 * ============================================================
 *  إلغاء حجز — admin/cancel_booking.php (POST فقط)
 *  يُسمح بالإلغاء من: معلق / موافق عليه / مؤكد
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('bookings.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT status FROM bookings WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
if (!$booking) {
    flash_set('الحجز غير موجود.', 'danger');
    redirect('bookings.php');
}

if (!in_array($booking['status'], ['pending', 'approved', 'confirmed'], true)) {
    flash_set('لا يمكن إلغاء حجز بحالة «' . getBookingStatus($booking['status']) . '».', 'warning');
    redirect('bookings.php');
}

$up = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
$up->bind_param('i', $id);
if ($up->execute()) {
    flash_set('تم إلغاء الحجز بنجاح.', 'success');
} else {
    flash_set('تعذر إلغاء الحجز.', 'danger');
}
redirect('bookings.php');
