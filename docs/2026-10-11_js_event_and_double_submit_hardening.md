# บันทึกการปรับปรุงระบบความปลอดภัย JavaScript: การป้องกัน Double Submission และการจัดการ Event Listeners กลาง

**วันที่ดำเนินการ:** 11 ตุลาคม 2026  
**ผู้รับผิดชอบ:** พัฒนาระบบ Full-Stack / HCI Specialist  
**สถานะ:** เสร็จสมบูรณ์ (Completed & Tested)

---

## 1. ทำอะไรบ้าง (What Was Done)

### 1.1 ไฟล์ที่แก้ไขและปรับปรุง
1. **`assets/js/member.js`**
   - เพิ่ม `isBookingSubmitting` Guard Flag ใน `handleBookingFormSubmit(e)` ป้องกันการกดซ้ำหรือกดส่งฟอร์มรัวขณะทำรายการจองสนาม
   - เพิ่ม `isPaymentSubmitting` Guard Flag ในฟอร์มแจ้งชำระเงิน/อัปโหลดสลิป (`#paymentForm`)
   - เพิ่ม `isRedeemSubmitting` Guard Flag ในการแลกของรางวัล (`executeRedeemSubmit`)
   - รวมการปิด Modal และจัดการฉากหลัง (Backdrop Click & ESC Key) เข้าสู่จุดศูนย์กลาง สำหรับ `#rewardConfirmModal` และ `#cancelModal`

2. **`booking_history.php`**
   - ลบแท็กปิดซ้ำซ้อน `</form></div></div>` ใน DOM Structure
   - ห่อหุ้ม JavaScript ของหน้าด้วย IIFE (`(function() { ... })();`) ป้องกันการชนกันของ Global Scope Variable (`defaultMemberName`, `defaultMemberPhone`)
   - เพิ่ม Guard Flag `isCancelSubmitting` ใน `handleCancelBookingSubmit(event)` พร้อมการปิดปุ่ม (Disable) และแสดงสถานะกำลังดำเนินการ (Spinner)
   - ปรับปรุงการล้างค่าฟอร์มและปุ่มเมื่อปิด Modal ยกเลิกการจอง
   - อัปเดต Cache Busting เป็น `?v=<?php echo filemtime('assets/js/member.js'); ?>`

3. **`chat.php`**
   - เพิ่ม `isSending` Guard Flag ใน Event Listener ของฟอร์มแชต (`#chatForm`) ป้องกันการกดส่งข้อความรัวจนเกิด duplicate AJAX requests
   - เพิ่ม Error Handler (`.catch()`) คืนค่าสถานะปุ่มและช่องพิมพ์ข้อความเมื่อการเชื่อมต่อล้มเหลว

4. **`assets/js/admin.js`**
   - เพิ่ม `isPosSubmitting` Guard Flag ใน `validateCheckout(event)` เพื่อป้องกันการกดยืนยันชำระเงินที่เคาน์เตอร์ POS ซ้ำ (ทั้งแบบ QR Code และเงินสด)
   - เพิ่ม `isDeleteConfirmed` และ `isActionConfirmed` Guard Flags ใน `SwalConfirmDelete()` และ `SwalConfirmAction()` ป้องกันการกดปุ่ม Confirm ซ้ำใน SweetAlert2 Modal
   - เพิ่ม `isFormRedirecting` Guard Flag ใน `submitActionFormOrRedirect()` ป้องกันการสร้าง Form POST และ Redirect ซ้ำ
   - เพิ่มระบบจัดการฉากหลังและปุ่ม ESC กลาง (`closeActiveAdminModal`, Backdrop Click Listener, ESC Keydown Listener) รองรับ Modal ทั้งหมดในระบบ Admin เช่น `#refundModal`, `#verifyPaymentModal`, `#repairModal`, `#courtEditModal`, `#walkInCourtModal`, `#returnModal`, `#eqRepairModal`, `#newsAddModal`

5. **`admin/pos.php`**
   - เพิ่มการตรวจสอบความซ้ำซ้อนของสนามในตะกร้า (`posCart.some(...)`) ในฟังก์ชัน `addCourtToCart()` ป้องกันการกดเพิ่มสนามเดิมหรือช่วงเวลาที่ทับซ้อนกันซ้ำหลายรอบ
   - อัปเดตการโหลดไฟล์สคริปต์ด้วย `filemtime` เพื่อป้องกันเบราว์เซอร์แคชสคริปต์เก่า

6. **`admin/payments.php`**
   - ห่อหุ้มสคริปต์ด้วย IIFE และเพิ่ม `isVerifySubmitting` Guard Flag ใน `confirmApprovePayment()` และ `confirmRejectPayment()` ป้องกันการอนุมัติ/ปฏิเสธสลิปโอนเงินซ้ำ

7. **`admin/cancellations.php`**
   - เพิ่ม `isRefundSubmitting` Guard Flag ใน `confirmRefundDecision(e)` พร้อมเปลี่ยนสถานะปุ่มบันทึกเป็น Loading Spinner ป้องกันการบันทึกผลการโอนเงินคืนซ้ำ

8. **`admin/equipment_returns.php`**
   - ห่อหุ้มสคริปต์ด้วย IIFE ป้องกัน Global Namespace Collision ของตัวแปร `currentLateMinutes` และ `currentItemPrice`
   - เพิ่ม `isReturnSubmitting` Guard Flag ใน `confirmReturnSubmit(e)` ป้องกันการตัดสต็อกหรือบันทึกค่าปรับซ้ำ

9. **`admin/products.php`**
   - เพิ่ม `isRepairSubmitting` Guard Flag ใน `confirmSendToRepair(e)` ป้องกันการตัดสต็อกอุปกรณ์ส่งซ่อมซ้ำซ้อน

10. **`admin/courts.php`**
    - กำหนด ID และ `onsubmit` Guard Handler ให้กับ `#addCourtForm`, `#courtRepairForm`, และ `#courtEditForm` ป้องกันการสร้างสนามหรือบันทึกข้อมูลซ้ำ
    - ห่อหุ้มฟังก์ชันสคริปต์ด้วย IIFE

11. **`admin/includes/footer.php`**
    - อัปเดต Cache Busting สำหรับ `admin.js` ด้วย `filemtime` อัตโนมัติ

---

## 2. ระบบเป็นอย่างไร (How the System Works)

### 2.1 วงจรการป้องกัน Double Submission (Submission Guard Cycle)
1. เมื่อผู้ใช้หรือผู้ดูแลระบบกดปุ่ม Submit/Confirm ระบบจะตรวจสอบตัวแปร Flag บูลีน (`isSubmitting`) ทันที
2. หาก Flag เป็น `true` (อยู่ในระหว่างการประมวลผล) ระบบจะตัดการทำงาน (`event.preventDefault()`, `return false`) ทันที ป้องกันการส่งคำขอ HTTP POST ซ้ำ
3. หาก Flag เป็น `false` ระบบจะตั้งค่าเป็น `true` จากนั้น:
   - สั่งปิดการใช้งานปุ่ม (Disabled) เพื่อป้องกันการคลิกผ่าน Pointer/Mouse
   - ปรับข้อความของปุ่มเป็น Spinner และคำว่า "กำลังบันทึก..."
   - แสดง SweetAlert2 Loading Overlay (ในจุดที่มีการประมวลผลระยะยาว เช่น POS และ การจองสนาม)
   - ส่งฟอร์มหรือ AJAX Request ไปยังฝั่งเซิร์ฟเวอร์

### 2.2 การจัดการ Global Scope Pollution ด้วย IIFE
- โค้ดสคริปต์หน้าเว็บแบบ Inline ถูกจัดระเบียบให้อยู่ภายใต้ Immediately Invoked Function Expression (IIFE)
- ตัวแปรเฉพาะของฟังก์ชัน (เช่น `currentLateMinutes`, `isVerifySubmitting`) จะถูกจำกัดขอบเขตอยู่ภายใน Closure ไม่รั่วไหลไปยัง Global Window Object
- ฟังก์ชันที่ต้องถูกเรียกใช้งานผ่านคุณสมบัติ HTML Event Handler (`onclick`, `onchange`) จะถูกผูกกับ `window.<functionName>` อย่างชัดเจนและปลอดภัย

### 2.3 กลไกการปิด Modal แบบรวมศูนย์ (Centralized Backdrop & ESC Dismissal)
- มี Event Listener ดักจับ `click` บน `document` ตรวจหาองค์ประกอบที่มีคลาส `.modal-overlay` หากผู้ใช้คลิกโดนพื้นที่สีดำนอกตัวการ์ด ระบบจะเรียกฟังก์ชันปิด Modal เฉพาะตัวหรือซ่อนด้วย `display: none`
- มี Event Listener ดักจับการกดปุ่ม `Escape` (`Esc`) โดยมีเงื่อนไขตรวจสอบว่าหาก SweetAlert2 กำลังเปิดอยู่ จะให้ SweetAlert2 จัดการของตนเองก่อน หากไม่มี SweetAlert2 เปิดอยู่ จะสั่งปิด Modal Overlay ที่แสดงผลอยู่บนหน้าจอทันที

---

## 3. การใช้งานอย่างไร (How to Use / Test)

### 3.1 การทดสอบฝั่งสมาชิก (Member)
1. **หน้าจองสนาม (`booking.php`):**
   - กรอกข้อมูลครบแล้วกดยืนยันการจอง ปุ่มจะถูก Disable ทันทีและขึ้น Spinner ป้องกันการกดย้ำ
2. **หน้าประวัติการจอง (`booking_history.php`):**
   - กดปุ่ม "ขอยกเลิกการจอง" -> กรอกเหตุผล -> กดยืนยันยกเลิก: ปุ่มจะ Disable ทันทีและขึ้น Spinner ป้องกันการยื่นคำขอยกเลิกซ้ำ
   - ทดสอบการกดพื้นที่ว่างด้านนอก หรือกดปุ่ม ESC: กล่อง Modal จะปิดลงอย่างนุ่มนวล
3. **หน้าระบบแชต (`chat.php`):**
   - พิมพ์ข้อความแล้วกดย้ำปุ่มส่งหรือเคาะ Enter รัวๆ: สังเกตว่าข้อความถูกส่งเพียง 1 ครั้งเท่านั้น

### 3.2 การทดสอบฝั่งผู้ดูแลระบบ (Admin)
1. **ระบบ POS (`admin/pos.php`):**
   - ลองเลือกสนามแล้วกดยืนยันชำระเงินรัวๆ: ระบบจะขึ้น Loading Overlay ป้องกันการบันทึกยอดซ้ำ
   - ลองกดเปิดสนาม Walk-in เดิมในเวลาเดิมเข้าตะกร้า: ระบบจะแจ้งเตือนว่า "สนามนี้มีอยู่ในตะกร้าแล้ว"
2. **การตรวจสลิป (`admin/payments.php`):**
   - เปิดสลิปขึ้นมาตรวจแล้วกดยืนยันการชำระเงิน: ปุ่ม Action จะถูกปิดการทำงานทันทีขณะส่งฟอร์ม
3. **การคืนเงินยกเลิก (`admin/cancellations.php`):**
   - เลือกการตัดสินใจคืนเงินแล้วกดยืนยัน: ปุ่มจะเปลี่ยนเป็น "กำลังบันทึกผล..." และไม่สามารถกดย้ำได้
4. **การตรวจรับคืนอุปกรณ์ (`admin/equipment_returns.php`):**
   - กดยืนยันรับคืนอุปกรณ์: ข้อมูลจะถูกส่งเพียงครั้งเดียว สต็อกจะถูกปรับปรุงอย่างถูกต้อง

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)

1. **ปัญหา Double Submission จากการเคาะ Enter และการคลิกเมาส์อย่างรวดเร็ว:**
   - *สาเหตุ:* การปิดปุ่มด้วย `btn.disabled = true` เพียงอย่างเดียวมีจังหวะ Delay เล็กน้อยก่อน DOM จะ re-render ทำให้ Event `submit` หลุดเข้าไปในเบราว์เซอร์มากกว่า 1 รอบ
   - *วิธีแก้:* นำ Boolean Guard Flags (`isSubmitting`) มาดักที่ระดับ Logic Event ทำให้คำสั่งที่ 2 และ 3 ถูกสกัดกั้นทันที 100%
2. **SweetAlert2 Confirmed Callbacks ถูกเรียกซ้ำ:**
   - *สาเหตุ:* การกดปุ่มตกลงใน SweetAlert2 แล้วผู้ใช้คลิกซ้ำหรือกดปุ่ม Enter ค้าง ทำให้ callback ใน `.then((result) => { ... })` ถูกเรียกซ้ำก่อนที่หน้าจะ redirect
   - *วิธีแก้:* เพิ่มตัวแปร `isConfirmedOnce` ภายในสโคปของ SweetAlert2 เพื่อให้คำสั่งภายใน callback รันได้เพียงรอบเดียวเท่านั้น
3. **การชนกันของตัวแปร Global Scope ในหน้าระบบ:**
   - *สาเหตุ:* ไฟล์ที่เขียนตัวแปร `let` หรือ `const` ไว้ในแท็ก `<script>` โดยตรง เสี่ยงต่อการเกิดข้อผิดพลาด `Identifier '...' has already been declared` เมื่อมีการโหลดคอนเทนต์ซ้ำ
   - *วิธีแก้:* ย้ายตัวแปรและฟังก์ชันเข้าสู่ IIFE และผูกเฉพาะฟังก์ชันที่จำเป็นกับ `window`

