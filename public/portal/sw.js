/* eslint-disable no-restricted-globals */

const CACHE_VERSION = 'portal-pwa-v1';
const RUNTIME_CACHE = `runtime-${CACHE_VERSION}`;

const PRECACHE_URLS = [
  '/portal',
  '/portal/login',
  '/assets/css/design-system.css',
  '/icone_1.png',
  '/Principal_1.png',
  '/portal/offline.html',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(RUNTIME_CACHE).then((cache) => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((k) => k.startsWith('runtime-') && k !== RUNTIME_CACHE)
          .map((k) => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

function isSameOrigin(url) {
  try {
    return new URL(url).origin === self.location.origin;
  } catch (e) {
    return false;
  }
}

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Only handle GET
  if (req.method !== 'GET') return;

  // Limit to same-origin and portal scope
  if (!isSameOrigin(req.url)) return;

  const url = new URL(req.url);
  const inScope = url.pathname === '/portal' || url.pathname.startsWith('/portal/');
  if (!inScope) return;

  // Network-first for HTML navigations, cache-first for assets.
  const isNav = req.mode === 'navigate' || (req.headers.get('accept') || '').includes('text/html');

  if (isNav) {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(RUNTIME_CACHE).then((cache) => cache.put(req, copy));
          return res;
        })
        .catch(() =>
          caches.match(req).then((cached) => cached || caches.match('/portal/offline.html'))
        )
    );
    return;
  }

  event.respondWith(
    caches.match(req).then((cached) =>
      cached ||
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(RUNTIME_CACHE).then((cache) => cache.put(req, copy));
          return res;
        })
        .catch(() => cached)
    )
  );
});

self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event && event.data ? event.data.json() : {};
  } catch (e) {
    data = {};
  }

  const title = (data && data.title) ? String(data.title) : 'Portal do Paciente';
  const body = (data && data.body) ? String(data.body) : '';
  const url = (data && data.url) ? String(data.url) : '/portal/notificacoes';

  event.waitUntil(
    self.registration.showNotification(title, {
      body,
      icon: '/icone_1.png',
      badge: '/icone_1.png',
      data: { url },
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  const url = event && event.notification && event.notification.data && event.notification.data.url
    ? String(event.notification.data.url)
    : '/portal';

  event.notification.close();

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url && client.url.includes('/portal')) {
          client.focus();
          return client.navigate(url);
        }
      }
      return clients.openWindow(url);
    })
  );
});
