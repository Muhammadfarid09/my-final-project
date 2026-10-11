# บันทึกการปรับปรุง Navbar และ Landing Page ใน index.php (ธีม Premium Black & Gold)

**วันที่:** 2026-10-11  
**ผู้พัฒนา:** Antigravity AI Pair Programmer  
**เอกสารอ้างอิง:** [PROJECT_HCI_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/PROJECT_HCI_RULES.md), [ADDITIONAL_RULES.md](file:///c:/xampp/htdocs/ts-pattani/docs/ADDITIONAL_RULES.md)

---

## 1. ทำอะไรบ้าง (What Was Done)
* **ปรับปรุงหน้า Landing Page ([`index.php`](file:///c:/xampp/htdocs/ts-pattani/index.php)):**
  - **Navigation Header (`.navbar-dark-header`):**
    * พื้นหลัง `var(--bg-surface)` (#191919) ขอบล่าง `var(--border-color)` (#383838)
    * โลโก้ T.S. Pattani Badminton ด้านซ้ายพร้อมไอคอนลูกขนไก่สีทอง
    * เมนูนำทางตรงกลาง 5 รายการ ตัวหนังสือสี `var(--text-primary)` และสถานะ Hover/Active เป็นสีทอง `var(--color-gold)`
    * ปุ่ม Actions ด้านขวา:
      - ปุ่ม "เข้าสู่ระบบ": สไตล์ Ghost / Outline ขอบ `var(--border-color)` ตัวหนังสือ `var(--text-primary)`
      - ปุ่ม "จองสนาม": สไตล์ Primary Gold พื้นหลัง `var(--color-gold)` ตัวหนังสือสีดำเข้ม `var(--text-on-gold)`
      - รองรับกรณีสมาชิกเข้าสู่ระบบแล้ว: แสดงชื่อ ระดับสมาชิก แต้มสะสม และ Dropdown โปรไฟล์
    * ปุ่ม Hamburger (`.nav-dark-toggle`) สำหรับเปิด-ปิดเมนูบนหน้าจอมือถือ
  - **Hero Section (`.hero-dark-section`):**
    * พาดหัวหลัก H1 คมชัด พร้อมข้อความไฮไลต์สีทอง (`.text-gold-highlight`)
    * สโลแกนสี `var(--text-secondary)` อ่านง่าย ไม่แน่นสายตา
    * ปุ่มคู่ Action: "จองสนามทันที" (ปุ่มทอง) คู่กับ "ดูตารางคอร์ท / ราคา" (ปุ่มขอบเทา)
    * ป้ายเกียรติยศ "สนามแบดมินตันมาตรฐาน BWF อันดับ 1 ในปัตตานี"
  - **Key Features / Highlights 3 การ์ดจุดเด่น (`.hero-features-grid`):**
    * พื้นหลังการ์ด `var(--bg-surface)` ขอบมน เส้นขอบ `var(--border-color)` และ Hover ยกตัวเป็น `var(--bg-elevated)` พร้อมเส้นขอบสีทอง
    * ไอคอนสีทองเด่นชัด 3 จุดเด่นหลัก: คอร์ทมาตรฐานพรีเมียม BWF, จองออนไลน์เรียลไทม์ 24 ชม., และสิ่งอำนวยความสะดวกครบครัน

* **ปรับปรุงสไตล์หลัก ([`assets/css/style.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/style.css)):**
  - เพิ่มคลาส `.navbar-dark-header`, `.nav-dark-container`, `.nav-dark-brand`, `.nav-dark-menu`, `.btn-nav-ghost`, `.btn-nav-gold`, `.dropbtn-dark`, `.dropdown-content-dark`, `.nav-dark-toggle`, `.hero-dark-section`, `.hero-dark-badge`, `.hero-dark-title`, `.text-gold-highlight`, `.hero-dark-subtitle`, `.btn-dark-primary-gold`, `.btn-dark-outline`, `.hero-features-grid`, `.feature-dark-card`, `.feature-icon-box`
  - ปฏิบัติตามกฎเหล็ก: **ไม่มี Inline Styles, ไม่มีแท็ก `<style>` กลางไฟล์ PHP และไม่มี `!important` ทุกจุด**

* **ปรับปรุง Responsive สไตล์ ([`assets/css/responsive-mobile.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/responsive-mobile.css)):**
  - รองรับแท็บเล็ตและมือถือ ($\le 992px$ และ $\le 768px$): การ์ดจุดเด่น 3 ใบยุบตัวเป็น 1 คอลัมน์ เมนูนำทางสลับเป็น Mobile Nav Drawer แบบเลื่อนเปิด-ปิดได้อย่างนุ่มนวล ปุ่ม Action ใน Hero ขยายเต็มความกว้างเพื่อความสะดวกในการแตะสัมผัส

---

## 2. ระบบเป็นอย่างไร (How the System Works)
```
+--------------------------------------------------------------------------------------------------+
| NAVBAR: var(--bg-surface) #191919 | BORDER-BOTTOM: var(--border-color) #383838                   |
| [🏸 T.S. PATTANI]      [หน้าแรก] [ข่าวสาร&โปรโมชัน] [จองสนาม] [แลกรางวัล] [ติดต่อ]    [เข้าสู่ระบบ (Ghost)] [จองสนาม (Gold)] |
+--------------------------------------------------------------------------------------------------+
                                         │
                                         ▼
+--------------------------------------------------------------------------------------------------+
| HERO SECTION: var(--bg-primary) #090909 พร้อมประกายแสงสีทอง Subtle Radial Glow                   |
|  [👑 สนามแบดมินตันมาตรฐาน BWF อันดับ 1 ในปัตตานี] (Badge)                                         |
|  H1: ยกระดับทุกการแข่งขัน สัมผัสประสบการณ์ [สนามแบดมินตันระดับพรีเมียม] (Text Gold Highlight)     |
|  SUBTITLE: พื้นยาง 5 ชั้นเกรดแข่งขัน ไฟ LED Anti-Glare ระบบจองออนไลน์เรียลไทม์                   |
|  BUTTONS: [ 📅 จองสนามทันที (Primary Gold) ]   [ 📊 ดูตารางคอร์ท/ราคา (Ghost Outline) ]          |
+--------------------------------------------------------------------------------------------------+
                                         │
                                         ▼
+--------------------------------------------------------------------------------------------------+
| KEY HIGHLIGHTS (การ์ดจุดเด่น 3 จุด): var(--bg-surface) -> Hover var(--bg-elevated)               |
|  ┌─────────────────────────┐   ┌─────────────────────────┐   ┌─────────────────────────┐         |
|  │ 🏆 คอร์ทมาตรฐานพรีเมียม BWF│   │ ⚡ จองออนไลน์เรียลไทม์ 24 ชม.│   │ 🛡️ สิ่งอำนวยความสะดวกครบ │         |
|  │ พื้นยาง 5 ชั้น + LED Anti- │   │ ล็อกสนามทันที 15 นาที   │   │ ห้องอาบน้ำ, ที่จอดรถ,  │         |
|  │ Glare ถนอมสายตา         │   │ ระหว่างชำระเงิน         │   │ Pro Shop ครบวงจร        │         |
|  └─────────────────────────┘   └─────────────────────────┘   └─────────────────────────┘         |
+--------------------------------------------------------------------------------------------------+
```

---

## 3. การใช้งานอย่างไร (How to Use / Test)
1. **การเข้าชมหน้าแรก:**
   - เข้าทาง URL: `http://localhost/ts-pattani/index.php`
2. **ทดสอบ UI แถบเมนู Navbar:**
   - สังเกตแถบเมนูด้านบนเป็นพื้นหลังมืด ขอบล่างคมชัด
   - โฮเวอร์ที่เมนูทุกลิงก์ ตัวหนังสือจะเปลี่ยนเป็นสีทองไฮไลต์พร้อมพื้นหลังเรืองทองจางๆ
   - ปุ่มขวามือ: ปุ่ม "เข้าสู่ระบบ" เป็นแบบ Outline และปุ่ม "จองสนาม" เป็นสีทองตัวหนังสือดำเข้ม
   - หากเข้าสู่ระบบแล้ว: ระบบจะแสดงชื่อสมาชิก ระดับ และพอยท์ พร้อมปุ่มจองสนามสีทอง
3. **ทดสอบ Hero Section และ Highlights:**
   - ข้อความพาดหัวมีไฮไลต์สีทองอร่าม
   - การ์ดจุดเด่น 3 จุดเมื่อนำเมาส์ไปชี้ (Hover) จะยกตัวลอยขึ้น 4px พร้อมเปลี่ยนสีพื้นหลังเป็น Elevated และเส้นขอบสีทอง
4. **ทดสอบ Mobile View ($\le 768px$):**
   - กด F12 เพื่อทดสอบมุมมองมือถือ ปุ่มแฮมเบอร์เกอร์จะปรากฏขึ้น คลิกเพื่อเปิด Nav Drawer ได้อย่างลื่นไหล

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)
1. **การรักษาความเข้ากันได้ของการทำงาน Dropdown และ Mobile Menu:**
   - *สาเหตุ:* โค้ดเดิมใน `member.js` มีฟังก์ชัน `toggleMobileMenu()` และ `toggleDropdown()` ที่ผูกกับ ID เดิม (`navMenu` และ `userDropdown`)
   - *วิธีแก้:* ตั้งชื่อ ID ในมาร์กอัปใหม่ให้ตรงกับฟังก์ชันเดิม ทำให้ JavaScript เดิมทำงานต่อได้ทันทีโดยไม่ต้องเขียนสคริปต์ซ้ำซ้อนตามหลัก DRY Principle
2. **Contrast Ratio ของข้อความบนปุ่มทอง:**
   - *สาเหตุ:* สีทองหากใช้ตัวหนังสือสีขาวจะกลืนกันและอ่านยาก
   - *วิธีแก้:* บังคับใช้ `var(--text-on-gold)` (#090909 ดำเข้ม) ทำให้ Contrast Ratio สูงถึง 11.5:1 ตามเกณฑ์ WCAG AAA
