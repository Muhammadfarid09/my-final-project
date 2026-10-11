# บันทึกการพัฒนาระบบ: การแก้ไข CSS Hover/Select ในปฏิทินเลือกวันที่ของ booking.php

**วันที่บันทึก:** 11 ตุลาคม 2026  
**ระบบ/หน้าจอที่เกี่ยวข้อง:** [`booking.php`](file:///c:/xampp/htdocs/ts-pattani/booking.php), [`assets/css/booking-wizard.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/booking-wizard.css)  
**มาตรฐานที่ยึดถือ:** [`docs/PROJECT_HCI_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md) และ [`docs/ADDITIONAL_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/ADDITIONAL_RULES.md) (Strict Mode)

---

## 1. วัตถุประสงค์และการเปลี่ยนแปลง (What Was Done)
1. **ลบสีเทาค้างจากสถานะ :focus ออก:**
   - กำหนด `.calendar-date-cell:focus, .calendar-date-cell:focus-visible { outline: none; }`
   - กำหนด `.calendar-date-cell:focus:not(.selected) { background: var(--bg-surface); }` เพื่อรับประกันว่าจะไม่มีสีเทาหรือเส้นขอบโฟกัสตกค้างเมื่อคลิกเลือกวันที่
2. **ปรับแต่งสถานะเมื่อชี้เมาส์ (:hover) ให้ขึ้นไฮไลต์สีทองทันที:**
   - กำหนด `.calendar-date-cell:hover:not(.disabled):not(.selected)` ให้ใช้พื้นหลังทองโปร่งแสง `rgba(255, 185, 29, 0.18)` เส้นขอบสีทอง `var(--color-gold)` ข้อความสีทอง และเงาสีทองนุ่มเบา `box-shadow: 0 2px 8px rgba(255, 185, 29, 0.2)`
   - ยกเลิกการใช้พื้นหลังสีเทาเข้ม (`var(--bg-elevated)`) เพื่อให้เห็นเอฟเฟกต์สีทองสว่างตามตำแหน่งเมาส์ทันที
3. **บังคับสถานะวันที่เลือกแล้ว (.selected) ให้คงสีทองถาวร:**
   - กำหนด `.calendar-date-cell.selected, .calendar-date-cell.selected:hover, .calendar-date-cell.selected:focus` ให้มีพื้นหลัง `var(--color-gold)` ข้อความสีดำ `var(--text-on-gold)` เส้นขอบสีทองหนา และเงาเรืองแสงสีทอง `box-shadow: 0 4px 12px rgba(255, 185, 29, 0.35)`
   - ป้องกันไม่ให้วันที่เลือกแล้วกลายเป็นสีเทาเมื่อเลื่อนเมาส์มาชี้ซ้ำ
4. **ความลื่นไหลของอนิเมชัน (Smooth Transition):**
   - กำหนด `transition: all 0.15s ease;` บน `.calendar-date-cell` และ `.btn-quick-date` ทำให้การสลับสีเมื่อเมาส์เคลื่อนผ่านและคลิกเลือกนุ่มนวลเป็นธรรมชาติ

---

## 2. หลักการทำงานของระบบ (How the System Works)
1. **CSS Specificity Hierarchy:**
   - เซลล์ปกติ: ใช้พื้นหลัง `var(--bg-surface)`
   - เซลล์ที่เมาส์ชี้ผ่าน: `:hover:not(.disabled):not(.selected)` จะแสดงสีทองอ่อน
   - เซลล์ที่ถูกเลือก: `.selected`, `.selected:hover`, `.selected:focus` มีความเฉพาะเจาะจงสูงสุดและจะคงสถานะสีทองพื้นหลัง/ตัวหนังสือสีดำเสมอ ไม่ถูกโอเวอร์ไรด์โดยกฎ hover ทั่วไป
2. **ปุ่มทางลัดเลือกวันด่วน (.btn-quick-date):**
   - ปุ่ม "วันนี้" และ "พรุ่งนี้" ได้รับการปรับปรุงสไตล์ `:hover:not(.active)` เป็นสีทองโปร่งแสง และ `.active:hover` จะคงสีทองถาวรเช่นเดียวกัน

---

## 3. การทดสอบและการใช้งาน (How to Use & Test)
1. เข้าใช้งานหน้าการจองผ่าน URL: `http://localhost/ts-pattani/booking.php`
2. **ทดสอบการเลื่อนเมาส์ (Hover):**
   - เลื่อนเคอร์เซอร์เมาส์ผ่านเซลล์วันที่ 1-31 ในปฏิทิน
   - สังเกตไฮไลต์สีทองตามตำแหน่งเมาส์ทันทีในเวลา 0.15 วินาที
3. **ทดสอบการคลิกเลือก (Select):**
   - คลิกเลือกวันที่ต้องการ (เช่น วันที่ปัจจุบันหรือวันพรุ่งนี้)
   - เซลล์วันที่ถูกเลือกจะเปลี่ยนเป็นพื้นหลังสีทองตัวหนังสือสีดำ
4. **ทดสอบการชี้เมาส์ซ้ำบนวันที่เลือก:**
   - เลื่อนเมาส์กลับมาชี้ที่เซลล์วันที่เลือกไว้อีกครั้ง
   - ยืนยันว่าเซลล์ยังคงเป็นพื้นหลังสีทองตัวหนังสือสีดำถาวร ไม่กลายเป็นสีเทา
5. **ทดสอบการคลิกออก (Focus Removal):**
   - คลิกที่บริเวณอื่นนอกปฏิทิน ยืนยันว่าไม่มีเงาสีเทาหรือขอบเทาหลงเหลือค้างอยู่

---

## 4. ปัญหาที่พบและการแก้ไข (Issues Encountered & Resolved)
1. **ปัญหาสีเทาติดค้างจากการใช้ `var(--bg-elevated)` บน :hover:**
   - *สาเหตุ:* เดิม `.calendar-date-cell:hover:not(.disabled)` กำหนดพื้นหลังเป็น `var(--bg-elevated)` (#292929) ซึ่งเป็นสีเทา ทำให้เมื่อชี้เมาส์ดูทึบหม่น และเมื่อชี้ซ้ำบนเซลล์ `.selected` ตัว hover ก็จะเปลี่ยนสีทองกลับเป็นสีเทา
   - *การแก้ไข:* แยกกฎ `:hover` ของเซลล์ที่ยังไม่เลือก (`:not(.selected)`) ให้ใช้ `rgba(255, 185, 29, 0.18)` สีทอง และล็อกสถานะ `.selected:hover` ให้คงสีทอง `var(--color-gold)` ถาวร 100%
