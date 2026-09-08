<?php
/**
 * ============================================================
 *  الموافقة على مكاتب السياحة — admin/approve_agents.php
 *  مراجعة وثائق الوكلاء الجدد (هوية + ترخيص) ثم: اعتماد / رفض / حذف
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);
$page_title = 'مكاتب السياحة';
$active = 'agents';

$tab = (string)($_GET['tab'] ?? 'pending');
if (!in_array($tab, ['pending', 'active', 'suspended'], true)) $tab = 'pending';

$agents = [];
if ($res = $conn->query("SELECT * FROM users WHERE role = 'agent' AND status = '$tab' ORDER BY created_at DESC")) {
    while ($row = $res->fetch_assoc()) {
        $agents[] = $row;
    }
}

/* عدد الطلبات المعلقة (لشريط التبويب) */
$pending_count = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role = 'agent' AND status = 'pending'")->fetch_assoc()['c'];

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-building-user me-2 text-primary"></i>مكاتب السياحة</h4>
</div>

<!-- التبويبات -->
<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>" href="approve_agents.php?tab=pending">
            بانتظار الموافقة <span class="badge bg-<?= $pending_count > 0 ? 'danger' : 'secondary' ?> ms-1"><?= $pending_count ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'active' ? 'active' : '' ?>" href="approve_agents.php?tab=active">مكاتب معتمدة</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'suspended' ? 'active' : '' ?>" href="approve_agents.php?tab=suspended">مرفوض / موقوف</a>
    </li>
</ul>

<?php if (count($agents) === 0): ?>
    <div class="empty-state">
        <i class="fa-solid fa-inbox fa-3x mb-3 text-primary"></i>
        <h5 class="fw-bold">لا يوجد مكاتب في هذا القسم</h5>
        <p class="mb-0">
            <?php if ($tab === 'pending'): ?>ممتاز — لا توجد طلبات بانتظار المراجعة حالياً.<?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($agents as $a): ?>
            <div class="col-xxl-6">
                <div class="card form-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <?= e(user_avatar($a, 56)) ?>
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1"><?= e($a['full_name']) ?> <?= user_badge($a['status']) ?></h5>
                                <div class="small text-muted mb-2">
                                    <span><i class="fa-regular fa-envelope me-1"></i><?= e($a['email']) ?></span>
                                    <span class="ms-3"><i class="fa-solid fa-phone me-1"></i><span dir="ltr"><?= e($a['phone'] ?: '-') ?></span></span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 small">
                                    <span class="info-chip"><i class="fa-solid fa-id-card"></i>ترخيص: <?= e($a['license_number'] ?: '—') ?></span>
                                    <span class="info-chip"><i class="fa-solid fa-briefcase"></i><?= (int)$a['years_of_experience'] ?> سنة خبرة</span>
                                    <span class="info-chip"><i class="fa-regular fa-calendar"></i>سُجل <?= e(format_date($a['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($a['bio'])): ?>
                            <p class="small text-secondary mt-3 mb-3" style="white-space:pre-line;"><?= e($a['bio']) ?></p>
                        <?php endif; ?>

                        <!-- الوثائق -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="small fw-bold mb-1"><i class="fa-solid fa-id-card me-1"></i>صورة الهوية</div>
                                <?php if (!empty($a['id_image'])): ?>
                                    <a href="uploads/agents/<?= e($a['id_image']) ?>" target="_blank" title="فتح بالحجم الكامل">
                                        <img src="uploads/agents/<?= e($a['id_image']) ?>" alt="الهوية" class="img-fluid rounded border" style="height:110px; object-fit:cover;">
                                    </a>
                                <?php else: ?>
                                    <div class="border rounded d-flex align-items-center justify-content-center text-muted small" style="height:110px;">غير مرفوعة</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-6">
                                <div class="small fw-bold mb-1"><i class="fa-solid fa-certificate me-1"></i>صورة الترخيص</div>
                                <?php if (!empty($a['license_image'])): ?>
                                    <a href="uploads/agents/<?= e($a['license_image']) ?>" target="_blank" title="فتح بالحجم الكامل">
                                        <img src="uploads/agents/<?= e($a['license_image']) ?>" alt="الترخيص" class="img-fluid rounded border" style="height:110px; object-fit:cover;">
                                    </a>
                                <?php else: ?>
                                    <div class="border rounded d-flex align-items-center justify-content-center text-muted small" style="height:110px;">غير مرفوعة</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- الإجراءات -->
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($a['status'] === 'pending'): ?>
                                <form method="post" action="toggle_user.php" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button class="btn btn-success"><i class="fa-solid fa-check me-1"></i>اعتماد المكتب</button>
                                </form>
                            <?php elseif ($a['status'] === 'suspended'): ?>
                                <form method="post" action="toggle_user.php" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button class="btn btn-primary"><i class="fa-solid fa-rotate-left me-1"></i>إعادة التفعيل</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($a['status'] === 'active'): ?>
                                <form method="post" action="toggle_user.php" class="d-inline" onsubmit="return confirm('إيقاف هذا المكتب؟ سيصبح غير قادر على الدخول وإدارة باقاته.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button class="btn btn-outline-warning"><i class="fa-solid fa-ban me-1"></i>إيقاف</button>
                                </form>
                            <?php endif; ?>
                            <a href="edit_user.php?id=<?= (int)$a['id'] ?>" class="btn btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>تعديل</a>
                            <form method="post" action="delete_user.php" class="d-inline" onsubmit="return confirm('حذف مكتب «<?= e(addslashes($a['full_name'])) ?>» نهائياً مع حجوزاته؟');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                <button class="btn btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>حذف</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
