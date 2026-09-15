<?php require ROOT_PATH . '/app/views/admin/partials/header.php'; ?>
<section class="stats-grid">
    <article class="stat-card accent"><span>طلبات جديدة</span><strong><?= $counts['new_requests'] ?></strong><a href="/admin/requests?status=new">عرض الطلبات</a></article>
    <article class="stat-card"><span>إجمالي الطلبات</span><strong><?= $counts['all_requests'] ?></strong><a href="/admin/requests">السجل الكامل</a></article>
    <article class="stat-card"><span>صفحات منشورة</span><strong><?= $counts['published_pages'] ?>/12</strong><a href="/admin/pages">إدارة المحتوى</a></article>
    <article class="stat-card"><span>خدمات ظاهرة</span><strong><?= $counts['visible_services'] ?></strong><a href="/admin/services">إدارة الخدمات</a></article>
</section>
<section class="admin-card">
    <div class="card-heading"><div><p>آخر النشاط</p><h2>أحدث طلبات التواصل</h2></div><a class="admin-link" href="/admin/requests">عرض الكل</a></div>
    <?php if (!$recent): ?><div class="empty-state">لا توجد طلبات تواصل حتى الآن.</div><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>الاسم</th><th>الخدمة</th><th>الحالة</th><th>التاريخ</th></tr></thead><tbody><?php foreach ($recent as $request): ?><tr><td><strong><?= e($request['name']) ?></strong><small dir="ltr"><?= e($request['contact_method']) ?></small></td><td><?= e($request['service_name'] ?: 'غير محددة') ?></td><td><span class="status <?= e($request['status']) ?>"><?= ['new'=>'جديد','in_progress'=>'قيد المتابعة','closed'=>'مغلق'][$request['status']] ?></span></td><td dir="ltr"><?= e(date('Y-m-d H:i', strtotime($request['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/app/views/admin/partials/footer.php'; ?>

