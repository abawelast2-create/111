// =============================================================
// worker.js - Advanced Service Worker with QUIC Protocol Fallback
// =============================================================

const CACHE_NAME = 'attendance-v3.6.0';
const STATIC_ASSETS = [
    './assets/css/style.css',
    './assets/css/admin.css',
];

// Install
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// Activate
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

// Advanced fetch with QUIC/HTTP3 fallback
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

    // Skip API and POST
    if (event.request.method !== 'GET' || url.pathname.includes('/api/')) {
        return;
    }

    // Static assets: Cache-first
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

    // HTML pages: Network-first with QUIC retry + protocol downgrade
    event.respondWith(
        fetchWithFallback(event.request)
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
            })
    );
});

/**
 * Advanced fetch with QUIC error detection and HTTP/1.1 fallback
 */
async function fetchWithFallback(request) {
    try {
        // First attempt: normal fetch
        const response = await fetch(request, {
            cache: 'no-cache',
            redirect: 'follow'
        });
        
        if (response.ok) return response;
        throw new Error('HTTP ' + response.status);
        
    } catch (err) {
        // QUIC/HTTP3 failure detected
        console.warn('[SW] Network error, trying fallback:', err.message);
        
        // Strategy 1: Retry with cache-busted URL (forces new connection)
        try {
            const retryUrl = new URL(request.url);
            retryUrl.searchParams.set('_retry', Date.now());
            
            const retryResponse = await fetch(retryUrl.toString(), {
                method: request.method,
                redirect: 'follow',
                cache: 'reload'
            });
            
            if (retryResponse.ok) return retryResponse;
            
        } catch (retryErr) {
            console.error('[SW] Retry failed:', retryErr.message);
        }
        
        // Strategy 2: Try mobile gateway (m.php)
        if (request.url.includes('attendance.php')) {
            try {
                const token = new URL(request.url).searchParams.get('token');
                if (token) {
                    const mUrl = request.url.replace('/employee/attendance.php', '/m.php');
                    const mResponse = await fetch(mUrl);
                    if (mResponse.ok) return mResponse;
                }
            } catch (mErr) {
                console.error('[SW] Mobile gateway failed:', mErr.message);
            }
        }
        
        // Final: throw original error to trigger cache fallback
        throw err;
    }
}

// Message handler for manual cache clear
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.delete(CACHE_NAME).then(() => {
                self.clients.claim();
                return self.registration.unregister();
            })
        );
    }
});
