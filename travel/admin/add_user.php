<?php
/**
 * ============================================================
 *  إضافة مستخدم — admin/add_user.php
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);
$page_title = 'إضافة مستخدم';
$active = 'users';

$errors = [];
$old = ['full_name' => '', 'username' => '', 'email' => '', 'phone' => '', 'role' => 'client',
        'status' => 'active', 'years_of_experience' => '', 'license_number' => '', 'bio' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['full_name']           = trim((string)($_POST['full_name'] ?? ''));
    $old['username']            = trim((string)($_POST['username'] ?? ''));
    $old['email']               = trim((string)($_POST['email'] ?? ''));
    $old['phone']               = trim((string)($_POST['phone'] ?? ''));
    $old['role']                = (string)($_POST['role'] ?? 'client');
    $old['status']              = (string)($_POST['status'] ?? 'active');
    $old['years_of_experience'] = (int)($_POST['years_of_experience'] ?? 0);
    $old['license_number']      = trim((string)($_POST['license_number'] ?? ''));
    $old['bio']                 = trim((string)($_POST['bio'] ?? ''));
    $password                   = (string)($_POST['password'] ?? '');

    if (!in_array($old['role'], ['admin', 'agent', 'client'], true)) $old['role'] = 'client';
    if (!in_array($old['status'], ['active', 'pending', 'suspended'], true)) $old['status'] = 'active';

    if (mb_strlen($old['full_name'], 'UTF-8') < 3) $errors[] = 'الاسم الكامل مطلوب (3 أحرف على الأقل).';
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $old['username'])) $errors[] = 'اسم المستخدم: 3-30 حرفاً إنجليزياً أو أرقام.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صالح.';
    if (strlen($password) < 6) $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';

    if (count($errors) === 0) {
        $chk = $conn->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param('ss', $old['username'], $old['email']);
        $chk->execute();
        $dupe = $chk->get_result()->fetch_assoc();
        if ($dupe) {
            if ($dupe['username'] === $old['username']) $errors[] = 'اسم المستخدم محجوز.';
            if ($dupe['email'] === $old['email']) $errors[] = 'البريد مسجل مسبقاً.';
        }
    }

    if (count($errors) === 0) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email, phone, bio, years_of_experience, license_number, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssssiis', $old['username'], $hash, $old['role'], $old['full_name'], $old['email'],
            $old['phone'], $old['bio'], $old['years_of_experience'], $old['license_number'], $old['status']);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            flash_set('تمت إضافة المستخدم «' . $old['full_name'] . '» بنجاح.', 'success');
            redirect('users.php');
        }
        $errors[] = 'تعذر حفظ المستخدم.';
    }
}

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-user-plus me-2 text-primary"></i>إضافة مستخدم جديد</h4>
    <a href="users.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-right me-1"></i>عودة</a>
</div>

<div class="card form-card">
    <div class="card-body p-4">
        <?php if (count($errors) > 0): ?>
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-4"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($old['full_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="<?= e($old['username']) ?>" pattern="[a-zA-Z0-9_]{3,30}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($old['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="tel" name="phone" class="form-control" value="<?= e($old['phone']) ?>" dir="ltr" style="text-align:right;">
                </div>
                <div class="col-md-6">
                    <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <div class="form-text">تُشفَّر تلقائياً (bcrypt)</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الدور</label>
                    <select name="role" class="form-select" id="roleSelect">
                        <option value="client" <?= $old['role'] === 'client' ? 'selected' : '' ?>>عميل</option>
                        <option value="agent" <?= $old['role'] === 'agent' ? 'selected' : '' ?>>وكيل سياحة</option>
                        <option value="admin" <?= $old['role'] === 'admin' ? 'selected' : '' ?>>مدير</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $old['status'] === 'active' ? 'selected' : '' ?>>نشط</option>
                        <option value="pending" <?= $old['status'] === 'pending' ? 'selected' : '' ?>>بانتظار الموافقة</option>
                        <option value="suspended" <?= $old['status'] === 'suspended' ? 'selected' : '' ?>>موقوف</option>
                    </select>
                </div>
                <div class="col-md-4 agent-extra d-none">
                    <label class="form-label">سنوات الخبرة</label>
                    <input type="number" name="years_of_experience" min="0" max="100" class="form-control" value="<?= e($old['years_of_experience']) ?>">
                </div>
                <div class="col-md-4 agent-extra d-none">
                    <label class="form-label">رقم الترخيص</label>
                    <input type="text" name="license_number" class="form-control" value="<?= e($old['license_number']) ?>">
                </div>
                <div class="col-12 agent-extra d-none">
                    <label class="form-label">نبذة عن المكتب</label>
                    <textarea name="bio" rows="2" class="form-control"><?= e($old['bio']) ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4"><i class="fa-solid fa-plus me-1"></i>حفظ المستخدم</button>
        </form>
    </div>
</div>

<script>
(function () {
    var sel = document.getElementById('roleSelect');
    function sync() {
        var isAgent = sel.value === 'agent';
        document.querySelectorAll('.agent-extra').forEach(function (el) { el.classList.toggle('d-none', !isAgent); });
    }
    sel.addEventListener('change', sync);
    sync();
})();
</script>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
