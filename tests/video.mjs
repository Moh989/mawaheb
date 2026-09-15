import { fileURLToPath } from 'node:url';
import { readFile, writeFile } from 'node:fs/promises';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = process.env.BASE_URL || 'http://127.0.0.1:8080';
const output = fileURLToPath(new URL('../previews', import.meta.url));
const results = [];
const check = (pass, label) => { results.push({pass: Boolean(pass), label}); if (!pass) console.log('FAIL ' + label); };
let browser;

try {
    for (const name of ['bg-1080', 'bg-720', 'bg2-1080', 'bg2-720']) {
        const bytes = await readFile(new URL(`../public/assets/video/${name}.mp4`, import.meta.url));
        const url = `${base}/assets/video/${name}.mp4`;
        const partial = await fetch(url, {headers: {Range: 'bytes=0-1023'}});
        check(partial.status === 206 && Buffer.from(await partial.arrayBuffer()).equals(bytes.subarray(0, 1024)), `${name}: correct first byte range`);
        const suffix = await fetch(url, {headers: {Range: 'bytes=-128'}});
        check(suffix.status === 206 && Buffer.from(await suffix.arrayBuffer()).equals(bytes.subarray(-128)), `${name}: correct suffix range`);
        const tail = await fetch(url, {headers: {Range: `bytes=${bytes.length - 8}-`}});
        check(tail.status === 206 && Buffer.from(await tail.arrayBuffer()).equals(bytes.subarray(-8)), `${name}: open-ended range`);
        const head = await fetch(url, {method: 'HEAD'});
        check(head.status === 200 && Number(head.headers.get('content-length')) === bytes.length && head.headers.get('content-type') === 'video/mp4', `${name}: HEAD and MIME`);
    }
    for (const range of ['bytes=999999999-', 'bytes=20-10', 'bytes=-0', 'bytes=-', 'invalid', 'bytes=0-2,6-9']) {
        const response = await fetch(`${base}/assets/video/bg-1080.mp4`, {headers: {Range: range}});
        check(response.status === 416, `reject invalid or unsupported range: ${range}`);
    }
    check((await fetch(`${base}/assets/video/bg-1080.mp4`, {method: 'POST'})).status === 405, 'MP4 route rejects POST');

    browser = await chromium.launch({headless: true, executablePath: process.env.PLAYWRIGHT_EXECUTABLE || undefined});
    const page = await browser.newPage({reducedMotion: 'no-preference'});
    page.on('pageerror', error => check(false, 'JavaScript: ' + error.message));
    let requested = [];
    page.on('request', request => { if (request.url().endsWith('.mp4')) requested.push(request.url()); });
    const playing = async index => page.waitForFunction(i => {
        const video = document.querySelectorAll('[data-slide-video]')[i];
        return video && !video.paused && video.readyState >= 2 && video.currentTime > 0.1;
    }, index, {timeout: 20000});
    const capture = async name => {
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.waitForFunction(() => {
            const slide = document.querySelector('.hero-slide.is-active');
            return Number(getComputedStyle(slide).opacity) === 1 && [...slide.querySelectorAll('.hero-slide-copy > *')].every(el => Number(getComputedStyle(el).opacity) === 1);
        });
        await page.screenshot({path:`${output}/${name}.png`});
    };

    for (const width of [1440, 768, 390]) {
        for (const lang of ['ar', 'en', 'ku']) {
            requested = [];
            await page.setViewportSize({width, height: 1000});
            const response = await page.goto(`${base}/${lang}/`, {waitUntil: 'networkidle'});
            await page.mouse.move(width / 2, 300);
            await playing(0);
            const state = await page.locator('[data-slide-video]').first().evaluate(v => ({width:v.videoWidth, muted:v.muted, inline:v.playsInline, duration:v.duration, src:v.currentSrc}));
            check(response.status() === 200 && state.muted && state.inline && state.duration > 9, `${lang} ${width}: silent inline autoplay`);
            check(state.width === (width <= 860 ? 1280 : 1920), `${lang} ${width}: appropriate video resolution`);
            check(!requested.some(url => url.includes('/bg2-')), `${lang} ${width}: inactive video not downloaded`);
            check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${lang} ${width}: no horizontal overflow`);
            if (lang === 'ar') await capture(`video-home-${width}`);
            await page.locator('[data-slider-pause]').click();
            check(await page.locator('[data-slide-video]').first().evaluate(v => v.paused), `${lang} ${width}: pause stops video`);
            await page.locator('[data-slider-pause]').click();
            await playing(0);
            check(true, `${lang} ${width}: resume video`);
            await page.locator('[data-slider-next]').click();
            await playing(1);
            check(await page.locator('[data-slide-video]').first().evaluate(v => v.paused), `${lang} ${width}: first video stops on slide change`);
            check((await page.locator('[data-slide-video]').nth(1).evaluate(v => v.currentSrc)).includes('/bg2-'), `${lang} ${width}: second video plays in slider`);
            if (lang === 'ar') await capture(`video-slide-2-${width}`);
            await page.locator('[data-slider-next]').click();
            check(await page.locator('[data-slide-video]').evaluateAll(videos => videos.every(v => v.paused)), `${lang} ${width}: image slide pauses both videos`);
            await page.locator('[data-slider-previous]').click();
            await playing(1);
            await page.evaluate(() => window.scrollTo(0, 2200));
            await page.waitForFunction(() => [...document.querySelectorAll('[data-slide-video]')].every(v => v.paused));
            check(true, `${lang} ${width}: offscreen video pauses`);
            await page.evaluate(() => window.scrollTo(0, 0));
            await playing(1);
            check(true, `${lang} ${width}: visible video resumes`);
        }
    }

    await page.setViewportSize({width:1440,height:1000});
    await page.mouse.move(0,0);
    await page.goto(`${base}/ar/`, {waitUntil:'networkidle'});
    await page.waitForFunction(() => document.querySelector('[data-slider-current]').textContent === '02', null, {timeout:16000});
    await playing(1);
    check(await page.locator('[data-slide-video]').first().evaluate(v => v.paused && v.ended), 'video ending advances and switches the playing video');
    await page.locator('[data-slider-pause]').click();
    await page.locator('[data-slider-pause]').blur();
    await page.mouse.move(0,0);
    // Wait a full slide interval to verify pause does not merely stop the image.
    await page.waitForTimeout(11500);
    check(await page.locator('[data-slider-current]').textContent() === '02' && await page.locator('[data-slide-video]').evaluateAll(vs => vs.every(v => v.paused)), 'manual pause keeps both timer and video stopped');

    const reduced = await browser.newPage({reducedMotion:'reduce'});
    let reducedRequests = 0;
    reduced.on('request', r => { if (r.url().endsWith('.mp4')) reducedRequests++; });
    await reduced.goto(`${base}/ar/`, {waitUntil:'networkidle'});
    check(reducedRequests === 0 && await reduced.locator('[data-slide-video]').evaluateAll(vs => vs.every(v => !v.getAttribute('src'))), 'reduced motion downloads no video');
    check(await reduced.locator('[data-slider-pause]').isHidden(), 'reduced-motion control state');
    await reduced.emulateMedia({reducedMotion:'no-preference'});
    await reduced.waitForFunction(() => !document.querySelector('video').paused && document.querySelector('video').currentTime > 0);
    await reduced.emulateMedia({reducedMotion:'reduce'});
    await reduced.waitForFunction(() => document.querySelector('video').paused);
    check(true, 'live motion preference pauses and resumes playback');

    for (const mode of ['save-data', 'blocked-autoplay', 'network-failure', 'no-js']) {
        const context = await browser.newContext({javaScriptEnabled: mode !== 'no-js', viewport:{width:390,height:844}});
        if (mode === 'save-data') await context.addInitScript(() => Object.defineProperty(navigator, 'connection', {value:{saveData:true}, configurable:true}));
        if (mode === 'blocked-autoplay') await context.addInitScript(() => { HTMLMediaElement.prototype.play = () => Promise.reject(new DOMException('Blocked for test', 'NotAllowedError')); });
        if (mode === 'network-failure') await context.route('**/assets/video/*.mp4', route => route.abort());
        const fallback = await context.newPage();
        let videoRequests = 0;
        fallback.on('request', r => { if (r.url().endsWith('.mp4')) videoRequests++; });
        fallback.on('pageerror', error => check(false, `${mode}: ${error.message}`));
        await fallback.goto(`${base}/ar/`, {waitUntil:'networkidle'});
        check(await fallback.locator('.hero-slide.is-active .hero-slide-image').evaluate(i => i.complete && i.naturalWidth > 0), `${mode}: fallback image loads`);
        check(await fallback.locator('.hero-slide.is-active video').evaluate(v => getComputedStyle(v).opacity === '0'), `${mode}: unavailable video does not hide poster`);
        if (['no-js','save-data'].includes(mode)) check(videoRequests === 0, `${mode}: no video download`);
        check(await fallback.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${mode}: no overflow`);
        await context.close();
    }
} catch (error) {
    check(false, error.stack || String(error));
} finally {
    await browser?.close();
    const report = {date: new Date().toISOString(), total:results.length, passed:results.filter(r=>r.pass).length, results};
    await writeFile(`${output}/video-checks.json`, JSON.stringify(report,null,2));
    console.log(JSON.stringify({total:report.total,passed:report.passed,failures:results.filter(r=>!r.pass)},null,2));
    process.exitCode = results.some(r=>!r.pass) ? 1 : 0;
}
