<?php
$direction = $lang === 'en' ? 'ltr' : 'rtl';
$currentPage = in_array($pageKey, ['home', 'about', 'services', 'contact'], true) ? $pageKey : 'home';
$title = $pageData['seo_title'] ?? t('not_found_title', $lang) . ' | ' . t('site_name', $lang);
$description = $pageData['seo_description'] ?? t('not_found_text', $lang);
$canonical = app_url(ltrim(page_url($lang, $currentPage), '/'));
$heroPath = $settings['media_hero'] ?? '/assets/img/hero-team.webp';
$logoPath = $settings['media_logo'] ?? '/assets/img/logo.png';
$pagePath = $currentPage === 'home' ? '' : $currentPage;
$languageQuery = $currentPage === 'contact' && isset($_GET['service']) && preg_match('/^[a-z0-9-]+$/', (string) $_GET['service']) ? ['service' => (string) $_GET['service']] : [];
$navItems = ['home' => 'nav_home', 'about' => 'nav_about', 'services' => 'nav_services', 'contact' => 'nav_contact'];
$navigationServices = $services ?? ContentRepository::services($lang);
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $settings['company_legal_ar'] ?? 'شركة المواهب للاخراج والتخليص الكمركي محدودة المسؤولية',
    'alternateName' => $settings['company_name_en'] ?? 'Al-Mawaheb Co.',
    'url' => app_url(''),
    'logo' => app_url(ltrim($logoPath, '/')),
    'email' => $settings['email'] ?? null,
    'telephone' => $settings['phone'] ?? null,
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $settings['address_en'] ?? null,
        'addressLocality' => 'Baghdad',
        'addressCountry' => 'IQ',
    ],
];
?>
<!doctype html>
<html lang="<?= e($lang) ?>" dir="<?= $direction ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="theme-color" content="#951f25">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <?php foreach (['ar', 'en', 'ku'] as $alternateLang): ?>
        <link rel="alternate" hreflang="<?= $alternateLang ?>" href="<?= e(app_url(ltrim(page_url($alternateLang, $currentPage), '/'))) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e(app_url(ltrim(page_url('ar', $currentPage), '/'))) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e(app_url(ltrim($heroPath, '/'))) ?>">
    <meta property="og:locale" content="<?= $lang === 'en' ? 'en_US' : ($lang === 'ku' ? 'ku_IQ' : 'ar_IQ') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="<?= e($logoPath) ?>" type="image/png">
    <?php if ($pageKey === 'home'): ?><link rel="preload" as="image" href="/assets/video/bg-poster-1600.jpg" imagesrcset="/assets/video/bg-poster-640.jpg 640w, /assets/video/bg-poster-1600.jpg 1600w" imagesizes="100vw" fetchpriority="high"><?php endif; ?>
    <?php if ($lang !== 'en'): ?><link rel="preload" href="/assets/fonts/noto-sans-arabic.woff2" as="font" type="font/woff2" crossorigin><?php endif; ?>
    <link rel="stylesheet" href="/assets/css/app.css?v=1.2.0">
    <link rel="stylesheet" href="/assets/css/identity.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/identity.css') ?>">
    <script type="application/ld+json" nonce="<?= e($nonce) ?>"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="page-<?= e($pageKey) ?>" id="page-top">
<a class="skip-link" href="#main-content"><?= e(t('skip', $lang)) ?></a>
<?php if (!empty($preview)): ?><div class="preview-bar">معاينة مسودة غير منشورة</div><?php endif; ?>
<div class="utility-bar"><div class="container utility-inner"><span><?= e(t('brand_statement', $lang)) ?></span><div><?php if (!empty($settings['email'])): ?><a dir="ltr" href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a><?php endif; ?><?php if (!empty($settings['phone'])): ?><a dir="ltr" href="tel:<?= e(preg_replace('/\s+/', '', $settings['phone'])) ?>"><?= e($settings['phone']) ?></a><?php endif; ?></div></div></div>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="<?= e(page_url($lang)) ?>" aria-label="<?= e(t('site_name', $lang)) ?>">
            <img src="<?= e($logoPath) ?>" width="58" height="58" alt="<?= e(t('site_name', $lang)) ?>">
            <span class="brand-copy">
                <strong><?= e(t('site_name', $lang)) ?></strong>
                <small><?= e(t('site_descriptor', $lang)) ?></small>
            </span>
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" data-menu-toggle>
            <span class="sr-only" data-menu-label data-open="<?= e(t('menu_open', $lang)) ?>" data-close="<?= e(t('menu_close', $lang)) ?>"><?= e(t('menu_open', $lang)) ?></span>
            <span></span><span></span><span></span>
        </button>

        <div class="header-actions" id="primary-nav" data-nav>
            <nav class="primary-nav" aria-label="<?= e(t('menu_open', $lang)) ?>">
                <?php foreach ($navItems as $key => $translationKey): ?>
                    <?php if ($key === 'services'): ?>
                    <details class="nav-services<?= $currentPage === 'services' ? ' is-current' : '' ?>" data-services-dropdown>
                        <summary class="services-toggle" aria-controls="services-menu" data-services-toggle><span><?= e(t('nav_services', $lang)) ?></span><svg aria-hidden="true" viewBox="0 0 12 8"><path d="m1 1 5 5 5-5"/></svg></summary>
                        <ul class="services-menu" id="services-menu" data-services-menu>
                            <li class="services-menu-overview"><a href="<?= e(page_url($lang, 'services')) ?>" data-services-overview<?= $currentPage === 'services' ? ' aria-current="page"' : '' ?>><span><strong><?= e(t('nav_services', $lang)) ?></strong><small><?= e(t('view_services_page', $lang)) ?></small></span><b aria-hidden="true">↗</b></a></li>
                            <?php foreach ($navigationServices as $navService): ?>
                            <li><a href="<?= e(page_url($lang, 'services') . '#' . rawurlencode($navService['slug'])) ?>" data-service-link><span class="nav-service-icon"><?= service_icon($navService['icon']) ?></span><span><?= e($navService['name']) ?></span><b aria-hidden="true">↗</b></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                    <?php else: ?>
                    <a href="<?= e(page_url($lang, $key)) ?>"<?= $currentPage === $key ? ' aria-current="page"' : '' ?>><?= e(t($translationKey, $lang)) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div class="language-switcher" data-language>
                <button type="button" class="language-button" aria-expanded="false" aria-controls="language-menu" data-language-button>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg>
                    <span><?= e(t(['ar' => 'arabic', 'en' => 'english', 'ku' => 'kurdish'][$lang], $lang)) ?></span>
                    <svg class="chevron" aria-hidden="true" viewBox="0 0 12 8"><path d="m1 1 5 5 5-5"/></svg>
                </button>
                <div class="language-menu" id="language-menu" hidden data-language-menu>
                    <a href="<?= e(page_url('ar', $currentPage, $languageQuery)) ?>" lang="ar" dir="rtl"<?= $lang === 'ar' ? ' aria-current="true"' : '' ?>>العربية</a>
                    <a href="<?= e(page_url('en', $currentPage, $languageQuery)) ?>" lang="en" dir="ltr"<?= $lang === 'en' ? ' aria-current="true"' : '' ?>>English</a>
                    <a href="<?= e(page_url('ku', $currentPage, $languageQuery)) ?>" lang="ku" dir="rtl"<?= $lang === 'ku' ? ' aria-current="true"' : '' ?>>کوردی</a>
                </div>
            </div>
            <a class="button button-sm header-quote" href="<?= e(page_url($lang, 'contact')) ?>#contact-form"><?= e(t('quote', $lang)) ?><span aria-hidden="true">↗</span></a>
        </div>
    </div>
</header>
