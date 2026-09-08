<?php
/**
 * ============================================================
 *  تعطيل / تفعيل مستخدم — admin/toggle_user.php (POST فقط)
 *  - نشط ↔ موقوف
 *  - بانتظار الموافقة → نشط (اعتماد مباشر)
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
    flash_set('لا يمكنك تغيير حالة حسابك الخاص.', 'danger');
    redirect('users.php');
}

$stmt = $conn->prepare("SELECT full_name, status FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$target = $stmt->get_result()->fetch_assoc();
if (!$target) {
    flash_set('المستخدم غير موجود.', 'danger');
    redirect('users.php');
}

$next = match ($target['status']) {
    'active'    => 'suspended',
    'suspended' => 'active',
    default     => 'active', // pending → اعتماد
};

$up = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
$up->bind_param('si', $next, $id);
if ($up->execute()) {
    $msg = $next === 'suspended'
        ? 'تم إيقاف المستخدم «' . $target['full_name'] . '».'
        : 'تم تفعيل المستخدم «' . $target['full_name'] . '».';
    flash_set($msg, 'success');
} else {
    flash_set('تعذر تغيير الحالة.', 'danger');
}
redirect('users.php');
