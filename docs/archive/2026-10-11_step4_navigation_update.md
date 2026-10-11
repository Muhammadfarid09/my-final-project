# บันทึกการพัฒนาระบบ: การปรับปรุงปุ่ม Navigation ขั้นตอนเลือกอุปกรณ์เสริม (Step 4) ใน booking.php

**วันที่บันทึก:** 11 ตุลาคม 2026  
**ระบบ/หน้าจอที่เกี่ยวข้อง:** [`booking.php`](file:///c:/xampp/htdocs/ts-pattani/booking.php), [`assets/js/member.js`](file:///c:/xampp/htdocs/ts-pattani/assets/js/member.js), [`assets/css/booking-wizard.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/booking-wizard.css)  
**มาตรฐานที่ยึดถือ:** [`docs/PROJECT_HCI_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md) และ [`docs/ADDITIONAL_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/ADDITIONAL_RULES.md) (Strict Mode)

---

## 1. วัตถุประสงค์และการเปลี่ยนแปลง (What Was Done)
1. **ตัดปุ่ม "ข้ามขั้นตอนนี้" ออก:**
   - ปรับแก้โครงสร้าง HTML ใน [`booking.php`](file:///c:/xampp/htdocs/ts-pattani/booking.php) (Step 4 Navigation Actions) โดยตัดปุ่ม `.btn-wizard-skip` และกล่องครอบ `<div>` ออก
   - คงเหลือปุ่ม **"ถัดไป: ตรวจสอบรายการ"** (`#btnStep4Next`) เพียงปุ่มเดียวฝั่งขวา เพื่อให้ผู้ใช้กดเดินทางต่อไปยัง Step 5 ได้ตรงไปตรงมาโดยไม่สับสน
2. **ระบบนับจำนวนรายการแบบไดนามิก (Dynamic Progressive Feedback):**
   - พัฒนาฟังก์ชัน `updateStep4NextButton()` ใน [`assets/js/member.js`](file:///c:/xampp/htdocs/ts-pattani/assets/js/member.js)
   - นับจำนวนชนิดสินค้าที่ผู้ใช้เลือก (Distinct Line Items with `qty > 0`)
   - หากเลือกสินค้า >= 1 รายการ ข้อความจะเปลี่ยนเป็น: `ถัดไป: ตรวจสอบรายการ (เลือกแล้ว X รายการ)` แบบ Real-time
   - หากไม่มีการเลือกสินค้า (0 รายการ) ข้อความจะแสดงเป็น: `ถัดไป: ตรวจสอบรายการ`
   - เชื่อมต่อการอัปเดตอัตโนมัติผ่าน `calculateGrandTotal()`, `updateStep4Summary()`, และ `initBookingWizard()`
3. **จัดสไตล์ปุ่มถัดไปเป็นสีทอง Design Tokens:**
   - ใช้คลาส `.btn-wizard-next` และ `#btnStep4Next` โดยดึงค่าจาก `var(--color-gold)` และ `var(--text-on-gold)`
   - ลบคลาสสไตล์ที่ไม่ได้ใช้งาน `.btn-wizard-skip` ออกจาก [`assets/css/booking-wizard.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/booking-wizard.css)

---

## 2. หลักการทำงานของระบบ (How the System Works)
1. **Event Flow & Real-time Synchronization:**
   - เมื่อผู้ใช้กดปุ่ม `+` หรือ `-` หรือเปลี่ยนตัวเลขในช่องจำนวนสินค้า ฟังก์ชัน `adjustProductQty()` หรือ `onProductQtyChange()` จะทำงาน
   - มีการเรียก `calculateGrandTotal()` เพื่อคำนวณยอดเงินรวมและเรียก `updateStep4NextButton()` ต่อทันที
   - ตัวนับจะสแกน input ใน `.qty-input` ทั้งหมด เพื่อนับจำนวนชนิดสินค้าที่มี `qty > 0` และอัปเดตข้อความบน `#btnStep4Next` ทันที
2. **UI Ergonomics:**
   - เลย์เอาต์ของ `.wizard-nav-actions` เป็นแบบ `justify-content: space-between`
   - ฝั่งซ้ายคือปุ่มย้อนกลับ `← ย้อนกลับไปเลือกสนาม`
   - ฝั่งขวาคือปุ่มสีทอง `ถัดไป: ตรวจสอบรายการ →`

---

## 3. การทดสอบและการใช้งาน (How to Use & Test)
1. เข้าใช้งานหน้าการจองผ่าน URL: `http://localhost/ts-pattani/booking.php`
2. เลือกวันที่ใน Step 1 -> เลือกเวลาใน Step 2 -> เลือกสนามใน Step 3 แล้วกด "ต่อไป: บริการเสริม" เข้าสู่ Step 4
3. **กรณีไม่เลือกสินค้า:** ตรวจสอบว่ามีปุ่ม "ถัดไป: ตรวจสอบรายการ" สีทองเพียงปุ่มเดียวฝั่งขวา กดแล้วสามารถข้ามไป Step 5 ได้
4. **กรณีเลือกสินค้า:**
   - กด `+` เพิ่มน้ำดื่ม -> ข้อความเปลี่ยนเป็น `ถัดไป: ตรวจสอบรายการ (เลือกแล้ว 1 รายการ)`
   - กด `+` เพิ่มไม้แบดมินตัน -> ข้อความเปลี่ยนเป็น `ถัดไป: ตรวจสอบรายการ (เลือกแล้ว 2 รายการ)`
   - กด `-` ลดจำนวนจนเหลือ 0 -> ข้อความเปลี่ยนกลับเป็น `ถัดไป: ตรวจสอบรายการ`

---

## 4. ปัญหาที่พบและการแก้ไข (Issues Encountered & Resolved)
1. **ปัญหาปุ่มซ้ำซ้อน (Cognitive Overload):**
   - เดิมมีทั้งปุ่ม "ข้ามขั้นตอนนี้" และปุ่ม "ถัดไป: ตรวจสอบรายการ" ซึ่งทำหน้าที่เดียวกัน (นำทางไป Step 5) ทำให้ผู้ใช้เกิดความลังเล
   - **การแก้ไข:** ตัดปุ่มข้ามออก แล้วใช้ปุ่มถัดไปปุ่มเดียว พร้อมใส่ตัวบอกสถานะ (เลือกแล้ว X รายการ) ทำให้ฟลูวการทำงานกระชับและเข้าใจง่ายขึ้น
