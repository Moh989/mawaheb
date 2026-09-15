import { writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const base = process.env.BASE_URL || 'http://127.0.0.1:8080';
const results = [];
const check = (pass, label) => { results.push({pass:!!pass,label}); if (!pass) console.log('FAIL '+label); };
const browser = await chromium.launch({headless:true,executablePath:process.env.PLAYWRIGHT_EXECUTABLE || undefined});
try {
    const page = await browser.newPage({viewport:{width:1440,height:1000},reducedMotion:'no-preference'});
    page.on('pageerror', error => check(false,error.message));
    const label = () => page.locator('[data-slider-current]').textContent();
    const waitSlide = index => page.waitForFunction(i => document.querySelector('[data-slider-current]').textContent === i, index, {timeout:20000});
    const play = index => page.waitForFunction(i => { const v=document.querySelectorAll('video')[i]; return !v.paused && v.currentTime > .05; },index);
    await page.goto(`${base}/ar/`,{waitUntil:'networkidle'});
    await page.mouse.move(700,300);
    await play(0);
    check(await page.locator('video').evaluateAll(vs=>vs.every(v=>!v.loop)), 'video looping disabled');
    // Deliberately make the fallback timer shorter than the clips. It must not
    // drive a playable video or cut it off while decoding/buffering is paused.
    await page.locator('[data-slide]').evaluateAll(ss=>ss.forEach(s=>s.dataset.duration='4000'));
    await page.locator('video').first().evaluate(v=>v.pause());
    await page.waitForTimeout(5000);
    check(await label()==='01','video is not advanced by the image fallback timer');
    await page.locator('video').first().evaluate(v=>v.play());
    await waitSlide('02');
    check(await page.locator('video').first().evaluate(v=>v.ended && Math.abs(v.duration-v.currentTime)<.1),'first video plays to its real end before advancing, with pointer over carousel');
    await play(1);
    await page.locator('[data-slider-next]').focus();
    await waitSlide('03');
    check(await page.locator('video').nth(1).evaluate(v=>v.ended),'second video ends and advances while persistent control has keyboard focus');
    await waitSlide('01');
    await play(0);
    check(await page.locator('video').first().evaluate(v=>v.currentTime<2),'image timer returns to first video from the beginning');

    await page.locator('video').first().evaluate(v=>{v.currentTime=v.duration-1;});
    await page.locator('[data-slider-pause]').click();
    await page.waitForTimeout(1500);
    check(await label()==='01' && await page.locator('video').first().evaluate(v=>v.paused && !v.ended),'manual pause near end prevents transition');
    await page.locator('[data-slider-pause]').click();
    await waitSlide('02');
    check(true,'resume completes remaining video and advances');

    await page.locator('[data-slider-previous]').click();
    await play(0);
    check(await page.locator('video').first().evaluate(v=>v.currentTime<2),'manual return restarts completed video');
    await page.locator('.hero-slide.is-active .button').first().focus();
    check(await page.locator('video').first().evaluate(v=>v.paused),'keyboard focus within slide content pauses to preserve focused link');
    await page.locator('.hero-slide.is-active .button').first().blur();
    await play(0);
    check(true,'leaving slide content resumes video');
    await page.locator('video').first().evaluate(v=>{v.currentTime=v.duration/2;});
    await page.waitForFunction(()=>Number(document.querySelector('[data-slider-progress]').style.transform.match(/scaleX\(([^)]+)\)/)?.[1])>.45);
    check(true,'progress indicator follows actual video position');

    await page.goto(`${base}/ku/`,{waitUntil:'networkidle'});
    await page.setViewportSize({width:390,height:844});
    await play(0);
    await page.locator('video').first().evaluate(v=>{v.currentTime=v.duration-.3;});
    await waitSlide('02');
    check(true,'mobile Sorani advances from actual ended event');

    const blocked = await browser.newContext({viewport:{width:390,height:844}});
    await blocked.addInitScript(()=>{HTMLMediaElement.prototype.play=()=>Promise.reject(new DOMException('Test policy','NotAllowedError'));});
    const fallback=await blocked.newPage();
    await fallback.goto(`${base}/en/`,{waitUntil:'networkidle'});
    await fallback.waitForFunction(()=>document.querySelector('[data-slider-current]').textContent==='02',null,{timeout:10000});
    check(await fallback.locator('.hero-slide.is-active .hero-slide-image').evaluate(i=>i.complete && i.naturalWidth>0),'blocked autoplay still advances poster slides');
    await blocked.close();
} catch(error) { check(false,error.stack || String(error)); }
finally {
    await browser.close();
    const report={date:new Date().toISOString(),total:results.length,passed:results.filter(r=>r.pass).length,results};
    await writeFile(fileURLToPath(new URL('../previews/video-ended-checks.json',import.meta.url)),JSON.stringify(report,null,2));
    console.log(JSON.stringify({total:report.total,passed:report.passed,failures:results.filter(r=>!r.pass)},null,2));
    process.exitCode=results.some(r=>!r.pass)?1:0;
}
