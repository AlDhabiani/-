<?php
/**
 * ============================================================
 *  التحقق من الجلسة والصلاحيات — includes/auth.php
 * ============================================================
 */

require_once __DIR__ . '/functions.php'; // يحمّل db.php والدوال المساعدة

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * التحقق من تسجيل الدخول
 * يُستخدم من صفحات المجلدات الفرعية (admin / agent / client)
 */
function check_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

/**
 * التحقق من تسجيل الدخول + الدور
 * مثال: check_role(['admin'])
 */
function check_role($allowed_roles = [])
{
    check_login();
    if (!in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
        header('Location: ../index.php');
        exit;
    }
}

/**
 * جلب بيانات المستخدم الحالي من قاعدة البيانات (لتحديث الحالة)
 * تعيد null إذا لم يعد المستخدم صالحاً (موقوف / محذوف)
 */
function current_user()
{
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        return null;
    }
    // مستخدم موقوف → إنهاء الجلسة فوراً
    if ($user['status'] === 'suspended') {
        session_destroy();
        return null;
    }
    return $user;
}

/**
 * التحقق الكامل: تسجيل دخول + دور + صلاحية الحساب
 * يعيد بيانات المستخدم الحالية أو ينهي العملية
 * مثال: $user = require_role(['agent']);
 */
function require_role(array $roles)
{
    check_role($roles);
    $user = current_user();
    if (!$user) {
        flash_safe_redirect();
        exit;
    }
    return $user;
}

/** إنهاء الجلسة والتحويل للرئيسية برسالة */
function flash_safe_redirect()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
    session_start();
    flash_set('انتهت جلستك أو تم إيقاف حسابك — يرجى تسجيل الدخول من جديد.', 'warning');
    header('Location: ../index.php');
    exit;
}
