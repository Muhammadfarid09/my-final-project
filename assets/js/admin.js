/**
 * ==============================================================================
 * T.S. PATTANI BADMINTON COURT - ADMIN DASHBOARD JAVASCRIPT ENGINE
 * ไฟล์สคริปต์หลักสำหรับระบบบริหารจัดการหลังบ้าน (Admin Management Panel)
 * ==============================================================================
 * 
 * [สารบัญระบบสคริปต์ / TABLE OF CONTENTS]
 * ------------------------------------------------------------------------------
 *  หมวดที่ 1: ส่วนกลางและระบบช่วยเหลือทั่วไป (Global Utilities & Alerts)
 *  หมวดที่ 2: ระบบเปิดหน้าต่างใบเสร็จอัตโนมัติ (Receipt Auto-trigger - admin/pos.php)
 *  หมวดที่ 3: ระบบ POS ขายหน้าร้าน (Point of Sale System - admin/pos.php)
 *  หมวดที่ 4: ระบบจำลองการชำระเงินด้วย QR Code (PromptPay QR Simulation - admin/pos.php)
 *  หมวดที่ 5: ระบบฟอร์มจัดซื้อและรับของเข้าสต็อก (Purchase / Restock - admin/purchase_add.php)
 *  หมวดที่ 6: ฟังก์ชันเปิดใบเสร็จย้อนหลัง (POS History Receipts - admin/pos_history.php)
 *  หมวดที่ 7: ระบบพิจารณายกเลิกการจองและคืนเงิน (Cancellations & Refunds - admin/cancellations.php)
 *  หมวดที่ 8: ระบบจัดการและแจ้งซ่อมสนาม (Court Maintenance - admin/courts.php)
 *  หมวดที่ 9: ระบบจัดการและส่งซ่อมอุปกรณ์ (Equipment Repairs - admin/products.php & equipment_repairs.php)
 *  หมวดที่ 10: หน้าข่าวสารและประชาสัมพันธ์ (News & Announcements - admin/news.php)
 *  หมวดที่ 11: หน้าตรวจสอบการชำระเงินและสลิปโอน (Payment & Slip Verification - admin/payments.php)
 *  หมวดที่ 12: หน้าแดชบอร์ดและกราฟสถิติ (Dashboard Visualization / Chart.js - admin/index.php)
 *  หมวดที่ 13: หน้าต่างสนทนากับสมาชิก (Live Chat Interface - admin/chats.php)
 *  หมวดที่ 14: เครื่องมือแจ้งเตือนและยืนยันส่วนกลาง SweetAlert2 (HCI Central Alert Engine - ครอบคลุม admin/rewards.php, admin/reward_edit.php)
 *  หมวดที่ 15: ตัวดักจับเหตุการณ์กลางและคีย์ลัด (Central Event Listeners & Shortcuts)
 * ==============================================================================
 */

/* ==============================================================================
   หมวดที่ 1: ส่วนกลางและระบบช่วยเหลือทั่วไป (Global Utilities & Alerts)
   ฟังก์ชันการทำงานพื้นฐานที่โหลดและทำงานในทุกหน้าของระบบหลังบ้าน
   ============================================================================== */

/**
 * ดักจับเหตุการณ์เมื่อโครงสร้าง HTML ถูกโหลดเสร็จสมบูรณ์ (DOMContentLoaded)
 * หน้าที่: ตรวจหากล่องข้อความแจ้งเตือน Flash Alert (.alert-success, .alert-error)
 * และสั่งให้ค่อยๆ จางหาย (Fade Out) และซ่อนตัวเองอัตโนมัติเมื่อครบ 3 วินาที
 */
document.addEventListener("DOMContentLoaded", function() {

    // ค้นหากล่องข้อความแจ้งเตือนทั้งแบบสำเร็จ (สีเขียว) และข้อผิดพลาด (สีแดง)
    var alerts = document.querySelectorAll(".alert-success, .alert-error");
    if (alerts.length > 0) {
        // หน่วงเวลา 3 วินาที (3,000 มิลลิวินาที) ก่อนเริ่มกระบวนการซ่อน
        setTimeout(function() {
            alerts.forEach(function(alert) {
                // ขั้นที่ 1: ปรับ Opacity ให้ค่อยๆ จางลงเป็น 0 ในเวลา 0.5 วินาที
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                
                // ขั้นที่ 2: เมื่อจางสนิทแล้ว (ผ่านไป 500ms) ให้ตั้ง display = 'none' เพื่อคืนพื้นที่บนหน้าจอ
                setTimeout(function() {
                    alert.style.display = "none";
                }, 500); 
            });
        }, 3000); 
    }

});

/* ==============================================================================
   หมวดที่ 2: ระบบเปิดหน้าต่างใบเสร็จอัตโนมัติ (Receipt Auto-trigger - admin/pos.php)
   ตรวจสอบทริกเกอร์หลังการบันทึกรายการขาย POS เพื่อเปิดหน้าต่างใบเสร็จขึ้นมาพิมพ์
   ============================================================================== */

/**
 * ดักจับการโหลดหน้าเว็บเพื่อตรวจสอบคำสั่งเปิดหน้าต่างใบเสร็จ
 * ตรวจสอบจาก Hidden Input: #trigger_receipt_id (POS) หรือ #trigger_booking_id (Walk-in Court)
 * หากพบค่า จะสั่งเปิดหน้าต่าง Pop-up ขนาด 420x650px ไปยัง receipt.php อัตโนมัติ
 */
document.addEventListener('DOMContentLoaded', function() {
    let receiptTrigger = document.getElementById('trigger_receipt_id');
    let bookingTrigger = document.getElementById('trigger_booking_id');
    if (receiptTrigger || bookingTrigger) {
        let posId = receiptTrigger ? receiptTrigger.value : '';
        let bookingId = bookingTrigger ? bookingTrigger.value : '';
        let url = 'receipt.php?';
        if (posId) url += 'pos_id=' + encodeURIComponent(posId);
        if (bookingId) url += (posId ? '&' : '') + 'booking_id=' + encodeURIComponent(bookingId);
        // เปิดหน้าต่าง Pop-up สำหรับพิมพ์ใบเสร็จ
        window.open(url, 'ReceiptWindow', 'width=420,height=650,scrollbars=yes');
    }
});


/* ==============================================================================
   หมวดที่ 3: ระบบ POS ขายหน้าร้าน (Point of Sale System - admin/pos.php)
   ระบบจัดการตะกร้าสินค้า, ค้นหาสินค้า, รับเงินสด, คำนวณเงินทอน และยืนยันการขาย
   ============================================================================== */

/**
 * posCart: อาร์เรย์สำหรับเก็บรายการสินค้า/สนามที่เลือกใส่ตะกร้าในหน้าขายหน้าร้าน (POS)
 * โครงสร้างแต่ละ Object: { id, name, price, qty, maxStock, is_court?, hours? }
 */
let posCart = []; 

/**
 * currentPosCategory: ตัวแปรเก็บสถานะหมวดหมู่สินค้าที่กำลังเลือกดูอยู่ในปัจจุบัน
 * ค่าที่เป็นไปได้: 'all' (ทั้งหมด), 'product' (เครื่องดื่ม/ขนม), 'rental' (อุปกรณ์เช่า), 'court' (สนาม Walk-in)
 */
let currentPosCategory = 'all';

/**
 * กรองและค้นหาสินค้า POS ตามหมวดหมู่และคำค้นหาแบบ Real-time
 * หน้าที่: ตรวจสอบค่าในช่องค้นหา (#posProductSearch) ควบคู่กับแท็บหมวดหมู่ (currentPosCategory)
 * และแสดงเฉพาะการ์ดสินค้า (.product-card) ที่ตรงตามเงื่อนไขทั้งสอง
 */
function applyPosFilters() {
    let searchInput = document.getElementById('posProductSearch');
    let query = searchInput ? searchInput.value.trim().toLowerCase() : '';
    let clearBtn = document.getElementById('posSearchClear');

    // สลับการแสดงปุ่มล้างคำค้นหา (กากบาท): แสดงเมื่อมีข้อความ และซ่อนเมื่อว่างเปล่า
    if (clearBtn) {
        clearBtn.style.display = (query.length > 0) ? 'block' : 'none';
    }

    // วนลูปตรวจสอบการ์ดสินค้าทุกใบในหน้า POS
    document.querySelectorAll('.product-card').forEach(card => {
        let cardType = card.getAttribute('data-type') || '';
        let cardName = card.getAttribute('data-name') || '';

        // เงื่อนไขที่ 1: หมวดหมู่ตรงกัน หรือเลือก 'all'
        let matchCategory = (currentPosCategory === 'all' || cardType === currentPosCategory);
        // เงื่อนไขที่ 2: ชื่อสินค้าหรือประเภทมีคำค้นหาตรงกับที่พิมพ์
        let matchQuery = (query === '' || cardName.includes(query) || cardType.toLowerCase().includes(query));

        // แสดงผลเฉพาะการ์ดที่ผ่านเกณฑ์ทั้งสอง
        if (matchCategory && matchQuery) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * สลับแท็บหมวดหมู่สินค้าในหน้า POS
 * @param {string} type - รหัสหมวดหมู่ เช่น 'all', 'product', 'rental', 'court'
 * @param {HTMLElement} btnElement - องค์ประกอบปุ่มแท็บที่ถูกคลิก
 */
function filterProducts(type, btnElement) {
    currentPosCategory = type;
    // ปรับเปลี่ยนสไตล์ปุ่มแท็บทั้งหมดให้เป็น Inactive
    document.querySelectorAll('.page-tabs .tab-btn').forEach(btn => {
        btn.classList.remove('tab-active');
        btn.classList.add('tab-inactive');
    });
    // ตั้งค่าปุ่มแท็บที่ถูกคลิกให้เป็น Active
    if (btnElement) {
        btnElement.classList.remove('tab-inactive');
        btnElement.classList.add('tab-active');
    }
    // ดำเนินการกรองรายการสินค้าตามหมวดหมู่ใหม่
    applyPosFilters();
}

/**
 * เรียกใช้การค้นหาสินค้าแบบ Real-time เมื่อผู้ใช้พิมพ์ในช่องค้นหา
 */
function searchPosProducts() {
    applyPosFilters();
}

/**
 * ล้างข้อความในช่องค้นหาสินค้า และคืนโฟกัสกลับมาที่ช่องค้นหา
 */
function clearPosSearch() {
    let searchInput = document.getElementById('posProductSearch');
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    applyPosFilters();
}

/**
 * เพิ่มรายการสินค้าลงในตะกร้า (Add to Cart)
 * @param {string|number} id - รหัสสินค้า
 * @param {string} name - ชื่อสินค้า
 * @param {number} price - ราคาต่อหน่วย
 * @param {number} maxStock - จำนวนสต็อกคงเหลือสูงสุดที่สามารถขายได้
 */
function addToCart(id, name, price, maxStock) {
    // ค้นหาว่ามีสินค้ารายการนี้อยู่ในตะกร้าแล้วหรือไม่
    let existingItem = posCart.find(item => item.id === id);
    if (existingItem) {
        // หากมีอยู่แล้ว ตรวจสอบว่าจำนวนไม่เกินสต็อกคงเหลือ
        if (existingItem.qty < maxStock) {
            existingItem.qty++;
        } else {
            // แจ้งเตือนเมื่อสต็อกไม่เพียงพอ
            if (typeof SwalToast === 'function') {
                SwalToast('warning', 'ไม่สามารถเพิ่มได้ สต็อกมีแค่ ' + maxStock + ' ชิ้น');
            } else {
                alert('ไม่สามารถเพิ่มได้ สต็อกมีแค่ ' + maxStock + ' ชิ้น');
            }
        }
    } else {
        // หากยังไม่มีในตะกร้า ให้สร้างรายการใหม่เริ่มต้นที่ 1 ชิ้น
        posCart.push({ id: id, name: name, price: price, qty: 1, maxStock: maxStock });
    }
    // อัปเดตการแสดงผลของตะกร้าบนหน้าจอ
    updateCartUI();
}

/**
 * ปรับเพิ่มหรือลดจำนวนสินค้าในตะกร้า
 * @param {string|number} id - รหัสสินค้า
 * @param {number} change - ค่าที่ต้องการเปลี่ยน เช่น +1 หรือ -1
 */
function updateQty(id, change) {
    let item = posCart.find(item => item.id === id);
    if (item) {
        let newQty = item.qty + change;
        // ต้องมากกว่า 0 และไม่เกินจำนวนสต็อกที่มีอยู่
        if (newQty > 0 && newQty <= item.maxStock) {
            item.qty = newQty;
        } else if (newQty > item.maxStock) {
            if (typeof SwalToast === 'function') {
                SwalToast('warning', 'สินค้าในสต็อกมีไม่เพียงพอ');
            } else {
                alert('สินค้ามีไม่พอ!');
            }
        }
    }
    updateCartUI();
}

/**
 * ลบรายการสินค้าออกจากตะกร้าตามรหัสสินค้า
 * @param {string|number} id - รหัสสินค้าที่ต้องการลบ
 */
function removeFromCart(id) {
    posCart = posCart.filter(item => item.id !== id);
    updateCartUI();
}

/**
 * ล้างรายการทั้งหมดในตะกร้า POS พร้อมกล่องยืนยัน SweetAlert2
 * ป้องกันการเผลอกดล้างรายการโดยไม่ได้ตั้งใจ (HCI Error Prevention)
 */
function confirmClearCart() {
    // หากตะกร้าว่างเปล่า ให้แจ้งเตือนและหยุดการทำงาน
    if (posCart.length === 0) {
        if (typeof SwalToast === 'function') {
            SwalToast('info', 'ยังไม่มีรายการในตะกร้า');
        }
        return;
    }

    // แสดงกล่องยืนยันด้วย SweetAlert2 (ถ้าโหลดสำเร็จ)
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'ล้างรายการในตะกร้า?',
            text: 'ต้องการล้างรายการสินค้าและสนามทั้งหมดในตะกร้าใช่หรือไม่',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#64748B',
            confirmButtonText: '<i class="fas fa-trash-alt mr-6"></i> ยืนยันล้างตะกร้า',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // ล้างอาร์เรย์ตะกร้าและรีเซ็ตค่าในช่องกรอกข้อมูล
                posCart = [];
                let cashInput = document.getElementById('cashReceived');
                if (cashInput) cashInput.value = '';
                let custPhoneInput = document.getElementById('posCustomerPhone');
                if (custPhoneInput) custPhoneInput.value = '';
                updateCartUI();
                if (typeof SwalToast === 'function') {
                    SwalToast('success', 'ล้างรายการในตะกร้าเรียบร้อยแล้ว');
                }
            }
        });
    } else {
        // Fallback กรณีบราวเซอร์ไม่ได้โหลด SweetAlert2
        if (confirm('ต้องการล้างรายการในตะกร้าทั้งหมดใช่หรือไม่?')) {
            posCart = [];
            let cashInput = document.getElementById('cashReceived');
            if (cashInput) cashInput.value = '';
            let custPhoneInput = document.getElementById('posCustomerPhone');
            if (custPhoneInput) custPhoneInput.value = '';
            updateCartUI();
        }
    }
}

/**
 * ปุ่มลัดจำนวนเงินสด (Quick Cash Presets)
 * @param {string|number} amount - ค่าจำนวนเงิน เช่น 'exact' (พอดี), 100, 500, 1000
 */
function setQuickCash(amount) {
    let totalAmountInput = document.getElementById('totalAmountInput');
    let totalPrice = parseFloat(totalAmountInput ? totalAmountInput.value : 0) || 0;
    let cashInput = document.getElementById('cashReceived');

    if (!cashInput) return;

    // ถ้ากดปุ่ม 'พอดี' ให้กรอกจำนวนเท่ากับยอดสุทธิพอดี
    if (amount === 'exact') {
        cashInput.value = (totalPrice > 0) ? totalPrice.toFixed(2) : '';
    } else {
        cashInput.value = parseFloat(amount).toFixed(2);
    }
    // คำนวณเงินทอนทันทีหลังใส่ยอดเงิน
    calculateChange();
}

/**
 * อัปเดตและเรนเดอร์โครงสร้าง UI ของตะกร้าสินค้าในหน้า POS
 * หน้าที่: สร้างแถวตารางใน <tbody>, รวมยอดเงิน/จำนวนชิ้น, ซิงค์ข้อมูลลง Hidden Inputs
 * และอัปเดตการสร้าง QR Code หากเลือกวิธีชำระผ่าน QR
 */
function updateCartUI() {
    let tbody = document.getElementById('cartBody');
    let emptyDiv = document.getElementById('cartEmpty');
    let table = document.getElementById('cartTable');
    let btnCheckout = document.getElementById('btnCheckout');
    
    // หากไม่พบองค์ประกอบตะกร้า (ไม่ได้เปิดหน้า POS) ให้หยุดทำงาน
    if (!tbody) return; 

    tbody.innerHTML = '';
    let totalQty = 0;
    let totalPrice = 0;

    // กรณีไม่มีสินค้าในตะกร้า
    if (posCart.length === 0) {
        emptyDiv.style.display = 'block';
        table.style.display = 'none';
        btnCheckout.disabled = true;
    } else {
        // กรณีมีสินค้าในตะกร้า: แสดงตารางและซ่อนกล่องว่าง
        emptyDiv.style.display = 'none';
        table.style.display = 'table';

        // วนลูปสร้างแถวข้อมูลสินค้าแต่ละรายการ
        posCart.forEach(item => {
            let itemTotal = item.price * item.qty;
            totalQty += item.qty;
            totalPrice += itemTotal;

            let tr = document.createElement('tr');
            let qtyHtml = '';
            // ถ้าเป็นรายการจองสนาม Walk-in ให้แสดงเป็นจำนวนชั่วโมง
            if (item.is_court) {
                qtyHtml = `<span class="badge-info text-12 font-bold">${item.hours || 1} ชม.</span>`;
            } else {
                // ถ้าเป็นสินค้า/อุปกรณ์ ให้แสดงปุ่มปรับเพิ่ม-ลดจำนวน (+/-)
                qtyHtml = `
                    <div class="cart-qty-ctrl">
                        <button type="button" class="cart-qty-btn" onclick="updateQty('${item.id}', -1)">-</button>
                        <span class="font-bold text-13 px-4">${item.qty}</span>
                        <button type="button" class="cart-qty-btn" onclick="updateQty('${item.id}', 1)">+</button>
                    </div>
                `;
            }

            // ประกอบ HTML สำหรับแสดงชื่อสินค้า, ราคา, ปุ่มจำนวน และปุ่มลบ
            tr.innerHTML = `
                <td>
                    <div class="font-bold text-13 text-dark-custom mb-2">${item.name}</div>
                    <div class="text-11 text-muted-custom">@${item.price.toFixed(2)} ฿</div>
                </td>
                <td class="text-center">${qtyHtml}</td>
                <td class="text-right text-success font-bold">${itemTotal.toFixed(2)}</td>
                <td class="text-center">
                    <i class="fas fa-times cart-remove" title="ลบรายการ" onclick="removeFromCart('${item.id}')"></i>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // แสดงผลยอดรวมจำนวนชิ้นและยอดเงินสุทธิ
    document.getElementById('totalItems').innerText = totalQty + ' ชิ้น';
    document.getElementById('totalPrice').innerText = totalPrice.toFixed(2) + ' ฿';
    
    // ซิงค์ข้อมูลตะกร้าลงใน Hidden Input สำหรับส่งฟอร์มไปยังฝั่ง Backend
    document.getElementById('cartDataInput').value = JSON.stringify(posCart);
    document.getElementById('totalAmountInput').value = totalPrice;

    // ถ้าอยู่ในโหมดชำระเงินด้วย QR Code ให้สร้างภาพ QR ใหม่ตามยอดเงินที่เปลี่ยนแปลง
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

    // คำนวณเงินทอนตามยอดรวมล่าสุด
    calculateChange();
}

/**
 * คำนวณเงินทอนหรือยอดเงินที่ยังขาดแบบ Real-time
 * ตรวจสอบยอดเงินสดที่รับมา (#cashReceived) เทียบกับยอดสุทธิ (#totalAmountInput)
 * และเปิด/ปิดการใช้งานปุ่มชำระเงิน (#btnCheckout)
 */
function calculateChange() {
    let elTotal = document.getElementById('totalAmountInput');
    let elCash = document.getElementById('cashReceived');
    let btnCheckout = document.getElementById('btnCheckout');
    let changeSpan = document.getElementById('changeAmount');
    
    if (!elTotal || !changeSpan) return;

    let totalPrice = parseFloat(elTotal.value) || 0;
    
    // ถ้าไม่มีรายการในตะกร้า ให้ปิดปุ่มชำระเงิน
    if (posCart.length === 0) {
        btnCheckout.disabled = true;
        changeSpan.innerText = "0.00 ฿";
        changeSpan.style.color = '#EF4444';
        return;
    }

    let paymentMethod = document.getElementById('paymentMethodInput') ? document.getElementById('paymentMethodInput').value : 'cash';

    // กรณีที่ 1: เลือกวิธีชำระแบบโอนเงิน (QR Code) ไม่ต้องคำนวณเงินทอน
    if (paymentMethod === 'qr') {
        btnCheckout.disabled = false;
        changeSpan.innerText = "- (โอนเงิน)";
        changeSpan.style.color = '#64748B';
        return;
    }

    // กรณีที่ 2: เลือกวิธีชำระด้วยเงินสด (Cash)
    let cashReceived = elCash ? (parseFloat(elCash.value) || 0) : 0;
    let change = cashReceived - totalPrice;
    
    // ถ้ารับเงินมาครบหรือเกิน: แสดงยอดเงินทอน (สีเขียว) และเปิดปุ่มชำระเงิน
    if (change >= 0 && cashReceived >= totalPrice) {
        changeSpan.innerText = change.toFixed(2) + ' ฿';
        changeSpan.style.color = '#059669'; 
        btnCheckout.disabled = false;
    } else {
        // ถ้ารับเงินมาไม่ครบ: แสดงยอดเงินที่ขาด (สีแดง) และปิดปุ่มชำระเงิน
        let missing = totalPrice - cashReceived;
        changeSpan.innerText = 'ขาดอีก ' + missing.toFixed(2) + ' ฿';
        changeSpan.style.color = '#EF4444'; 
        btnCheckout.disabled = true;
    }
}

/**
 * isPosSubmitting: ตัวแปรสถานะป้องกันการกดยืนยันการชำระเงินซ้ำ (Double Submission Prevention)
 */
let isPosSubmitting = false;

/**
 * ตรวจสอบความถูกต้องและยืนยันการชำระเงินก่อนส่งฟอร์ม (Checkout Validation)
 * แสดง SweetAlert2 Modal สรุปยอดเงิน และ Loading State ขณะตัดสต็อก
 * @param {Event} event - ออบเจกต์ Event การส่งฟอร์ม
 * @returns {boolean}
 */
function validateCheckout(event) {
    // ป้องกันการส่งฟอร์มซ้ำขณะกำลังประมวลผล
    if (isPosSubmitting) {
        if (event) event.preventDefault();
        return false;
    }

    let form = document.getElementById('checkoutForm');
    let totalPrice = parseFloat(document.getElementById('totalAmountInput').value) || 0;
    let paymentMethod = document.getElementById('paymentMethodInput') ? document.getElementById('paymentMethodInput').value : 'cash';

    // ตรวจสอบว่ามีรายการในตะกร้าหรือไม่
    if (posCart.length === 0 || totalPrice <= 0) {
        if (typeof SwalToast === 'function') {
            SwalToast('warning', 'กรุณาเลือกสินค้าหรือสนามก่อนชำระเงิน');
        } else {
            alert('กรุณาเลือกสินค้าก่อนชำระเงิน');
        }
        return false;
    }

    // กรณีบราวเซอร์ไม่ได้โหลด SweetAlert2 ให้ใช้ window.confirm
    if (typeof Swal === 'undefined') {
        if (paymentMethod === 'qr') {
            if (confirm("ยืนยันว่าลูกค้าสแกนจ่ายสำเร็จ และยอดเงิน " + totalPrice.toFixed(2) + " บาท เข้าบัญชีแล้วใช่หรือไม่?")) {
                isPosSubmitting = true;
                return true;
            }
            return false;
        } else {
            let cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
            if (cashReceived < totalPrice) {
                alert('รับเงินมาไม่ครบ! ขาดอีก ' + (totalPrice - cashReceived).toFixed(2) + ' บาท');
                return false;
            }
            if (confirm("ยืนยันรับเงินสด " + cashReceived.toFixed(2) + " บาท?")) {
                isPosSubmitting = true;
                return true;
            }
            return false;
        }
    }

    if (event) {
        event.preventDefault();
    }

    // ฟังก์ชันย่อยสำหรับส่งฟอร์มพร้อมแสดงกล่อง Loading ป้องกันการกดซ้ำ
    const triggerSubmitWithLoading = () => {
        if (isPosSubmitting) return;
        isPosSubmitting = true;
        const submitBtn = form.querySelector('button[type="submit"]') || document.getElementById('btnCheckout');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-6"></i> กำลังบันทึก...';
        }
        Swal.fire({
            title: 'กำลังบันทึกรายการขาย...',
            text: 'กรุณารอสักครู่ ระบบกำลังตัดสต็อกและจัดเตรียมใบเสร็จ',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        form.submit();
    };

    // กรณีชำระเงินด้วย QR Code
    if (paymentMethod === 'qr') {
        Swal.fire({
            title: 'ยืนยันรับชำระผ่าน QR Code',
            html: `
                <div class="swal-custom-body">
                    <p class="swal-body-text">ยอดชำระสุทธิ: <strong class="text-primary">${totalPrice.toFixed(2)} ฿</strong></p>
                    <div class="swal-consequence-info">
                        <i class="fas fa-check-circle"></i>
                        <span>กรุณาตรวจสอบยอดเงินโอนเข้าบัญชีเรียบร้อยแล้ว ก่อนกดยืนยันเพื่อบันทึกบิลและพิมพ์ใบเสร็จ</span>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check mr-6"></i> ยืนยันรับชำระแล้ว',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                triggerSubmitWithLoading();
            }
        });
        return false;
    } else {
        // กรณีชำระเงินด้วยเงินสด
        let cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
        if (cashReceived < totalPrice) {
            SwalToast('warning', 'รับเงินสดมาไม่ครบ! ขาดอีก ' + (totalPrice - cashReceived).toFixed(2) + ' บาท');
            return false;
        }
        let change = cashReceived - totalPrice;
        Swal.fire({
            title: 'ยืนยันการรับเงินสด',
            html: `
                <div class="swal-custom-body">
                    <div class="mb-10 text-15">ยอดชำระ: <strong>${totalPrice.toFixed(2)} ฿</strong></div>
                    <div class="mb-10 text-15">รับเงินมา: <strong class="text-primary">${cashReceived.toFixed(2)} ฿</strong></div>
                    <div class="mb-15 text-16">เงินทอนลูกค้า: <strong class="text-success">${change.toFixed(2)} ฿</strong></div>
                    <div class="swal-consequence-info">
                        <i class="fas fa-receipt"></i>
                        <span>ยืนยันเพื่อบันทึกรายการขาย ตัดสต็อกสินค้า และออกใบเสร็จ</span>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check mr-6"></i> ยืนยันรับเงินสด',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                triggerSubmitWithLoading();
            }
        });
        return false;
    }
}

/* ==============================================================================
   หมวดที่ 4: ระบบจำลองการชำระเงินด้วย QR Code (PromptPay QR Simulation - admin/pos.php)
   สร้าง QR Code จำลองสำหรับชำระเงินหน้าร้าน พร้อมตัวนับถอยหลัง 5 นาที
   ============================================================================== */

/**
 * qrCountdownInterval: ออบเจกต์เก็บตัวจับเวลาแบบนับถอยหลัง (setInterval)
 */
let qrCountdownInterval = null;

/**
 * สลับวิธีชำระเงินระหว่าง 'cash' (เงินสด) และ 'qr' (สแกนโอน QR Code)
 * @param {string} method - 'cash' หรือ 'qr'
 */
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
        // เลือกชำระเงินสด: แสดงโซนเงินสด ซ่อนโซน QR และหยุดตัวจับเวลา
        btnCash.classList.add('active');
        sectionCash.style.display = 'block';
        sectionQR.style.display = 'none';
        stopQRCouuntdown(); 
        calculateChange(); 
    } else {
        // เลือกชำระ QR Code: แสดงโซน QR ซ่อนโซนเงินสด และเริ่มสร้าง QR พร้อมจับเวลา
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

/**
 * สร้างภาพ QR Code จำลองและเริ่มนับเวลาถอยหลัง 5 นาที (300 วินาที)
 * @param {number} amount - ยอดเงินสุทธิที่ต้องการรับชำระ
 */
function generateQRCodeAndStartTimer(amount) {
    const qrImg = document.getElementById('qrCodeImage');
    const qrIcon = document.getElementById('qrIconPlaceholder');
    const timerDisplay = document.getElementById('qrTimer');
    const btnCheckout = document.getElementById('btnCheckout');
    
    if (!qrImg || !qrIcon) return;

    // สร้างลิงก์ข้อมูล PromptPay จำลอง
    const promptpayDummy = "0812345678";
    const qrText = encodeURIComponent(`PromptPay:${promptpayDummy}?amount=${amount}`);
    
    // ดึงรูป QR จาก API ภายนอก
    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${qrText}`;
    qrImg.onload = function() {
        qrImg.style.display = 'block';
        qrIcon.style.display = 'none';
    };

    // ล้างตัวจับเวลาเดิมก่อนเริ่มใหม่
    stopQRCouuntdown(); 
    let timeRemaining = 300; // 5 นาที (300 วินาที)

    // นับเวลาถอยหลังทุกๆ 1 วินาที
    qrCountdownInterval = setInterval(() => {
        timeRemaining--;
        
        let minutes = Math.floor(timeRemaining / 60);
        let seconds = timeRemaining % 60;
        
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        
        timerDisplay.innerText = `${minutes}:${seconds}`;

        // เมื่อหมดเวลา (ครบ 5 นาที)
        if (timeRemaining <= 0) {
            stopQRCouuntdown();
            timerDisplay.innerText = "หมดเวลา!";
            btnCheckout.disabled = true;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'หมดเวลาการชำระเงิน',
                    text: 'กรุณาสร้าง QR Code ใหม่โดยการสลับวิธีชำระเงิน หรือทำรายการใหม่',
                    confirmButtonColor: '#1e3c72',
                    confirmButtonText: 'เข้าใจแล้ว'
                });
            } else {
                alert("หมดเวลาการชำระเงิน กรุณาสร้าง QR Code ใหม่โดยการสลับปุ่มไปมา หรือทำรายการใหม่");
            }
        }
    }, 1000);
}

/**
 * หยุดการนับเวลาถอยหลังของ QR Code และรีเซ็ตการแสดงผล
 */
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

/* ==============================================================================
   หมวดที่ 5: ระบบฟอร์มจัดซื้อและรับของเข้าสต็อก (Purchase / Restock - admin/purchase_add.php)
   คำนวณยอดเงินรวมอัตโนมัติขณะกรอกข้อมูลการสั่งซื้อสินค้าเข้าสต็อก
   ============================================================================== */

/**
 * คำนวณยอดเงินรวมการจัดซื้อแบบ Real-time
 * นำจำนวนสินค้า (#purchase_quantity) คูณกับราคาต่อหน่วย (#purchase_price_per_unit)
 * และแสดงผลในช่องยอดเงินรวม (#display_total)
 */
function calcTotal() {
    let elQty = document.getElementById('purchase_quantity');
    let elPrice = document.getElementById('purchase_price_per_unit');
    let elTotal = document.getElementById('display_total');
    
    // ถ้าไม่มีช่องให้กรอก (ไม่ได้อยู่หน้าจัดซื้อ) ให้หยุดทำงาน
    if (!elQty || !elPrice || !elTotal) return; 

    let qty = parseFloat(elQty.value) || 0;
    let price = parseFloat(elPrice.value) || 0;
    let total = qty * price;
    
    // จัดรูปแบบทศนิยม 2 ตำแหน่งพร้อมสัญลักษณ์บาท
    elTotal.value = total.toFixed(2) + ' ฿';
}

/* ==============================================================================
   หมวดที่ 6: ฟังก์ชันเปิดใบเสร็จย้อนหลัง (POS History Receipts - admin/pos_history.php)
   เปิดหน้าต่าง Pop-up สำหรับพิมพ์หรือดูใบเสร็จของรายการขายที่บันทึกแล้ว
   ============================================================================== */

/**
 * เปิดหน้าต่างใบเสร็จย้อนหลังตามรหัสรายการขาย POS
 * @param {string|number} posId - รหัสรายการขาย POS
 */
function openReceipt(posId) {
    if (posId) {
        window.open('receipt.php?pos_id=' + posId, 'ReceiptWindow', 'width=400,height=600,scrollbars=yes');
    }
}

/* ==============================================================================
   หมวดที่ 7: ระบบพิจารณายกเลิกการจองและคืนเงิน (Cancellations & Refunds - admin/cancellations.php)
   จัดการ Modal พิจารณาคืนเงิน, คัดลอกเลขบัญชี และสลับฟิลด์คืนแต้มสะสม
   ============================================================================== */

/**
 * เปิด Modal พิจารณาการยกเลิกและคืนเงิน พร้อมเติมข้อมูลคำขอลงในฟอร์ม
 * @param {number} cancelId - รหัสคำขอยกเลิก
 * @param {number} bookingId - รหัสการจองสนาม
 * @param {number} price - ยอดเงินค่าจองเต็มจำนวน
 * @param {number} penaltyAmount - ค่าธรรมเนียมยกเลิก
 * @param {number} suggestedRefund - ยอดเงินที่แนะนำให้คืน
 * @param {number} penaltyPercent - เปอร์เซ็นต์ค่าธรรมเนียม
 * @param {string} memberName - ชื่อสมาชิกผู้จอง
 * @param {string} memberPhone - เบอร์โทรศัพท์สมาชิก
 * @param {string} slipImage - ชื่อไฟล์รูปภาพสลิปโอนเงิน (ถ้ามี)
 * @param {string} refundBank - ธนาคารของผู้รับเงินคืน
 * @param {string} refundAccNo - เลขบัญชีสำหรับโอนเงินคืน
 * @param {string} refundAccName - ชื่อบัญชีสำหรับโอนเงินคืน
 */
function openRefundModal(cancelId, bookingId, price, penaltyAmount, suggestedRefund, penaltyPercent, memberName, memberPhone, slipImage, refundBank, refundAccNo, refundAccName) {
    let modal = document.getElementById('refundModal');
    if (modal) {
        // เติมรหัสคำขอและรหัสการจองลงใน Hidden Inputs
        document.getElementById('modal_cancel_id').value = cancelId;
        document.getElementById('modal_booking_id').value = bookingId;
        document.getElementById('modal_booking_price').innerText = parseFloat(price).toFixed(2);
        
        // เติมข้อมูลค่าปรับและยอดเงินคืนที่แนะนำ
        let elPenaltyPercent = document.getElementById('modal_penalty_percent');
        let elPenaltyAmount = document.getElementById('modal_penalty_amount');
        let elSuggestedRefund = document.getElementById('modal_suggested_refund');
        
        if(elPenaltyPercent) elPenaltyPercent.innerText = penaltyPercent;
        if(elPenaltyAmount) elPenaltyAmount.innerText = parseFloat(penaltyAmount).toFixed(2);
        if(elSuggestedRefund) elSuggestedRefund.innerText = parseFloat(suggestedRefund).toFixed(2);
        
        // กำหนดค่ายอดเงินคืนเริ่มต้นใน Input
        let elRefundInput = document.getElementById('modal_refund_amount_input');
        if(elRefundInput) elRefundInput.value = parseFloat(suggestedRefund).toFixed(2);

        // เติมข้อมูลผู้จองและบัญชีธนาคารสำหรับโอนเงินคืน
        let elMemberName = document.getElementById('modal_refund_member_name');
        if (elMemberName) elMemberName.innerText = memberName || '-';

        let elMemberPhone = document.getElementById('modal_refund_member_phone');
        if (elMemberPhone) elMemberPhone.innerText = memberPhone || '-';

        let elRefundBank = document.getElementById('modal_refund_bank');
        if (elRefundBank) elRefundBank.innerText = refundBank || '-';

        let elRefundAccNo = document.getElementById('modal_refund_acc_no');
        if (elRefundAccNo) elRefundAccNo.innerText = refundAccNo || '-';

        let elRefundAccName = document.getElementById('modal_refund_acc_name');
        if (elRefundAccName) elRefundAccName.innerText = refundAccName || '-';

        // จัดการแสดงรูปภาพสลิปโอนเงินต้นฉบับ
        let elSlipLink = document.getElementById('modal_refund_slip_link');
        let elSlipBox = document.getElementById('modal_slip_preview_box');
        let elSlipImg = document.getElementById('modal_slip_img');
        let elSlipPreviewLink = document.getElementById('modal_slip_preview_link');

        if (slipImage) {
            let safeSlipUrl = `../view_slip.php?file=${encodeURIComponent(slipImage)}`;
            if (elSlipLink) {
                elSlipLink.innerHTML = `<a href="${safeSlipUrl}" target="_blank" class="btn-sm-info text-12" style="text-decoration: none;"><i class="fas fa-receipt"></i> ดูรูปสลิปต้นฉบับ</a>`;
            }
            if (elSlipBox && elSlipImg && elSlipPreviewLink) {
                elSlipImg.src = safeSlipUrl;
                elSlipPreviewLink.href = safeSlipUrl;
                elSlipBox.style.display = 'block';
            }
        } else {
            if (elSlipLink) {
                elSlipLink.innerHTML = `<span class="text-small-muted">ไม่มีสลิป</span>`;
            }
            if (elSlipBox) {
                elSlipBox.style.display = 'none';
            }
        }
        
        // รีเซ็ตฟิลด์เลือกสถานะและซ่อนกล่องคืนแต้ม
        let elRefundStatus = document.getElementById('refund_status');
        if (elRefundStatus) elRefundStatus.value = '';
        let pointBox = document.getElementById('point_return_group');
        if (pointBox) pointBox.style.display = 'none';
        
        // เปิดแสดงหน้าต่าง Modal
        modal.style.display = 'flex';
    }
}

/**
 * คัดลอกเลขที่บัญชีธนาคารสำหรับโอนเงินคืนลงในคลิปบอร์ด (Clipboard Copy)
 * รองรับทั้ง modern navigator.clipboard และ fallback วิธีดั้งเดิม
 */
function copyRefundAccount() {
    let el = document.getElementById('modal_refund_acc_no');
    let text = el ? el.innerText.trim() : '';
    if (!text || text === '-' || text === 'ไม่มีข้อมูล') {
        if (typeof SwalToast === 'function') {
            SwalToast('warning', 'ไม่มีเลขบัญชีสำหรับคัดลอก');
        } else {
            alert('ไม่มีเลขบัญชีสำหรับคัดลอก');
        }
        return;
    }

    // ใช้วิธี Clipboard API สมัยใหม่เมื่ออยู่ใน Secure Context (HTTPS หรือ Localhost)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            if (typeof SwalToast === 'function') {
                SwalToast('success', 'คัดลอกเลขบัญชี ' + text + ' เรียบร้อยแล้ว');
            } else {
                alert('คัดลอกเลขบัญชี ' + text + ' เรียบร้อยแล้ว');
            }
        }).catch(() => {
            fallbackCopyAccount(text);
        });
    } else {
        fallbackCopyAccount(text);
    }
}

/**
 * ฟังก์ชันสำรองสำหรับคัดลอกข้อความ (Legacy Fallback Copy)
 * @param {string} text - ข้อความที่ต้องการคัดลอก
 */
function fallbackCopyAccount(text) {
    let textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        let successful = document.execCommand('copy');
        if (successful) {
            if (typeof SwalToast === 'function') {
                SwalToast('success', 'คัดลอกเลขบัญชี ' + text + ' เรียบร้อยแล้ว');
            } else {
                alert('คัดลอกเลขบัญชี ' + text + ' เรียบร้อยแล้ว');
            }
        } else {
            if (typeof SwalToast === 'function') {
                SwalToast('error', 'ไม่สามารถคัดลอกได้');
            } else {
                alert('ไม่สามารถคัดลอกได้');
            }
        }
    } catch (err) {
        if (typeof SwalToast === 'function') {
            SwalToast('error', 'ไม่สามารถคัดลอกได้');
        }
    }
    document.body.removeChild(textArea);
}

/**
 * ปิด Modal พิจารณาการยกเลิกและคืนเงิน
 */
function closeRefundModal() {
    let modal = document.getElementById('refundModal');
    if (modal) modal.style.display = 'none';
}

/**
 * สลับการแสดงผลช่องกรอกจำนวนพอยท์ที่จะคืนให้สมาชิก
 * แสดงผลเมื่อเลือกสถานะเป็น 'คืนแล้ว' และซ่อนเมื่อเป็นสถานะอื่น
 */
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

/* ==============================================================================
   หมวดที่ 8: ระบบจัดการและแจ้งซ่อมสนาม (Court Maintenance - admin/courts.php)
   เปิดและปิด Modal สำหรับแจ้งปิดปรับปรุง/ส่งซ่อมสนามแบดมินตัน
   ============================================================================== */

/**
 * เปิด Modal แจ้งซ่อมสนาม
 * @param {number} courtId - รหัสสนาม
 * @param {string} courtName - ชื่อสนาม
 */
function openRepairModal(courtId, courtName) {
    let modal = document.getElementById('repairModal');
    if (modal) {
        document.getElementById('modal_court_id').value = courtId;
        document.getElementById('modal_court_name').innerText = courtName;
        modal.style.display = 'flex';
    }
}

/**
 * ปิด Modal แจ้งซ่อมสนาม
 */
function closeRepairModal() {
    let modal = document.getElementById('repairModal');
    if (modal) modal.style.display = 'none';
}

/* ==============================================================================
   หมวดที่ 9: ระบบจัดการและส่งซ่อมอุปกรณ์ (Equipment Repairs - admin/products.php & equipment_repairs.php)
   จัดการ Modal ส่งซ่อมอุปกรณ์เช่า, ยืนยันซ่อมเสร็จ และตัดจำหน่ายของชำรุด
   ============================================================================== */

/**
 * เปิด Modal ส่งซ่อมอุปกรณ์เช่าจากหน้ารายการสินค้า
 * @param {number} productId - รหัสอุปกรณ์
 * @param {string} productName - ชื่ออุปกรณ์
 * @param {number} maxStock - จำนวนสต็อกสูงสุดที่มีอยู่
 */
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

/**
 * ปิด Modal ส่งซ่อมอุปกรณ์เช่า
 */
function closeEqRepairModal() {
    let modal = document.getElementById('eqRepairModal');
    if (modal) modal.style.display = 'none';
}

/**
 * เปิด Modal ยืนยันว่าการซ่อมอุปกรณ์เสร็จสิ้นแล้วเพื่อนำกลับเข้าสต็อก
 * @param {number} id - รหัสรายการซ่อม
 * @param {string} name - ชื่ออุปกรณ์
 */
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

/**
 * เปิด Modal รายงานว่าอุปกรณ์ชำรุดเสียหายถาวร (ตัดจำหน่าย)
 * @param {number} id - รหัสรายการซ่อม
 * @param {string} name - ชื่ออุปกรณ์
 */
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

/**
 * ฟังก์ชันกลางสำหรับปิด Modal เกี่ยวกับอุปกรณ์ตามรหัส element id
 * @param {string} modalId - ID ของ Modal เช่น 'eqFinishModal', 'eqBrokenModal'
 */
function closeEqModal(modalId) {
    let modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/* ==============================================================================
   หมวดที่ 10: หน้าข่าวสารและประชาสัมพันธ์ (News & Announcements - admin/news.php)
   เปิดและปิด Modal สำหรับเพิ่มข่าวสารและโปรโมชั่นของสนาม
   ============================================================================== */

/**
 * เปิด Modal ฟอร์มเพิ่มข่าวสาร/กิจกรรมใหม่
 */
function openNewsModal() {
    let modal = document.getElementById('newsAddModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

/**
 * ปิด Modal ฟอร์มเพิ่มข่าวสาร/กิจกรรมใหม่
 */
function closeNewsModal() {
    let modal = document.getElementById('newsAddModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/* ==============================================================================
   หมวดที่ 11: หน้าตรวจสอบการชำระเงินและสลิปโอน (Payment & Slip Verification - admin/payments.php)
   เปิด Modal พรีวิวภาพสลิปโอนเงิน ยอดชำระ และข้อมูลการจองเพื่ออนุมัติหรือปฏิเสธ
   ============================================================================== */

/**
 * เปิด Modal ตรวจสอบสลิปและยืนยันการชำระเงิน
 * @param {number} paymentId - รหัสรายการชำระเงิน
 * @param {string} slipImg - ชื่อไฟล์ภาพสลิปในโฟลเดอร์ uploads/slips/
 * @param {number} amount - ยอดเงินที่แจ้งชำระ
 * @param {string} time - วันที่และเวลาที่โอนเงิน
 * @param {number} bookingId - รหัสการจองสนามที่ผูกกับยอดนี้
 * @param {string} memberName - ชื่อสมาชิกผู้ทำรายการ
 */
function openVerifyModal(paymentId, slipImg, amount, time, bookingId, memberName) {
    let modal = document.getElementById('verifyPaymentModal');
    if (modal) {
        document.getElementById('modal_payment_id').value = paymentId;
        document.getElementById('modal_booking_id').value = bookingId;
        
        // เซ็ตข้อมูลรายละเอียดเพื่อแสดงใน Modal
        document.getElementById('preview_slip_img').src = '../view_slip.php?file=' + encodeURIComponent(slipImg);
        document.getElementById('info_member_name').innerText = memberName;
        document.getElementById('info_amount').innerText = amount + ' บาท';
        document.getElementById('info_time').innerText = time;
        document.getElementById('info_booking_id').innerText = '#BK-' + bookingId;

        modal.style.display = 'flex';
    }
}

/**
 * ปิด Modal ตรวจสอบการชำระเงิน
 */
function closeVerifyModal() {
    let modal = document.getElementById('verifyPaymentModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/* ==============================================================================
   หมวดที่ 12: หน้าแดชบอร์ดและกราฟสถิติ (Dashboard Visualization / Chart.js - admin/index.php)
   ประมวลผลและวาดกราฟสถิติภาพรวม 6 รูปแบบด้วย Chart.js จาก Data Attributes
   ============================================================================== */

/**
 * ดักจับเหตุการณ์เมื่อหน้า Dashboard โหลดเสร็จ เพื่อเรนเดอร์กราฟสถิติ Chart.js ทั้ง 6 รูปแบบ
 */
document.addEventListener("DOMContentLoaded", function() {
    
    // --------------------------------------------------------------------------
    // กราฟที่ 1: กราฟแท่งรายรับรวมรายวัน (Daily Revenue Bar Chart)
    // --------------------------------------------------------------------------
    let barCtxEl = document.getElementById('revenueBarChart');
    if (barCtxEl) {
        // ดึงข้อมูลวันที่และยอดเงินที่ซ่อนไว้ใน data-labels และ data-values มาแปลงเป็น Array
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

    // --------------------------------------------------------------------------
    // กราฟที่ 2: กราฟวงกลมโดนัทสัดส่วนรายได้ 3 แหล่ง (Revenue Breakdown Doughnut Chart)
    // สัดส่วน: ค่าจองสนาม (สีเขียว), ค่าเช่าอุปกรณ์ (สีเหลือง), ค่าสินค้า (สีฟ้า)
    // --------------------------------------------------------------------------
    let pieCtxEl = document.getElementById('revenuePieChart');
    if (pieCtxEl) {
        // ดึงค่ายอดเงินแยกตามประเภทจาก data-attributes
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

    // --------------------------------------------------------------------------
    // กราฟที่ 3: กราฟประชากรศาสตร์สัดส่วนเพศ (Member Gender Demographics Doughnut)
    // ชาย (ฟ้า), หญิง (ชมพู), อื่นๆ (ม่วง)
    // --------------------------------------------------------------------------
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

    // --------------------------------------------------------------------------
    // กราฟที่ 4: กราฟแท่งแนวนอนสัดส่วนอาชีพของผู้จอง (Occupation Horizontal Bar Chart)
    // --------------------------------------------------------------------------
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
                indexAxis: 'y', // เปลี่ยนทิศทางแท่งเป็นแนวนอน (Horizontal Bar)
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { beginAtZero: true } }
            }
        });
    }

    // --------------------------------------------------------------------------
    // กราฟที่ 5: กราฟโดนัทสัดส่วนช่วงอายุของสมาชิก (Age Group Doughnut Chart)
    // จัดกลุ่มช่วงอายุ: <18, 18-25, 26-35, 36-50, >50 และไม่ระบุ
    // --------------------------------------------------------------------------
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

    // --------------------------------------------------------------------------
    // กราฟที่ 6: กราฟแท่งซ้อนรายงานรายได้ 12 เดือน (Monthly Revenue Stacked Bar Chart)
    // รวมรายได้ค่าสนาม, ค่าเช่าอุปกรณ์ และค่าสินค้าในแท่งเดียวกันแบบสะสม (Stacked)
    // --------------------------------------------------------------------------
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

/* ==============================================================================
   หมวดที่ 13: หน้าต่างสนทนากับสมาชิก (Live Chat Interface - admin/chats.php)
   จัดการพฤติกรรมการเลื่อนตำแหน่งกล่องข้อความสนทนา
   ============================================================================== */

/**
 * ดักจับการโหลดหน้าแชท เพื่อเลื่อน Scrollbar ของกล่องข้อความลงล่างสุดอัตโนมัติ
 * ทำให้ผู้ดูแลระบบเห็นข้อความล่าสุดทันทีโดยไม่ต้องเลื่อนด้วยมือ
 */
document.addEventListener("DOMContentLoaded", function() {
    let chatBox = document.getElementById("chatMessagesBox");
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
});

/* ==============================================================================
   หมวดที่ 14: เครื่องมือแจ้งเตือนและยืนยันส่วนกลาง SweetAlert2 (HCI Central Alert Engine)
   เครื่องมือสร้าง Modal และ Toast ตามมาตรฐาน HCI เพื่อป้องกันความผิดพลาด (Error Prevention)
   รองรับการใช้งานร่วมกับหน้าระบบของรางวัล (admin/rewards.php, admin/reward_edit.php) และทุกหน้า
   ============================================================================== */

/**
 * แสดง Toast แจ้งเตือนมุมขวาบนแบบลอยตัว (Non-blocking Top-End Toast)
 * ใช้ในกรณีแจ้งเตือนสถานะความสำเร็จหรือข้อมูลสั้นๆ โดยไม่ขัดจังหวะการทำงานของผู้ใช้
 * (เช่น แจ้งเตือนเมื่อเลือกไฟล์รูปภาพของรางวัลใหม่ใน admin/reward_edit.php)
 * @param {string} icon - ชนิดไอคอน: 'success' | 'error' | 'warning' | 'info'
 * @param {string} title - ข้อความแจ้งเตือน
 * @param {number} timer - ระยะเวลาแสดงผล (มิลลิวินาที, ค่าเริ่มต้น 3500ms)
 */
function SwalToast(icon, title, timer = 3500) {
    if (typeof Swal === 'undefined') {
        console.warn('SweetAlert2 is not loaded');
        return;
    }
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
    Toast.fire({
        icon: icon,
        title: title
    });
}

/**
 * isFormRedirecting: ตัวแปรสถานะป้องกันการส่งฟอร์ม Action ซ้ำซ้อน
 */
let isFormRedirecting = false;

/**
 * ผู้ช่วยส่งคำขอ Action ผ่าน Form POST พร้อมแนบ CSRF Token อัตโนมัติ
 * แปลงคำขอที่เป็น GET URL เช่น `_delete_db.php?id=1` ให้กลายเป็นฟอร์ม POST ที่ปลอดภัย
 * @param {string} url - URL ปลายทางที่ต้องการส่งคำขอ
 * @param {object} options - ออปชันเพิ่มเติม เช่น method
 */
function submitActionFormOrRedirect(url, options = {}) {
    if (!url || isFormRedirecting) return;
    isFormRedirecting = true;
    if (url.includes('_db.php') || options.method === 'POST') {
        let form = document.createElement('form');
        form.method = 'POST';
        let parts = url.split('?');
        form.action = parts[0];
        // แปลง Query String เป็น Hidden Inputs
        if (parts[1]) {
            let params = new URLSearchParams(parts[1]);
            params.forEach((val, key) => {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = val;
                form.appendChild(input);
            });
        }
        // ดึงและแนบ CSRF Token จาก Meta Tag หรือ Input ป้องกัน CSRF Attack
        let csrfMeta = document.querySelector('meta[name="csrf-token"]');
        let csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : (document.getElementById('admin_csrf_token')?.value || '');
        if (csrfToken) {
            let csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }
        document.body.appendChild(form);
        form.submit();
    } else {
        window.location.href = url;
    }
}

/**
 * กล่องยืนยันการลบข้อมูลถาวร (SweetAlert2 Delete Confirmation Modal)
 * มีการแจ้งผลกระทบ (Consequence Alert) สีแดงชัดเจนตามเกณฑ์ HCI Error Prevention
 * @param {object} options - การตั้งค่ากล่องยืนยัน เช่น { title, message, consequence, confirmText, actionUrl, onConfirm }
 */
function SwalConfirmDelete(options) {
    if (typeof Swal === 'undefined') {
        if (confirm(options.title || 'ยืนยันการลบข้อมูล?')) {
            if (typeof options.onConfirm === 'function') options.onConfirm();
            else if (options.actionUrl) submitActionFormOrRedirect(options.actionUrl, options);
        }
        return;
    }

    let isDeleteConfirmed = false;
    let title = options.title || 'ยืนยันการลบข้อมูล';
    let message = options.message || options.text || 'คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?';
    let consequence = options.consequence || 'คำเตือน: การลบข้อมูลนี้เป็นการลบถาวร ไม่สามารถกู้คืนได้';
    let confirmText = options.confirmText || 'ยืนยันการลบ';
    let cancelText = options.cancelText || 'ยกเลิก';

    let htmlContent = `
        <div class="swal-custom-body">
            <div class="swal-body-text">${message}</div>
            ${consequence ? `
                <div class="swal-consequence-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>${consequence}</span>
                </div>
            ` : ''}
        </div>
    `;

    Swal.fire({
        title: title,
        html: htmlContent,
        icon: 'warning',
        iconColor: '#dc2626',
        showCancelButton: true,
        confirmButtonText: `<i class="fas fa-trash-alt mr-6"></i> ${confirmText}`,
        cancelButtonText: cancelText,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed && !isDeleteConfirmed) {
            isDeleteConfirmed = true;
            if (typeof options.onConfirm === 'function') {
                options.onConfirm();
            } else if (options.actionUrl) {
                submitActionFormOrRedirect(options.actionUrl, options);
            }
        }
    });
}

/**
 * กล่องยืนยันการทำรายการทั่วไปในระบบ Workflow (Approve, Reject, Toggle, Finish Repair)
 * ปรับเปลี่ยนสีและไอคอนตามความรุนแรงของการกระทำ (type: primary, success, warning, danger)
 * @param {object} options - การตั้งค่ากล่องยืนยัน เช่น { title, message, consequence, confirmText, type, actionUrl, onConfirm }
 */
function SwalConfirmAction(options) {
    if (typeof Swal === 'undefined') {
        if (confirm(options.title || 'ยืนยันการทำรายการ?')) {
            if (typeof options.onConfirm === 'function') options.onConfirm();
            else if (options.actionUrl) submitActionFormOrRedirect(options.actionUrl, options);
        }
        return;
    }

    let isActionConfirmed = false;
    let title = options.title || 'ยืนยันการทำรายการ';
    let message = options.message || options.text || 'คุณต้องการดำเนินการนี้ใช่หรือไม่?';
    let consequence = options.consequence || '';
    let confirmText = options.confirmText || 'ยืนยัน';
    let cancelText = options.cancelText || 'ยกเลิก';
    let type = options.type || 'primary'; // 'primary' | 'success' | 'warning' | 'danger'
    let icon = options.icon || (type === 'danger' ? 'warning' : (type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'question')));

    let confirmColor = '#1e3c72';
    let consequenceClass = 'swal-consequence-info';

    // กำหนดธีมสีตามระดับความสำคัญ
    if (type === 'danger') {
        confirmColor = '#dc2626';
        consequenceClass = 'swal-consequence-danger';
    } else if (type === 'success') {
        confirmColor = '#059669';
        consequenceClass = 'swal-consequence-success';
    } else if (type === 'warning') {
        confirmColor = '#d97706';
        consequenceClass = 'swal-consequence-warning';
    }

    let htmlContent = `<div class="swal-custom-body"><div class="swal-body-text">${message}</div>`;
    if (consequence) {
        htmlContent += `
            <div class="${consequenceClass}">
                <i class="fas fa-info-circle"></i>
                <span>${consequence}</span>
            </div>
        `;
    }
    htmlContent += `</div>`;

    Swal.fire({
        title: title,
        html: htmlContent,
        icon: icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#64748b',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed && !isActionConfirmed) {
            isActionConfirmed = true;
            if (typeof options.onConfirm === 'function') {
                options.onConfirm();
            } else if (options.actionUrl) {
                submitActionFormOrRedirect(options.actionUrl, options);
            }
        }
    });
}

/**
 * ฟังก์ชัน Wrapper สำหรับความเข้ากันได้ย้อนหลัง 100% (Backward Compatibility)
 * หน้าที่: ช่วยให้โค้ดที่มีการเรียก showActionConfirmModal(...) เดิมทำงานผ่าน SweetAlert2 ได้ทันที
 * (ตัวอย่างสำคัญ: ปุ่ม "ลบของรางวัล" ใน admin/rewards.php เรียกใช้ฟังก์ชันนี้)
 * @param {object} options - ออปชันการยืนยัน
 */
function showActionConfirmModal(options) {
    if (options.type === 'danger') {
        SwalConfirmDelete(options);
    } else {
        SwalConfirmAction(options);
    }
}

/**
 * ฟังก์ชันกลางสำหรับปิด Modal ที่กำลังเปิดอยู่ในระบบ Admin อย่างปลอดภัย
 * ตรวจสอบรหัส element id และเรียกฟังก์ชันปิดเฉพาะตัวของแต่ละหน้า เพื่อคืนค่าสถานะให้เรียบร้อย
 * @param {HTMLElement} modal - องค์ประกอบ Modal overlay ที่ต้องการสั่งปิด
 */
function closeActiveAdminModal(modal) {
    if (!modal) return;
    const modalId = modal.id;
    if (modalId === 'refundModal' && typeof closeRefundModal === 'function') {
        closeRefundModal();
    } else if (modalId === 'verifyPaymentModal' && typeof closeVerifyModal === 'function') {
        closeVerifyModal();
    } else if (modalId === 'repairModal' && typeof closeRepairModal === 'function') {
        closeRepairModal();
    } else if (modalId === 'courtEditModal' && typeof closeCourtEditModal === 'function') {
        closeCourtEditModal();
    } else if (modalId === 'walkInCourtModal' && typeof closeWalkInCourtModal === 'function') {
        closeWalkInCourtModal();
    } else if (modalId === 'returnModal' && typeof closeReturnModal === 'function') {
        closeReturnModal();
    } else if (modalId === 'eqRepairModal' && typeof closeEqRepairModal === 'function') {
        closeEqRepairModal();
    } else if (modalId === 'newsAddModal' && typeof closeNewsModal === 'function') {
        closeNewsModal();
    } else {
        modal.style.display = 'none';
    }
}

/* ==============================================================================
   หมวดที่ 15: ตัวดักจับเหตุการณ์กลางและคีย์ลัด (Central Event Listeners & Shortcuts)
   จัดการ Flash Session Toasts, Logout Confirmation, คลิก Backdrop และปุ่ม ESC
   ============================================================================== */

/**
 * Event Listener รวมศูนย์กลางสำหรับจัดการการแจ้งเตือนและการโต้ตอบทั่วทั้งระบบหลังบ้าน
 */
document.addEventListener("DOMContentLoaded", function() {
    
    // --------------------------------------------------------------------------
    // 1. ตรวจสอบ Flash Message จาก Session ที่ส่งมาจาก PHP Header
    // --------------------------------------------------------------------------
    let successEl = document.getElementById('flashSuccessData');
    if (successEl && successEl.dataset && successEl.dataset.message) {
        SwalToast('success', successEl.dataset.message);
    }

    let errorEl = document.getElementById('flashErrorData');
    if (errorEl && errorEl.dataset && errorEl.dataset.message) {
        SwalToast('error', errorEl.dataset.message);
    }

    // --------------------------------------------------------------------------
    // 2. ดักจับปุ่ม Logout ทั้งหมดในระบบ Admin เพื่อแสดง SweetAlert2 ยืนยันก่อนออกจากระบบ
    // ป้องกันการเผลอคลิกออกจากระบบโดยไม่ตั้งใจ (HCI Error Prevention)
    // --------------------------------------------------------------------------
    let logoutBtns = document.querySelectorAll('.btn-logout, a[href*="logout_db.php"]');
    logoutBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            let logoutUrl = this.getAttribute('href');
            Swal.fire({
                title: 'ยืนยันออกจากระบบ?',
                text: 'คุณต้องการออกจากระบบผู้ดูแลระบบ T.S. Pattani ใช่หรือไม่?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt mr-6"></i> ออกจากระบบ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                reverseButtons: true
            }).then((res) => {
                if (res.isConfirmed) {
                    submitActionFormOrRedirect(logoutUrl, { method: 'POST' });
                }
            });
        });
    });

    // --------------------------------------------------------------------------
    // 3. Centralized Modal Backdrop Click Listener
    // สั่งปิด Modal อัตโนมัติเมื่อผู้ใช้คลิกที่พื้นที่พื้นหลังมืดรอบนอก (.modal-overlay)
    // --------------------------------------------------------------------------
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('modal-overlay')) {
            closeActiveAdminModal(e.target);
        }
    });

    // --------------------------------------------------------------------------
    // 4. Centralized ESC Key Handler for Modal Overlays
    // สั่งปิด Modal ที่กำลังเปิดอยู่เมื่อผู้ใช้กดปุ่ม 'Escape' บนคีย์บอร์ด
    // --------------------------------------------------------------------------
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            if (typeof Swal !== 'undefined' && Swal.isVisible && Swal.isVisible()) {
                return; // ปล่อยให้ SweetAlert2 จัดการเหตุการณ์ ESC ของตัวเอง
            }
            const openModals = document.querySelectorAll('.modal-overlay');
            openModals.forEach(modal => {
                const display = window.getComputedStyle(modal).display;
                if (display !== 'none') {
                    closeActiveAdminModal(modal);
                }
            });
        }
    });
});