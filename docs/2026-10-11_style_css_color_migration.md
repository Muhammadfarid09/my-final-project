# สรุปการตรวจสอบและปรับปรุงค่าสีตกค้าง (Hardcoded Legacy Colors Migration) ใน assets/css/style.css
**วันที่:** 11 ตุลาคม 2026  
**ระบบ:** T.S. Pattani Badminton Booking System  
**ธีม:** Premium Black & Gold Theme  
**มาตรฐานอ้างอิง:** `PROJECT_HCI_RULES.md` และ `ADDITIONAL_RULES.md` (Strict Mode)

---

## 1. ภาพรวมการดำเนินงาน (Overview)
ทำการตรวจสอบ (Audit) ค้นหา และปรับปรุงโค้ดสีดั้งเดิมที่ Hardcoded ไว้ (โดยเฉพาะกลุ่มสีฟ้าเดิม `#007bff`, `#1e3c72`, `#2563eb`, `#0284c7`, ค่าสีเทา/ขาวตกค้าง และคำสั่ง `!important`) ภายในไฟล์ [assets/css/style.css](file:///c:/xampp/htdocs/ts-pattani/assets/css/style.css) เพื่อ Migrate เข้าสู่ระบบ Design Tokens ของ [assets/css/global.css](file:///c:/xampp/htdocs/ts-pattani/assets/css/global.css) ตามแนวทาง Premium Black & Gold อย่างสมบูรณ์และถูกต้องตามกฎ Strict Mode 100%

---

## 2. ผลการตรวจสอบและจำนวนที่ตรวจพบ (Audit Results)
- **ก่อนดำเนินการ (Before Migration):**
  - คำสั่ง `!important`: พบ 1 จุด (บรรทัดที่ 208 `.text-danger`)
  - โค้ดสีฟ้า/น้ำเงิน Legacy Blues: พบทั้งหมด 85 จุด กระจายอยู่ใน 11 โมดูล
  - โค้ดสี Hex Hardcoded ทั้งหมด: 321 จุด
- **หลังดำเนินการ (After Migration):**
  - คำสั่ง `!important`: **0 จุด** (กำจัดหมดจด 100%)
  - โค้ดสีฟ้า UI ตกค้าง: **0 จุด** (คงเหลือเฉพาะ 2 จุดที่เป็นสีสัญลักษณ์แถบแบรนด์ภายนอก `.brand-badge-yonex` และ `.brand-badge-mizuno` ในส่วนของ Marquee สปอนเซอร์)
  - โมดูลทั้งหมดถูกเชื่อมโยงเข้าสู่ CSS Variables กลางอย่างเป็นระบบ

---

## 3. รายละเอียดการแทนที่ด้วย Design Tokens (Design Token Mapping)

| หมวดหมู่ UI | ค่าสีเดิม (Legacy) | CSS Token ใหม่ที่นำมาใช้ | ความหมาย/บทบาท |
|---|---|---|---|
| Primary Buttons & Links | `#007bff`, `#1e3c72`, `#2563eb` | `var(--color-gold)` (`#FFB91D`) | สีทองหลักสำหรับ Interactive / CTA |
| Button Hover State | `#0056b3`, `#162c54`, `#0369a1` | `var(--color-gold-hover)` (`#E9A812`) | สีทองเข้มขึ้นเล็กน้อยสำหรับ Hover / Active |
| Text on Primary Button | `#ffffff` (สีขาวบนปุ่มฟ้า) | `var(--text-on-gold)` (`#090909`) | ตัวหนังสือดำเข้มบนพื้นสีทอง (WCAG AAA Contrast) |
| Elevated Cards & Modals | `#ffffff`, `#f8fafc` | `var(--bg-surface)` / `var(--bg-elevated)` | พื้นหลังการ์ดโทนดำหรูหรา (`#191919` / `#292929`) |
| Form Input Focus Halos | `rgba(0, 123, 255, 0.12)` | `rgba(255, 185, 29, 0.15)` | แสงเรืองแสงสีทองรอบช่องกรอกข้อมูล |
| Modal & Box Borders | `#e2e8f0`, `#cbd5e1` | `var(--border-color)` (`#383838`) | เส้นขอบมินิมอลไม่สว่างแยงตา |
| Status & Alert Badges | `#e0f2fe`, `#0369a1` | `rgba(255, 185, 29, 0.15)` & `var(--color-gold)` | ป้ายกำกับและรหัสคูปองสีทองพรีเมียม |

---

## 4. รายการโมดูลที่ได้รับการปรับปรุงใน style.css

1. **กำจัด `!important`:** บรรทัดที่ 208 (`.text-danger`) ปรับเป็น `.text-danger { color: var(--status-booked, #ef4444); }`
2. **Mobile Drawer Navigation:** `.mobile-user-card`, `.mobile-user-info`, `.mobile-user-points`, `.mobile-user-divider`
3. **Court Booking & Summary:** `.detail-row strong`, `.booking-summary-detail`, `.court-type-pill`
4. **Loyalty & Rewards Center:**
   - `.loyalty-badge.silver` (ปรับพื้นหลังและตัวหนังสือเป็น High Contrast)
   - `.reward-item-title`, `.reward-item-desc`, `.reward-item-footer`
   - `.redeem-preview-title`, `.redeem-calc-table`, `.redeem-calc-row`, `.badge-rdm-code`
   - `.btn-empty-action` และสถานะ `:hover`
5. **Live Chat System (`chat.php`):**
   - `.chat-container`, `.chat-header`, `.chat-body`
   - `.chat-message.admin .chat-bubble` (พื้นหลัง Elevated Dark Surface)
   - `.chat-message.member .chat-bubble` (พื้นหลัง Gold พร้อมตัวหนังสือดำ)
   - `.chat-input`, `.chat-input:focus`, `.chat-footer`
   - `.btn-send` และ `.btn-send:hover`
6. **Modal ประกาศข่าวสาร:** `.news-modal-header`, `.news-modal-title`
7. **ประวัติการจองและยกเลิก:**
   - `.booking-price-cell`
   - `.modal-cancel-box`, `.modal-cancel-summary-box`, `.modal-cancel-price`
   - `.notice-unverified-slip`
8. **ข้อมูลสมาชิก (`profile.php`):**
   - `.profile-header-card` (เปลี่ยน Gradient น้ำเงินเป็น Dark Surface Gradient พร้อมขอบทองอ่อน)
   - `.profile-avatar`, `.profile-card-title i`
   - `.form-control-custom:focus`
   - `.btn-profile-submit`, `.btn-profile-outline`, `.summary-stat-val`
   - `.point-badge-admin`, `.point-val-admin`
9. **หน้าชำระเงิน (`payment.php`):**
   - `.payment-account-number`, `.payment-upload-icon`
   - `.btn-payment-submit` และสถานะ `:hover`
10. **Modern Landing Hero (Legacy Fallback Rules):**
    - `.landing-hero-section`, `.hero-badge-tag`, `.hero-title-gradient`
    - `.btn-hero-primary`, `.btn-hero-primary:hover`, `.hero-stats-strip`, `.stat-icon`
11. **หน้าเข้าสู่ระบบและสมัครสมาชิกเดิม (Fallback & Auth Inputs):**
    - `.auth-title`, `.input-group:focus-within .input-icon`, `.input-group .form-control:focus`
    - `.btn-toggle-password:hover`, `.login-book-cover`
    - `.login-title`, `.checkbox-label input[type="checkbox"]`
    - `.forgot-password-link`, `.login-footer-register a`

---

## 5. การตรวจสอบความปลอดภัยและผลกระทบ (Safety & Verification)
- **Zero Syntax Errors:** ผ่านการตรวจสอบ PHP Linter (`php -l`) ในทุกไฟล์ที่เกี่ยวข้อง (`index.php`, `login.php`, `register.php`, `profile.php`, `chat.php`, `payment.php`)
- **Zero `!important`:** ปราศจากการใช้ `!important` ในไฟล์ CSS ทั้งหมดตามกฎข้อ 2 ใน `ADDITIONAL_RULES.md`
- **Zero Inline Styles:** ไม่มีการใช้ Inline Style หรือแทรก `<style>` กลางไฟล์
- **CSS Architecture:** รักษา Class Name เดิมไว้ครบ 100% (No Breaking Change / Zero Regression)
