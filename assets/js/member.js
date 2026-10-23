/* ==========================================================================
   T.S. Pattani - Member / Frontend JavaScript Controller
   มาตรฐานการจัดการสคริปต์ฝั่งผู้ใช้งาน (Strict Mode & HCI Design Standard)
   --------------------------------------------------------------------------
   สารบัญโครงสร้างระบบ 13 หมวดหมู่ (System Architecture Table of Contents):
   1. ระบบนำทางและเมนูมือถือ (Navigation & Drawer Menu)
   2. ระบบแจ้งเตือนสากล (Global Alert System)
   3. ระบบปฏิทินและเลือกวันจอง (Calendar & Date Picker - Wizard Step 1)
   4. ระบบเลือกเวลาและตารางคอร์ท (Time Slots & Court Matrix - Wizard Step 2 & 3)
   5. ระบบอุปกรณ์เช่าและบริการเสริม (Add-ons & Rental Selection - Wizard Step 4)
   6. กลไกการคำนวณราคาแบบ Real-time (Dynamic Pricing Engine)
   7. ตัวควบคุมขั้นตอน Wizard State Machine (Multi-Step Controller)
   8. การส่งฟอร์มการจองและการจัดการ Guest (Booking Submission & Interceptor)
   9. ระบบชำระเงิน แนบสลิป และเวลานับถอยหลัง (Payment, Slip & Countdown - payment.php)
   10. ระบบสะสมแต้มและแลกของรางวัล (Rewards & Loyalty System - rewards.php)
   11. ระบบฟอร์มยืนยันตัวตนและความปลอดภัย (Auth & Security Forms - register.php & login.php)
   12. ระบบประวัติการจองและขอยกเลิก (Booking History & Cancellation - booking_history.php)
   13. ระบบข่าวสารและโปรโมชัน (Promotions & Announcements - promotions.php)
   ========================================================================== */

/* ==========================================================================
   หมวดเสริม / Legacy Compatibility: ตรวจสอบความถูกต้องเบื้องต้น
   ========================================================================== */

/**
 * ฟังก์ชันตรวจสอบความถูกต้องของฟอร์มสมัครสมาชิก (Legacy Compatibility)
 * @description ตรวจสอบเบอร์โทรศัพท์ (10 หลัก) และตรวจสอบว่ารหัสผ่านตรงกับช่องยืนยันรหัสผ่านหรือไม่
 * @returns {boolean} คืนค่า true หากข้อมูลถูกต้อง หรือ false หากข้อมูลไม่ถูกต้องพร้อมแสดง Alert
 * @element ฟอร์มสมัครสมาชิกแบบดั้งเดิม (register.php)
 */
function validatePassword() {
    var password = document.getElementById("password").value;
    var confirmPassword = document.getElementById("confirm_password").value;
    var phone = document.getElementById("member_phone").value;

    // เช็คว่าเบอร์โทรเป็นตัวเลข 10 หลักหรือไม่
    if(!/^[0-9]{10}$/.test(phone)) {
        alert("กรุณากรอกเบอร์โทรศัพท์ให้ครบ 10 หลัก (เฉพาะตัวเลขเท่านั้น)");
        return false;
    }

    // เช็ครหัสผ่านให้ตรงกัน
    if (password !== confirmPassword) {
        alert("รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง!");
        return false;
    }
    return true;
}

/* ==========================================================================
   หมวดที่ 2: ระบบแจ้งเตือนสากล (Global Alert System)
   ========================================================================== */

/**
 * ระบบซ่อนแถบแจ้งเตือนสถานะอัตโนมัติ (Global Auto-dismiss Alert)
 * @description ดักจับแถบแจ้งเตือนความสำเร็จ/ข้อผิดพลาดทั้งหมดบนหน้าเว็บฝั่ง Member 
 *              และทำให้ค่อยๆ จางหายไป (Fade Out) พร้อมสไลด์ขึ้นอย่างนุ่มนวลภายในเวลา 3.5 วินาที
 * @event DOMContentLoaded (ทำงานทันทีเมื่อโครงสร้างหน้าเว็บโหลดเสร็จ)
 * @element .alert-success, .alert-error, .alert-box, .alert-success-box, .alert-error-box
 */
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        let alerts = document.querySelectorAll('.alert-success, .alert-error, .alert-box, .alert-success-box, .alert-error-box');
        alerts.forEach(alert => {
            alert.style.transition = "opacity 0.5s ease, transform 0.5s ease";
            alert.style.opacity = "0";
            alert.style.transform = "translateY(-6px)";
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        });
    }, 3500);
});

/**
 * ระบบควบคุมการส่งฟอร์มส่วนกลาง (Global Form Submission Interceptor)
 * @description ดักจับการส่งฟอร์มเพื่อป้องกัน Double Submission และแสดงสถานะกำลังโหลด (Loading State)
 *              ครอบคลุมฟอร์มโปรไฟล์ (.profile-form), ฟอร์มลืมรหัสผ่าน (#forgotForm), และฟอร์มที่มีคลาส .prevent-double-submit
 * @event submit บน document
 */
document.addEventListener('submit', function(e) {
    let form = e.target;
    if (!form || form.tagName !== 'FORM') return;

    // หากฟอร์มมี HTML5 Validation และข้อมูลยังไม่ผ่านเกณฑ์ ให้ยกเลิกและปล่อยให้เบราว์เซอร์แจ้งเตือน
    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
        return;
    }

    // กรณีฟอร์มลืมรหัสผ่าน (#forgotForm) ตรวจสอบเบอร์โทร 10 หลัก
    if (form.id === 'forgotForm') {
        let phoneInput = form.querySelector('#member_phone');
        if (phoneInput) {
            let phone = phoneInput.value.trim();
            if (!/^[0-9]{10}$/.test(phone)) {
                e.preventDefault();
                alert('กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้องครบ 10 หลัก (เฉพาะตัวเลขเท่านั้น)');
                phoneInput.focus();
                return;
            }
        }
    }

    // ตรวจสอบว่าเป็นฟอร์มที่ต้องการป้องกัน Double Submission หรือไม่
    if (form.classList.contains('profile-form') || form.classList.contains('prevent-double-submit') || form.id === 'forgotForm') {
        let submitBtn = form.querySelector('button[type="submit"]:not(:disabled)');
        if (submitBtn) {
            if (!submitBtn.dataset.originalHtml) {
                submitBtn.dataset.originalHtml = submitBtn.innerHTML;
            }
            let loadingText = submitBtn.dataset.loadingText || (form.id === 'forgotForm' ? 'กำลังส่งคำขอ...' : 'กำลังบันทึกข้อมูล...');
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-6"></i> <span>${loadingText}</span>`;
        }
    }
});

/* ==========================================================================
   หมวดที่ 1: ระบบนำทางและเมนูมือถือ (Navigation & Drawer Menu)
   ========================================================================== */

/**
 * เปิด-ปิด Dropdown เมนูโปรไฟล์ผู้ใช้งาน
 * @description สลับคลาส 'show' บน Element เมนูโปรไฟล์มุมขวาบนของ Navbar
 * @element #userDropdown (.user-dropdown / .dropdown-content)
 * @event onclick บนรูปโปรไฟล์หรือชื่อผู้ใช้งาน
 */
function toggleDropdown() {
    document.getElementById("userDropdown").classList.toggle("show");
}

/**
 * เปิด-ปิด เมนูสำหรับมือถือ (Mobile Navbar Collapse)
 * @description สลับคลาส 'show' บนเมนูหลักของ Navbar เมื่อเปิดดูบนอุปกรณ์หน้าจอเล็ก
 * @element #navMenu
 * @event onclick บนปุ่ม Navbar Toggle เดิม
 */
function toggleMobileMenu() {
    var navMenu = document.getElementById("navMenu");
    if (navMenu) {
        navMenu.classList.toggle("show");
    }
}

/**
 * เปิด-ปิด Offcanvas Drawer เมนูด้านข้างสำหรับมือถือ (Modern Responsive Drawer)
 * @description ควบคุมการสไลด์เข้า-ออกของ Drawer เมนู และแผ่นฟิล์ม Backdrop มืด 
 *              พร้อมทั้งล็อก Scroll ของ Body (body.drawer-open) ป้องกันการเลื่อนทะลุฉากหลัง
 * @element #mobileDrawer, #drawerBackdrop, body.drawer-open
 * @event onclick บนปุ่มแฮมเบอร์เกอร์ (☰) และปุ่มปิด Drawer
 */
function toggleMobileDrawer() {
    var drawer = document.getElementById("mobileDrawer");
    var backdrop = document.getElementById("drawerBackdrop");
    if (!drawer || !backdrop) return;

    var isOpen = drawer.classList.contains("show");
    if (isOpen) {
        drawer.classList.remove("show");
        backdrop.classList.remove("show");
        document.body.classList.remove("drawer-open");
    } else {
        drawer.classList.add("show");
        backdrop.classList.add("show");
        document.body.classList.add("drawer-open");
    }
}

/**
 * ตัวตรวจจับการกดปุ่มคีย์บอร์ด ESC เพื่อปิด Drawer และเมนูอัตโนมัติ (Accessibility Standard)
 * @description รองรับมาตรฐานการเข้าถึง (Accessibility) เมื่อผู้ใช้กดปุ่ม 'Escape' จะทำการปิด Drawer และ Backdrop ทันที
 * @param {KeyboardEvent} event - อ็อบเจกต์อีเวนต์การกดคีย์บอร์ด
 * @event keydown
 */
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        var drawer = document.getElementById("mobileDrawer");
        var backdrop = document.getElementById("drawerBackdrop");
        if (drawer && drawer.classList.contains("show")) {
            drawer.classList.remove("show");
            if (backdrop) backdrop.classList.remove("show");
            document.body.classList.remove("drawer-open");
        }
    }
});

/**
 * ตัวตรวจจับการคลิกภายนอกพื้นที่เพื่อปิด Dropdown เมนูโปรไฟล์อัตโนมัติ (Click Outside Handler)
 * @description เมื่อคลิกที่บริเวณใดๆ นอกเหนือจากกล่อง .user-dropdown จะสั่งลบคลาส 'show' เพื่อพับเก็บเมนูทันที
 * @param {MouseEvent} event - อ็อบเจกต์อีเวนต์การคลิกเมาส์
 * @event window.onclick
 */
window.onclick = function(event) {
    if (!event.target.closest('.user-dropdown')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
}

/* ==========================================================================
   หมวดที่ 6: กลไกการคำนวณราคาแบบ Real-time (Dynamic Pricing Engine)
   ========================================================================== */

/**
 * แปลงวัตถุ Date เป็นสตริงวันที่ในรูปแบบ 'YYYY-MM-DD' ตาม Local Timezone
 * @description ป้องกันปัญหา Timezone Offset (UTC Shift) ที่อาจทำให้วันที่คลาดเคลื่อนถอยหลัง 1 วัน
 * @param {Date} [d=new Date()] - วัตถุ Date ที่ต้องการแปลง (ค่าเริ่มต้นคือวันและเวลาปัจจุบัน)
 * @returns {string} สตริงวันที่ในรูปแบบ 'YYYY-MM-DD' เช่น '2026-10-17'
 */
function getLocalDateString(d = new Date()) {
    let year = d.getFullYear();
    let month = String(d.getMonth() + 1).padStart(2, '0');
    let day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * คำนวณราคาค่าบริการสนามแบดมินตันแบบ Dynamic Pricing Engine
 * @description หัวใจหลักของการคำนวณราคา:
 *              1. วันธรรมดา (จันทร์ - ศุกร์):
 *                 - ช่วงกลางวัน (08:00 - 17:00 น.): 150 บาท/ชม. (Off-peak)
 *                 - ช่วงค่ำ (17:00 - 23:00 น.): 180 บาท/ชม. (Standard)
 *              2. วันหยุด (เสาร์ - อาทิตย์): 200 บาท/ชม. ตลอดทั้งวัน (Peak)
 *              3. การจองคาบเกี่ยวข้ามช่วงเวลา (Cross-Window เช่น 16:00 - 18:00 น.):
 *                 จะแบ่งคิดคำนวณตามชั่วโมงจริงของแต่ละช่วงแล้วนำมารวมกันอย่างแม่นยำ
 * @param {string} dateStr - วันที่ที่เลือกในรูปแบบ 'YYYY-MM-DD'
 * @param {string} startTimeStr - เวลาเริ่มต้น เช่น '16:00'
 * @param {string} endTimeStr - เวลาสิ้นสุด เช่น '18:00'
 * @param {Object|null} [customRates=null] - อัตราค่าบริการพิเศษ { peak, offpeak, standard } (ถ้ามี)
 * @returns {Object} ผลลัพธ์การคำนวณ { status: 'success'|'error', totalPrice, totalHours, rateType, breakdown: [...] }
 * @element คำนวณอัตโนมัติเมื่อเลือกสล็อตเวลาใน Step 2 & 3 และตรวจสอบในใบแจ้งหนี้ Step 4
 */
function calculateCourtBookingPrice(dateStr, startTimeStr, endTimeStr, customRates = null) {
    if (!startTimeStr || !endTimeStr || !dateStr) {
        return { status: 'error', message: 'กรุณาระบุวันที่และเวลาให้ครบถ้วน' };
    }

    // 1. ตรวจสอบความถูกต้องของวันที่ (YYYY-MM-DD และต้องมีอยู่จริงตามปฏิทิน)
    let dateParts = dateStr.trim().split('-');
    if (dateParts.length !== 3) {
        return { status: 'error', message: 'รูปแบบวันที่ไม่ถูกต้อง' };
    }
    let yr = parseInt(dateParts[0], 10);
    let mo = parseInt(dateParts[1], 10);
    let da = parseInt(dateParts[2], 10);
    let checkD = new Date(yr, mo - 1, da);
    if (checkD.getFullYear() !== yr || checkD.getMonth() !== (mo - 1) || checkD.getDate() !== da) {
        return { status: 'error', message: 'วันที่ระบุไม่มีอยู่จริงตามปฏิทิน' };
    }

    // 2. ตรวจสอบรูปแบบเวลาอย่างเข้มงวด (00:00 - 23:59 ไม่อนุญาต 24:00)
    let timeRegex = /^([01]\d|2[0-3]):([0-5]\d)/;
    let sMatch = startTimeStr.trim().match(timeRegex);
    let eMatch = endTimeStr.trim().match(timeRegex);
    if (!sMatch || !eMatch) {
        return { status: 'error', message: 'รูปแบบเวลาไม่ถูกต้อง' };
    }

    let startDec = parseInt(sMatch[1], 10) + (parseInt(sMatch[2], 10) / 60);
    let endDec = parseInt(eMatch[1], 10) + (parseInt(eMatch[2], 10) / 60);

    if (startDec >= endDec) {
        return { status: 'error', message: 'เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด' };
    }
    if (startDec < 8 || endDec > 23) {
        return { status: 'error', message: 'ช่วงเวลาที่เลือกอยู่นอกเวลาทำการ (08:00 - 23:00 น.)' };
    }

    let hours = endDec - startDec;
    if (hours < 1) {
        return { status: 'error', message: 'ระยะเวลาการจองต้องอย่างน้อย 1 ชั่วโมง' };
    }

    // 3. กำหนดวันในสัปดาห์แบบ Local Time (ป้องกันปัญหา UTC Timezone Shift)
    let dow = checkD.getDay(); // 0 = อาทิตย์, 6 = เสาร์

    let peakRate = (customRates && customRates.peak) ? customRates.peak : 200;
    let offpeakRate = (customRates && customRates.offpeak) ? customRates.offpeak : 150;
    let standardRate = (customRates && customRates.standard) ? customRates.standard : 180;

    if (dow === 0 || dow === 6) {
        let total = hours * peakRate;
        if (total <= 0) {
            return { status: 'error', message: 'เกิดข้อผิดพลาดในการคำนวณราคา' };
        }
        return {
            status: 'success',
            totalPrice: total,
            totalHours: hours,
            rateType: 'weekend',
            daytimeHours: 0,
            eveningHours: 0,
            weekendHours: hours,
            breakdown: [{ label: 'เสาร์ - อาทิตย์ (ทุกช่วงเวลา)', hours: hours, rate: peakRate, subtotal: total }]
        };
    }

    // 4. วันจันทร์-ศุกร์: Cross-window 08:00-17:00 (กลางวัน 150) / 17:00-23:00 (เย็น 180)
    let dayHours = Math.max(0, Math.min(endDec, 17) - Math.max(startDec, 8));
    let eveHours = Math.max(0, Math.min(endDec, 23) - Math.max(startDec, 17));

    let dayPrice = dayHours * offpeakRate;
    let evePrice = eveHours * standardRate;
    let total = dayPrice + evePrice;

    if (total <= 0) {
        return { status: 'error', message: 'เกิดข้อผิดพลาดในการคำนวณราคา กรุณาตรวจสอบช่วงเวลาการจอง' };
    }

    let breakdown = [];
    if (dayHours > 0) {
        breakdown.push({ label: 'ช่วงกลางวัน (08:00 - 17:00 น.)', hours: dayHours, rate: offpeakRate, subtotal: dayPrice });
    }
    if (eveHours > 0) {
        breakdown.push({ label: 'ช่วงเย็น (17:00 - 23:00 น.)', hours: eveHours, rate: standardRate, subtotal: evePrice });
    }

    return {
        status: 'success',
        totalPrice: total,
        totalHours: hours,
        rateType: (dayHours > 0 && eveHours > 0) ? 'cross_window' : (dayHours > 0 ? 'daytime' : 'evening'),
        daytimeHours: dayHours,
        eveningHours: eveHours,
        weekendHours: 0,
        breakdown: breakdown
    };
}

/* ==========================================================================
   หมวดที่ 7: ตัวควบคุมขั้นตอน Wizard State Machine (Multi-Step Controller)
   ========================================================================== */

/**
 * อ็อบเจกต์ศูนย์กลางเก็บสถานะการจอง (Central State Machine)
 * @description จัดเก็บข้อมูลสถานะปัจจุบันทั้งหมดของระบบการจองแบบ 5 ขั้นตอน (Progressive Disclosure)
 *              รวมถึงวันที่, เดือนที่เปิดดู, สล็อตเวลา, สนามที่เลือก, อุปกรณ์เช่า และยอดเงินรวม
 */
const wizardState = {
    currentStep: 1,
    selectedDate: getLocalDateString(new Date()),
    currentCalMonth: new Date().getMonth(),
    currentCalYear: new Date().getFullYear(),
    dayRateCategory: 'weekday',
    dayRatePrice: 180,
    dayRateLabel: 'จันทร์ - ศุกร์ (กลางวัน 150 ฿ | เย็น 180 ฿/ชม.)',
    selectedStartTime: '',
    bookingNickname: '',
    matrixData: null,
    // Map of slotStart => { courtId, courtName, price, slotStart, slotEnd }
    selectedSlots: new Map(),
    selectedCourtId: null,
    selectedCourtName: '',
    selectedHours: 0,
    courtPriceTotal: 0,
    rentalPriceTotal: 0,
    productPriceTotal: 0,
    grandTotal: 0
};

/**
 * รายชื่อเดือนภาษาไทยสำหรับแสดงผลบนปฏิทิน
 * @type {string[]}
 */
const thaiMonthNames = [
    "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
    "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];

/**
 * รายชื่อวันในสัปดาห์ภาษาไทย (แบบย่อ) สำหรับส่วนหัวของปฏิทิน
 * @type {string[]}
 */
const thaiDayNames = ["อา.", "จ.", "อ.", "พ.", "พฤ.", "ศ.", "ส."];

/**
 * เริ่มต้นการทำงานของระบบจองสนามแบบ Multi-Step Wizard
 * @description ตรวจสอบว่ามีข้อมูลการจองที่ค้างอยู่ (Pending Booking จากการล็อกอิน) หรือไม่
 *              หากมีจะสั่งเรียก restorePendingBooking() เพื่อกู้คืนสถานะ
 *              หากไม่มี จะกำหนดวันที่เริ่มต้นเป็นวันนี้ เรนเดอร์ปฏิทิน และโหลดข้อมูลสถานะสนามผ่าน AJAX
 * @element #pendingBookingData, #input_booking_date, #step3_nickname
 * @event เรียกใช้งานทันทีเมื่อโหลดหน้า booking.php
 */
function initBookingWizard() {
    let pendingEl = document.getElementById('pendingBookingData');
    if (pendingEl) {
        try {
            let pData = JSON.parse(pendingEl.textContent);
            if (pData && pData.booking_date) {
                restorePendingBooking(pData);
                return;
            }
        } catch (e) {
            console.error("Failed to parse pending booking:", e);
        }
    }

    let nicknameInput = document.getElementById('step3_nickname');
    if (nicknameInput) {
        wizardState.bookingNickname = nicknameInput.value.trim();
    }
    
    let inputDate = document.getElementById('input_booking_date');
    let todayStr = (inputDate && inputDate.value) ? inputDate.value : getLocalDateString(new Date());
    wizardState.selectedDate = todayStr;
    
    let dParts = todayStr.split('-').map(Number);
    if (dParts.length === 3) {
        wizardState.currentCalYear = dParts[0];
        wizardState.currentCalMonth = dParts[1] - 1;
    } else {
        wizardState.currentCalMonth = new Date().getMonth();
        wizardState.currentCalYear = new Date().getFullYear();
    }

    if (inputDate) inputDate.value = todayStr;

    // Render Calendar and load court data
    renderCalendar();
    loadCourtMatrixForWizard(todayStr);
    updateWizardStepperUI(1);
    updateStep4NextButton();
}

/**
 * กู้คืนข้อมูลการจองที่ค้างอยู่กรณีผู้ใช้ทั่วไป (Guest) ไปเข้าสู่ระบบแล้วถูกส่งกลับมา
 * @description อ่านข้อมูล JSON จาก Session/DOM นำมาเซ็ตค่ากลับคืนใน wizardState ทั้งวันที่, เวลาเริ่มต้น,
 *              เวลาสิ้นสุด, คอร์ทที่เลือก, อุปกรณ์เช่า, สล็อตชั่วโมง และนำทางตรงไปยังขั้นตอนสรุปรายการ (Step 5)
 * @param {Object} pData - อ็อบเจกต์ข้อมูลการจองเดิมที่เก็บค้างไว้ใน Session
 */
function restorePendingBooking(pData) {
    let nicknameInput = document.getElementById('step3_nickname');
    if (nicknameInput && pData.booking_nickname) {
        nicknameInput.value = pData.booking_nickname;
        wizardState.bookingNickname = pData.booking_nickname;
    }
    let inputNickname = document.getElementById('input_booking_nickname');
    if (inputNickname && pData.booking_nickname) {
        inputNickname.value = pData.booking_nickname;
    }

    let dateStr = pData.booking_date;
    wizardState.selectedDate = dateStr;
    wizardState.selectedStartTime = pData.start_time;

    let dParts = dateStr.split('-').map(Number);
    if (dParts.length === 3) {
        wizardState.currentCalYear = dParts[0];
        wizardState.currentCalMonth = dParts[1] - 1;
    }

    let inputDate = document.getElementById('input_booking_date');
    if (inputDate) inputDate.value = dateStr;
    let inputStart = document.getElementById('input_start_time');
    if (inputStart) inputStart.value = pData.start_time;
    let inputEnd = document.getElementById('input_end_time');
    if (inputEnd) inputEnd.value = pData.end_time;
    let inputCourt = document.getElementById('input_court_id');
    if (inputCourt) inputCourt.value = pData.court_id;

    renderCalendar();

    loadCourtMatrixForWizard(dateStr).then(() => {
        let targetCourtId = parseInt(pData.court_id);
        let targetCourt = wizardState.matrixData && wizardState.matrixData.courts 
            ? wizardState.matrixData.courts.find(c => c.court_id === targetCourtId) 
            : null;
        let courtName = targetCourt ? targetCourt.court_name : ('สนาม ' + targetCourtId);

        let startH = parseInt(pData.start_time.split(':')[0]);
        let endH = parseInt(pData.end_time.split(':')[0]);
        wizardState.selectedSlots.clear();

        for (let h = startH; h < endH; h++) {
            let sStart = String(h).padStart(2, '0') + ':00:00';
            let sEnd = String(h + 1).padStart(2, '0') + ':00:00';
            let sStartKey = String(h).padStart(2, '0') + ':00';
            let sInfo = (targetCourt && targetCourt.slots) ? (targetCourt.slots[sStartKey] || targetCourt.slots[sStart]) : null;
            let dow = wizardState.matrixData ? wizardState.matrixData.day_of_week : new Date(dateStr).getDay();
            let sPrice = sInfo ? sInfo.price : (dow === 0 || dow === 6 ? 200 : (h < 17 ? 150 : 180));

            wizardState.selectedSlots.set(sStart, {
                courtId: targetCourtId,
                courtName: courtName,
                price: sPrice,
                slotStart: sStart,
                slotEnd: sEnd
            });
        }

        // Restore products
        if (pData.products && typeof pData.products === 'object') {
            for (let pId in pData.products) {
                let qty = parseInt(pData.products[pId]) || 0;
                let qtyInput = document.getElementById('prod_qty_' + pId) || document.querySelector(`input[name="products[${pId}]"]`);
                if (qtyInput) {
                    qtyInput.value = qty;
                }
            }
        }

        updateStep4Summary();
        updateInvoiceReview();
        goToStep(5);
    }).catch(err => {
        console.error("Error restoring pending booking matrix:", err);
        goToStep(1);
    });
}


/**
 * ควบคุมการเปลี่ยนขั้นตอนใน Booking Wizard พร้อมระบบตรวจสอบความถูกต้อง (Validation Guard)
 * @description ทำการ Validate เงื่อนไขก่อนก้าวไปข้างหน้า:
 *              - สเต็ป 2: ต้องเลือกวันที่ก่อน
 *              - สเต็ป 3: ต้องเลือกเวลาเริ่มต้นก่อน
 *              - สเต็ป 4: ต้องเลือกสนามอย่างน้อย 1 ช่วงเวลาก่อน
 *              - สเต็ป 5: อัปเดตใบแจ้งหนี้แจกแจงค่าใช้จ่าย
 *              เมื่อผ่านจะสลับคลาส active ของแผงสเต็ป และเลื่อนหน้าจอกลับขึ้นด้านบนแบบ Smooth
 * @param {number} targetStep - หมายเลขขั้นตอนปลายทางที่ต้องการไป (1 - 5)
 * @element #stepPanel1 - #stepPanel5, .wizard-page-wrapper
 */
function goToStep(targetStep) {
    if (targetStep < 1 || targetStep > 5) return;

    // Validation before stepping forward
    if (targetStep > wizardState.currentStep) {
        if (targetStep === 2) {
            if (!wizardState.selectedDate) {
                alert("กรุณาเลือกวันที่ต้องการใช้บริการ");
                return;
            }
            if (!wizardState.matrixData || wizardState.matrixData.date !== wizardState.selectedDate) {
                renderTimeSlots();
                loadCourtMatrixForWizard(wizardState.selectedDate);
            } else {
                renderTimeSlots();
            }
        } else if (targetStep === 3) {
            if (!wizardState.selectedStartTime) {
                alert("กรุณาเลือกเวลาเริ่มต้น");
                return;
            }
            renderCourtHourBlocks();
        } else if (targetStep === 4) {
            if (wizardState.selectedSlots.size === 0) {
                alert("กรุณาเลือกสนามอย่างน้อย 1 ช่วงเวลา");
                return;
            }
            updateStep4Summary();
        } else if (targetStep === 5) {
            updateInvoiceReview();
        }
    } else {
        // If stepping backwards
        if (targetStep === 2) {
            renderTimeSlots();
        } else if (targetStep === 3) {
            renderCourtHourBlocks();
        } else if (targetStep === 4) {
            updateStep4Summary();
        }
    }

    wizardState.currentStep = targetStep;

    // Hide all panels & show target panel
    for (let s = 1; s <= 5; s++) {
        let panel = document.getElementById('stepPanel' + s);
        if (panel) {
            if (s === targetStep) {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }
        }
    }

    updateWizardStepperUI(targetStep);

    // Scroll to top of wizard
    let wrapper = document.querySelector('.wizard-page-wrapper');
    if (wrapper) {
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/**
 * อนุญาตให้ผู้ใช้คลิกย้อนกลับไปยังขั้นตอนที่ผ่านมาแล้วได้ทันที (Jump Back Handler)
 * @description ป้องกันไม่ให้คลิกข้ามไปยังขั้นตอนข้างหน้าที่ยังไม่ผ่านการกรอกข้อมูล
 * @param {number} targetStep - ขั้นตอนที่ต้องการย้อนกลับไป
 */
function jumpToWizardStep(targetStep) {
    if (targetStep < wizardState.currentStep) {
        goToStep(targetStep);
    }
}

/**
 * อัปเดตการแสดงผลของ Stepper Progress Bar ด้านบน
 * @description ปรับเปลี่ยนสถานะคลาส active, completed, upcoming บนแต่ละปุ่มขั้นตอน
 *              และคำนวณความกว้างเปอร์เซ็นต์ของแถบสีความคืบหน้า (Fill Bar)
 * @param {number} currentStep - ขั้นตอนปัจจุบัน (1 - 5)
 * @element #stepperNode1 - #stepperNode5, #stepperFillBar
 */
function updateWizardStepperUI(currentStep) {
    for (let s = 1; s <= 5; s++) {
        let node = document.getElementById('stepperNode' + s);
        if (!node) continue;

        node.classList.remove('active', 'completed', 'upcoming');
        if (s < currentStep) {
            node.classList.add('completed');
        } else if (s === currentStep) {
            node.classList.add('active');
        } else {
            node.classList.add('upcoming');
        }
    }

    let fillBar = document.getElementById('stepperFillBar');
    if (fillBar) {
        let percent = ((currentStep - 1) / 4) * 100;
        fillBar.style.width = percent + '%';
    }
}

/* ==========================================================================
   หมวดที่ 3: ระบบปฏิทินและเลือกวันจอง (Calendar & Date Picker - Wizard Step 1)
   ========================================================================== */

/**
 * เรนเดอร์ตารางปฏิทินรายเดือนแบบ Interactive พร้อมตรวจสอบสถานะวันที่
 * @description สร้างเซลล์วันที่ตามปฏิทินจริง พร้อมคำนวณวันเริ่มต้นของสัปดาห์ (Sunday-first),
 *              กำหนดสีแยกวันหยุดเสาร์-อาทิตย์, ล็อกวันที่ในอดีตไม่ให้คลิกเลือกได้ (disabled),
 *              และไฮไลต์วันปัจจุบัน (today) รวมถึงวันที่ผู้ใช้กำลังเลือก (selected)
 * @element #calMonthTitle, #calendarGrid
 */
function renderCalendar() {
    let titleEl = document.getElementById('calMonthTitle');
    let gridEl = document.getElementById('calendarGrid');
    if (!titleEl || !gridEl) return;

    let year = wizardState.currentCalYear;
    let month = wizardState.currentCalMonth;
    titleEl.innerText = `${thaiMonthNames[month]} ${year + 543} (${year})`;

    let html = '';
    // Day headers
    thaiDayNames.forEach((dName, idx) => {
        let weekendClass = (idx === 0 || idx === 6) ? ' weekend' : '';
        html += `<div class="calendar-day-header${weekendClass}">${dName}</div>`;
    });

    // First day of month & number of days
    let firstDayIndex = new Date(year, month, 1).getDay();
    let daysInMonth = new Date(year, month + 1, 0).getDate();

    // Empty cells before first day
    for (let e = 0; e < firstDayIndex; e++) {
        html += `<div class="calendar-date-cell empty"></div>`;
    }

    let today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let day = 1; day <= daysInMonth; day++) {
        let cellDate = new Date(year, month, day);
        cellDate.setHours(0, 0, 0, 0);

        let dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        let isPast = cellDate < today;
        let isToday = cellDate.getTime() === today.getTime();
        let isSelected = dateStr === wizardState.selectedDate;

        let classes = ['calendar-date-cell'];
        if (isPast) classes.push('disabled');
        if (isToday) classes.push('today');
        if (isSelected) classes.push('selected');

        let clickAttr = isPast ? '' : `onclick="onSelectCalendarDate('${dateStr}')"`;
        html += `<div class="${classes.join(' ')}" ${clickAttr}>${day}</div>`;
    }

    gridEl.innerHTML = html;
}

/**
 * เปลี่ยนเดือนบนหน้าปฏิทิน (ก่อนหน้า / ถัดไป)
 * @description ปรับค่า currentCalMonth และ currentCalYear ใน wizardState เมื่อข้ามปี แล้วสั่งเรนเดอร์ปฏิทินใหม่
 * @param {number} dir - ทิศทางการเปลี่ยน (-1 สำหรับเดือนก่อนหน้า, +1 สำหรับเดือนถัดไป)
 * @element #calPrevMonthBtn, #calNextMonthBtn
 */
function navigateCalendarMonth(dir) {
    wizardState.currentCalMonth += dir;
    if (wizardState.currentCalMonth < 0) {
        wizardState.currentCalMonth = 11;
        wizardState.currentCalYear--;
    } else if (wizardState.currentCalMonth > 11) {
        wizardState.currentCalMonth = 0;
        wizardState.currentCalYear++;
    }
    renderCalendar();
}

/**
 * ปุ่มลัดเลือกวันที่ใช้งานอย่างรวดเร็ว (Quick Date Buttons)
 * @description ช่วยอำนวยความสะดวกให้ผู้ใช้งานสามารถคลิกเลือก "วันนี้" หรือ "พรุ่งนี้" ได้ทันทีในคลิกเดียว
 * @param {string} type - ชนิดของวันลัด ('today' หรือ 'tomorrow')
 * @element #btnQuickToday, #btnQuickTomorrow
 */
function selectQuickDate(type) {
    let target = new Date();
    if (type === 'tomorrow') {
        target.setDate(target.getDate() + 1);
    }
    let dateStr = getLocalDateString(target);

    document.querySelectorAll('.btn-quick-date').forEach(b => b.classList.remove('active'));
    let btn = (type === 'tomorrow') ? document.getElementById('btnQuickTomorrow') : document.getElementById('btnQuickToday');
    if (btn) btn.classList.add('active');

    wizardState.currentCalMonth = target.getMonth();
    wizardState.currentCalYear = target.getFullYear();

    onSelectCalendarDate(dateStr);
}

/**
 * จัดการเมื่อผู้ใช้คลิกเลือกวันที่บนเซลล์ปฏิทิน
 * @description ทำการเซ็ตค่าวันที่ที่เลือกลงใน State, รีเซ็ตสล็อตเวลาและสนามที่เคยเลือกไว้เดิม (เพื่อป้องกันข้อมูลข้ามวัน),
 *              ไฮไลต์เซลล์ปฏิทินใหม่ และส่งคำขอ AJAX โหลดตารางสถานะสนามของวันนั้น
 * @param {string} dateStr - วันที่ที่เลือกในรูปแบบ 'YYYY-MM-DD'
 * @element #input_booking_date, .calendar-date-cell
 */
function onSelectCalendarDate(dateStr) {
    wizardState.selectedDate = dateStr;
    let inputDate = document.getElementById('input_booking_date');
    if (inputDate) inputDate.value = dateStr;

    // Reset slot selection if date changes
    wizardState.selectedSlots.clear();
    wizardState.selectedCourtId = null;
    wizardState.selectedCourtName = '';
    wizardState.selectedHours = 0;
    wizardState.courtPriceTotal = 0;

    renderCalendar();
    loadCourtMatrixForWizard(dateStr);
}

/**
 * โหลดข้อมูล Matrix สถานะสนามและความพร้อมของสล็อตเวลาผ่าน AJAX
 * @description ติดต่อกับ backend ผ่าน actions/get_court_matrix.php?date=... เพื่อดึงสถานะสนามทั้ง 3 คอร์ท
 *              พร้อมอัตราค่าบริการของวันนั้น (วันธรรมดา / วันหยุด) และสั่งเรนเดอร์หน้าจอตามขั้นตอนปัจจุบัน
 * @param {string} dateStr - วันที่ที่ต้องการตรวจสอบสถานะสนาม 'YYYY-MM-DD'
 * @returns {Promise} สัญญา Promise ของคำขอ fetch
 * @element #datePriceBanner, #datePriceText, #timeSlotGrid
 */
function loadCourtMatrixForWizard(dateStr) {
    let banner = document.getElementById('datePriceBanner');
    let textSpan = document.getElementById('datePriceText');
    if (textSpan) textSpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังตรวจสอบอัตราค่าบริการ...';

    let timeGrid = document.getElementById('timeSlotGrid');
    if (timeGrid && wizardState.currentStep === 2 && (!wizardState.matrixData || wizardState.matrixData.date !== dateStr)) {
        timeGrid.innerHTML = '<p class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin fa-lg"></i> กำลังโหลดช่วงเวลา...</p>';
    }

    return fetch('actions/get_court_matrix.php?date=' + encodeURIComponent(dateStr))
    .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    })
    .then(res => {
        if (res.status === 'success') {
            wizardState.matrixData = res;
            wizardState.dayRateCategory = res.rate_category;
            wizardState.dayRateLabel = res.rate_label;

            let dow = res.day_of_week;
            let ratePrice = (dow === 0 || dow === 6) ? 200 : 180;
            wizardState.dayRatePrice = ratePrice;

            if (banner && textSpan) {
                banner.className = 'date-price-banner ' + res.rate_category;
                textSpan.innerHTML = `อัตราค่าบริการสำหรับ <strong>${res.day_name} (${formatDateThai(dateStr)})</strong>: <span class="price-tag-value">${res.rate_label}</span>`;
            }

            // [HCI CRITICAL]: เมื่อข้อมูลเดินทางมาถึง ให้ Render หน้าจอขั้นตอนปัจจุบันทันที
            if (wizardState.currentStep === 2) {
                renderTimeSlots();
            } else if (wizardState.currentStep === 3) {
                renderCourtHourBlocks();
            }
        } else {
            throw new Error(res.message || 'ไม่สามารถโหลดข้อมูลสนามได้');
        }
    })
    .catch(err => {
        console.error('Court matrix fetch error:', err);
        if (textSpan) textSpan.innerText = 'ไม่สามารถดึงข้อมูลอัตราค่าบริการได้';
        if (timeGrid && wizardState.currentStep === 2) {
            timeGrid.innerHTML = `
                <div class="col-12 text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>เกิดข้อผิดพลาดในการโหลดช่วงเวลา (${err.message})</p>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="loadCourtMatrixForWizard('${dateStr}')">
                        <i class="fas fa-redo"></i> ลองใหม่อีกครั้ง
                    </button>
                </div>
            `;
        }
    });
}

/**
 * แปลงสตริงวันที่รูปแบบ ISO (YYYY-MM-DD) เป็นรูปแบบวันที่ไทย (DD/MM/YYYY พ.ศ.)
 * @param {string} dateStr - วันที่ในรูปแบบ 'YYYY-MM-DD'
 * @returns {string} วันที่ไทย เช่น '17/10/2569' หรือ '-' หากไม่มีข้อมูล
 */
function formatDateThai(dateStr) {
    if (!dateStr) return '-';
    let parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    return `${parts[2]}/${parts[1]}/${parseInt(parts[0]) + 543}`;
}

/* ==========================================================================
   หมวดที่ 4: ระบบเลือกเวลาและตารางคอร์ท (Time Slots & Court Matrix - Wizard Step 2 & 3)
   ========================================================================== */

/**
 * เรนเดอร์การ์ดช่วงเวลาเปิดให้บริการเริ่มต้น (Operating Hours Grid)
 * @description สร้างการ์ดเวลาเริ่มต้นตั้งแต่ 09:00 ถึง 23:00 น. 
 *              ตรวจสอบช่วงเวลาที่เลยเวลาปัจจุบันแล้ว (Past Hour) หรือช่วงเวลาที่สนามปิดบริการทั้งหมด
 *              พร้อมปรับสถานะคลาส disabled และป้องกันการคลิก
 * @element #timeSlotGrid, .time-slot-card
 */
function renderTimeSlots() {
    let container = document.getElementById('timeSlotGrid');
    if (!container) return;

    if (!wizardState.matrixData || wizardState.matrixData.date !== wizardState.selectedDate || !wizardState.matrixData.slots) {
        container.innerHTML = '<p class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin fa-lg"></i> กำลังโหลดช่วงเวลา...</p>';
        return;
    }

    let slots = wizardState.matrixData.slots;
    let courts = wizardState.matrixData.courts;

    let now = new Date();
    let isToday = (wizardState.selectedDate === getLocalDateString(now));
    let currentHour = now.getHours();

    let html = '';
    slots.forEach(slot => {
        let slotHour = parseInt(slot.start.split(':')[0]);
        let isPastHour = isToday && (slotHour <= currentHour);

        // ตรวจสอบว่าช่วงเวลานี้เปิดให้บริการหรือไม่ (เช็คจากสนามทั้งหมด)
        let hasOpenCourt = false;
        if (courts) {
            courts.forEach(c => {
                let sInfo = c.slots ? c.slots[slot.start] : null;
                if (sInfo && sInfo.status !== 'closed') {
                    hasOpenCourt = true;
                }
            });
        }

        let isDisabled = isPastHour || !hasOpenCourt;
        let statusText = 'เปิดให้บริการ';
        let cardClass = 'time-slot-card';

        if (isPastHour) {
            statusText = 'เลยเวลาแล้ว';
            cardClass += ' disabled';
        } else if (!hasOpenCourt) {
            statusText = 'นอกเวลาทำการ';
            cardClass += ' disabled';
        }

        let clickAttr = isDisabled ? '' : `onclick="onSelectStartTime('${slot.start}')"`;

        html += `
            <div class="${cardClass}" ${clickAttr} title="${slot.start} - ${slot.end}">
                <i class="fas fa-clock"></i>
                <div class="time-slot-time">${slot.start} น.</div>
                <div class="time-slot-status">${statusText}</div>
            </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * จัดการเมื่อผู้ใช้เลือกเวลาเริ่มต้นที่ต้องการจอง
 * @description บันทึกค่าเวลาเริ่มต้นลงใน wizardState และ input ซ่อน แล้วนำทางเข้าสู่ Step 3 ทันที (HCI Progressive Disclosure)
 * @param {string} startStr - เวลาเริ่มต้นที่เลือก เช่น '14:00'
 * @element #input_start_time
 */
function onSelectStartTime(startStr) {
    wizardState.selectedStartTime = startStr;
    let inputStart = document.getElementById('input_start_time');
    if (inputStart) inputStart.value = startStr;

    // Transition immediately to Step 3 as required by HCI Progressive Disclosure
    goToStep(3);
}

/**
 * เรนเดอร์ตารางเลือกคอร์ทสนามแบบรายชั่วโมง (Court Hour Blocks Grid)
 * @description สร้างบล็อกชั่วโมงเริ่มต้นตั้งแต่เวลาที่ผู้ใช้เลือก พร้อมทั้งแสดงชั่วโมงถัดไปเพื่อให้สามารถจองต่อเนื่องได้
 *              ตรวจสอบสถานะแต่ละสนาม (Available ว่าง, Booked จองแล้ว, Pending รอตรวจสอบ, Maintenance ปิดปรับปรุง)
 *              และไฮไลต์คอร์ทที่ผู้ใช้กำลังเลือกอยู่
 * @element #courtHourBlocksContainer, .court-card-v2
 */
function renderCourtHourBlocks() {
    let container = document.getElementById('courtHourBlocksContainer');
    if (!container || !wizardState.matrixData) return;

    let slots = wizardState.matrixData.slots;
    let courts = wizardState.matrixData.courts;
    let startHour = parseInt(wizardState.selectedStartTime.split(':')[0]);

    // กรองเฉพาะช่วงเวลาที่เริ่มตั้งแต่ selectedStartTime เป็นต้นไป
    let availableSlots = slots.filter(s => parseInt(s.start.split(':')[0]) >= startHour);

    let dow = wizardState.matrixData ? wizardState.matrixData.day_of_week : new Date(wizardState.selectedDate).getDay();

    let html = '';
    availableSlots.forEach((slot, index) => {
        let isFirstHour = (index === 0);
        let hourLabel = isFirstHour 
            ? `ชั่วโมงที่ 1 : ${slot.start} - ${slot.end} น.` 
            : `ชั่วโมงถัดไป (จองต่อเนื่อง) : ${slot.start} - ${slot.end} น.`;

        let slotHour = parseInt(slot.start.split(':')[0]);
        let slotRateLabel = (dow === 0 || dow === 6) ? 'วันหยุด (200 ฿/ชม.)' : (slotHour < 17 ? 'กลางวัน (150 ฿/ชม.)' : 'ช่วงเย็น (180 ฿/ชม.)');

        html += `
            <div class="slot-hour-block">
                <div class="slot-hour-header">
                    <div class="slot-hour-title">
                        <i class="fas ${isFirstHour ? 'fa-star text-warning' : 'fa-clock text-primary'}"></i>
                        ${hourLabel}
                    </div>
                    <span class="slot-rate-badge">
                        <i class="fas fa-tag"></i> ${slotRateLabel}
                    </span>
                </div>
                <div class="court-cards-grid">
        `;

        courts.forEach(court => {
            let sInfo = court.slots ? court.slots[slot.start] : null;
            let status = sInfo ? sInfo.status : 'closed';
            let bookedBy = sInfo ? sInfo.booked_by : '';
            let price = sInfo ? sInfo.price : (dow === 0 || dow === 6 ? 200 : (slotHour < 17 ? 150 : 180));

            // ตรวจสอบว่า slot นี้ถูกเลือกไว้ใน state หรือไม่
            let isCurrentSlotSelected = false;
            let selectedEntry = wizardState.selectedSlots.get(slot.start);
            if (selectedEntry && selectedEntry.courtId === court.court_id) {
                isCurrentSlotSelected = true;
            }

            let cardClass = 'court-card-v2 ';
            let userContent = '';
            let userTitle = '';
            let badgeText = '';
            let clickAttr = '';

            let safeBookedBy = String(bookedBy || 'ผู้ใช้งาน').replace(/[&<>"']/g, m => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[m]));

            if (isCurrentSlotSelected) {
                cardClass += 'status-selected';
                badgeText = '✓ กำลังเลือก';
                clickAttr = `onclick="toggleCourtSlot('${slot.start}', '${slot.end}', ${court.court_id}, '${court.court_name}', ${price})"`;
            } else if (status === 'available') {
                cardClass += 'status-available';
                badgeText = '● ว่าง';
                clickAttr = `onclick="toggleCourtSlot('${slot.start}', '${slot.end}', ${court.court_id}, '${court.court_name}', ${price})"`;
            } else if (status === 'pending') {
                cardClass += 'status-pending';
                userContent = `<i class="fas fa-user-clock"></i> <span class="booked-name">${safeBookedBy}</span>`;
                userTitle = `รอตรวจสอบการชำระเงิน (${safeBookedBy})`;
                badgeText = '● รอตรวจสอบ';
            } else if (status === 'booked') {
                cardClass += 'status-booked';
                userContent = `<i class="fas fa-user"></i> <span class="booked-name">${safeBookedBy}</span>`;
                userTitle = `จองแล้ว (${safeBookedBy})`;
                badgeText = '● จองแล้ว';
            } else if (status === 'maintenance') {
                cardClass += 'status-maintenance';
                badgeText = '● ปิดปรับปรุง';
            } else {
                cardClass += 'status-maintenance';
                badgeText = '● ปิดบริการ';
            }

            let userHtml = userContent ? `<div class="court-card-user" title="${userTitle}">${userContent}</div>` : '';

            html += `
                <div class="${cardClass}" ${clickAttr}>
                    <i class="fas fa-map-marker-alt court-card-icon"></i>
                    <h4 class="court-card-title">${court.court_name}</h4>
                    ${userHtml}
                    <span class="court-card-badge">${badgeText}</span>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    updateStickySummaryBar();
}

/**
 * สลับการเลือก/ยกเลิกสล็อตเวลาของคอร์ทสนาม (Consecutive Multi-Hour Engine)
 * @description จัดการกฎเหล็ก 2 ข้อของการจองสนาม:
 *              1. กฎสนามเดียวกัน (Same Court Rule): การจองหลายชั่วโมงต้องเป็นคอร์ทเดียวกันเท่านั้น
 *              2. กฎเวลาต่อเนื่อง (Consecutive Slot Rule): ชั่วโมงที่เลือกเพิ่มต้องติดกับชั่วโมงเดิม ไม่ข้ามชั่วโมง
 * @param {string} slotStart - เวลาเริ่มต้นของสล็อต เช่น '14:00:00'
 * @param {string} slotEnd - เวลาสิ้นสุดของสล็อต เช่น '15:00:00'
 * @param {number} courtId - รหัสสนามคอร์ทแบดมินตัน
 * @param {string} courtName - ชื่อสนาม เช่น 'คอร์ท 1'
 * @param {number} price - ราคาของสล็อตเวลานั้น (บาท)
 */
function toggleCourtSlot(slotStart, slotEnd, courtId, courtName, price) {
    if (wizardState.selectedSlots.has(slotStart)) {
        let cur = wizardState.selectedSlots.get(slotStart);
        if (cur.courtId === courtId) {
            // Unselect this slot
            wizardState.selectedSlots.delete(slotStart);
            if (wizardState.selectedSlots.size === 0) {
                wizardState.selectedCourtId = null;
                wizardState.selectedCourtName = '';
            }
            renderCourtHourBlocks();
            return;
        } else {
            // Change court for this slot
            wizardState.selectedSlots.set(slotStart, {
                courtId: courtId,
                courtName: courtName,
                price: price,
                slotStart: slotStart,
                slotEnd: slotEnd
            });
            wizardState.selectedCourtId = courtId;
            wizardState.selectedCourtName = courtName;
            renderCourtHourBlocks();
            return;
        }
    }

    // Check same court rule for consecutive hours
    if (wizardState.selectedCourtId && wizardState.selectedCourtId !== courtId) {
        if (confirm(`สำหรับการจองต่อเนื่องหลายชั่วโมง ต้องเป็นสนามเดียวกัน\nท่านต้องการเปลี่ยนการจองทั้งหมดเป็น "${courtName}" หรือไม่?`)) {
            wizardState.selectedSlots.forEach((slotData) => {
                slotData.courtId = courtId;
                slotData.courtName = courtName;
            });
            wizardState.selectedCourtId = courtId;
            wizardState.selectedCourtName = courtName;
        } else {
            return;
        }
    }

    // Check consecutive slot rule
    if (wizardState.selectedSlots.size > 0) {
        let selectedHoursList = [];
        wizardState.selectedSlots.forEach((val, key) => {
            selectedHoursList.push(parseInt(key.split(':')[0]));
        });
        selectedHoursList.sort((a, b) => a - b);

        let newSlotHr = parseInt(slotStart.split(':')[0]);
        let minHr = selectedHoursList[0];
        let maxHr = selectedHoursList[selectedHoursList.length - 1];

        // Slot must be adjacent (1 hr before min or 1 hr after max)
        if (newSlotHr !== (minHr - 1) && newSlotHr !== (maxHr + 1)) {
            alert("สำหรับการจองหลายชั่วโมง กรุณาเลือกช่วงเวลาที่ต่อเนื่องกัน (เช่น 14:00 - 15:00 และ 15:00 - 16:00)\nหรือคลิกยกเลิกการเลือกเดิมเพื่อเริ่มเลือกช่วงเวลาใหม่");
            return;
        }
    }

    // Add this slot
    wizardState.selectedSlots.set(slotStart, {
        courtId: courtId,
        courtName: courtName,
        price: price,
        slotStart: slotStart,
        slotEnd: slotEnd
    });
    wizardState.selectedCourtId = courtId;
    wizardState.selectedCourtName = courtName;

    renderCourtHourBlocks();
}

/**
 * อัปเดตแถบสรุปยอดและสล็อตสนามที่เลือกลอยตัวด้านล่าง (Sticky Summary Bar)
 * @description คำนวณจำนวนชั่วโมงรวม, ราคารวมค่าคอร์ท, ช่วงเวลาเริ่มต้น-สิ้นสุด
 *              และอัปเดตข้อความบน Sticky Bar พร้อมเปิด/ปิดปุ่ม "ถัดไป" (Step 3 Next)
 * @element #stickyHoursDisplay, #stickyCourtDisplay, #stickyTotalDisplay, #btnStickyNext
 */
function updateStickySummaryBar() {
    let hours = wizardState.selectedSlots.size;
    wizardState.selectedHours = hours;

    let courtTotal = 0;
    let slotStarts = [];
    let slotEnds = [];
    let courtNames = new Set();

    wizardState.selectedSlots.forEach(s => {
        courtTotal += s.price;
        slotStarts.push(s.slotStart);
        slotEnds.push(s.slotEnd);
        courtNames.add(s.courtName);
    });

    wizardState.courtPriceTotal = courtTotal;

    // คำนวณช่วงเวลาเริ่มต้นและสิ้นสุด
    if (slotStarts.length > 0) {
        slotStarts.sort();
        slotEnds.sort();
        let earliestStart = slotStarts[0];
        let latestEnd = slotEnds[slotEnds.length - 1];

        let inputStart = document.getElementById('input_start_time');
        let inputEnd = document.getElementById('input_end_time');
        let inputCourt = document.getElementById('input_court_id');

        if (inputStart) inputStart.value = earliestStart;
        if (inputEnd) inputEnd.value = latestEnd;
        if (inputCourt) inputCourt.value = wizardState.selectedCourtId;
    }

    // อัปเดต Sticky Bar UI
    let hoursSpan = document.getElementById('stickyHoursDisplay');
    let courtSpan = document.getElementById('stickyCourtDisplay');
    let totalSpan = document.getElementById('stickyTotalDisplay');
    let btnNext = document.getElementById('btnStickyNext');

    if (hoursSpan) hoursSpan.innerText = `[ ${hours} Hour${hours > 1 ? 's' : ''} ]`;
    if (courtSpan) {
        let courtStr = Array.from(courtNames).join(', ');
        courtSpan.innerText = courtStr ? `[ ${courtStr} ]` : '[ ยังไม่ได้เลือกสนาม ]';
    }
    if (totalSpan) totalSpan.innerText = `[ Total ${courtTotal.toLocaleString()} ฿ ]`;

    if (btnNext) {
        btnNext.disabled = (hours === 0);
    }

    calculateGrandTotal();
}

/**
 * ซิงค์ชื่อย่อผู้เล่นลงใน Input ซ่อนเพื่อบันทึกลงฐานข้อมูล (PDPA Nickname)
 * @description ใช้ชื่อย่อสำหรับแสดงผลบนตารางจองสาธารณะ เพื่อรักษาความเป็นส่วนตัวตามกฎหมาย PDPA
 * @param {string} val - ชื่อย่อหรือฉายาที่กรอกในฟอร์ม
 * @element #input_booking_nickname, #step3_nickname
 */
function syncBookingNickname(val) {
    wizardState.bookingNickname = val.trim();
    let hiddenInput = document.getElementById('input_booking_nickname');
    if (hiddenInput) hiddenInput.value = wizardState.bookingNickname;
}

/* ==========================================================================
   หมวดที่ 5: ระบบอุปกรณ์เช่าและบริการเสริม (Add-ons & Rental Selection - Wizard Step 4)
   ========================================================================== */

/**
 * อัปเดตแถบสรุปข้อมูลการจองย่อในขั้นตอนที่ 4
 * @description ดึงข้อมูลวันที่, ช่วงเวลา, และชื่อสนามที่เลือกในขั้นตอนก่อนหน้ามาแสดงผลบนหัวข้อของ Step 4
 * @element #stripDate, #stripTime, #stripCourt
 */
function updateStep4Summary() {
    let stripDate = document.getElementById('stripDate');
    let stripTime = document.getElementById('stripTime');
    let stripCourt = document.getElementById('stripCourt');

    let inputStart = document.getElementById('input_start_time');
    let inputEnd = document.getElementById('input_end_time');

    if (stripDate) stripDate.innerText = `วันที่: ${formatDateThai(wizardState.selectedDate)}`;
    if (stripTime) stripTime.innerText = `เวลา: ${inputStart ? inputStart.value : ''} - ${inputEnd ? inputEnd.value : ''} (${wizardState.selectedHours} ชม.)`;
    if (stripCourt) stripCourt.innerText = `สนาม: ${wizardState.selectedCourtName || '-'}`;

    updateStep4NextButton();
}

/**
 * อัปเดตข้อความบนปุ่ม "ถัดไป" ในขั้นตอนที่ 4 ตามจำนวนสินค้าและบริการเสริมที่เลือก
 * @description นับจำนวนสินค้าที่มียอดสั่งซื้อ > 0 เพื่อแสดง badge จำนวนรายการบนปุ่ม เช่น "ถัดไป: ตรวจสอบรายการ (เลือกแล้ว 2 รายการ)"
 * @element #btnStep4Next, .qty-input
 */
function updateStep4NextButton() {
    let btnNext = document.getElementById('btnStep4Next');
    if (!btnNext) return;

    let qtyInputs = document.querySelectorAll('.qty-input');
    let distinctItems = 0;

    qtyInputs.forEach(input => {
        let q = parseInt(input.value) || 0;
        if (q > 0) {
            distinctItems++;
        }
    });

    if (distinctItems > 0) {
        btnNext.innerHTML = `ถัดไป: ตรวจสอบรายการ (เลือกแล้ว ${distinctItems} รายการ) <i class="fas fa-arrow-right"></i>`;
    } else {
        btnNext.innerHTML = `ถัดไป: ตรวจสอบรายการ <i class="fas fa-arrow-right"></i>`;
    }
}

/**
 * ปรับเพิ่มหรือลดจำนวนสินค้า/อุปกรณ์เช่าผ่านปุ่มบวก-ลบ (+ / -)
 * @description ปรับจำนวนสินค้าในช่อง Input ตาม delta (+1 หรือ -1) 
 *              พร้อมป้องกันไม่ให้ค่าน้อยกว่า 0 หรือเกินกว่าค่า max ใน attribute ของ DOM
 * @param {string|number} prodId - รหัสสินค้าหรือบริการเสริม
 * @param {number} delta - ค่าการเปลี่ยนแปลง (+1 สำหรับเพิ่ม, -1 สำหรับลด)
 * @element #prod_qty_[prodId]
 */
/**
 * อัปเดตสถานะ Highlight ของการ์ดอุปกรณ์/สินค้าเมื่อมีการเลือกจำนวน
 * @param {string|number} prodId - รหัสสินค้า
 * @param {number} val - จำนวนที่เลือก
 */
function updateCardActiveState(prodId, val) {
    let card = document.getElementById('card_prod_' + prodId);
    if (card) {
        if (val > 0) {
            card.classList.add('has-selected');
        } else {
            card.classList.remove('has-selected');
        }
    }
}

function adjustProductQty(prodId, delta) {
    let input = document.getElementById('prod_qty_' + prodId);
    if (!input) return;

    let curVal = parseInt(input.value) || 0;
    let max = parseInt(input.getAttribute('max')) || 999;
    let newVal = curVal + delta;

    if (newVal < 0) newVal = 0;
    if (newVal > max) newVal = max;

    input.value = newVal;
    updateCardActiveState(prodId, newVal);
    calculateGrandTotal();
}

/**
 * จัดการเมื่อผู้ใช้พิมพ์แก้ไขจำนวนสินค้าลงในช่องกรอกตัวเลขโดยตรง
 * @description ตรวจสอบความถูกต้องของตัวเลข ไม่ให้ติดลบหรือเกินค่า max แล้วสั่งคำนวณราคารวมใหม่ทันที
 * @param {string|number} prodId - รหัสสินค้าหรือบริการเสริม
 * @element #prod_qty_[prodId]
 */
function onProductQtyChange(prodId) {
    let input = document.getElementById('prod_qty_' + prodId);
    if (!input) return;

    let val = parseInt(input.value) || 0;
    let max = parseInt(input.getAttribute('max')) || 999;

    if (val < 0) val = 0;
    if (val > max) val = max;

    input.value = val;
    updateCardActiveState(prodId, val);
    calculateGrandTotal();
}

let currentDetailProdId = null;

/**
 * เปิดหน้าต่างดูรายละเอียดอุปกรณ์และสเปก (Product Detail Modal)
 * @param {Object} data ข้อมูลสินค้าและคุณลักษณะอุปกรณ์
 */
function openProductDetailModal(data) {
    if (!data) return;
    const modal = document.getElementById('productDetailModal');
    if (!modal) return;

    currentDetailProdId = data.id;

    // Image & Placeholder
    const imgEl = document.getElementById('detail_prod_image');
    const placeholderEl = document.getElementById('detail_prod_placeholder');
    if (data.image && String(data.image).trim() !== '') {
        if (imgEl) {
            imgEl.src = data.image;
            imgEl.alt = data.name || '';
            imgEl.style.display = 'block';
        }
        if (placeholderEl) placeholderEl.style.display = 'none';
    } else {
        if (imgEl) imgEl.style.display = 'none';
        if (placeholderEl) placeholderEl.style.display = 'flex';
    }

    // Name & Badges
    const nameEl = document.getElementById('detail_prod_name');
    const brandEl = document.getElementById('detail_prod_brand');
    const modelEl = document.getElementById('detail_prod_model');
    const sizeEl = document.getElementById('detail_prod_size');
    const sizeRow = document.getElementById('detail_prod_size_row');
    const priceEl = document.getElementById('detail_prod_price');
    const stockEl = document.getElementById('detail_prod_stock');
    const typeBadge = document.getElementById('detail_prod_type_badge');
    const levelBadge = document.getElementById('detail_prod_level_badge');

    if (nameEl) nameEl.textContent = data.name || '-';
    if (brandEl) brandEl.textContent = data.brand || '-';
    if (modelEl) modelEl.textContent = data.model || '-';
    if (priceEl) priceEl.textContent = Number(data.price || 0).toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' ฿ / หน่วย';
    if (stockEl) stockEl.textContent = 'คงเหลือ ' + (data.stock || 0) + ' ชิ้น';

    if (typeBadge) typeBadge.textContent = data.type || 'อุปกรณ์เช่า';

    if (data.size && String(data.size).trim() !== '') {
        if (sizeEl) sizeEl.textContent = data.size;
        if (sizeRow) sizeRow.style.display = 'flex';
    } else {
        if (sizeRow) sizeRow.style.display = 'none';
    }

    // Callout Boxes
    const calloutBeginner = document.getElementById('detail_callout_beginner');
    const calloutPro = document.getElementById('detail_callout_pro');
    const calloutShoes = document.getElementById('detail_callout_shoes');
    const calloutGeneral = document.getElementById('detail_callout_general');

    if (calloutBeginner) calloutBeginner.style.display = 'none';
    if (calloutPro) calloutPro.style.display = 'none';
    if (calloutShoes) calloutShoes.style.display = 'none';
    if (calloutGeneral) calloutGeneral.style.display = 'none';

    if (levelBadge) {
        if (data.level === 'มือใหม่') {
            levelBadge.className = 'badge-racket-level level-beginner';
            levelBadge.innerHTML = '<i class="fas fa-seedling"></i> ระดับมือใหม่ (Beginner)';
            levelBadge.style.display = 'inline-flex';
            if (calloutBeginner) calloutBeginner.style.display = 'block';
        } else if (data.level === 'มือโปร') {
            levelBadge.className = 'badge-racket-level level-pro';
            levelBadge.innerHTML = '<i class="fas fa-crown"></i> ระดับมือโปร (Pro & Advanced)';
            levelBadge.style.display = 'inline-flex';
            if (calloutPro) calloutPro.style.display = 'block';
        } else {
            levelBadge.style.display = 'none';
            if (data.name && String(data.name).includes('รองเท้า')) {
                if (calloutShoes) calloutShoes.style.display = 'block';
            } else {
                if (calloutGeneral) calloutGeneral.style.display = 'block';
            }
        }
    }

    // Sync stepper from card
    const cardInput = document.getElementById('prod_qty_' + data.id);
    const modalInput = document.getElementById('detail_modal_qty_input');
    const currentQty = cardInput ? (parseInt(cardInput.value, 10) || 0) : 0;
    if (modalInput) {
        modalInput.value = currentQty;
        modalInput.setAttribute('max', data.stock || 999);
    }

    modal.style.display = 'flex';
}

/**
 * ปิดหน้าต่างดูรายละเอียดอุปกรณ์
 */
function closeProductDetailModal() {
    const modal = document.getElementById('productDetailModal');
    if (modal) modal.style.display = 'none';
    currentDetailProdId = null;
}

/**
 * ปรับจำนวนสินค้าจากภายใน Modal และซิงค์กับ Input ของ Step 4 ทันที
 * @param {number} delta - เพิ่ม (+1) หรือลด (-1)
 */
function adjustDetailModalQty(delta) {
    if (!currentDetailProdId) return;
    const modalInput = document.getElementById('detail_modal_qty_input');
    if (!modalInput) return;

    let curVal = parseInt(modalInput.value, 10) || 0;
    let max = parseInt(modalInput.getAttribute('max'), 10) || 999;
    let newVal = curVal + delta;
    if (newVal < 0) newVal = 0;
    if (newVal > max) newVal = max;

    modalInput.value = newVal;

    // ซิงค์กับ Input ในการ์ดและคำนวณราคายอดรวมใหม่ทันที
    const cardInput = document.getElementById('prod_qty_' + currentDetailProdId);
    if (cardInput) {
        cardInput.value = newVal;
        updateCardActiveState(currentDetailProdId, newVal);
        if (typeof calculateGrandTotal === 'function') calculateGrandTotal();
    }
}

// ผูก Event ปิด Modal ด้วย Escape และการคลิก Backdrop
document.addEventListener('DOMContentLoaded', function() {
    const prodModal = document.getElementById('productDetailModal');
    if (prodModal) {
        prodModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeProductDetailModal();
            }
        });
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProductDetailModal();
    }
});

window.openProductDetailModal = openProductDetailModal;
window.closeProductDetailModal = closeProductDetailModal;
window.adjustDetailModalQty = adjustDetailModalQty;

/* ==========================================================================
   หมวดที่ 6 (ต่อ): การคำนวณยอดสุทธิและใบแจกแจงค่าใช้จ่าย (Grand Total & Invoice Review)
   ========================================================================== */

/**
 * คำนวณยอดเงินรวมสุทธิทั้งหมด (Grand Total Calculation)
 * @description รวมยอดค่าบริการสนาม + ค่าเช่าอุปกรณ์ + ค่าสินค้าเครื่องดื่ม
 *              และซิงค์ค่ายอดรวมลงใน Hidden Inputs เพื่อเตรียมส่งข้อมูลบันทึกลงฐานข้อมูล
 * @element #input_court_price, #input_rental_price, #input_product_price, #input_total_price
 */
function calculateGrandTotal() {
    let rentTotal = 0;
    let prodTotal = 0;

    let qtyInputs = document.querySelectorAll('.qty-input');
    qtyInputs.forEach(input => {
        let qty = parseInt(input.value) || 0;
        let price = parseFloat(input.getAttribute('data-price')) || 0;
        let type = input.getAttribute('data-type');

        if (qty > 0) {
            if (type === 'อุปกรณ์เช่า') {
                rentTotal += (qty * price);
            } else {
                prodTotal += (qty * price);
            }
        }
    });

    wizardState.rentalPriceTotal = rentTotal;
    wizardState.productPriceTotal = prodTotal;
    wizardState.grandTotal = wizardState.courtPriceTotal + rentTotal + prodTotal;

    // Sync to hidden inputs
    let inputCourtPrice = document.getElementById('input_court_price');
    let inputRentPrice = document.getElementById('input_rental_price');
    let inputProdPrice = document.getElementById('input_product_price');
    let inputTotalPrice = document.getElementById('input_total_price');

    if (inputCourtPrice) inputCourtPrice.value = wizardState.courtPriceTotal;
    if (inputRentPrice) inputRentPrice.value = rentTotal;
    if (inputProdPrice) inputProdPrice.value = prodTotal;
    if (inputTotalPrice) inputTotalPrice.value = wizardState.grandTotal;

    updateStep4NextButton();
}

/**
 * เรนเดอร์ใบแจ้งหนี้แจกแจงรายการค่าใช้จ่ายแบบละเอียด (Itemized Invoice Review - Step 5)
 * @description สร้างตาราง HTML รายการค่าใช้จ่ายแยกระหว่าง:
 *              1. ค่าบริการสนาม: แจกแจงตามชั่วโมงจริง แยกวันหยุด / กลางวัน / ค่ำ
 *              2. ค่าสินค้าและอุปกรณ์เช่า: แสดงจำนวน หน่วย และราคารวมย่อย
 *              พร้อมแสดงยอดเงินรวมสุทธิทั้งสิ้น
 * @element #invNickname, #invDate, #invTime, #invCourt, #invGrandTotal, #invoiceTableBody
 */
function updateInvoiceReview() {
    calculateGrandTotal();

    let invNick = document.getElementById('invNickname');
    let invDate = document.getElementById('invDate');
    let invTime = document.getElementById('invTime');
    let invCourt = document.getElementById('invCourt');
    let invGrand = document.getElementById('invGrandTotal');
    let tableBody = document.getElementById('invoiceTableBody');

    let inputStart = document.getElementById('input_start_time');
    let inputEnd = document.getElementById('input_end_time');

    let nick = wizardState.bookingNickname || 'ผู้ใช้งาน';
    if (invNick) invNick.innerText = nick;
    if (invDate) invDate.innerText = `${formatDateThai(wizardState.selectedDate)}`;
    if (invTime) invTime.innerText = `${inputStart ? inputStart.value : ''} - ${inputEnd ? inputEnd.value : ''} (${wizardState.selectedHours} ชั่วโมง)`;
    if (invCourt) invCourt.innerText = wizardState.selectedCourtName || '-';
    if (invGrand) invGrand.innerText = wizardState.grandTotal.toLocaleString() + ' ฿';

    if (tableBody) {
        let html = '';

        // 1. ค่าบริการสนาม (รองรับแจกแจงแยกช่วงเวลา Cross-Window / กลางวัน / เย็น / วันหยุด)
        let daySlots = [];
        let eveSlots = [];
        let weekendSlots = [];
        let dow = wizardState.matrixData ? wizardState.matrixData.day_of_week : new Date(wizardState.selectedDate).getDay();

        wizardState.selectedSlots.forEach(s => {
            let hr = parseInt(s.slotStart.split(':')[0]);
            if (dow === 0 || dow === 6) {
                weekendSlots.push(s);
            } else if (hr < 17) {
                daySlots.push(s);
            } else {
                eveSlots.push(s);
            }
        });

        if (weekendSlots.length > 0) {
            let wkRate = weekendSlots[0].price || 200;
            let wkSubtotal = weekendSlots.reduce((sum, s) => sum + s.price, 0);
            html += `
                <tr>
                    <td><strong>ค่าบริการสนาม (${wizardState.selectedCourtName})</strong><br><small class="text-muted">เสาร์ - อาทิตย์ (ทุกช่วงเวลา 200 ฿/ชม.)</small></td>
                    <td>${weekendSlots.length} ชม.</td>
                    <td>${wkRate.toLocaleString()} ฿</td>
                    <td>${wkSubtotal.toLocaleString()} ฿</td>
                </tr>
            `;
        } else {
            if (daySlots.length > 0) {
                let dayRate = daySlots[0].price || 150;
                let daySubtotal = daySlots.reduce((sum, s) => sum + s.price, 0);
                html += `
                    <tr>
                        <td><strong>ค่าบริการสนาม (${wizardState.selectedCourtName})</strong><br><small class="text-muted">ช่วงกลางวัน (08:00 - 17:00 น.)</small></td>
                        <td>${daySlots.length} ชม.</td>
                        <td>${dayRate.toLocaleString()} ฿</td>
                        <td>${daySubtotal.toLocaleString()} ฿</td>
                    </tr>
                `;
            }
            if (eveSlots.length > 0) {
                let eveRate = eveSlots[0].price || 180;
                let eveSubtotal = eveSlots.reduce((sum, s) => sum + s.price, 0);
                html += `
                    <tr>
                        <td><strong>ค่าบริการสนาม (${wizardState.selectedCourtName})</strong><br><small class="text-muted">ช่วงเย็น (17:00 - 23:00 น.)</small></td>
                        <td>${eveSlots.length} ชม.</td>
                        <td>${eveRate.toLocaleString()} ฿</td>
                        <td>${eveSubtotal.toLocaleString()} ฿</td>
                    </tr>
                `;
            }
        }

        // 2. สินค้าและอุปกรณ์เช่า
        let qtyInputs = document.querySelectorAll('.qty-input');
        qtyInputs.forEach(input => {
            let qty = parseInt(input.value) || 0;
            if (qty > 0) {
                let name = input.getAttribute('data-name');
                let price = parseFloat(input.getAttribute('data-price')) || 0;
                let type = input.getAttribute('data-type');
                let itemTotal = qty * price;

                html += `
                    <tr>
                        <td>${name} <small class="text-muted">(${type})</small></td>
                        <td>${qty} หน่วย</td>
                        <td>${price.toLocaleString()} ฿</td>
                        <td>${itemTotal.toLocaleString()} ฿</td>
                    </tr>
                `;
            }
        });

        tableBody.innerHTML = html;
    }
}

/* ==========================================================================
   หมวดที่ 8: การส่งฟอร์มการจองและการจัดการ Guest (Booking Submission & Interceptor)
   ========================================================================== */

/** ตัวแปรแฟล็กป้องกันการกดส่งฟอร์มจองซ้ำ */
let isBookingSubmitting = false;

/**
 * ตรวจสอบความถูกต้องและส่งฟอร์มยืนยันการจองสนาม (Final Booking Submission)
 * @description ตรวจสอบว่ามีการเลือกสนามหรือไม่ และตรวจสอบสถานะการเข้าสู่ระบบ
 *              หากเป็นผู้ใช้ทั่วไป (Guest) จะส่งต่อไปยัง submitGuestBooking()
 *              หากล็อกอินแล้ว จะล็อกปุ่มส่งฟอร์ม แสดงไอคอน Spinner หมุน และอนุญาตให้ส่งคำขอ
 * @param {Event} [event] - อีเวนต์การส่งฟอร์ม
 * @returns {boolean} คืนค่า true หากพร้อมส่ง หรือ false หากติดเงื่อนไขการตรวจสอบ
 * @element #btnFinalSubmitBooking, #bookingForm
 */
function handleBookingFormSubmit(event) {
    if (isBookingSubmitting) {
        if (event) event.preventDefault();
        return false;
    }

    if (wizardState.selectedSlots.size === 0) {
        alert("กรุณาเลือกสนามก่อนยืนยันการจอง");
        if (event) event.preventDefault();
        return false;
    }

    // ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือยัง
    const isUserLoggedIn = document.body.dataset.loggedIn === 'true';
    if (!isUserLoggedIn) {
        if (event) event.preventDefault();
        submitGuestBooking('login');
        return false;
    }

    isBookingSubmitting = true;
    let btnSubmit = document.getElementById('btnFinalSubmitBooking');
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึกการจองและล็อกสนาม...';
        btnSubmit.style.opacity = "0.75";
        btnSubmit.style.cursor = "not-allowed";
    }

    return true;
}

/**
 * จัดการกรณีผู้ใช้ทั่วไป (Guest) ยังไม่ได้เข้าสู่ระบบแต่อยากจองสนาม
 * @description บันทึกข้อมูลการจองปัจจุบันลงในฟอร์ม แล้วนำทางผู้ใช้ไปยังหน้าล็อกอินหรือสมัครสมาชิก
 *              โดยเซิร์ฟเวอร์จะจดจำ Pending Booking ไว้ และส่งกลับมาหลังยืนยันตัวตนสำเร็จ
 * @param {string} [authType='login'] - รูปแบบการนำทาง ('login' หรือ 'register')
 * @element #input_auth_action, #bookingForm
 */
function submitGuestBooking(authType = 'login') {
    let bookingForm = document.getElementById('bookingForm');
    if (!bookingForm) return;

    if (wizardState.selectedSlots.size === 0) {
        alert("กรุณาเลือกสนามก่อนดำเนินการ");
        return;
    }

    // Ensure hidden summary and total price are up to date
    updateStep4Summary();
    updateInvoiceReview();

    let authInput = document.getElementById('input_auth_action');
    if (!authInput) {
        authInput = document.createElement('input');
        authInput.type = 'hidden';
        authInput.name = 'auth_action';
        authInput.id = 'input_auth_action';
        bookingForm.appendChild(authInput);
    }
    authInput.value = authType;

    let btnFinal = document.getElementById('btnFinalSubmitBooking');
    if (btnFinal) {
        btnFinal.disabled = true;
        btnFinal.innerHTML = '<i class="fas fa-spinner fa-spin mr-6"></i> กำลังบันทึกข้อมูลและนำทางไปยังหน้า' + (authType === 'register' ? 'สมัครสมาชิก...' : 'เข้าสู่ระบบ...');
        btnFinal.style.opacity = "0.75";
        btnFinal.style.cursor = "not-allowed";
    }

    bookingForm.submit();
}


/* ==========================================================================
   หมวดที่ 9: ระบบชำระเงิน แนบสลิป และเวลานับถอยหลัง (Payment, Slip & Countdown - payment.php)
   ========================================================================== */

/**
 * ตัวจับเวลานับถอยหลัง 15 นาทีล็อกสนามบนหน้าชำระเงิน (15-Minute Slot Lock Countdown)
 * @description คำนวณช่วงเวลาที่เหลือก่อนสล็อตสนามจะหมดอายุ (distance = expireTime - now)
 *              หากเวลาหมดลงจะแจ้งเตือนผู้ใช้งานและยกเลิกการจองพร้อม Redirect กลับไปยังหน้า booking.php ทันที
 * @param {string} lockExpireStr - สตริงเวลาหมดอายุที่ได้รับจากฐานข้อมูล เช่น '2026-10-17 14:15:00'
 * @element #countdown
 */
function initPaymentCountdown(lockExpireStr) {
    let countdownElement = document.getElementById("countdown");
    if (!countdownElement) return;

    // หากไม่ได้ส่งค่า lockExpireStr เข้ามา ให้อ่านจาก data-expire บน element #countdown โดยตรง
    if (!lockExpireStr && countdownElement.dataset && countdownElement.dataset.expire) {
        lockExpireStr = countdownElement.dataset.expire;
    }
    if (!lockExpireStr) return;

    let expireTime = new Date(lockExpireStr.replace(/-/g, "/")).getTime();

    let countdownTimer = setInterval(function() {
        let now = new Date().getTime();
        let distance = expireTime - now;

        if (distance < 0) {
            clearInterval(countdownTimer);
            countdownElement.innerText = "หมดเวลาการจอง!";
            alert("หมดเวลาชำระเงินภายใน 15 นาที ระบบจะยกเลิกการจองนี้อัตโนมัติ");
            window.location.href = "booking.php";
            return;
        }

        let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((distance % (1000 * 60)) / 1000);

        countdownElement.innerText = 
            (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
    }, 1000);
}

/**
 * ระบบจัดการอัปโหลดสลิปหลักฐานการโอนเงิน (Slip Upload & Real-time Preview)
 * @description รองรับการทำงานแบบครบวงจรตามหลัก HCI:
 *              1. อัปโหลดผ่านการคลิกเลือกไฟล์ หรือลากวาง (Drag & Drop)
 *              2. ตรวจสอบชนิดไฟล์ (JPG, PNG, WEBP) ฝั่ง Client ทันที
 *              3. ตรวจสอบขนาดไฟล์ไม่เกิน 5MB ป้องกันการโหลดไฟล์ขนาดใหญ่เกินจำเป็น
 *              4. แสดงภาพตัวอย่างสลิปโอนเงินทันที (Live Preview) ผ่าน FileReader API
 *              5. มีระบบป้องกันการกดส่งฟอร์มซ้ำ (Double Submit Prevention) พร้อมปุ่ม Loading Spinner
 * @element #paymentSlipInput, #slipUploadBox, #slipPreviewImg, #paymentForm, #btnSubmitPayment
 */
function initSlipUpload() {
    let fileInput = document.getElementById("paymentSlipInput");
    let uploadBox = document.getElementById("slipUploadBox");
    let uploadPrompt = document.getElementById("uploadPrompt");
    let previewContainer = document.getElementById("previewContainer");
    let previewImg = document.getElementById("slipPreviewImg");
    let fileInfoText = document.getElementById("fileInfoText");
    let btnChangeSlip = document.getElementById("btnChangeSlip");
    let errorMsg = document.getElementById("clientErrorMsg");
    let paymentForm = document.getElementById("paymentForm");
    let btnSubmit = document.getElementById("btnSubmitPayment");

    if (!fileInput || !uploadBox) return;

    // คลิกที่กล่องเพื่อเปิด File Dialog
    uploadBox.addEventListener("click", function(e) {
        if (e.target.closest("#btnChangeSlip") || e.target === fileInput) return;
        fileInput.click();
    });

    if (btnChangeSlip) {
        btnChangeSlip.addEventListener("click", function(e) {
            e.stopPropagation();
            fileInput.click();
        });
    }

    // Drag and Drop
    ["dragenter", "dragover"].forEach(eventName => {
        uploadBox.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadBox.classList.add("dragover");
        }, false);
    });

    ["dragleave", "drop"].forEach(eventName => {
        uploadBox.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadBox.classList.remove("dragover");
        }, false);
    });

    uploadBox.addEventListener("drop", function(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        if (files && files.length > 0) {
            fileInput.files = files;
            handleSlipFile(files[0]);
        }
    });

    // เมื่อเลือกไฟล์ผ่าน Dialog
    fileInput.addEventListener("change", function() {
        if (fileInput.files && fileInput.files.length > 0) {
            handleSlipFile(fileInput.files[0]);
        }
    });

    function handleSlipFile(file) {
        if (errorMsg) {
            errorMsg.style.display = "none";
            errorMsg.innerText = "";
        }

        // 1. ตรวจสอบประเภทไฟล์
        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        const ext = file.name.split('.').pop().toLowerCase();
        const allowedExts = ["jpg", "jpeg", "png", "webp"];

        if (!allowedTypes.includes(file.type) && !allowedExts.includes(ext)) {
            showError("รูปแบบไฟล์ไม่ถูกต้อง! รองรับเฉพาะ JPG, PNG, WEBP เท่านั้น");
            resetUploadState();
            return;
        }

        // 2. ตรวจสอบขนาดไฟล์ (ไม่เกิน 5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showError("ขนาดไฟล์รูปภาพเกินกำหนด! (สูงสุด 5MB)");
            resetUploadState();
            return;
        }

        // 3. แสดงรูปพรีวิวด้วย FileReader
        let reader = new FileReader();
        reader.onload = function(e) {
            if (previewImg) previewImg.src = e.target.result;
            
            let sizeFormatted = file.size > 1024 * 1024 
                ? (file.size / (1024 * 1024)).toFixed(2) + " MB" 
                : (file.size / 1024).toFixed(1) + " KB";
            
            if (fileInfoText) {
                fileInfoText.innerText = file.name + " (" + sizeFormatted + ")";
            }

            if (uploadPrompt) uploadPrompt.style.display = "none";
            if (previewContainer) previewContainer.style.display = "flex";
        };
        reader.readAsDataURL(file);
    }

    function showError(msg) {
        if (errorMsg) {
            errorMsg.innerText = msg;
            errorMsg.style.display = "block";
        } else {
            alert(msg);
        }
    }

    function resetUploadState() {
        fileInput.value = "";
        if (previewImg) previewImg.src = "";
        if (uploadPrompt) uploadPrompt.style.display = "flex";
        if (previewContainer) previewContainer.style.display = "none";
    }

    // 4. Loading & Disabled State เมื่อ Submit Form พร้อมป้องกัน Double Submit
    let isPaymentSubmitting = false;
    if (paymentForm) {
        paymentForm.addEventListener("submit", function(e) {
            if (isPaymentSubmitting) {
                e.preventDefault();
                return false;
            }

            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                showError("กรุณาแนบรูปภาพสลิปการโอนเงินก่อนยืนยัน");
                return false;
            }

            isPaymentSubmitting = true;
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังอัปโหลดสลิป กรุณารอสักครู่...';
                btnSubmit.style.opacity = "0.75";
                btnSubmit.style.cursor = "not-allowed";
            }
        });
    }
}

/**
 * ฟังก์ชันคัดลอกเลขที่บัญชีธนาคาร 1-Click ลงสู่ Clipboard
 * @description ใช้ Clipboard API (พร้อม Fallback กรณีเบราว์เซอร์รุ่นเก่า) และแสดงกล่อง Feedback แจ้งเตือนผู้ใช้ 2.5 วินาที
 * @param {string} accNo - เลขบัญชีธนาคารที่ต้องการคัดลอก
 * @element #copyFeedback
 */
function copyBankAccount(accNo) {
    if (!accNo) return;
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(accNo).then(showFeedback).catch(() => fallbackCopy(accNo));
    } else {
        fallbackCopy(accNo);
    }

    function fallbackCopy(text) {
        let tempInput = document.createElement("textarea");
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
        showFeedback();
    }

    function showFeedback() {
        let feedback = document.getElementById("copyFeedback");
        if (feedback) {
            feedback.style.display = "inline";
            setTimeout(() => {
                feedback.style.display = "none";
            }, 2500);
        }
    }
}

/* ==========================================================================
   หมวดที่ 10: ระบบสะสมแต้มและแลกของรางวัล (Rewards & Loyalty System - rewards.php)
   ========================================================================== */

/**
 * สลับแท็บแสดงของรางวัลตามหมวดหมู่ (Rewards Category Tab Switcher)
 * @description ปรับเปลี่ยนคลาส active บนปุ่มแท็บและกล่องเนื้อหา pane ที่ตรงกับหมวดที่เลือก (ทั้งหมด, ค่าสนาม, เครื่องดื่ม, อุปกรณ์)
 * @param {string} targetTab - รหัสแท็บเป้าหมาย เช่น 'all', 'court', 'drink', 'item'
 * @element .rewards-tab-btn, .rewards-tab-pane
 */
function switchRewardsTab(targetTab) {
    let tabButtons = document.querySelectorAll('.rewards-tab-btn');
    let tabPanes = document.querySelectorAll('.rewards-tab-pane');

    tabButtons.forEach(btn => {
        if (btn.getAttribute('data-tab') === targetTab) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    tabPanes.forEach(pane => {
        if (pane.id === targetTab) {
            pane.classList.add('active');
        } else {
            pane.classList.remove('active');
        }
    });
}

/**
 * เปิดหน้าต่าง Modal ยืนยันการแลกของรางวัลด้วยแต้มสะสม
 * @description นำข้อมูลของรางวัลที่เลือกมาแสดงผลบน Modal พร้อมคำนวณและแสดงแจกแจงแต้ม:
 *              แต้มปัจจุบัน, แต้มที่จะถูกหัก, และแต้มคงเหลือสุทธิหลังการแลก
 * @param {number|string} rewardId - รหัสของรางวัล
 * @param {string} rewardName - ชื่อของรางวัล
 * @param {number} pointCost - จำนวนแต้มที่ต้องใช้แลก
 * @param {string} rewardImg - ชื่อไฟล์รูปภาพของรางวัล
 * @param {number} userPoints - จำนวนแต้มสะสมปัจจุบันของผู้ใช้งาน
 * @element #rewardConfirmModal, #modal_reward_id, #modal_reward_title, #modal_reward_cost, #modal_remain_points
 */
function openRedeemModal(rewardId, rewardName, pointCost, rewardImg, userPoints) {
    let modal = document.getElementById('rewardConfirmModal');
    if (!modal) return;

    // Set hidden form values
    let inputRewardId = document.getElementById('modal_reward_id');
    if (inputRewardId) inputRewardId.value = rewardId;

    // Set preview details
    let titleEl = document.getElementById('modal_reward_title');
    let costEl = document.getElementById('modal_reward_cost');
    let imgEl = document.getElementById('modal_reward_img');
    let iconEl = document.getElementById('modal_reward_placeholder');

    if (titleEl) titleEl.innerText = rewardName;
    if (costEl) costEl.innerHTML = '<i class="fas fa-coins text-warning"></i> ' + Number(pointCost).toLocaleString() + ' คะแนน';

    if (rewardImg && rewardImg.trim() !== '') {
        if (imgEl) {
            imgEl.src = 'uploads/rewards/' + rewardImg;
            imgEl.style.display = 'block';
        }
        if (iconEl) iconEl.style.display = 'none';
    } else {
        if (imgEl) imgEl.style.display = 'none';
        if (iconEl) iconEl.style.display = 'flex';
    }

    // Calculate points breakdown
    let currentPtsEl = document.getElementById('modal_current_points');
    let deductPtsEl = document.getElementById('modal_deduct_points');
    let remainPtsEl = document.getElementById('modal_remain_points');

    let current = Number(userPoints);
    let cost = Number(pointCost);
    let remaining = current - cost;

    if (currentPtsEl) currentPtsEl.innerText = current.toLocaleString() + ' คะแนน';
    if (deductPtsEl) deductPtsEl.innerText = '- ' + cost.toLocaleString() + ' คะแนน';
    if (remainPtsEl) remainPtsEl.innerText = remaining.toLocaleString() + ' คะแนน';

    // Show modal
    modal.classList.add('active');
}

/**
 * ปิดหน้าต่าง Modal ยืนยันการแลกของรางวัล
 * @description ลบคลาส 'active' ออกจาก Element #rewardConfirmModal
 * @element #rewardConfirmModal
 */
function closeRedeemModal() {
    let modal = document.getElementById('rewardConfirmModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

/** ตัวแปรแฟล็กป้องกันการกดแลกของรางวัลซ้ำ */
let isRedeemSubmitting = false;

/**
 * ส่งฟอร์มคำขอยืนยันการแลกของรางวัล (Execute Redeem Submit)
 * @description ล็อกปุ่มยืนยัน ป้องกันการ Double Submit และสั่ง Submit ฟอร์มไปยัง backend
 * @element #btnModalConfirmRedeem, #redeemRewardForm
 */
function executeRedeemSubmit() {
    if (isRedeemSubmitting) return false;

    let btnSubmit = document.getElementById('btnModalConfirmRedeem');
    let form = document.getElementById('redeemRewardForm');

    isRedeemSubmitting = true;
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังทำรายการ...';
        btnSubmit.style.opacity = '0.75';
        btnSubmit.style.cursor = 'not-allowed';
    }

    if (form) {
        form.submit();
    }
}

/**
 * ดักจับการคลิกภายนอกพื้นที่ (Backdrop Click) และการกดปุ่ม ESC สำหรับปิด Modal ฝั่ง Member ทั้งหมด
 * @description รองรับการปิด Modal ของรางวัล (#rewardConfirmModal) และ Modal การยกเลิก (#cancelModal) ตามมาตรฐาน HCI
 * @event click, keydown (Escape)
 */
document.addEventListener('DOMContentLoaded', function() {
    // ----------------------------------------------------------------------
    // 1. ดักจับการคลิกภายนอกพื้นที่ (Backdrop Click) สำหรับปิด Modal ฝั่ง Member
    // ----------------------------------------------------------------------
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('member-modal-overlay') || 
            e.target.classList.contains('modal-overlay') || 
            e.target.classList.contains('modal-cancel-overlay') ||
            e.target.id === 'newsModal') {
            
            if (e.target.id === 'rewardConfirmModal' && typeof closeRedeemModal === 'function') {
                closeRedeemModal();
            } else if (e.target.id === 'cancelModal' && typeof closeCancelModal === 'function') {
                closeCancelModal();
            } else if (e.target.id === 'newsModal' && typeof closeNewsDetailModal === 'function') {
                closeNewsDetailModal();
            } else {
                e.target.style.display = 'none';
                e.target.classList.remove('active', 'show');
            }
        }
    });

    // ----------------------------------------------------------------------
    // 2. ปิดเมื่อกดปุ่ม ESC (HCI Keyboard Accessibility)
    // ----------------------------------------------------------------------
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            // Modal ข่าวสาร (promotions.php)
            let newsModal = document.getElementById('newsModal');
            if (newsModal && newsModal.classList.contains('show') && typeof closeNewsDetailModal === 'function') {
                closeNewsDetailModal();
                return;
            }

            // Modal ขอยกเลิกการจอง (booking_history.php)
            let cancelModal = document.getElementById('cancelModal');
            if (cancelModal && (cancelModal.style.display === 'flex' || cancelModal.classList.contains('active')) && typeof closeCancelModal === 'function') {
                closeCancelModal();
                return;
            }

            // Modal อื่นๆ
            let activeOverlay = document.querySelector('.member-modal-overlay.active, .modal-overlay.active, .modal-cancel-overlay[style*="display: flex"], .modal-overlay[style*="display: flex"]');
            if (activeOverlay) {
                if (activeOverlay.id === 'rewardConfirmModal' && typeof closeRedeemModal === 'function') {
                    closeRedeemModal();
                } else if (activeOverlay.id === 'cancelModal' && typeof closeCancelModal === 'function') {
                    closeCancelModal();
                } else {
                    activeOverlay.style.display = 'none';
                    activeOverlay.classList.remove('active');
                }
            }
        }
    });

    // ----------------------------------------------------------------------
    // 3. เริ่มต้นระบบอัตโนมัติตามองค์ประกอบที่พบบนหน้าจอ (Automatic Lifecycle Inits)
    // ----------------------------------------------------------------------
    
    // หน้าชำระเงิน (payment.php): อ่าน data-expire แล้วเริ่มนับถอยหลังและตั้งค่าอัปโหลดสลิป
    let countdownEl = document.getElementById('countdown');
    if (countdownEl) {
        initPaymentCountdown();
        if (typeof initSlipUpload === 'function') {
            initSlipUpload();
        }
    }

    // หน้าจองสนาม (booking.php): เริ่มต้นระบบ 5-Step Wizard
    if (document.querySelector('.wizard-page-wrapper') || document.getElementById('stepPanel1')) {
        if (typeof initBookingWizard === 'function') {
            initBookingWizard();
        }
    }

    // หน้าสะสมแต้มและของรางวัล (rewards.php): ปรับหลอดความคืบหน้าระดับสมาชิกตาม data-progress
    let pFill = document.getElementById('loyaltyProgressFill');
    if (pFill && pFill.dataset && pFill.dataset.progress) {
        pFill.style.width = pFill.dataset.progress + '%';
    }

    // หน้าข่าวสารและโปรโมชัน (promotions.php): เปิดระบบแท็บตัวกรองและตรวจจับ data-initial-id
    if (document.querySelector('.news-page-wrapper') && typeof initPromotionsPage === 'function') {
        initPromotionsPage();
    }
});

/* ==========================================================================
   หมวดที่ 11: ระบบฟอร์มยืนยันตัวตนและความปลอดภัย (Auth & Security Forms - register.php & login.php)
   ========================================================================== */

/**
 * โมดูลควบคุมฟอร์มสมัครสมาชิก (Registration Form Controller - register.php)
 * @description จัดการการตรวจสอบข้อมูลฝั่ง Client-side แบบ Real-time ตามมาตรฐาน HCI:
 *              1. แสดง/ซ่อนรหัสผ่าน (Password Visibility Toggle) ทั้งสองช่อง
 *              2. ตัวตรวจจับความตรงกันของรหัสผ่านสด (Live Match Indicator)
 *              3. กรองเบอร์โทรศัพท์เฉพาะตัวเลข 10 หลักอัตโนมัติ (Numeric Sanitizer)
 *              4. ตรวจสอบเงื่อนไขความถูกต้อง (ชื่อ, เบอร์โทร 10 หลักขึ้นต้นด้วย 0, อายุ 5-100 ปี, รหัสผ่าน >= 6 ตัว)
 *              5. แสดง Alert ข้อความเตือนระดับ Client-side โดยไม่ต้องรีเฟรชหน้า
 *              6. ล็อกปุ่มป้องกันการคลิกส่งซ้ำ (Double Submit Lockout)
 */
(function() {
    function initRegisterForm() {
        const form = document.getElementById('registerForm');
        if (!form) return;

        const firstNameInput = document.getElementById('first_name');
        const lastNameInput = document.getElementById('last_name');
        const nameInput = document.getElementById('member_name');
        const phoneInput = document.getElementById('member_phone');
        const ageInput = document.getElementById('member_age');
        const passInput = document.getElementById('password');
        const confirmPassInput = document.getElementById('confirm_password');
        const btnTogglePass = document.getElementById('btnTogglePassword');
        const togglePassIcon = document.getElementById('togglePasswordIcon');
        const btnToggleConfirm = document.getElementById('btnToggleConfirmPassword');
        const toggleConfirmIcon = document.getElementById('toggleConfirmPasswordIcon');
        const matchHint = document.getElementById('passwordMatchHint');
        const alertBox = document.getElementById('registerClientAlertBox');
        const btnSubmit = document.getElementById('btnRegisterSubmit');

        let isSubmitting = false;

        /**
         * แสดงกล่องข้อความแจ้งเตือนข้อผิดพลาดบนฟอร์มสมัครสมาชิก
         * @param {string} msg - ข้อความแจ้งเตือน
         */
        function showAlert(msg) {
            if (!alertBox) {
                alert(msg);
                return;
            }
            alertBox.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>${escapeHtml(msg)}</span>
                </div>
            `;
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        /**
         * ล้างกล่องแจ้งเตือนข้อผิดพลาด
         */
        function clearAlert() {
            if (alertBox) alertBox.innerHTML = '';
        }

        /**
         * ป้องกันช่องโหว่ XSS ด้วยการแปลงอักขระพิเศษเป็น HTML Entities
         * @param {string} str - ข้อความดิบ
         * @returns {string} ข้อความที่ผ่านการ Escape แล้ว
         */
        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        /**
         * ติดตั้งตัวสลับการแสดง/ซ่อนรหัสผ่าน (Password Visibility Toggle)
         * @param {HTMLElement} btn - ปุ่มไอคอนลูกตา
         * @param {HTMLInputElement} input - ช่อง Input รหัสผ่าน
         * @param {HTMLElement} icon - ไอคอน FontAwesome
         */
        function setupToggle(btn, input, icon) {
            if (!btn || !input || !icon) return;
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }

        setupToggle(btnTogglePass, passInput, togglePassIcon);
        setupToggle(btnToggleConfirm, confirmPassInput, toggleConfirmIcon);

        /**
         * ตรวจสอบว่ารหัสผ่านและการยืนยันรหัสผ่านตรงกันหรือไม่แบบสด (Live Matching)
         */
        function checkPasswordMatch() {
            if (!matchHint || !passInput || !confirmPassInput) return;
            const pVal = passInput.value;
            const cVal = confirmPassInput.value;

            if (!cVal) {
                matchHint.style.display = 'none';
                matchHint.className = 'password-match-hint';
                matchHint.innerHTML = '';
                return;
            }

            matchHint.style.display = 'block';
            if (pVal === cVal) {
                matchHint.className = 'password-match-hint valid';
                matchHint.innerHTML = '<i class="fas fa-check-circle"></i> รหัสผ่านตรงกัน';
            } else {
                matchHint.className = 'password-match-hint invalid';
                matchHint.innerHTML = '<i class="fas fa-times-circle"></i> รหัสผ่านไม่ตรงกัน';
            }
        }

        if (confirmPassInput) {
            confirmPassInput.addEventListener('input', checkPasswordMatch);
        }
        if (passInput) {
            passInput.addEventListener('input', function() {
                if (confirmPassInput && confirmPassInput.value) {
                    checkPasswordMatch();
                }
            });
        }

        // Phone Input Sanitation (numeric only)
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            });
        }

        // Form Submit Handler
        form.addEventListener('submit', function(e) {
            clearAlert();

            const firstName = firstNameInput ? firstNameInput.value.trim() : '';
            const lastName = lastNameInput ? lastNameInput.value.trim() : '';
            const legacyName = nameInput ? nameInput.value.trim() : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';
            const password = passInput ? passInput.value : '';
            const confirmPass = confirmPassInput ? confirmPassInput.value : '';
            const ageVal = ageInput && ageInput.value ? parseInt(ageInput.value) : null;

            // ตรวจสอบชื่อและนามสกุล (รองรับทั้งแบบแยกช่องและแบบเดิม)
            if (firstNameInput || lastNameInput) {
                if (!firstName) {
                    e.preventDefault();
                    showAlert('กรุณากรอกชื่อจริง');
                    if (firstNameInput) firstNameInput.focus();
                    return;
                }
                if (!lastName) {
                    e.preventDefault();
                    showAlert('กรุณากรอกนามสกุล');
                    if (lastNameInput) lastNameInput.focus();
                    return;
                }
            } else if (nameInput && !legacyName) {
                e.preventDefault();
                showAlert('กรุณากรอกชื่อ - นามสกุล');
                nameInput.focus();
                return;
            }

            if (!phone) {
                e.preventDefault();
                showAlert('กรุณากรอกเบอร์โทรศัพท์');
                if (phoneInput) phoneInput.focus();
                return;
            }

            if (!/^0[0-9]{9}$/.test(phone)) {
                e.preventDefault();
                showAlert('เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลักและขึ้นต้นด้วย 0 (เช่น 08X-XXX-XXXX)');
                if (phoneInput) phoneInput.focus();
                return;
            }

            if (ageVal !== null && (isNaN(ageVal) || ageVal < 5 || ageVal > 100)) {
                e.preventDefault();
                showAlert('กรุณาระบุอายุให้ถูกต้อง (ระหว่าง 5 - 100 ปี)');
                if (ageInput) ageInput.focus();
                return;
            }

            if (!password) {
                e.preventDefault();
                showAlert('กรุณาตั้งรหัสผ่าน');
                if (passInput) passInput.focus();
                return;
            }

            if (password.length < 6) {
                e.preventDefault();
                showAlert('รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
                if (passInput) passInput.focus();
                return;
            }

            if (password !== confirmPass) {
                e.preventDefault();
                showAlert('รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
                if (confirmPassInput) confirmPassInput.focus();
                return;
            }

            // Double Submit Prevention
            if (isSubmitting) {
                e.preventDefault();
                return;
            }

            isSubmitting = true;
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-6"></i> <span>กำลังบันทึกข้อมูล...</span>';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRegisterForm);
    } else {
        initRegisterForm();
    }
})();

/**
 * โมดูลควบคุมฟอร์มเข้าสู่ระบบ (Login Form Controller - login.php)
 * @description จัดการความปลอดภัยและการตรวจสอบข้อมูลฝั่ง Client-side:
 *              1. สลับแสดง/ซ่อนรหัสผ่าน (Password Visibility Toggle)
 *              2. กรองเบอร์โทรศัพท์เฉพาะตัวเลข 10 หลักอัตโนมัติ
 *              3. ตรวจสอบการกรอกเบอร์โทรศัพท์และรหัสผ่านก่อนส่งฟอร์ม
 *              4. แสดงข้อความแจ้งเตือนข้อผิดพลาดระดับ Client-side
 *              5. ป้องกันการกดส่งฟอร์มซ้ำ (Double Submit Prevention) พร้อมปุ่ม Loading
 */
(function() {
    function initLoginForm() {
        const form = document.getElementById('loginForm');
        if (!form) return;

        const phoneInput = document.getElementById('member_phone');
        const passInput = document.getElementById('member_password');
        const btnTogglePass = document.getElementById('btnToggleLoginPassword');
        const togglePassIcon = document.getElementById('toggleLoginPasswordIcon');
        const btnSubmit = document.getElementById('btnLoginSubmit');
        const alertBox = document.getElementById('loginClientAlertBox');

        let isSubmitting = false;

        /**
         * แสดงกล่องข้อความแจ้งเตือนข้อผิดพลาดบนฟอร์มเข้าสู่ระบบ
         * @param {string} msg - ข้อความแจ้งเตือน
         */
        function showAlert(msg) {
            if (!alertBox) {
                alert(msg);
                return;
            }
            alertBox.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>${escapeHtml(msg)}</span>
                </div>
            `;
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        /**
         * ล้างกล่องแจ้งเตือนข้อผิดพลาด
         */
        function clearAlert() {
            if (alertBox) alertBox.innerHTML = '';
        }

        /**
         * ป้องกันช่องโหว่ XSS ด้วยการแปลงอักขระพิเศษเป็น HTML Entities
         * @param {string} str - ข้อความดิบ
         * @returns {string} ข้อความที่ผ่านการ Escape แล้ว
         */
        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Toggle Password Visibility
        if (btnTogglePass && passInput && togglePassIcon) {
            btnTogglePass.addEventListener('click', function(e) {
                e.preventDefault();
                if (passInput.type === 'password') {
                    passInput.type = 'text';
                    togglePassIcon.classList.remove('fa-eye');
                    togglePassIcon.classList.add('fa-eye-slash');
                } else {
                    passInput.type = 'password';
                    togglePassIcon.classList.remove('fa-eye-slash');
                    togglePassIcon.classList.add('fa-eye');
                }
            });
        }

        // Phone Input Sanitation (numeric only, max 10 digits)
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            });
        }

        // Form Submit Handler
        form.addEventListener('submit', function(e) {
            clearAlert();

            const phone = phoneInput ? phoneInput.value.trim() : '';
            const password = passInput ? passInput.value : '';

            if (!phone) {
                e.preventDefault();
                showAlert('กรุณากรอกเบอร์โทรศัพท์');
                if (phoneInput) phoneInput.focus();
                return;
            }

            if (!/^0[0-9]{9}$/.test(phone)) {
                e.preventDefault();
                showAlert('เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลักและขึ้นต้นด้วย 0 (เช่น 08X-XXX-XXXX)');
                if (phoneInput) phoneInput.focus();
                return;
            }

            if (!password) {
                e.preventDefault();
                showAlert('กรุณากรอกรหัสผ่าน');
                if (passInput) passInput.focus();
                return;
            }

            // Double Submit Prevention
            if (isSubmitting) {
                e.preventDefault();
                return;
            }

            isSubmitting = true;
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-6"></i> <span>กำลังตรวจสอบข้อมูล...</span>';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoginForm);
    } else {
        initLoginForm();
    }
})();

/* ==========================================================================
   หมวดที่ 12: ระบบประวัติการจองและขอยกเลิก (Booking History & Cancellation - booking_history.php)
   ========================================================================== */

let isCancelSubmitting = false;

/**
 * เปิด Modal ขอยกเลิกการจองสนามและคำนวณนโยบายการคืนเงิน
 * @description ดึงค่าเริ่มต้นชื่อและเบอร์โทรจาก data-default-name และ data-default-phone บน #cancelModal (ป้องกัน XSS)
 * @param {number|string} id - รหัสการจอง
 * @param {string} court - ชื่อสนาม
 * @param {string} date - วันที่จอง
 * @param {string} time - เวลาที่จอง
 * @param {string} price - ยอดเงินรวม
 * @param {string} status - สถานะการจอง (เช่น 'รอตรวจสอบ', 'จองแล้ว')
 * @param {boolean} hasSlip - มีการแนบสลิปแล้วหรือไม่
 */
function openCancelModal(id, court, date, time, price, status, hasSlip) {
    let idInput = document.getElementById('modal_cancel_booking_id');
    let idText = document.getElementById('modal_cancel_id_text');
    let courtText = document.getElementById('modal_cancel_court');
    let dateText = document.getElementById('modal_cancel_date');
    let timeText = document.getElementById('modal_cancel_time');
    let priceText = document.getElementById('modal_cancel_price');

    if (idInput) idInput.value = id;
    if (idText) idText.textContent = id;
    if (courtText) courtText.textContent = court;
    if (dateText) dateText.textContent = date;
    if (timeText) timeText.textContent = time;
    if (priceText) priceText.textContent = price;

    const unverifiedNotice = document.getElementById('modal_unverified_notice');
    const unverifiedSlipNotice = document.getElementById('modal_unverified_slip_notice');
    const policyBox = document.getElementById('modal_refund_policy_box');
    const accountSection = document.getElementById('modal_refund_account_section');
    const titleText = document.getElementById('modal_cancel_title_text');
    const submitText = document.getElementById('modal_cancel_submit_text');
    const submitBtn = document.getElementById('modal_cancel_submit_btn');

    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    }
    isCancelSubmitting = false;

    // ดึงค่าเริ่มต้นของสมาชิกจาก Data Attributes บน #cancelModal (ป้องกัน XSS และแยก Concerns 100%)
    const modal = document.getElementById('cancelModal');
    const defaultMemberName = modal ? (modal.dataset.defaultName || '') : '';
    const defaultMemberPhone = modal ? (modal.dataset.defaultPhone || '') : '';

    const accNoInput = document.getElementById('cancel_refund_account_no');
    const accNameInput = document.getElementById('cancel_refund_account_name');

    // เติมค่าเริ่มต้นเพื่อลดภาระการพิมพ์ (HCI: Avoid Redundant User Input)
    if (accNoInput && !accNoInput.value) accNoInput.value = defaultMemberPhone;
    if (accNameInput && !accNameInput.value) accNameInput.value = defaultMemberName;

    if (status === 'รอตรวจสอบ' && !hasSlip) {
        // กรณีที่ 1: รอแนบสลิป (ยังไม่จ่าย)
        if (unverifiedNotice) unverifiedNotice.style.display = 'block';
        if (unverifiedSlipNotice) unverifiedSlipNotice.style.display = 'none';
        if (policyBox) policyBox.style.display = 'none';
        if (accountSection) accountSection.style.display = 'none';
        if (accNoInput) accNoInput.removeAttribute('required');
        if (accNameInput) accNameInput.removeAttribute('required');
        if (titleText) titleText.textContent = 'ยกเลิกการจองสนาม (ก่อนชำระเงิน)';
        if (submitText) submitText.textContent = 'ยืนยันยกเลิกการจอง';
    } else if (status === 'รอตรวจสอบ' && hasSlip) {
        // กรณีที่ 2: แนบสลิปแล้ว แต่อยู่ระหว่างรอตรวจ (จ่ายแล้ว)
        if (unverifiedNotice) unverifiedNotice.style.display = 'none';
        if (unverifiedSlipNotice) unverifiedSlipNotice.style.display = 'block';
        if (policyBox) policyBox.style.display = 'none';
        if (accountSection) accountSection.style.display = 'block';
        if (accNoInput) accNoInput.setAttribute('required', 'required');
        if (accNameInput) accNameInput.setAttribute('required', 'required');
        if (titleText) titleText.textContent = 'ขอยกเลิกและขอคืนเงิน (แนบสลิปแล้ว)';
        if (submitText) submitText.textContent = 'ส่งคำขอยกเลิกและขอคืนเงิน';
    } else {
        // กรณีที่ 3: จองแล้ว (แอดมินอนุมัติแล้ว)
        if (unverifiedNotice) unverifiedNotice.style.display = 'none';
        if (unverifiedSlipNotice) unverifiedSlipNotice.style.display = 'none';
        if (policyBox) policyBox.style.display = 'block';
        if (accountSection) accountSection.style.display = 'block';
        if (accNoInput) accNoInput.setAttribute('required', 'required');
        if (accNameInput) accNameInput.setAttribute('required', 'required');
        if (titleText) titleText.textContent = 'ขอยกเลิกการจองสนาม (เพื่อขอคืนเงิน)';
        if (submitText) submitText.textContent = 'ส่งคำขอยกเลิกการจอง';
    }

    if (modal) modal.style.display = 'flex';
}

/**
 * ปิด Modal ขอยกเลิกการจองสนาม
 */
function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    if (modal) modal.style.display = 'none';
    const form = document.getElementById('cancelBookingForm');
    if (form) form.reset();
    const submitBtn = document.getElementById('modal_cancel_submit_btn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    }
    isCancelSubmitting = false;
}

/**
 * ป้องกันการกดส่งคำขอยกเลิกการจองซ้ำ (Double Submission Prevention)
 * @param {Event} event - Submit Event
 */
function handleCancelBookingSubmit(event) {
    if (isCancelSubmitting) {
        if (event && typeof event.preventDefault === 'function') event.preventDefault();
        return false;
    }

    isCancelSubmitting = true;
    const submitBtn = document.getElementById('modal_cancel_submit_btn');
    const submitText = document.getElementById('modal_cancel_submit_text');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.75';
        submitBtn.style.cursor = 'not-allowed';
    }
    if (submitText) {
        submitText.innerHTML = '<i class="fas fa-spinner fa-spin mr-6"></i> กำลังส่งคำขอยกเลิก...';
    }
    return true;
}

window.openCancelModal = openCancelModal;
window.closeCancelModal = closeCancelModal;
window.handleCancelBookingSubmit = handleCancelBookingSubmit;

/**
 * เปิด Modal ใบเสร็จรับเงินอิเล็กทรอนิกส์ (E-Receipt)
 * @param {Object} data ข้อมูลการจองและรายการสินค้า/อุปกรณ์
 */
function openReceiptModal(data) {
    if (!data) return;

    const modal = document.getElementById('receiptModal');
    if (!modal) return;

    // 1. จัดการชื่อลูกค้า: ป้องกันชื่อซ้ำซ้อน เช่น Farid cheloh (Farid cheloh)
    let formattedCustName = data.customer_name || 'ลูกค้าทั่วไป';
    const dupMatch = formattedCustName.match(/^(.+?)\s*\(\1\)$/i);
    if (dupMatch) {
        formattedCustName = dupMatch[1];
    }

    // 2. จัดรูปแบบเบอร์โทรศัพท์ (3-3-4 pattern) ให้อ่านง่าย
    let formattedPhone = data.customer_phone || '-';
    const cleanDigits = String(formattedPhone).replace(/\D/g, '');
    if (cleanDigits.length === 10) {
        formattedPhone = cleanDigits.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
    } else if (cleanDigits.length === 9) {
        formattedPhone = cleanDigits.replace(/(\d{2})(\d{3})(\d{4})/, '$1-$2-$3');
    }

    // 3. ปรับชื่อสนามให้เป็นทางการ (เช่น courts 2 -> คอร์ท 2)
    let formattedCourt = data.court || '-';
    const courtMatch = String(formattedCourt).trim().match(/^courts?\s*(\d+)$/i);
    if (courtMatch) {
        formattedCourt = 'คอร์ท ' + courtMatch[1];
    }

    // เติมข้อมูลหลักของเอกสาร
    const idText = document.getElementById('receipt_id_text');
    const createdAt = document.getElementById('receipt_created_at');
    const customerName = document.getElementById('receipt_customer_name');
    const customerPhone = document.getElementById('receipt_customer_phone');
    const courtName = document.getElementById('receipt_court_name');
    const bookingDate = document.getElementById('receipt_booking_date');
    const bookingTime = document.getElementById('receipt_booking_time');
    const stampRef = document.getElementById('receipt_stamp_ref');

    const bookingIdPadded = String(data.id).padStart(4, '0');
    if (idText) idText.textContent = '#REC-BK' + bookingIdPadded;
    if (createdAt) createdAt.textContent = data.created_at || '-';
    if (customerName) customerName.textContent = formattedCustName;
    if (customerPhone) customerPhone.textContent = formattedPhone;
    if (courtName) courtName.textContent = formattedCourt;
    if (bookingDate) bookingDate.textContent = data.date || '-';
    if (bookingTime) bookingTime.textContent = (data.time ? data.time + ' น.' : '-');
    if (stampRef) stampRef.textContent = 'REF: TS-BK' + bookingIdPadded + '-PAID';

    // เติมรายการในตารางแจกแจง
    const tbody = document.getElementById('receipt_items_body');
    if (tbody) {
        tbody.innerHTML = '';
        let itemIndex = 1;

        // 1. แถวค่าบริการสนาม
        const trCourt = document.createElement('tr');
        trCourt.innerHTML = `
            <td class="receipt-col-seq">${itemIndex++}</td>
            <td class="receipt-col-desc">
                <strong>ค่าบริการสนาม: ${escapeReceiptHtml(formattedCourt)}</strong>
                <div class="text-xs-muted">วันที่ ${escapeReceiptHtml(data.date || '-')} เวลา ${escapeReceiptHtml(data.time || '-')} น.</div>
            </td>
            <td class="receipt-col-qty">1 คอร์ท</td>
            <td class="receipt-col-unit">${data.court_price}</td>
            <td class="receipt-col-total font-bold">${data.court_price}</td>
        `;
        tbody.appendChild(trCourt);

        // 2. แถวอุปกรณ์เช่า / สินค้า (ถ้ามี)
        if (Array.isArray(data.items) && data.items.length > 0) {
            data.items.forEach(item => {
                const trItem = document.createElement('tr');
                const qty = parseInt(item.rental_quantity, 10) || 1;
                const unitPrice = parseFloat(item.product_price) || 0;
                const lineTotal = (qty * unitPrice).toFixed(2);
                const typeBadge = item.product_type === 'อุปกรณ์เช่า' ? ' [เช่า]' : ' [สินค้า]';

                trItem.innerHTML = `
                    <td class="receipt-col-seq">${itemIndex++}</td>
                    <td class="receipt-col-desc">
                        <span>${escapeReceiptHtml(item.product_name)}</span>
                        <span class="text-xs-muted">${typeBadge}</span>
                    </td>
                    <td class="receipt-col-qty">${qty} ชิ้น</td>
                    <td class="receipt-col-unit">${unitPrice.toFixed(2)}</td>
                    <td class="receipt-col-total font-bold">${lineTotal}</td>
                `;
                tbody.appendChild(trItem);
            });
        }
    }

    // ยอดรวมและการแจกแจง Subtotals
    const courtSubtotal = document.getElementById('receipt_court_subtotal');
    const rentalSubtotal = document.getElementById('receipt_rental_subtotal');
    const productSubtotal = document.getElementById('receipt_product_subtotal');
    const grandTotal = document.getElementById('receipt_grand_total');

    const rentalRow = document.getElementById('receipt_rental_row');
    const productRow = document.getElementById('receipt_product_row');

    if (courtSubtotal) courtSubtotal.textContent = data.court_price + ' ฿';

    const rentalVal = parseFloat(String(data.rental_price).replace(/,/g, '')) || 0;
    const productVal = parseFloat(String(data.product_price).replace(/,/g, '')) || 0;

    if (rentalSubtotal) rentalSubtotal.textContent = data.rental_price + ' ฿';
    if (rentalRow) rentalRow.style.display = rentalVal > 0 ? '' : 'none';

    if (productSubtotal) productSubtotal.textContent = data.product_price + ' ฿';
    if (productRow) productRow.style.display = productVal > 0 ? '' : 'none';

    if (grandTotal) grandTotal.textContent = data.total_price + ' ฿';

    modal.style.display = 'flex';
}

/**
 * ปิด Modal ใบเสร็จรับเงินอิเล็กทรอนิกส์
 */
function closeReceiptModal() {
    const modal = document.getElementById('receiptModal');
    if (modal) modal.style.display = 'none';
}

function escapeReceiptHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Event Listeners สำหรับการปิด Modal ด้วย Escape หรือคลิกพื้นที่ภายนอก (HCI Standard)
document.addEventListener('DOMContentLoaded', function() {
    const receiptModal = document.getElementById('receiptModal');
    if (receiptModal) {
        receiptModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeReceiptModal();
            }
        });
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReceiptModal();
    }
});

window.openReceiptModal = openReceiptModal;
window.closeReceiptModal = closeReceiptModal;

/* ==========================================================================
   หมวดที่ 13: ระบบข่าวสารและโปรโมชัน (Promotions & Announcements - promotions.php)
   ========================================================================== */

/**
 * เริ่มต้นการทำงานของหน้าข่าวสารและโปรโมชัน
 * จัดการระบบแท็บตัวกรอง (Filter Tabs) และตรวจสอบการเปิด Modal ทันทีผ่าน data-initial-id หรือ Query String ?id=...
 */
function initPromotionsPage() {
    const pageWrapper = document.querySelector('.news-page-wrapper');
    if (!pageWrapper) return;

    const filterBtns = document.querySelectorAll('.btn-filter-tab');
    const cards = document.querySelectorAll('.news-card');
    const filterEmpty = document.getElementById('filterEmptyState');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');

            const filter = this.dataset.filter;
            let visibleCount = 0;

            cards.forEach(card => {
                const cardType = card.dataset.type;
                if (filter === 'all' || cardType === filter) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (filterEmpty) {
                if (visibleCount === 0 && cards.length > 0) {
                    filterEmpty.classList.remove('d-none');
                } else {
                    filterEmpty.classList.add('d-none');
                }
            }
        });
    });

    // ตรวจสอบ Parameter ID จาก data-initial-id หรือ Query String เพื่อเปิด Modal อัตโนมัติ
    const initId = parseInt(pageWrapper.dataset.initialId || new URLSearchParams(window.location.search).get('id') || '0', 10);
    if (initId > 0) {
        openNewsDetailModal(initId);
    }
}

/**
 * เปิด Modal รายละเอียดข่าวสาร/โปรโมชัน
 * @param {number|string} newsId - รหัสข่าวสาร
 */
function openNewsDetailModal(newsId) {
    const idInt = parseInt(newsId, 10);
    if (!idInt || idInt <= 0) return;

    const tpl = document.getElementById('news-data-' + idInt);
    if (tpl && tpl.content) {
        const payload = tpl.content.querySelector('.template-payload');
        if (payload && payload.dataset) {
            renderModalData(
                payload.dataset.title,
                payload.dataset.date,
                payload.dataset.type,
                payload.dataset.image,
                payload.dataset.content
            );
            return;
        }
    }

    // กรณีไม่พบใน template บนหน้าเว็บ ให้เรียกผ่าน AJAX API
    fetch('actions/get_news_detail.php?id=' + idInt)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                renderModalData(res.data.title, res.data.published_at, res.data.type, res.data.image, res.data.content);
            } else {
                alert(res.message || 'ไม่สามารถโหลดข้อมูลข่าวสารได้');
            }
        })
        .catch(err => {
            console.error(err);
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        });
}

/**
 * เรนเดอร์ข้อมูลลงในองค์ประกอบต่างๆ ของ Modal ข่าวสาร
 */
function renderModalData(title, date, type, image, content) {
    let modalTitle = document.getElementById('modalTitle');
    let modalDate = document.getElementById('modalDate');
    let modalType = document.getElementById('modalTypeBadge');
    let modalContent = document.getElementById('modalContent');

    if (modalTitle) modalTitle.textContent = title;
    if (modalDate) modalDate.textContent = date;
    if (modalType) modalType.textContent = type || 'ข่าวสารและกิจกรรม';
    if (modalContent) modalContent.innerHTML = content;

    const imgContainer = document.getElementById('modalImgContainer');
    const imgTag = document.getElementById('modalImg');
    if (image && image !== '') {
        if (imgTag) imgTag.src = image;
        if (imgContainer) imgContainer.classList.remove('d-none');
    } else {
        if (imgContainer) imgContainer.classList.add('d-none');
        if (imgTag) imgTag.src = '';
    }

    const modal = document.getElementById('newsModal');
    if (modal) {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    }
    document.body.style.overflow = 'hidden';
}

/**
 * ปิด Modal รายละเอียดข่าวสาร/โปรโมชัน
 */
function closeNewsDetailModal() {
    const modal = document.getElementById('newsModal');
    if (modal) {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }
    document.body.style.overflow = '';
}

window.openNewsDetailModal = openNewsDetailModal;
window.closeNewsDetailModal = closeNewsDetailModal;

