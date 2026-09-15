<?php require ROOT_PATH . '/app/views/partials/header.php'; ?>
<main id="main-content">
    <?php
    $heroImage = $settings['media_hero'] ?? '/assets/img/hero-team.webp';
    $heroAlt = $settings['media_hero_alt_' . $lang] ?? ($lang === 'en'
        ? 'Al-Mawaheb customs clearance specialist at a container facility'
        : ($lang === 'ku'
            ? 'پسپۆڕی پاککردنەوەی گومرگیی المواهب لە شوێنی کۆنتێنەرەکان'
            : 'مختص من شركة المواهب في موقع للحاويات'));
    $slides = [
        [
            'class' => 'slide-seaport has-video',
            'video' => '/assets/video/bg-1080.mp4',
            'video_mobile' => '/assets/video/bg-720.mp4',
            'image' => '/assets/video/bg-poster-1600.jpg',
            'srcset' => '/assets/video/bg-poster-640.jpg 640w, /assets/video/bg-poster-1600.jpg 1600w',
            'sizes' => '100vw',
            'width' => 1600,
            'height' => 900,
            'alt' => t('port_video_alt', $lang),
            'eyebrow' => content_field($content, 'eyebrow'),
            'title' => content_field($content, 'hero_title'),
            'text' => content_field($content, 'hero_description'),
            'illustrative' => true,
        ],
        [
            'class' => 'slide-port-video has-video',
            'video' => '/assets/video/bg2-1080.mp4',
            'video_mobile' => '/assets/video/bg2-720.mp4',
            'image' => '/assets/video/bg2-poster-1600.jpg',
            'srcset' => '/assets/video/bg2-poster-640.jpg 640w, /assets/video/bg2-poster-1600.jpg 1600w',
            'sizes' => '100vw',
            'width' => 1600,
            'height' => 900,
            'alt' => t('port_video_alt', $lang),
            'eyebrow' => t('slider_port_eyebrow', $lang),
            'title' => t('slider_port_title', $lang),
            'text' => t('slider_port_text', $lang),
            'illustrative' => true,
        ],
        [
            'class' => 'slide-air-cargo',
            'image' => '/assets/img/slider-air-cargo.webp',
            'srcset' => '/assets/img/slider-air-cargo-640.webp 640w, /assets/img/slider-air-cargo-960.webp 960w, /assets/img/slider-air-cargo.webp 1672w',
            'sizes' => '100vw',
            'width' => 1672,
            'height' => 941,
            'alt' => t('slider_air_alt', $lang),
            'eyebrow' => t('slider_air_eyebrow', $lang),
            'title' => t('slider_air_title', $lang),
            'text' => t('slider_air_text', $lang),
            'illustrative' => true,
        ],
    ];
    ?>
    <section class="hero-slider" data-slider role="region" aria-roledescription="<?= e(t('slider_carousel', $lang)) ?>" aria-label="<?= e(t('slider_region', $lang)) ?>">
        <div class="hero-slider-track">
            <?php foreach ($slides as $index => $slide): ?>
                <article id="hero-slide-<?= $index + 1 ?>" class="hero-slide <?= e($slide['class']) ?><?= $index === 0 ? ' is-active' : '' ?>" data-slide data-duration="6500" role="group" aria-roledescription="<?= e(t('slider_slide', $lang)) ?>" aria-label="<?= $index + 1 ?> / <?= count($slides) ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                    <img class="hero-slide-image" src="<?= e($slide['image']) ?>" srcset="<?= e($slide['srcset']) ?>" sizes="<?= e($slide['sizes']) ?>" width="<?= (int) $slide['width'] ?>" height="<?= (int) $slide['height'] ?>" alt="<?= e($slide['alt']) ?>" <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                    <?php if (isset($slide['video'])): ?>
                    <video class="hero-slide-video" data-slide-video data-src="<?= e($slide['video']) ?>" data-mobile-src="<?= e($slide['video_mobile']) ?>" width="1920" height="1080" muted playsinline preload="none" disablepictureinpicture aria-hidden="true" tabindex="-1"></video>
                    <?php endif; ?>
                    <div class="hero-slide-shade" aria-hidden="true"></div>
                    <div class="container hero-slide-inner">
                        <div class="hero-slide-copy">
                            <p class="eyebrow light"><span></span><?= e($slide['eyebrow']) ?></p>
                            <?php if ($index === 0): ?><h1><?= e($slide['title']) ?></h1><?php else: ?><h2 class="hero-heading"><?= e($slide['title']) ?></h2><?php endif; ?>
                            <p class="hero-slide-lead"><?= e($slide['text']) ?></p>
                            <div class="button-row">
                                <a class="button" href="<?= e(page_url($lang, 'services')) ?>"><?= e(t('explore_services', $lang)) ?></a>
                                <a class="button button-glass" href="<?= e(page_url($lang, 'contact')) ?>"><?= e(t('contact_us', $lang)) ?></a>
                            </div>
                        </div>
                        <aside class="hero-dossier">
                            <div class="dossier-top"><span><?= e(t('scope_label', $lang)) ?></span><span aria-hidden="true">↗</span></div>
                            <strong><?= e(t('brand_statement', $lang)) ?></strong>
                            <div class="dossier-routes"><?php foreach (['land', 'sea', 'air'] as $mode): ?><span><?= service_icon($mode) ?><?= e(t($mode . '_crossings', $lang)) ?></span><?php endforeach; ?></div>
                            <small><?= e(t(isset($slide['video']) ? 'illustrative_footage' : ($slide['illustrative'] ? 'illustrative_image' : 'company_image'), $lang)) ?></small>
                        </aside>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="container hero-slider-controls" data-slider-controls>
            <div class="slider-count" aria-hidden="true"><span data-slider-current>01</span><i><b data-slider-progress></b></i><span>03</span></div>
            <div class="slider-dots" role="group" aria-label="<?= e(t('slider_region', $lang)) ?>">
                <?php foreach ($slides as $index => $_slide): ?>
                    <button type="button" aria-controls="hero-slide-<?= $index + 1 ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>" aria-label="<?= e(t('slider_slide', $lang)) ?> <?= $index + 1 ?>" data-slider-dot="<?= $index ?>"><span></span><b><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></b></button>
                <?php endforeach; ?>
            </div>
            <div class="slider-buttons">
                <button type="button" class="slider-button slider-pause" aria-label="<?= e(t('slider_motion_pause', $lang)) ?>" aria-pressed="false" data-play-label="<?= e(t('slider_motion_play', $lang)) ?>" data-pause-label="<?= e(t('slider_motion_pause', $lang)) ?>" data-slider-pause><span aria-hidden="true"></span></button>
                <button type="button" class="slider-button" aria-label="<?= e(t('slider_previous', $lang)) ?>" data-slider-previous><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg></button>
                <button type="button" class="slider-button" aria-label="<?= e(t('slider_next', $lang)) ?>" data-slider-next><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg></button>
            </div>
        </div>
        <div class="slider-route" aria-hidden="true"><span></span><i></i><span></span></div>
    </section>

    <div class="container capability-band">
        <div class="capability-heading"><span class="mini-brand"><img src="<?= e($logoPath) ?>" width="32" height="32" alt=""></span><strong><?= e(t('service_directory', $lang)) ?></strong></div>
        <?php foreach (array_slice($services, 0, 3) as $service): ?>
            <a href="<?= e(page_url($lang, 'services')) ?>#<?= e($service['slug']) ?>"><?= service_icon($service['icon']) ?><span><?= e($service['name']) ?></span><b aria-hidden="true">↗</b></a>
        <?php endforeach; ?>
    </div>

    <section class="section intro-section">
        <div class="container brand-story">
            <div class="story-visual reveal">
                <figure class="story-portrait"><img src="<?= e($heroImage) ?>" width="1079" height="1400" loading="lazy" alt="<?= e($heroAlt) ?>"><figcaption><?= e(t('company_image', $lang)) ?></figcaption></figure>
                <figure class="story-detail"><img src="/assets/img/slider-air-cargo-640.webp" width="640" height="360" loading="lazy" alt="<?= e(t('slider_air_alt', $lang)) ?>"><figcaption><?= e(t('illustrative_image', $lang)) ?></figcaption></figure>
                <span class="story-seal"><img src="<?= e($logoPath) ?>" width="60" height="60" alt=""><span><?= e(t('brand_statement', $lang)) ?></span></span>
            </div>
            <div class="story-copy reveal">
                <p class="eyebrow"><span></span><?= e(t('nav_about', $lang)) ?></p>
                <h2><?= e(content_field($content, 'about_title')) ?></h2>
                <p><?= e(content_field($content, 'about_body')) ?></p>
                <div class="story-highlights"><?php foreach (array_slice(lines(content_field($content, 'why_items')), 0, 2) as $benefit): ?><p><span aria-hidden="true">✓</span><?= e($benefit) ?></p><?php endforeach; ?></div>
                <a class="button" href="<?= e(page_url($lang, 'about')) ?>"><?= e(t('explore_company', $lang)) ?><span aria-hidden="true">↗</span></a>
            </div>
        </div>
    </section>

    <section class="section services-preview">
        <div class="container">
            <div class="section-heading wide reveal services-heading">
                <p class="eyebrow light"><span></span><?= e(t('nav_services', $lang)) ?></p>
                <h2><?= e(content_field($content, 'services_title')) ?></h2>
                <p><?= e(content_field($content, 'services_intro')) ?></p>
            </div>
            <div class="services-showcase">
                <figure class="services-photo"><img src="/assets/img/slider-seaport-960.webp" width="960" height="540" loading="lazy" alt="<?= e(t('slider_port_alt', $lang)) ?>"><figcaption><span><?= e(t('illustrative_image', $lang)) ?></span><strong><?= e(t('our_approach', $lang)) ?></strong><a class="button button-light" href="<?= e(page_url($lang, 'services')) ?>"><?= e(t('all_services', $lang)) ?> ↗</a></figcaption></figure>
                <div class="service-accordion">
                <?php foreach ($services as $index => $service): ?>
                    <details class="service-disclosure"<?= $index === 0 ? ' open' : '' ?>>
                        <summary><span class="service-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><span class="service-icon"><?= service_icon($service['icon']) ?></span><h3><?= e($service['name']) ?></h3><span class="disclosure-plus" aria-hidden="true">+</span></summary>
                        <div class="disclosure-body"><p><?= e($service['description']) ?></p><a class="text-link" href="<?= e(page_url($lang, 'contact', ['service' => $service['slug']])) ?>#contact-form"><?= e(t('ask_about', $lang)) ?> ↗</a></div>
                    </details>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section why-section">
        <div class="container why-grid">
            <div class="why-title reveal">
                <p class="eyebrow"><span></span><?= e(t('our_approach', $lang)) ?></p>
                <h2><?= e(content_field($content, 'why_title')) ?></h2>
                <div class="orbit-mark" aria-hidden="true"><img src="<?= e($settings['media_logo'] ?? '/assets/img/logo.png') ?>" width="150" height="150" alt=""></div>
            </div>
            <ol class="why-list">
                <?php foreach (lines(content_field($content, 'why_items')) as $index => $item): ?>
                    <li class="reveal" data-delay="<?= ($index % 3) + 1 ?>"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><p><?= e($item) ?></p></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>

    <section class="section scope-section">
        <div class="container scope-grid">
            <div class="scope-gallery reveal">
                <figure><img src="/assets/img/slider-seaport-960.webp" width="960" height="540" loading="lazy" alt="<?= e(t('slider_port_alt', $lang)) ?>"><figcaption><?= service_icon('sea') ?><strong><?= e(t('sea_crossings', $lang)) ?></strong><small><?= e(t('illustrative_image', $lang)) ?></small></figcaption></figure>
                <figure><img src="/assets/img/slider-air-cargo-640.webp" width="640" height="360" loading="lazy" alt="<?= e(t('slider_air_alt', $lang)) ?>"><figcaption><?= service_icon('air') ?><strong><?= e(t('air_crossings', $lang)) ?></strong><small><?= e(t('illustrative_image', $lang)) ?></small></figcaption></figure>
            </div>
            <div class="scope-copy reveal" data-delay="1">
                <p class="eyebrow light"><span></span><?= e(t('site_name', $lang)) ?></p>
                <h2><?= e(content_field($content, 'scope_title')) ?></h2>
                <p><?= e(content_field($content, 'scope_body')) ?></p>
                <div class="scope-modes"><?php foreach (['land', 'sea', 'air'] as $mode): ?><span><?= service_icon($mode) ?><?= e(t($mode . '_crossings', $lang)) ?></span><?php endforeach; ?></div>
                <a class="text-link" href="<?= e(page_url($lang, 'contact')) ?>#contact-form"><?= e(t('contact_us', $lang)) ?> ↗</a>
            </div>
        </div>
    </section>

    <section class="section profile-section">
        <div class="container profile-grid reveal">
            <div class="profile-file" aria-hidden="true"><img src="<?= e($logoPath) ?>" width="45" height="45" alt=""><span><?= e(t('company_profile', $lang)) ?></span><strong>2026</strong><i></i></div>
            <div>
                <p class="eyebrow"><span></span><?= e(t('company_profile', $lang)) ?></p>
                <h2><?= e(content_field($content, 'profile_title')) ?></h2>
                <p><?= e(content_field($content, 'profile_text')) ?></p>
            </div>
            <a class="button" href="<?= e($settings['media_profile'] ?? '#') ?>" target="_blank" rel="noopener"><?= e(t('download_profile', $lang)) ?></a>
        </div>
    </section>

    <section class="section cta-section">
        <div class="container cta-block reveal">
            <div><p class="eyebrow light"><span></span><?= e(t('contact_us', $lang)) ?></p><h2><?= e(content_field($content, 'cta_title')) ?></h2><p><?= e(content_field($content, 'cta_text')) ?></p></div>
            <a class="button button-light" href="<?= e(page_url($lang, 'contact')) ?>#contact-form"><?= e(t('quote', $lang)) ?></a>
        </div>
    </section>
</main>
<?php require ROOT_PATH . '/app/views/partials/footer.php'; ?>
