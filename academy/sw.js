/*
 * Service worker for the student academy.
 *
 * Accra loses the line often, and a learner on a phone may have no data at
 * all on the way home. This keeps the academy usable: the page still opens,
 * the interface is already on the device, and work is never lost to a dropped
 * connection.
 *
 * It lives at /academy/ on purpose. A service worker's scope is its own
 * directory, so this one controls the learner pages and nothing else -- never
 * the sponsor portal, the teacher portal or a student's published page. It
 * still sees the requests those learner pages make, which is how the shared
 * stylesheet gets cached without widening the scope.
 *
 * What it caches, and what it refuses to:
 *
 *   yes  the shell -- stylesheets, scripts, icons, the offline page. The same
 *        bytes for everybody, and safe to keep on a shared lab machine.
 *   no   any page HTML. Academy pages are personal to one child, and these
 *        machines are shared. Showing a cached dashboard to the next learner
 *        would be worse than showing nothing, so a failed navigation gets the
 *        offline page instead.
 *   no   anything under /students/ or /api/, cached or not.
 *
 * Unsent work is handled by assets/js/academy-offline.js, not here: replaying
 * a POST needs to know which learner is signed in, and that belongs in the
 * page rather than in a worker shared by whoever uses the device next.
 */

/* Bump this when the shell changes. Old caches are deleted on activate. */
const CACHE_VERSION = 'v1';
const SHELL_CACHE = 'academy-shell-' + CACHE_VERSION;

/* Resolved against this worker's own URL, so the site works at any install
   path -- /academy/ on the live host, /ghana-school/academy/ under XAMPP. */
const BASE = new URL('./', self.location);
const url = (path) => new URL(path, BASE).toString();

const OFFLINE_PAGE = url('offline.html');

const SHELL = [
    OFFLINE_PAGE,
    url('manifest.webmanifest'),
    url('../assets/css/style.css'),
    url('../assets/css/academy.css'),
    url('../assets/js/academy.js'),
    url('../assets/js/academy-offline.js'),
    url('../assets/images/brand-mark.svg'),
    url('../assets/images/academy-icon-192.png'),
];

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(SHELL_CACHE);
        // addAll fails the whole install if any one file 404s, which would
        // leave no worker at all. Fetch each on its own so a missing asset
        // costs that asset and nothing else.
        await Promise.all(SHELL.map(async (href) => {
            try {
                const response = await fetch(href, { cache: 'reload' });
                if (response.ok) await cache.put(href, response);
            } catch (error) {
                /* offline during install: the next update will pick it up */
            }
        }));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const names = await caches.keys();
        await Promise.all(
            names
                .filter((name) => name.startsWith('academy-shell-') && name !== SHELL_CACHE)
                .map((name) => caches.delete(name))
        );
        await self.clients.claim();
    })());
});

/** Shell assets are the only thing worth storing. */
function isShellAsset(requestUrl) {
    if (requestUrl.origin !== self.location.origin) return false;
    return /\/assets\/(css|js|images)\//.test(requestUrl.pathname)
        || requestUrl.pathname.endsWith('/manifest.webmanifest');
}

/** Places a stale copy would be wrong or unsafe. */
function isNeverCached(requestUrl) {
    return /\/students\//.test(requestUrl.pathname)
        || /\/api\//.test(requestUrl.pathname);
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // POSTs are the page's business, not the worker's.
    if (request.method !== 'GET') return;

    const requestUrl = new URL(request.url);
    if (requestUrl.origin !== self.location.origin) return;
    if (isNeverCached(requestUrl)) return;

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigation(request));
        return;
    }

    if (isShellAsset(requestUrl)) {
        event.respondWith(handleShellAsset(request));
    }
});

/**
 * Always ask the network for a page. A learner's dashboard is personal and
 * these machines are shared, so a stale copy is never served -- if the
 * network cannot answer, the offline page explains why.
 */
async function handleNavigation(request) {
    try {
        return await fetch(request);
    } catch (error) {
        const cached = await caches.match(OFFLINE_PAGE, { ignoreSearch: true });
        return cached || new Response(
            '<!doctype html><meta charset="utf-8"><title>Offline</title>'
            + '<p style="font-family:system-ui;padding:2rem">You are offline, and this page '
            + 'has not been saved on this device yet.</p>',
            { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }
}

/**
 * Serve the shell from the cache straight away, and refresh it in the
 * background. On a slow connection the interface appears at once; on the next
 * visit it is up to date.
 */
async function handleShellAsset(request) {
    const cache = await caches.open(SHELL_CACHE);
    const cached = await cache.match(request);

    const network = fetch(request).then((response) => {
        if (response && response.ok) cache.put(request, response.clone());
        return response;
    }).catch(() => null);

    if (cached) return cached;

    const fresh = await network;
    if (fresh) return fresh;
    return new Response('', { status: 504, statusText: 'Offline' });
}

/** The page asks for this when someone signs out on a shared machine. */
self.addEventListener('message', (event) => {
    const action = event.data && event.data.action;
    if (action === 'clear-shell') {
        event.waitUntil(caches.keys().then((names) =>
            Promise.all(names.filter((n) => n.startsWith('academy-shell-')).map((n) => caches.delete(n)))
        ));
    } else if (action === 'skip-waiting') {
        self.skipWaiting();
    }
});
