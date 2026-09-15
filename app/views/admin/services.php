<?php require ROOT_PATH . '/app/views/admin/partials/header.php'; ?>
<section class="admin-card">
    <div class="card-heading"><div><p>الترتيب والظهور والترجمة</p><h2>الخدمات</h2></div><a class="admin-button" href="/admin/services?new=1">إضافة خدمة</a></div>
    <div class="content-list">
        <?php foreach ($servicesAdmin as $service): ?>
            <a class="content-row" href="/admin/services?id=<?= (int) $service['id'] ?>"><span class="content-icon service-mini"><?= service_icon($service['icon']) ?></span><div><strong><?= e($service['published_name'] ?: $service['draft_name'] ?: $service['slug']) ?></strong><small dir="ltr"><?= e($service['slug']) ?></small></div><div class="content-states"><span class="status <?= $service['is_visible'] ? 'published' : 'draft' ?>"><?= $service['is_visible'] ? 'ظاهرة' : 'مخفية' ?></span><span>الترتيب: <?= (int) $service['sort_order'] ?></span></div><span class="row-arrow">←</span></a>
        <?php endforeach; ?>
    </div>
</section>
<?php require ROOT_PATH . '/app/views/admin/partials/footer.php'; ?>

