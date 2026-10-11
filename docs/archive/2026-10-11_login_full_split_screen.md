# รายงานการพัฒนาหน้า login.php แบบ Full Split-Screen (50/50, 100vh) ธีม Premium Black & Gold

**วันที่:** 11 ตุลาคม 2026  
**ระบบ:** T.S. Pattani Badminton - Member Authentication (`login.php`)  
**เป้าหมาย:** ปรับปรุงเลย์เอาต์หน้า `login.php` สู่รูปแบบ Full Split-Screen (แบ่งครึ่งซ้าย-ขวา 50/50 เต็มจอ 100vh) ในธีม "Premium Black & Gold" ตามหลัก HCI และข้อบังคับ `ADDITIONAL_RULES.md`

---

## 1. สิ่งที่ได้ดำเนินการ (What Was Done)

### 1.1 การปรับปรุงโครงสร้าง HTML (`login.php`)
- **ฝั่งซ้าย (Brand & Visual Panel 50% Desktop):**
  - คอนเทนเนอร์ `.login-visual-panel` ใช้พื้นหลังสีดำลึก `var(--bg-primary)` พร้อมลวดลายเส้นคอร์ทมินิมอล `.visual-court-pattern` และแสงสะท้อนสีทอง `.visual-ambient-glow`
  - ส่วนบน: ป้ายแคปซูลโปร่งแสง `.visual-badge-capsule` แสดง "🏸 T.S. Pattani Badminton Booking Portal"
  - ส่วนกลาง: พาดหัว H1 เด่นชัดพร้อมไฮไลต์สีทอง, สโลแกน และการ์ดจุดเด่นลอยตัว 3 ใบ (`.visual-highlight-card`):
    1. คอร์ทมาตรฐาน BWF (พื้นยาง 5 ชั้น ซับแรงกระแทก เกรดแข่งขัน)
    2. ระบบไฟส่องสว่างระดับแข่งขัน (LED Anti-Glare ตัดแสงสะท้อน ถนอมสายตา)
    3. จองคิวออนไลน์เรียลไทม์ 24 ชม. (เลือกรอบเวลา ล็อกคอร์ทอัตโนมัติ รวดเร็ว 100%)
  - ส่วนล่าง: ป้ายความปลอดภัยและมาตรฐานความเชื่อมั่นสากล
- **ฝั่งขวา (Interaction & Form Panel 50% Desktop):**
  - คอนเทนเนอร์ `.login-form-panel` ใช้พื้นหลัง `var(--bg-surface)`
  - แถบเครื่องมือด้านบน (`.login-top-utility`):
    - ปุ่มซ้าย: `← กลับหน้าหลัก` (`.btn-utility-back`) นำทางกลับ `index.php`
    - เมนูขวา: แสดงสถานะระบบ `.utility-status-pill` พร้อมจุดไฟสีเขียว `.status-indicator-dot` "ระบบพร้อมให้บริการ"
  - การ์ดฟอร์มเข้าสู่ระบบลอยตัว (`.login-card-floating`):
    - ป้ายแท็กแบรนด์ `T.S. PATTANI`, หัวข้อ "เข้าสู่ระบบสมาชิก"
    - ช่องกรอกเบอร์โทรศัพท์ (Username) พร้อมไอคอน `fas fa-phone`
    - ช่องกรอกรหัสผ่าน พร้อมปุ่ม Toggle รูปลูกตา `fas fa-eye` / `fas fa-eye-slash`
    - แถวควบคุม: Checkbox "จดจำการเข้าสู่ระบบ" (Remember Me) และลิงก์ "ลืมรหัสผ่าน?"
    - ปุ่ม CTA สีทองเต็มความกว้าง (`.btn-login-gold-full`) "เข้าสู่ระบบ" พร้อมไอคอนลูกศร
    - ลิงก์ท้ายการ์ด: "ยังไม่มีบัญชีใช่ไหม? สมัครสมาชิกที่นี่" (ลิงก์ `register.php`)

### 1.2 การปรับปรุงสไตล์ CSS (`assets/css/style.css`)
- สไตล์ Full Split-Screen 100vh (`.login-split-wrapper`)
- สไตล์ Visual Court Lines และ Ambient Glow
- สไตล์ Floating Highlight Cards ด้วย Backdrop Blur และ Border Gold Hover
- สไตล์ Form Controls Dark Theme: `.input-group-dark`, `.form-control-dark`, `.btn-login-gold-full`

### 1.3 การจัดการพฤติกรรมบนอุปกรณ์พกพา (`assets/css/responsive-mobile.css`)
- บนหน้าจอขนาดเล็ก (<= 768px):
  - ซ่อนฝั่งซ้าย (`.login-visual-panel { display: none; }`) อัตโนมัติเพื่อประหยัดพื้นที่หน้าจอ
  - ให้ฝั่งขวา (`.login-form-panel`) ขยายเต็มความกว้าง 100% พร้อม Padding พอดีกับจอมือถือ
  - การ์ดฟอร์มขยายเต็มหน้าจอ ไม่ล้นขอบจอ

### 1.4 การจัดการระบบความปลอดภัย (Security & HCI)
- **Prepared Statements (PDO):** ค้นหาข้อมูลสมาชิกผ่าน Parameter Binding ใน `actions/login_db.php`
- **Password Hashing:** ตรวจสอบความถูกต้องของรหัสผ่านด้วย `password_verify()`
- **Session Integrity:** ป้องกัน Session Fixation ด้วย `session_regenerate_id(true)`
- **JavaScript Double Submit Prevention:** มีระบบป้องกันการกดส่งฟอร์มซ้ำซ้อนใน `assets/js/member.js` พร้อมข้อความแจ้งเตือน "กำลังตรวจสอบข้อมูล..."

---

## 2. รายการไฟล์ที่แก้ไขและเพิ่มใหม่

| ไฟล์ | การเปลี่ยนแปลง |
|---|---|
| `login.php` | ปรับโครงสร้างหน้าเป็น Full Split-Screen 50/50 เต็มจอ 100vh พร้อม Utility Bar และ Floating Card |
| `assets/css/style.css` | เพิ่มสไตล์คลาสสำหรับ Full Split-Screen Layout, Visual Panel, Form Panel และ Gold CTA |
| `assets/css/responsive-mobile.css` | เพิ่มกฎ Media Query สำหรับจอมือถือ (<= 768px) ซ่อนฝั่งซ้ายและขยายฝั่งขวาเต็ม 100% |
| `docs/2026-10-11_login_full_split_screen.md` | บันทึกเอกสารสรุปผลประจำวันตามข้อกำหนด GEMINI.md |

---

## 3. การตรวจสอบความสอดคล้องกับกฎระเบียบ (Compliance Verification)

1. **DRY Principle:** ใช้ CSS Design Tokens ใน `global.css` ทั้งหมด
2. **Strict Separation of Concerns:**
   - ปราศจาก Inline Style (`style="..."`) 100%
   - ปราศจากแท็ก `<style>` แทรกกลางไฟล์ PHP
   - ปราศจากคำสั่ง `!important` ในไฟล์ CSS ทั้งหมด
3. **Safety First & Backward Compatibility:** ไม่กระทบ Logic ของ Session, Remember Me และ Event IDs เดิมของ `member.js`
4. **HCI Standards:** ยึดหลัก Visual Hierarchy, Form Ergonomics และ Mobile Ergonomics

