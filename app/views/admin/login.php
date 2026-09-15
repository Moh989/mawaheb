<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>تسجيل الدخول | إدارة شركة المواهب</title>
    <link rel="icon" href="/assets/img/logo.png" type="image/png">
    <link rel="stylesheet" href="/assets/css/admin.css?v=1.0.0">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-panel">
        <a class="admin-brand centered" href="/ar/"><img src="/assets/img/logo.png" width="74" height="74" alt="شركة المواهب"><span><strong>شركة المواهب</strong><small>لوحة إدارة الموقع</small></span></a>
        <div class="login-heading"><p>دخول آمن</p><h1>مرحباً بعودتك</h1><span>أدخل بيانات الحساب الإداري للمتابعة.</span></div>
        <?php if ($loginError): ?><div class="admin-alert error" role="alert"><?= e($loginError) ?></div><?php endif; ?>
        <?php if ($message = flash('error')): ?><div class="admin-alert error" role="alert"><?= e($message) ?></div><?php endif; ?>
        <form method="post" action="/admin/login">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <label><span>البريد الإلكتروني</span><input type="email" name="email" required autocomplete="username" autofocus></label>
            <label><span>كلمة المرور</span><input type="password" name="password" required autocomplete="current-password"></label>
            <button class="admin-button full" type="submit">تسجيل الدخول</button>
        </form>
        <a class="back-link" href="/ar/">العودة إلى الموقع</a>
    </section>
    <aside class="login-art" aria-hidden="true"><span>مسار واضح</span><i></i><img src="/assets/img/logo.png" width="210" height="210" alt=""><i></i><span>إدارة دقيقة</span></aside>
</main>
</body>
</html>

