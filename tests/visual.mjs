import { fileURLToPath } from 'node:url';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
import { writeFile } from 'node:fs/promises';
const browser = await chromium.launch({executablePath:process.env.PLAYWRIGHT_EXECUTABLE || undefined,headless:true});
const checks=[];
const problems=[];
const base=process.env.BASE_URL || 'http://127.0.0.1:8080';
const destination=fileURLToPath(new URL('../previews',import.meta.url));
const assert=(value,label)=>{checks.push({label,pass:!!value});if(!value)problems.push(label);};
const page=await browser.newPage({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
page.on('pageerror',error=>problems.push('JavaScript: '+error.message));
page.on('response',response=>{if(response.status()>=400)problems.push(response.status()+': '+response.url());});
for(const width of [1440,768,390,320]){
  await page.setViewportSize({width,height:width===1440?1000:900});
  for(const lang of ['ar','en','ku']){
    for(const route of ['','about','services','contact']){
      const name=route||'home';
      const response=await page.goto(`${base}/${lang}/${route}`,{waitUntil:'networkidle'});
      await page.evaluate(()=>document.fonts.ready);
      const layout=await page.evaluate(()=>({
        width:innerWidth,scroll:document.documentElement.scrollWidth,
        broken:[...document.images].filter(i=>i.loading!=='lazy'&&(!i.complete||i.naturalWidth===0)).map(i=>i.src),
        h1:document.querySelectorAll('h1').length,
        dir:document.documentElement.dir,
        font:document.documentElement.lang==='en'?getComputedStyle(document.body).fontFamily.includes('Helvetica'):document.fonts.check('16px "Mawaheb Arabic"'),
        overflow:[...document.querySelectorAll('main *')].filter(e=>{const r=e.getBoundingClientRect();return r.width>0&&(r.right>innerWidth+2||r.left< -2)&&getComputedStyle(e).position!=='absolute';}).slice(0,10).map(e=>e.className)
      }));
      assert(response.status()===200,`${width} ${lang}/${name}: HTTP 200`);
      assert(layout.scroll<=layout.width,`${width} ${lang}/${name}: no horizontal overflow (${layout.scroll}) ${layout.overflow.join(',')}`);
      assert(layout.broken.length===0,`${width} ${lang}/${name}: images loaded`);
      assert(layout.h1===1,`${width} ${lang}/${name}: one H1`);
      assert(layout.dir===(lang==='en'?'ltr':'rtl'),`${width} ${lang}/${name}: direction`);
      assert(layout.font,`${width} ${lang}/${name}: correct language font available`);
      if(lang==='ar'&&[1440,390].includes(width))await page.screenshot({path:`${destination}/${name}-${width===1440?'desktop':'mobile'}.png`,fullPage:true});
      if(lang==='ar'&&width===768)await page.screenshot({path:`${destination}/${name}-tablet.png`,fullPage:true});
      if(lang==='ar'&&name==='home'&&[1440,390].includes(width))await page.screenshot({path:`${destination}/home-${width}-first-screen.png`});
      if(lang!=='ar'&&name==='home'&&[1440,390].includes(width))await page.screenshot({path:`${destination}/${name}-${lang}-${width}.png`,fullPage:true});
    }
  }
}
await page.setViewportSize({width:1440,height:1000});
await page.goto(`${base}/ar/`);
await page.locator('[data-slider-next]').click();
assert(await page.locator('[data-slider-current]').textContent()==='02','slider next');
await page.locator('[data-slider-previous]').click();
assert(await page.locator('[data-slider-current]').textContent()==='01','slider previous');
await page.locator('[data-slider-dot]').nth(2).click();
assert(await page.locator('[data-slider-current]').textContent()==='03','slider pagination');
await page.keyboard.press('ArrowLeft');
assert(await page.locator('[data-slider-current]').textContent()==='01','RTL keyboard slider wraps');
assert(await page.locator('[data-slider-pause]').isHidden(),'reduced motion hides autoplay control');
await page.locator('.service-disclosure').nth(1).locator('summary').click();
assert(await page.locator('.service-disclosure').nth(1).getAttribute('open')!==null,'service details open');
await page.locator('.service-disclosure').nth(1).locator('.text-link').click();
assert(await page.locator('select[name=service]').inputValue()!=='','service inquiry preselects service');
const service=await page.locator('select[name=service]').inputValue();
await page.locator('[data-language-button]').click();
await page.locator('[data-language-menu] a[lang=en]').click();
assert(page.url().includes('/en/contact')&&await page.locator('select[name=service]').inputValue()===service,'language switch retains page and selected service');
await page.locator('[data-language-button]').click();
await page.keyboard.press('Escape');
assert(await page.locator('[data-language-menu]').isHidden(),'language menu Escape');
await page.locator('[data-language-button]').click();
await page.locator('h1').click();
assert(await page.locator('[data-language-menu]').isHidden(),'language menu outside click');
assert(await page.locator('#contact-form form').evaluate(f=>!f.checkValidity()),'empty form validation, no request sent');
await page.setViewportSize({width:390,height:844});
await page.locator('[data-menu-toggle]').click();
assert(await page.locator('[data-nav]').isVisible(),'mobile navigation opens');
await page.keyboard.press('Escape');
assert(await page.locator('[data-nav]').isHidden(),'mobile navigation Escape');
await page.emulateMedia({reducedMotion:'no-preference'});
await page.goto(`${base}/ar/`,{waitUntil:'networkidle'});
await page.locator('[data-slider-pause]').click();
assert(await page.locator('[data-slider-pause]').getAttribute('aria-pressed')==='true','autoplay pause control');
const noscript=await browser.newPage({viewport:{width:390,height:844},javaScriptEnabled:false});
await noscript.goto(`${base}/ar/`,{waitUntil:'networkidle'});
assert(await noscript.locator('.story-copy').isVisible(),'no-JS main copy visible');
assert(await noscript.locator('[data-nav]').isVisible(),'no-JS navigation visible');
assert(await noscript.locator('[data-language-menu]').isVisible(),'no-JS language links visible');
assert(await noscript.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'no-JS no horizontal overflow');
await noscript.screenshot({path:`${destination}/home-mobile-no-js.png`,fullPage:true});
await browser.close();
const result={date:new Date().toISOString(),checks:checks.length,passed:checks.filter(c=>c.pass).length,problems,details:checks};
await writeFile(`${destination}/visual-checks.json`,JSON.stringify(result,null,2));
console.log(JSON.stringify({checks:result.checks,passed:result.passed,problems},null,2));
process.exitCode=problems.length?1:0;
