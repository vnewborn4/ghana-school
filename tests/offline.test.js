/*
 * Browser tests for the academy's offline support.
 *
 * Drives a real Chromium through a real dropped connection: registers the
 * service worker, goes offline, checks what is and is not served from the
 * cache, queues work, comes back online, and checks the work arrived.
 *
 *   node tests/offline.test.js
 *
 * Needs a dev server on BASE_URL. Use localhost, not 127.0.0.1: the browser
 * only treats localhost as a secure context, and without that there are no
 * service workers to test. Needs one learner with a known username and PIN,
 * and at least one published assignment that takes a file, for the upload
 * check (tests/run-offline-tests.sh sets both up).
 *
 *   BASE_URL      default http://localhost:8099
 *   LEARNER/PIN   default ama-x9 / 4821
 */
'use strict';

/*
 * Playwright is a development dependency, not part of the site. Look for it
 * where npm would put it, and say plainly what to do if it is missing rather
 * than failing with a stack trace.
 */
let chromium;
try {
  ({ chromium } = require('playwright'));
} catch (error) {
  try {
    ({ chromium } = require(process.env.PLAYWRIGHT_PATH || '/tmp/pwtest/node_modules/playwright'));
  } catch (inner) {
    console.error('Playwright is not installed. From anywhere:\n'
      + '  PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 npm install playwright\n'
      + 'then re-run, setting PLAYWRIGHT_PATH if it is not on the module path.');
    process.exit(2);
  }
}

const BASE = process.env.BASE_URL || 'http://localhost:8099';
const EXECUTABLE = process.env.CHROMIUM_PATH || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const LEARNER = process.env.LEARNER || 'ama-x9';
const PIN = process.env.PIN || '4821';

let failures = 0;
const pass = (name) => console.log('  PASS  ' + name);
const fail = (name, detail) => { failures++; console.log('  FAIL  ' + name + (detail ? ' — ' + detail : '')); };
const check = (name, actual, expected) =>
  (actual === expected ? pass(name) : fail(name, `expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`));
const checkTrue = (name, value, detail) => (value ? pass(name) : fail(name, detail));

/** How many items are sitting in the device's outbox. */
async function outboxCount(page) {
  return page.evaluate(() => new Promise((resolve) => {
    const open = indexedDB.open('academy-offline', 1);
    open.onsuccess = () => {
      const tx = open.result.transaction('outbox', 'readonly');
      const all = tx.objectStore('outbox').getAll();
      all.onsuccess = () => resolve(all.result.length);
      all.onerror = () => resolve(-1);
    };
    open.onerror = () => resolve(-1);
  }));
}

async function signIn(page, username, pin) {
  await page.goto(BASE + '/academy/login.php', { waitUntil: 'domcontentloaded' });
  await page.fill('#username', username);
  await page.fill('#pin', pin);
  await Promise.all([page.waitForURL(/academy\/(index|change_pin)\.php/), page.click('button[type=submit]')]);
}

/** The worker installs on first load; wait for it to be controlling. */
async function waitForController(page) {
  await page.waitForFunction(() => navigator.serviceWorker.controller !== null, null, { timeout: 15000 });
}

(async () => {
  const browser = await chromium.launch({ executablePath: EXECUTABLE });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('Offline tests against ' + BASE);

    console.log('-- the worker registers --');
    await signIn(page, LEARNER, PIN);
    const registered = await page.evaluate(async () => {
      const reg = await navigator.serviceWorker.getRegistration();
      return !!reg;
    });
    checkTrue('the service worker registers', registered);

    await page.reload({ waitUntil: 'domcontentloaded' });
    await waitForController(page);
    pass('it controls the page after a reload');

    const scope = await page.evaluate(async () => {
      const reg = await navigator.serviceWorker.getRegistration();
      return new URL(reg.scope).pathname;
    });
    check('its scope is the academy alone', scope, '/academy/');

    console.log('-- the shell is cached, page HTML is not --');
    const cached = await page.evaluate(async () => {
      const names = await caches.keys();
      const cache = await caches.open(names.find((n) => n.startsWith('academy-shell-')));
      const keys = await cache.keys();
      return keys.map((r) => new URL(r.url).pathname).sort();
    });
    checkTrue('the stylesheet is cached', cached.includes('/assets/css/academy.css'), cached.join(', '));
    checkTrue('the offline page is cached', cached.includes('/academy/offline.html'));
    checkTrue('no page HTML is cached',
      !cached.some((p) => p.endsWith('.php')), 'cached: ' + cached.join(', '));

    console.log('-- offline, a navigation gets the offline page, not a stale dashboard --');
    await context.setOffline(true);
    await page.goto(BASE + '/academy/index.php', { waitUntil: 'domcontentloaded' });
    const heading = (await page.textContent('h1')) || '';
    check('the offline page is shown', heading.trim(), 'No internet right now');
    const leaked = await page.content();
    checkTrue("the other learner's name is not on it", !/Ama|Kwesi/.test(leaked));

    console.log('-- offline, the interface still has its styling --');
    const styled = await page.evaluate(() =>
      getComputedStyle(document.body).backgroundColor);
    checkTrue('CSS was served from the cache while offline',
      styled && styled !== 'rgba(0, 0, 0, 0)' && styled !== 'transparent', styled);

    console.log('-- work typed offline is queued, not lost --');
    await context.setOffline(false);
    await page.goto(BASE + '/academy/mysite.php', { waitUntil: 'domcontentloaded' });
    await waitForController(page);
    await context.setOffline(true);

    await page.fill('#html', '<h1>Written with no internet</h1>');
    await page.click('#site-editor-form button[type=submit]');
    // The generic "no internet" banner is already up, so wait for the text
    // that specifically confirms this save was kept.
    await page.waitForFunction(() => {
      const bar = document.getElementById('offline-banner');
      return bar && !bar.hidden && /is saved on this computer/i.test(bar.textContent);
    }, null, { timeout: 10000 }).catch(() => {});
    const bannerText = (await page.textContent('#offline-banner')) || '';
    checkTrue('the learner is told their work is safe',
      /is saved on this computer/i.test(bannerText), bannerText);

    const queued = await page.evaluate(() => new Promise((resolve) => {
      const open = indexedDB.open('academy-offline', 1);
      open.onsuccess = () => {
        const tx = open.result.transaction('outbox', 'readonly');
        const all = tx.objectStore('outbox').getAll();
        all.onsuccess = () => resolve(all.result.map((i) => ({ learner: i.learner, label: i.label })));
      };
      open.onerror = () => resolve([]);
    }));
    check('one item is queued', queued.length, 1);
    check('it is tagged with the learner who made it', queued[0] && queued[0].learner, LEARNER);

    const storedCsrf = await page.evaluate(() => new Promise((resolve) => {
      const open = indexedDB.open('academy-offline', 1);
      open.onsuccess = () => {
        const tx = open.result.transaction('outbox', 'readonly');
        const all = tx.objectStore('outbox').getAll();
        all.onsuccess = () => resolve(all.result[0].fields.some((f) => f[0] === 'csrf'));
      };
      open.onerror = () => resolve(false);
    }));
    check('the stale CSRF token is not stored with it', storedCsrf, false);

    console.log('-- back online, the queue drains by itself --');
    await context.setOffline(false);
    await page.waitForFunction(() => {
      const bar = document.getElementById('offline-banner');
      return bar && !bar.hidden && /has been sent/i.test(bar.textContent);
    }, null, { timeout: 15000 }).catch(() => {});
    const afterOnline = await outboxCount(page);
    check('the queue empties when the line returns', afterOnline, 0);

    await page.goto(BASE + '/academy/mysite.php', { waitUntil: 'domcontentloaded' });
    const savedOffline = await page.inputValue('#html');
    checkTrue('the work written offline reached the server',
      savedOffline.includes('Written with no internet'), savedOffline.slice(0, 80));

    console.log("-- work left by another learner is never sent as this one --");
    // Stand in for a child who queued work and walked away: the device still
    // holds it, and someone else signs in. It must stay put.
    await page.evaluate(() => new Promise((resolve) => {
      const open = indexedDB.open('academy-offline', 1);
      open.onsuccess = () => {
        const tx = open.result.transaction('outbox', 'readwrite');
        tx.objectStore('outbox').add({
          learner: 'someone-else',
          action: location.href,
          fields: [['action', 'save'], ['html', '<h1>Not mine</h1>'], ['css', ''], ['js', '']],
          label: 'Their page',
          created: Date.now()
        });
        tx.oncomplete = resolve;
        tx.onerror = resolve;
      };
      open.onerror = resolve;
    }));

    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2500);
    check("the other learner's work is left untouched", await outboxCount(page), 1);
    const otherBanner = await page.textContent('#offline-banner').catch(() => '');
    checkTrue('and the situation is explained',
      /another sign-in/i.test(otherBanner || ''), otherBanner);

    const untouched = await page.inputValue('#html');
    checkTrue("and it did not overwrite this learner's page",
      !untouched.includes('Not mine'), untouched.slice(0, 60));

    // Clear it so the rest of the run starts from a known state.
    await page.evaluate(() => new Promise((resolve) => {
      const open = indexedDB.open('academy-offline', 1);
      open.onsuccess = () => {
        const tx = open.result.transaction('outbox', 'readwrite');
        tx.objectStore('outbox').clear();
        tx.oncomplete = resolve;
        tx.onerror = resolve;
      };
      open.onerror = resolve;
    }));

    console.log('-- a screenshot attached with no internet survives too --');
    // Files are the part most likely to break: they go into IndexedDB as
    // Blobs and have to be rebuilt into a multipart body on the way out.
    const fileAssignment = await page.evaluate(async (base) => {
      const res = await fetch(base + '/academy/index.php', { credentials: 'same-origin' });
      const html = await res.text();
      const ids = [...html.matchAll(/assignment\.php\?id=(\d+)/g)].map((m) => m[1]);
      for (const id of ids) {
        const page = await (await fetch(base + '/academy/assignment.php?id=' + id,
          { credentials: 'same-origin' })).text();
        if (/type="file"/.test(page)) return id;
      }
      return null;
    }, BASE);

    if (!fileAssignment) {
      console.log('  SKIP  no assignment takes a file on this database');
    } else {
      await page.goto(BASE + '/academy/assignment.php?id=' + fileAssignment,
        { waitUntil: 'domcontentloaded' });
      await waitForController(page);
      await context.setOffline(true);

      await page.setInputFiles('#upload', {
        name: 'my-maze.png',
        mimeType: 'image/png',
        // A one-pixel PNG is enough to prove the bytes round-trip.
        buffer: Buffer.from(
          'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
          'base64'),
      });
      await page.click('button[name=action][value=submit]');
      await page.waitForFunction(() => {
        const bar = document.getElementById('offline-banner');
        return bar && !bar.hidden && /is saved on this computer/i.test(bar.textContent);
      }, null, { timeout: 10000 }).catch(() => {});

      const storedFile = await page.evaluate(() => new Promise((resolve) => {
        const open = indexedDB.open('academy-offline', 1);
        open.onsuccess = () => {
          const tx = open.result.transaction('outbox', 'readonly');
          const all = tx.objectStore('outbox').getAll();
          all.onsuccess = () => {
            const item = all.result[all.result.length - 1];
            const upload = item && item.fields.find((f) => f[0] === 'upload');
            resolve(upload && upload[1] && upload[1].name
              ? { name: upload[1].name, size: upload[1].size, type: upload[1].type }
              : null);
          };
        };
        open.onerror = () => resolve(null);
      }));
      checkTrue('the file itself is kept on the device',
        storedFile && storedFile.name === 'my-maze.png' && storedFile.size > 0,
        JSON.stringify(storedFile));

      await context.setOffline(false);
      await page.waitForFunction(() => {
        const bar = document.getElementById('offline-banner');
        return bar && !bar.hidden && /has been sent/i.test(bar.textContent);
      }, null, { timeout: 15000 }).catch(() => {});
      check('it is sent when the line returns', await outboxCount(page), 0);

      await page.goto(BASE + '/academy/assignment.php?id=' + fileAssignment,
        { waitUntil: 'domcontentloaded' });
      const shown = await page.content();
      checkTrue('and the server has the file', /my-maze\.png/.test(shown));
    }

    console.log('-- the editor keeps a local copy of unsaved typing --');
    await page.goto(BASE + '/academy/mysite.php', { waitUntil: 'domcontentloaded' });
    await page.fill('#html', '<h1>Half-finished</h1>');
    await page.waitForTimeout(1200);
    const draft = await page.evaluate((who) =>
      window.localStorage.getItem('academy-draft:' + who + ':' + document.body.dataset.siteSlug), LEARNER);
    checkTrue('typing is stashed on the device', draft && draft.includes('Half-finished'));

    await page.goto(BASE + '/academy/mysite.php', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('.notice-warn', { timeout: 8000 });
    const restoreOffered = await page.isVisible('text=Bring back my work');
    checkTrue('and offered back on the next visit', restoreOffered);
    await page.click('text=Bring back my work');
    const restored = await page.inputValue('#html');
    checkTrue('restoring puts it back in the editor', restored.includes('Half-finished'), restored.slice(0, 60));

    console.log('-- the manifest is installable --');
    const manifest = await page.evaluate(async (base) => {
      const res = await fetch(base + '/academy/manifest.webmanifest');
      return res.ok ? await res.json() : null;
    }, BASE);
    checkTrue('the manifest loads', !!manifest);
    checkTrue('it has a 192px and a 512px icon',
      manifest && manifest.icons.some((i) => i.sizes === '192x192')
                && manifest.icons.some((i) => i.sizes === '512x512'));
    checkTrue('it has a maskable icon',
      manifest && manifest.icons.some((i) => (i.purpose || '').includes('maskable')));

    console.log('-- the worker stays out of the rest of the site --');
    const outOfScope = await page.evaluate(async (base) => {
      const res = await fetch(base + '/portal.php', { redirect: 'manual' });
      return res.type;
    }, BASE);
    checkTrue('a request outside /academy/ is not intercepted', outOfScope !== 'error', outOfScope);

  } catch (error) {
    fail('the suite itself', error.message);
  } finally {
    await browser.close();
  }

  console.log('');
  if (failures) { console.log('OFFLINE TESTS FAILED (' + failures + ')'); process.exit(1); }
  console.log('OFFLINE TESTS PASSED');
})();
