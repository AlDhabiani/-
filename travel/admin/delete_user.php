<?php
/**
 * ============================================================
 *  حذف مستخدم — admin/delete_user.php (POST فقط)
 *  - يحذف الحجوزات المرتبطة (CASCADE)
 *  - يجعل باقات المستخدم بلا وكيل (ON DELETE SET NULL)
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id === (int)$user['id']) {
    flash_set('لا يمكنك حذف حسابك الخاص.', 'danger');
    redirect('users.php');
}

$stmt = $conn->prepare("SELECT full_name, profile_image FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$target = $stmt->get_result()->fetch_assoc();

if (!$target) {
    flash_set('المستخدم غير موجود.', 'danger');
    redirect('users.php');
}

/* حذف الصورة الشخصية إن وجدت */
delete_upload('agents', $target['profile_image']);

$del = $conn->prepare("DELETE FROM users WHERE id = ?");
$del->bind_param('i', $id);
if ($del->execute()) {
    flash_set('تم حذف المستخدم «' . $target['full_name'] . '» وحجوزاته.', 'success');
} else {
    flash_set('تعذر الحذف — قد توجد قيود مرتبطة.', 'danger');
}
redirect('users.php');
