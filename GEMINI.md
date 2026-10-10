# Project Development Guidelines & Reporting Standard (TS-Pattani)

### 📌 กฎการตั้งชื่อไฟล์และการจัดเก็บเอกสาร (File Naming & Daily Documentation Rule)
* **แบ่งเอกสารบันทึกการทำงานเป็นรายวัน:** เอกสารรายงาน สรุปผล หรือบันทึกการเปลี่ยนแปลง (Daily Logs / Reports) ให้แบ่งเป็นรายวัน เพื่อให้อ่านง่ายและค้นหาง่าย
* **รูปแบบชื่อไฟล์:** ต้องนำหน้าด้วย **วันที่ (YYYY-MM-DD)** ก่อนชื่อไฟล์เสมอ เช่น:
  - `docs/2026-09-25_system_audit.md`
  - `docs/2026-09-25_bug_fixes.md`
  - `docs/2026-09-26_equipment_return_system.md`
* ทุกครั้งที่ทำงานเสร็จในแต่ละวัน ให้บันทึกรายละเอียดสรุปลงในไฟล์ที่มีวันที่นำหน้าในโฟลเดอร์ `docs/` ด้วยเสมอ

---

### 📋 โครงสร้างการรายงานผลการทำงาน 4 ส่วนบังคับ (Mandatory 4-Section Report)
ทุกครั้งที่ดำเนินการทำงาน แก้ไขโค้ด พัฒนาระบบ หรือทำขั้นตอนใดๆ เสร็จสิ้น จะต้องรายงานผลสรุปให้ผู้ใช้ทราบอย่างละเอียดตามโครงสร้าง 4 ส่วนนี้เสมอ:

#### 1. ทำอะไรบ้าง (What Was Done)
* สรุปรายการไฟล์ที่สร้าง เพิ่มเติม หรือแก้ไข (พร้อมระบุลิงก์ไฟล์บันทึกประจำวัน `docs/YYYY-MM-DD_...`)
* รายละเอียดการเปลี่ยนแปลงโค้ด, ฐานข้อมูล (DB Schema), หรือไฟล์ Configuration

#### 2. ระบบเป็นอย่างไร (How the System Works)
* อธิบายหลักการทำงาน (Mechanism / Logic Flow) ของระบบที่ทำ
* ความสัมพันธ์กับส่วนอื่นๆ ในระบบ (เช่น ตารางฐานข้อมูล, API/Actions, Session, สิทธิ์ผู้ใช้ Member/Admin)
* Diagram หรือโฟลว์การทำงาน (ถ้ามี)

#### 3. การใช้งานอย่างไร (How to Use / Test)
* ขั้นตอนการทดสอบและการใช้งานทีละสเต็ป (Step-by-step instructions)
* การเข้าถึงผ่าน URL หรือเมนูทั้งฝั่งผู้ใช้ (Member) และฝั่งผู้ดูแลระบบ (Admin)
* ข้อมูลตัวอย่างสำหรับทดสอบ (Test inputs / Credentials)
* ผลลัพธ์ที่คาดหวังหลังจากทำรายการ (Expected outputs / UI behavior)

#### 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)
* สาเหตุของปัญหาเดิมหรือ Bug ที่ตรวจพบ (Root Cause)
* วิธีการที่ใช้แก้ปัญหา (Solution / Workaround)
* ข้อจำกัด หรือข้อควรระวัง (Edge Cases & Notes)

---

### 🎨 มาตรฐานการออกแบบ UI/UX ตามหลัก HCI (HCI & UI/UX Guidelines)
* ทุกการแก้ไขและออกแบบ UI/UX ทั้งฝั่ง Member และ Admin ต้องยึดถือกฎใน [docs/PROJECT_HCI_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md) อย่างเคร่งครัด
* ก่อนแก้ไข UI/UX ต้องปฏิบัติตามลำดับ 6 ขั้นตอนเสมอ: Understand → Analyze → Propose → Confirm → Modify → Verify

---

### 🛡️ กฎเหล็กและข้อจำกัดการปฏิบัติงาน (System Rules & Strict Operational Constraints)
* ต้องปฏิบัติตามกฎเหล็กทั้ง 6 ข้อใน [docs/ADDITIONAL_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/ADDITIONAL_RULES.md) อย่างเคร่งครัดเด็ดขาดควบคู่กับ [docs/PROJECT_HCI_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md) โดยไม่ยกเลิกกฎเดิม
  1. **DRY Principle & Code Reuse:** ค้นหาก่อนสร้างใหม่ นำฟังก์ชัน/คลาสเดิมมาใช้ซ้ำ ใช้ฟังก์ชันกลางสำหรับ SQL และ Constants
  2. **Strict Separation of Concerns (CSS):** ห้ามใช้ Inline Styles (ยกเว้น dynamic values) และห้ามแทรก `<style>` กลางไฟล์ PHP แยกสโคปไฟล์ CSS ตามหน้าที่อย่างเคร่งครัด และห้ามใช้ `!important` เพื่อแก้ปัญหาความทับซ้อน
  3. **Safety First & Blast Radius Control:** ห้ามเปลี่ยน/ลบคลาสเดิม ห้ามเปลี่ยน HTML Nesting โดยไม่สั่ง แก้ไขเฉพาะจุดที่ได้รับมอบหมาย รักษาความเข้ากันได้ย้อนหลัง
  4. **JavaScript Safety & Event Integrity:** ห้ามผูก Event ซ้ำซ้อน มีกลไกป้องกัน Double Submission เสมอ และห้ามประกาศตัวแปรใน Global Scope
  5. **Backend Logic & Session Integrity:** ตรวจสอบ Session/Auth ผ่านโมดูลกลางที่หัวไฟล์เท่านั้น และทำ Input Sanitization เสมอ
  6. **AI Operational Protocol:** วางแผนและวิเคราะห์ผลกระทบก่อนลงมือเสมอ (Plan & Impact Analysis First) และห้ามแก้ไขโค้ดใดๆ โดยไม่รายงาน (No Silent Changes)


