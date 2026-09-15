<?php
$addressKey = 'address_' . $lang;
$success = flash('success');
$error = flash('error');
$selectedService = (string) ($_GET['service'] ?? old('service'));
$hoursKey = 'working_hours_' . $lang;
require ROOT_PATH . '/app/views/partials/header.php';
?>
<main id="main-content">
    <section class="inner-hero contact-hero section">
        <div class="container inner-hero-grid">
            <div class="reveal"><p class="eyebrow"><span></span><?= e(content_field($content, 'eyebrow')) ?></p><h1><?= e(content_field($content, 'title')) ?></h1><a class="button" href="#contact-form"><?= e(t('quote', $lang)) ?> ↗</a></div>
            <p class="inner-lead reveal" data-delay="1"><?= e(content_field($content, 'intro')) ?></p>
        </div>
        <figure class="container inner-hero-media inner-hero-media-contact reveal" data-delay="1">
            <img src="/assets/img/slider-air-cargo.webp" srcset="/assets/img/slider-air-cargo-640.webp 640w, /assets/img/slider-air-cargo-960.webp 960w, /assets/img/slider-air-cargo.webp 1672w" sizes="(max-width: 640px) 100vw, 1180px" width="1672" height="941" fetchpriority="high" alt="<?= e(t('slider_air_alt', $lang)) ?>">
            <span class="inner-media-label"><?= e(t('illustrative_image', $lang)) ?></span>
            <span class="inner-media-route" aria-hidden="true"><i></i><b></b><i></i></span>
        </figure>
    </section>

    <section class="section contact-section">
        <div class="container contact-grid">
            <aside class="contact-details reveal">
                <div class="contact-intro"><p class="eyebrow"><span></span><?= e(t('contact_direct', $lang)) ?></p><h2><?= e(t('talk_to_us', $lang)) ?></h2><p><?= e(t('request_help', $lang)) ?></p></div>
                <div class="contact-detail"><span>01</span><div><h2><?= e(t('address', $lang)) ?></h2><p><?= e($settings[$addressKey] ?? '') ?></p></div></div>
                <div class="contact-detail"><span>02</span><div><h2><?= e(t('phone', $lang)) ?></h2><a dir="ltr" href="tel:<?= e(preg_replace('/\s+/', '', $settings['phone'] ?? '')) ?>"><?= e($settings['phone'] ?? '') ?></a><?php if (!empty($settings['whatsapp'])): ?><a class="sub-link" dir="ltr" href="https://wa.me/<?= e($settings['whatsapp']) ?>" target="_blank" rel="noopener">WhatsApp ↗</a><?php endif; ?></div></div>
                <div class="contact-detail"><span>03</span><div><h2><?= e(t('email', $lang)) ?></h2><a dir="ltr" href="mailto:<?= e($settings['email'] ?? '') ?>"><?= e($settings['email'] ?? '') ?></a></div></div>
                <?php if (!empty($settings[$hoursKey])): ?><div class="contact-detail"><span>04</span><div><h2><?= e($lang === 'en' ? 'Working hours' : ($lang === 'ku' ? 'کاتەکانی کار' : 'ساعات العمل')) ?></h2><p><?= e($settings[$hoursKey]) ?></p></div></div><?php endif; ?>
                <?php if (!empty($settings['map_url'])): ?><a class="button button-ghost map-link" href="<?= e($settings['map_url']) ?>" target="_blank" rel="noopener"><?= e($lang === 'en' ? 'Open map' : ($lang === 'ku' ? 'کردنەوەی نەخشە' : 'فتح الخريطة')) ?> ↗</a><?php endif; ?>
            </aside>

            <div class="contact-form-card reveal" data-delay="1" id="contact-form">
                <div class="form-heading"><p class="eyebrow"><span></span><?= e(t('quote', $lang)) ?></p><h2><?= e(content_field($content, 'form_title')) ?></h2><p><?= e(content_field($content, 'form_intro')) ?></p></div>
                <?php if ($success): ?><div class="alert success" role="status"><?= e($success) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post" action="<?= e(page_url($lang, 'contact')) ?>">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <div class="hp-field" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div class="form-grid">
                        <label><span><?= e(t('contact_name', $lang)) ?> *</span><input name="name" value="<?= e(old('name')) ?>" required minlength="2" maxlength="120" autocomplete="name"></label>
                        <label><span><?= e(t('company_name', $lang)) ?></span><input name="company" value="<?= e(old('company')) ?>" maxlength="160" autocomplete="organization"></label>
                        <label class="full"><span><?= e(t('contact_method', $lang)) ?> *</span><input dir="auto" name="contact_method" value="<?= e(old('contact_method')) ?>" required minlength="5" maxlength="190" autocomplete="email"></label>
                        <label class="full"><span><?= e(t('service_required', $lang)) ?></span><select name="service"><option value=""><?= e(t('choose_service', $lang)) ?></option><?php foreach ($services as $service): ?><option value="<?= e($service['slug']) ?>"<?= $selectedService === $service['slug'] ? ' selected' : '' ?>><?= e($service['name']) ?></option><?php endforeach; ?></select></label>
                        <label class="full"><span><?= e(t('request_details', $lang)) ?> *</span><textarea name="details" required minlength="10" maxlength="5000" rows="6"><?= e(old('details')) ?></textarea></label>
                    </div>
                    <div class="form-submit"><button class="button" type="submit"><?= e(t('send_request', $lang)) ?></button><p><?= e(t('privacy_note', $lang)) ?></p></div>
                </form>
            </div>
        </div>
    </section>
</main>
<?php clear_old(); require ROOT_PATH . '/app/views/partials/footer.php'; ?>
