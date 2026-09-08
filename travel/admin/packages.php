<?php
/**
 * ============================================================
 *  إدارة الباقات السياحية — admin/packages.php
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);
$page_title = 'إدارة الباقات';
$active = 'packages';

$f_q      = trim((string)($_GET['q'] ?? ''));
$f_status = (string)($_GET['status'] ?? '');
if (!in_array($f_status, ['available', 'reserved', 'cancelled'], true)) $f_status = '';

$where  = [];
$params = [];
$types  = '';
if ($f_q !== '') {
    $where[] = '(p.title LIKE ? OR p.destination LIKE ? OR u.full_name LIKE ?)';
    $like = '%' . $f_q . '%';
    array_push($params, $like, $like, $like);
    $types .= 'sss';
}
if ($f_status !== '') {
    $where[] = 'p.status = ?';
    $params[] = $f_status;
    $types .= 's';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT p.*, u.full_name AS agent_name
        FROM packages p
        LEFT JOIN users u ON u.id = p.agent_id
        $where_sql
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$packages = $stmt->get_result();

/* عدّاد سريع */
$total_count = (int)$conn->query("SELECT COUNT(*) c FROM packages")->fetch_assoc()['c'];

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-box-open me-2 text-primary"></i>إدارة الباقات السياحية <span class="badge bg-primary ms-1"><?= $total_count ?></span></h4>
    <a href="add_package.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>إضافة باقة</a>
</div>

<div class="card table-card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="q" value="<?= e($f_q) ?>" class="form-control" placeholder="بحث بالعنوان / الوجهة / الوكيل...">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    <option value="available" <?= $f_status === 'available' ? 'selected' : '' ?>>متاحة</option>
                    <option value="reserved" <?= $f_status === 'reserved' ? 'selected' : '' ?>>محجوزة</option>
                    <option value="cancelled" <?= $f_status === 'cancelled' ? 'selected' : '' ?>>ملغاة</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i>فلترة</button>
            </div>
        </form>
    </div>
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
                        <th>الوكيل</th>
                        <th>الحالة</th>
                        <th>مشاهدات</th>
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
                                    <div class="text-muted small"><?= (int)$p['duration'] ?> أيام — أضيفت <?= e(format_date($p['created_at'])) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= e($p['destination']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e(getPackageType($p['type'])) ?></span></td>
                        <td class="fw-bold"><?= e(format_price($p['price'])) ?></td>
                        <td class="small"><?= e($p['agent_name'] ?: '—') ?></td>
                        <td><?= package_badge($p['status']) ?></td>
                        <td class="text-muted small"><?= (int)$p['views'] ?></td>
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
                    <tr><td colspan="9" class="text-center text-muted py-5">لا توجد باقات</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
