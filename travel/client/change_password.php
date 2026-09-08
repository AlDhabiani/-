<?php
/**
 * ============================================================
 *  تغيير كلمة المرور — العميل — client/change_password.php
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['client']);
$page_title = 'تغيير كلمة المرور';
$active = 'password';

$uid = (int)$user['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $current = (string)($_POST['current_password'] ?? '');
    $new     = (string)($_POST['new_password'] ?? '');
    $new2    = (string)($_POST['new_password2'] ?? '');

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!password_verify($current, $row['password'])) {
        $error = 'كلمة المرور الحالية غير صحيحة.';
    } elseif (strlen($new) < 6) {
        $error = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.';
    } elseif ($new !== $new2) {
        $error = 'كلمتا المرور الجديدتان غير متطابقتين.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $up->bind_param('si', $hash, $uid);
        if ($up->execute()) {
            flash_set('تم تغيير كلمة المرور بنجاح.', 'success');
            redirect('profile.php');
        }
        $error = 'تعذر الحفظ — حاول مرة أخرى.';
    }
}

require __DIR__ . '/../includes/sidebar_client.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-key me-2 text-primary"></i>تغيير كلمة المرور</h4>
    <a href="profile.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-right me-1"></i>الملف الشخصي</a>
</div>

<div class="card form-card" style="max-width:560px;">
    <div class="card-body p-4">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">كلمة المرور الحالية <span class="text-danger">*</span></label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">كلمة المرور الجديدة <span class="text-danger">*</span></label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="mb-4">
                <label class="form-label">تأكيد كلمة المرور الجديدة <span class="text-danger">*</span></label>
                <input type="password" name="new_password2" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key me-1"></i>تحديث كلمة المرور</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
