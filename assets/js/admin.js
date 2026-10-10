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

// ฟังก์ชันเปิด/ปิดเมนูด้านข้างบนมือถือ (Admin Mobile Sidebar Drawer)
function toggleAdminSidebar() {
    var sidebar = document.getElementById('adminSidebar');
    var backdrop = document.getElementById('adminSidebarBackdrop');
    if (sidebar) {
        sidebar.classList.toggle('sidebar-open');
    }
    if (backdrop) {
        backdrop.classList.toggle('active');
    }
}

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
            if (typeof SwalToast === 'function') {
                SwalToast('warning', 'ไม่สามารถเพิ่มได้ สต็อกมีแค่ ' + maxStock + ' ชิ้น');
            } else {
                alert('ไม่สามารถเพิ่มได้ สต็อกมีแค่ ' + maxStock + ' ชิ้น');
            }
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
            if (typeof SwalToast === 'function') {
                SwalToast('warning', 'สินค้าในสต็อกมีไม่เพียงพอ');
            } else {
                alert('สินค้ามีไม่พอ!');
            }
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

// ตรวจสอบก่อนชำระเงิน (ปรับปรุงใหม่ด้วย SweetAlert2)
function validateCheckout(event) {
    let form = document.getElementById('checkoutForm');
    let totalPrice = parseFloat(document.getElementById('totalAmountInput').value) || 0;
    let paymentMethod = document.getElementById('paymentMethodInput') ? document.getElementById('paymentMethodInput').value : 'cash';

    if (posCart.length === 0 || totalPrice <= 0) {
        if (typeof SwalToast === 'function') {
            SwalToast('warning', 'กรุณาเลือกสินค้าหรือสนามก่อนชำระเงิน');
        } else {
            alert('กรุณาเลือกสินค้าก่อนชำระเงิน');
        }
        return false;
    }

    if (typeof Swal === 'undefined') {
        if (paymentMethod === 'qr') {
            return confirm("ยืนยันว่าลูกค้าสแกนจ่ายสำเร็จ และยอดเงิน " + totalPrice.toFixed(2) + " บาท เข้าบัญชีแล้วใช่หรือไม่?");
        } else {
            let cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
            if (cashReceived < totalPrice) {
                alert('รับเงินมาไม่ครบ! ขาดอีก ' + (totalPrice - cashReceived).toFixed(2) + ' บาท');
                return false;
            }
            return confirm("ยืนยันรับเงินสด " + cashReceived.toFixed(2) + " บาท?");
        }
    }

    if (event) {
        event.preventDefault();
    }

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
                form.submit();
            }
        });
        return false;
    } else {
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
                form.submit();
            }
        });
        return false;
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
function openRefundModal(cancelId, bookingId, price, penaltyAmount, suggestedRefund, penaltyPercent, memberName, memberPhone, slipImage, refundBank, refundAccNo, refundAccName) {
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
        
        let elRefundInput = document.getElementById('modal_refund_amount_input');
        if(elRefundInput) elRefundInput.value = parseFloat(suggestedRefund).toFixed(2);

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

        let elSlipLink = document.getElementById('modal_refund_slip_link');
        let elSlipBox = document.getElementById('modal_slip_preview_box');
        let elSlipImg = document.getElementById('modal_slip_img');
        let elSlipPreviewLink = document.getElementById('modal_slip_preview_link');

        if (slipImage) {
            if (elSlipLink) {
                elSlipLink.innerHTML = `<a href="../uploads/slips/${slipImage}" target="_blank" class="btn-sm-info text-12" style="text-decoration: none;"><i class="fas fa-receipt"></i> ดูรูปสลิปต้นฉบับ</a>`;
            }
            if (elSlipBox && elSlipImg && elSlipPreviewLink) {
                elSlipImg.src = `../uploads/slips/${slipImage}`;
                elSlipPreviewLink.href = `../uploads/slips/${slipImage}`;
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
        
        // รีเซ็ตฟิลด์เลือกสถานะ
        let elRefundStatus = document.getElementById('refund_status');
        if (elRefundStatus) elRefundStatus.value = '';
        let pointBox = document.getElementById('point_return_group');
        if (pointBox) pointBox.style.display = 'none';
        
        modal.style.display = 'flex';
    }
}

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

/* =========================================
   SweetAlert2 Central Engine for Admin T.S. Pattani
   (HCI Rule 5: Error Prevention & Consistency)
   ========================================= */

/**
 * แสดง Toast แจ้งเตือนมุมขวาบนแบบลอยตัว (Non-blocking Top-End Toast)
 * @param {string} icon - 'success' | 'error' | 'warning' | 'info'
 * @param {string} title - ข้อความแจ้งเตือน
 * @param {number} timer - เวลาแสดงผล (มิลลิวินาที, ค่าเริ่มต้น 3500)
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
 * ผู้ช่วยส่งคำขอ Action ผ่าน Form POST พร้อม CSRF Token อัตโนมัติ
 * สำหรับ Action Scripts เช่น _delete_db.php หรือ _finish_db.php
 */
function submitActionFormOrRedirect(url, options = {}) {
    if (!url) return;
    if (url.includes('_db.php') || options.method === 'POST') {
        let form = document.createElement('form');
        form.method = 'POST';
        let parts = url.split('?');
        form.action = parts[0];
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
 * กล่องยืนยันการลบข้อมูล (Delete Confirmation)
 * @param {object} options - การตั้งค่ากล่องยืนยัน
 */
function SwalConfirmDelete(options) {
    if (typeof Swal === 'undefined') {
        if (confirm(options.title || 'ยืนยันการลบข้อมูล?')) {
            if (typeof options.onConfirm === 'function') options.onConfirm();
            else if (options.actionUrl) submitActionFormOrRedirect(options.actionUrl, options);
        }
        return;
    }

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
        if (result.isConfirmed) {
            if (typeof options.onConfirm === 'function') {
                options.onConfirm();
            } else if (options.actionUrl) {
                submitActionFormOrRedirect(options.actionUrl, options);
            }
        }
    });
}

/**
 * กล่องยืนยันการทำรายการทั่วไปใน Workflow (Approve, Reject, Toggle, Finish Repair)
 * @param {object} options - การตั้งค่ากล่องยืนยัน
 */
function SwalConfirmAction(options) {
    if (typeof Swal === 'undefined') {
        if (confirm(options.title || 'ยืนยันการทำรายการ?')) {
            if (typeof options.onConfirm === 'function') options.onConfirm();
            else if (options.actionUrl) submitActionFormOrRedirect(options.actionUrl, options);
        }
        return;
    }

    let title = options.title || 'ยืนยันการทำรายการ';
    let message = options.message || options.text || 'คุณต้องการดำเนินการนี้ใช่หรือไม่?';
    let consequence = options.consequence || '';
    let confirmText = options.confirmText || 'ยืนยัน';
    let cancelText = options.cancelText || 'ยกเลิก';
    let type = options.type || 'primary'; // 'primary' | 'success' | 'warning' | 'danger'
    let icon = options.icon || (type === 'danger' ? 'warning' : (type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'question')));

    let confirmColor = '#1e3c72';
    let consequenceClass = 'swal-consequence-info';

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
        if (result.isConfirmed) {
            if (typeof options.onConfirm === 'function') {
                options.onConfirm();
            } else if (options.actionUrl) {
                submitActionFormOrRedirect(options.actionUrl, options);
            }
        }
    });
}

/**
 * ฟังก์ชัน Wrapper สำหรับความเข้ากันได้ย้อนหลัง (Backward Compatibility)
 * ช่วยให้โค้ดที่มีการเรียก showActionConfirmModal(...) เดิมทำงานผ่าน SweetAlert2 ได้ทันที 100%
 */
function showActionConfirmModal(options) {
    if (options.type === 'danger') {
        SwalConfirmDelete(options);
    } else {
        SwalConfirmAction(options);
    }
}

/**
 * Event Listener กลางสำหรับ Flash Session Toasts และ Logout Confirmation
 */
document.addEventListener("DOMContentLoaded", function() {
    // 1. ตรวจสอบ Flash Message จาก Session ที่ส่งมาจาก PHP Header
    let successEl = document.getElementById('flashSuccessData');
    if (successEl && successEl.dataset && successEl.dataset.message) {
        SwalToast('success', successEl.dataset.message);
    }

    let errorEl = document.getElementById('flashErrorData');
    if (errorEl && errorEl.dataset && errorEl.dataset.message) {
        SwalToast('error', errorEl.dataset.message);
    }

    // 2. ดักจับปุ่ม Logout ทั้งหมดในระบบ Admin เพื่อให้ขึ้น SweetAlert2 ยืนยัน
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
                    window.location.href = logoutUrl;
                }
            });
        });
    });
});