/**
 * T.S. Pattani Badminton - Progressive Web App (PWA) Service Worker
 * Version: 1.0.0 (Cache Name: ts-badminton-v1)
 * Strategy: Network-First for dynamic PHP endpoints; Stale-While-Revalidate for static assets.
 */

const CACHE_NAME = 'ts-badminton-pwa-v1';

// รายการไฟล์ Assets พื้นฐานที่แคชไว้ล่วงหน้าเพื่อความเร็วในการโหลด (Pre-cache list)
const STATIC_PRECACHE = [
    '/ts-pattani/manifest.json',
    '/ts-pattani/assets/css/global.css',
    '/ts-pattani/assets/css/style.css',
    '/ts-pattani/assets/css/navbar.css',
    '/ts-pattani/assets/css/responsive-mobile.css',
    '/ts-pattani/assets/css/pwa-install.css',
    '/ts-pattani/assets/img/icons/icon-192x192.png',
    '/ts-pattani/assets/img/icons/icon-512x512.png',
    '/ts-pattani/assets/img/icons/icon-maskable-192x192.png',
    '/ts-pattani/assets/img/icons/icon-maskable-512x512.png'
];

// ==============================================================================
// 1. Install Event: ทำการดาวน์โหลดและแคช Assets พื้นฐาน
// ==============================================================================
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_PRECACHE).catch((err) => {
                console.warn('[PWA SW] Pre-caching partial failure:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// ==============================================================================
// 2. Activate Event: ลบแคชเวอร์ชันเก่าที่หมดอายุ
// ==============================================================================
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// ==============================================================================
// 3. Fetch Event: จัดการการดึงข้อมูลอย่างปลอดภัย (Safe Caching Protocol)
// ==============================================================================
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // กฎเหล็ก: ข้ามคำขอที่ไม่ใช่ GET (POST, PUT, DELETE) และคำขอ Action ฐานข้อมูล
    if (request.method !== 'GET') {
        return;
    }

    // กฎเหล็ก: API หรือ Action Script ให้ดึงจาก Network เสมอ เพื่อความสดใหม่ของข้อมูลการจอง
    if (url.pathname.includes('/actions/') || url.pathname.includes('get_notifications.php') || url.pathname.includes('check_')) {
        return;
    }

    // สำหรับ Static Assets (CSS, JS, Fonts, Images): Stale-While-Revalidate
    if (
        request.destination === 'style' ||
        request.destination === 'script' ||
        request.destination === 'image' ||
        request.destination === 'font' ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.svg')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => cachedResponse);

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // สำหรับเอกสาร HTML / PHP Pages: Network First (ดึงสดก่อนเสมอ หาก Offline จึงใช้แคช)
    event.respondWith(
        fetch(request).then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
                const responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(request, responseToCache);
                });
            }
            return networkResponse;
        }).catch(() => {
            return caches.match(request).then((cachedResponse) => {
                if (cachedResponse) return cachedResponse;
                // ถ้าไม่มีแคชและไม่มีเน็ต สามารถแสดงหน้า 404/Offline สำรองได้
                return caches.match('/ts-pattani/404.php');
            });
        })
    );
});
