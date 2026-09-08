<?php
/**
 * ============================================================
 *  باقاتي — agent/my_packages.php
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['agent']);
$page_title = 'باقاتي';
$active = 'packages';

$uid = (int)$user['id'];
$stmt = $conn->prepare("SELECT * FROM packages WHERE agent_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$packages = $stmt->get_result();

require __DIR__ . '/../includes/sidebar_agent.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-box-open me-2 text-primary"></i>باقاتي السياحية</h4>
    <a href="add_package.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>إضافة باقة</a>
</div>

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الباقة</th>
                        <th>الوجهة</th>
                        <th>النوع</th>
                        <th>السعر</th>
                        <th>الحالة</th>
                        <th>مشاهدات</th>
                        <th>تاريخ الإضافة</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 0; while ($p = $packages->fetch_assoc()): $i++; ?>
                    <tr>
                        <td class="text-muted"><?= $i ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= !empty($p['image']) ? 'uploads/packages/' . e($p['image']) : 'assets/img/placeholder.svg' ?>" class="thumb" alt="">
                                <div>
                                    <div class="fw-bold"><?= e($p['title']) ?></div>
                                    <div class="text-muted small"><?= (int)$p['duration'] ?> أيام</div>
                                </div>
                            </div>
                        </td>
                        <td><?= e($p['destination']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e(getPackageType($p['type'])) ?></span></td>
                        <td class="fw-bold"><?= e(format_price($p['price'])) ?></td>
                        <td><?= package_badge($p['status']) ?></td>
                        <td class="text-muted small"><?= (int)$p['views'] ?></td>
                        <td class="small text-muted"><?= e(format_date($p['created_at'])) ?></td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="../package_details.php?id=<?= (int)$p['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary action-btn" title="عرض"><i class="fa-regular fa-eye"></i></a>
                                <a href="edit_package.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary action-btn" title="تعديل"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" action="delete_package.php" class="d-inline" onsubmit="return confirm('حذف الباقة «<?= e(addslashes($p['title'])) ?>»؟ ستُحذف حجوزاتها المرتبطة أيضاً.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger action-btn" title="حذف"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($i === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">
                        <div>
                            <i class="fa-solid fa-box-open fa-2x mb-2 d-block"></i>
                            لا توجد باقات بعد — <a href="add_package.php" class="text-primary fw-bold">أضف باقتك الأولى</a>
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
