/**
 * TontineSN Service Worker v3
 * Stratégies : cache-first pour assets, network-first pour pages
 * Supporte FCM (Firebase Cloud Messaging)
 */

const APP_VERSION   = 'v4';
const STATIC_CACHE  = `tontinesn-static-${APP_VERSION}`;
const DYNAMIC_CACHE = `tontinesn-dynamic-${APP_VERSION}`;
const ALL_CACHES    = [STATIC_CACHE, DYNAMIC_CACHE];

// Assets précachés — ne changent pas souvent
const PRECACHE_ASSETS = [
  '/css/tontine.css',
  '/js/tontine.js',
  '/manifest.json',
  '/images/element-logo.png',
  '/images/icon-192.png',
  '/images/icon-512.png',
];

// Page de fallback offline
const OFFLINE_URL = '/offline';

// ── INSTALL ────────────────────────────────────────────────────
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => cache.addAll([OFFLINE_URL, ...PRECACHE_ASSETS]))
      .then(() => self.skipWaiting())
  );
});

// ── ACTIVATE — nettoyage des vieux caches ──────────────────────
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys
          .filter((key) => !ALL_CACHES.includes(key))
          .map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

// ── FETCH ──────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-GET et cross-origin
  if (request.method !== 'GET') return;
  if (url.origin !== self.location.origin) return;

  // Ignorer les routes dynamiques Laravel (webhooks, api, admin actions)
  const skipPatterns = ['/webhooks/', '/api/', '/sanctum/', '/broadcasting/'];
  if (skipPatterns.some((p) => url.pathname.startsWith(p))) return;

  // ── Assets statiques : Cache First ────────────────────────────
  if (/\.(css|js|png|jpg|jpeg|svg|ico|woff2?|gif|webp)$/.test(url.pathname)) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) return cached;
        return fetch(request).then((response) => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
          }
          return response;
        }).catch(() => caches.match('/images/icon-192.png'));
      })
    );
    return;
  }

  // ── Navigation (pages HTML) : Network First avec fallback ─────
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Ne cache que les pages 200 (pas les redirections 302)
          if (response.ok && response.status === 200) {
            const clone = response.clone();
            caches.open(DYNAMIC_CACHE).then((cache) => cache.put(request, clone));
          }
          return response;
        })
        .catch(() =>
          caches.match(request)
            .then((cached) => cached || caches.match(OFFLINE_URL))
        )
    );
    return;
  }

  // ── Autres requêtes : Network avec fallback cache ──────────────
  event.respondWith(
    fetch(request).catch(() => caches.match(request))
  );
});

// ── PUSH NOTIFICATIONS — Web Push API ──────────────────────────
self.addEventListener('push', (event) => {
  let data = { 
    title: 'TontineSN', 
    body: 'Nouvelle notification', 
    icon: '/images/icon-192.png', 
    badge: '/images/icon-192.png', 
    url: '/dashboard' 
  };

  try {
    if (event.data) {
      data = { ...data, ...event.data.json() };
    }
  } catch (e) {
    if (event.data) data.body = event.data.text();
  }

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body:    data.body,
      icon:    data.icon,
      badge:   data.badge,
      data:    { url: data.url },
      vibrate: [200, 100, 200],
      requireInteraction: false,
      tag: 'tontinesn-notif',
      renotify: true,
    })
  );
});

// ── FCM MESSAGES (Firebase Cloud Messaging) ────────────────────
self.addEventListener('message', (event) => {
  if (!event.data) return;

  // Support pour SKIP_WAITING (force la mise à jour)
  if (event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
    return;
  }

  // Support pour messages FCM depuis Firebase Messaging
  if (event.data.firebaseMessaging) {
    // Les données FCM sont traitées par le SDK Firebase
    // Mais on peut aussi les afficher manuellement si nécessaire
    const { notification, data } = event.data.firebaseMessaging;
    
    if (notification) {
      const notificationOptions = {
        body: notification.body,
        icon: notification.icon || '/images/icon-192.png',
        badge: '/images/icon-192.png',
        vibrate: [200, 100, 200],
        requireInteraction: false,
        tag: 'tontinesn-fcm',
        renotify: true,
      };

      if (data?.url) {
        notificationOptions.data = { url: data.url };
      }

      event.waitUntil(
        self.registration.showNotification(notification.title || 'TontineSN', notificationOptions)
      );
    }
  }
});

// ── NOTIFICATION CLICK ─────────────────────────────────────────
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = event.notification.data?.url || '/dashboard';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clients) => {
        const existing = clients.find((c) => c.url.includes(self.location.origin));
        if (existing) {
          existing.focus();
          return existing.navigate(targetUrl);
        }
        return self.clients.openWindow(targetUrl);
      })
  );
});

