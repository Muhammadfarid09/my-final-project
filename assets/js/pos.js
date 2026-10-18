/**
 * ==============================================================================
 * T.S. PATTANI BADMINTON COURT - POS WALK-IN & ADVANCED SYSTEM
 * File: assets/js/pos.js
 * Description: โมดูลจัดการระบบขายหน้าร้าน (POS) เฉพาะทางสำหรับการเปิดสนาม Walk-in
 *              คำนวณอัตราค่าบริการตามช่วงเวลา (Peak/Off-peak/Standard) และจัดการตะกร้าสินค้า
 * Author: Antigravity AI & T.S. Pattani Team
 * License: Educational & Commercial Use for T.S. Pattani Badminton Court
 * ==============================================================================
 */

/**
 * เปิด Modal สำหรับระบุรายละเอียดการเปิดสนาม Walk-in หน้าร้าน
 * @param {number|string} courtId - รหัสสนาม
 * @param {string} courtName - ชื่อสนาม
 * @param {number} courtPrice - อัตราค่าบริการปกติ (จ.-พ.-ศ. 180 ฿)
 * @param {number} peakPrice - อัตราค่าบริการช่วงวันหยุด (ส.-อา. 200 ฿)
 * @param {number} offpeakPrice - อัตราค่าบริการช่วงพิเศษกลางวัน (อ.-พฤ. 150 ฿)
 */
function openWalkInCourtModal(courtId, courtName, courtPrice, peakPrice, offpeakPrice) {
    const elId = document.getElementById('modal_court_id');
    const elName = document.getElementById('modal_court_name');
    const elBase = document.getElementById('modal_court_base_price');
    const elPeak = document.getElementById('modal_court_peak_price');
    const elOffpeak = document.getElementById('modal_court_offpeak_price');
    const modal = document.getElementById('walkInCourtModal');

    if (!elId || !modal) return;

    elId.value = courtId;
    if (elName) elName.textContent = courtName;
    if (elBase) elBase.value = courtPrice;
    if (elPeak) elPeak.value = peakPrice || courtPrice;
    if (elOffpeak) elOffpeak.value = offpeakPrice || courtPrice;

    calculateCourtTotal();
    modal.style.display = 'flex';
}

/**
 * ปิด Modal การเปิดสนาม Walk-in หน้าร้าน
 */
function closeWalkInCourtModal() {
    const modal = document.getElementById('walkInCourtModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * คำนวณอัตราค่าบริการสนาม Walk-in แบบ Real-time ตามวันและช่วงเวลาที่เลือก
 * กฎอัตราค่าบริการ:
 * - วันหยุดเสาร์-อาทิตย์: อัตรา Peak (200 ฿/ชม.)
 * - วันธรรมดา:
 *   - ช่วงกลางวัน (08:00 - 17:00): อัตรา Off-peak (150 ฿/ชม.)
 *   - ช่วงเย็น (17:00 - 23:00): อัตราปกติ (180 ฿/ชม.)
 *   - คาบเกี่ยวช่วงเวลา: แยกคำนวณตามชั่วโมงจริงในแต่ละช่วง
 */
function calculateCourtTotal() {
    const elBase = document.getElementById('modal_court_base_price');
    const elPeak = document.getElementById('modal_court_peak_price');
    const elOffpeak = document.getElementById('modal_court_offpeak_price');
    const elHours = document.getElementById('modal_court_hours');
    const elDate = document.getElementById('modal_court_date');
    const elStart = document.getElementById('modal_court_start_time');

    let baseRate = elBase ? (parseFloat(elBase.value) || 180) : 180;
    let peakRate = elPeak ? (parseFloat(elPeak.value) || 200) : 200;
    let offpeakRate = elOffpeak ? (parseFloat(elOffpeak.value) || 150) : 150;
    let hours = elHours ? (parseInt(elHours.value, 10) || 1) : 1;
    let dateVal = elDate ? elDate.value : '';
    let startTime = elStart ? elStart.value : '08:00';

    let startHour = parseInt(startTime.split(':')[0], 10);
    let endHour = startHour + hours;

    let total = 0;
    let badgeText = "วันธรรมดา";
    let badgeBg = "#e0f2fe";
    let badgeColor = "#0369a1";

    if (dateVal) {
        let dateObj = new Date(dateVal + 'T00:00:00');
        let dow = dateObj.getDay(); // 0=Sun, 6=Sat
        if (dow === 0 || dow === 6) {
            total = hours * peakRate;
            badgeText = "วันหยุด ส.-อา. (200฿)";
            badgeBg = "#fef3c7";
            badgeColor = "#b45309";
        } else {
            let dayHours = Math.max(0, Math.min(endHour, 17) - Math.max(startHour, 8));
            let eveHours = Math.max(0, Math.min(endHour, 23) - Math.max(startHour, 17));
            total = (dayHours * offpeakRate) + (eveHours * baseRate);
            if (dayHours > 0 && eveHours > 0) {
                badgeText = "คาบเกี่ยว (กลางวัน+เย็น)";
                badgeBg = "#f3e8ff";
                badgeColor = "#7e22ce";
            } else if (dayHours > 0) {
                badgeText = "กลางวัน (150฿)";
                badgeBg = "#dcfce7";
                badgeColor = "#15803d";
            } else {
                badgeText = "ช่วงเย็น (180฿)";
                badgeBg = "#e0f2fe";
                badgeColor = "#0369a1";
            }
        }
    } else {
        total = hours * baseRate;
    }

    let rateElem = document.getElementById('modal_court_price_rate');
    if (rateElem) {
        rateElem.textContent = (hours > 0 ? (total / hours).toFixed(2) : baseRate.toFixed(2));
    }

    let badgeElem = document.getElementById('modal_court_rate_badge');
    if (badgeElem) {
        badgeElem.textContent = badgeText;
        badgeElem.style.background = badgeBg;
        badgeElem.style.color = badgeColor;
    }

    let totalElem = document.getElementById('modal_court_total_price');
    if (totalElem) {
        totalElem.textContent = total.toFixed(2);
    }
}

/**
 * เพิ่มรายการจองสนาม Walk-in ลงในตะกร้าสินค้า POS
 * พร้อมตรวจสอบเงื่อนไขเวลาเกินเวลาปิดสนาม (23:00 น.) และป้องกันการจองสนามซ้ำซ้อนในเวลาเดียวกัน
 */
function addCourtToCart() {
    let courtId = document.getElementById('modal_court_id').value;
    let courtName = document.getElementById('modal_court_name').textContent;
    let baseRate = parseFloat(document.getElementById('modal_court_base_price').value) || 180;
    let peakRate = parseFloat(document.getElementById('modal_court_peak_price').value) || 200;
    let offpeakRate = parseFloat(document.getElementById('modal_court_offpeak_price').value) || 150;
    let hours = parseInt(document.getElementById('modal_court_hours').value, 10) || 1;
    let date = document.getElementById('modal_court_date').value;
    let startTime = document.getElementById('modal_court_start_time').value;
    let phone = document.getElementById('modal_court_member_phone').value.trim();

    let startHour = parseInt(startTime.split(':')[0], 10);
    let endHour = startHour + hours;

    // ตรวจสอบเงื่อนไขเวลาปิดทำการ (ไม่เกิน 23:00 น.)
    if (endHour > 23) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'เวลาเกินเวลาปิดสนาม',
                text: 'เวลาสิ้นสุดการใช้งาน (' + endHour + ':00 น.) เกินเวลาปิดทำการของสนาม (23:00 น.) กรุณาลดจำนวนชั่วโมงการเล่น',
                confirmButtonColor: '#2563eb'
            });
        } else {
            alert('เวลาสิ้นสุดการใช้งานเกินเวลาปิดสนาม (23:00 น.) กรุณาลดจำนวนชั่วโมงการเล่น');
        }
        return;
    }

    let endTimeStr = (endHour < 10 ? '0' : '') + endHour + ':00';

    let totalPrice = 0;
    if (date) {
        let dateObj = new Date(date + 'T00:00:00');
        let dow = dateObj.getDay();
        if (dow === 0 || dow === 6) {
            totalPrice = hours * peakRate;
        } else {
            let dayHours = Math.max(0, Math.min(endHour, 17) - Math.max(startHour, 8));
            let eveHours = Math.max(0, Math.min(endHour, 23) - Math.max(startHour, 17));
            totalPrice = (dayHours * offpeakRate) + (eveHours * baseRate);
        }
    } else {
        totalPrice = hours * baseRate;
    }

    // อ้างอิงตะกร้าสินค้าจาก Global Environment (admin.js)
    let cart = (typeof posCart !== 'undefined') ? posCart : (window.posCart || []);

    // ป้องกันการเลือกสนามซ้ำหรือเวลาชนกันในตะกร้า POS
    let isDuplicateCourt = cart.some(item => {
        if (!item.is_court || String(item.court_id) !== String(courtId) || item.booking_date !== date) {
            return false;
        }
        let existingStart = item.start_time.substring(0, 5);
        let existingEnd = item.end_time.substring(0, 5);
        return (startTime < existingEnd && endTimeStr > existingStart);
    });

    if (isDuplicateCourt) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'สนามนี้มีอยู่ในตะกร้าแล้ว',
                text: 'สนาม ' + courtName + ' ในช่วงเวลาดังกล่าว มีอยู่ในรายการขายแล้ว กรุณาตรวจสอบตะกร้าสินค้า',
                confirmButtonColor: '#2563eb'
            });
        } else {
            alert('สนาม ' + courtName + ' ในช่วงเวลาดังกล่าว มีอยู่ในรายการขายแล้ว');
        }
        return;
    }

    let itemUniqueId = 'court_' + courtId + '_' + Date.now();

    cart.push({
        id: itemUniqueId,
        is_court: true,
        court_id: courtId,
        name: 'เปิดสนาม ' + courtName + ' (' + startTime + ' - ' + endTimeStr + ')',
        price: totalPrice,
        qty: 1,
        maxStock: 1,
        hours: hours,
        booking_date: date,
        start_time: startTime + ':00',
        end_time: endTimeStr + ':00',
        member_phone: phone
    });

    // หากมีการระบุเบอร์โทรลูกค้า ให้เติมลงในช่องเบอร์โทรลูกค้าของแบบฟอร์ม POS อัตโนมัติ
    if (phone) {
        let custPhoneInput = document.getElementById('posCustomerPhone');
        if (custPhoneInput && !custPhoneInput.value) {
            custPhoneInput.value = phone;
        }
    }

    closeWalkInCourtModal();
    if (typeof updateCartUI === 'function') {
        updateCartUI();
    }
    if (typeof calculateChange === 'function') {
        calculateChange();
    }
}

// ผูกเข้ากับ Global Window Scope เพื่อความเข้ากันได้ย้อนหลัง 100%
window.openWalkInCourtModal = openWalkInCourtModal;
window.closeWalkInCourtModal = closeWalkInCourtModal;
window.calculateCourtTotal = calculateCourtTotal;
window.addCourtToCart = addCourtToCart;

// จัดการคีย์ลัด ESC และ Backdrop Click สำหรับ Walk-in Modal
document.addEventListener('DOMContentLoaded', function() {
    const walkInModal = document.getElementById('walkInCourtModal');
    if (walkInModal) {
        walkInModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeWalkInCourtModal();
            }
        });
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const walkInModal = document.getElementById('walkInCourtModal');
        if (walkInModal && walkInModal.style.display === 'flex') {
            closeWalkInCourtModal();
        }
    }
});

