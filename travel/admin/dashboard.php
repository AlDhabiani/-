<?php
/**
 * ============================================================
 *  لوحة تحكم المدير — admin/dashboard.php
 *  إحصائيات عامة: المستخدمون، الوكلاء، الباقات، الحجوزات، الإيرادات
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);
$page_title = 'لوحة تحكم المدير';
$active = 'dashboard';

/* ---------- الإحصائيات ---------- */
$stats = ['users' => 0, 'agents' => 0, 'packages' => 0, 'bookings' => 0, 'revenue' => 0, 'pending_agents' => 0, 'pending_bookings' => 0];
if ($res = $conn->query("SELECT
        (SELECT COUNT(*) FROM users) AS users,
        (SELECT COUNT(*) FROM users WHERE role = 'agent' AND status = 'active') AS agents,
        (SELECT COUNT(*) FROM packages) AS packages,
        (SELECT COUNT(*) FROM bookings) AS bookings,
        (SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status IN ('approved','confirmed','completed')) AS revenue,
        (SELECT COUNT(*) FROM users WHERE role = 'agent' AND status = 'pending') AS pending_agents,
        (SELECT COUNT(*) FROM bookings WHERE status = 'pending') AS pending_bookings")) {
    $stats = $res->fetch_assoc();
}

/* ---------- توزيع حالات الحجوزات (رسم بياني) ---------- */
$status_counts = [];
if ($res = $conn->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status")) {
    while ($row = $res->fetch_assoc()) {
        $status_counts[$row['status']] = (int)$row['c'];
    }
}
$status_order = ['pending', 'approved', 'confirmed', 'completed', 'cancelled'];
$bar_max = max(1, max(array_map(fn($s) => $status_counts[$s] ?? 0, $status_order)));

/* ---------- آخر الحجوزات ---------- */
$latest_bookings = [];
if ($res = $conn->query("SELECT b.*, p.title AS package_title, u.full_name AS client_name, u.profile_image AS client_image
                         FROM bookings b
                         JOIN packages p ON p.id = b.package_id
                         JOIN users u ON u.id = b.client_id
                         ORDER BY b.created_at DESC LIMIT 5")) {
    while ($row = $res->fetch_assoc()) {
        $latest_bookings[] = $row;
    }
}

/* ---------- آخر المستخدمين ---------- */
$latest_users = [];
if ($res = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")) {
    while ($row = $res->fetch_assoc()) {
        $latest_users[] = $row;
    }
}

$pending_ratio = $stats['bookings'] > 0 ? round($stats['pending_bookings'] / $stats['bookings'] * 100) : 0;

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-gauge-high me-2 text-primary"></i>لوحة تحكم المدير</h4>
    <span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i><?= e(format_date(date('Y-m-d'))) ?></span>
</div>

<!-- بطاقات الإحصائيات -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card stat-teal h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['users'] ?></div>
                    <div class="stat-label">المستخدمون</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card stat-indigo h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-building-user"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['agents'] ?></div>
                    <div class="stat-label">مكاتب سياحة نشطة</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card stat-gold h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['packages'] ?></div>
                    <div class="stat-label">الباقات السياحية</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card stat-sky h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-ticket"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['bookings'] ?></div>
                    <div class="stat-label">إجمالي الحجوزات</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card stat-rose h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div>
                    <div class="stat-value" style="font-size:1.15rem;"><?= e(format_price($stats['revenue'])) ?></div>
                    <div class="stat-label">إجمالي الإيرادات</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card stat-card stat-violet h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$stats['pending_bookings'] ?></div>
                    <div class="stat-label">حجوزات معلقة</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- شريط تقدم الحجوزات المعلقة -->
    <div class="col-lg-5">
        <div class="card table-card h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-simple me-1 text-primary"></i>نسبة الحجوزات المعلقة</h6>
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">بانتظار المعالجة</span>
                    <span class="fw-bold"><?= (int)$stats['pending_bookings'] ?> من <?= (int)$stats['bookings'] ?></span>
                </div>
                <div class="progress" style="height:14px;">
                    <div class="progress-bar" role="progressbar" style="width:<?= (int)$pending_ratio ?>%; background:linear-gradient(90deg,var(--gold),var(--gold-dark));" aria-valuenow="<?= (int)$pending_ratio ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted mt-2 d-block"><?= (int)$pending_ratio ?>% من إجمالي الحجوزات بانتظار المعالجة</small>

                <hr>
                <?php if ((int)$stats['pending_agents'] > 0): ?>
                    <div class="alert alert-warning d-flex align-items-center justify-content-between py-2 mb-0">
                        <span><i class="fa-solid fa-building-user me-2"></i><?= (int)$stats['pending_agents'] ?> مكتب سياحة بانتظار الموافقة</span>
                        <a href="approve_agents.php" class="btn btn-sm btn-warning">مراجعة</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success py-2 mb-0"><i class="fa-solid fa-circle-check me-2"></i>لا توجد طلبات مكاتب بانتظار الموافقة.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- رسم بياني بحالات الحجوزات -->
    <div class="col-lg-7">
        <div class="card table-card h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-column me-1 text-primary"></i>الحجوزات حسب الحالة</h6>
                <div class="bar-chart">
                    <?php foreach ($status_order as $s): ?>
                        <?php $c = $status_counts[$s] ?? 0; $w = (int)round($c / $bar_max * 100); ?>
                        <div class="bar-row">
                            <span class="bar-label"><?= e(getBookingStatus($s)) ?></span>
                            <div class="bar-track"><div class="bar-fill" style="width:<?= max($w, 3) ?>%;"></div></div>
                            <span class="bar-value"><?= $c ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- آخر الحجوزات -->
    <div class="col-lg-7">
        <div class="card table-card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i>آخر 5 حجوزات</h6>
                    <a href="bookings.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>العميل</th><th>الباقة</th><th>الإجمالي</th><th>الحالة</th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($latest_bookings) > 0): ?>
                                <?php foreach ($latest_bookings as $b): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?= e(user_avatar($b, 34)) ?>
                                                <span><?= e($b['client_name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="small"><?= e($b['package_title']) ?></td>
                                        <td class="fw-bold small"><?= e(format_price($b['total_price'])) ?></td>
                                        <td><?= booking_badge($b['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">لا توجد حجوزات بعد</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- آخر المستخدمين -->
    <div class="col-lg-5">
        <div class="card table-card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-user-plus me-1 text-primary"></i>آخر 5 مستخدمين</h6>
                    <a href="users.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>المستخدم</th><th>الدور</th><th>الحالة</th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($latest_users) > 0): ?>
                                <?php foreach ($latest_users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?= e(user_avatar($u, 34)) ?>
                                                <div>
                                                    <div class="fw-bold small"><?= e($u['full_name']) ?></div>
                                                    <div class="text-muted" style="font-size:.75rem;">@<?= e($u['username']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= e(getRoleLabel($u['role'])) ?></span></td>
                                        <td><?= user_badge($u['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">لا يوجد مستخدمون</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
