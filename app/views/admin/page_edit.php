<?php
$pageNames = ['home'=>'الرئيسية','about'=>'من نحن','services'=>'خدماتنا','contact'=>'اتصل بنا'];
$langNames = ['ar'=>'العربية','en'=>'English','ku'=>'کوردی'];
$fieldLabels = [
    'eyebrow'=>'العبارة التمهيدية','hero_title'=>'العنوان الرئيسي','hero_description'=>'وصف الافتتاحية','about_title'=>'عنوان النبذة','about_body'=>'نص النبذة','services_title'=>'عنوان الخدمات','services_intro'=>'مقدمة الخدمات','why_title'=>'عنوان عناصر التميز','why_items'=>'عناصر التميز - كل عنصر في سطر','scope_title'=>'عنوان نطاق العمل','scope_body'=>'نص نطاق العمل','profile_title'=>'عنوان الملف التعريفي','profile_text'=>'وصف الملف التعريفي','cta_title'=>'عنوان دعوة التواصل','cta_text'=>'نص دعوة التواصل','title'=>'عنوان الصفحة','intro'=>'مقدمة الصفحة','company_title'=>'عنوان التعريف','company_body'=>'التعريف بالشركة','vision_title'=>'عنوان الرؤية','vision'=>'نص الرؤية','mission_title'=>'عنوان الرسالة','mission'=>'نص الرسالة','values_title'=>'عنوان القيم','values'=>'القيم - كل سطر بالصيغة: العنوان|الوصف','team_title'=>'عنوان فريق العمل','team_body'=>'نص فريق العمل','future_title'=>'عنوان الرؤية المستقبلية','future_body'=>'نص الرؤية المستقبلية','form_title'=>'عنوان النموذج','form_intro'=>'مقدمة النموذج'
];
require ROOT_PATH . '/app/views/admin/partials/header.php';
?>
<form method="post" action="/admin/pages?id=<?= (int) $translation['id'] ?>">
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $translation['id'] ?>">
    <div class="editor-toolbar"><a class="admin-link" href="/admin/pages">→ العودة</a><div><a class="admin-button secondary" target="_blank" href="<?= e(page_url($translation['lang'], $translation['page_key'], ['preview'=>1])) ?>">معاينة المسودة ↗</a><button class="admin-button secondary" name="action" value="save_page">حفظ كمسودة</button><button class="admin-button" name="action" value="publish_page">حفظ ونشر</button></div></div>
    <section class="admin-card editor-card">
        <div class="card-heading"><div><p><?= e($langNames[$translation['lang']]) ?></p><h2><?= e($pageNames[$translation['page_key']]) ?></h2></div><span class="status <?= e($translation['translation_status']) ?>"><?= $translation['translation_status'] === 'approved' ? 'الترجمة معتمدة' : 'بحاجة إلى مراجعة لغوية' ?></span></div>
        <div class="admin-form-grid">
            <?php foreach ($fields as $key => $value): $long = in_array($key, ['hero_description','about_body','services_intro','why_items','scope_body','profile_text','cta_text','intro','company_body','vision','mission','values','team_body','future_body','form_intro'], true); ?>
                <label class="<?= $long ? 'full' : '' ?>"><span><?= e($fieldLabels[$key] ?? $key) ?></span><?php if ($long): ?><textarea name="content[<?= e($key) ?>]" rows="<?= in_array($key, ['why_items','values'], true) ? 7 : 5 ?>" required><?= e((string) $value) ?></textarea><?php else: ?><input name="content[<?= e($key) ?>]" value="<?= e((string) $value) ?>" required><?php endif; ?></label>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="admin-card editor-card">
        <div class="card-heading"><div><p>محركات البحث والمشاركة</p><h2>بيانات SEO</h2></div></div>
        <div class="admin-form-grid"><label class="full"><span>عنوان الصفحة</span><input name="seo_title" maxlength="180" value="<?= e($translation['draft_seo_title']) ?>" required></label><label class="full"><span>وصف الصفحة</span><textarea name="seo_description" maxlength="320" rows="3" required><?= e($translation['draft_seo_description']) ?></textarea></label><label><span>حالة اعتماد الترجمة</span><select name="translation_status"><option value="approved"<?= $translation['translation_status'] === 'approved' ? ' selected' : '' ?>>معتمدة</option><option value="needs_review"<?= $translation['translation_status'] === 'needs_review' ? ' selected' : '' ?>>بحاجة إلى مراجعة</option></select></label></div>
    </section>
</form>
<?php require ROOT_PATH . '/app/views/admin/partials/footer.php'; ?>

