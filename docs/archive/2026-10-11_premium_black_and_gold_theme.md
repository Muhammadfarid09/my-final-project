# บันทึกการปรับเปลี่ยนธีมทั้งระบบเป็นสไตล์ "Premium Black & Gold" (Design Tokens)

**วันที่:** 2026-10-11  
**ผู้พัฒนา:** Antigravity AI Pair Programmer  
**เอกสารอ้างอิง:** [PROJECT_HCI_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md), [ADDITIONAL_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/ADDITIONAL_RULES.md)

---

## 1. ทำอะไรบ้าง (What Was Done)
* **อัปเดต Design Tokens ใน [`assets/css/global.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/global.css):**
  - **พื้นผิวและพื้นหลัง (Surface & Elevation 70% / 20%):**
    * `--bg-primary: #090909;` (Canvas พื้นหลังหลักของหน้าเว็บ)
    * `--bg-surface: #191919;` (Card, Container, Panel พื้นหลัง)
    * `--bg-elevated: #292929;` (Input Box, Select Box, Hover Surface)
  - **โทนสีเน้นแบรนด์ (Brand Accent 10%):**
    * `--color-gold: #FFB91D;` (Primary CTA, Active State, ราคาสำคัญ)
    * `--color-gold-hover: #E9A812;` (Interactive Hover State)
  - **ตัวอักษรและความคมชัด (Typography & Contrast Ratio):**
    * `--text-primary: #F5F5F5;` (Heading, Label, Value บนพื้นหลังมืด)
    * `--text-secondary: #A3A3A3;` (Caption, Placeholder, Helper Text)
    * `--text-on-gold: #090909;` (ข้อความบนปุ่มทอง เป็นสีดำเข้มตามข้อกำหนด ห้ามใช้สีขาว)
  - **เส้นขอบและเส้นแบ่ง (Borders & Dividers):**
    * `--border-color: #383838;` (Border ของ Container และ Form Elements)
  - **สีสถานะการจองสนาม (Domain Status):**
    * `--status-available: #22C55E;` (คอร์ทว่าง)
    * `--status-booked: #EF4444;` (คอร์ทติดจอง)
  - **การรักษาความเข้ากันได้ย้อนหลัง (Backward Compatibility Mapping):**
    * แมป `--brand-primary` และ `--brand-accent` ไปยัง `var(--color-gold)`
    * แมป `--brand-primary-dark` และ `--brand-accent-hover` ไปยัง `var(--color-gold-hover)`
    * แมป `--bg-page` ไปยัง `var(--bg-primary)`
    * แมป `--bg-card` ไปยัง `var(--bg-surface)`
    * แมป `--bg-alt` ไปยัง `var(--bg-elevated)`
    * แมป `--text-muted` ไปยัง `var(--text-secondary)`
    * แมป `--border-light` ไปยัง `var(--border-color)`
    * ปรับปรุงสีป้ายระดับสมาชิก (`--gold-color`, `--gold-bg`, `--silver-color`, `--silver-bg`, `--bronze-color`, `--bronze-bg`) และเงา Dark Elevation (`--shadow-sm`, `--shadow-md`, `--shadow-lg`, `--shadow-xl`) ให้กลมกลืนกับพื้นหลังมืด
* **ปฏิบัติตามขอบเขตงานอย่างเคร่งครัด:**
  - แก้ไขเฉพาะไฟล์ `assets/css/global.css` เท่านั้น
  - ไม่มีการแก้ไขหรือสร้างไฟล์ `.php` และ `.css` อื่นๆ
  - ไม่มีการใช้ `!important` ภายในบล็อกการเปลี่ยนแปลง

---

## 2. ระบบเป็นอย่างไร (How the System Works)
```
+-----------------------------------------------------------------------------------+
|                        assets/css/global.css (:root Block)                        |
+-----------------------------------------------------------------------------------+
                                         │
                   ┌─────────────────────┴─────────────────────┐
                   ▼                                           ▼
+------------------------------------+      +------------------------------------+
| 1. Design Tokens ใหม่ (Core Tokens)|      | 2. Backward Compatibility Mappings |
| - --bg-primary: #090909;           |      | - --brand-primary: var(--color-gold)|
| - --bg-surface: #191919;           |      | - --brand-accent: var(--color-gold)|
| - --bg-elevated: #292929;          |      | - --bg-page: var(--bg-primary);    |
| - --color-gold: #FFB91D;           |      | - --bg-card: var(--bg-surface);    |
| - --text-primary: #F5F5F5;         |      | - --border-light: var(--border-color)|
| - --text-on-gold: #090909;         |      | - --text-muted: var(--text-secondary)|
+------------------------------------+      +------------------------------------+
                   │                                           │
                   └─────────────────────┬─────────────────────┘
                                         ▼
+-----------------------------------------------------------------------------------+
| หน้าบ้าน (Frontend) และ หลังบ้าน (Admin):                                         |
| style.css, responsive-mobile.css, admin_style.css ฯลฯ ที่ดึงตัวแปรจาก global.css  |
| จะได้รับค่าสี Premium Black & Gold ทันทีโดยไม่ต้องแก้โค้ดแต่ละไฟล์ซ้ำซ้อน         |
+-----------------------------------------------------------------------------------+
```

---

## 3. การใช้งานอย่างไร (How to Use / Test)
1. **ทดสอบผลการแสดงผลตัวแปร CSS:**
   - เปิดหน้าแรก `http://localhost/ts-pattani/index.php` หรือหน้าจองสนาม `booking.php` หรือหน้าล็อกอิน `login.php`
   - กดปุ่ม `F12` เพื่อเปิด DevTools -> Elements -> ตรวจสอบบล็อก `:root`
   - จะเห็นตัวแปรพื้นผิว `#090909`, `#191919`, `#292929` และสีทอง `#FFB91D` มีผลบังคับใช้
2. **ทดสอบ Contrast Ratio (Accessibility):**
   - ข้อความหลักสี `#F5F5F5` แสดงผลบนพื้นผิว `#090909` และ `#191919` อย่างชัดเจน ไร้ปัญหาแสงสะท้อนจ้า
   - ข้อความบนปุ่มทองใช้สี `#090909` (ดำเข้ม) ทำให้อ่านง่าย ชัดเจน ตรงตามเกณฑ์ WCAG AAA

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)
1. **ความเสี่ยงที่สไตล์เดิมในระบบจะพังจากการเปลี่ยนชื่อตัวแปร:**
   - *สาเหตุ:* โค้ดเดิมในหลายๆ หน้าใช้งานตัวแปรชื่อ `--brand-primary`, `--bg-card`, `--bg-page` หากประกาศเฉพาะตัวแปรใหม่โดยไม่ดูแลตัวแปรเดิม อาจทำให้หน้าที่มีอยู่เดิมขาดตัวแปรตกกระทบ
   - *วิธีแก้:* ใช้เทคนิค Token Mapping ภายใน `:root` เชื่อมโยงตัวแปรเดิมเข้ากับ Token ใหม่อย่างไร้รอยต่อ
2. **การรักษาข้อกำหนด Strict Scope:**
   - *สาเหตุ:* โจทย์กำหนดข้อห้ามเด็ดขาดไม่ให้แตะต้องไฟล์ `.php` และ `.css` อื่นๆ
   - *วิธีแก้:* ปรับแก้เฉพาะบล็อก `:root` ใน `assets/css/global.css` ไฟล์เดียว ทำให้ Blast Radius เป็นศูนย์

