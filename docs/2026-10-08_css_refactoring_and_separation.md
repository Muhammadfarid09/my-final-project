# เอกสารบันทึกการทำงานประจำวัน: การแยกและจัดระเบียบ CSS ฝั่งผู้ใช้ (CSS Refactoring & Style Separation)
**วันที่:** 8 ตุลาคม 2026 (2026-10-08)  
**โครงการ:** ระบบจัดการสนามแบดมินตัน T.S. Pattani  
**ผู้รับผิดชอบ:** Antigravity (AI Coding Assistant)  

---

## 1. วัตถุประสงค์ (Objective)
ก่อนเริ่มขั้นตอนการตกแต่งและยกระดับ UI/UX ตามมาตรฐาน HCI อย่างเต็มรูปแบบ จำเป็นต้องเคลียร์หนี้ทางเทคนิค (Technical Debt) โดยการ:
1. กำจัดแท็ก `<style>` ที่ฝังอยู่ในไฟล์ PHP/HTML ฝั่งผู้ใช้งานทั้งหมด (100%)
2. กำจัด Inline Style (`style="..."`) ที่กระจัดกระจายอยู่ใน Markup ออกทั้งหมด
3. แยกสไตล์ส่วนกลางและสไตล์เฉพาะส่วนเข้าไปจัดกลุ่มอย่างเป็นระเบียบใน:
   - `assets/css/global.css` (สไตล์ Utility, Status Badges, Form Inputs, Global Helpers)
   - `assets/css/style.css` (สไตล์เฉพาะโมดูลสำหรับฝั่งสมาชิก/ลูกค้า)
4. แปลง Dynamic Style ที่เคยคำนวณจาก PHP หรือ Database ให้เป็น Semantic CSS Classes (เช่น Tier Membership Badges, Point Transaction Badges) โดยคงรูปลักษณ์และพฤติกรรมเดิมไว้ 100%

---

## 2. รายการไฟล์ที่ดำเนินการ (Files Modified & Refactored)

### 2.1 ไฟล์สไตล์ชีตส่วนกลาง (Centralized Stylesheets)
1. **`assets/css/global.css`**:
   - เพิ่ม Utility Classes สำหรับหน้ายืนยันตัวตนและการจัดเลย์เอาต์: `.page-body`, `.d-none`, `.auth-card-sm`, `.auth-card-md`, `.auth-header-mb`, `.auth-label-row`, `.auth-label-mb-0`, `.auth-forgot-link`, `.divider-dashed`, `.table-responsive`
2. **`assets/css/style.css`**:
   - เพิ่มโมดูล CSS ครบทุกหน้าของผู้ใช้ รวมกว่า 350 บรรทัด ได้แก่:
     - **Customer Chat:** `.chat-page-wrapper`, `.chat-container`, `.chat-card`, `.chat-header`, `.chat-body`, `.chat-bubble`, `.chat-bubble.admin`, `.chat-bubble.member`, `.chat-form`, `.chat-input-group`, `.chat-input`, `.chat-empty-state`, ฯลฯ
     - **Promotions & News:** `.news-page-wrapper`, `.news-card`, `.news-img`, `.news-img-placeholder`, `.news-badge-promo`, `.news-badge-general`, `.news-body`, `.news-modal-*`, `.news-empty-state`, ฯลฯ
     - **Booking History & Cancel Modal:** `.history-wrapper`, `.history-card`, `.modal-cancel-box`, `.modal-cancel-notice`, `.modal-refund-account-box`, `.modal-actions-right`, `.btn-modal-dismiss`, ฯลฯ
     - **Member Profile & Points History:** `.profile-container`, `.profile-tier-badge` (`.tier-bronze`, `.tier-silver`, `.tier-gold`), `.profile-card`, `.point-type-badge` (`.point-plus`, `.point-minus`), `.profile-point-table`, ฯลฯ
     - **Payment & Upload Dropzone:** `.payment-page-container`, `.payment-countdown-card`, `.payment-countdown-subtitle`, `.payment-countdown-val`, `.payment-grid-two-cols`, `.payment-qr-wrapper`, `.payment-qr-placeholder`, `.payment-qr-icon`, `.payment-bank-title`, `.payment-account-row`, `.payment-account-number`, `.payment-account-holder`, `.payment-divider`, `.payment-total-row`, `.payment-total-val`, `.payment-upload-label`, `.payment-file-input`, `.payment-upload-icon`, `.payment-upload-text`, `.payment-upload-hint`, `.payment-file-info`, `.payment-client-error`, `.btn-payment-submit`

### 2.2 ไฟล์ฝั่งผู้ใช้งานที่ได้รับการ Refactor (User-Facing Frontend Files)
1. **`chat.php`**: ลบแท็ก `<style>` ฝังในไฟล์ (221 บรรทัด) และลบ Inline styles ทั้งหมด -> อ้างอิงผ่านคลาสใน `style.css`
2. **`promotions.php`**: ลบแท็ก `<style>` ฝังในไฟล์ (172 บรรทัด) และลบ Inline styles ทั้งหมด -> อ้างอิงผ่านคลาสใน `style.css`
3. **`booking_history.php`**: ลบแท็ก `<style>` ฝังในไฟล์ (77 บรรทัด) และลบ Inline styles ใน Cancel Modal -> ย้ายค่าเริ่มต้น `display: none;` เข้า CSS
4. **`profile.php`**: ลบแท็ก `<style>` ฝังในไฟล์ (62 บรรทัด) และ Inline styles สำหรับ Member Tier Badges / Point Badges -> แปลงเป็น `$tier_class` (`tier-bronze`, `tier-silver`, `tier-gold`) และ `$point_class` (`point-plus`, `point-minus`)
5. **`payment.php`**: ลบ Inline styles ทั้งหมด 18 จุด (ตัวเลขนับถอยหลัง, กล่อง QR Code, Dropzone, ข้อความแจ้งเตือน, ปุ่มยืนยัน) -> ใช้ Semantic Classes
6. **`login.php`**: ลบ Inline styles ทั้งหมด 7 จุด (ความกว้างการ์ด, ระยะห่างหัวข้อ, บล็อกแจ้งเตือน Alert, แถวรหัสผ่าน/ลืมรหัสผ่าน) -> ใช้ Utility Classes
7. **`forgot_password.php`**: ลบ Inline styles ทั้งหมด 4 จุด (ความกว้างการ์ด, บล็อก Alert, เส้นประคั่น) -> ใช้ Utility Classes
8. **`register.php`**: ลบ Inline style 1 จุด (`style="padding-left: 40px;"` ในเพศ) -> ใช้ความสามารถเดิมของ `.input-group .form-control`
9. **`index.php`**: ลบ Inline styles 2 จุด ในกล่องแสดงสถานะเมื่อไม่มีข่าวสาร -> ใช้ `.news-empty-state` และ `.news-empty-icon`
10. **`booking.php`**: ตรวจสอบพบความสะอาดตามมาตรฐานแล้ว (0 `<style>`, 0 Inline Style)
11. **`rewards.php`**: ตรวจสอบพบความสะอาดตามมาตรฐานแล้ว (0 `<style>`, 0 Inline Style)
12. **`includes/navbar.php`**: ตรวจสอบพบความสะอาดตามมาตรฐานแล้ว (0 `<style>`, 0 Inline Style)

---

## 3. ผลการตรวจสอบและยืนยันผล (Verification & Quality Assurance)

### 3.1 สรุปการตรวจสอบ Audit Script
ผลการรันการตรวจสอบไฟล์ทั้ง 12 ไฟล์ด้วย PHP Automated Pattern Audit:
```
=== USER FRONTEND STYLE AUDIT RESULTS ===
booking.php               | <style> tags: 0 | inline styles: 0
booking_history.php       | <style> tags: 0 | inline styles: 0
chat.php                  | <style> tags: 0 | inline styles: 0
forgot_password.php       | <style> tags: 0 | inline styles: 0
index.php                 | <style> tags: 0 | inline styles: 0
login.php                 | <style> tags: 0 | inline styles: 0
payment.php               | <style> tags: 0 | inline styles: 0
profile.php               | <style> tags: 0 | inline styles: 0
promotions.php            | <style> tags: 0 | inline styles: 0
register.php              | <style> tags: 0 | inline styles: 0
rewards.php               | <style> tags: 0 | inline styles: 0
includes/navbar.php       | <style> tags: 0 | inline styles: 0
==========================================
SUCCESS: 100% of user-facing files are completely free of embedded & inline styles!
```

### 3.2 การตรวจสอบไวยากรณ์ PHP (PHP Linting)
ผ่านการตรวจสอบไวยากรณ์ด้วย `php -l` ครบทุกไฟล์โดยไม่มีข้อผิดพลาด (Zero syntax errors detected).

---

## 4. โครงสร้างการรายงานผลการทำงาน 4 ส่วนบังคับ (Mandatory 4-Section Report)

### 4.1 ทำอะไรบ้าง (What Was Done)
1. ตรวจสอบค้นหาและรวบรวมรายการแท็ก `<style>` ที่ฝังอยู่ในโค้ด (~532 บรรทัด) และ Inline styles (`style="..."` 136 จุด) ในไฟล์ฝั่งผู้ใช้งานทั้งระบบ
2. ออกแบบและเพิ่มคลาสสไตล์ใหม่ลงใน `assets/css/style.css` และ `assets/css/global.css` เพื่อรองรับคอมโพเนนต์ต่างๆ อย่างเป็นระบบ
3. ทำการ Refactor ไฟล์ PHP ฝั่งผู้ใช้งานจำนวน 11 ไฟล์ ได้แก่ `chat.php`, `promotions.php`, `booking_history.php`, `profile.php`, `payment.php`, `login.php`, `forgot_password.php`, `register.php`, และ `index.php` โดยแปลงสไตล์ฝังตัวเป็น Semantic CSS Classes
4. จัดการ Dynamic Style ใน PHP (เช่น ระดับสมาชิก Bronze/Silver/Gold และประเภทธุรกรรมแต้ม บวก/ลบ) โดยแปลงค่าให้กลายเป็น CSS Classes แทนการพิมพ์ Style attribute โดยตรง
5. ตรวจสอบความถูกต้องของไวยากรณ์ PHP (`php -l`) และรันสคริปต์ตรวจสอบพบว่าไฟล์ฝั่งผู้ใช้ทั้งหมด 100% ปลอดแท็ก `<style>` และ Inline styles

### 4.2 ระบบเป็นอย่างไร (How the System Works)
1. **การแยกหน้าที่อย่างชัดเจน (Separation of Concerns):** โค้ด PHP/HTML ฝั่ง Frontend มีหน้าที่เฉพาะการจัดโครงสร้างเนื้อหา (Markup) และประมวลผลข้อมูลทางธุรกิจ ส่วนการนำเสนอ (Presentation) ถูกแยกไปไว้ในไฟล์ `.css` ภายนอกทั้งหมด
2. **ประสิทธิภาพในการโหลดแคช (Browser Caching):** เบราว์เซอร์สามารถดึงและบันทึกแคชไฟล์ `style.css` และ `global.css` ได้อย่างมีประสิทธิภาพ ช่วยลดปริมาณข้อมูลที่ต้องส่งผ่านเครือข่ายในการเปิดแต่ละหน้า
3. **ความเข้ากันได้กับการจัดการผ่าน JavaScript (DOM Manipulation):** สไตล์เริ่มต้นที่ซ่อนอยู่ เช่น `.preview-container` หรือ `.modal-cancel-notice` ถูกตั้งค่า `display: none;` ใน CSS อย่างถูกต้อง เมื่อ JavaScript (เช่น `member.js`) มีการสั่งงาน ก็สามารถสลับแสดงผลผ่านคลาสหรือ DOM property ได้อย่างลื่นไหล
4. **ความพร้อมต่อการปรับปรุง UI/UX (HCI Ready):** ระบบพร้อมสำหรับการปรับธีม, การทำ Color Contrast, การปรับ Font Scale และ Responsive Layout ในขั้นตอนถัดไป โดยแก้ไขที่จุดศูนย์กลางเพียงแห่งเดียว

### 4.3 การใช้งานอย่างไร (How to Use / Test)
1. **การทดสอบหน้าจอฝั่งสมาชิก (Member Views):**
   - **เข้าสู่ระบบ / สมัครสมาชิก / ลืมรหัสผ่าน:** ไปที่ `http://localhost/ts-pattani/login.php`, `register.php`, `forgot_password.php` ตรวจสอบความกว้างของฟอร์ม ช่องกรอกข้อมูล และการแสดงผล Alert ข้อความแจ้งเตือน
   - **หน้าแรกและข่าวสาร:** ไปที่ `http://localhost/ts-pattani/index.php` และ `promotions.php` ตรวจสอบการ์ดโปรโมชั่น ป้ายแท็กประเภทข่าว และ Modal แสดงรายละเอียดข่าว
   - **หน้าแชทกับเจ้าหน้าที่:** ไปที่ `http://localhost/ts-pattani/chat.php` ตรวจสอบกล่องแชท บับเบิ้ลข้อความฝั่งลูกค้า (สีน้ำเงิน) และฝั่งแอดมิน (สีเทา)
   - **หน้าประวัติการจองและยกเลิก:** ไปที่ `http://localhost/ts-pattani/booking_history.php` ตรวจสอบตารางการจอง และทดลองกดปุ่มยกเลิกการจองเพื่อดู Pop-up Modal ยืนยันการยกเลิก
   - **หน้าโปรไฟล์สมาชิกและประวัติแต้ม:** ไปที่ `http://localhost/ts-pattani/profile.php` ตรวจสอบป้ายระดับสมาชิก (Bronze/Silver/Gold) และตารางประวัติการรับ/ใช้แต้ม
   - **หน้าชำระเงินและแนบสลิป:** ไปที่ `http://localhost/ts-pattani/payment.php?booking_id=XX` ตรวจสอบตัวเลขนับถอยหลัง กล่อง QR Code ช่องสรุปยอดเงิน และ Dropzone สำหรับลากวางสลิป
2. **ผลลัพธ์ที่คาดหวัง (Expected Results):** หน้าจอทุกหน้ายังคงความสวยงาม จัดวางเลย์เอาต์ และมีฟังก์ชันการทำงานสมบูรณ์ 100% เหมือนเดิมทุกประการ โดยไม่มีการบิดเบี้ยวของ UI

### 4.4 ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)
1. **ปัญหาการจัดการ Dynamic Style จาก PHP:**
   - *เดิม:* ใน `profile.php` มีการใช้ `$badge_style = "background-color: ...; color: ...;"` แล้วแทรกใน `style="<?php echo $badge_style; ?>"`
   - *วิธีแก้:* แปลงตัวแปรเป็นชื่อคลาส `$tier_class` (`tier-bronze`, `tier-silver`, `tier-gold`) และเรียกใช้ `.profile-tier-badge.tier-gold` ใน `style.css` ทำให้โครงสร้างโค้ดสะอาดและจัดการง่าย
2. **ปัญหาความไม่สอดคล้องของบรรทัด (Line Ending CRLF vs LF):**
   - *ปัญหา:* ไฟล์บางไฟล์ในระบบปฏิบัติการ Windows มีการผสมกันของ CRLF และ LF ทำให้เครื่องมือแก้ไขโค้ดค้นหาตำแหน่งข้อความไม่ตรงจุด
   - *วิธีแก้:* เขียนสคริปต์ปรับ Normalization เป็น LF อย่างปลอดภัยก่อนดำเนินการแก้ไข
3. **ปัญหาการทำงานร่วมกับ JavaScript (Display Toggle):**
   - *ปัญหา:* การลบ `style="display: none;"` อาจทำให้ Element ปรากฏขึ้นมาก่อนหาก CSS ไม่ได้ระบุไว้
   - *วิธีแก้:* กำหนด `display: none;` ในคลาส CSS ของ Element นั้นๆ อย่างชัดเจน เช่น `.modal-cancel-notice`, `.modal-refund-account-box`, และ `.preview-container` เพื่อให้ฟังก์ชันของ `member.js` ทำงานได้อย่างราบรื่น
