<?php
/**
 * ============================================================
 *  ملف الاتصال بقاعدة البيانات — db.php
 * ============================================================
 *
 *  البيئة المحلية (XAMPP):
 *      اترك القيم الافتراضية كما هي (127.0.0.1 / root / فارغ / travel_db)
 *      وتأكد من إنشاء قاعدة البيانات travel_db واستيراد database.sql
 *
 *  الاستضافة (InfinityFree):
 *      استبدل القيم بأرقامك من لوحة الاستضافة، مثال:
 *      $host = "sqlXXX.infinityfree.com";
 *      $user = "if0XXXXXX_username";
 *      $pass = "كلمة_السر";
 *      $db   = "if0XXXXXX_travel_db";
 * ============================================================
 */

$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "travel_db";

/*
 * على PHP 8.1+ يرمي mysqli استثناءً عند فشل الاتصال بدل إرجاع false —
 * نجعله يرجع false حتى تظهر الصفحة العربية الموضحَة أدناه مع سبب الخطأ.
 */
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    http_response_code(500);
    echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>خطأ في الاتصال</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="alert alert-danger text-center shadow-sm" role="alert">
    <h4 class="alert-heading"><i class="fa-solid fa-database me-2"></i>تعذر الاتصال بقاعدة البيانات</h4>
    <p class="mb-2">يرجى التحقق من بيانات الاتصال في ملف <code>db.php</code> والتأكد من تشغيل MySQL واستيراد ملف <code>database.sql</code>.</p>
    <small class="text-secondary">السبب: ' . htmlspecialchars(mysqli_connect_error(), ENT_QUOTES, "UTF-8") . '</small>
  </div>
</div>
</body>
</html>';
    die();
}

mysqli_set_charset($conn, "utf8mb4");
