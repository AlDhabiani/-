<?php
/**
 * ============================================================
 *  الدوال المساعدة المشتركة — includes/functions.php
 * ============================================================
 *  دوال: تطهير الإخراج، رسائل النجاح/الخطأ، حماية CSRF،
 *  التسميات العربية، رفع الملفات، البطاقات، التقييمات.
 * ============================================================
 */

require_once __DIR__ . '/../db.php';

/* ---------- إعدادات الموقع ---------- */
define('SITE_NAME', 'سياحة');
define('CURRENCY', 'ر.ي'); // العملة المستخدمة — يمكن تغييرها (دولار، ريال سعودي...)

/* ============================================================
   دوال أساسية
   ============================================================ */

/** تطهير أي قيمة قبل عرضها (حماية من XSS) */
function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** تحويل فوري إلى صفحة أخرى */
function redirect($url)
{
    header("Location: $url");
    exit;
}

/** تنسيق السعر */
function format_price($price)
{
    return number_format((float)$price, 0) . ' ' . CURRENCY;
}

/** تنسيق التاريخ */
function format_date($date)
{
    if (empty($date)) return '-';
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : '-';
}

/** تنسيق التاريخ والوقت */
function format_datetime($datetime)
{
    if (empty($datetime)) return '-';
    $ts = strtotime($datetime);
    return $ts ? date('d/m/Y - H:i', $ts) : '-';
}

/* ============================================================
   التسميات العربية
   ============================================================ */

/** عرض نوع الباقة بالعربي */
function getPackageType($type)
{
    switch ($type) {
        case 'family':    return 'عائلي';
        case 'adventure': return 'مغامرات';
        case 'cultural':  return 'ثقافي';
        case 'beach':     return 'شاطئي';
        case 'religious': return 'ديني';
        case 'business':  return 'عملي';
        default:          return 'سياحي';
    }
}

/** عرض حالة الباقة بالعربي */
function getPackageStatus($status)
{
    switch ($status) {
        case 'available': return 'متاحة';
        case 'reserved':  return 'محجوزة';
        case 'cancelled': return 'ملغاة';
        default:          return 'غير معروف';
    }
}

/** عرض حالة الحجز بالعربي */
function getBookingStatus($status)
{
    switch ($status) {
        case 'pending':   return 'معلق';
        case 'approved':  return 'موافق عليه';
        case 'confirmed': return 'مؤكد';
        case 'cancelled': return 'ملغي';
        case 'completed': return 'مكتمل';
        default:          return 'غير معروف';
    }
}

/** عرض الدور بالعربي */
function getRoleLabel($role)
{
    switch ($role) {
        case 'admin':  return 'مدير';
        case 'agent':  return 'وكيل سياحة';
        case 'client': return 'عميل';
        default:       return 'غير معروف';
    }
}

/** عرض حالة المستخدم بالعربي */
function getUserStatusLabel($status)
{
    switch ($status) {
        case 'active':    return 'نشط';
        case 'pending':   return 'بانتظار الموافقة';
        case 'suspended': return 'موقوف';
        default:          return 'غير معروف';
    }
}

/* ---------- شارات ملونة (Bootstrap Badges) ---------- */

function package_badge($status)
{
    $map = ['available' => 'success', 'reserved' => 'warning', 'cancelled' => 'danger'];
    $bg  = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $bg . '">' . e(getPackageStatus($status)) . '</span>';
}

function booking_badge($status)
{
    $map = ['pending' => 'warning', 'approved' => 'info', 'confirmed' => 'primary', 'cancelled' => 'danger', 'completed' => 'success'];
    $bg  = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $bg . '">' . e(getBookingStatus($status)) . '</span>';
}

function user_badge($status)
{
    $map = ['active' => 'success', 'pending' => 'warning', 'suspended' => 'danger'];
    $bg  = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $bg . '">' . e(getUserStatusLabel($status)) . '</span>';
}

/* ============================================================
   رسائل النجاح والخطأ (Flash Messages)
   ============================================================ */

function flash_set($message, $type = 'success')
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function flash_render()
{
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $icons = [
        'success' => 'fa-circle-check',
        'danger'  => 'fa-circle-xmark',
        'warning' => 'fa-triangle-exclamation',
        'info'    => 'fa-circle-info',
    ];
    $icon = $icons[$f['type']] ?? 'fa-circle-info';

    return '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show flash-alert" role="alert">
        <i class="fa-solid ' . $icon . ' me-2"></i>' . e($f['message']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
    </div>';
}

/* ============================================================
   حماية من CSRF
   ============================================================ */

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** حقل مخفي يُضاف داخل كل نموذج POST */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** التحقق من صحة الرمز عند استلام POST — يُستخدم في بداية كل معالجة */
function csrf_verify()
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        die('<div style="font-family:Cairo,sans-serif;direction:rtl;text-align:center;padding:60px 20px;">
            <h2>⚠️ رمز الأمان غير صالح</h2>
            <p>يرجى العودة والضغط على زر الإرسال مرة أخرى.</p>
            <a href="javascript:history.back()">الرجوع</a></div>');
    }
}

/* ============================================================
   رفع الملفات (صور الباقات / وثائق الوكلاء / الصور الشخصية)
   ============================================================ */

/**
 * رفع صورة بأمان
 * @param array  $file      مدخل $_FILES
 * @param string $subfolder مجلد الفرع داخل uploads/ (packages, agents)
 * @param int    $max_mb    الحد الأقصى للحجم
 * @return array ['ok' => bool, 'file' => name, 'error' => msg]
 */
function upload_image($file, $subfolder, $max_mb = 5)
{
    $folder = __DIR__ . '/../uploads/' . $subfolder;
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    if (empty($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'لم يتم اختيار ملف'];
    }
    if ($file['size'] <= 0) {
        return ['ok' => false, 'error' => 'الملف فارغ'];
    }
    if ($file['size'] > $max_mb * 1024 * 1024) {
        return ['ok' => false, 'error' => "حجم الملف يتجاوز $max_mb ميغابايت"];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed_mime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    if (!isset($allowed_mime[$mime])) {
        return ['ok' => false, 'error' => 'صيغة غير مدعومة (يُسمح فقط بـ JPG / PNG / WEBP / GIF)'];
    }

    $name = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $allowed_mime[$mime];
    if (!move_uploaded_file($file['tmp_name'], $folder . '/' . $name)) {
        return ['ok' => false, 'error' => 'تعذر حفظ الملف — تحقق من صلاحيات مجلد uploads'];
    }
    return ['ok' => true, 'file' => $name];
}

/** حذف ملف مرفوع (إذا كان موجوداً) */
function delete_upload($subfolder, $file)
{
    if (empty($file)) return;
    $path = __DIR__ . '/../uploads/' . $subfolder . '/' . basename($file);
    if (is_file($path)) {
        @unlink($path);
    }
}

/* ============================================================
   التقييمات
   ============================================================ */

/** متوسط تقييم باقة */
function package_avg_rating($package_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE package_id = ?");
    $stmt->bind_param('i', $package_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return (float)($row[0] ?? 0);
}

/** نجوم التقييم */
function stars_html($avg)
{
    $avg = (float)$avg;
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($avg >= $i - 0.25) {
            $html .= '<i class="fa-solid fa-star"></i>';
        } elseif ($avg >= $i - 0.75) {
            $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
        } else {
            $html .= '<i class="fa-regular fa-star"></i>';
        }
    }
    return '<span class="stars">' . $html . '</span>';
}

/* ============================================================
   بطاقة الباقة (تُستخدم في الصفحة الرئيسية وصفحة الباقات)
   ============================================================ */

function package_card($p, $agent_name = '')
{
    $rating = package_avg_rating($p['id']);
    $img    = !empty($p['image']) ? 'uploads/packages/' . e($p['image']) : 'assets/img/placeholder.svg';

    return '
    <div class="col">
        <div class="card pkg-card h-100 border-0 shadow-sm">
            <div class="pkg-img position-relative overflow-hidden">
                <img src="' . $img . '" alt="' . e($p['title']) . '" loading="lazy">
                <span class="badge bg-primary pkg-type-badge">' . e(getPackageType($p['type'])) . '</span>
                <span class="pkg-price badge bg-dark"><i class="fa-solid fa-tag me-1"></i>' . e(format_price($p['price'])) . '</span>
            </div>
            <div class="card-body d-flex flex-column">
                <h5 class="pkg-title"><a href="package_details.php?id=' . (int)$p['id'] . '">' . e($p['title']) . '</a></h5>
                <div class="pkg-meta text-secondary small mb-2">
                    <span><i class="fa-solid fa-location-dot me-1"></i>' . e($p['destination']) . '</span>
                    <span class="ms-3"><i class="fa-regular fa-calendar me-1"></i>' . (int)$p['duration'] . ' أيام</span>
                </div>
                <div class="d-flex align-items-center mb-1">
                    ' . stars_html($rating) . '
                    <span class="small text-secondary ms-2">(' . number_format($rating, 1) . ')</span>
                </div>
                ' . ($agent_name !== '' ? '<div class="small text-secondary"><i class="fa-solid fa-building-user me-1"></i>بواسطة: ' . e($agent_name) . '</div>' : '') . '
                <div class="mt-auto pt-3">
                    <a href="package_details.php?id=' . (int)$p['id'] . '" class="btn btn-primary w-100">
                        <i class="fa-solid fa-circle-info me-1"></i>التفاصيل والحجز
                    </a>
                </div>
            </div>
        </div>
    </div>';
}

/* ============================================================
   صورة المستخدم (صورة شخصية أو حرف من الاسم)
   ============================================================ */

function user_avatar($user, $size = 40)
{
    if (!empty($user['profile_image'])) {
        return '<img src="uploads/agents/' . e($user['profile_image']) . '" alt="' . e($user['full_name']) . '" class="rounded-circle object-fit-cover" style="width:' . (int)$size . 'px;height:' . (int)$size . 'px;">';
    }
    $initial = mb_substr(trim((string)$user['full_name']), 0, 1, 'UTF-8');
    return '<span class="d-inline-flex align-items-center justify-content-center rounded-circle avatar-initial" style="width:' . (int)$size . 'px;height:' . (int)$size . 'px;">' . e($initial) . '</span>';
}
