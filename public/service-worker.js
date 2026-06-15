const CACHE_NAME = 'sinphony-v2';
const RUNTIME_CACHE = 'sinphony-runtime';
const AUDIO_CACHE = 'sinphony-audio';

const ASSETS_TO_CACHE = [
  '/',
  '/manifest.json',
  '/css/style.css',
  '/js/player.js',
  '/icons/icon.svg',
];

// Installation du service worker
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installation...');
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Mise en cache des assets');
      return cache.addAll(ASSETS_TO_CACHE).catch((error) => {
        console.warn('[Service Worker] Certains assets n\'ont pas pu être mis en cache:', error);
        // Continue même si certains assets manquent
      });
    })
  );
  self.skipWaiting();
});

// Activation du service worker
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activation...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE && cacheName !== AUDIO_CACHE) {
            console.log('[Service Worker] Suppression du cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Interception des requêtes
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-GET
  if (request.method !== 'GET') {
    return;
  }

  // Traitement des fichiers audio
  if (isAudioFile(url.pathname) || url.pathname.startsWith('/audio/')) {
    event.respondWith(handleAudioRequest(request));
    return;
  }

  // Traitement des ressources statiques (JS, CSS, images)
  if (isStaticAsset(url.pathname)) {
    event.respondWith(handleStaticRequest(request));
    return;
  }

  // Traitement des requêtes API
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(handleApiRequest(request));
    return;
  }

  // Traitement par défaut
  event.respondWith(handleDefaultRequest(request));
});

/**
 * Vérifie si le fichier est un fichier audio
 */
function isAudioFile(pathname) {
  const audioExtensions = ['.mp3', '.wav', '.ogg', '.m4a', '.flac'];
  return audioExtensions.some(ext => pathname.endsWith(ext));
}

/**
 * Vérifie si c'est une ressource statique
 */
function isStaticAsset(pathname) {
  const staticExtensions = ['.js', '.css', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.woff', '.woff2', '.ttf', '.otf'];
  return staticExtensions.some(ext => pathname.endsWith(ext));
}

/**
 * Gestion des requêtes audio : cache-first avec fallback réseau
 */
function handleAudioRequest(request) {
  return caches.match(request).then((response) => {
    if (response) {
      console.log('[Service Worker] Audio trouvé en cache:', request.url);
      return response;
    }

    console.log('[Service Worker] Téléchargement audio:', request.url);
    return fetch(request)
      .then((response) => {
        // Vérifier que la réponse est valide
        if (!response || response.status !== 200 || response.type === 'error') {
          return response;
        }

        // Cloner la réponse et la mettre en cache
        const responseToCache = response.clone();
        caches.open(AUDIO_CACHE).then((cache) => {
          cache.put(request, responseToCache);
        });

        return response;
      })
      .catch((error) => {
        console.warn('[Service Worker] Erreur lors du téléchargement audio:', error);
        // Retourner une réponse vide ou une alternative
        return new Response('Fichier audio indisponible', {
          status: 503,
          statusText: 'Service Unavailable',
        });
      });
  });
}

/**
 * Gestion des requêtes statiques : cache-first avec fallback réseau
 */
function handleStaticRequest(request) {
  return caches.match(request).then((response) => {
    if (response) {
      console.log('[Service Worker] Ressource statique en cache:', request.url);
      return response;
    }

    console.log('[Service Worker] Téléchargement ressource statique:', request.url);
    return fetch(request)
      .then((response) => {
        // Vérifier que la réponse est valide
        if (!response || response.status !== 200 || response.type === 'error') {
          return response;
        }

        // Cloner la réponse et la mettre en cache
        const responseToCache = response.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(request, responseToCache);
        });

        return response;
      })
      .catch(() => {
        console.warn('[Service Worker] Ressource statique indisponible:', request.url);
        // Retourner une placeholder pour les images
        if (request.destination === 'image') {
          return new Response(
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect fill="#ccc" width="100" height="100"/></svg>',
            { headers: { 'Content-Type': 'image/svg+xml' } }
          );
        }
        return new Response('Ressource indisponible', { status: 503 });
      });
  });
}

/**
 * Gestion des requêtes API : network-first avec fallback cache
 */
function handleApiRequest(request) {
  return fetch(request)
    .then((response) => {
      // Mettre en cache la réponse réussie
      if (response && response.status === 200) {
        const responseToCache = response.clone();
        caches.open(RUNTIME_CACHE).then((cache) => {
          cache.put(request, responseToCache);
        });
      }
      return response;
    })
    .catch(() => {
      console.log('[Service Worker] Mode offline - tentative de cache:', request.url);
      return caches.match(request).then((response) => {
        if (response) {
          return response;
        }
        return new Response(JSON.stringify({ error: 'Offline' }), {
          status: 503,
          headers: { 'Content-Type': 'application/json' },
        });
      });
    });
}

/**
 * Gestion par défaut : network-first avec fallback cache
 */
function handleDefaultRequest(request) {
  return fetch(request)
    .then((response) => {
      if (response && response.status === 200) {
        const responseToCache = response.clone();
        caches.open(RUNTIME_CACHE).then((cache) => {
          cache.put(request, responseToCache);
        });
      }
      return response;
    })
    .catch(() => {
      return caches.match(request).then((response) => {
        if (response) return response;
        return caches.match('/').then((fallback) => {
          return fallback || new Response('Hors ligne', { status: 503 });
        });
      });
    });
}

/**
 * Gestion des messages depuis le client
 */
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'CLEAR_CACHE') {
    caches.delete(AUDIO_CACHE).then(() => {
      console.log('[Service Worker] Cache audio vidé');
      event.ports[0].postMessage({ success: true });
    });
  }

  if (event.data && event.data.type === 'CACHE_URLS') {
    const urls = event.data.urls || [];
    caches.open(RUNTIME_CACHE).then((cache) => {
      cache.addAll(urls).then(() => {
        console.log('[Service Worker] URLs mises en cache:', urls);
        event.ports[0].postMessage({ success: true });
      });
    });
  }
});
