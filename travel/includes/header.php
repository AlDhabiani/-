<?php
/**
 * ============================================================
 *  ترويسة الموقع العامة — includes/header.php
 * ============================================================
 *  تُشغّل الجلسة، تفتح الصفحة، وتعرض شريط التنقل والرسائل.
 *  يمكن تمرير $page_title قبل الاستدعاء.
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/functions.php';

$page_title = $page_title ?? SITE_NAME;
$is_logged  = isset($_SESSION['user_id']);
$role       = $_SESSION['role'] ?? '';
$role_dashboard = [
    'admin'  => '../admin/dashboard.php',
    'agent'  => '../agent/dashboard.php',
    'client' => '../client/dashboard.php',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($page_title) ?> — منصة <?= e(SITE_NAME) ?> لحجز الرحلات السياحية">
    <title><?= e($page_title) ?> | <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg site-navbar sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <span class="brand-icon"><i class="fa-solid fa-umbrella-beach"></i></span>
            <span class="brand-text"><?= e(SITE_NAME) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-house me-1"></i>الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link" href="packages.php"><i class="fa-solid fa-compass me-1"></i>الباقات السياحية</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#features"><i class="fa-solid fa-star me-1"></i>لماذا نحن؟</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#about"><i class="fa-solid fa-circle-info me-1"></i>من نحن</a></li>
            </ul>
            <ul class="navbar-nav">
                <?php if ($is_logged): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <?= e(user_avatar(['full_name' => $_SESSION['full_name'] ?? '', 'profile_image' => $_SESSION['profile_image'] ?? ''], 28)) ?>
                            <span class="ms-2"><?= e($_SESSION['full_name'] ?? '') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($role === 'client'): ?>
                                <li><a class="dropdown-item" href="client/dashboard.php"><i class="fa-solid fa-gauge me-2"></i>لوحة التحكم</a></li>
                                <li><a class="dropdown-item" href="client/my_bookings.php"><i class="fa-solid fa-ticket me-2"></i>حجوزاتي</a></li>
                                <li><a class="dropdown-item" href="client/profile.php"><i class="fa-solid fa-user me-2"></i>ملفي الشخصي</a></li>
                            <?php elseif ($role === 'agent'): ?>
                                <li><a class="dropdown-item" href="agent/dashboard.php"><i class="fa-solid fa-gauge me-2"></i>لوحة التحكم</a></li>
                                <li><a class="dropdown-item" href="agent/my_packages.php"><i class="fa-solid fa-box-open me-2"></i>باقاتي</a></li>
                                <li><a class="dropdown-item" href="agent/profile.php"><i class="fa-solid fa-user me-2"></i>ملفي الشخصي</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fa-solid fa-gauge me-2"></i>لوحة التحكم</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>تسجيل الخروج</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="fa-solid fa-right-to-bracket me-1"></i>تسجيل الدخول</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-gold btn-sm px-3" href="register.php"><i class="fa-solid fa-user-plus me-1"></i>سجّل الآن</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="site-main">
<?php echo flash_render(); ?>
