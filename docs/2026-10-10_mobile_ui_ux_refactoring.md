# รายงานการพัฒนาและปรับปรุงระบบ Mobile UI/UX ตามมาตรฐาน HCI & Figma Reference
**วันที่:** 10 ตุลาคม 2026  
**โครงการ:** ระบบบริหารจัดการสนามแบดมินตัน T.S. Pattani  
**เอกสารอ้างอิง:** `MOBILE_UI_GUIDE.md`, `docs/PROJECT_HCI_RULES.md`, `GEMINI.md`, Figma mbooking Movie Ticket App Design Patterns  

---

## 1. ทำอะไรบ้าง (What Was Done)

### 1.1 ไฟล์ที่สร้าง เพิ่มเติม หรือแก้ไข
1. **`assets/css/global.css`**:
   - เพิ่ม Design Tokens สำหรับ Mobile Interface: `--touch-min-size: 44px;`, `--bottom-nav-height: 64px;`, Safe-area env variables (`env(safe-area-inset-bottom)`)
   - เพิ่มคลาสควบคุมการแสดงผลตามขนาดหน้าจอ: `.hide-on-mobile`, `.hide-on-desktop`
2. **`assets/css/style.css`**:
   - เพิ่มคลาส `.member-bottom-nav` พร้อมเอฟเฟกต์ Frosted Glass (backdrop-filter blur), active state highlight, และ safe-area insets
   - ป้องกันการชนกันของ Bottom Bar ในหน้าจอง: `body.booking-page-body .member-bottom-nav { display: none !important; }`
   - เพิ่มคลาสสำหรับหน้าแรก (Landing & Member Home): `.landing-hero-showcase`, `.facility-showcase-grid`, `.guest-bottom-bar`, `.member-home-hero`, `.action-shortcuts-grid`, `.live-court-strip`
   - เพิ่มคลาสสำหรับหน้า Auth: `.auth-card-segmented`, `.input-eye-group`, `.btn-eye-toggle`
   - เพิ่มคลาสสำหรับหน้าจอง: `.wizard-mobile-stepper`, `.addon-filter-pill-bar`, `.btn-addon-filter`
   - เพิ่มคลาสสำหรับประวัติและโปรไฟล์: `.history-filter-bar`, `.btn-history-filter`, `.history-mobile-cards`, `.virtual-member-card` (พร้อมคลาสเหรียญระดับสมาชิก `.virtual-card-bronze`, `.virtual-card-silver`, `.virtual-card-gold`), `.btn-redeem-chip`
3. **`assets/css/admin.css`**:
   - เพิ่มคลาสรองรับ Mobile Drawer Navigation สำหรับฝั่งผู้ดูแลระบบ: `.admin-mobile-toggle`, `.admin-sidebar-backdrop`, `.btn-close-sidebar`
   - เพิ่ม Media Query `@media (max-width: 992px)` ปรับเปลี่ยน Sidebar จาก Fixed Margin เป็น Off-canvas Drawer เลื่อนเข้า-ออก นุ่มนวล พร้อม Backdrop เบลอพื้นหลัง
4. **`includes/navbar.php`**:
   - ตรวจสอบสถานะการล็อกอิน (`isset($_SESSION['member_id'])`) และตัวแปรควบคุม (`!isset($hide_bottom_nav)`)
   - แสดงผล `<nav class="member-bottom-nav">` 5 แท็บหลัก (`หน้าแรก`, `จองสนาม`, `แลกของ`, `ประวัติ`, `โปรไฟล์`) พร้อมคำนวณ Active tab อัตโนมัติ
5. **`index.php`**:
   - ปรับแยกการแสดงผล 2 โหมดสมบูรณ์แบบ:
     - **โหมดผู้เยี่ยมชม (Guest Landing):** Hero Badminton Visual Showcase, จุดเด่น 4 ด้าน, อัตราค่าบริการโปร่งใส, แถบ Sticky CTA ด้านล่างบนมือถือ
     - **โหมดสมาชิก (Member Dashboard Home):** สวัสดีทักทายชื่อสมาชิก, ป้ายสถานะ Tier/พอยท์, ตั๋วการจองที่กำลังจะมาถึง (Upcoming Match Ticket Card), 4 เมนูด่วนไอคอนชัดเจน, แถบสถานะสนามสด (Live Court Status Strip), ข่าวสารและโปรโมชั่น
6. **`login.php` & `register.php`**:
   - เพิ่ม Segmented Tab สลับระหว่าง "เข้าสู่ระบบ" และ "สมัครสมาชิก"
   - เพิ่มปุ่มลูกศรย้อนกลับสู่หน้าแรก
   - กำหนด `inputmode="tel"` สำหรับเบอร์โทรศัพท์ เพื่อให้มือถือเปิดแป้นพิมพ์ตัวเลขทันที
   - เพิ่มปุ่มไอคอนดวงตา เปิด-ปิดการมองเห็นรหัสผ่าน
7. **`booking.php`**:
   - กำหนดคลาส `booking-page-body` บน `<body>` และ `$hide_bottom_nav = true;` เพื่อไม่ให้ Bottom Nav ซ้อนทับแถบสรุปการจอง `#stickySummaryBar`
   - เพิ่มแถบสถานะขั้นตอนบนมือถือแบบ Slim Progress Stepper (`.wizard-mobile-stepper`)
   - เพิ่ม Filter Pills สำหรับกรองหมวดหมู่สินค้าและอุปกรณ์เช่าใน Step 4 (`[ทั้งหมด]`, `[สินค้าบริโภค]`, `[อุปกรณ์เช่า]`)
8. **`booking_history.php`**:
   - เพิ่ม Filter Status Pills (`[ทั้งหมด]`, `[รอชำระ/รอตรวจ]`, `[จองสำเร็จ]`, `[ยกเลิก]`)
   - เพิ่มการแสดงผลแบบตั๋วการจองมือถือ (`.history-mobile-cards`) สลับกับตารางแบบ Desktop ผ่าน CSS Media Queries โดยคงฟังก์ชันและปุ่ม Action (ชำระเงิน, ยกเลิกการจอง, ดูใบเสร็จ) ไว้อย่างครบถ้วน 100%
9. **`profile.php`**:
   - อัปเกรดส่วนหัวข้อมูลสมาชิกเป็นบัตรสมาชิกดิจิทัล (Virtual Digital Member Card) พร้อมชุดสี Gradient ตามระดับชั้น Bronze / Silver / Gold แสดงข้อมูลชื่อ เบอร์โทร พอยท์คงเหลือ และปุ่มลัดแลกของรางวัล
10. **`admin/includes/header.php` & `admin/includes/sidebar.php`**:
    - เพิ่มปุ่ม Hamburger Toggle ในแถบ Navbar ด้านบน
    - เพิ่ม Off-canvas Backdrop และปุ่มปิดเมนู (X) ใน Sidebar
11. **`assets/js/member.js`**:
    - เพิ่ม `togglePasswordVisibility(inputId, btn)` สำหรับสลับดูรหัสผ่าน
    - เพิ่ม `filterAddonCategory(category, btn)` สำหรับกรองสินค้าและอุปกรณ์เสริมในหน้าจอง
    - อัปเดต `updateWizardStepperUI(step)` ให้ซิงโครไนซ์ความคืบหน้าของ Stepper ทั้ง Desktop และ Mobile
    - เพิ่ม `filterHistoryStatus(cat, btn)` สำหรับกรองสถานะประวัติการจองทั้งบนตารางและ Mobile Cards
12. **`assets/js/admin.js`**:
    - เพิ่ม `toggleAdminSidebar()` สำหรับควบคุมการเลื่อนเปิด-ปิด Sidebar บนหน้าจอแท็บเล็ตและสมาร์ทโฟน

---

## 2. ระบบเป็นอย่างไร (How the System Works)

### 2.1 สถาปัตยกรรมและการไหลของข้อมูล (Interaction & Logic Flow)
```
[ผู้เยี่ยมชม (Guest)]
       │
       ▼
 ┌─────────────┐       ┌─────────────┐
 │  Landing    │ ────► │ Login / Reg │
 │  (Hero/CTA) │       │ (Segmented) │
 └─────────────┘       └─────────────┘
                              │ (เข้าสู่ระบบสำเร็จ)
                              ▼
                   ┌─────────────────────┐
                   │ Member Logged-In    │
                   │ (Bottom Nav Active) │
                   └─────────────────────┘
                              │
     ┌──────────────┬─────────┼──────────────┬──────────────┐
     ▼              ▼         ▼              ▼              ▼
┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐
│ Member    │ │ Booking   │ │ Rewards   │ │ History   │ │ Profile   │
│ Home Dash │ │ Wizard    │ │ Catalog   │ │ (Mobile   │ │ (Virtual  │
│ (Upcoming)│ │ (Sticky   │ │           │ │  Tickets) │ │  Card)    │
└───────────┘ │  Bar Auto)│ └───────────┘ └───────────┘ └───────────┘
              └───────────┘
```

1. **Member Bottom Navigation Lifecycle:**
   - ตรวจสอบผ่าน PHP Session `$_SESSION['member_id']` ใน `includes/navbar.php`
   - หากผู้ใช้เป็น Guest จะไม่แสดงแถบ Bottom Nav โดยเด็ดขาด
   - เมื่อเข้าสู่ขั้นตอนการจอง (`booking.php`) คลาส `booking-page-body` และตัวแปร `$hide_bottom_nav` จะซ่อน Bottom Nav อัตโนมัติ เพื่อให้ผู้ใช้โฟกัสกับขั้นตอนการจองและใช้แถบ Sticky Summary Bar ได้สะดวกโดยไม่มีปุ่มซ้อนทับกัน
2. **Admin Responsive Drawer Flow:**
   - แยกสิทธิ์และเลย์เอาต์ออกจากฝั่ง Member 100%
   - เมนู Admin จะคงเป็น Fixed Sidebar ซ้ายมือบนหน้าจอ Desktop (กว้าง > 992px)
   - บนหน้าจอหน้ากว้าง <= 992px เมนูจะพับซ่อนด้านซ้าย (Off-canvas) และสามารถกดปุ่ม Hamburger หรือ Backdrop เพื่อเปิด/ปิดได้อย่างราบรื่น

---

## 3. การใช้งานอย่างไร (How to Use / Test)

### 3.1 ขั้นตอนการทดสอบฝั่งสมาชิก (Member Experience)
1. **ทดสอบ Guest Landing Page:**
   - เข้า URL: `http://localhost/ts-pattani/index.php` (ขณะยังไม่ล็อกอิน)
   - ตรวจสอบความถูกต้อง: พบ Hero Showcase, อัตราค่าบริการ, ปุ่ม Sticky ด้านล่างสำหรับมือถือ และต้องไม่มี Member Bottom Nav
2. **ทดสอบ Login / Register:**
   - เข้า URL: `http://localhost/ts-pattani/login.php` หรือ `register.php`
   - สลับแท็บ "เข้าสู่ระบบ" และ "สมัครสมาชิก"
   - ทดสอบกดไอคอนรูปดวงตาเพื่อดู/ซ่อนรหัสผ่าน
   - ตรวจสอบการเปิดแป้นพิมพ์ตัวเลขในช่องกรอกเบอร์โทรศัพท์
3. **ทดสอบ Member Home Dashboard:**
   - ล็อกอินเข้าสู่ระบบ แล้วไปที่หน้าแรก `index.php`
   - ตรวจสอบความถูกต้อง: แสดงการ์ดต้อนรับพร้อมยอดพอยท์, ตั๋วการจองล่าสุด (ถ้ามี), ทางลัด 4 ปุ่ม, แถบสถานะสนามสด, และ Member Bottom Nav แสดงครบ 5 แท็บ
4. **ทดสอบหน้าจองสนามบนมือถือ (`booking.php`):**
   - เข้าสู่หน้าจองสนาม ตรวจสอบว่า Bottom Nav ถูกซ่อน และมี Slim Stepper แสดงขั้นตอนอยู่ด้านบน
   - ไปยังขั้นตอนที่ 4 (บริการเสริม) ทดสอบกดปุ่ม Pill เพื่อกรองหมวดหมู่ `สินค้าบริโภค` และ `อุปกรณ์เช่า`
5. **ทดสอบหน้าประวัติการจอง (`booking_history.php`):**
   - บนหน้าจอมือถือ ตรวจสอบว่ารายการแสดงเป็นการ์ดตั๋ว (Ticket Card)
   - ทดสอบกดปุ่มฟิลเตอร์สถานะ: `ทั้งหมด`, `รอชำระ/รอตรวจ`, `จองสำเร็จ`, `ยกเลิก` รายการจะกรองทันทีโดยไม่ต้องโหลดหน้าเว็บใหม่
6. **ทดสอบหน้าโปรไฟล์ (`profile.php`):**
   - ตรวจสอบบัตรสมาชิกดิจิทัล (Virtual Card) แสดงสีและข้อความตามระดับของสมาชิก (Bronze / Silver / Gold) และมีปุ่มลัดไปยังหน้ารางวัล

### 3.2 ขั้นตอนการทดสอบฝั่งผู้ดูแลระบบ (Admin Responsive Drawer)
1. เข้า URL: `http://localhost/ts-pattani/admin/`
2. ปรับขนาดหน้าจอเบราว์เซอร์ให้แคบลงกว่า 992px (หรือเปิดโหมดมือถือ Responsive Device Mode: iPhone / Android)
3. ตรวจสอบว่า Sidebar ยุบซ่อน และมีปุ่ม Hamburger แสดงขึ้นมาที่มุมซ้ายบน
4. กดปุ่ม Hamburger เมนูจะเลื่อนออกมาจากด้านซ้าย พร้อม Backdrop สีเข้มเบลอ
5. กดปุ่ม (X) หรือคลิกบน Backdrop เมนูจะปิดกลับเข้าไปอย่างราบรื่น

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)

1. **ปัญหาการแย่งพื้นที่หน้าจอบนมือถือในหน้าจองสนาม (`booking.php`):**
   - *สาเหตุ:* หน้าจองมีแถบ Sticky Action Bar (`#stickySummaryBar`) อยู่ที่ `bottom: 0` หากมี Bottom Navigation Bar อีกจะเกิดปัญหาปุ่มทับซ้อน (UI Occlusion)
   - *วิธีแก้:* กำหนดกฎ CSS แบบเฉพาะเจาะจง `body.booking-page-body .member-bottom-nav { display: none !important; }` ร่วมกับตัวแปร `$hide_bottom_nav` ใน PHP ทำให้แถบสรุปการจองใช้งานได้อย่างราบรื่น 100%
2. **ปัญหาตารางกว้างเกินหน้าจอบนมือถือในหน้าประวัติการจอง (`booking_history.php`):**
   - *สาเหตุ:* ตารางประวัติมีหลายคอลัมน์ ทำให้เกิด Horizontal Scrollbar บนหน้าจอมือถือซึ่งใช้งานยาก
   - *วิธีแก้:* สร้างเลย์เอาต์การ์ดตั๋วแบบ Mobile Ticket Cards (`.history-mobile-cards`) เพื่อแสดงผลบนมือถือโดยเฉพาะ และซ่อนตาราง Desktop โดยคงการเรียกใช้ Modal ยกเลิกการจองและ Logic เดิมไว้ครบถ้วน
3. **การควบคุมความสม่ำเสมอของโค้ดตามกฎ Separation of Concerns:**
   - *การตรวจสอบ:* ตรวจสอบผ่าน Script Audit พบว่าไม่มีการเพิ่ม Inline Style (`style="..."`) หรือ `<style>` แท็กใหม่ลงในไฟล์ PHP แม้แต่จุดเดียว โค้ดสไตล์ทั้งหมดถูกจัดเก็บลงใน `global.css`, `style.css` และ `admin.css`
4. **ความเข้ากันได้ของระบบเดิม (System Integrity Guarantee):**
   - ทุกตัวแปรใน PHP, Form actions, API endpoints, ตารางฐานข้อมูล, ระบบแต้ม, ระบบคิดเงิน และสิทธิ์ Session คงเดิมครบถ้วน 100% ไม่มีการดัดแปลง Business Logic เดิม
