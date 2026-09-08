<?php
/**
 * ============================================================
 *  إضافة باقة — agent/add_package.php
 *  (الوكيل يضيف باقة على حسابه فقط)
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['agent']);
$page_title = 'إضافة باقة';
$active = 'packages';

$destinations = [];
if ($res = $conn->query("SELECT name FROM destinations ORDER BY name")) {
    while ($row = $res->fetch_assoc()) $destinations[] = $row;
}
if (count($destinations) === 0) {
    flash_set('لا توجد وجهات معتمدة حالياً — تواصل مع إدارة المنصة.', 'warning');
    redirect('my_packages.php');
}

$errors = [];
$old = ['title' => '', 'destination' => '', 'type' => '', 'duration' => '', 'price' => '',
        'description' => '', 'includes' => '', 'excludes' => '', 'itinerary' => '', 'status' => 'available', 'agent_id' => (int)$user['id']];

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

    $image = '';
    if (count($errors) === 0 && !empty($_FILES['image']['name'])) {
        $up = upload_image($_FILES['image'], 'packages', 5);
        if (!$up['ok']) {
            $errors[] = 'صورة الباقة: ' . $up['error'];
        } else {
            $image = $up['file'];
        }
    }

    if (count($errors) === 0) {
        $agent = (int)$user['id'];
        $stmt = $conn->prepare("INSERT INTO packages (title, description, destination, duration, type, price, includes, excludes, itinerary, image, status, agent_id)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssdisdsssssi',
            $old['title'], $old['description'], $old['destination'], $old['duration'], $old['type'],
            $old['price'], $old['includes'], $old['excludes'], $old['itinerary'], $image,
            $old['status'], $agent);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            flash_set('تمت إضافة باقتك «' . $old['title'] . '» بنجاح.', 'success');
            redirect('my_packages.php');
        }
        $errors[] = 'تعذر حفظ الباقة.';
    }
}

require __DIR__ . '/../includes/sidebar_agent.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-plus me-2 text-primary"></i>إضافة باقة سياحية</h4>
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
            <?php
            $show_agent = false;
            require __DIR__ . '/../includes/package_form_fields.php';
            ?>
            <button type="submit" class="btn btn-primary mt-4"><i class="fa-solid fa-plus me-1"></i>حفظ الباقة</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
