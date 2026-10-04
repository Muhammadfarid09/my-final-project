# บันทึกการพัฒนาระบบ: การยกเครื่องหน้าจองสนามสู่ 5-Step Wizard Pattern และ Zero Inline Style (booking.php)
**วันที่:** 5 ตุลาคม 2026 (2026-10-05)  
**ระบบ/โมดูล:** Frontend & Member Booking Engine (booking.php, assets/css/style.css, assets/js/member.js, actions/booking_db.php, actions/get_court_matrix.php)  
**มาตรฐานการออกแบบ:** HCI Usability Heuristics, Progressive Disclosure, Zero Inline Styles, และ PDPA Privacy Compliance

---

## 1. วัตถุประสงค์และปัญหาเดิม (Background & Problem Statement)
1. **Information Overload & Cognitive Overload:**
   - หน้าเดิมแสดงตารางขนาดใหญ่ (Court Availability Matrix) สูงถึง 13 ช่วงเวลา x 10 สนาม (130 ช่อง) ร่วมกับฟอร์มกรอกข้อมูลและรายการสินค้าในหน้าจอเดียว ทำให้ผู้ใช้เกิดความสับสน ไม่รู้ว่าจะต้องเริ่มต้นจากส่วนใด
2. **ละเมิดหลัก Progressive Disclosure & ข้อมูลซ้ำซ้อน:**
   - การ์ดสนามทุกใบแสดงเงื่อนไขราคายาว 3 บรรทัด (จ.-พ.-ศ. 180 ฿, ส.-อา. 200 ฿, อ.-พฤ. 150 ฿) สร้างขยะสายตา แทนที่จะแสดงราคาจริงเฉพาะของวันที่เลือก
3. **ขาดความเป็นส่วนตัวตามหลัก PDPA:**
   - สถานะการจองเดิมไม่มีการรองรับชื่อเล่นของผู้จอง อาจทำให้ชื่อ-นามสกุลจริงถูกเปิดเผยแก่ผู้ใช้งานคนอื่น
4. **ละเมิด Clean Code (Inline Styles):**
   - มีการใช้ `style="..."` และแท็ก `<style>` แทรกใน `booking.php` จำนวนมาก ขัดต่อ Separation of Concerns

---

## 2. รายละเอียดการดำเนินงานและการเปลี่ยนแปลง (What Was Done)

### 2.1 ฐานข้อมูล (Database Migration)
- เพิ่มคอลัมน์ `booking_nickname VARCHAR(50) NULL DEFAULT NULL` ในตาราง `Booking` ต่อจาก `member_id` เพื่อเก็บชื่อเล่น/ชื่อก๊วนผู้จองสำหรับแสดงผลต่อสาธารณะ

### 2.2 ปรับปรุง Backend Action & API
1. **`actions/get_court_matrix.php`:**
   - ปรับ Query ดึงสนามทั้งหมดรวมถึงสนามที่ `court_status = 'ปิดปรับปรุง'` เพื่อให้แสดงการ์ดสนามครบ 10 สนามพร้อมสถานะสีเทา
   - เชื่อมต่อ `Booking.booking_nickname` (และ Fallback เป็นชื่อแรกของ Member กรณีไม่มีชื่อเล่น) เพื่อส่งกลับใน JSON ในชื่อ `booked_by` โดยไม่เปิดเผยชื่อจริง-นามสกุลจริงตาม PDPA
   - ขยาย Time Slots ให้ครอบคลุมจนถึง 23:00 น. เพื่อรองรับเวลาปิดทำการของสนาม
2. **`actions/booking_db.php`:**
   - รับค่า `booking_nickname` จากฟอร์ม ตัดช่องว่าง และจำกัดความยาวไม่เกิน 30 ตัวอักษร (Fallback เป็น `$_SESSION['member_name']` กรณีเว้นว่าง)
   - จัดรูปแบบ `start_time` และ `end_time` เป็น `H:i:s` เพื่อความถูกต้องในการเปรียบเทียบใน MySQL
   - รองรับการจองหลายชั่วโมงต่อเนื่อง (Consecutive Slot) โดยคำนวณ `hours = (end - start) / 3600` และคิดราคาตามเรทจริงของวัน (Server-side validation)
   - บันทึก `booking_nickname` ลงในตาราง `Booking` ภายใต้ Database Transaction

### 2.3 ออกแบบ CSS ใหม่ทั้งหมดใน `assets/css/style.css` (Zero Inline Styles)
- ถอด inline styles ทั้งหมดใน `booking.php` ออก 100%
- เพิ่มคลาสควบคุมระบบ 5-Step Wizard:
  * `.wizard-stepper-box`, `.wizard-stepper-nav`, `.wizard-step-node`, `.wizard-stepper-fill`
  * `.wizard-panel`, `.wizard-card`, `.btn-wizard-back`, `.btn-wizard-next`, `.btn-wizard-skip`
  * `.calendar-picker-container`, `.calendar-box`, `.calendar-grid`, `.calendar-date-cell`, `.btn-quick-date`, `.date-price-banner`
  * `.time-grid-container`, `.time-slot-card`
  * `.nickname-box`, `.nickname-control`, `.slot-hour-block`, `.court-cards-grid`, `.court-card-v2`
  * 5 สถานะสีของการ์ดสนาม: `.status-available` (เขียว), `.status-selected` (เหลือง), `.status-pending` (ส้ม), `.status-booked` (แดง), `.status-maintenance` (เทา)
  * `.sticky-summary-bar`, `.sticky-metrics-group`, `.btn-sticky-next`
  * `.addon-cards-grid`, `.addon-item-card`, `.addon-stepper-control`, `.addon-stepper-input`
  * `.invoice-card`, `.invoice-breakdown-table`, `.invoice-total-strip`, `.btn-submit-booking-final`

### 2.4 ปรับโครงสร้าง `booking.php` สู่ 5-Step Wizard
- ลบ `<style>` แท็กเดิมทั้งหมด
- ลบตาราง Availability Matrix ก้อนใหญ่ออกจากหน้าแรก
- แบ่งหน้าเป็น 5 Panels ตามลำดับการคิดและการตัดสินใจของผู้ใช้:
  * **Step 1:** ปฏิทิน Visual Calendar (1–31) พร้อมปุ่ม `[วันนี้]` `[พรุ่งนี้]` และป้ายบอกราคา 1 บรรทัด
  * **Step 2:** บล็อกปุ่มช่วงเวลาเปิดทำการ (09:00 - 22:00 น.) ปิดเวลาที่เลยไปแล้วอัตโนมัติ
  * **Step 3:** เลือกคอร์ท & ชั่วโมงต่อเนื่อง พร้อมช่องกรอกชื่อเล่น และตรึง Sticky Bottom Summary Bar
  * **Step 4:** บริการเสริมและอุปกรณ์เช่า พร้อมตัวปรับบวก-ลบ Stepper และปุ่ม `[ข้ามขั้นตอนนี้]`
  * **Step 5:** Itemized Invoice Breakdown Card ตรวจสอบค่าใช้จ่ายสุทธิและข้อความเตือน 15 นาที

### 2.5 พัฒนา Wizard Controller ใน `assets/js/member.js`
- บริหารจัดการ State ด้วย `wizardState` (วันที่, ปฏิทิน, เวลาเริ่ม, คอร์ทที่เลือก, สินค้า, ราคารวม)
- มีฟังก์ชันตรวจสอบการเปลี่ยนสเต็ป `goToStep(step)` และการกดกระโดดกลับ `jumpToWizardStep(step)`
- ควบคุมการเลือกสนามแบบ Consecutive Slot: ป้องกันการเลือกสนามคนละสนามในชั่วโมงต่อเนื่อง และป้องกันการเลือกชั่วโมงที่กระโดดข้าม
- คำนวณราคารวมแบบ Real-time และอัปเดต Sticky Bar ทันที

---

## 3. รายการไฟล์ที่เกี่ยวข้อง (Files Modified)
1. `booking.php` (Refactored to 5-Step Wizard, Zero Inline Styles)
2. `assets/css/style.css` (Added complete Wizard, Calendar, Court 5 Statuses, Sticky Bar CSS)
3. `assets/js/member.js` (Implemented Wizard State Machine, Calendar logic, Consecutive Slot selection)
4. `actions/get_court_matrix.php` (Added maintenance court status & PDPA-safe nickname)
5. `actions/booking_db.php` (Added `booking_nickname` support & time standardization)
6. `docs/2026-10-05_booking_wizard_refactor.md` (Daily Work Documentation)

---

## 4. ผลการทดสอบ (Verification & Test Results)
- ตรวจสอบไวยากรณ์ PHP (`php -l`): ผ่านทุกไฟล์ 100% ไม่มี Syntax Error
- ตรวจสอบไวยากรณ์ JavaScript (`node -c assets/js/member.js`): ผ่าน 100%
- ตรวจสอบ Inline Style (`Select-String style=`): ไม่พบการใช้ inline style แม้แต่จุดเดียวใน `booking.php`
- จำลองการดึง Matrix และการบันทึกข้อมูลการจองผ่านสคริปต์ทดสอบ:
  * วันจันทร์คิดเรท 180 ฿/ชม. ถูกต้อง
  * การจองต่อเนื่อง 2 ชั่วโมง (14:00 - 16:00) ถูกบันทึกเป็น 1 รายการสมบูรณ์
  * ชื่อเล่น `ก๊วนตีแบดวันหยุด` ถูกนำไปแสดงบนการ์ดสนามในสถานะ `● รอตรวจสอบ (ก๊วนตีแบดวันหยุด)` โดยไม่มีการเปิดเผยชื่อจริงของสมาชิก
