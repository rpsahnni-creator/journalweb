const CACHE_NAME = 'srtjmr-shell-v1';
const PRIVATE_PATH = /^\/(admin|editorial|reviewer|author|submissions|dashboard|profile|notifications|reviews|login|register|locale)\b/;

self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (PRIVATE_PATH.test(url.pathname) || url.pathname === '/oai' || url.pathname.startsWith('/oai')) {
        return;
    }

    const accept = request.headers.get('accept') || '';
    if (accept.includes('text/html')) {
        event.respondWith(fetch(request));
        return;
    }

    if (! url.pathname.startsWith('/build/') && ! url.pathname.startsWith('/images/')) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                }

                return response;
            });
        })
    );
});
