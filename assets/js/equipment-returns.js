/**
 * ==============================================================================
 * T.S. PATTANI BADMINTON COURT - EQUIPMENT RETURN & PENALTY SYSTEM
 * File: assets/js/equipment-returns.js
 * Description: โมดูลเฉพาะทางสำหรับระบบตรวจรับคืนอุปกรณ์กีฬา (Equipment Return)
 *              คำนวณค่าปรับเกินกำหนด/ชำรุด/สูญหาย และยืนยันการรับคืนผ่าน SweetAlert2
 * Author: Antigravity AI & T.S. Pattani Team
 * License: Educational & Commercial Use for T.S. Pattani Badminton Court
 * ==============================================================================
 */

(function() {
    'use strict';

    /**
     * ตัวแปรจำกัดขอบเขตภายในโมดูล (Module-scoped variables)
     */
    let currentLateMinutes = 0;
    let currentItemPrice = 0;
    let isReturnSubmitting = false;

    /**
     * เปิด Modal ตรวจรับคืนอุปกรณ์กีฬา พร้อมตั้งค่าเริ่มต้นและคำนวณค่าปรับคืนช้าอัตโนมัติ
     * @param {number|string} id - รหัสการเช่า (#RT{id})
     * @param {string} name - ชื่ออุปกรณ์ที่เช่า
     * @param {number} qty - จำนวนชิ้น
     * @param {string} member - ชื่อผู้เช่า
     * @param {string} due - เวลาที่ครบกำหนดคืน
     * @param {boolean} isLate - สถานะว่าเกินกำหนดคืนหรือไม่
     * @param {number} lateMinutes - จำนวนนาทีที่เกินกำหนด
     * @param {number} price - ราคาของอุปกรณ์ต่อชิ้น (สำหรับกรณีชดใช้หากสูญหาย)
     */
    window.openReturnModal = function(id, name, qty, member, due, isLate, lateMinutes, price) {
        const rentalIdInput = document.getElementById('modal_rental_id');
        const itemNameElem = document.getElementById('modal_item_name');
        const qtyElem = document.getElementById('modal_qty');
        const memberNameElem = document.getElementById('modal_member_name');
        const dueTimeElem = document.getElementById('modal_due_time');
        const lateBox = document.getElementById('lateAlertBox');
        const lateText = document.getElementById('modal_late_text');
        const fineInput = document.getElementById('fineAmountInput');
        const reasonInput = document.getElementById('fineReasonInput');
        const defaultConditionRadio = document.querySelector('input[name="condition_status"][value="ปกติ"]');
        const repairCauseGroup = document.getElementById('repairCauseGroup');
        const submitBtn = document.querySelector('#returnForm button[type="submit"]');
        const modal = document.getElementById('returnModal');

        if (rentalIdInput) rentalIdInput.value = id;
        if (itemNameElem) itemNameElem.textContent = name;
        if (qtyElem) qtyElem.textContent = qty;
        if (memberNameElem) memberNameElem.textContent = member;
        if (dueTimeElem) dueTimeElem.textContent = due;

        currentLateMinutes = lateMinutes || 0;
        currentItemPrice = parseFloat(price) || 0;

        // รีเซ็ตสถานะสภาพอุปกรณ์เป็น 'ปกติ'
        if (defaultConditionRadio) defaultConditionRadio.checked = true;
        if (repairCauseGroup) repairCauseGroup.style.display = 'none';

        // จัดการกรณีคืนอุปกรณ์เกินกำหนดเวลา (Overdue Late Fine Calculation)
        if (isLate && lateMinutes > 0) {
            if (lateBox) lateBox.style.display = 'block';
            const hours = Math.ceil(lateMinutes / 60);
            if (lateText) lateText.textContent = `${hours} ชั่วโมง (${lateMinutes} นาที)`;

            // อัตราค่าปรับคืนช้าอัตโนมัติ: ชม. ละ 20 บาท ต่อชิ้น
            const lateFine = hours * 20 * (parseInt(qty, 10) || 1);
            if (fineInput) fineInput.value = lateFine.toFixed(2);
            if (reasonInput) reasonInput.value = `คืนอุปกรณ์ช้ากว่ากำหนด ${hours} ชม.`;
        } else {
            if (lateBox) lateBox.style.display = 'none';
            if (fineInput) fineInput.value = '0.00';
            if (reasonInput) reasonInput.value = '';
        }

        // คืนสถานะปุ่มยืนยัน
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> ยืนยันการรับคืน';
        }
        isReturnSubmitting = false;

        if (modal) {
            modal.style.display = 'flex';
        }
    };

    /**
     * ปิด Modal ตรวจรับคืนอุปกรณ์กีฬา
     */
    window.closeReturnModal = function() {
        isReturnSubmitting = false;
        const modal = document.getElementById('returnModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    /**
     * จัดการเมื่อเปลี่ยนตัวเลือกสภาพของอุปกรณ์ (ปกติ / ชำรุด / สูญหาย)
     * @param {string} status - ค่าสภาพ ('ปกติ', 'ชำรุด', 'สูญหาย')
     */
    window.handleConditionChange = function(status) {
        const repairGroup = document.getElementById('repairCauseGroup');
        const repairInput = document.getElementById('repairCauseInput');
        const fineInput = document.getElementById('fineAmountInput');
        const reasonInput = document.getElementById('fineReasonInput');

        if (status === 'ชำรุด') {
            if (repairGroup) repairGroup.style.display = 'block';
            if (repairInput) repairInput.required = true;

            // แนะนำค่าปรับซ่อม (ค่าเริ่มต้น 100 บาท หากยังไม่ได้กรอก)
            if (fineInput && parseFloat(fineInput.value) === 0) {
                fineInput.value = '100.00';
                if (reasonInput) reasonInput.value = 'อุปกรณ์ชำรุดจากการใช้งาน';
            }
        } else if (status === 'สูญหาย') {
            if (repairGroup) repairGroup.style.display = 'none';
            if (repairInput) repairInput.required = false;

            // คิดค่าปรับชดใช้เต็มจำนวนราคาของอุปกรณ์
            if (currentItemPrice > 0 && fineInput) {
                fineInput.value = currentItemPrice.toFixed(2);
                if (reasonInput) reasonInput.value = 'อุปกรณ์สูญหาย คิดค่าชดใช้เต็มจำนวน';
            }
        } else {
            // สภาพปกติ
            if (repairGroup) repairGroup.style.display = 'none';
            if (repairInput) repairInput.required = false;

            // หากไม่ได้คืนช้า ให้รีเซ็ตค่าปรับเป็น 0.00
            if (currentLateMinutes <= 0 && fineInput) {
                fineInput.value = '0.00';
                if (reasonInput) reasonInput.value = '';
            }
        }
    };

    /**
     * ดักจับและยืนยันการส่งแบบฟอร์มตรวจรับคืนอุปกรณ์กีฬาผ่าน SweetAlert2
     * ป้องกัน Double Submission และแสดงสถานะกำลังบันทึกข้อมูล
     * @param {Event} e - Submit Event Object
     */
    window.confirmReturnSubmit = function(e) {
        if (e && typeof e.preventDefault === 'function') {
            e.preventDefault();
        }
        if (isReturnSubmitting) return false;

        const itemNameElem = document.getElementById('modal_item_name');
        const qtyElem = document.getElementById('modal_qty');
        const memberNameElem = document.getElementById('modal_member_name');
        const conditionRadio = document.querySelector('input[name="condition_status"]:checked');
        const fineInput = document.getElementById('fineAmountInput');

        const itemName = itemNameElem ? itemNameElem.innerText : '';
        const qty = qtyElem ? qtyElem.innerText : '1';
        const member = memberNameElem ? memberNameElem.innerText : '';
        const condition = conditionRadio ? conditionRadio.value : 'ปกติ';
        const fine = fineInput ? (parseFloat(fineInput.value) || 0) : 0;

        let conditionBadge = '';
        if (condition === 'ปกติ') {
            conditionBadge = '<span class="badge badge-success">สภาพปกติ</span>';
        } else if (condition === 'ชำรุด') {
            conditionBadge = '<span class="badge badge-warning">ชำรุด (ส่งซ่อม)</span>';
        } else {
            conditionBadge = '<span class="badge badge-danger">สูญหาย</span>';
        }

        let html = `
            <div class="swal-custom-body">
                <div class="mb-10 text-15">ผู้เช่า: <strong>${member}</strong></div>
                <div class="mb-10 text-15">อุปกรณ์: <strong>${itemName}</strong> (${qty} ชิ้น)</div>
                <div class="mb-10 text-15">สภาพอุปกรณ์: ${conditionBadge}</div>
                ${fine > 0 ? `<div class="mb-15 text-danger font-bold text-16">ค่าปรับที่บันทึก: ${fine.toFixed(2)} ฿</div>` : ''}
                <div class="swal-consequence-info">
                    <i class="fas fa-check-circle"></i>
                    <span>ยืนยันเพื่อบันทึกการตรวจรับคืน และปรับปรุงสต็อกอุปกรณ์ในระบบ</span>
                </div>
            </div>
        `;

        if (typeof SwalConfirmAction === 'function') {
            SwalConfirmAction({
                title: 'ยืนยันการตรวจรับคืนอุปกรณ์?',
                message: html,
                type: condition === 'สูญหาย' ? 'danger' : (fine > 0 ? 'warning' : 'success'),
                confirmText: '<i class="fas fa-check-circle mr-6"></i> ยืนยันการรับคืน',
                onConfirm: function() {
                    if (isReturnSubmitting) return;
                    isReturnSubmitting = true;
                    const submitBtn = document.querySelector('#returnForm button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-6"></i> กำลังบันทึกการรับคืน...';
                    }
                    const returnForm = document.getElementById('returnForm');
                    if (returnForm) returnForm.submit();
                }
            });
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'ยืนยันการตรวจรับคืนอุปกรณ์?',
                html: html,
                icon: condition === 'สูญหาย' ? 'error' : (fine > 0 ? 'warning' : 'question'),
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-check-circle mr-6"></i> ยืนยันการรับคืน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (isReturnSubmitting) return;
                    isReturnSubmitting = true;
                    const submitBtn = document.querySelector('#returnForm button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-6"></i> กำลังบันทึกการรับคืน...';
                    }
                    const returnForm = document.getElementById('returnForm');
                    if (returnForm) returnForm.submit();
                }
            });
        } else {
            if (confirm('ยืนยันเพื่อบันทึกการตรวจรับคืนอุปกรณ์?')) {
                isReturnSubmitting = true;
                const returnForm = document.getElementById('returnForm');
                if (returnForm) returnForm.submit();
            }
        }

        return false;
    };

    // จัดการคีย์ลัด ESC และ Backdrop Click สำหรับ Return Modal
    document.addEventListener('DOMContentLoaded', function() {
        const returnModal = document.getElementById('returnModal');
        if (returnModal) {
            returnModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    window.closeReturnModal();
                }
            });
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const returnModal = document.getElementById('returnModal');
            if (returnModal && returnModal.style.display === 'flex') {
                window.closeReturnModal();
            }
        }
    });
})();
