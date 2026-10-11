# บันทึกการพัฒนาระบบ: การปรับปรุงหน้าการจองสนาม (booking.php) ธีม Premium Black & Gold

**วันที่บันทึก:** 11 ตุลาคม 2026  
**ระบบ/หน้าจอที่เกี่ยวข้อง:** [`booking.php`](file:///c:/xampp/htdocs/ts-pattani/booking.php), [`assets/css/booking-wizard.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/booking-wizard.css), [`actions/booking_db.php`](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php)  
**มาตรฐานที่ยึดถือ:** [`docs/PROJECT_HCI_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md) และ [`docs/ADDITIONAL_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/ADDITIONAL_RULES.md) (Strict Mode)

---

## 1. วัตถุประสงค์และการเปลี่ยนแปลง (What Was Done)
1. **ยกระดับ UI สู่ธีม "Premium Black & Gold":**
   - Refactor ไฟล์ [`assets/css/booking-wizard.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/booking-wizard.css) ขจัดสีฟ้าและสีเทาสว่างเดิมทั้งหมด 52 จุดเป็นศูนย์ (0)
   - ขจัดคำสั่ง `!important` เดิมออกทั้งหมดจนเหลือ 0 รายการ เพื่อสอดคล้องกับกฎระเบียบ Strict CSS
   - ผสานสไตล์เข้ากับ Design Tokens กลางใน `global.css` (`--bg-primary: #0D0D0D`, `--bg-surface: #191919`, `--bg-elevated: #292929`, `--color-gold: #FFB91D`, `--text-primary: #F0F0F0`, `--border-color: #383838`)
2. **ปรับปรุง Court Booking Grid (ขั้นตอนที่ 3):**
   - โครงสร้างตารางและหัวข้อแต่ละช่วงเวลาใช้พื้นหลัง `var(--bg-surface)` และ `var(--bg-elevated)` ขอบมน 12px เส้นแบ่ง `var(--border-color)`
   - **สถานะคอร์ทว่าง (Available):** พื้นผิว `var(--bg-elevated)` ขอบและไอคอนใช้สีเขียว `var(--status-available: #22C55E)` พร้อม Badge สีเขียวโปร่งแสง
   - **สถานะคอร์ทติดจอง (Booked):** พื้นหลังหม่น `var(--bg-primary)` ขอบมืด `#2d2d2d` ตัวหนังสือและไอคอนสีเทาเข้ม มี Badge สีแดง `var(--status-booked: #EF4444)` และปิดการคลิก (`cursor: not-allowed`)
   - **สถานะคอร์ทรอตรวจสอบ (Pending):** พื้นหลัง `var(--bg-primary)` ขอบส้ม มี Badge ส้มแจ้งสถานะรอตรวจสอบ
   - **สถานะคอร์ทกำลังเลือก (Selected):** พื้นหลังเรืองแสงสีทอง `rgba(255, 185, 29, 0.12)` เส้นขอบสีทองหนา 2px `var(--color-gold)` เงาสีทอง ไอคอนและหัวข้อสีทอง พร้อม Badge สีทองเต็มตัวหนังสือสีดำ `var(--text-on-gold)`
3. **ปรับปรุง Booking Summary Card (ขั้นตอนที่ 5 & Sticky Bar):**
   - กล่องใบสรุปรายการจองสนาม (Invoice Card) พื้นหลัง `var(--bg-surface)` ขอบมน 16px เงาสีเข้ม
   - ตารางแจกแจงรายการค่าใช้จ่าย (Breakdown Table) สไตล์พรีเมียม หัวตาราง `var(--bg-elevated)`
   - ยอดรวมราคาชำระสุทธิ (Grand Total Price): แสดงตัวเลขขนาดใหญ่ 30px เด่นชัดด้วยสีทอง `var(--color-gold)` พร้อมเงาเรืองแสงสีทองอ่อน
   - ปุ่มยืนยันการจอง (Submit CTA): พื้นหลังสีทอง `var(--color-gold)` ข้อความสีดำเข้ม `var(--text-on-gold)` ปราศจากความขัดแย้งด้าน Contrast (WCAG AAA) พร้อมเอฟเฟกต์ยกตัวเมื่อ Hover
4. **Backend & Concurrency Security Audit:**
   - ตรวจสอบไฟล์ [`actions/booking_db.php`](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php) ยืนยันการป้องกันการจองซ้อนด้วย Database Transaction (`$conn->beginTransaction()`), Pessimistic Row Lock (`SELECT ... FOR UPDATE`), Overlap Conflict Query, และระบบ Auto-cancel ปลดล็อกสนาม 15 นาที

---

## 2. หลักการทำงานของระบบ (How the System Works)
1. **Wizard Flow & Progressive Disclosure:**
   - ผู้ใช้ดำเนินขั้นตอนการจอง 5 ขั้นตอน (วันที่ -> เวลาเริ่ม -> สนามและชั่วโมง -> บริการเสริม -> ตรวจสอบและยืนยัน)
   - Stepper แสดงความคืบหน้าด้วยสีทอง (กำลังทำ) และสีเขียว (ผ่านแล้ว)
2. **Sticky Bottom Summary Bar:**
   - ขณะเลือกสนามใน Step 3 แถบด้านล่างจะตรึงอยู่กับที่เสมอ (Fixed Bottom) พร้อมแสดงสรุปจำนวนชั่วโมง สนามที่เลือก และยอดเงินรวมสีทองแบบ Real-time
3. **Guest Auth Gate:**
   - หากผู้ใช้ยังไม่ได้เข้าสู่ระบบ ระบบจะอนุญาตให้เลือกสนามและบริการเสริมได้ก่อน เมื่อถึง Step 5 จะมี Auth Gate Card โทน Black & Gold นำทางไปสู่หน้า Login หรือ Register โดยเก็บสเตทการจองไว้ใน Session ป้องกันข้อมูลสูญหาย

---

## 3. การทดสอบและการใช้งาน (How to Use & Test)
1. เข้าใช้งานหน้าการจองผ่าน URL: `http://localhost/ts-pattani/booking.php`
2. **Step 1 (วันที่):** เลือกวันจากปฏิทิน ตรวจสอบการเปลี่ยนสีของเซลล์วันที่ที่เลือกเป็นสีทอง
3. **Step 2 (เวลาเริ่ม):** คลิกเลือกเวลาเปิดทำการ (เช่น 17:00 น.) เพื่อเข้าสู่หน้าเลือกสนาม
4. **Step 3 (คอร์ท & ชั่วโมง):**
   - สังเกตคอร์ทว่างเป็นกรอบสีเขียว คอร์ทติดจองเป็นสีเทาหม่น Badge แดง
   - คลิกเลือกสนามที่ว่าง ตรวจสอบการเปลี่ยนเป็นกรอบสีทองหนา และปุ่ม Sticky Bar ปรากฏยอดเงินสีทอง
5. **Step 4 (บริการเสริม):** เพิ่มน้ำดื่มหรืออุปกรณ์เช่า ตรวจสอบปุ่มปรับจำนวน +/-
6. **Step 5 (สรุปและยืนยัน):**
   - ตรวจสอบยอดชำระสุทธิขนาดใหญ่ 30px สีทองเรืองแสง
   - ปุ่มยืนยันการจองสีทองตัวหนังสือดำเด่นชัด

---

## 4. ปัญหาที่พบและการแก้ไข (Issues Encountered & Resolved)
1. **Legacy Blue & Hardcoded Colors:**
   - เดิมมีรหัสสีฟ้า 52 จุดใน `booking-wizard.css` (เช่น `#1e3c72`, `#2563eb`, `#0284c7`) และสีพื้นสว่าง
   - **การแก้ไข:** ทำการ Refactor ทั้งหมดเข้าสู่ Design Tokens (`var(--bg-surface)`, `var(--bg-elevated)`, `var(--color-gold)`, `var(--text-primary)`, `var(--border-color)`)
2. **การขจัดคำสั่ง `!important`:**
   - เดิมมี `!important` ในคลาส `.calendar-date-cell.selected`
   - **การแก้ไข:** จัดลำดับความเฉพาะเจาะจงของ CSS Selector (Specificity) ใหม่ ทำให้ไม่ต้องพึ่งพา `!important` อีกต่อไป
