const { chromium } = require('playwright');

function arg(name, fallback = '') {
    const prefix = `--${name}=`;
    const value = process.argv.find((item) => item.startsWith(prefix));

    return value ? value.slice(prefix.length) : fallback;
}

async function main() {
    const url = arg('url');
    const timeout = Number(arg('timeout', '90000'));
    const waitText = arg('wait-text', 'Realisasi Swakelola');

    if (!url) {
        throw new Error('Missing --url argument');
    }

    const browser = await chromium.launch({
        headless: process.env.INAPROC_REKAP_HEADLESS !== 'false',
    });

    try {
        const page = await browser.newPage({
            viewport: { width: 1366, height: 900 },
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125 Safari/537.36',
        });

        await page.goto(url, { waitUntil: 'domcontentloaded', timeout });
        await page.waitForFunction(
            (text) => document.body && document.body.innerText.includes(text),
            waitText,
            { timeout }
        );

        const innerText = await page.evaluate(() => document.body.innerText);
        process.stdout.write(JSON.stringify({ url, innerText }));
    } finally {
        await browser.close();
    }
}

main().catch((error) => {
    process.stderr.write(error && error.stack ? error.stack : String(error));
    process.exit(1);
});
