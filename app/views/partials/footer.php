<footer class="site-footer">
    <div class="container footer-opening"><p><?= e(t('brand_statement', $lang)) ?></p><a href="<?= e(page_url($lang, 'contact')) ?>#contact-form"><?= e(t('talk_to_us', $lang)) ?><span aria-hidden="true">↗</span></a></div>
    <div class="container footer-grid">
        <div class="footer-brand">
            <img src="<?= e($settings['media_logo'] ?? '/assets/img/logo.png') ?>" width="56" height="56" alt="">
            <div><strong><?= e(t('site_name', $lang)) ?></strong><p><?= e(t('footer_profile', $lang)) ?></p></div>
        </div>
        <div class="footer-contact">
            <h2><?= e(t('contact_direct', $lang)) ?></h2>
            <a dir="ltr" href="tel:<?= e(preg_replace('/\s+/', '', $settings['phone'] ?? '')) ?>"><?= e($settings['phone'] ?? '') ?></a>
            <a dir="ltr" href="mailto:<?= e($settings['email'] ?? '') ?>"><?= e($settings['email'] ?? '') ?></a>
            <?php if (!empty($settings['address_' . $lang])): ?><p><?= e($settings['address_' . $lang]) ?></p><?php endif; ?>
        </div>
        <nav class="footer-nav" aria-label="<?= e(t('footer_navigation', $lang)) ?>">
            <h2><?= e(t('footer_navigation', $lang)) ?></h2>
            <a href="<?= e(page_url($lang, 'about')) ?>"><?= e(t('nav_about', $lang)) ?></a>
            <a href="<?= e(page_url($lang, 'services')) ?>"><?= e(t('nav_services', $lang)) ?></a>
            <a href="<?= e(page_url($lang, 'contact')) ?>"><?= e(t('nav_contact', $lang)) ?></a>
            <?php if (!empty($settings['media_profile'])): ?><a href="<?= e($settings['media_profile']) ?>" target="_blank" rel="noopener"><?= e(t('company_profile', $lang)) ?> ↗</a><?php endif; ?>
        </nav>
    </div>
    <?php $socials = array_filter(['Facebook' => $settings['facebook'] ?? '', 'Instagram' => $settings['instagram'] ?? '', 'LinkedIn' => $settings['linkedin'] ?? '']); if ($socials): ?>
        <div class="container footer-socials"><?php foreach ($socials as $label => $url): ?><a href="<?= e($url) ?>" target="_blank" rel="noopener"><?= e($label) ?> ↗</a><?php endforeach; ?></div>
    <?php endif; ?>
    <div class="container footer-bottom">
        <p>© <?= date('Y') ?> <?= e(t('site_name', $lang)) ?>. <?= e(t('rights', $lang)) ?></p>
        <span class="route-signature" aria-hidden="true"><i></i><i></i><i></i></span>
        <a class="back-top" href="#page-top"><?= e(t('back_top', $lang)) ?> ↑</a>
    </div>
</footer>
<script src="/assets/js/app.js?v=<?= filemtime(PUBLIC_PATH . '/assets/js/app.js') ?>" defer></script>
</body>
</html>
