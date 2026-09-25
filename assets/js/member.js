/* =========================================
   T.S. Pattani - Member/Frontend JavaScript (v1.0)
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

// ฟังก์ชันคำนวณยอดเงินและตรวจสอบเงื่อนไข (booking.php)
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

    // 1. คำนวณเวลา (แก้เป็นต้อง 1 ชม. ขึ้นไป)
    if (start && end) {
        let startHr = parseInt(start.split(':')[0]);
        let endHr = parseInt(end.split(':')[0]);
        hours = endHr - startHr;

        // แก้ตรงนี้เป็น < 1
        if (hours < 1) {
            timeError.style.display = "block";
            btnSubmit.disabled = true;
            document.getElementById('sum-hours').innerText = "เวลาไม่ถูกต้อง";
            return; 
        } else {
            timeError.style.display = "none";
            document.getElementById('sum-hours').innerText = hours + " ชั่วโมง";
        }
    }

    // 2. คำนวณค่าสนาม
    let selectedCourt = document.querySelector('input[name="court_id"]:checked');
    // 🔴 แก้ตรงนี้: เปลี่ยนจาก hours >= 2 เป็น hours >= 1
    if (selectedCourt && hours >= 1) {
        let pricePerHour = parseFloat(selectedCourt.getAttribute('data-price'));
        courtPriceTotal = pricePerHour * hours;
    }

    // 3. คำนวณค่าสินค้าและอุปกรณ์เช่า
    let qtyInputs = document.querySelectorAll('.qty-input');
    qtyInputs.forEach(input => {
        let qty = parseInt(input.value) || 0;
        let price = parseFloat(input.getAttribute('data-price'));
        let type = input.getAttribute('data-type');
        
        if(qty > 0) {
            if (type === 'อุปกรณ์เช่า') {
                rentTotal += (qty * price);
            } else {
                prodTotal += (qty * price);
            }
        }
    });

    // 4. สรุปยอดรวมและอัปเดตหน้าจอ
    document.getElementById('sum-court').innerText = courtPriceTotal.toLocaleString() + " ฿";
    document.getElementById('sum-rent').innerText = rentTotal.toLocaleString() + " ฿";
    document.getElementById('sum-product').innerText = prodTotal.toLocaleString() + " ฿";
    
    let grandTotal = courtPriceTotal + rentTotal + prodTotal;
    document.getElementById('sum-total').innerText = grandTotal.toLocaleString() + " ฿";

    // 5. อัปเดต Input Hidden เตรียมส่งให้ PHP
    document.getElementById('input_court_price').value = courtPriceTotal;
    document.getElementById('input_rental_price').value = rentTotal;
    document.getElementById('input_product_price').value = prodTotal;
    document.getElementById('input_total_price').value = grandTotal;

    // 🔴 แก้ตรงนี้: เปลี่ยนจาก hours >= 2 เป็น hours >= 1
    // ปลดล็อกปุ่มกดยืนยันถ้าเลือกสนามและเวลาถูกต้อง (ขั้นต่ำ 1 ชม.)
    if (hours >= 1 && selectedCourt) {
        btnSubmit.disabled = false;
    } else {
        btnSubmit.disabled = true;
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