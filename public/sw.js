const CACHE = 'morning-workout-v4';
const BASE = new URL('.', self.location).pathname.replace(/\/$/, '');
const PRECACHE = [
    `${BASE}/manifest.webmanifest`,
    `${BASE}/icons/icon-192.png`,
    `${BASE}/icons/icon-512.png`,
    `${BASE}/icons/icon-maskable-512.png`,
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin || url.pathname === `${BASE}/csrf-token`) {
        return;
    }

    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(networkFirst(request));
        return;
    }

    if (
        url.pathname.startsWith(`${BASE}/build/`)
        || url.pathname.startsWith(`${BASE}/icons/`)
        || url.pathname.endsWith('.webmanifest')
    ) {
        event.respondWith(cacheFirst(request));
    }
});

async function networkFirst(request) {
    const cache = await caches.open(CACHE);

    try {
        const response = await fetch(request);

        if (response.ok) {
            cache.put(request, response.clone());
        }

        return response;
    } catch {
        const cached = await cache.match(request);

        if (cached) {
            return cached;
        }

        if (new URL(request.url).pathname === `${BASE}/` || new URL(request.url).pathname === BASE) {
            const today = await cache.match(`${BASE}/today`);

            if (today) {
                return today;
            }
        }

        return new Response('You are offline. Open today’s workout once while online, then it will be available here.', {
            status: 503,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
    }
}

async function cacheFirst(request) {
    const cache = await caches.open(CACHE);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    const response = await fetch(request);

    if (response.ok) {
        cache.put(request, response.clone());
    }

    return response;
}
