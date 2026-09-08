<?php
/**
 * ============================================================
 *  تعديل باقة — agent/edit_package.php
 *  (الوكيل يعدل باقاته فقط)
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['agent']);
$page_title = 'تعديل باقة';
$active = 'packages';

$uid = (int)$user['id'];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM packages WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();

if (!$package || (int)$package['agent_id'] !== $uid) {
    flash_set('هذه الباقة غير موجودة أو لا تملك صلاحيتها.', 'danger');
    redirect('my_packages.php');
}

$destinations = [];
if ($res = $conn->query("SELECT name FROM destinations ORDER BY name")) {
    while ($row = $res->fetch_assoc()) $destinations[] = $row;
}

$errors = [];
$old = $package;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['title']       = trim((string)($_POST['title'] ?? ''));
    $old['destination'] = trim((string)($_POST['destination'] ?? ''));
    $old['type']        = (string)($_POST['type'] ?? '');
    $old['duration']    = (int)($_POST['duration'] ?? 0);
    $old['price']       = (float)($_POST['price'] ?? 0);
    $old['description'] = trim((string)($_POST['description'] ?? ''));
    $old['includes']    = trim((string)($_POST['includes'] ?? ''));
    $old['excludes']    = trim((string)($_POST['excludes'] ?? ''));
    $old['itinerary']   = trim((string)($_POST['itinerary'] ?? ''));
    $old['status']      = (string)($_POST['status'] ?? 'available');

    $valid_types  = ['family', 'adventure', 'cultural', 'beach', 'religious', 'business'];
    $valid_status = ['available', 'reserved', 'cancelled'];
    if (!in_array($old['status'], $valid_status, true)) $old['status'] = 'available';

    if (mb_strlen($old['title'], 'UTF-8') < 5) $errors[] = 'عنوان الباقة قصير جداً.';
    if ($old['destination'] === '') $errors[] = 'اختر الوجهة.';
    if (!in_array($old['type'], $valid_types, true)) $errors[] = 'اختر نوع الرحلة.';
    if ($old['duration'] < 1) $errors[] = 'المدة بالأيام يجب أن تكون 1 على الأقل.';
    if ($old['price'] <= 0) $errors[] = 'السعر يجب أن يكون أكبر من صفر.';

    if (count($errors) === 0) {
        $chk = $conn->prepare("SELECT id FROM destinations WHERE name = ? LIMIT 1");
        $chk->bind_param('s', $old['destination']);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) {
            $errors[] = 'الوجهة المحددة غير موجودة في قائمة الوجهات.';
        }
    }

    $new_image = $package['image'] ?? '';
    if (count($errors) === 0 && !empty($_FILES['image']['name'])) {
        $up = upload_image($_FILES['image'], 'packages', 5);
        if (!$up['ok']) {
            $errors[] = 'صورة الباقة: ' . $up['error'];
        } else {
            $new_image = $up['file'];
        }
    }

    if (count($errors) === 0) {
        $agent = $uid;
        $stmt = $conn->prepare("UPDATE packages SET title=?, description=?, destination=?, duration=?, type=?, price=?, includes=?, excludes=?, itinerary=?, image=?, status=?, agent_id=? WHERE id=? AND agent_id=?");
        $stmt->bind_param('ssdisdsssssiii',
            $old['title'], $old['description'], $old['destination'], $old['duration'], $old['type'],
            $old['price'], $old['includes'], $old['excludes'], $old['itinerary'], $new_image,
            $old['status'], $agent, $id, $uid);
        if ($stmt->execute()) {
            if ($new_image !== ($package['image'] ?? '') && !empty($package['image'])) {
                delete_upload('packages', $package['image']);
            }
            flash_set('تم تحديث الباقة «' . $old['title'] . '» بنجاح.', 'success');
            redirect('my_packages.php');
        }
        $errors[] = 'تعذر حفظ التعديلات.';
    }
}

require __DIR__ . '/../includes/sidebar_agent.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-pen me-2 text-primary"></i>تعديل: <?= e($package['title']) ?></h4>
    <a href="my_packages.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-right me-1"></i>عودة</a>
</div>

<div class="card form-card">
    <div class="card-body p-4">
        <?php if (count($errors) > 0): ?>
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-4"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <?php
            $show_agent = false;
            require __DIR__ . '/../includes/package_form_fields.php';
            ?>
            <button type="submit" class="btn btn-primary mt-4"><i class="fa-solid fa-floppy-disk me-1"></i>حفظ التعديلات</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
