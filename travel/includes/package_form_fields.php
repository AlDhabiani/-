<?php
/**
 * ============================================================
 *  حقول نموذج الباقة (مشارَك بين إضافة/تعديل — مدير/وكيل)
 *  المتطلبات قبل الاستدعاء:
 *    $old           array  قيم الحقول الحالية
 *    $destinations  array  قوائم الوجهات
 *    $show_agent    bool   عرض حقل اختيار الوكيل (للمدير فقط)
 *    $agents_list   array  قائمة الوكلاء النشطين
 * ============================================================
 */
$types_options = [
    'family'    => 'عائلي',
    'adventure' => 'مغامرات',
    'cultural'  => 'ثقافي',
    'beach'     => 'شاطئي',
    'religious' => 'ديني',
    'business'  => 'عملي',
];
$status_options = ['available' => 'متاحة', 'reserved' => 'محجوزة', 'cancelled' => 'ملغاة'];
?>
<div class="row g-3">
    <div class="col-12">
        <label class="form-label">عنوان الباقة <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control" value="<?= e($old['title'] ?? '') ?>" required maxlength="200" placeholder="مثال: جولة صنعاء القديمة: سحر التاريخ والعمران">
    </div>
    <div class="col-md-4">
        <label class="form-label">الوجهة <span class="text-danger">*</span></label>
        <select name="destination" class="form-select" required>
            <option value="">اختر...</option>
            <?php foreach ($destinations as $d): ?>
                <option value="<?= e($d['name']) ?>" <?= (string)($old['destination'] ?? '') === $d['name'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">نوع الرحلة <span class="text-danger">*</span></label>
        <select name="type" class="form-select" required>
            <option value="">اختر...</option>
            <?php foreach ($types_options as $k => $label): ?>
                <option value="<?= $k ?>" <?= (string)($old['type'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">المدة (أيام) <span class="text-danger">*</span></label>
        <input type="number" name="duration" min="1" max="90" class="form-control" value="<?= e($old['duration'] ?? '') ?>" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">السعر للشخص (<?= e(CURRENCY) ?>) <span class="text-danger">*</span></label>
        <input type="number" name="price" min="0" step="0.01" class="form-control" value="<?= e($old['price'] ?? '') ?>" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select">
            <?php foreach ($status_options as $k => $label): ?>
                <option value="<?= $k ?>" <?= (string)($old['status'] ?? 'available') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if (!empty($show_agent)): ?>
        <div class="col-md-4">
            <label class="form-label">الوكيل المسؤول (اختياري)</label>
            <select name="agent_id" class="form-select">
                <option value="0">بدون وكيل (باقة عامة)</option>
                <?php foreach ($agents_list as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= (int)($old['agent_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="col-12">
        <label class="form-label">وصف الباقة</label>
        <textarea name="description" rows="3" class="form-control" placeholder="وصف جذاب يعمم على الرحلة..."><?= e($old['description'] ?? '') ?></textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">ما تشمله الباقة</label>
        <textarea name="includes" rows="3" class="form-control" placeholder="كل بند في سطر جديد:
الإقامة في فنادق 4 نجوم
وجبة الإفطار يومياً
المواصلات من المطار"><?= e($old['includes'] ?? '') ?></textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">ما لا تشمله الباقة</label>
        <textarea name="excludes" rows="3" class="form-control" placeholder="كل بند في سطر جديد:
التذاكر الدولية
المصروفات الشخصية"><?= e($old['excludes'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
        <label class="form-label">برنامج الرحلة اليومي</label>
        <textarea name="itinerary" rows="4" class="form-control" placeholder="اليوم 1: الوصول والاستقبال والجولة...
اليوم 2: زيارة المواقع...
(كل يوم في سطر جديد)"><?= e($old['itinerary'] ?? '') ?></textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">صورة الباقة <?= !empty($old['image']) ? '<span class="text-muted small">(اختر صورة جديدة لاستبدال الحالية)</span>' : '' ?></label>
        <input type="file" name="image" class="form-control" accept="image/*">
        <div class="form-text">JPG / PNG / WEBP / GIF — بحد أقصى 5MB</div>
    </div>
    <?php if (!empty($old['image'])): ?>
        <div class="col-md-6 d-flex align-items-center gap-3">
            <img src="uploads/packages/<?= e($old['image']) ?>" alt="الصورة الحالية" class="rounded" style="height:84px; width:132px; object-fit:cover;">
            <span class="small text-muted">الصورة الحالية</span>
        </div>
    <?php endif; ?>
</div>
