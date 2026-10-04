# บันทึกการดำเนินงาน: ย้ายเอกสาร PROJECT_HCI_RULES.md เข้าสู่โฟลเดอร์ docs/ (2026-10-05)
**วันที่:** 2026-10-05  
**ระบบ:** T.S. Pattani Badminton Court Management System  
**ผู้จัดทำ:** Antigravity AI Assistant

---

## 1. ทำอะไรบ้าง (What Was Done)

ดำเนินการย้ายที่ตั้งไฟล์ข้อกำหนดด้าน HCI จาก Root Directory ไปยังโฟลเดอร์จัดเก็บเอกสาร `docs/` เพื่อความเป็นระเบียบและรวมศูนย์เอกสารของโครงการ:

### รายการไฟล์ที่ดำเนินการ:
1. **ย้ายไฟล์ด้วย Git (`git mv`):**
   - ย้ายจาก `PROJECT_HCI_RULES.md` ที่ Root ไปเป็น [docs/PROJECT_HCI_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md)
   - การใช้คำสั่ง `git mv` ช่วยรักษาประวัติการแก้ไข (Git History / Blame) ไว้อย่างสมบูรณ์
2. **[README.md](file:///c:/xampp/htdocs/ts-pattani/README.md):**
   - อัปเดตผังโครงสร้างไดเรกทอรีของโปรเจกต์ (Project Structure Tree) ในบรรทัดโฟลเดอร์ `docs/` ให้ระบุถึง [docs/PROJECT_HCI_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md) ไว้อย่างชัดเจน
3. **[GEMINI.md](file:///c:/xampp/htdocs/ts-pattani/GEMINI.md):**
   - เพิ่มหมวดหมู่ **"🎨 มาตรฐานการออกแบบ UI/UX ตามหลัก HCI"** กำหนดให้ AI Assistant ยึดถือกฎใน [docs/PROJECT_HCI_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md) และต้องปฏิบัติตามกระบวนการ 6 ขั้นตอน (*Understand → Analyze → Propose → Confirm → Modify → Verify*) อย่างเคร่งครัด

---

## 2. ระบบเป็นอย่างไร (How the System Works)

### โครงสร้างการจัดระเบียบ Root Directory (Clean Architecture):
```text
ts-pattani/
├── README.md                      <-- หน้าแรกของ GitHub (ภาพรวม, ติดตั้ง, บัญชีทดสอบ)
├── GEMINI.md                      <-- กฎระบบควบคุม AI Assistant (อ้างอิงไปยัง docs/PROJECT_HCI_RULES.md)
├── docs/                          <-- ศูนย์รวมเอกสาร สเปก และบันทึกทั้งหมด
│   ├── PROJECT_HCI_RULES.md       <-- กฎการออกแบบ UI/UX, Heuristics และ Workflow (ย้ายมาที่นี่)
│   ├── USER_SCOPE_AUDIT_RESULT.md <-- ผลการตรวจขอบเขต 1.3
│   ├── CHANGELOG.md               <-- ประวัติการพัฒนาระบบ
│   └── YYYY-MM-DD_...md           <-- บันทึกการทำงานประจำวัน
├── actions/                       # Business Logic ฝั่งสมาชิก
├── admin/                         # ระบบบริหารจัดการหลังบ้าน
└── assets/                        # ไฟล์ CSS / JS / Images
```

* **Root Directory สะอาด:** มีเฉพาะไฟล์ที่จำเป็นต่อเครื่องมือภายนอก (GitHub อ่าน `README.md`, Antigravity อ่าน `GEMINI.md`)
* **Docs Directory ครบถ้วน:** เอกสารเชิงเทคนิคและการออกแบบทั้งหมดถูกรวมไว้ในที่เดียว อ่านง่าย ค้นหาง่าย สำหรับการทำเล่มโครงงาน

---

## 3. การใช้งานอย่างไร (How to Use / Test)

1. **การเข้าถึงเอกสาร HCI:**
   - สามารถเปิดอ่านไฟล์ได้โดยตรงที่: [docs/PROJECT_HCI_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md)
2. **การตรวจสอบลิงก์ในเอกสาร:**
   - ในหน้าแรกของ [README.md](file:///c:/xampp/htdocs/ts-pattani/README.md) และ [GEMINI.md](file:///c:/xampp/htdocs/ts-pattani/GEMINI.md) จะมีลิงก์อ้างอิงชี้มาที่ `docs/PROJECT_HCI_RULES.md` อย่างถูกต้อง

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)

* **ความต้องการเดิม:** ผู้ใช้ต้องการย้ายไฟล์ `PROJECT_HCI_RULES.md` ไปไว้ในโฟลเดอร์ `docs/` เพื่อไม่ให้ Root Directory มีไฟล์กระจัดกระจาย
* **วิธีการแก้ไข:**
  - ย้ายไฟล์ด้วย `git mv` เพื่อไม่ให้สูญเสียประวัติการ Commit
  - อัปเดต Path การอ้างอิงใน `README.md` และ `GEMINI.md` ทันที ป้องกันการเกิด Broken Link
