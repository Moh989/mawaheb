<?php $pageData = ['seo_title' => t('not_found_title', $lang) . ' | ' . t('site_name', $lang), 'seo_description' => t('not_found_text', $lang)]; $preview = false; require ROOT_PATH . '/app/views/partials/header.php'; ?>
<main id="main-content" class="not-found">
    <div class="container not-found-inner"><span>404</span><h1><?= e(t('not_found_title', $lang)) ?></h1><p><?= e(t('not_found_text', $lang)) ?></p><a class="button" href="<?= e(page_url($lang)) ?>"><?= e(t('back_home', $lang)) ?></a></div>
</main>
<?php require ROOT_PATH . '/app/views/partials/footer.php'; ?>

