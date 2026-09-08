<?php
/**
 * ============================================================
 *  عرض جميع الباقات السياحية — packages.php
 *  بحث متقدم: الوجهة، النوع، السعر، المدة + فرز + ترقيم صفحات
 * ============================================================
 */
$page_title = 'الباقات السياحية';
require_once 'includes/header.php';

/* ---------- قوائم الفلاتر ---------- */
$destinations_list = [];
if ($res = $conn->query("SELECT DISTINCT destination FROM packages ORDER BY destination")) {
    while ($row = $res->fetch_assoc()) {
        $destinations_list[] = $row['destination'];
    }
}
$types_list = [
    'family'    => 'عائلي',
    'adventure' => 'مغامرات',
    'cultural'  => 'ثقافي',
    'beach'     => 'شاطئي',
    'religious' => 'ديني',
    'business'  => 'عملي',
];

/* ---------- قراءة الفلاتر من الرابط ---------- */
$f_dest  = trim((string)($_GET['destination'] ?? ''));
$f_type  = (string)($_GET['type'] ?? '');
$f_price = (string)($_GET['max_price'] ?? '');
$f_dur   = (string)($_GET['min_duration'] ?? '');
$f_sort  = (string)($_GET['sort'] ?? 'newest');
$page    = max(1, (int)($_GET['page'] ?? 1));
$per_page = 9;

if (!in_array($f_type, array_keys($types_list), true)) $f_type = '';
if (!ctype_digit($f_price)) $f_price = '';
if (!ctype_digit($f_dur)) $f_dur = '';

$allowed_sort = ['newest', 'price_asc', 'price_desc', 'views', 'rating'];
if (!in_array($f_sort, $allowed_sort, true)) $f_sort = 'newest';

/* ---------- بناء الاستعلام ---------- */
$where  = ["p.status = 'available'"];
$params = [];
$types  = '';

if ($f_dest !== '') {
    $where[] = "p.destination LIKE ?";
    $params[] = '%' . $f_dest . '%';
    $types .= 's';
}
if ($f_type !== '') {
    $where[] = "p.type = ?";
    $params[] = $f_type;
    $types .= 's';
}
if ($f_price !== '') {
    $where[] = "p.price <= ?";
    $params[] = (int)$f_price;
    $types .= 'i';
}
if ($f_dur !== '') {
    $where[] = "p.duration >= ?";
    $params[] = (int)$f_dur;
    $types .= 'i';
}
$where_sql = implode(' AND ', $where);

$sort_sql = match ($f_sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'views'      => 'p.views DESC',
    'rating'     => 'COALESCE(r.avg_rating, 0) DESC',
    default      => 'p.created_at DESC',
};

/* ---------- العدد الكلي ---------- */
$count_sql = "SELECT COUNT(*) FROM packages p LEFT JOIN (SELECT package_id, AVG(rating) avg_rating FROM reviews GROUP BY package_id) r ON r.package_id = p.id WHERE $where_sql";
$stmt = $conn->prepare($count_sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_row()[0];

$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

/* ---------- النتائج ---------- */
$sql = "SELECT p.*, u.full_name AS agent_name, r.avg_rating
        FROM packages p
        LEFT JOIN users u ON u.id = p.agent_id
        LEFT JOIN (SELECT package_id, AVG(rating) avg_rating FROM reviews GROUP BY package_id) r ON r.package_id = p.id
        WHERE $where_sql
        ORDER BY $sort_sql
        LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$packages = $stmt->get_result();

/* ---------- رابط مع الحفاظ على الفلاتر ---------- */
function packages_url(array $overrides = [])
{
    global $f_dest, $f_type, $f_price, $f_dur, $f_sort;
    $q = array_filter([
        'destination'  => $f_dest,
        'type'         => $f_type,
        'max_price'    => $f_price,
        'min_duration' => $f_dur,
        'sort'         => $f_sort !== 'newest' ? $f_sort : '',
    ], fn($v) => $v !== '');
    $q = array_merge($q, $overrides);
    return 'packages.php' . ($q ? '?' . http_build_query($q) : '');
}
?>

<section class="py-4 py-lg-5">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title"><i class="fa-solid fa-compass me-2 text-primary"></i>الباقات السياحية</h2>
            <div class="title-line mx-auto mb-3"></div>
            <p class="text-secondary">استعرض <?= $total ?> باقة متاحة — صفِّ وافرز حتى تجد رحلة أحلامك</p>
        </div>

        <!-- شريط البحث والفلترة -->
        <div class="card filter-card mb-4">
            <div class="card-body">
                <form method="get" action="packages.php" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small fw-bold" for="f_dest">الوجهة</label>
                        <input type="text" class="form-control" id="f_dest" name="destination" value="<?= e($f_dest) ?>" placeholder="مثال: صنعاء، عدن...">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small fw-bold" for="f_type">نوع الرحلة</label>
                        <select class="form-select" id="f_type" name="type">
                            <option value="">الكل</option>
                            <?php foreach ($types_list as $k => $label): ?>
                                <option value="<?= e($k) ?>" <?= $f_type === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small fw-bold" for="f_price">حد السعر (<?= e(CURRENCY) ?>)</label>
                        <input type="number" min="0" class="form-control" id="f_price" name="max_price" value="<?= e($f_price) ?>" placeholder="الأقصى">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small fw-bold" for="f_dur">الحد الأدنى للمدة</label>
                        <select class="form-select" id="f_dur" name="min_duration">
                            <option value="">أي مدة</option>
                            <?php foreach ([1, 2, 3, 5, 7] as $d): ?>
                                <option value="<?= $d ?>" <?= $f_dur === (string)$d ? 'selected' : '' ?>><?= $d ?>+ أيام</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small fw-bold" for="f_sort">الفرز حسب</label>
                        <select class="form-select" id="f_sort" name="sort">
                            <option value="newest" <?= $f_sort === 'newest' ? 'selected' : '' ?>>الأحدث</option>
                            <option value="price_asc" <?= $f_sort === 'price_asc' ? 'selected' : '' ?>>السعر: الأقل أولاً</option>
                            <option value="price_desc" <?= $f_sort === 'price_desc' ? 'selected' : '' ?>>السعر: الأعلى أولاً</option>
                            <option value="views" <?= $f_sort === 'views' ? 'selected' : '' ?>>الأكثر مشاهدة</option>
                            <option value="rating" <?= $f_sort === 'rating' ? 'selected' : '' ?>>الأعلى تقييماً</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>بحث</button>
                        <a href="packages.php" class="btn btn-outline-secondary">مسح الفلاتر</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- النتائج -->
        <?php if ($total > 0): ?>
            <div class="row g-4">
                <?php
                while ($p = $packages->fetch_assoc()):
                    echo package_card($p, $p['agent_name'] ?? '');
                endwhile;
                ?>
            </div>

            <!-- الترقيم -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4" aria-label="ترقيم الصفحات">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(packages_url(['page' => $page - 1])) ?>"><i class="fa-solid fa-chevron-right"></i></a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e(packages_url(['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(packages_url(['page' => $page + 1])) ?>"><i class="fa-solid fa-chevron-left"></i></a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-magnifying-glass fa-3x mb-3 text-primary"></i>
                <h5 class="fw-bold">لا توجد باقات تطابق بحثك</h5>
                <p>جرّب توسيع نطاق البحث أو <a href="packages.php" class="text-primary fw-bold">مسح الفلاتر</a>.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
