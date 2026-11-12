/**
 * Lighthouse, run here, the way PageSpeed Insights runs it.
 *
 *   node lighthouse-run.mjs <site-url> <out-dir> [route,route,...]
 *
 * Google's keyless PageSpeed quota is shared by everyone and is used up most
 * days, and PageSpeed cannot reach a local site at all. This runs the same
 * engine (Lighthouse 13: Performance, Accessibility, Best practices, SEO and
 * Agentic browsing) with PageSpeed's own settings - mobile on a throttled
 * Moto G Power over slow 4G, desktop unthrottled - and writes each full
 * report as <out-dir>/<route-slug>.<mobile|desktop>.json.
 *
 * The plugin reads those files with ldrnseo_lh_import_dir(), through the same
 * reducer it uses for a PageSpeed Insights API response, so a local run and a
 * Google run look identical on the Speed tab.
 *
 * Needs: npm i lighthouse chrome-launcher, and a Chrome (CHROME_PATH), or
 * PUPPETEER=<path to puppeteer> where chrome-launcher cannot read the port.
 * RUNS=3 keeps the median run by Performance score, as Google recommends.
 * DEVICES=mobile or DEVICES=desktop runs only one form factor.
 */
import fs from 'node:fs';
import path from 'node:path';
import lighthouse from 'lighthouse';
import * as chromeLauncher from 'chrome-launcher';
import desktopConfig from 'lighthouse/core/config/desktop-config.js';

const [site, outDir, only] = process.argv.slice(2);
if (!site || !outDir) {
  console.error('usage: node lighthouse-run.mjs <site-url> <out-dir> [routes]');
  process.exit(1);
}
fs.mkdirSync(outDir, { recursive: true });
const routes = (only || '/').split(',');
const devices = (process.env.DEVICES || 'mobile,desktop').split(',');
const categories = ['performance', 'accessibility', 'best-practices', 'seo', 'agentic-browsing'];
const slug = (r) => (r === '/' ? 'home' : r.replace(/^\/|\/$/g, '').replace(/[^a-z0-9]+/gi, '-'));

async function startChrome() {
  if (process.env.PUPPETEER) {
    const puppeteer = (await import(process.env.PUPPETEER)).default;
    const port = 9300 + Math.floor(Math.random() * 400);
    const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox', `--remote-debugging-port=${port}`, ...(process.env.CHROME_ARGS || '').split(' ').filter(Boolean)] });
    return { port, kill: () => browser.close() };
  }
  return chromeLauncher.launch({ chromePath: process.env.CHROME_PATH, chromeFlags: ['--headless=new', '--no-sandbox'] });
}

const n = Math.max(1, parseInt(process.env.RUNS || '1', 10));
for (const route of routes) {
  for (const strategy of devices) {
    // A fresh Chrome per report: nothing cached from the page before, and the
    // memory goes back between runs.
    const chrome = await startChrome();
    try {
      const url = site.replace(/\/$/, '') + route;
      const opts = { port: chrome.port, output: 'json', logLevel: 'error', onlyCategories: categories };
      const lhrs = [];
      for (let i = 0; i < n; i++) lhrs.push((await lighthouse(url, opts, strategy === 'desktop' ? desktopConfig : undefined)).lhr);
      lhrs.sort((x, y) => (x.categories.performance?.score ?? 0) - (y.categories.performance?.score ?? 0));
      const median = lhrs[Math.floor(lhrs.length / 2)];
      median.inuSpread = lhrs.map((l) => Math.round((l.categories.performance?.score ?? 0) * 100));
      median.inuRoute = route;
      median.inuStrategy = strategy;
      fs.writeFileSync(path.join(outDir, `${slug(route)}.${strategy}.json`), JSON.stringify(median));
      console.log(route, strategy, Object.values(median.categories).map((c) => Math.round((c.score ?? 0) * 100)).join(' / '));
    } catch (e) {
      console.log(route, strategy, 'FAILED', e.message);
    } finally {
      await chrome.kill();
    }
  }
}
console.log('done', outDir);
