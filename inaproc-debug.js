const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    page.on('response', response => {
        if (response.status() >= 400) {
            console.log('HTTP ERROR:', response.status(), response.url());
        }
    });

    page.on('requestfailed', request => {
        console.log('REQUEST FAILED:', request.url(), request.failure()?.errorText);
    });

    page.on('console', msg => {
        if (msg.type() === 'error') console.log('CONSOLE ERROR:', msg.text());
    });

    await page.goto(
        'https://data.inaproc.id/profil-pengadaan?tahun=2026&jenis=3&instansi=D1&view=Nilai',
        { waitUntil: 'domcontentloaded', timeout: 90000 }
    );

    await page.waitForTimeout(20000);

    console.log('\n===== PAGE TEXT =====');
    console.log(await page.locator('body').innerText());

    await browser.close();
})();