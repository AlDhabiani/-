<?php
/**
 * ============================================================
 *  الملف الشخصي — وكيل السياحة — agent/profile.php
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['agent']);
$page_title = 'ملفي الشخصي';
$active = 'profile';

$uid = (int)$user['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $uid);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

$errors = [];
$old = [
    'full_name'           => $profile['full_name'],
    'email'               => $profile['email'],
    'phone'               => $profile['phone'] ?? '',
    'bio'                 => $profile['bio'] ?? '',
    'years_of_experience' => $profile['years_of_experience'] ?? 0,
    'license_number'      => $profile['license_number'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['full_name']           = trim((string)($_POST['full_name'] ?? ''));
    $old['email']               = trim((string)($_POST['email'] ?? ''));
    $old['phone']               = trim((string)($_POST['phone'] ?? ''));
    $old['bio']                 = trim((string)($_POST['bio'] ?? ''));
    $old['years_of_experience'] = (int)($_POST['years_of_experience'] ?? 0);
    $old['license_number']      = trim((string)($_POST['license_number'] ?? ''));

    if (mb_strlen($old['full_name'], 'UTF-8') < 3) $errors[] = 'الاسم الكامل مطلوب.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صالح.';

    if (count($errors) === 0) {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
        $chk->bind_param('si', $old['email'], $uid);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $errors[] = 'البريد الإلكتروني مسجل لحساب آخر.';
        }
    }

    /* الصورة الشخصية */
    if (count($errors) === 0 && !empty($_FILES['profile_image']['name'])) {
        $up = upload_image($_FILES['profile_image'], 'agents', 3);
        if (!$up['ok']) {
            $errors[] = 'الصورة الشخصية: ' . $up['error'];
        } else {
            $profile['profile_image'] = $up['file'];
        }
    }

    /* صورة الترخيص الجديدة */
    if (count($errors) === 0 && !empty($_FILES['license_image']['name'])) {
        $up = upload_image($_FILES['license_image'], 'agents', 5);
        if (!$up['ok']) {
            $errors[] = 'صورة الترخيص: ' . $up['error'];
        } else {
            $profile['license_image'] = $up['file'];
        }
    }

    if (count($errors) === 0) {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, bio=?, years_of_experience=?, license_number=?, profile_image=?, license_image=? WHERE id=?");
        $stmt->bind_param('ssssisssi',
            $old['full_name'], $old['email'], $old['phone'], $old['bio'],
            $old['years_of_experience'], $old['license_number'],
            $profile['profile_image'], $profile['license_image'], $uid);
        if ($stmt->execute()) {
            /* تحديث الجلسة */
            $_SESSION['full_name']     = $old['full_name'];
            $_SESSION['profile_image'] = $profile['profile_image'];
            flash_set('تم حفظ الملف الشخصي بنجاح.', 'success');
            redirect('profile.php');
        }
        $errors[] = 'تعذر حفظ التعديلات.';
    }
}

require __DIR__ . '/../includes/sidebar_agent.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-user me-2 text-primary"></i>ملفي الشخصي</h4>
    <a href="change_password.php" class="btn btn-outline-secondary"><i class="fa-solid fa-key me-1"></i>تغيير كلمة المرور</a>
</div>

<div class="card form-card">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded" style="background:#f6f8f8;">
            <?= e(user_avatar($profile, 64)) ?>
            <div>
                <div class="fw-bold"><?= e($profile['full_name']) ?></div>
                <div class="text-muted small">@<?= e($profile['username']) ?> — <?= e(getRoleLabel($profile['role'])) ?> <?= user_badge($profile['status']) ?></div>
                <div class="text-muted small">عضو منذ <?= e(format_date($profile['created_at'])) ?></div>
            </div>
        </div>

        <?php if (count($errors) > 0): ?>
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-4"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($old['full_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($old['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="tel" name="phone" class="form-control" value="<?= e($old['phone']) ?>" dir="ltr" style="text-align:right;">
                </div>
                <div class="col-md-3">
                    <label class="form-label">سنوات الخبرة</label>
                    <input type="number" name="years_of_experience" min="0" max="100" class="form-control" value="<?= e($old['years_of_experience']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">رقم الترخيص</label>
                    <input type="text" name="license_number" class="form-control" value="<?= e($old['license_number']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">نبذة عن المكتب</label>
                    <textarea name="bio" rows="3" class="form-control" placeholder="خبرتك، تخصصك، أبرز وجهاتك..."><?= e($old['bio']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">الصورة الشخصية</label>
                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                    <div class="form-text">JPG / PNG / WEBP / GIF — بحد أقصى 3MB</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">صورة الترخيص (استبدال)</label>
                    <input type="file" name="license_image" class="form-control" accept="image/*">
                    <div class="form-text">اتركها فارغة للإبقاء على الحالية</div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4"><i class="fa-solid fa-floppy-disk me-1"></i>حفظ الملف الشخصي</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
