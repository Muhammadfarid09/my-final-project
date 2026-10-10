# บันทึกการพัฒนาระบบ: Guest Court & Timeslot Browsing with Auth Gate at Payment
**วันที่:** 2026-10-11  
**ระบบ:** T.S. Pattani Badminton Booking System  
**หัวข้อ:** อนุญาตให้ผู้ใช้ทั่วไป (Guest) ดูตารางสนาม เลือกวัน-เวลา และบริการเสริมได้อิสระ โดยมี Auth Gate ที่ขั้นตอนชำระเงิน (Step 5) พร้อมระบบ In-Page AJAX Authentication เพื่อคงสถานะการเลือกไว้ 100%

---

## 1. ทำอะไรบ้าง (What Was Done)

### 1.1 ไฟล์ที่สร้างใหม่ (New Files)
1. [`actions/login_ajax.php`](file:///c:/xampp/htdocs/ts-pattani/actions/login_ajax.php):
   - เอนด์พอยต์ JSON API สำหรับการเข้าสู่ระบบผ่าน AJAX
   - ตรวจสอบเบอร์โทรและรหัสผ่านจากตาราง `Member`
   - ตรวจสอบสถานะการระงับสิทธิ์ (`member_status`)
   - ผูก Session (`$_SESSION['member_id']`, `$_SESSION['member_name']`, `$_SESSION['member_phone']`) เมื่อสำเร็จ และตอบกลับเป็น JSON `{ status: 'success' }`
2. [`actions/register_ajax.php`](file:///c:/xampp/htdocs/ts-pattani/actions/register_ajax.php):
   - เอนด์พอยต์ JSON API สำหรับการสมัครสมาชิกผ่าน AJAX
   - ตรวจสอบข้อมูลบังคับและเบอร์โทร 10 หลักที่ไม่ซ้ำ
   - ทำ Password Hashing ด้วย `password_hash(..., PASSWORD_DEFAULT)`
   - บันทึกข้อมูลลงตาราง `Member` และเปิดบัญชีพอยท์ในตาราง `Point` (ระดับ Bronze) ภายใต้ Transaction อะตอมิก
   - ผูก Session ให้อัตโนมัติหลังสมัครเสร็จ และตอบกลับเป็น JSON พร้อมข้อมูลสมาชิกใหม่

### 1.2 ไฟล์ที่ได้รับการปรับปรุง (Modified Files)
1. [`booking.php`](file:///c:/xampp/htdocs/ts-pattani/booking.php):
   - ถอด `require_once 'includes/auth_check.php'` ที่เคยบล็อกผู้ใช้ไม่ให้เข้าหน้าจองออก
   - เพิ่มการตรวจสอบ Session แบบ Non-blocking: ตรวจสอบความถูกต้องของสิทธิ์ผู้ใช้ ถ้ามีสิทธิ์ให้ `$is_logged_in = true` ถ้าไม่มีหรือไม่ถูกต้องให้ `$is_logged_in = false` โดยไม่สั่ง `header("Location: login.php")`
   - เพิ่ม `data-logged-in="true|false"` ที่แท็ก `<body>`
   - ใน **Step 5 (สรุปรายการและชำระเงิน)**:
     - แสดง Badge แยกตามสถานะ: ลูกค้าสมาชิก (`.pdpa-badge`) หรือ ผู้ใช้ทั่วไป (`.badge-guest`)
     - เพิ่ม Auth Gate Card (`.guest-auth-gate-card`) พร้อมข้อความคำแนะนำและปุ่ม "เข้าสู่ระบบ" และ "สมัครสมาชิกใหม่"
     - ปุ่มยืนยันการจองด้านล่างปรับเปลี่ยนเป็นปุ่มเรียก Modal เข้าสู่ระบบอัตโนมัติหากยังไม่ได้ล็อกอิน
   - เพิ่ม **Booking Auth Modal (`#bookingAuthModal`)**:
     - หน้าต่างป๊อปอัปสไตล์โมเดิร์น มีแท็บสลับระหว่าง "เข้าสู่ระบบ" และ "สมัครสมาชิกใหม่"
     - ช่องกรอกข้อมูลพร้อม Input Validation และกล่องแจ้งเตือน Alert Box แบบไดนามิก
2. [`assets/css/booking-wizard.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/booking-wizard.css):
   - เพิ่มคลาสสไตล์สำหรับ Guest Gate: `.badge-guest`, `.guest-auth-gate-card`, `.guest-auth-header`, `.guest-auth-icon`, `.guest-auth-title`, `.guest-auth-desc`, `.guest-auth-actions`, `.btn-guest-login`, `.btn-guest-register`
   - เพิ่มคลาสสไตล์สำหรับ Auth Modal: `.booking-auth-modal-card`, `.auth-modal-header`, `.auth-tabs-nav`, `.auth-tab-btn`, `.auth-tab-pane`, `.alert-box-auth`, `.auth-label`, `.auth-input-wrap`, `.auth-input-icon`, `.auth-control`, `.btn-auth-submit`
   - รองรับ Responsive Media Query สำหรับหน้าจอมือถือ (`<= 768px`) ให้จัดเรียงปุ่มแบบเต็มความกว้างและใช้งานง่ายตามหลัก Touch Target (HCI)
3. [`assets/js/member.js`](file:///c:/xampp/htdocs/ts-pattani/assets/js/member.js):
   - ปรับปรุงฟังก์ชัน `handleBookingFormSubmit(event)`: ตรวจสอบ `document.body.dataset.loggedIn === 'true'` หากยังไม่ได้ล็อกอิน ให้ระงับการส่งฟอร์มและเปิด `#bookingAuthModal` ขึ้นมาแทน
   - เพิ่มฟังก์ชัน `openBookingAuthModal(defaultTab)`, `closeBookingAuthModal()`, `switchBookingAuthTab(tabName)`
   - เพิ่มฟังก์ชัน `handleBookingAjaxLogin(event)` และ `handleBookingAjaxRegister(event)`:
     - ป้องกัน Double Submit และแสดง Loading Spinner
     - สื่อสารกับ JSON endpoint หลังสำเร็จจะอัปเดต `document.body.dataset.loggedIn = 'true'`, ปิด Modal และสั่ง `bookingForm.submit()` ต่อทันทีโดยไม่ต้องโหลดหน้าซ้ำ
   - ผูก Event ปิด Modal เมื่อคลิก Backdrop หรือกดปุ่ม `Escape`
4. [`actions/booking_db.php`](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php):
   - เพิ่ม Fallback Guard: หากมีคำขอ POST ส่งเข้ามาโดยที่ `$_SESSION['member_id']` ว่าง จะเก็บข้อมูลฟอร์มลง `$_SESSION['pending_booking']` และรีไดเรกต์ไปที่ `login.php?redirect=booking.php`
   - เมื่อสร้างการจองและล็อกสนามสำเร็จ ให้ล้าง `unset($_SESSION['pending_booking'])`
5. [`login.php`](file:///c:/xampp/htdocs/ts-pattani/login.php) & [`actions/login_db.php`](file:///c:/xampp/htdocs/ts-pattani/actions/login_db.php):
   - รองรับพารามิเตอร์ `redirect` ในฟอร์มและการส่งต่อลิงก์ไปยัง `register.php`
   - เมื่อล็อกอินผ่านฟอร์มธรรมดาสำเร็จ ตรวจสอบค่า `redirect` หรือ `$_SESSION['pending_booking']` หากมีให้ส่งกลับไปหน้า `booking.php`
6. [`register.php`](file:///c:/xampp/htdocs/ts-pattani/register.php) & [`actions/register_db.php`](file:///c:/xampp/htdocs/ts-pattani/actions/register_db.php):
   - รองรับพารามิเตอร์ `redirect` ส่งต่อไปยังหน้าล็อกอินหลังสมัครสำเร็จ
7. [`includes/navbar.php`](file:///c:/xampp/htdocs/ts-pattani/includes/navbar.php):
   - เมื่อผู้ใช้อยู่ในหน้า `booking.php` ลิงก์เข้าสู่ระบบจะส่งพารามิเตอร์ `?redirect=booking.php` ให้อัตโนมัติ

---

## 2. ระบบเป็นอย่างไร (How the System Works)

### 2.1 สถาปัตยกรรมและโฟลว์การทำงาน (System Flow)
```
[ผู้เยี่ยมชมทั่วไป (Guest)]
        │
        ▼
เข้าหน้า booking.php (Non-blocking session check: $is_logged_in = false)
        │
        ├─► Step 1: เลือกวันที่ (ดึงปฏิทินและราคาโปรโมชันประจำวัน)
        ├─► Step 2: เลือกเวลาเริ่มเล่น (ระบบ Matrix สีเขียว/เหลือง/แดง)
        ├─► Step 3: เลือกสนามและจำนวนชั่วโมง (คำนวณราคา real-time)
        ├─► Step 4: เลือกเครื่องดื่ม / เช่าไม้แบด / รองเท้า (บันทึก state ใน JS)
        │
        ▼
    Step 5: สรุปรายการคำนวณยอดเงินรวม (Invoice Breakdown)
        │
        ├─► แสดง Badge "ผู้ใช้งานทั่วไป (ยังไม่เข้าสู่ระบบ)"
        ├─► แสดง Guest Auth Gate Card อธิบายขั้นตอนถัดไป
        │
        ▼
คลิก "เข้าสู่ระบบเพื่อยืนยันและชำระเงิน" หรือ ปุ่มใน Auth Gate Card
        │
        ▼
เปิด In-Page Modal (#bookingAuthModal) (สลับแท็บ Login / Register ได้)
        │
        ├──► ผู้ใช้กรอกเบอร์โทร & รหัสผ่าน
        │        │
        │        ▼ ยิง Fetch POST ไปยัง actions/login_ajax.php หรือ actions/register_ajax.php
        │        │
        │        ▼ สำเร็จ: สร้าง Session ฝั่งเซิร์ฟเวอร์ และอัปเดต document.body.dataset.loggedIn = 'true'
        │
        ▼
ปิด Modal ทันที และสั่ง bookingForm.submit() ส่งข้อมูล Step 1-4 ที่เลือกไว้ทั้งหมด
        │
        ▼
actions/booking_db.php บันทึกลงฐานข้อมูล Booking, Booking_Court_Slot, Rental
        │
        ▼
ล็อกสนาม 15 นาที และรีไดเรกต์ไปยัง payment.php?booking_id=... พร้อมชำระเงิน
```

### 2.2 การรักษาความปลอดภัยและสิทธิ์การใช้งาน
- **Strict Separation of Concerns:** CSS ทั้งหมดถูกจัดสรรไว้ใน `assets/css/booking-wizard.css` ตามหลัก BEM/Scoped ปราศจาก Inline Styles หรือการแทรก `<style>` กลางไฟล์ PHP
- **Zero Data Loss:** ผู้ใช้ที่เลือกสนามและบริการเสริมเสร็จแล้ว ไม่ต้องเริ่มต้นเลือกใหม่เมื่อล็อกอินผ่าน In-Page Modal
- **Double Submission Prevention:** มี Flag `isBookingSubmitting`, `isAjaxLoginSubmitting`, `isAjaxRegisterSubmitting` ควบคุมการกดซ้ำ และปรับเปลี่ยนสถานะปุ่มเป็นปุ่มหมุน (Spinner)
- **Account Integrity:** มีการเช็คสถานะ `ระงับสิทธิ์` ทั้งใน Ajax และ Backend Guard

---

## 3. การใช้งานและการทดสอบ (How to Use / Test)

### 3.1 ขั้นตอนการทดสอบฝั่งผู้ใช้ทั่วไป (Guest Flow)
1. **เข้าสู่หน้าจองสนาม:**
   - เปิดบราวเซอร์ (แนะนำโหมดไม่ระบุตัวตน / Incognito เพื่อให้มั่นใจว่ายังไม่ได้ล็อกอิน)
   - เข้า URL: `http://localhost/ts-pattani/booking.php`
   - *ผลลัพธ์ที่คาดหวัง:* หน้าเว็บโหลดได้ทันที ไม่ถูกเด้งไปหน้า `login.php` และเมนูด้านบนแสดงปุ่ม "เข้าสู่ระบบ"
2. **ดำเนินการเลือกขั้นตอนที่ 1 - 4:**
   - **Step 1:** เลือกวันที่ที่ต้องการจอง
   - **Step 2:** เลือกเวลาเริ่มต้น
   - **Step 3:** เลือกสนามแบดมินตัน
   - **Step 4:** ปรับเพิ่มสินค้าหรืออุปกรณ์เช่า (ถ้าต้องการ)
   - *ผลลัพธ์ที่คาดหวัง:* สเต็ปเปอร์ทำงานลื่นไหล ตารางชั่วโมงแสดงสถานะว่าง/จองแล้ว ยอดเงินคำนวณถูกต้อง
3. **ตรวจสอบขั้นตอนที่ 5 (Invoice & Auth Gate):**
   - ไปที่ Step 5 ตรวจสอบข้อมูลในใบสรุป
   - *ผลลัพธ์ที่คาดหวัง:* มี Badge สีส้มแจ้งเตือนว่า "ผู้ใช้งานทั่วไป (ยังไม่เข้าสู่ระบบ)" และมีกล่องสีน้ำเงิน `guest-auth-gate-card` ปรากฏด้านล่างใบสรุป
4. **ทดสอบการยืนยันการจอง:**
   - กดปุ่ม "เข้าสู่ระบบเพื่อยืนยันและชำระเงิน"
   - *ผลลัพธ์ที่คาดหวัง:* ป๊อปอัป `#bookingAuthModal` แสดงขึ้นมาอย่างนุ่มนวล โดยช่องเบอร์โทรศัพท์ได้รับ Focus อัตโนมัติ
5. **ทดสอบการเข้าสู่ระบบผ่าน Modal:**
   - กรอกเบอร์โทรและรหัสผ่านของสมาชิก (เช่น บัญชีทดสอบที่มีในระบบ)
   - กดปุ่ม "เข้าสู่ระบบและไปชำระเงินทันที"
   - *ผลลัพธ์ที่คาดหวัง:* ปุ่มเปลี่ยนเป็นสปินเนอร์หมุน เมื่อตรวจสอบผ่าน จะแจ้งเตือนสีเขียว ปิดหน้าต่าง และนำผู้ใช้ไปยังหน้า `payment.php?booking_id=...` โดยข้อมูลสนามและสินค้าที่เลือกไว้ใน Step 1-4 ถูกบันทึกลงฐานข้อมูลอย่างสมบูรณ์

### 3.2 ขั้นตอนการทดสอบสมาชิกเดิมที่ล็อกอินอยู่แล้ว (Authenticated Flow)
1. เข้าสู่ระบบตามปกติที่ `http://localhost/ts-pattani/login.php`
2. เข้าหน้า `booking.php`
3. *ผลลัพธ์ที่คาดหวัง:* ทำการจองตั้งแต่ Step 1 ถึง Step 5 โดยใน Step 5 จะแสดงชื่อสมาชิก ไม่แสดง Auth Gate Card และปุ่มสุดท้ายจะยืนยันการจองได้ทันทีโดยไม่ต้องเปิด Modal ซ้ำ

---

## 4. ปัญหาที่พบและการแก้ไข (Issues Encountered & Resolved)

1. **ปัญหาเดิม (Root Cause):**
   - ไฟล์ `booking.php` มีคำสั่ง `require_once 'includes/auth_check.php';` ที่หัวไฟล์ ซึ่งบังคับให้ผู้ใช้ที่ไม่ล็อกอินถูกเตะออกจากหน้าทันที ทำให้ลูกค้าใหม่ไม่สามารถดูตารางเวลาและโปรโมชันสนามได้
   - *วิธีแก้:* เปลี่ยนเป็นการตรวจสอบ Session แบบ Non-blocking ใน `booking.php` โดยให้หน้าเว็บแสดงผลได้สำหรับทุกคน และนำจุดตรวจสิทธิ์ (Auth Gate) ไปวางไว้ที่ Step 5 ก่อนส่งข้อมูล
2. **ความเสี่ยงข้อมูลการเลือกสูญหาย (Data Loss during Page Reload):**
   - หากใช้วิธี Redirect ผู้ใช้ไปที่ `login.php` ข้อมูลที่เลือกไว้ใน Wizard (JavaScript Memory) จะหายไปทั้งหมด
   - *วิธีแก้:* พัฒนา In-Page Auth Modal พร้อม Ajax JSON Endpoints (`actions/login_ajax.php` และ `actions/register_ajax.php`) เพื่อให้ผู้ใช้สามารถล็อกอินหรือสมัครสมาชิกได้เสร็จสิ้นภายในหน้าเดิม จากนั้น JavaScript จะส่งฟอร์มต่อให้ทันที
3. **การรักษาความเข้ากันได้ย้อนหลัง (Backward Compatibility & Fallback):**
   - หากผู้ใช้ปิดการทำงานของ JavaScript หรือเข้าผ่านลิงก์ฟอร์มโดยตรง
   - *วิธีแก้:* เพิ่ม Backend Guard ใน `actions/booking_db.php` เก็บค่าลง `$_SESSION['pending_booking']` และรองรับพารามิเตอร์ `?redirect=booking.php` ใน `login.php`, `register.php`, และ `actions/login_db.php`
