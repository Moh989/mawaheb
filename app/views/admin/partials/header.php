<?php
$adminTitles = [
    '' => 'لوحة التحكم',
    'pages' => 'محتوى الصفحات',
    'services' => 'الخدمات',
    'requests' => 'طلبات التواصل',
    'settings' => 'الإعدادات والوسائط',
    'account' => 'الحساب الإداري',
];
$adminTitle = $adminTitles[$adminSection] ?? 'لوحة التحكم';
$success = flash('success');
$error = flash('error');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($adminTitle) ?> | إدارة شركة المواهب</title>
    <link rel="icon" href="/assets/img/logo.png" type="image/png">
    <link rel="stylesheet" href="/assets/css/admin.css?v=1.0.0">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <a class="admin-brand" href="/admin"><img src="/assets/img/logo.png" width="48" height="48" alt=""><span><strong>شركة المواهب</strong><small>إدارة الموقع</small></span></a>
        <nav>
            <a href="/admin"<?= $adminSection === '' ? ' aria-current="page"' : '' ?>><span>⌂</span>نظرة عامة</a>
            <a href="/admin/pages"<?= $adminSection === 'pages' ? ' aria-current="page"' : '' ?>><span></span>محتوى الصفحات</a>
            <a href="/admin/services"<?= $adminSection === 'services' ? ' aria-current="page"' : '' ?>><span>◇</span>الخدمات</a>
            <a href="/admin/requests"<?= $adminSection === 'requests' ? ' aria-current="page"' : '' ?>><span>↙</span>طلبات التواصل</a>
            <a href="/admin/settings"<?= $adminSection === 'settings' ? ' aria-current="page"' : '' ?>><span>⚙</span>الإعدادات والوسائط</a>
            <a href="/admin/account"<?= $adminSection === 'account' ? ' aria-current="page"' : '' ?>><span>○</span>الحساب الإداري</a>
        </nav>
        <div class="sidebar-footer">
            <a href="/ar/" target="_blank">معاينة الموقع ↗</a>
            <form method="post" action="/admin/logout"><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><button type="submit">تسجيل الخروج</button></form>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar"><button type="button" class="sidebar-toggle" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false">☰<span class="sr-only">القائمة</span></button><div><p>مرحباً، <?= e($_SESSION['admin_name'] ?? 'مدير الموقع') ?></p><h1><?= e($adminTitle) ?></h1></div></header>
        <?php if ($success): ?><div class="admin-alert success" role="status"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="admin-alert error" role="alert"><?= e($error) ?></div><?php endif; ?>

