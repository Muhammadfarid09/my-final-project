# บันทึกการพัฒนาระบบ: การปรับปรุงปุ่ม Action ท้ายขั้นตอนสรุปการจอง (Step 5) ใน booking.php

**วันที่บันทึก:** 11 ตุลาคม 2026  
**ระบบ/หน้าจอที่เกี่ยวข้อง:** [`booking.php`](file:///c:/xampp/htdocs/ts-pattani/booking.php), [`assets/css/booking-wizard.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/booking-wizard.css)  
**มาตรฐานที่ยึดถือ:** [`docs/PROJECT_HCI_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md) และ [`docs/ADDITIONAL_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/ADDITIONAL_RULES.md) (Strict Mode)

---

## 1. วัตถุประสงค์และการเปลี่ยนแปลง (What Was Done)
1. **ปรับปุ่มซ้ายเหลือบรรทัดเดียว:**
   - เปลี่ยนข้อความใน [`booking.php`](file:///c:/xampp/htdocs/ts-pattani/booking.php) จากเดิม *"กลับไปแก้ไขบริการเสริม"* เป็น **"← ย้อนกลับไปแก้ไข"**
   - กำหนดสไตล์ `white-space: nowrap;` ป้องกันข้อความตัดตกบรรทัด
2. **ล็อกความสูงทั้งสองปุ่มให้เท่ากัน (min-height: 48px):**
   - ใน [`assets/css/booking-wizard.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/booking-wizard.css) กำหนด `.step5-wrapper .wizard-nav-actions` ให้ใช้ Flexbox จัดกึ่งกลางแนวดิ่ง (`align-items: center; justify-content: space-between; gap: 16px;`)
   - ทั้งปุ่มซ้าย (`.btn-wizard-back`) และปุ่มขวา (`.btn-submit-booking-final`) ล็อกความสูงเท่ากันที่ `min-height: 48px;` และ `box-sizing: border-box;`
3. **จัดสไตล์ปุ่มขวาเป็นสีทองพรีเมียม:**
   - ใช้โทนสีทอง `var(--color-gold)` ตัวหนังสือสีดำเข้ม `var(--text-on-gold)` ขอบมน `var(--radius-md)`
   - คงเอฟเฟกต์ยกตัว `transform: translateY(-2px);` และเงาเรืองแสงสีทอง `box-shadow: 0 8px 24px rgba(255, 185, 29, 0.38);` เมื่อ Hover
4. **รองรับอุปกรณ์พกพา (Mobile Viewport <= 576px):**
   - สแต็กปุ่มทั้งสองแนวตั้ง (`flex-direction: column-reverse; gap: 12px;`) เต็มความกว้าง 100% พร้อมคงความสูง `min-height: 48px;` ตามมาตรฐาน Touch Target

---

## 2. หลักการทำงานของระบบ (How the System Works)
1. **Flexbox Alignment:**
   - `.step5-wrapper .wizard-nav-actions` จัดการการเรียงตัวของปุ่มย้อนกลับและปุ่มยืนยัน
   - ปุ่มย้อนกลับมี `flex-shrink: 0;` ปรับขนาดตามเนื้อหา "ย้อนกลับไปแก้ไข" โดยไม่ถูกบีบ
   - ปุ่มยืนยันมี `flex: 1;` ขยายตัวเต็มพื้นที่ที่เหลือของแถบอย่างสมดุลและสง่างาม
2. **Backward Compatibility:**
   - คง ID `#btnFinalSubmitBooking` และคลาส `.btn-submit-booking-final` สำหรับการทำงานของ JavaScript (`member.js`) ส่งผลให้การทำงานของ Double Submission Guard และ Loading Spinner ทำงานได้ตามปกติ 100%

---

## 3. การทดสอบและการใช้งาน (How to Use & Test)
1. เข้าใช้งานหน้าการจองผ่าน URL: `http://localhost/ts-pattani/booking.php`
2. ดำเนินการขั้นตอนการจองจนถึง Step 5 (สรุปและยืนยัน)
3. ตรวจสอบปุ่มซ้าย:
   - แสดงข้อความ `← ย้อนกลับไปแก้ไข` บรรทัดเดียวอย่างชัดเจน ไม่มีการตัดคำตกบรรทัด
   - ความสูงเท่ากับปุ่มยืนยัน (48px)
4. ตรวจสอบปุ่มขวา:
   - แสดงพื้นหลังสีทองเด่นชัด ตัวหนังสือสีดำสนิท มีเงาเรืองแสง
   - ความสูงเท่ากับปุ่มซ้าย (48px)
   - กดปุ่มเพื่อดำเนินการไปยังหน้าชำระเงินหรือ Auth Gate ได้อย่างราบรื่น

---

## 4. ปัญหาที่พบและการแก้ไข (Issues Encountered & Resolved)
1. **ปัญหาปุ่มไม่สมดุลและความสูงไม่เท่ากัน:**
   - เดิมปุ่มซ้ายมีข้อความยาว ("กลับไปแก้ไขบริการเสริม") อาจตัดคำตกบรรทัดบนบางขนาดหน้าจอ และความสูงของปุ่มซ้ายและปุ่มขวาไม่เท่ากันเนื่องจาก padding ต่างกัน
   - **การแก้ไข:** รวบคำให้กระชับ ("ย้อนกลับไปแก้ไข"), บังคับ `white-space: nowrap;`, และล็อก `min-height: 48px;` ร่วมกับ `box-sizing: border-box;` และ Flexbox vertical centering ส่งผลให้ทั้งสองปุ่มมีความสูงเท่ากันเป๊ะในทุกอุปกรณ์
