# บันทึกการเพิ่มและผสานกฎเหล็กเสริม (ADDITIONAL_RULES.md) ควบคู่กับมาตรฐาน HCI

**วันที่ดำเนินการ:** 11 ตุลาคม 2026  
**ผู้รับผิดชอบ:** AI Pair Programmer / System Architect  
**สถานะ:** เสร็จสมบูรณ์ (Completed & Active)

---

## 1. ทำอะไรบ้าง (What Was Done)

### 1.1 ไฟล์ที่สร้างและแก้ไข
1. **สร้างไฟล์ [`docs/ADDITIONAL_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/ADDITIONAL_RULES.md):**
   - รวบรวมและจัดระเบียบกฎเหล็กและข้อจำกัดการปฏิบัติงานทั้ง 6 ข้ออย่างเป็นทางการ:
     - ข้อที่ 1: DRY Principle & Code Reuse (ห้ามเขียนซ้ำซ้อน)
     - ข้อที่ 2: Strict Separation of Concerns (สถาปัตยกรรม CSS 5 ไฟล์หลัก ห้าม Inline Style/Embedded `<style>` และห้าม `!important`)
     - ข้อที่ 3: Safety First & Blast Radius Control (ควบคุมขอบเขตผลกระทบ ห้ามลบ/เปลี่ยนชื่อคลาสเดิม)
     - ข้อที่ 4: JavaScript Safety & Event Integrity (ห้ามผูก Event ซ้ำซ้อน, ป้องกัน Double Submission, Scope Isolation ด้วย IIFE)
     - ข้อที่ 5: Backend Logic & Session Integrity (Unified Auth Check, Input Sanitization)
     - ข้อที่ 6: AI Operational Protocol (วางแผนและวิเคราะห์ผลกระทบก่อนลงมือ, ห้ามแอบเปลี่ยนโค้ดโดยไม่แจ้ง)
2. **แก้ไขไฟล์ [`GEMINI.md`](file:///c:/xampp/htdocs/ts-pattani/GEMINI.md):**
   - เพิ่มหมวด "🛡️ กฎเหล็กและข้อจำกัดการปฏิบัติงาน (System Rules & Strict Operational Constraints)"
   - เชื่อมโยง [`docs/ADDITIONAL_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/ADDITIONAL_RULES.md) เข้าเป็นกฎหลักที่ต้องปฏิบัติตามอย่างเคร่งครัดควบคู่กับ [`docs/PROJECT_HCI_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md) โดยคงกฎการตั้งชื่อไฟล์, การบันทึกรายวัน, การรายงาน 4 ส่วน และขั้นตอน 6 สเต็ปของ HCI เดิมไว้อย่างครบถ้วน 100%

---

## 2. ระบบเป็นอย่างไร (How the System Works)

### 2.1 โครงสร้างลำดับชั้นของกฎ (Rule Hierarchy & Governance)
```
[GEMINI.md] (Master Guidelines & Operational Standards)
   ├── กฎการตั้งชื่อไฟล์และการจัดเก็บเอกสารรายวัน (docs/YYYY-MM-DD_...)
   ├── โครงสร้างการรายงานผลการทำงาน 4 ส่วนบังคับ
   ├── มาตรฐานการออกแบบ UI/UX ตามหลัก HCI (docs/PROJECT_HCI_RULES.md)
   │     └── 6-Step Workflow: Understand → Analyze → Propose → Confirm → Modify → Verify
   └── กฎเหล็กและข้อจำกัดการปฏิบัติงาน 6 ข้อ (docs/ADDITIONAL_RULES.md)
         ├── 1. DRY Principle & Code Reuse
         ├── 2. Strict Separation of Concerns (CSS 5 ไฟล์)
         ├── 3. Safety First & Blast Radius Control
         ├── 4. JavaScript Safety & Event Integrity
         ├── 5. Backend Logic & Session Integrity
         └── 6. AI Operational Protocol
```

### 2.2 การทำงานร่วมกันแบบไม่ทับซ้อน (Non-Conflicting Coexistence)
- **`PROJECT_HCI_RULES.md`** รับผิดชอบมิติด้าน **UI/UX & Cognitive Usability:** ยึด Nielsen's 10 Heuristics, แอนิเมชัน, ความชัดเจนของสถานะ, สีย้อนแย้ง และการป้องกันความสับสนของผู้ใช้
- **`ADDITIONAL_RULES.md`** รับผิดชอบมิติด้าน **Technical Architecture & Operational Safety:** โครงสร้างไฟล์ CSS, ความปลอดภัยของ JavaScript, การห้าม Inline Style, การใช้ฟังก์ชัน SQL/Auth กลาง และระเบียบการแก้ไขของ AI
- กฎทั้งสองชุดเสริมพลังซึ่งกันและกันอย่างสมบูรณ์แบบเพื่อทำให้ระบบมีความปลอดภัย เสถียรภาพ และใช้งานง่ายสูงสุด

---

## 3. การใช้งานอย่างไร (How to Use / Test)

1. **การตรวจสอบความถูกต้องของเอกสาร:**
   - ตรวจสอบไฟล์ [`docs/ADDITIONAL_RULES.md`](file:///c:/xampp/htdocs/ts-pattani/docs/ADDITIONAL_RULES.md) เนื้อหากฎเหล็กครบทั้ง 6 ข้อ
   - ตรวจสอบไฟล์ [`GEMINI.md`](file:///c:/xampp/htdocs/ts-pattani/GEMINI.md) มีการอ้างอิงลิงก์และสรุปย่ออย่างชัดเจน
2. **การนำไปปฏิบัติในทุกคำสั่งถัดไป (Enforcement):**
   - ทุกครั้งก่อนเริ่มเขียนโค้ด AI จะต้องตรวจสอบความซ้ำซ้อนตามหลัก DRY
   - สรุปแผนและไฟล์ที่จะแก้ไขพร้อมผลกระทบให้ผู้ใช้ทราบก่อนลงมือ (Plan & Impact Analysis First)
   - สไตล์ใหม่ทั้งหมดต้องลงในไฟล์ CSS เฉพาะของตน ห้ามใช้ `style="..."` หรือ `<style>` ใน PHP
   - รักษาความเข้ากันได้ย้อนหลัง ไม่แตะต้องโค้ดหรือคลาสเดิมที่ไม่ได้รับมอบหมาย

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)

1. **การป้องกันไม่ให้กฎใหม่ไปลบล้างกฎเดิม (Preserving Existing Rule Integrity):**
   - *วิธีแก้:* ไม่ลบส่วนใดๆ ใน `GEMINI.md` ออก แต่ใช้วิธีต่อเติมส่วน "กฎเหล็กและข้อจำกัดการปฏิบัติงาน" และลิงก์ไปยัง `docs/ADDITIONAL_RULES.md` โดยตรง
2. **การอ้างอิงเส้นทางไฟล์ (File Path Links):**
   - *วิธีแก้:* ใช้ลิงก์รูปแบบ GitHub Markdown link พร้อม `file:///` scheme ตามมาตรฐาน Antigravity เพื่อให้ผู้ใช้สามารถคลิกเปิดเอกสารได้ทันทีจากแชตหรือตัวแก้ไขโค้ด
