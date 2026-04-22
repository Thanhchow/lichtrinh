const CACHE_NAME = 'tung-van-trip-v2'; // Updated cache name
const urlsToCache = [
  './index.html',
  './manifest.json',
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
];

// Cài đặt Cache
self.addEventListener('install', event => {
  // Bắt buộc Service Worker mới cài đặt ngay lập tức
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

// Kích hoạt: Xóa cache cũ nếu có
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Phục vụ offline theo cơ chế Stale-While-Revalidate
self.addEventListener('fetch', event => {
  // Bỏ qua các request POST, hoặc API lấy dữ liệu động (api.php)
  if (event.request.method !== 'GET' || event.request.url.includes('api.php')) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then(cachedResponse => {
      const fetchPromise = fetch(event.request).then(networkResponse => {
        // Cập nhật lại cache ngầm định
        caches.open(CACHE_NAME).then(cache => {
          cache.put(event.request, networkResponse.clone());
        });
        return networkResponse;
      }).catch(() => {
        // Không có mạng, return cache hoặc rỗng
      });

      // Trả về cache ngay lập tức nếu có, đồng thời fetch ngầm để update cache.
      // Nếu chưa có cache, chờ fetchPromise.
      return cachedResponse || fetchPromise;
    })
  );
});