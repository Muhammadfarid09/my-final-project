/* =========================================
   Global: ฟังก์ชันที่ทำงานทุกหน้า
   ========================================= */
// รอให้หน้าเว็บโหลดโครงสร้าง HTML ให้เสร็จก่อนแล้วค่อยทำงาน
document.addEventListener("DOMContentLoaded", function() {

    // ฟังก์ชันซ่อนกล่องแจ้งเตือน (Alert) อัตโนมัติ (สีเขียว success และสีแดง error)
    var alerts = document.querySelectorAll(".alert-success, .alert-error");
    if (alerts.length > 0) {
        // ตั้งเวลาให้ทำงานหลังจากผ่านไป 3 วินาที (3000 มิลลิวินาที)
        setTimeout(function() {
            alerts.forEach(function(alert) {
                // สั่งให้ค่อยๆ จางหายไป (Fade out)
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                
                // หลังจากจางจนมองไม่เห็น (0.5 วินาที) ให้ซ่อนพื้นที่นั้นทิ้งไป
                setTimeout(function() {
                    alert.style.display = "none";
                }, 500); 
            });
        }, 3000); 
    }

});

/* =========================================
   ระบบเปิดหน้าต่างใบเสร็จอัตโนมัติ (admin/pos.php)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    let receiptTrigger = document.getElementById('trigger_receipt_id');
    let bookingTrigger = document.getElementById('trigger_booking_id');
    if (receiptTrigger || bookingTrigger) {
        let posId = receiptTrigger ? receiptTrigger.value : '';
        let bookingId = bookingTrigger ? bookingTrigger.value : '';
        let url = 'receipt.php?';
        if (posId) url += 'pos_id=' + encodeURIComponent(posId);
        if (bookingId) url += (posId ? '&' : '') + 'booking_id=' + encodeURIComponent(bookingId);
        window.open(url, 'ReceiptWindow', 'width=420,height=650,scrollbars=yes');
    }
});

/* =========================================
   ระบบ POS ขายหน้าร้าน (admin/pos.php)
   ========================================= */
let posCart = []; // ใช้ชื่อ posCart ป้องกันตัวแปรซ้ำซ้อนกับหน้าอื่น

// สลับแท็บหมวดหมู่
function filterProducts(type, btnElement) {
    document.querySelectorAll('.page-tabs .tab-btn').forEach(btn => {
        btn.classList.remove('tab-active');
        btn.classList.add('tab-inactive');
    });
    btnElement.classList.remove('tab-inactive');
    btnElement.classList.add('tab-active');

    document.querySelectorAll('.product-card').forEach(card => {
        if (type === 'all' || card.getAttribute('data-type') === type) {
            card.style.display = 'flex'; // ปรับเป็น flex ตาม CSS ล่าสุด
        } else {
            card.style.display = 'none';
        }
    });
}

// เพิ่มลงตะกร้า
function addToCart(id, name, price, maxStock) {
    let existingItem = posCart.find(item => item.id === id);
    if (existingItem) {
        if (existingItem.qty < maxStock) {
            existingItem.qty++;
        } else {
            alert('ไม่สามารถเพิ่มได้ สต็อกมีแค่ ' + maxStock + ' ชิ้น');
        }
    } else {
        posCart.push({ id: id, name: name, price: price, qty: 1, maxStock: maxStock });
    }
    updateCartUI();
}

// ปรับเพิ่มลดจำนวน
function updateQty(id, change) {
    let item = posCart.find(item => item.id === id);
    if (item) {
        let newQty = item.qty + change;
        if (newQty > 0 && newQty <= item.maxStock) {
            item.qty = newQty;
        } else if (newQty > item.maxStock) {
            alert('สินค้ามีไม่พอ!');
        }
    }
    updateCartUI();
}

// ลบออกจากตะกร้า
function removeFromCart(id) {
    posCart = posCart.filter(item => item.id !== id);
    updateCartUI();
}

// อัปเดตหน้าจอ UI
function updateCartUI() {
    let tbody = document.getElementById('cartBody');
    let emptyDiv = document.getElementById('cartEmpty');
    let table = document.getElementById('cartTable');
    let btnCheckout = document.getElementById('btnCheckout');
    
    // ถ้าไม่มี element เหล่านี้ (แปลว่าไม่ได้อยู่หน้า POS) ให้หยุดทำงาน
    if (!tbody) return; 

    tbody.innerHTML = '';
    let totalQty = 0;
    let totalPrice = 0;

    if (posCart.length === 0) {
        emptyDiv.style.display = 'block';
        table.style.display = 'none';
        btnCheckout.disabled = true;
    } else {
        emptyDiv.style.display = 'none';
        table.style.display = 'table';
        // การปิด/เปิดปุ่มชำระเงินจะถูกจัดการใน calculateChange อีกที

        posCart.forEach(item => {
            let itemTotal = item.price * item.qty;
            totalQty += item.qty;
            totalPrice += itemTotal;

            let tr = document.createElement('tr');
            let qtyHtml = '';
            if (item.is_court) {
                qtyHtml = `<span style="font-size:12px; color:#1e3c72; font-weight:bold;">${item.hours || 1} ชม.</span>`;
            } else {
                qtyHtml = `
                    <button type="button" class="cart-qty-btn" onclick="updateQty('${item.id}', -1)">-</button>
                    <span style="margin: 0 5px;">${item.qty}</span>
                    <button type="button" class="cart-qty-btn" onclick="updateQty('${item.id}', 1)">+</button>
                `;
            }

            tr.innerHTML = `
                <td><div style="font-weight:bold; font-size: 12px; line-height: 1.2;">${item.name}</div><div style="color:#888; font-size:11px;">@${item.price.toFixed(2)}</div></td>
                <td style="text-align: center; white-space: nowrap;">${qtyHtml}</td>
                <td style="text-align: right; color:#28a745; font-weight:bold;">${itemTotal.toFixed(2)}</td>
                <td style="text-align: center;"><i class="fas fa-times cart-remove" onclick="removeFromCart('${item.id}')"></i></td>
            `;
            tbody.appendChild(tr);
        });
    }

    document.getElementById('totalItems').innerText = totalQty + ' ชิ้น';
    document.getElementById('totalPrice').innerText = totalPrice.toFixed(2) + ' ฿';
    
    document.getElementById('cartDataInput').value = JSON.stringify(posCart);
    document.getElementById('totalAmountInput').value = totalPrice;

    // ถ้ากำลังเลือกชำระแบบ QR อยู่ ให้สร้าง QR ใหม่ตามยอดปัจจุบัน
    let paymentMethod = document.getElementById('paymentMethodInput') ? document.getElementById('paymentMethodInput').value : 'cash';
    if (paymentMethod === 'qr' && totalPrice > 0) {
        if(typeof generateQRCodeAndStartTimer === "function") {
            generateQRCodeAndStartTimer(totalPrice);
        }
    } else if (paymentMethod === 'qr' && totalPrice === 0) {
         if(typeof stopQRCouuntdown === "function") {
             stopQRCouuntdown();
         }
    }

    calculateChange();
}

// คำนวณเงินทอน (ปรับปรุงใหม่เพื่อรองรับ QR Code)
function calculateChange() {
    let elTotal = document.getElementById('totalAmountInput');
    let elCash = document.getElementById('cashReceived');
    let btnCheckout = document.getElementById('btnCheckout');
    let changeSpan = document.getElementById('changeAmount');
    
    if (!elTotal || !changeSpan) return;

    let totalPrice = parseFloat(elTotal.value) || 0;
    
    if (posCart.length === 0) {
        btnCheckout.disabled = true;
        changeSpan.innerText = "0.00 ฿";
        changeSpan.style.color = '#dc3545';
        return;
    }

    let paymentMethod = document.getElementById('paymentMethodInput') ? document.getElementById('paymentMethodInput').value : 'cash';

    // กรณีจ่ายแบบโอนเงิน (QR)
    if (paymentMethod === 'qr') {
        btnCheckout.disabled = false;
        changeSpan.innerText = "- (โอนเงิน)";
        changeSpan.style.color = '#666';
        return;
    }

    // กรณีจ่ายแบบเงินสด
    let cashReceived = elCash ? (parseFloat(elCash.value) || 0) : 0;
    let change = cashReceived - totalPrice;
    
    if (change >= 0 && cashReceived >= totalPrice) {
        changeSpan.innerText = change.toFixed(2) + ' ฿';
        changeSpan.style.color = '#28a745'; 
        btnCheckout.disabled = false;
    } else {
        changeSpan.innerText = '0.00 ฿';
        changeSpan.style.color = '#dc3545'; 
        btnCheckout.disabled = true;
    }
}

// ตรวจสอบก่อนชำระเงิน (ปรับปรุงใหม่เพื่อรองรับ QR Code)
function validateCheckout() {
    let totalPrice = parseFloat(document.getElementById('totalAmountInput').value) || 0;
    let paymentMethod = document.getElementById('paymentMethodInput') ? document.getElementById('paymentMethodInput').value : 'cash';

    if (posCart.length === 0 || totalPrice <= 0) {
        alert('กรุณาเลือกสินค้าก่อนชำระเงิน');
        return false;
    }

    if (paymentMethod === 'qr') {
        // หากชำระผ่าน QR ให้ขึ้นกล่อง Confirm ว่าลูกค้าโอนเงินแล้วจริง
        return confirm("ยืนยันว่าลูกค้าสแกนจ่ายสำเร็จ และยอดเงิน " + totalPrice.toFixed(2) + " บาท เข้าบัญชีแล้วใช่หรือไม่?");
    } else {
        // หากชำระผ่านเงินสด
        let cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
        if (cashReceived < totalPrice) {
            alert('รับเงินมาไม่ครบ! ขาดอีก ' + (totalPrice - cashReceived).toFixed(2) + ' บาท');
            return false;
        }
        return confirm("ยืนยันรับเงินสด " + cashReceived.toFixed(2) + " บาท?");
    }
}
/* =========================================
   ระบบจำลองการชำระเงินด้วย QR Code (POS)
   ========================================= */
let qrCountdownInterval = null;

// ฟังก์ชันเลือกวิธีชำระเงิน
function selectPaymentMethod(method) {
    let btnCash = document.getElementById('btnPayCash');
    let btnQR = document.getElementById('btnPayQR');
    let paymentInput = document.getElementById('paymentMethodInput');
    let sectionCash = document.getElementById('cashPaymentSection');
    let sectionQR = document.getElementById('qrPaymentSection');
    let btnCheckout = document.getElementById('btnCheckout');
    let totalInput = document.getElementById('totalAmountInput');

    // ถ้าไม่มี element เหล่านี้ (แปลว่าไม่ได้อยู่หน้า POS) ให้หยุดทำงาน
    if (!btnCash || !btnQR || !paymentInput) return;

    btnCash.classList.remove('active');
    btnQR.classList.remove('active');
    paymentInput.value = method;

    if (method === 'cash') {
        btnCash.classList.add('active');
        sectionCash.style.display = 'block';
        sectionQR.style.display = 'none';
        stopQRCouuntdown(); 
        calculateChange(); 
    } else {
        btnQR.classList.add('active');
        sectionCash.style.display = 'none';
        sectionQR.style.display = 'block';
        
        let currentTotal = parseFloat(totalInput.value) || 0;
        if (currentTotal > 0) {
            btnCheckout.disabled = false;
            generateQRCodeAndStartTimer(currentTotal);
        }
        calculateChange();
    }
}

// ฟังก์ชันสร้าง QR จำลองและเริ่มจับเวลา
function generateQRCodeAndStartTimer(amount) {
    const qrImg = document.getElementById('qrCodeImage');
    const qrIcon = document.getElementById('qrIconPlaceholder');
    const timerDisplay = document.getElementById('qrTimer');
    const btnCheckout = document.getElementById('btnCheckout');
    
    if (!qrImg || !qrIcon) return;

    const promptpayDummy = "0812345678";
    const qrText = encodeURIComponent(`PromptPay:${promptpayDummy}?amount=${amount}`);
    
    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${qrText}`;
    qrImg.onload = function() {
        qrImg.style.display = 'block';
        qrIcon.style.display = 'none';
    };

    stopQRCouuntdown(); 
    let timeRemaining = 300; // 5 นาที

    qrCountdownInterval = setInterval(() => {
        timeRemaining--;
        
        let minutes = Math.floor(timeRemaining / 60);
        let seconds = timeRemaining % 60;
        
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        
        timerDisplay.innerText = `${minutes}:${seconds}`;

        if (timeRemaining <= 0) {
            stopQRCouuntdown();
            timerDisplay.innerText = "หมดเวลา!";
            btnCheckout.disabled = true;
            alert("หมดเวลาการชำระเงิน กรุณาสร้าง QR Code ใหม่โดยการสลับปุ่มไปมา หรือทำรายการใหม่");
        }
    }, 1000);
}
// ฟังก์ชันหยุดจับเวลา QR
function stopQRCouuntdown() {
    if (qrCountdownInterval) {
        clearInterval(qrCountdownInterval);
    }
    let timerDisplay = document.getElementById('qrTimer');
    let qrImg = document.getElementById('qrCodeImage');
    let qrIcon = document.getElementById('qrIconPlaceholder');
    
    if (timerDisplay) timerDisplay.innerText = "05:00";
    if (qrImg) qrImg.style.display = 'none';
    if (qrIcon) qrIcon.style.display = 'block';
}

/* =========================================
   ระบบฟอร์มจัดซื้อ/รับของเข้า (admin/purchase_add.php)
   ========================================= */
function calcTotal() {
    let elQty = document.getElementById('purchase_quantity');
    let elPrice = document.getElementById('purchase_price_per_unit');
    let elTotal = document.getElementById('display_total');
    
    // ถ้าไม่มีช่องให้กรอก (ไม่ได้อยู่หน้าจัดซื้อ) ให้หยุดทำงาน
    if (!elQty || !elPrice || !elTotal) return; 

    let qty = parseFloat(elQty.value) || 0;
    let price = parseFloat(elPrice.value) || 0;
    let total = qty * price;
    
    elTotal.value = total.toFixed(2) + ' ฿';
}

/* =========================================
   ฟังก์ชันเปิดใบเสร็จย้อนหลัง (admin/pos_history.php)
   ========================================= */
function openReceipt(posId) {
    if (posId) {
        window.open('receipt.php?pos_id=' + posId, 'ReceiptWindow', 'width=400,height=600,scrollbars=yes');
    }
}

/* =========================================
   ระบบพิจารณายกเลิก (admin/cancellations.php)
   ========================================= */
function openRefundModal(cancelId, bookingId, price, penaltyAmount, suggestedRefund, penaltyPercent) {
    let modal = document.getElementById('refundModal');
    if (modal) {
        document.getElementById('modal_cancel_id').value = cancelId;
        document.getElementById('modal_booking_id').value = bookingId;
        document.getElementById('modal_booking_price').innerText = parseFloat(price).toFixed(2);
        
        let elPenaltyPercent = document.getElementById('modal_penalty_percent');
        let elPenaltyAmount = document.getElementById('modal_penalty_amount');
        let elSuggestedRefund = document.getElementById('modal_suggested_refund');
        
        if(elPenaltyPercent) elPenaltyPercent.innerText = penaltyPercent;
        if(elPenaltyAmount) elPenaltyAmount.innerText = parseFloat(penaltyAmount).toFixed(2);
        if(elSuggestedRefund) elSuggestedRefund.innerText = parseFloat(suggestedRefund).toFixed(2);
        
        modal.style.display = 'flex';
    }
}

function closeRefundModal() {
    let modal = document.getElementById('refundModal');
    if (modal) modal.style.display = 'none';
}

function togglePointInput() {
    let status = document.getElementById('refund_status').value;
    let pointBox = document.getElementById('point_return_group');
    if (pointBox) {
        if (status === 'คืนแล้ว') {
            pointBox.style.display = 'block';
        } else {
            pointBox.style.display = 'none';
        }
    }
}

/* =========================================
   ระบบแจ้งซ่อมสนาม (admin/courts.php)
   ========================================= */
function openRepairModal(courtId, courtName) {
    let modal = document.getElementById('repairModal');
    if (modal) {
        document.getElementById('modal_court_id').value = courtId;
        document.getElementById('modal_court_name').innerText = courtName;
        modal.style.display = 'flex';
    }
}

function closeRepairModal() {
    let modal = document.getElementById('repairModal');
    if (modal) modal.style.display = 'none';
}

/* =========================================
   ระบบส่งซ่อมอุปกรณ์ (admin/products.php?tab=rental)
   ========================================= */
function openEqRepairModal(productId, productName, maxStock) {
    let modal = document.getElementById('eqRepairModal');
    if (modal) {
        document.getElementById('modal_eq_id').value = productId;
        document.getElementById('modal_eq_name').innerText = productName;
        document.getElementById('modal_eq_max_stock').innerText = maxStock;
        
        // บังคับไม่ให้กรอกจำนวนซ่อมเกินสต็อกที่มีอยู่
        document.getElementById('modal_eq_qty').max = maxStock;
        document.getElementById('modal_eq_qty').value = 1;
        
        modal.style.display = 'flex';
    }
}

function closeEqRepairModal() {
    let modal = document.getElementById('eqRepairModal');
    if (modal) modal.style.display = 'none';
}

/* =========================================
   หน้าประวัติซ่อมบำรุงอุปกรณ์ (admin/equipment_repairs.php)
   ========================================= */
function openEqFinishModal(id, name) {
    let modalId = document.getElementById('modal_finish_id');
    let modalName = document.getElementById('modal_finish_name');
    let modal = document.getElementById('eqFinishModal');
    if (modal && modalId && modalName) {
        modalId.value = id;
        modalName.innerText = name;
        modal.style.display = 'flex';
    }
}

function openEqBrokenModal(id, name) {
    let modalId = document.getElementById('modal_broken_id');
    let modalName = document.getElementById('modal_broken_name');
    let modal = document.getElementById('eqBrokenModal');
    if (modal && modalId && modalName) {
        modalId.value = id;
        modalName.innerText = name;
        modal.style.display = 'flex';
    }
}

function closeEqModal(modalId) {
    let modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/* =========================================
   หน้าข่าวสารและประชาสัมพันธ์ (admin/news.php)
   ========================================= */
function openNewsModal() {
    let modal = document.getElementById('newsAddModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeNewsModal() {
    let modal = document.getElementById('newsAddModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/* =========================================
   หน้าตรวจสอบการชำระเงิน (admin/payments.php)
   ========================================= */
function openVerifyModal(paymentId, slipImg, amount, time, bookingId, memberName) {
    let modal = document.getElementById('verifyPaymentModal');
    if (modal) {
        document.getElementById('modal_payment_id').value = paymentId;
        document.getElementById('modal_booking_id').value = bookingId;
        
        // เซ็ตข้อมูลโชว์ใน Modal
        document.getElementById('preview_slip_img').src = '../uploads/slips/' + slipImg;
        document.getElementById('info_member_name').innerText = memberName;
        document.getElementById('info_amount').innerText = amount + ' บาท';
        document.getElementById('info_time').innerText = time;
        document.getElementById('info_booking_id').innerText = '#BK-' + bookingId;

        modal.style.display = 'flex';
    }
}

function closeVerifyModal() {
    let modal = document.getElementById('verifyPaymentModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/* =========================================
   หน้า Dashboard และกราฟ (admin/index.php)
   ========================================= */
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. โหลดกราฟแท่ง (Bar Chart)
    let barCtxEl = document.getElementById('revenueBarChart');
    if (barCtxEl) {
        // ดึงข้อมูลที่ซ่อนไว้ใน data-attribute ออกมาแปลงกลับเป็น Array
        let labels = JSON.parse(barCtxEl.getAttribute('data-labels'));
        let values = JSON.parse(barCtxEl.getAttribute('data-values'));

        new Chart(barCtxEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'รายรับรวม (บาท)',
                    data: values,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // 2. โหลดกราฟวงกลมโดนัท (Doughnut Chart)
    let pieCtxEl = document.getElementById('revenuePieChart');
    if (pieCtxEl) {
        // ดึงข้อมูลที่ซ่อนไว้ใน data-attribute
        let court = parseFloat(pieCtxEl.getAttribute('data-court'));
        let rental = parseFloat(pieCtxEl.getAttribute('data-rental'));
        let product = parseFloat(pieCtxEl.getAttribute('data-product'));

        new Chart(pieCtxEl.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['ค่าจองสนาม', 'ค่าเช่าอุปกรณ์', 'ค่าสินค้า'],
                datasets: [{
                    data: [court, rental, product],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',  // เขียว (สนาม)
                        'rgba(255, 193, 7, 0.7)',  // เหลือง (เช่า)
                        'rgba(23, 162, 184, 0.7)'  // ฟ้า (สินค้า)
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // 3. โหลดกราฟประชากรศาสตร์: เพศ (Doughnut/Pie Chart)
    let genderCtxEl = document.getElementById('genderPieChart');
    if (genderCtxEl) {
        let labels = JSON.parse(genderCtxEl.getAttribute('data-labels'));
        let values = JSON.parse(genderCtxEl.getAttribute('data-values'));

        new Chart(genderCtxEl.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',  // ชาย (ฟ้า)
                        'rgba(255, 99, 132, 0.7)',  // หญิง (ชมพู)
                        'rgba(153, 102, 255, 0.7)'  // อื่นๆ (ม่วง)
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // 4. โหลดกราฟประชากรศาสตร์: อาชีพ (Horizontal Bar Chart)
    let occCtxEl = document.getElementById('occupationBarChart');
    if (occCtxEl) {
        let labels = JSON.parse(occCtxEl.getAttribute('data-labels'));
        let values = JSON.parse(occCtxEl.getAttribute('data-values'));

        new Chart(occCtxEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'จำนวนครั้งที่จอง (ครั้ง)',
                    data: values,
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y', // เปลี่ยนเป็นกราฟแท่งแนวนอน (Horizontal)
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { beginAtZero: true } }
            }
        });
    }

    // 5. โหลดกราฟประชากรศาสตร์: ช่วงอายุ (Doughnut Chart)
    let ageCtxEl = document.getElementById('ageGroupChart');
    if (ageCtxEl) {
        let labels = JSON.parse(ageCtxEl.getAttribute('data-labels') || '[]');
        let values = JSON.parse(ageCtxEl.getAttribute('data-values') || '[]');

        new Chart(ageCtxEl.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.75)',  // เขียวมิ้นต์ (<18)
                        'rgba(54, 162, 235, 0.75)',  // ฟ้า (18-25)
                        'rgba(255, 206, 86, 0.75)',  // เหลือง (26-35)
                        'rgba(255, 159, 64, 0.75)',  // ส้ม (36-50)
                        'rgba(153, 102, 255, 0.75)', // ม่วง (>50)
                        'rgba(201, 203, 207, 0.75)'  // เทา (ไม่ระบุ)
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // 6. โหลดกราฟรายงานรายได้รายเดือน 12 เดือน (Stacked Bar Chart)
    let monthlyCtxEl = document.getElementById('monthlyRevenueChart');
    if (monthlyCtxEl) {
        let labels = JSON.parse(monthlyCtxEl.getAttribute('data-labels') || '[]');
        let courts = JSON.parse(monthlyCtxEl.getAttribute('data-courts') || '[]');
        let rentals = JSON.parse(monthlyCtxEl.getAttribute('data-rentals') || '[]');
        let products = JSON.parse(monthlyCtxEl.getAttribute('data-products') || '[]');

        new Chart(monthlyCtxEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'ค่าสนาม',
                        data: courts,
                        backgroundColor: 'rgba(40, 167, 69, 0.75)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'ค่าเช่าอุปกรณ์',
                        data: rentals,
                        backgroundColor: 'rgba(255, 193, 7, 0.75)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'ค่าสินค้า',
                        data: products,
                        backgroundColor: 'rgba(23, 162, 184, 0.75)',
                        borderColor: 'rgba(23, 162, 184, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: { 
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) { return val.toLocaleString() + ' ฿'; }
                        }
                    }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + Number(context.raw).toLocaleString() + ' ฿';
                            }
                        }
                    }
                }
            }
        });
    }
});

/* =========================================
   หน้าแชท (admin/chats.php)
   ========================================= */
document.addEventListener("DOMContentLoaded", function() {
    // เลื่อนกล่องแชทลงมาล่างสุดเสมอเมื่อโหลดหน้า
    let chatBox = document.getElementById("chatMessagesBox");
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
});