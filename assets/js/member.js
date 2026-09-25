/* =========================================
   T.S. Pattani - Member/Frontend JavaScript (v1.5)
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

// ฟังก์ชันคำนวณยอดเงินและตรวจสอบเงื่อนไข Dynamic Peak / Off-peak (booking.php)
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
    let peakHours = 0;
    let offpeakHours = 0;
    let peakPriceTotal = 0;
    let offpeakPriceTotal = 0;

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

    // 2. คำนวณค่าสนามแบบ Dynamic Peak / Off-peak
    let selectedCourt = document.querySelector('input[name="court_id"]:checked');
    if (selectedCourt && hours >= 1) {
        let baseRate = parseFloat(selectedCourt.getAttribute('data-price')) || 0;
        let peakRate = parseFloat(selectedCourt.getAttribute('data-peak-price')) || baseRate;
        let offpeakRate = parseFloat(selectedCourt.getAttribute('data-offpeak-price')) || baseRate;

        let startHr = parseInt(start.split(':')[0]);
        let endHr = parseInt(end.split(':')[0]);

        for (let h = startHr; h < endHr; h++) {
            // ช่วง Peak: 17:00 - 22:00 น.
            if (h >= 17 && h < 22) {
                peakHours++;
                peakPriceTotal += peakRate;
            } else {
                offpeakHours++;
                offpeakPriceTotal += offpeakRate;
            }
        }
        courtPriceTotal = peakPriceTotal + offpeakPriceTotal;
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
    if (hours > 0 && selectedCourt) {
        let details = [];
        if (offpeakHours > 0) details.push(`Off-peak: ${offpeakHours} ชม. (${offpeakPriceTotal.toLocaleString()} ฿)`);
        if (peakHours > 0) details.push(`Peak: ${peakHours} ชม. (${peakPriceTotal.toLocaleString()} ฿)`);
        if (details.length > 0) {
            courtSummaryText += `<br><span style="font-size: 11px; color: #64748b; font-weight: normal;">${details.join(' | ')}</span>`;
        }
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

    let html = `<table class="matrix-table"><thead><tr>`;
    html += `<th class="matrix-court-col">สนาม / เวลา</th>`;

    data.slots.forEach(slot => {
        html += `<th>
            <div class="slot-time-header">${slot.start} - ${slot.end}</div>
            ${slot.is_peak ? '<span class="slot-peak-tag">Peak</span>' : '<span class="slot-offpeak-tag">Off-peak</span>'}
        </th>`;
    });
    html += `</tr></thead><tbody>`;

    data.courts.forEach(court => {
        html += `<tr>`;
        html += `<td class="matrix-court-col">
            <strong>${court.court_name}</strong>
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
                let cellClass = slotInfo.is_peak ? 'cell-peak' : 'cell-offpeak';
                html += `<td class="matrix-cell ${cellClass}" 
                             data-court-id="${court.court_id}" 
                             data-start="${slot.start}" 
                             data-end="${slot.end}" 
                             onclick="selectSlotFromMatrix(${court.court_id}, '${slot.start}', '${slot.end}', this)"
                             title="คลิกเพื่อเลือกจองช่วง ${slot.start} - ${slot.end}">
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
}

function onMatrixDatePickerChange(val) {
    document.querySelectorAll('.btn-matrix-date').forEach(b => b.classList.remove('active'));
    let bookingDate = document.getElementById('booking_date');
    if (bookingDate) bookingDate.value = val;
    loadCourtMatrix(val);
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