const CACHE_NAME = 'tung-van-trip-v1';
const urlsToCache = [
  './index.html',
  './manifest.json',
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
];

// Cài đặt Cache
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

// Phục vụ offline
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Trả về cache nếu có, không thì tải từ mạng
        return response || fetch(event.request);
      })
  );
});