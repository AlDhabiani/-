<?php
/**
 * ============================================================
 *  تسجيل مستخدم جديد — register.php
 *  (عميل → يُفعَّل فوراً / وكيل سياحة → بانتظار موافقة الإدارة)
 * ============================================================
 */
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$role = 'client';
$old  = ['full_name' => '', 'username' => '', 'email' => '', 'phone' => '',
         'bio' => '', 'years_of_experience' => '', 'license_number' => '',
         'agree_to_terms' => false];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['full_name']           = trim((string)($_POST['full_name'] ?? ''));
    $old['username']            = trim((string)($_POST['username'] ?? ''));
    $old['email']               = trim((string)($_POST['email'] ?? ''));
    $old['phone']               = trim((string)($_POST['phone'] ?? ''));
    $old['bio']                 = trim((string)($_POST['bio'] ?? ''));
    $old['years_of_experience'] = trim((string)($_POST['years_of_experience'] ?? ''));
    $old['license_number']      = trim((string)($_POST['license_number'] ?? ''));
    $old['agree_to_terms']      = !empty($_POST['agree_to_terms']);
    $role                        = (string)($_POST['role'] ?? 'client');
    $password                    = (string)($_POST['password'] ?? '');
    $password2                   = (string)($_POST['password2'] ?? '');

    if (!in_array($role, ['client', 'agent'], true)) {
        $role = 'client';
    }

    /* ---------- التحقق من الحقول ---------- */
    if (mb_strlen($old['full_name'], 'UTF-8') < 3) {
        $errors[] = 'الاسم الكامل مطلوب (3 أحرف على الأقل).';
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $old['username'])) {
        $errors[] = 'اسم المستخدم: 3-30 حرفاً إنجليزياً أو أرقام فقط (بدون مسافات).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح.';
    }
    if ($password === '' || strlen($password) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
    }
    if ($password !== $password2) {
        $errors[] = 'كلمتا المرور غير متطابقتين.';
    }
    if ($role === 'agent') {
        if ($old['license_number'] === '') {
            $errors[] = 'رقم الترخيص مطلوب لوكلاء السياحة.';
        }
        if (!empty($_FILES['license_image']['name']) || !empty($_FILES['id_image']['name'])) {
            // will be validated below via upload
        }
        if (empty($_FILES['license_image']['name']) && empty($_FILES['id_image']['name'])) {
            $errors[] = 'رفع صورة الترخيص وصورة الهوية مطلوبان لوكلاء السياحة.';
        }
        if (!$old['agree_to_terms']) {
            $errors[] = 'يجب الموافقة على الشروط والأحكام.';
        }
    }

    /* ---------- التكرار ---------- */
    if (count($errors) === 0) {
        $chk = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param('ss', $old['username'], $old['email']);
        $chk->execute();
        $dupe = $chk->get_result()->fetch_assoc();
        if ($dupe) {
            if ($dupe['username'] === $old['username']) {
                $errors[] = 'اسم المستخدم محجوز مسبقاً.';
            }
            if ($dupe['email'] === $old['email']) {
                $errors[] = 'البريد الإلكتروني مسجل مسبقاً.';
            }
        }
    }

    /* ---------- رفع وثائق الوكيل ---------- */
    $id_image = '';
    $license_image = '';
    if (count($errors) === 0 && $role === 'agent') {
        $up_id = upload_image($_FILES['id_image'] ?? null, 'agents', 5);
        if (!$up_id['ok']) {
            $errors[] = 'صورة الهوية: ' . $up_id['error'];
        } else {
            $id_image = $up_id['file'];
        }

        $up_lic = upload_image($_FILES['license_image'] ?? null, 'agents', 5);
        if (!$up_lic['ok']) {
            if ($id_image) delete_upload('agents', $id_image);
            $errors[] = 'صورة الترخيص: ' . $up_lic['error'];
        } else {
            $license_image = $up_lic['file'];
        }
    }

    /* ---------- الحفظ ---------- */
    if (count($errors) === 0) {
        $status  = $role === 'agent' ? 'pending' : 'active';
        $hash    = password_hash($password, PASSWORD_BCRYPT);
        $years   = (int)$old['years_of_experience'];
        $agree   = (int)$old['agree_to_terms'];

        $stmt = $conn->prepare("INSERT INTO users
            (username, password, role, full_name, email, phone, bio, profile_image, years_of_experience, license_number, license_image, id_image, status, agree_to_terms)
            VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssssissssi',
            $old['username'], $hash, $role, $old['full_name'], $old['email'], $old['phone'],
            $old['bio'], $years, $old['license_number'], $license_image, $id_image, $status, $agree);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            if ($role === 'agent') {
                // وكيل: رسالة انتظار الموافقة (بدون تسجيل دخول تلقائي)
                $agent_done = true;
                $agent_name = $old['full_name'];
            } else {
                flash_set('تم إنشاء حسابك بنجاح — يمكنك تسجيل الدخول الآن.', 'success');
                redirect('login.php');
            }
        } else {
            $errors[] = 'تعذر حفظ البيانات — يرجى المحاولة مرة أخرى.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إنشاء حساب جديد | <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-shell">
    <div class="container" style="max-width:640px;">
        <div class="text-center text-white mb-4">
            <span class="brand-icon mx-auto mb-2" style="background:rgba(255,255,255,.15);"><i class="fa-solid fa-umbrella-beach"></i></span>
            <h1 class="fw-bold mb-1"><?= e(SITE_NAME) ?></h1>
            <p class="opacity-75 mb-0">انضم إلينا — استغرق أقل من دقيقة</p>
        </div>

        <div class="card form-card">
            <div class="card-body p-4 p-lg-5">

                <?php if (!empty($agent_done)): ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-circle-check fa-4x text-success mb-3"></i>
                        <h4 class="fw-bold">تم استلام طلبك، <?= e($agent_name) ?>!</h4>
                        <p class="text-secondary">
                            تم تسجيل مكتبك (وكيل سياحة) وسيتم مراجعة وثائقك من قِبل الإدارة.
                            <br>سيتاح لك تسجيل الدخول فور اعتماد حسابك.
                        </p>
                        <a href="login.php" class="btn btn-primary mt-2"><i class="fa-solid fa-right-to-bracket me-1"></i>تسجيل الدخول</a>
                    </div>
                <?php else: ?>
                    <h4 class="fw-bold mb-4"><i class="fa-solid fa-user-plus me-2 text-primary"></i>إنشاء حساب جديد</h4>

                    <?php if (count($errors) > 0): ?>
                        <div class="alert alert-danger py-2">
                            <div class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-2"></i>يرجى تصحيح الأخطاء التالية:</div>
                            <ul class="mb-0 ps-4 small">
                                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" novalidate>
                        <?= csrf_field() ?>

                        <!-- اختيار الدور -->
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="role_client" value="client" <?= ($role ?? 'client') === 'client' ? 'checked' : '' ?> onchange="toggleRole()">
                                <label class="btn w-100 text-start" for="role_client">
                                    <i class="fa-solid fa-user me-2 text-primary"></i>
                                    <span class="d-block fw-bold small">عميل</span>
                                    <small class="text-secondary d-block">أريد حجز رحلات</small>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="role_agent" value="agent" <?= ($role ?? 'client') === 'agent' ? 'checked' : '' ?> onchange="toggleRole()">
                                <label class="btn w-100 text-start" for="role_agent">
                                    <i class="fa-solid fa-building-user me-2 text-primary"></i>
                                    <span class="d-block fw-bold small">وكيل سياحة</span>
                                    <small class="text-secondary d-block">أدير مكتب سياحة</small>
                                </label>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="full_name">الاسم الكامل <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="username">اسم المستخدم <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= e($old['username']) ?>" pattern="[a-zA-Z0-9_]{3,30}" required>
                                <div class="form-text">حروف إنجليزية وأرقام فقط (3-30)</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">البريد الإلكتروني <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($old['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">رقم الهاتف</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($old['phone']) ?>" dir="ltr" style="text-align:right;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">كلمة المرور <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password2">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password2" name="password2" required minlength="6">
                            </div>
                        </div>

                        <!-- حقول وكيل السياحة -->
                        <div id="agentFields" class="mt-4 <?= ($role ?? 'client') === 'agent' ? '' : 'd-none' ?>" style="border:1px dashed #cfd8d6; border-radius:14px; padding:1.1rem; background:#f8faf9;">
                            <h6 class="fw-bold"><i class="fa-solid fa-building-user me-1 text-primary"></i>بيانات مكتب السياحة (لإثبات الترخيص)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="license_number">رقم الترخيص <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="license_number" name="license_number" value="<?= e($old['license_number']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="years">سنوات الخبرة</label>
                                    <input type="number" min="0" max="100" class="form-control" id="years" name="years_of_experience" value="<?= e($old['years_of_experience']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="bio">نبذة عن المكتب</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="خبرتك، تخصصك، أبرز وجهاتك..."><?= e($old['bio']) ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="id_image">صورة الهوية الشخصية <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="id_image" name="id_image" accept="image/*">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="license_image">صورة الترخيص <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="license_image" name="license_image" accept="image/*">
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="agree" name="agree_to_terms" value="1" <?= $old['agree_to_terms'] ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="agree">
                                            أوافق على <a href="#" class="text-primary">شروط الاستخدام</a> وأتعهد بصحة الوثائق المقدمة <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 btn-lg mt-4">
                            <i class="fa-solid fa-user-plus me-2"></i>إنشاء الحساب
                        </button>
                    </form>

                    <hr>
                    <p class="text-center text-secondary mb-0">
                        لديك حساب بالفعل؟ <a href="login.php" class="fw-bold text-primary">سجّل الدخول</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="index.php" class="text-white-50 small"><i class="fa-solid fa-arrow-right me-1"></i>العودة للرئيسية</a>
        </div>
    </div>
</div>

<script>
function toggleRole() {
    var isAgent = document.getElementById('role_agent').checked;
    var fields = document.getElementById('agentFields');
    fields.classList.toggle('d-none', !isAgent);
    ['license_number','years_of_experience','bio','id_image','license_image','agree_to_terms'].forEach(function(n){
        var el = document.querySelector('[name="' + n + '"]');
        if (el) el.disabled = !isAgent;
    });
}
toggleRole();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
