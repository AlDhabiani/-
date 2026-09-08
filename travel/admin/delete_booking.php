<?php
/**
 * ============================================================
 *  حذف حجز — admin/delete_booking.php (POST فقط)
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('bookings.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);

$del = $conn->prepare("DELETE FROM bookings WHERE id = ?");
$del->bind_param('i', $id);
if ($del->execute() && $del->affected_rows > 0) {
    flash_set('تم حذف الحجز نهائياً.', 'success');
} else {
    flash_set('الحجز غير موجود أو تم حذفه مسبقاً.', 'danger');
}
redirect('bookings.php');
