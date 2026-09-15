import { writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

// Read-only public navigation checks: no login, form submission or DB edits.
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const browser = await chromium.launch({headless: true, executablePath: process.env.PLAYWRIGHT_EXECUTABLE || undefined});
const base = process.env.BASE_URL || 'http://127.0.0.1:8080';
const destination = fileURLToPath(new URL('../previews/', import.meta.url));
const results = [];
const check = (pass, label) => {
    results.push({pass: Boolean(pass), label});
    if (!pass) console.log('FAIL ' + label);
};
const slugs = ['cargo-customs-clearance', 'customs-documentation', 'customs-consultancy', 'shipment-monitoring', 'customs-dispute-resolution'];
const names = ['التخليص الكمركي للبضائع', 'إعداد المعاملات الكمركية', 'استشارات كمركية', 'متابعة الشحنات', 'تسوية القضايا الكمركية'];
const toggle = '[data-services-toggle]';
const dropdown = '[data-services-dropdown]';
const menu = '[data-services-menu]';
const mobileToggle = '[data-menu-toggle]';
const openServices = async page => {
    if (await page.locator(mobileToggle).isVisible() && await page.locator(mobileToggle).getAttribute('aria-expanded') !== 'true') await page.locator(mobileToggle).click();
    if (!(await page.locator(dropdown).evaluate(e => e.open))) await page.locator(toggle).click();
};
const noOverflow = page => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth);

try {
    const page = await browser.newPage({viewport: {width: 1440, height: 1000}, reducedMotion: 'reduce'});
    page.on('pageerror', error => check(false, 'JavaScript: ' + error.message));
    for (const width of [1440, 1024, 768, 390, 320]) {
        await page.setViewportSize({width, height: width >= 1024 ? 1000 : 844});
        for (const lang of ['ar', 'en', 'ku']) {
            for (const route of ['', 'about', 'services', 'contact']) {
                const prefix = `${width} ${lang}/${route || 'home'}`;
                const response = await page.goto(`${base}/${lang}/${route}`, {waitUntil: 'networkidle'});
                check(response.status() === 200, `${prefix}: HTTP 200`);
                check(await page.locator('.primary-nav > a, .primary-nav > details').count() === 4, `${prefix}: four top-level navigation entries`);
                check(!(await page.locator(dropdown).evaluate(e => e.open)), `${prefix}: dropdown initially closed`);
                await openServices(page);
                check(await page.locator(`${menu} a`).count() === 6, `${prefix}: overview and five services`);
                const hrefs = await page.locator(`${menu} a`).evaluateAll(links => links.map(a => new URL(a.href).pathname + new URL(a.href).hash));
                check(JSON.stringify(hrefs) === JSON.stringify([`/${lang}/services`, ...slugs.map(slug => `/${lang}/services#${slug}`)]), `${prefix}: correct language, order and detail anchors`);
                if (lang === 'ar') check(JSON.stringify(await page.locator('[data-service-link] > span:not(.nav-service-icon)').allTextContents()) === JSON.stringify(names), `${prefix}: requested service names`);
                check(await noOverflow(page), `${prefix}: open menu has no horizontal overflow`);
                check(await page.locator(menu).evaluate(e => {const r = e.getBoundingClientRect(); return r.left >= 0 && r.right <= innerWidth;}), `${prefix}: menu within screen edges`);
                if (route === '' && [1440, 1024, 390].includes(width)) {
                    await page.screenshot({path: `${destination}services-menu-${lang}-${width === 1440 ? 'desktop' : width === 390 ? 'mobile' : '1024'}.png`});
                }
                await page.keyboard.press('Escape');
                check(!(await page.locator(dropdown).evaluate(e => e.open)), `${prefix}: Escape closes services`);
                check(await page.locator(toggle).evaluate(e => e === document.activeElement), `${prefix}: Escape restores trigger focus`);
                if (width <= 860) {
                    check(await page.locator(mobileToggle).getAttribute('aria-expanded') === 'true', `${prefix}: first Escape keeps parent navigation open`);
                    await page.keyboard.press('Escape');
                    check(await page.locator('[data-nav]').isHidden(), `${prefix}: second Escape closes parent navigation`);
                }
            }
        }
    }

    // Follow every detail link on both desktop and phone, in every language.
    for (const width of [1440, 390]) {
        await page.setViewportSize({width, height: width === 1440 ? 1000 : 844});
        for (const lang of ['ar', 'en', 'ku']) {
            await page.goto(`${base}/${lang}/about`, {waitUntil: 'networkidle'});
            for (const slug of slugs) {
                await openServices(page);
                await page.locator(`[data-service-link][href$="#${slug}"]`).click();
                await page.waitForURL(`${base}/${lang}/services#${slug}`);
                await page.waitForFunction(id => {
                    const rect = document.getElementById(id)?.getBoundingClientRect();
                    return rect && rect.top >= document.querySelector('.site-header').getBoundingClientRect().bottom - 1 && rect.top < innerHeight;
                }, slug);
                check(await page.locator(`#${slug} h2, #${slug} h3`).count() > 0, `${width} ${lang} ${slug}: correct visible detail below sticky header`);
                check(!(await page.locator(dropdown).evaluate(e => e.open)), `${width} ${lang} ${slug}: dropdown closes after selection`);
                await page.waitForFunction(id => document.querySelector(`[data-service-link][href$="#${id}"]`)?.getAttribute('aria-current') === 'location', slug);
                check(await page.locator(`[data-service-link][href$="#${slug}"]`).getAttribute('aria-current') === 'location', `${width} ${lang} ${slug}: selected service highlighted`);
                if (width === 390) check(await page.locator('[data-nav]').isHidden(), `${width} ${lang} ${slug}: mobile parent closes after selection`);
            }
            await openServices(page);
            await page.locator('[data-services-overview]').click();
            await page.waitForURL(`${base}/${lang}/services`);
            check(new URL(page.url()).hash === '', `${width} ${lang}: overview displays full services page without fragment`);
        }
    }

    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto(`${base}/ar/services#customs-consultancy`, {waitUntil: 'networkidle'});
    await page.locator('[data-language-button]').click();
    await page.locator('[data-language-menu] a[lang="en"]').click();
    await page.waitForURL(`${base}/en/services#customs-consultancy`);
    check(await page.locator('#customs-consultancy').count() === 1, 'language switch preserves selected service detail');
    await page.locator(toggle).focus();
    await page.keyboard.press('ArrowDown');
    check(await page.locator('[data-services-overview]').evaluate(e => e === document.activeElement), 'ArrowDown opens dropdown and focuses overview');
    await page.keyboard.press('End');
    check(await page.locator('[data-service-link]').last().evaluate(e => e === document.activeElement), 'End focuses last service');
    await page.keyboard.press('ArrowDown');
    check(await page.locator('[data-services-overview]').evaluate(e => e === document.activeElement), 'ArrowDown wraps to first entry');
    await page.keyboard.press('ArrowUp');
    check(await page.locator('[data-service-link]').last().evaluate(e => e === document.activeElement), 'ArrowUp wraps to last entry');
    await page.keyboard.press('Home');
    check(await page.locator('[data-services-overview]').evaluate(e => e === document.activeElement), 'Home focuses first entry');
    await page.keyboard.press('End');
    await page.keyboard.press('Tab');
    check(!(await page.locator(dropdown).evaluate(e => e.open)), 'Tab leaves and closes dropdown without trapping focus');
    await page.locator(toggle).focus();
    await page.keyboard.press('ArrowUp');
    check(await page.locator('[data-service-link]').last().evaluate(e => e === document.activeElement), 'ArrowUp on trigger opens last service');
    await page.keyboard.press('Escape');
    await page.locator(toggle).press('Enter');
    check(await page.locator(dropdown).evaluate(e => e.open), 'Enter operates native dropdown trigger');
    await page.locator('h1').click();
    check(!(await page.locator(dropdown).evaluate(e => e.open)), 'click outside closes services dropdown');
    await openServices(page);
    await page.locator('[data-language-button]').click();
    check(!(await page.locator(dropdown).evaluate(e => e.open)) && await page.locator('[data-language-menu]').isVisible(), 'opening language menu closes services dropdown');
    await page.locator(toggle).click();
    await page.waitForFunction(() => document.querySelector('[data-language-menu]').hidden);
    check(await page.locator(dropdown).evaluate(e => e.open), 'opening services closes language menu');
    await page.setViewportSize({width: 390, height: 844});
    check(!(await page.locator(dropdown).evaluate(e => e.open)), 'breakpoint change resets dropdown state');
    await openServices(page);
    await page.setViewportSize({width: 390, height: 568});
    await page.locator('[data-service-link]').last().click();
    await page.waitForURL(`${base}/en/services#customs-dispute-resolution`);
    check(true, 'last service accessible in short mobile viewport');

    const touch = await browser.newPage({viewport: {width: 390, height: 844}, hasTouch: true, isMobile: true, reducedMotion: 'reduce'});
    await touch.goto(`${base}/ar/`, {waitUntil: 'networkidle'});
    await touch.locator(mobileToggle).tap();
    await touch.locator(toggle).tap();
    await touch.locator('[data-service-link]').first().tap();
    await touch.waitForURL(`${base}/ar/services#cargo-customs-clearance`);
    check(await touch.locator('[data-nav]').isHidden(), 'touch opens dropdown and follows service link');
    await touch.close();

    await page.emulateMedia({reducedMotion: 'no-preference'});
    await page.goto(`${base}/ar/services#customs-consultancy`, {waitUntil: 'networkidle'});
    await page.waitForFunction(() => getComputedStyle(document.getElementById('customs-consultancy')).opacity === '1');
    check(await page.locator('#customs-consultancy').evaluate(e => e === document.activeElement), 'deep link reveals service and moves keyboard focus with normal motion');
    await page.emulateMedia({reducedMotion: 'reduce'});

    await page.goto(`${base}/ar/not-a-real-page`, {waitUntil: 'networkidle'});
    await openServices(page);
    check(await page.locator('[data-service-link]').count() === 5, '404 page retains published services dropdown');
    for (const width of [1440, 390]) {
        const nojs = await browser.newPage({viewport: {width, height: 844}, javaScriptEnabled: false});
        await nojs.goto(`${base}/ar/`, {waitUntil: 'networkidle'});
        await nojs.locator(toggle).click();
        check(await nojs.locator('[data-service-link]').last().isVisible(), `${width}: native dropdown works without JavaScript`);
        check(await noOverflow(nojs), `${width}: no-JavaScript dropdown without horizontal overflow`);
        await nojs.locator('[data-service-link]').last().click();
        await nojs.waitForURL(`${base}/ar/services#customs-dispute-resolution`);
        await nojs.waitForLoadState('networkidle');
        check(await nojs.locator('#customs-dispute-resolution').isVisible(), `${width}: service detail navigation works without JavaScript`);
        check(await nojs.evaluate(() => {
            const target = document.getElementById('customs-dispute-resolution').getBoundingClientRect();
            return target.top >= Math.max(0, document.querySelector('.site-header').getBoundingClientRect().bottom) && target.top < innerHeight;
        }), `${width}: no-JavaScript header does not cover service destination`);
        await nojs.close();
    }
} catch (error) {
    check(false, error.stack || String(error));
} finally {
    await browser.close();
    const report = {date: new Date().toISOString(), total: results.length, passed: results.filter(r => r.pass).length, results};
    await writeFile(`${destination}navigation-checks.json`, JSON.stringify(report, null, 2));
    console.log(JSON.stringify({total: report.total, passed: report.passed, failures: results.filter(r => !r.pass)}, null, 2));
    process.exitCode = results.some(r => !r.pass) ? 1 : 0;
}
