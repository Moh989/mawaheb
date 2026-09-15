<?php require ROOT_PATH . '/app/views/partials/header.php'; ?>
<main id="main-content">
    <section class="inner-hero services-hero section">
        <div class="container inner-hero-grid">
            <div class="reveal"><p class="eyebrow"><span></span><?= e(content_field($content, 'eyebrow')) ?></p><h1><?= e(content_field($content, 'title')) ?></h1><a class="text-link" href="#services-directory"><?= e(t('service_directory', $lang)) ?> ↓</a></div>
            <p class="inner-lead reveal" data-delay="1"><?= e(content_field($content, 'intro')) ?></p>
        </div>
        <figure class="container inner-hero-media inner-hero-media-services reveal" data-delay="1">
            <img src="/assets/img/slider-seaport.webp" srcset="/assets/img/slider-seaport-640.webp 640w, /assets/img/slider-seaport-960.webp 960w, /assets/img/slider-seaport.webp 1672w" sizes="(max-width: 640px) 100vw, 1180px" width="1672" height="941" fetchpriority="high" alt="<?= e(t('slider_port_alt', $lang)) ?>">
            <span class="inner-media-label"><?= e(t('illustrative_image', $lang)) ?></span>
            <span class="inner-media-route" aria-hidden="true"><i></i><b></b><i></i></span>
        </figure>
    </section>
    <section class="section service-list-section" id="services-directory">
        <div class="container service-directory-layout">
        <aside class="directory-nav"><p class="eyebrow"><span></span><?= e(t('service_directory', $lang)) ?></p><?php foreach ($services as $index => $service): ?><a href="#<?= e($service['slug']) ?>"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><?= e($service['name']) ?><b aria-hidden="true">↙</b></a><?php endforeach; ?><?php if (!empty($settings['media_profile'])): ?><a class="directory-profile" href="<?= e($settings['media_profile']) ?>" target="_blank" rel="noopener">PDF · <?= e(t('download_profile', $lang)) ?> ↗</a><?php endif; ?></aside>
        <div class="detailed-services">
            <?php foreach ($services as $index => $service): ?>
                <article class="detailed-service reveal" id="<?= e($service['slug']) ?>" tabindex="-1">
                    <div class="service-meta"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><div class="service-icon large"><?= service_icon((string) $service['icon']) ?></div></div>
                    <div class="service-detail"><p class="detail-kicker"><?= e(t('service_details', $lang)) ?></p><h2><?= e($service['name']) ?></h2><p><?= e($service['description']) ?></p></div>
                    <a class="text-link" href="<?= e(page_url($lang, 'contact', ['service' => $service['slug']])) ?>#contact-form"><?= e(t('ask_about', $lang)) ?><span aria-hidden="true">↗</span></a>
                </article>
            <?php endforeach; ?>
        </div>
        </div>
    </section>
    <section class="section cta-section compact">
        <div class="container cta-block reveal"><div><p class="eyebrow light"><span></span><?= e(t('contact_us', $lang)) ?></p><h2><?= e($lang === 'en' ? 'Tell us what the transaction requires.' : ($lang === 'ku' ? 'پێمان بڵێ مامەڵەکە چی پێویستە.' : 'أخبرنا بما تتطلبه المعاملة.')) ?></h2></div><a class="button button-light" href="<?= e(page_url($lang, 'contact')) ?>#contact-form"><?= e(t('quote', $lang)) ?></a></div>
    </section>
</main>
<?php require ROOT_PATH . '/app/views/partials/footer.php'; ?>
