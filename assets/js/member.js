/* =========================================
   T.S. Pattani - Member/Frontend JavaScript (v1.7)
   ========================================= */

// ฟังก์ชันตรวจสอบความถูกต้องของฟอร์มสมัครสมาชิก (register.php)
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

// ฟังก์ชันซ่อน Alert อัตโนมัติ (ใช้ร่วมกันทุกหน้าฝั่งลูกค้า)
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        let alerts = document.querySelectorAll('.alert-success, .alert-error');
        alerts.forEach(alert => {
            alert.style.transition = "opacity 0.5s";
            alert.style.opacity = "0";
            setTimeout(() => alert.style.display = 'none', 500);
        });
    }, 3000);
});

/* =========================================
   Navbar Controls
   ========================================= */
// เปิด-ปิด Dropdown โปรไฟล์
function toggleDropdown() {
    document.getElementById("userDropdown").classList.toggle("show");
}

// เปิด-ปิด เมนูสำหรับมือถือ (แฮมเบอร์เกอร์)
function toggleMobileMenu() {
    document.getElementById("navMenu").classList.toggle("show");
}

// คลิกที่อื่นบนจอเพื่อปิด Dropdown อัตโนมัติ
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

/* =========================================
   Booking Page Controls (หน้าจองสนาม)
   ========================================= */

// ฟังก์ชันคำนวณยอดเงินและตรวจสอบเงื่อนไขตามวันในสัปดาห์ (booking.php)
// 1. เสาร์ - อาทิตย์ = 200 ฿ (Peak / Weekend Rate)
// 2. จันทร์, พุธ, ศุกร์ = 180 ฿ (Standard Rate)
// 3. อังคาร, พฤหัสบดี = 150 ฿ (Promo / Discount Rate)
function calculateSummary() {
    let startTimeElement = document.getElementById('start_time');
    let endTimeElement = document.getElementById('end_time');
    
    // ถ้าไม่ได้อยู่หน้าจองสนาม ให้ข้ามฟังก์ชันนี้ไปเลย
    if (!startTimeElement || !endTimeElement) return;

    let start = startTimeElement.value;
    let end = endTimeElement.value;
    let timeError = document.getElementById('time-error');
    let btnSubmit = document.getElementById('btnSubmitBooking');
    
    let hours = 0;
    let courtPriceTotal = 0;
    let rentTotal = 0;
    let prodTotal = 0;
    let rateDetailText = "";

    // 1. คำนวณเวลา (ต้อง 1 ชม. ขึ้นไป)
    if (start && end) {
        let startHr = parseInt(start.split(':')[0]);
        let endHr = parseInt(end.split(':')[0]);
        hours = endHr - startHr;

        if (hours < 1) {
            if (timeError) timeError.style.display = "block";
            if (btnSubmit) btnSubmit.disabled = true;
            document.getElementById('sum-hours').innerText = "เวลาไม่ถูกต้อง";
            return; 
        } else {
            if (timeError) timeError.style.display = "none";
            document.getElementById('sum-hours').innerText = hours + " ชั่วโมง";
        }
    }

    // 2. คำนวณค่าสนามตามวันในสัปดาห์
    let selectedCourt = document.querySelector('input[name="court_id"]:checked');
    let bookingDateInput = document.getElementById('booking_date');

    if (selectedCourt && hours >= 1 && bookingDateInput && bookingDateInput.value) {
        let baseRate = parseFloat(selectedCourt.getAttribute('data-price')) || 180;
        let peakRate = parseFloat(selectedCourt.getAttribute('data-peak-price')) || 200;
        let offpeakRate = parseFloat(selectedCourt.getAttribute('data-offpeak-price')) || 150;

        let dateObj = new Date(bookingDateInput.value + 'T00:00:00');
        let dow = dateObj.getDay(); // 0=Sun, 6=Sat, 2=Tue, 4=Thu, 1=Mon, 3=Wed, 5=Fri
        let hourlyRate = baseRate;

        if (dow === 0 || dow === 6) {
            hourlyRate = peakRate;
            rateDetailText = `เสาร์-อาทิตย์: ${hours} ชม. x ${hourlyRate} ฿`;
        } else if (dow === 2 || dow === 4) {
            hourlyRate = offpeakRate;
            rateDetailText = `โปรโมชั่น อังคาร-พฤหัส: ${hours} ชม. x ${hourlyRate} ฿`;
        } else {
            hourlyRate = baseRate;
            rateDetailText = `วันปกติ จันทร์-พุธ-ศุกร์: ${hours} ชม. x ${hourlyRate} ฿`;
        }

        courtPriceTotal = hourlyRate * hours;
    }

    // 3. คำนวณค่าสินค้าและอุปกรณ์เช่า
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

    // 4. สรุปยอดรวมและอัปเดตหน้าจอ
    let courtSummaryText = courtPriceTotal.toLocaleString() + " ฿";
    if (hours > 0 && selectedCourt && rateDetailText) {
        courtSummaryText += `<br><span style="font-size: 11px; color: #64748b; font-weight: normal;">(${rateDetailText})</span>`;
    }

    document.getElementById('sum-court').innerHTML = courtSummaryText;
    document.getElementById('sum-rent').innerText = rentTotal.toLocaleString() + " ฿";
    document.getElementById('sum-product').innerText = prodTotal.toLocaleString() + " ฿";
    
    let grandTotal = courtPriceTotal + rentTotal + prodTotal;
    document.getElementById('sum-total').innerText = grandTotal.toLocaleString() + " ฿";

    // 5. อัปเดต Input Hidden เตรียมส่งให้ PHP
    document.getElementById('input_court_price').value = courtPriceTotal;
    document.getElementById('input_rental_price').value = rentTotal;
    document.getElementById('input_product_price').value = prodTotal;
    document.getElementById('input_total_price').value = grandTotal;

    // ปลดล็อกปุ่มกดยืนยันถ้าเลือกสนามและเวลาถูกต้อง (ขั้นต่ำ 1 ชม.)
    if (hours >= 1 && selectedCourt) {
        if (btnSubmit) btnSubmit.disabled = false;
    } else {
        if (btnSubmit) btnSubmit.disabled = true;
    }
}

// ตรวจสอบก่อนกด Submit ทำรายการจอง
function validateBooking() {
    let totalInput = document.getElementById('input_total_price');
    if (!totalInput) return false;

    let total = parseFloat(totalInput.value);
    if(total <= 0) {
        alert("กรุณาเลือกสนามและเวลาที่ต้องการจอง");
        return false;
    }
    return confirm("ยอดชำระสุทธิ " + total.toLocaleString() + " บาท\nยืนยันการทำรายการจองใช่หรือไม่?");
}

/* =========================================
   Time-Slot Matrix Controller (booking.php)
   ========================================= */

function loadCourtMatrix(dateStr) {
    let container = document.getElementById('matrixContainer');
    if (!container) return;

    container.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #64748b;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top: 10px;">กำลังโหลดตารางความพร้อมของสนาม (${dateStr})...</p>
        </div>
    `;

    fetch('actions/get_court_matrix.php?date=' + encodeURIComponent(dateStr))
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            renderCourtMatrix(res);
        } else {
            container.innerHTML = `<div style="text-align: center; padding: 30px; color: #dc3545;"><i class="fas fa-exclamation-circle fa-2x"></i><p>${res.message || 'ไม่สามารถโหลดข้อมูลได้'}</p></div>`;
        }
    })
    .catch(err => {
        console.error('Matrix error:', err);
        container.innerHTML = `<div style="text-align: center; padding: 30px; color: #dc3545;"><i class="fas fa-exclamation-triangle fa-2x"></i><p>เกิดข้อผิดพลาดในการโหลดตารางสนาม</p></div>`;
    });
}

function renderCourtMatrix(data) {
    let container = document.getElementById('matrixContainer');
    if (!container) return;

    // ป้ายสถานะเรทราคาประจำวันที่เลือก
    let badgeBg = data.rate_category === 'weekend' ? '#fef3c7' : (data.rate_category === 'promo' ? '#dcfce7' : '#e0f2fe');
    let badgeColor = data.rate_category === 'weekend' ? '#b45309' : (data.rate_category === 'promo' ? '#15803d' : '#0369a1');

    let headerInfo = `
        <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 13px;">
            <div>
                <i class="fas fa-calendar-day" style="color: #2563eb;"></i> 
                <strong>${data.day_name}</strong> (${data.date}) : 
                <span style="font-weight: 600; color: #1e3c72;">${data.rate_label}</span>
            </div>
            <span style="background: ${badgeBg}; color: ${badgeColor}; font-size: 11px; padding: 3px 10px; border-radius: 12px; font-weight: 600; border: 1px solid rgba(0,0,0,0.06);">
                <i class="fas fa-tag"></i> ${data.rate_badge}
            </span>
        </div>
    `;

    let html = headerInfo + `<table class="matrix-table"><thead><tr>`;
    html += `<th class="matrix-court-col">สนาม / เวลา</th>`;

    data.slots.forEach(slot => {
        html += `<th>
            <div class="slot-time-header">${slot.start} - ${slot.end}</div>
        </th>`;
    });
    html += `</tr></thead><tbody>`;

    data.courts.forEach(court => {
        html += `<tr>`;
        html += `<td class="matrix-court-col">
            <strong>${court.court_name}</strong>
            <div style="font-size: 10px; color: ${badgeColor}; font-weight: bold;">${court.current_rate} ฿/ชม.</div>
        </td>`;

        data.slots.forEach(slot => {
            let slotInfo = court.slots[slot.start];
            if (!slotInfo) {
                html += `<td class="matrix-cell cell-closed">-</td>`;
                return;
            }

            if (slotInfo.status === 'booked' || slotInfo.status === 'pending') {
                let txt = slotInfo.status === 'pending' ? 'รอตรวจ' : 'จองแล้ว';
                html += `<td class="matrix-cell cell-booked" title="ช่วงเวลานี้มีผู้จองแล้ว">${txt}</td>`;
            } else if (slotInfo.status === 'closed') {
                html += `<td class="matrix-cell cell-closed" title="นอกเวลาทำการ">ปิด</td>`;
            } else {
                let cellClass = 'cell-' + slotInfo.rate_category;
                html += `<td class="matrix-cell ${cellClass}" 
                             data-court-id="${court.court_id}" 
                             data-start="${slot.start}" 
                             data-end="${slot.end}" 
                             onclick="selectSlotFromMatrix(${court.court_id}, '${slot.start}', '${slot.end}', this)"
                             title="คลิกเพื่อเลือกจองช่วง ${slot.start} - ${slot.end} (${slotInfo.price}฿)">
                             <div>ว่าง</div>
                             <div style="font-weight: bold; font-size: 11px;">${slotInfo.price}฿</div>
                         </td>`;
            }
        });

        html += `</tr>`;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;
}

function selectSlotFromMatrix(courtId, startStr, endStr, cellEl) {
    // 1. ไฮไลต์ cell ที่เลือก
    document.querySelectorAll('.matrix-cell.selected').forEach(el => el.classList.remove('selected'));
    if (cellEl) cellEl.classList.add('selected');

    // 2. ซิงค์กับ Form
    let matrixDatePicker = document.getElementById('matrix_date_picker');
    let bookingDateInput = document.getElementById('booking_date');
    if (matrixDatePicker && bookingDateInput) {
        bookingDateInput.value = matrixDatePicker.value;
    }

    // เลือกสนาม
    let courtRadio = document.querySelector(`input[name="court_id"][value="${courtId}"]`);
    if (courtRadio) {
        courtRadio.checked = true;
    }

    // เลือกเวลา
    let startTimeSelect = document.getElementById('start_time');
    let endTimeSelect = document.getElementById('end_time');

    if (startTimeSelect) startTimeSelect.value = startStr;
    if (endTimeSelect) endTimeSelect.value = endStr;

    // คำนวณสรุปยอดเงินทันที
    calculateSummary();

    // เลื่อนหน้าจอลงไปที่ฟอร์มอย่างนุ่มนวล
    let bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function setMatrixDate(type, btnEl) {
    document.querySelectorAll('.btn-matrix-date').forEach(b => b.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');

    let targetDate = new Date();
    if (type === 'tomorrow') {
        targetDate.setDate(targetDate.getDate() + 1);
    }
    let dateStr = targetDate.toISOString().split('T')[0];

    let picker = document.getElementById('matrix_date_picker');
    if (picker) picker.value = dateStr;

    let bookingDate = document.getElementById('booking_date');
    if (bookingDate) bookingDate.value = dateStr;

    loadCourtMatrix(dateStr);
    calculateSummary();
}

function onMatrixDatePickerChange(val) {
    document.querySelectorAll('.btn-matrix-date').forEach(b => b.classList.remove('active'));
    let bookingDate = document.getElementById('booking_date');
    if (bookingDate) bookingDate.value = val;
    loadCourtMatrix(val);
    calculateSummary();
}

/* =========================================
   Payment Countdown Timer (หน้าชำระเงิน)
   ========================================= */
function initPaymentCountdown(lockExpireStr) {
    let countdownElement = document.getElementById("countdown");
    if (!countdownElement || !lockExpireStr) return;

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