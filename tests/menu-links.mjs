import { writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

// Public-only regression for WebKit's null-relatedTarget pointer focusout.
// Run separately for webkit and chromium; no user browser profiles or DB writes.
const playwright = await import(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const engine = process.env.MENU_ENGINE || 'webkit';
if (!['webkit', 'chromium'].includes(engine)) throw new Error('MENU_ENGINE must be webkit or chromium');
const browser = await playwright[engine].launch({headless: true, executablePath: process.env.PLAYWRIGHT_EXECUTABLE || undefined});
const base = process.env.BASE_URL || 'http://127.0.0.1:8080';
const destination = fileURLToPath(new URL('../previews/', import.meta.url));
const results = [];
const check = (pass, label) => {
    results.push({pass: Boolean(pass), label});
    if (!pass) throw new Error(label);
};
const menu = '[data-services-dropdown]';
const toggle = '[data-services-toggle]';
const links = '[data-services-menu] a';
const mobile = '[data-menu-toggle]';
const openMenu = async (page, touch = false) => {
    const activate = async locator => touch ? locator.tap() : locator.click();
    if (await page.locator(mobile).isVisible() && await page.locator(mobile).getAttribute('aria-expanded') !== 'true') await activate(page.locator(mobile));
    if (!(await page.locator(menu).evaluate(e => e.open))) await activate(page.locator(toggle));
};
const waitDestination = async (page, expected) => {
    await page.waitForURL(expected, {timeout: 5000});
    if (new URL(expected).hash) await page.waitForFunction(() => {
        const element = document.getElementById(location.hash.slice(1));
        if (!element) return false;
        const r = element.getBoundingClientRect();
        return r.top >= document.querySelector('.site-header').getBoundingClientRect().bottom - 1 && r.top < innerHeight && getComputedStyle(element).opacity === '1';
    }, null, {timeout: 5000});
};

try {
    for (const width of [1440, 390]) {
        const page = await browser.newPage({viewport: {width, height: 900}, reducedMotion: 'reduce'});
        page.on('pageerror', error => results.push({pass: false, label: error.message}));
        for (const lang of ['ar', 'en', 'ku']) {
            await page.goto(`${base}/${lang}/`, {waitUntil: 'networkidle'});
            const destinations = await page.locator(links).evaluateAll(as => as.map(a => a.href));
            for (let index = 0; index < destinations.length; index++) {
                // Include source pages other than the services page, then exercise
                // consecutive same-page fragment changes for the other details.
                if (index === 1) await page.goto(`${base}/${lang}/about`, {waitUntil: 'networkidle'});
                if (index === 4) await page.goto(`${base}/${lang}/contact`, {waitUntil: 'networkidle'});
                await openMenu(page);
                const target = page.locator(links).nth(index);
                await target.scrollIntoViewIfNeeded();
                const box = await target.boundingBox();
                await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
                await page.mouse.down();
                await page.waitForTimeout(80);
                check(await page.locator(menu).evaluate(e => e.open), `${width} ${lang} link ${index}: remains open between pointerdown and pointerup`);
                await page.mouse.up();
                await waitDestination(page, destinations[index]);
                check(true, `${width} ${lang} link ${index}: native click reaches expected destination`);
                check(!(await page.locator(menu).evaluate(e => e.open)), `${width} ${lang} link ${index}: closes after activation`);
                if (width === 390) check(await page.locator('[data-nav]').isHidden(), `${width} ${lang} link ${index}: parent mobile menu closes after activation`);
            }
            await openMenu(page);
            await page.screenshot({path: `${destination}menu-links-${engine}-${lang}-${width}.png`});
            await page.keyboard.press('Escape');
            check(await page.locator(toggle).evaluate(e => e === document.activeElement), `${width} ${lang}: Escape returns focus`);
        }
        await page.close();
    }

    const keyboard = await browser.newPage({viewport: {width: 1440, height: 900}, reducedMotion: 'reduce'});
    await keyboard.goto(`${base}/ar/`, {waitUntil: 'networkidle'});
    await keyboard.locator(toggle).focus();
    await keyboard.keyboard.press('ArrowDown');
    await keyboard.keyboard.press('ArrowDown');
    await keyboard.keyboard.press('Enter');
    await waitDestination(keyboard, `${base}/ar/services#cargo-customs-clearance`);
    check(true, 'keyboard activation still opens service details');
    await keyboard.locator(toggle).focus();
    await keyboard.keyboard.press('ArrowUp');
    await keyboard.keyboard.press('Tab');
    await keyboard.locator('[data-services-menu]').waitFor({state: 'hidden', timeout: 3000});
    check(!(await keyboard.locator(menu).evaluate(e => e.open)), 'Tab leaving dropdown still closes it');
    await openMenu(keyboard);
    await keyboard.locator('h1').click();
    check(!(await keyboard.locator(menu).evaluate(e => e.open)), 'outside click still closes dropdown');
    await openMenu(keyboard);
    await keyboard.locator('[data-language-button]').click();
    await keyboard.locator('[data-language-menu] a[lang="en"]').click();
    await waitDestination(keyboard, `${base}/en/services#cargo-customs-clearance`);
    check(true, 'language dropdown still preserves selected service');
    await keyboard.close();

    const touch = await browser.newPage({viewport: {width: 390, height: 844}, hasTouch: true, isMobile: true, reducedMotion: 'reduce'});
    for (const lang of ['ar', 'en', 'ku']) {
        await touch.goto(`${base}/${lang}/`, {waitUntil: 'networkidle'});
        const destinations = await touch.locator(links).evaluateAll(as => as.map(a => a.href));
        for (let index = 0; index < destinations.length; index++) {
            await openMenu(touch, true);
            await touch.locator(links).nth(index).tap();
            await waitDestination(touch, destinations[index]);
            check(await touch.locator('[data-nav]').isHidden(), `${lang} link ${index}: touch navigation reaches destination and closes menu`);
        }
    }
    await touch.close();
} catch (error) {
    results.push({pass: false, label: error.stack || String(error)});
} finally {
    await browser.close();
    const report = {date: new Date().toISOString(), engine, total: results.length, passed: results.filter(r => r.pass).length, results};
    await writeFile(`${destination}menu-links-${engine}.json`, JSON.stringify(report, null, 2));
    console.log(JSON.stringify({engine, total: report.total, passed: report.passed, failures: results.filter(r => !r.pass)}, null, 2));
    process.exitCode = results.some(r => !r.pass) ? 1 : 0;
}
