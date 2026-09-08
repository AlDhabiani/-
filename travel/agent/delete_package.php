<?php
/**
 * ============================================================
 *  حذف باقة — agent/delete_package.php (POST فقط)
 *  (الوكيل يحذف باقاته فقط)
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my_packages.php');
}
csrf_verify();

$uid = (int)$user['id'];
$id  = (int)($_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT title, image FROM packages WHERE id = ? AND agent_id = ? LIMIT 1");
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
if (!$package) {
    flash_set('الباقة غير موجودة أو لا تملك صلاحيتها.', 'danger');
    redirect('my_packages.php');
}

$del = $conn->prepare("DELETE FROM packages WHERE id = ? AND agent_id = ?");
$del->bind_param('ii', $id, $uid);
if ($del->execute()) {
    delete_upload('packages', $package['image']);
    flash_set('تم حذف الباقة «' . $package['title'] . '».', 'success');
} else {
    flash_set('تعذر حذف الباقة.', 'danger');
}
redirect('my_packages.php');
