<?php
/**
 * ============================================================
 *  القائمة الجانبية — لوحة تحكم المدير (البداية)
 *  المتطلبات: $user, $page_title, $active
 * ============================================================
 */
if (!isset($user)) {
    $user = current_user();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title ?? 'لوحة التحكم') ?> | لوحة <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="dash-body">

<div class="dash-topbar d-lg-none">
    <button class="btn btn-link text-white p-1" id="sidebarToggle" aria-label="القائمة">
        <i class="fa-solid fa-bars fs-4"></i>
    </button>
    <span class="brand-text fs-5"><?= e(SITE_NAME) ?> <span class="text-gold">| لوحة المدير</span></span>
</div>

<div class="dash-wrap">
    <aside class="dash-sidebar" id="dashSidebar">
        <div class="sidebar-brand d-flex align-items-center">
            <span class="brand-icon"><i class="fa-solid fa-umbrella-beach"></i></span>
            <span class="brand-text fs-5"><?= e(SITE_NAME) ?></span>
        </div>

        <div class="sidebar-user d-flex align-items-center">
            <?= e(user_avatar($user, 46)) ?>
            <div class="ms-2 overflow-hidden">
                <div class="sidebar-user-name"><?= e($user['full_name']) ?></div>
                <div class="sidebar-user-role"><i class="fa-solid fa-crown me-1 text-gold"></i><?= e(getRoleLabel($user['role'])) ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link<?= $active === 'dashboard' ? ' active' : '' ?>"><i class="fa-solid fa-gauge-high"></i>لوحة التحكم</a>
            <a href="users.php" class="nav-link<?= $active === 'users' ? ' active' : '' ?>"><i class="fa-solid fa-users"></i>المستخدمون</a>
            <a href="approve_agents.php" class="nav-link<?= $active === 'agents' ? ' active' : '' ?>"><i class="fa-solid fa-building-user"></i>مكاتب السياحة</a>
            <a href="packages.php" class="nav-link<?= $active === 'packages' ? ' active' : '' ?>"><i class="fa-solid fa-box-open"></i>الباقات السياحية</a>
            <a href="bookings.php" class="nav-link<?= $active === 'bookings' ? ' active' : '' ?>"><i class="fa-solid fa-ticket"></i>الحجوزات</a>
            <a href="destinations.php" class="nav-link<?= $active === 'destinations' ? ' active' : '' ?>"><i class="fa-solid fa-map-location-dot"></i>الوجهات</a>
            <hr class="sidebar-divider">
            <a href="../index.php" target="_blank" class="nav-link"><i class="fa-solid fa-arrow-up-right-from-square"></i>عرض الموقع</a>
            <a href="../logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i>تسجيل الخروج</a>
        </nav>
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <main class="dash-content">
        <?php echo flash_render(); ?>
