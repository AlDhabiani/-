<?php
/**
 * ============================================================
 *  تسجيل الدخول — login.php
 * ============================================================
 */
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* المستخدم المسجل مسبقاً → مباشرة للوحة */
if (isset($_SESSION['user_id'])) {
    $dash = ['admin' => 'admin/dashboard.php', 'agent' => 'agent/dashboard.php', 'client' => 'client/dashboard.php'][$_SESSION['role']] ?? 'index.php';
    redirect($dash);
}

$error = '';
$redirect_param = (string)($_GET['redirect'] ?? $_POST['redirect'] ?? '');

/* قبول روابط نسبية فقط (حماية من open redirect) */
function safe_redirect($url, $fallback)
{
    if ($url !== ''
        && preg_match('#^[\w\-./]+\.php(\?.*)?$#', $url)
        && strpos($url, '..') === false
        && strpos($url, '://') === false) {
        return $url;
    }
    return $fallback;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $login    = trim((string)($_POST['login'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $error = 'يرجى إدخال اسم المستخدم (أو البريد) وكلمة المرور.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
        } elseif ($user['status'] === 'suspended') {
            $error = 'تم إيقاف حسابك — يرجى التواصل مع إدارة المنصة.';
        } elseif ($user['status'] === 'pending') {
            $error = 'حسابك (وكيل سياحة) بانتظار موافقة الإدارة. يمكنك تسجيل الدخول بعد اعتمادك.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']      = (int)$user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['full_name']    = $user['full_name'];
            $_SESSION['profile_image']= $user['profile_image'];

            $fallback = ['admin' => 'admin/dashboard.php', 'agent' => 'agent/dashboard.php', 'client' => 'client/dashboard.php'][$user['role']] ?? 'index.php';
            redirect(safe_redirect($redirect_param, $fallback));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول | <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-shell">
    <div class="container" style="max-width:460px;">
        <div class="text-center text-white mb-4">
            <span class="brand-icon mx-auto mb-2" style="background:rgba(255,255,255,.15);"><i class="fa-solid fa-umbrella-beach"></i></span>
            <h1 class="fw-bold mb-1"><?= e(SITE_NAME) ?></h1>
            <p class="opacity-75 mb-0">سجّل دخولك وتابع رحلاتك</p>
        </div>

        <div class="card form-card">
            <div class="card-body p-4 p-lg-5">
                <h4 class="fw-bold mb-4"><i class="fa-solid fa-right-to-bracket me-2 text-primary"></i>تسجيل الدخول</h4>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger py-2"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="login.php" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect" value="<?= e($redirect_param) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="login">اسم المستخدم أو البريد الإلكتروني</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                            <input type="text" class="form-control" id="login" name="login" value="<?= e($_POST['login'] ?? '') ?>" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">كلمة المرور</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>دخول
                    </button>
                </form>

                <hr>
                <p class="text-center text-secondary mb-0">
                    ليس لديك حساب؟ <a href="register.php" class="fw-bold text-primary">أنشئ حساباً جديداً</a>
                </p>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="index.php" class="text-white-50 small"><i class="fa-solid fa-arrow-right me-1"></i>العودة للرئيسية</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
