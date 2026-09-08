<?php
/**
 * ============================================================
 *  إضافة باقة — admin/add_package.php
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);
$page_title = 'إضافة باقة';
$active = 'packages';

$destinations = [];
if ($res = $conn->query("SELECT name FROM destinations ORDER BY name")) {
    while ($row = $res->fetch_assoc()) $destinations[] = $row;
}
if (count($destinations) === 0) {
    flash_set('أضف الوجهات أولاً من صفحة «الوجهات» قبل إضافة أي باقة.', 'warning');
    redirect('destinations.php');
}

$agents_list = [];
if ($res = $conn->query("SELECT id, full_name FROM users WHERE role = 'agent' AND status = 'active' ORDER BY full_name")) {
    while ($row = $res->fetch_assoc()) $agents_list[] = $row;
}

$errors = [];
$old = ['title' => '', 'destination' => '', 'type' => '', 'duration' => '', 'price' => '',
        'description' => '', 'includes' => '', 'excludes' => '', 'itinerary' => '', 'status' => 'available', 'agent_id' => 0];

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
    $old['agent_id']    = (int)($_POST['agent_id'] ?? 0);

    $valid_types  = ['family', 'adventure', 'cultural', 'beach', 'religious', 'business'];
    $valid_status = ['available', 'reserved', 'cancelled'];
    if (!in_array($old['status'], $valid_status, true)) $old['status'] = 'available';

    if (mb_strlen($old['title'], 'UTF-8') < 5) $errors[] = 'عنوان الباقة قصير جداً.';
    if ($old['destination'] === '') $errors[] = 'اختر الوجهة.';
    if (!in_array($old['type'], $valid_types, true)) $errors[] = 'اختر نوع الرحلة.';
    if ($old['duration'] < 1) $errors[] = 'المدة بالأيام يجب أن تكون 1 على الأقل.';
    if ($old['price'] <= 0) $errors[] = 'السعر يجب أن يكون أكبر من صفر.';

    if (count($errors) === 0) {
        /* التحقق من صحة الوجهة (أن تكون ضمن جدول الوجهات) */
        $chk = $conn->prepare("SELECT id FROM destinations WHERE name = ? LIMIT 1");
        $chk->bind_param('s', $old['destination']);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) {
            $errors[] = 'الوجهة المحددة غير موجودة في قائمة الوجهات.';
        }
    }

    /* رفع الصورة */
    $image = '';
    if (count($errors) === 0) {
        $up = upload_image($_FILES['image'] ?? null, 'packages', 5);
        if (!$up['ok'] && !empty($_FILES['image']['name'])) {
            $errors[] = 'صورة الباقة: ' . $up['error'];
        } elseif ($up['ok']) {
            $image = $up['file'];
        }
    }

    if (count($errors) === 0) {
        $agent = $old['agent_id'] > 0 ? $old['agent_id'] : null;
        if ($agent !== null) {
            $chk = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'agent' AND status = 'active' LIMIT 1");
            $chk->bind_param('i', $agent);
            $chk->execute();
            if (!$chk->get_result()->fetch_assoc()) {
                $errors[] = 'الوكيل المحدد غير صالح.';
                $agent = null;
            }
        }
    }

    if (count($errors) === 0) {
        $stmt = $conn->prepare("INSERT INTO packages (title, description, destination, duration, type, price, includes, excludes, itinerary, image, status, agent_id)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssdisdsssisi',
            $old['title'], $old['description'], $old['destination'], $old['duration'], $old['type'],
            $old['price'], $old['includes'], $old['excludes'], $old['itinerary'], $image,
            $old['status'], $agent);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            flash_set('تمت إضافة الباقة «' . $old['title'] . '» بنجاح.', 'success');
            redirect('packages.php');
        }
        $errors[] = 'تعذر حفظ الباقة.';
    }
}

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="page-head">
    <h4><i class="fa-solid fa-plus me-2 text-primary"></i>إضافة باقة سياحية</h4>
    <a href="packages.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-right me-1"></i>عودة</a>
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
            $show_agent = true;
            require __DIR__ . '/../includes/package_form_fields.php';
            ?>
            <button type="submit" class="btn btn-primary mt-4"><i class="fa-solid fa-plus me-1"></i>حفظ الباقة</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_footer.php'; ?>
