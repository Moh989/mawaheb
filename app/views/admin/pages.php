<?php
$pageNames = ['home'=>'الرئيسية','about'=>'من نحن','services'=>'خدماتنا','contact'=>'اتصل بنا'];
$langNames = ['ar'=>'العربية','en'=>'English','ku'=>'کوردی'];
require ROOT_PATH . '/app/views/admin/partials/header.php';
?>
<section class="admin-card">
    <div class="card-heading"><div><p>12 نسخة مستقلة</p><h2>الصفحات واللغات</h2></div><span class="help-text">المسودة لا تظهر للزائر حتى النشر</span></div>
    <div class="content-list">
        <?php foreach ($pages as $page): ?>
            <a class="content-row" href="/admin/pages?id=<?= (int) $page['id'] ?>">
                <span class="content-icon"><?= e(mb_substr($pageNames[$page['page_key']], 0, 1)) ?></span>
                <div><strong><?= e($pageNames[$page['page_key']]) ?></strong><small><?= e($langNames[$page['lang']]) ?></small></div>
                <div class="content-states"><span class="status <?= $page['published_at'] ? 'published' : 'draft' ?>"><?= $page['published_at'] ? 'منشور' : 'مسودة' ?></span><span class="status <?= e($page['translation_status']) ?>"><?= $page['translation_status'] === 'approved' ? 'معتمد' : 'بحاجة إلى مراجعة' ?></span></div>
                <span class="row-arrow">←</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php require ROOT_PATH . '/app/views/admin/partials/footer.php'; ?>

