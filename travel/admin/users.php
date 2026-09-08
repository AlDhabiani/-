<?php
/**
 * ============================================================
 *  إدارة المستخدمين — admin/users.php
 *  جدول كامل مع فلترة وبحث + إجراءات (تعديل / تعطيل / حذف)
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);
$page_title = 'إدارة المستخدمين';
$active = 'users';

/* ---------- الفلاتر ---------- */
$f_role  = (string)($_GET['role'] ?? '');
$f_q     = trim((string)($_GET['q'] ?? ''));
if (!in_array($f_role, ['admin', 'agent', 'client'], true)) $f_role = '';

$where  = [];
$params = [];
$types  = '';
if ($f_role !== '') {
    $where[] = 'u.role = ?';
    $params[] = $f_role;
    $types .= 's';
}
if ($f_q !== '') {
    $where[] = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    $like = '%' . $f_q . '%';
    array_push($params, $like, $like, $like);
    $types .= 'sss';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT u.* FROM users u $where_sql ORDER BY u.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-users me-2 text-primary"></i>إدارة المستخدمين</h4>
    <a href="add_user.php" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i>إضافة مستخدم</a>
</div>

<!-- الفلترة -->
<div class="card table-card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="q" value="<?= e($f_q) ?>" class="form-control" placeholder="بحث بالاسم / المستخدم / البريد...">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">كل الأدوار</option>
                    <option value="admin" <?= $f_role === 'admin' ? 'selected' : '' ?>>مدير</option>
                    <option value="agent" <?= $f_role === 'agent' ? 'selected' : '' ?>>وكيل سياحة</option>
                    <option value="client" <?= $f_role === 'client' ? 'selected' : '' ?>>عميل</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i>فلترة</button>
            </div>
            <div class="col-md-3 text-md-end">
                <a href="users.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left me-1"></i>مسح</a>
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
                        <th>المستخدم</th>
                        <th>البريد / الهاتف</th>
                        <th>الدور</th>
                        <th>الحالة</th>
                        <th>تاريخ الانضمام</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 0; while ($u = $users->fetch_assoc()): $i++; ?>
                    <tr>
                        <td class="text-muted"><?= $i ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?= e(user_avatar($u, 40)) ?>
                                <div>
                                    <div class="fw-bold"><?= e($u['full_name']) ?></div>
                                    <div class="text-muted small">@<?= e($u['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small"><?= e($u['email']) ?></div>
                            <div class="text-muted small" dir="ltr"><?= e($u['phone'] ?: '-') ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= e(getRoleLabel($u['role'])) ?></span></td>
                        <td><?= user_badge($u['status']) ?></td>
                        <td class="small text-muted"><?= e(format_date($u['created_at'])) ?></td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                                    <a href="edit_user.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-primary action-btn" title="تعديل"><i class="fa-solid fa-pen"></i></a>
                                    <form method="post" action="toggle_user.php" class="d-inline" onsubmit="return confirm('تغيير حالة «<?= e(addslashes($u['full_name'])) ?>»؟');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button class="btn btn-sm btn-outline-warning action-btn" title="<?= $u['status'] === 'active' ? 'تعطيل' : 'تفعيل' ?>">
                                            <i class="fa-solid <?= $u['status'] === 'active' ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="post" action="delete_user.php" class="d-inline" onsubmit="return confirm('تنبيه: حذف المستخدم «<?= e(addslashes($u['full_name'])) ?>» سيحذف حجوزاته نهائياً. متابعة؟');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger action-btn" title="حذف"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-secondary">حسابك</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($i === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">لا يوجد مستخدمون مطابقون</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
