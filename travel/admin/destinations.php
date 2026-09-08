<?php
/**
 * ============================================================
 *  إدارة الوجهات — admin/destinations.php
 *  إضافة / تعديل / حذف (صفحة واحدة)
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);
$page_title = 'إدارة الوجهات';
$active = 'destinations';

$errors = [];
$editing = null;
$old = ['name' => '', 'country' => '', 'description' => ''];

/* ---------- معالجة الإرسال ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action  = (string)($_POST['action'] ?? 'add');

    /* ---------- الحذف ---------- */
    if ($action === 'delete') {
        $delete_id = (int)($_POST['edit_id'] ?? 0);
        $chk = $conn->prepare("SELECT (SELECT COUNT(*) FROM packages p WHERE p.destination = d.name) AS c FROM destinations d WHERE d.id = ?");
        $chk->bind_param('i', $delete_id);
        $chk->execute();
        if ((int)$chk->get_result()->fetch_assoc()['c'] > 0) {
            flash_set('لا يمكن الحذف — توجد باقات مرتبطة بهذه الوجهة.', 'danger');
            redirect('destinations.php');
        }
        $del = $conn->prepare("DELETE FROM destinations WHERE id = ?");
        $del->bind_param('i', $delete_id);
        if ($del->execute()) {
            flash_set('تم حذف الوجهة.', 'success');
        } else {
            flash_set('تعذر حذف الوجهة.', 'danger');
        }
        redirect('destinations.php');
    }

    $old['name']        = trim((string)($_POST['name'] ?? ''));
    $old['country']     = trim((string)($_POST['country'] ?? ''));
    $old['description'] = trim((string)($_POST['description'] ?? ''));

    if (mb_strlen($old['name'], 'UTF-8') < 2) $errors[] = 'اسم الوجهة مطلوب.';
    if (mb_strlen($old['country'], 'UTF-8') < 2) $errors[] = 'اسم الدولة مطلوب.';

    if (count($errors) === 0) {
        if ($action === 'add') {
            $chk = $conn->prepare("SELECT id FROM destinations WHERE name = ? AND country = ? LIMIT 1");
            $chk->bind_param('ss', $old['name'], $old['country']);
            $chk->execute();
            if ($chk->get_result()->fetch_assoc()) {
                $errors[] = 'هذه الوجهة مضافة مسبقاً.';
            }
        }
    }

    if (count($errors) === 0) {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO destinations (name, country, description) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $old['name'], $old['country'], $old['description']);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                flash_set('تمت إضافة وجهة «' . $old['name'] . '» بنجاح.', 'success');
                redirect('destinations.php');
            }
        } else {
            $edit_id = (int)($_POST['edit_id'] ?? 0);
            $stmt = $conn->prepare("UPDATE destinations SET name = ?, country = ?, description = ? WHERE id = ?");
            $stmt->bind_param('sssi', $old['name'], $old['country'], $old['description'], $edit_id);
            if ($stmt->execute()) {
                flash_set('تم تحديث وجهة «' . $old['name'] . '».', 'success');
                redirect('destinations.php');
            }
        }
        $errors[] = 'حدث خطأ أثناء الحفظ.';
    }
}

/* ---------- تحميل الوجهات ---------- */
$destinations = [];
if ($res = $conn->query("SELECT d.*,
        (SELECT COUNT(*) FROM packages p WHERE p.destination = d.name) AS packages_count
                         FROM destinations d ORDER BY d.name")) {
    while ($row = $res->fetch_assoc()) {
        $destinations[] = $row;
    }
}

/* ---------- وضع التعديل ---------- */
$edit_id = (int)($_GET['edit_id'] ?? 0);
if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc();
    if ($editing) {
        $old['name']        = $editing['name'];
        $old['country']     = $editing['country'];
        $old['description'] = $editing['description'] ?? '';
    }
}

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-map-location-dot me-2 text-primary"></i>إدارة الوجهات <span class="badge bg-primary ms-1"><?= count($destinations) ?></span></h4>
</div>

<div class="row g-3">
    <!-- نموذج إضافة/تعديل -->
    <div class="col-lg-4">
        <div class="card form-card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">
                    <i class="fa-solid <?= $editing ? 'fa-pen' : 'fa-plus' ?> me-1 text-primary"></i>
                    <?= $editing ? 'تعديل: ' . e($editing['name']) : 'إضافة وجهة جديدة' ?>
                </h6>
                <?php if (count($errors) > 0): ?>
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-4"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'add' ?>">
                    <?php if ($editing): ?><input type="hidden" name="edit_id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">اسم الوجهة <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= e($old['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الدولة <span class="text-danger">*</span></label>
                        <input type="text" name="country" class="form-control" value="<?= e($old['country']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وصف قصير</label>
                        <textarea name="description" rows="3" class="form-control"><?= e($old['description']) ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i><?= $editing ? 'حفظ التعديل' : 'إضافة' ?></button>
                        <?php if ($editing): ?><a href="destinations.php" class="btn btn-outline-secondary">إلغاء</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- قائمة الوجهات -->
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>الوجهة</th>
                                <th>الدولة</th>
                                <th>الوصف</th>
                                <th>الباقات</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($destinations) > 0): ?>
                            <?php foreach ($destinations as $d): ?>
                                <tr>
                                    <td class="fw-bold"><i class="fa-solid fa-location-dot text-primary me-2"></i><?= e($d['name']) ?></td>
                                    <td><?= e($d['country']) ?></td>
                                    <td class="small text-muted" style="max-width:260px;">
                                        <?= e(mb_substr((string)($d['description'] ?: '—'), 0, 60, 'UTF-8')) ?><?= mb_strlen((string)($d['description'] ?: '')) > 60 ? '…' : '' ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= (int)$d['packages_count'] ?> باقة</span></td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="destinations.php?edit_id=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-primary action-btn" title="تعديل"><i class="fa-solid fa-pen"></i></a>
                                            <?php if ((int)$d['packages_count'] > 0): ?>
                                                <span class="btn btn-sm btn-outline-secondary action-btn disabled" title="لا يمكن الحذف — توجد باقات مرتبطة"><i class="fa-solid fa-lock"></i></span>
                                            <?php else: ?>
                                                <form method="post" action="destinations.php" class="d-inline" onsubmit="return confirm('حذف وجهة «<?= e(addslashes($d['name'])) ?>»؟');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="edit_id" value="<?= (int)$d['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger action-btn" title="حذف"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-5">لا توجد وجهات بعد</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
