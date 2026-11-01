/**
 * T.S. Pattani Badminton - Progressive Web App (PWA) Handler
 * Manages Service Worker Registration, Install Prompts, and iOS Add-to-Home Guidance.
 */

(function () {
    'use strict';

    // 1. ตรวจสอบการรองรับ Service Worker และทำการ Register
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/ts-pattani/sw.js', { scope: '/ts-pattani/' })
                .then(function (registration) {
                    // Service Worker ลงทะเบียนสำเร็จ
                })
                .catch(function (error) {
                    console.warn('[PWA] Service Worker registration failed:', error);
                });
        });
    }

    // 2. ตรวจสอบว่าแอปกำลังทำงานในโหมด Standalone (ติดตั้งแล้ว) หรือไม่
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (isStandalone) {
        return; // ทำงานในโหมดแอปแล้ว ไม่ต้องแสดงแถบเชิญชวนติดตั้ง
    }

    // 3. ตรวจสอบประวัติการกดปิดแบนเนอร์ (ถ้าเคยกดปิด จะไม่กวนใจเป็นเวลา 3 วัน)
    const lastDismissed = localStorage.getItem('ts_pwa_dismissed');
    if (lastDismissed) {
        const diffDays = (Date.now() - parseInt(lastDismissed, 10)) / (1000 * 60 * 60 * 24);
        if (diffDays < 3) {
            return;
        }
    }

    let deferredPrompt = null;

    // สร้าง HTML Elements ของแบนเนอร์ติดตั้ง PWA
    function injectInstallBanner() {
        if (document.getElementById('pwaInstallBanner')) return;

        const banner = document.createElement('div');
        banner.id = 'pwaInstallBanner';
        banner.className = 'pwa-install-banner';
        banner.innerHTML = `
            <div class="pwa-banner-left">
                <img src="/ts-pattani/assets/img/icons/icon-192x192.png" alt="T.S. Badminton App" class="pwa-app-icon">
                <div class="pwa-banner-text">
                    <h5 class="pwa-banner-title">ติดตั้งแอป T.S. Badminton</h5>
                    <p class="pwa-banner-desc">จองสนามและเช็คคอร์ทว่างได้สะดวกรวดเร็วบนมือถือ</p>
                    <div id="pwaIosGuide" class="pwa-ios-guide">
                        แตะปุ่มแชร์ <i class="fas fa-arrow-up-from-bracket"></i> ด้านล่าง แล้วเลือก <strong>"เพิ่มไปยังหน้าจอโฮม"</strong>
                    </div>
                </div>
            </div>
            <div class="pwa-banner-actions">
                <button type="button" id="btnPwaInstall" class="btn-pwa-install">
                    <i class="fas fa-download"></i> ติดตั้ง
                </button>
                <button type="button" id="btnPwaDismiss" class="btn-pwa-dismiss" title="ไว้คราวหลัง">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        document.body.appendChild(banner);

        // ผูก Event ปุ่มปิด
        document.getElementById('btnPwaDismiss').addEventListener('click', function () {
            hideBanner();
            localStorage.setItem('ts_pwa_dismissed', Date.now().toString());
        });

        // ผูก Event ปุ่มติดตั้ง
        document.getElementById('btnPwaInstall').addEventListener('click', function () {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function (choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        // ผู้ใช้ยอมรับการติดตั้ง
                        hideBanner();
                    }
                    deferredPrompt = null;
                });
            } else {
                // กรณี iOS หรืออุปกรณ์ที่ไม่รองรับ deferredPrompt
                const iosGuide = document.getElementById('pwaIosGuide');
                if (iosGuide) {
                    iosGuide.style.display = 'block';
                }
            }
        });
    }

    function showBanner() {
        injectInstallBanner();
        setTimeout(function () {
            const banner = document.getElementById('pwaInstallBanner');
            if (banner) {
                banner.classList.add('pwa-show');
            }
        }, 1200);
    }

    function hideBanner() {
        const banner = document.getElementById('pwaInstallBanner');
        if (banner) {
            banner.classList.remove('pwa-show');
            setTimeout(function () {
                banner.remove();
            }, 400);
        }
    }

    // 4. ดักจับ Event 'beforeinstallprompt' สำหรับ Chrome, Edge, Android
    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredPrompt = event;
        showBanner();
    });

    // 5. ดักจับ Event 'appinstalled' เมื่อติดตั้งสำเร็จ
    window.addEventListener('appinstalled', function () {
        hideBanner();
        deferredPrompt = null;
    });

    // 6. ตรวจจับ Safari บน iOS เพื่อแสดงคำแนะนำแบบนุ่มนวล
    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    if (isIos && !isStandalone && !lastDismissed) {
        // แสดงแบนเนอร์พร้อมคำแนะนำสำหรับ iOS หลังจากใช้งานไปแล้ว 3 วินาที
        setTimeout(function () {
            showBanner();
            setTimeout(function () {
                const iosGuide = document.getElementById('pwaIosGuide');
                const btnInstall = document.getElementById('btnPwaInstall');
                if (iosGuide && btnInstall) {
                    btnInstall.style.display = 'none'; // ซ่อนปุ่ม install บน iOS แล้วแสดงคำแนะนำแทน
                    iosGuide.style.display = 'block';
                }
            }, 100);
        }, 3000);
    }
})();
