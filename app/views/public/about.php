<?php require ROOT_PATH . '/app/views/partials/header.php'; ?>
<main id="main-content">
    <section class="inner-hero section">
        <div class="container inner-hero-grid">
            <div class="reveal"><p class="eyebrow"><span></span><?= e(content_field($content, 'eyebrow')) ?></p><h1><?= e(content_field($content, 'title')) ?></h1><a class="text-link" href="#company-story"><?= e(t('explore_company', $lang)) ?> ↓</a></div>
            <p class="inner-lead reveal" data-delay="1"><?= e(content_field($content, 'intro')) ?></p>
        </div>
        <?php $aboutHero = $settings['media_hero'] ?? '/assets/img/hero-team.webp'; ?>
        <figure class="container inner-hero-media inner-hero-media-company reveal" data-delay="1">
            <img src="<?= e($aboutHero) ?>"<?= $aboutHero === '/assets/img/hero-team.webp' ? ' srcset="/assets/img/hero-team-420.webp 420w, /assets/img/hero-team-640.webp 640w, /assets/img/hero-team.webp 1079w" sizes="(max-width: 640px) 100vw, 1180px"' : '' ?> width="1079" height="1400" fetchpriority="high" alt="<?= e($settings['media_hero_alt_' . $lang] ?? t('site_name', $lang)) ?>">
            <span class="inner-media-route" aria-hidden="true"><i></i><b></b><i></i></span>
        </figure>
    </section>

    <section class="section company-intro" id="company-story">
        <div class="container company-intro-grid">
            <div class="company-emblem reveal"><img src="<?= e($settings['media_logo'] ?? '/assets/img/logo.png') ?>" width="260" height="260" alt=""><span><?= e($settings['company_legal_ar'] ?? t('site_name', $lang)) ?></span><small><?= e(t('brand_statement', $lang)) ?></small></div>
            <div class="reveal" data-delay="1"><p class="eyebrow"><span></span>01</p><h2><?= e(content_field($content, 'company_title')) ?></h2><p class="large-copy"><?= e(content_field($content, 'company_body')) ?></p></div>
        </div>
    </section>

    <section class="section vision-mission">
        <div class="container vision-grid">
            <article class="vision-panel reveal"><span class="panel-no">02</span><h2><?= e(content_field($content, 'vision_title')) ?></h2><p><?= e(content_field($content, 'vision')) ?></p></article>
            <article class="vision-panel dark reveal" data-delay="1"><span class="panel-no">03</span><h2><?= e(content_field($content, 'mission_title')) ?></h2><p><?= e(content_field($content, 'mission')) ?></p></article>
        </div>
    </section>

    <section class="section values-section">
        <div class="container">
            <div class="section-heading reveal"><p class="eyebrow"><span></span>04</p><h2><?= e(content_field($content, 'values_title')) ?></h2></div>
            <div class="values-list">
                <?php foreach (lines(content_field($content, 'values')) as $index => $value): [$valueTitle, $valueText] = array_pad(explode('|', $value, 2), 2, ''); ?>
                    <article class="value-row reveal"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><h3><?= e($valueTitle) ?></h3><p><?= e($valueText) ?></p></article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section team-section">
        <div class="container team-grid">
            <div class="team-image reveal"><img src="<?= e($settings['media_hero'] ?? '/assets/img/hero-team.webp') ?>" width="1079" height="1400" loading="lazy" alt="<?= e($settings['media_hero_alt_' . $lang] ?? t('site_name', $lang)) ?>"><span></span></div>
            <div class="team-copy reveal" data-delay="1"><p class="eyebrow light"><span></span>05</p><h2><?= e(content_field($content, 'team_title')) ?></h2><p><?= e(content_field($content, 'team_body')) ?></p></div>
        </div>
    </section>

    <section class="section future-section">
        <div class="container future-grid reveal"><div><p class="eyebrow"><span></span>06</p><h2><?= e(content_field($content, 'future_title')) ?></h2></div><p><?= e(content_field($content, 'future_body')) ?></p></div>
    </section>
</main>
<?php require ROOT_PATH . '/app/views/partials/footer.php'; ?>
