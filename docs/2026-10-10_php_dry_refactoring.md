# บันทึกการปรับปรุงโค้ด PHP ตามหลัก DRY (Don't Repeat Yourself) และความปลอดภัย
**วันที่บันทึก:** 10 ตุลาคม 2026  
**ขอบเขตงาน:** รวมศูนย์ Logic ซ้ำซ้อน, จัดระเบียบ Session & Auth Guard ทั้งฝั่งสมาชิกและแอดมิน, ปรับปรุงการตรวจสอบสถานะการจอง (Booking Status) และรวมศูนย์ Helper Functions

---

## 1. วัตถุประสงค์ของการปรับปรุง (Objectives)
1. **ขจัดคำสั่ง SQL และ Business Logic ที่ซ้ำซ้อน (DRY Principle):** คำนวณราคาคอร์ทตามวันในสัปดาห์ (DOW), ตรวจสอบสล็อตเวลาการจองชนกัน (Court Availability & Overlap), การสะสมพอยท์และเลื่อนระดับสมาชิก (Tier Progression), และการคืนสต็อกอุปกรณ์เช่าเมื่อยกเลิกการจอง
2. **รวมศูนย์ระบบตรวจสอบสิทธิ์และเซสชัน (Centralized Auth & Security Guard):**
   - ฝั่งผู้ใช้ (Member): รวมศูนย์ที่ `includes/auth_check.php` พร้อมตรวจสอบสถานะ `Member.member_status = 'ระงับสิทธิ์'` แบบ Real-time และรองรับ AJAX/JSON requests
   - ฝั่งผู้ดูแลระบบ (Admin): ปรับปรุง `admin/includes/auth_check.php` ให้รองรับการ Redirect อย่างยืดหยุ่น และรองรับ API/JSON endpoint
3. **จัดระเบียบและแก้ปัญหาความขัดแย้งของสถานะการจอง (Booking Status Harmonization):**
   - แก้ไขโค้ดใน `booking_history.php` และส่วนที่เกี่ยวข้องที่เคยมีเงื่อนไขสถานะที่ไม่มีอยู่จริงใน ENUM ฐานข้อมูล (`'ชำระเงินแล้ว'`, `'อนุมัติแล้ว'`) โดยอิงตาม Schema จริงของตาราง `Booking.booking_status` คือ `'รอตรวจสอบ'`, `'จองแล้ว'`, `'ยกเลิก'` พร้อมใช้ค่าคงที่ (`STATUS_BOOKING_*`)
4. **ความปลอดภัยของระบบอัปโหลดไฟล์ (Secure File Upload):** รวมศูนย์ฟังก์ชันตรวจสอบไฟล์รูปภาพ (ขนาด, นามสกุล, MIME type, directory auto-create, unique filename) ผ่าน `handle_secure_image_upload()`

---

## 2. รายการไฟล์ที่สร้างและปรับปรุง (Files Modified & Created)

### 2.1 ไฟล์ที่สร้างขึ้นใหม่ (New Files)
- `includes/functions.php`: คลัง Helper Functions ส่วนกลางของระบบ ประกอบด้วย:
  - ค่าคงที่สถานะการจอง: `STATUS_BOOKING_PENDING`, `STATUS_BOOKING_CONFIRMED`, `STATUS_BOOKING_CANCELLED`
  - ฟังก์ชันจัดรูปแบบภาษาไทย: `format_thai_date()`, `format_thai_datetime()`, `get_thai_months()`, `get_thai_day_names()`
  - ฟังก์ชันจัดรูปแบบราคา: `format_baht()`
  - ฟังก์ชันคำนวณราคาคอร์ทแบบ Dynamic ตามวันในสัปดาห์: `calculate_court_hourly_rate()`
  - ฟังก์ชันตรวจสอบสนามว่าง/เวลาชนกัน: `is_court_available()`
  - ฟังก์ชันคำนวณและเลื่อนระดับพอยท์สมาชิก: `award_member_points()`
  - ฟังก์ชันยกเลิกการจองและคืนสต็อกอุปกรณ์เช่าอัตโนมัติ: `cancel_booking_and_restore_stock()`
  - ฟังก์ชันอัปโหลดรูปภาพมาตรฐานความปลอดภัยสูง: `handle_secure_image_upload()`
- `includes/auth_check.php`: มิดเดิลแวร์ตรวจสอบเซสชันและสถานะสมาชิก รองรับทั้งหน้าเว็บปกติและ AJAX/JSON

### 2.2 ไฟล์ที่ปรับปรุง (Modified Files)
- `config/config.php`: เรียก `require_once __DIR__ . '/../includes/functions.php';` อัตโนมัติเพื่อให้ทุกสคริปต์ในระบบสามารถเรียกใช้ Helper functions ได้ทันที
- `booking.php`: สลับมาใช้ `require_once 'includes/auth_check.php';`
- `booking_history.php`: สลับมาใช้ `require_once 'includes/auth_check.php';` และล้าง Phantom Statuses ('ชำระเงินแล้ว', 'อนุมัติแล้ว') ให้ตรงตาม DB ENUM
- `payment.php`: สลับมาใช้ `require_once 'includes/auth_check.php';`
- `profile.php`: สลับมาใช้ `require_once 'includes/auth_check.php';`
- `rewards.php`: สลับมาใช้ `require_once 'includes/auth_check.php';`
- `chat.php`: สลับมาใช้ `require_once 'includes/auth_check.php';`
- `actions/booking_db.php`: ใช้ `auth_check.php` และเรียก `calculate_court_hourly_rate()`
- `actions/cancel_booking_db.php`: ใช้ `auth_check.php` และเรียก `cancel_booking_and_restore_stock()`
- `actions/get_court_matrix.php`: เรียก `get_thai_day_names()` และ `calculate_court_hourly_rate()`
- `actions/payment_db.php`: ใช้ `auth_check.php` และเรียก `handle_secure_image_upload()`
- `actions/profile_edit_db.php`: สลับมาใช้ `auth_check.php`
- `actions/redeem_reward_db.php`: สลับมาใช้ `auth_check.php`
- `actions/send_chat_db.php`: สลับมาใช้ `auth_check.php`
- `actions/get_chat_messages.php`: สลับมาใช้ `auth_check.php` พร้อมส่ง Header JSON
- `admin/includes/auth_check.php`: ปรับปรุงให้คำนวณ Path ไปยังหน้า `login.php` อัตโนมัติ และตรวจจับ AJAX เพื่อส่งผลลัพธ์แบบ JSON
- `admin/includes/auto_cancel.php`: ปรับให้ครอบฟังก์ชัน `trigger_auto_cancel()` โดยเรียก `cancel_booking_and_restore_stock()`
- `admin/actions/cancellation_action_db.php`: ปรับให้เรียก `cancel_booking_and_restore_stock()` แทนการเขียนลูปคืนสต็อกอุปกรณ์ซ้ำ
- `admin/actions/chat_messages_db.php`: สลับมาใช้ `admin/includes/auth_check.php`
- `admin/actions/court_edit_db.php`: แก้ไขจากเดิมที่ Redirect ไป `../../login.php` ผิดทาง ให้ใช้ `admin/includes/auth_check.php`
- `admin/actions/export_csv.php`: สลับมาใช้ `admin/includes/auth_check.php`
- `admin/actions/payment_verify_db.php`: ใช้ `award_member_points()` สำหรับการสะสมคะแนนเมื่อแอดมินอนุมัติสลิป
- `admin/actions/pos_checkout_db.php`: ใช้ `calculate_court_hourly_rate()` และ `award_member_points()` สำหรับการคิดราคาและแจกคะแนนหน้าร้าน POS

---

## 3. ผลการทดสอบและตรวจสอบความปลอดภัย (Verification & Testing)
1. **PHP CLI Syntax Lint:** ผ่านการตรวจสอบไวยากรณ์ด้วย `php -l` ทุกไฟล์ 100% ไม่มีข้อผิดพลาด
2. **HTTP Endpoints Response:**
   - `index.php` -> HTTP 200 OK
   - `login.php` -> HTTP 200 OK
   - `booking.php` (Guest) -> HTTP 302 Found (Redirect สู่ `login.php`)
   - `admin/login.php` -> HTTP 200 OK
   - `admin/index.php` (Guest) -> HTTP 302 Found (Redirect สู่ `admin/login.php`)
3. **Database & Data Integrity:** คงโครงสร้างตารางเดิมทั้งหมด รองรับ Transaction ปลอดภัย ปราศจาก SQL Injection (ใช้ Prepared Statements)
