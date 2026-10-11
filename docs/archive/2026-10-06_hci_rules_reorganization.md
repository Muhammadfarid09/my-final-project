# บันทึกการปฏิบัติงานรายวัน: การปรับโครงสร้างและจัดระเบียบ PROJECT_HCI_RULES.md ตามมาตรฐาน Jakob Nielsen's 10 Heuristics
> **วันที่:** 6 ตุลาคม 2026 (2026-10-06)  
> **ไฟล์เป้าหมาย:** `docs/PROJECT_HCI_RULES.md`  
> **มาตรฐานที่ใช้:** Jakob Nielsen's 10 Usability Heuristics, T.S. Pattani Project-Specific Rules

---

## 1. ทำอะไรบ้าง (What Was Done)

### 1.1 การปรับปรุงเอกสาร
* **[docs/PROJECT_HCI_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md)**:
  - ปรับโครงสร้างเอกสารใหม่ทั้งหมดให้ยึดถือ **Nielsen's 10 Usability Heuristics** เป็นแกนหลักเดี่ยว (ไม่มี Heuristic ข้อที่ 11)
  - นำกฎเฉพาะของโปรเจกต์เดิมทั้ง 23 หัวข้อ มาจัดกลุ่มเข้าอยู่ภายใต้ 10 Heuristics ที่เกี่ยวข้องอย่างเป็นระบบ
  - เพิ่มกฎเฉพาะ **Avoid Redundant User Input (ลดการกรอกข้อมูลซ้ำซ้อน)** ไว้ภายใต้ **2.6 Recognition Rather Than Recall (ข้อย่อย 2.6.2)** พร้อมระบุหลักการ กฎ ตัวอย่างในระบบ T.S. Pattani และข้อยกเว้นอย่างสมบูรณ์
  - จัดระเบียบ **Booking Flow เป็น 4 ระยะ** (Phase 1: จองคอร์ท, Phase 2: บริการเสริม One-Stop, Phase 3: ตรวจสอบและยืนยัน, Phase 4: การชำระเงินและการอนุมัติ) ภายใต้ Heuristic ข้อ 2.6.3
  - แยกความหมายและนิยามอย่างชัดเจนระหว่าง **Booking Lock** (กลไกป้องกันการจองซ้ำระหว่างชำระเงิน 15 นาที) กับ **Payment / Verification Status** (สถานะการตรวจสอบสลิปของแอดมิน) ไว้ใน Heuristic ข้อ 2.1.4
  - รวบรวมแนวทางการทำงานของนักพัฒนา (6-Step UI Process, ปรัชญาการออกแบบ, Checklist ตรวจสอบ) ไว้ในหมวดที่ 3 เพื่อให้อ่านเข้าใจง่ายและเป็นขั้นตอน
* **ความปลอดภัยของระบบ (File Safety):** ไม่มีการแตะต้องหรือแก้ไขไฟล์ Source Code (PHP, HTML, CSS, JavaScript, SQL) แม้แต่ไฟล์เดียว

---

## 2. ระบบเป็นอย่างไร (How the System Works)

### 2.1 โครงสร้างใหม่ของเอกสาร
เอกสารใหม่ได้รับการแบ่งลำดับชั้น (Document Architecture) ออกเป็น 3 ส่วนหลัก:
1. **หมวดที่ 1: วัตถุประสงค์และขอบเขต (Purpose & Scope):** ประกาศเจตนารมณ์และขอบเขตการบังคับใช้กฎ HCI ทั่วทั้งระบบ
2. **หมวดที่ 2: Nielsen's 10 Usability Heuristics & Project-Specific Rules:**
   - `2.1 Visibility of System Status` (Feedback ทันที, 5 States, Real Database State, Booking Lock vs Payment Status)
   - `2.2 Match Between the System and the Real World` (ศัพท์สนามแบดมินตัน, การคำนวณวัน Normal/Peak/Off-peak)
   - `2.3 User Control and Freedom` (ปุ่มย้อนกลับ Multi-step, ปุ่มข้าม Add-ons, การปิด Modal)
   - `2.4 Consistency and Standards` (แยก Role Member vs Admin, Design Tokens สี, Components, Responsive, WCAG)
   - `2.5 Error Prevention` (Form Validation, SweetAlert2 Confirmation รายการเสี่ยง, Concurrency Lock)
   - `2.6 Recognition Rather Than Recall` (Cognitive Load, **Avoid Redundant User Input**, 4-Phase Booking Flow, คำนวณราคาอัตโนมัติ, สรุปก่อนจ่าย)
   - `2.7 Flexibility and Efficiency of Use` (Admin Dashboard, POS Quick Walk-in, Member ปุ่มด่วน [วันนี้]/[พรุ่งนี้])
   - `2.8 Aesthetic and Minimalist Design` (Visual Hierarchy, ถอด Matrix ใหญ่, Zero Inline Styles)
   - `2.9 Help Users Recognize, Diagnose, and Recover from Errors` (Error 3 องค์ประกอบ, สลิปผิด, คอร์ทหลุด Lock)
   - `2.10 Help and Documentation` (นโยบายยกเลิก/คืนเงิน, ระบบแต้ม Tiers, PDPA Helper Text)
3. **หมวดที่ 3: มาตรฐานการปฏิบัติงานของ AI Developer:**
   - ขั้นตอน 6 สเต็ป: Understand → Analyze → Propose → Confirm → Modify → Verify
   - ปรัชญา HCI > Decoration และ Don't Redesign Blindly
   - รายการตรวจสอบก่อนส่งมอบงาน (Verification Checklist 11 ข้อ)

---

## 3. การใช้งานอย่างไร (How to Use / Test)

1. **การนำเอกสารไปอ้างอิงในการพัฒนา:**
   - นักพัฒนาและ AI Developer สามารถเปิดอ่านและปฏิบัติตามกฎใน [docs/PROJECT_HCI_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md) เป็น Checklist ทุกครั้งที่มีการแก้ไข UI/UX
   - ใช้ตรวจสอบโครงสร้าง Multi-step Booking ใน `booking.php` ว่าสอดคล้องกับ Phase 1 - 4 หรือไม่
   - ใช้ตรวจสอบการส่งต่อข้อมูลข้ามหน้า เพื่อป้องกันการถามข้อมูลซ้ำซ้อนตามกฎ `Avoid Redundant User Input`
   - ใช้ตรวจสอบว่า Action ที่มีความเสี่ยงใน Admin มี SweetAlert2 Confirmation ที่แสดง Consequence ชัดเจนตาม Heuristic 5 หรือไม่

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)

1. **ปัญหาโครงสร้างเดิมกระจัดกระจาย:** เดิมเอกสารแตกออกเป็น 23 หัวข้อระดับบนสุด โดยที่ Section 2 มีการสรุปสั้นๆ และมีหัวข้อย่อยด้านล่างกล่าวซ้ำไปมา → **แก้ไขโดย:** ยึด Nielsen's 10 Heuristics เป็นหัวข้อหลักเดียวในหมวดที่ 2 และนำกฎเฉพาะทั้งหมดของโปรเจกต์เข้าไปเป็นหัวข้อย่อย ทำให้เอกสารมีความเป็นวิชาการ เป็นระบบ และค้นหาง่าย
2. **การป้องกันข้อผิดพลาดเชิงนิยาม (No 11th Heuristic):** วางกฎ "Avoid Redundant User Input" ไว้ภายใต้ Heuristic ข้อที่ 6 เพื่อไม่ให้เกิดการอ้างอิงผิดว่า Nielsen มี Heuristic 11 ข้อ
3. **การรักษาเนื้อหาเดิม:** ไม่มีการตัดทอนกฎเฉพาะที่มีประโยชน์เดิมทิ้ง แต่ใช้วิธีจัดระเบียบใหม่ (Consolidate & Restructure) 100%
