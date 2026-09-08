<?php
/**
 * ============================================================
 *  حذف باقة — admin/delete_package.php (POST فقط)
 *  - تحذف الحجوزات المرتبطة (CASCADE)
 *  - تحذف صورة الباقة من القرص
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('packages.php');
}
csrf_verify();

$id = (int)($_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT title, image FROM packages WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
if (!$package) {
    flash_set('الباقة غير موجودة.', 'danger');
    redirect('packages.php');
}

$del = $conn->prepare("DELETE FROM packages WHERE id = ?");
$del->bind_param('i', $id);
if ($del->execute()) {
    delete_upload('packages', $package['image']);
    flash_set('تم حذف الباقة «' . $package['title'] . '» وحجوزاتها المرتبطة.', 'success');
} else {
    flash_set('تعذر حذف الباقة.', 'danger');
}
redirect('packages.php');
