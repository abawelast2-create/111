const CACHE_NAME = 'attendance-laravel-v1.0.0';

const STATIC_ASSETS = [
  '/',
  '/employee',
  '/manifest.json',
  '/assets/css/admin.css',
  '/assets/css/radar.css',
  '/assets/fonts/tajawal.css',
  '/assets/js/radar.js',
  '/assets/js/theme.js',
  '/assets/images/loogo.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  if (!url.protocol.startsWith('http')) return;
  if (url.origin !== self.location.origin) return;

  if (event.request.method !== 'GET' || url.pathname.startsWith('/api/')) {
    return;
  }

  if (url.pathname.match(/\.(css|js|png|jpg|jpeg|svg|woff2?)$/i)) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        if (cached) return cached;

        return fetch(event.request).then((response) => {
          if (response && response.ok) {
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, response.clone()));
          }
          return response;
        });
      })
    );

    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        if (response && response.ok) {
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, response.clone()));
        }
        return response;
      })
      .catch(() => caches.match(event.request))
  );
});
