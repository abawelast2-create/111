// =============================================================
// Service Worker - نظام الحضور والانصراف (PWA)
// =============================================================

const CACHE_NAME = 'attendance-v3.6.0';
const STATIC_ASSETS = [
    './assets/css/style.css',
    './assets/css/admin.css',
];

// تثبيت: تخزين الملفات الثابتة
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// تفعيل: مسح الكاش القديم
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

// جلب: Network-first مع fallback للكاش (للملفات الثابتة فقط)
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Skip non-http(s) schemes (chrome-extension://, etc.)
    if (!url.protocol.startsWith('http')) {
        return;
    }
    
    // Skip cross-origin requests entirely (map tiles, fonts, CDNs)
    if (url.origin !== self.location.origin) {
        return;
    }
    
    // لا تخزّن طلبات API أو POST
    if (event.request.method !== 'GET' || url.pathname.includes('/api/')) {
        return;
    }

    // للملفات الثابتة: Cache-first
    if (url.pathname.match(/\.(css|js|png|jpg|jpeg|svg|woff2?)$/)) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                if (cached) return cached;
                return fetch(event.request).then((response) => {
                    if (response && response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    }
                    return response;
                });
            }).catch(() => new Response('', {status: 408, statusText: 'Offline'}))
        );
        return;
    }

    // لصفحات HTML: Network-first مع إعادة محاولة تلقائية عند فشل QUIC
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (response && response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            })
            .catch((err) => {
                // QUIC/HTTP3 failure — retry with cache-busted URL to force HTTP/2
                const retryUrl = new URL(event.request.url);
                retryUrl.searchParams.set('_h2', Date.now());
                return fetch(retryUrl.toString(), {
                    method: event.request.method,
                    redirect: 'follow'
                })
                .then((response) => {
                    if (response && response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(event.request).then((cached) => {
                        return cached || new Response('', {status: 408, statusText: 'Offline'});
                    });
                });
            })
    );
});
