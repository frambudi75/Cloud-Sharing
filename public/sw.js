// Service Worker for Cloud Sharing PWA
const CACHE_NAME = 'cloudsharing-v1.5.6';
const STATIC_ASSETS = [
    'assets/img/favicon.svg',
    'manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only handle http and https requests
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Skip non-GET requests and dynamic streaming/actions
    if (
        request.method !== 'GET' ||
        url.search.includes('download') ||
        url.search.includes('upload') ||
        url.search.includes('stream') ||
        url.search.includes('raw=') ||
        request.headers.get('range')
    ) {
        return;
    }

    // Static Assets: Cache-first with robust fallback
    if (url.pathname.includes('/assets/') || url.pathname.endsWith('.svg') || url.pathname.endsWith('.png') || url.pathname.endsWith('.css') || url.pathname.endsWith('.js')) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) return cachedResponse;
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    return new Response('', { status: 408, statusText: 'Asset Fetch Failed' });
                });
            }).catch(() => {
                return new Response('', { status: 408, statusText: 'Asset Match Failed' });
            })
        );
        return;
    }

    // Dynamic Pages: Network-first with safe cache fallback
    event.respondWith(
        fetch(request).catch(async () => {
            try {
                const cached = await caches.match(request);
                if (cached) return cached;
            } catch (e) {
                // Ignore cache match error
            }
            return new Response('Halaman tidak dapat diakses saat offline.', {
                status: 503,
                statusText: 'Service Unavailable',
                headers: { 'Content-Type': 'text/plain; charset=utf-8' }
            });
        })
    );
});

