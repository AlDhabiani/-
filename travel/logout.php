<?php
/**
 * ============================================================
 *  تسجيل الخروج — logout.php
 * ============================================================
 */
require_once 'includes/functions.php';

/* مسح الجلسة الحالية */
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

/* جلسة جديدة فقط لعرض رسالة التأكيد */
session_start();
flash_set('تم تسجيل خروجك بنجاح — نراك قريباً!', 'success');
redirect('index.php');
