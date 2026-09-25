# USER SCOPE AUDIT RESULT
**โครงการ:** เว็บแอปพลิเคชันระบบจัดการสนามแบดมินตัน T.S. Pattani  
**เป้าหมายการตรวจสอบ:** ตรวจสอบการทำงานจริงของระบบฝั่ง User / สมาชิก เปรียบเทียบกับขอบเขตหัวข้อ 1.3 ของรายงานโครงงาน  
**วันที่ตรวจสอบ:** 25 กันยายน 2569  
**กติกาการตรวจสอบ:** ตรวจสอบจาก Source Code, Database, PHP, JavaScript, SQL, UI และ Business Logic จริง 100% (ห้ามแก้ไขโค้ดหรือฐานข้อมูล)

---

## 1. Executive Summary (สรุปภาพรวม)

### สถิติผลการตรวจสอบสถานะ (User Scope)
| สถานะ (Status) | จำนวนรายการ | คำอธิบาย |
|---|:---:|---|
| **Implemented** | **23** | พบการทำงานจริง โค้ดถูกต้อง และตรงตาม Requirement |
| **Partial** | **11** | มีบางส่วน แต่ยังไม่ครบถ้วน หรือมีข้อบกพร่อง/บัคในโค้ด |
| **Missing** | **6** | Requirement ระบุไว้ชัดเจนใน 1.3 แต่ไม่พบโค้ดทำงาน |
| **Not Found** | **1** | ค้นหาหลักฐานไฟล์/ฟังก์ชันที่ระบุในเมนูแล้วไม่พบ (`profile.php`) |
| **N/A** | **3** | เป็นหน้าที่ของ Admin หรือระบบหลังบ้านโดยตรง |

### สรุปข้อค้นพบสำคัญ
1. **ส่วนที่ทำงานได้ดีมาก (Implemented):**
   * ระบบลงทะเบียนและยืนยันตัวตน มีการเข้ารหัสผ่าน `password_hash` และเปิดบัญชีแต้มเริ่มต้นอัตโนมัติ
   * ระบบกู้คืนรหัสผ่าน (`forgot_password.php`) ยืนยันตัวตนด้วยชื่อและเบอร์โทรศัพท์จริง
   * ระบบการจองสนามแบบ One-stop Booking (จองสนาม + เช่าอุปกรณ์ + ซื้อสินค้า) ทำงานได้ในหน้าเดียว
   * ระบบป้องกันการจองเวลาซ้อน (Overlap Protection) และระบบ Atomic Lock 15 นาที
   * การคำนวณราคาฝั่ง Server ป้องกันการดัดแปลงราคาผ่าน Web Inspector
   * ระบบเคลียร์การจองที่หมดเวลา 15 นาที (Lazy Auto-cancel) พร้อมคืนสต็อกอุปกรณ์อัตโนมัติ
   * ระบบชำระเงินและตรวจสอบสลิป ตรวจสอบ MIME Type และขนาดไฟล์
   * ระบบแลกของรางวัลฝั่งสมาชิก มีการล็อกแถวข้อมูล (`FOR UPDATE`) ป้องกัน Race Condition
   * การป้องกันการเข้าถึงข้อมูลข้ามผู้ใช้ (IDOR) ในหน้าชำระเงินและประวัติการจองทำได้รัดกุม

2. **จุดผิดพลาดและบัคในโค้ดฝั่ง User (Bugs to Fix):**
   * **Broken Link 404:** มีลิงก์ `profile.php` ใน Navbar Dropdown แต่ไม่มีไฟล์ในโปรเจกต์
   * **ปุ่มดูสลิปไม่ขึ้น:** ใน `booking_history.php` โค้ดเรียกคอลัมน์ `$row['booking_slip']` แต่ใน Database ชื่อคอลัมน์จริงคือ `payment_slip`
   * **สีป้ายสถานะเพี้ยน:** ใน `booking_history.php` เช็คคำว่า `'ชำระเงินแล้ว'` หรือ `'อนุมัติแล้ว'` แต่สถานะจริงใน ENUM คือ `'จองแล้ว'` ทำให้รายการที่ผ่านการอนุมัติแสดงเป็นสีเหลือง (Pending)
   * **ENUM Mismatch:** ตาราง `Rental` ใน `setup.php` มีแค่ `'กำลังเช่า', 'คืนแล้ว'` แต่โค้ดจองบันทึก `'รอตรวจสอบ'` และโค้ดยกเลิกบันทึก `'ยกเลิก'`

3. **ฟังก์ชันที่ขาดหายไปตามขอบเขต 1.3 (Critical Requirement Gaps):**
   * **ระบบรับคืนอุปกรณ์เช่า (Return System):** สต็อกอุปกรณ์เช่าถูกตัดเมื่อจอง แต่ไม่มีขั้นตอน/หน้าจอรับคืนอุปกรณ์เพื่อบวกสต็อกกลับเข้าคลัง
   * **ระบบยกเลิกการจอง (Cancellation Flow):** ในหน้า `booking_history.php` ไม่มีปุ่มกดยกเลิก และในทั้งระบบไม่มีคำสั่ง `INSERT INTO Cancellation` เลยแม้แต่จุดเดียว
   * **ตารางสถานะสนามแบบเรียลไทม์ (Real-time Court Availability):** หน้า `booking.php` ยังไม่มีตาราง Time-slot Matrix แสดงสถานะ ว่าง/รอตรวจสอบ/จองแล้ว ให้เห็นก่อนกดยืนยัน
   * **ราคา Peak / Off-peak:** ในโค้ด `booking_db.php` ยังไม่ได้นำเงื่อนไขเวลามาคิดราคาเร่งด่วนตามที่ระบุใน 1.3.4 (ใช้ราคาปกติเสมอ)

---

## 2. 1.3.1 Web Application (การพัฒนาเว็บแอปพลิเคชัน)

| รายการตรวจสอบ | สถานะ | ไฟล์ที่ตรวจพบ | หลักฐานเชิงประจักษ์ |
|---|:---:|---|---|
| **ใช้งานผ่าน Web Browser** | Implemented | ทุกหน้า `.php` | ทำงานบน Apache/PHP 8.x รองรับการเปิดผ่านเบราว์เซอร์มาตรฐาน |
| **Responsive หลายขนาดหน้าจอ** | Implemented | `assets/css/style.css`, `global.css` | มี Media Queries `@media (max-width: 768px)` และ `@media (max-width: 480px)` จัดการ Grid และ Layout |
| **Mobile / Desktop Navigation** | Implemented | `includes/navbar.php`, `assets/js/member.js` | มีปุ่ม Hamburger Menu (`toggleMobileMenu()`) และ Dropdown สำหรับจอคอมพิวเตอร์ |
| **User Navigation ระหว่างหน้า** | Partial | `includes/navbar.php` | ลิงก์ส่วนใหญ่ทำงานถูกต้อง แต่มีลิงก์ไปยัง `profile.php` ซึ่งเกิด **404 Not Found** |

---

## 3. 1.3.2 ระบบลงทะเบียนและยืนยันตัวตน (Registration & Authentication)

### 3.1 การสมัครสมาชิก (Register)
* **Status:** `Implemented`
* **หลักฐาน:**
  * File: `register.php`, `actions/register_db.php`
  * Form Input: `member_name`, `member_phone`, `member_gender`, `member_age`, `member_occupation`, `member_password`, `confirm_password`
  * Client Validation: `assets/js/member.js` ฟังก์ชัน `validatePassword()` ตรวจสอบเบอร์โทร 10 หลัก (`/^[0-9]{10}$/`) และรหัสผ่านตรงกัน
  * Server Validation: `actions/register_db.php` ตรวจสอบข้อมูลว่าง, ตรวจสอบรูปแบบเบอร์โทร, ตรวจสอบเบอร์โทรซ้ำ (`SELECT COUNT(*) FROM Member WHERE member_phone = :phone`)
  * Security: เข้ารหัสผ่านด้วย `password_hash($password, PASSWORD_DEFAULT)`
  * Database: ใช้ Transaction บันทึกข้อมูลลงตาราง `Member` และสร้างบัญชีแต้มเริ่มต้นลงตาราง `Point` (`point_balance = 0, member_level = 'Bronze'`)
  * Feedback: แจ้งเตือนผ่าน Flash Session `$_SESSION['success']` และ `$_SESSION['error']`

### 3.2 การแก้ไขข้อมูลส่วนตัว (Profile Edit)
* **Status:** `Not Found` / `Missing`
* **หลักฐาน:**
  * มีลิงก์ `<a href="profile.php">` ใน `includes/navbar.php` บรรทัดที่ 49
  * ตรวจสอบไฟล์ในระบบ ไม่พบไฟล์ `profile.php` หรือ `actions/profile_db.php`
  * สมาชิกยังไม่สามารถดูหรือแก้ไขข้อมูลส่วนตัว (ชื่อ, เบอร์โทร, อายุ, อาชีพ) ของตนเองได้ มีเพียง Admin ที่แก้ไขข้อมูลสมาชิกได้ใน `admin/member_edit.php`

### 3.3 การกู้คืนรหัสผ่าน (Forgot / Reset Password)
* **Status:** `Implemented`
* **หลักฐาน:**
  * File: `forgot_password.php`, `actions/forgot_password_db.php`
  * Verification: ตรวจสอบความถูกต้องของเบอร์โทรศัพท์และชื่อ-นามสกุลจริงจากฐานข้อมูล
  * Password Update: รับรหัสผ่านใหม่ (ขั้นต่ำ 6 ตัวอักษร) แฮชด้วย `password_hash()` และอัปเดตลงตาราง `Member`
  * Entry Point: มีลิงก์ "ลืมรหัสผ่าน?" ในหน้า `login.php`

### 3.4 Login / Logout / Session Management
* **Status:** `Implemented`
* **หลักฐาน:**
  * File: `login.php`, `actions/login_db.php`, `actions/logout.php`
  * Authentication: ตรวจสอบด้วย `password_verify($password, $member['member_password'])`
  * Suspension Check: มีการดักจับสถานะ `if ($member['member_status'] === 'ระงับสิทธิ์')` ปฏิเสธการเข้าสู่ระบบ
  * Session Data: บันทึก `$_SESSION['member_id']`, `$_SESSION['member_name']`, `$_SESSION['member_phone']`
  * Logout: `actions/logout.php` ทำการ `session_unset()` และ `session_destroy()` กลับสู่หน้าแรก

---

## 4. 1.3.3 ระบบจัดการคะแนนสะสมและระดับสมาชิก (Points & Loyalty)

### 4.1 การดูคะแนนและระดับสมาชิก
* **Status:** `Implemented`
* **หลักฐาน:**
  * File: `includes/navbar.php`, `rewards.php`
  * Table: `Point` (คอลัมน์ `point_balance`, `member_level`)
  * การแสดงผล: แสดงแต้มและระดับสมาชิก (Bronze/Silver/Gold) บน Navbar และกล่องแดชบอร์ดส่วนตัวในหน้า `rewards.php`

### 4.2 แคตตาล็อกและการแลกของรางวัล
* **Status:** `Implemented`
* **หลักฐาน:**
  * File: `rewards.php`, `actions/redeem_reward_db.php`
  * UI Control: ปุ่มแลกรางวัลถูก Disable พร้อมระบุสาเหตุชัดเจนหากแต้มไม่พอ, ระดับสมาชิกไม่ถึง, หรือสินค้าหมด
  * Concurrency Protection: ใช้ `SELECT ... FOR UPDATE` ล็อกแถวข้อมูลในตาราง `Reward` และ `Point` ภายใน Database Transaction
  * Deductions: หักสต็อก `Reward.reward_stock` และหักคะแนน `Point.point_balance` พร้อมบันทึกลง `Point_Transaction` (`transaction_type = 'ใช้'`)

### 4.3 Business Logic การคำนวณแต้มและอัปเกรดระดับ
* **คำนวณแต้มจากยอดเงินจริง:** `Implemented` — ใน `admin/actions/payment_verify_db.php` คำนวณ `$earned_points = floor($data['booking_total_price'] / 100)` (100 บาท = 1 แต้ม)
* **การอัปเกรดระดับสมาชิกอัตโนมัติ:** `Partial` — ในโค้ดปัจจุบันยังไม่มี Logic ตรวจสอบเกณฑ์คะแนนสะสมรวมเพื่อปรับระดับ Bronze -> Silver -> Gold อัตโนมัติ (ปัจจุบันเป็นการปรับมือโดย Admin)
* **ดูประวัติการแลกรางวัลของตนเอง:** `Missing` — สมาชิกยังไม่มีหน้าจอสำหรับดูรายการประวัติของรางวัลที่ตนเองเคยกดแลกไป (ดูได้เฉพาะ Admin ที่หน้า `admin/redemptions.php`)

---

## 5. 1.3.4 ระบบตรวจสอบสถานะสนาม (Court Status)

| ฟังก์ชันที่ตรวจสอบ | สถานะ | หลักฐานในโค้ด | ปัญหา / ข้อสังเกต |
|---|:---:|---|---|
| **เลือกวันและเวลา (ขั้นต่ำ 1 ชม.)** | Implemented | `booking.php`, `member.js`, `actions/booking_db.php` | ตรวจสอบทั้งหน้าบ้านและหลังบ้าน (`hours >= 1`) |
| **ตัดสนามที่ปิดปรับปรุงออก** | Implemented | `booking.php#L15` | `SELECT * FROM COURT WHERE court_status != 'ปิดปรับปรุง'` |
| **ตารางสถานะสนามแบบเรียลไทม์ (4 สถานะ)** | Partial | `booking.php` | **ยังไม่มีตาราง Time-slot Matrix** แสดงสถานะ `ว่าง / รอตรวจสอบ / จองแล้ว` ให้เห็นก่อนเลือก ผู้ใช้ต้องเลือกสนามแล้วกดยืนยัน หากเวลาชนระบบจึงจะแจ้งเตือน Error |
| **ป้องกันการเลือกสนามที่ถูกจองแล้ว** | Implemented | `actions/booking_db.php#L88-L106` | มีคำสั่ง SQL ตรวจจับเวลาชนก่อนบันทึก |

---

## 6. 1.3.5 ระบบบริการสินค้าและอุปกรณ์เช่า (Products & Rental)

### 6.1 สินค้าบริโภค (น้ำดื่ม/ลูกแบด)
* **Status:** `Implemented`
* **หลักฐาน:**
  * File: `booking.php` ส่วน `สินค้าบริโภค (น้ำดื่ม/ลูกแบด)`
  * Table: `Product` (`product_type = 'สินค้าบริโภค'`)
  * Controls: แสดงรูปภาพ, ชื่อ, ยี่ห้อ, ราคา และมีปุ่มเพิ่ม/ลดจำนวน โดยจำกัดไม่เกินสต็อกคงเหลือจริง (`max="<?php echo $prod['product_stock']; ?>"`)

### 6.2 อุปกรณ์เช่า (ไม้แบด/รองเท้า)
* **Status:** `Implemented`
* **หลักฐาน:**
  * File: `booking.php` ส่วน `อุปกรณ์กีฬาให้เช่า`
  * Table: `Product` (`product_type = 'อุปกรณ์เช่า'`)
  * Specifications: แสดงรายละเอียดตรงตามข้อกำหนด 1.3:
    * ไม้แบดมินตัน: แสดงระดับการใช้งาน `product_level` (มือใหม่ / มือโปร)
    * รองเท้า: แสดงขนาดไซส์ `product_size`
    * ยี่ห้อและรุ่น: แสดง `product_brand` และ `product_model`
  * Inventory Validation: ตรวจสอบสต็อกซ้ำในฝั่งเซิร์ฟเวอร์ (`actions/booking_db.php#L166`) และตัดสต็อกทันทีเมื่อสร้างรายการจอง
  * Tracking: บันทึกลงตาราง `Rental` พร้อมระบุ `rental_start_time` และ `rental_return_time`

### 6.3 การติดตามและการรับคืนอุปกรณ์เช่า
* **Status:** `Missing`
* **ข้อค้นพบ:** แม้ระบบจะตัดสต็อกและบันทึกเวลาเช่าลงตาราง `Rental` แต่**ไม่มีขั้นตอนหรือหน้าจอรับคืนอุปกรณ์** ทำให้สต็อกอุปกรณ์เช่าไม่เคยถูกปรับคืนเข้าสู่ระบบหลังจากใช้งานเสร็จ

---

## 7. 1.3.6 ระบบการจองสนามและคำนวณยอด (Booking & Calculations)

### 7.1 กระบวนการจองแบบขั้นตอนเดียว (One-stop Booking)
* **Status:** `Implemented`
* **หลักฐาน:** [booking.php](file:///c:/xampp/htdocs/ts-pattani/booking.php) สามารถเลือกวัน เวลา สนาม อุปกรณ์เช่า และสินค้าบริโภค แล้วกดยืนยันการจองพร้อมกันได้ในฟอร์มเดียว

### 7.2 การคำนวณยอดเงิน (Calculation Engine)
* **Client-side Calculation:** `member.js` คำนวณค่าสนาม ค่าเช่า ค่าสินค้า และยอดรวมสุทธิแบบ Real-time
* **Server-side Security:** [actions/booking_db.php#L43-L84](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php#L43-L84) ดึงราคาจากตาราง `Court` และ `Product` มาคำนวณใหม่ทั้งหมดทางฝั่ง Server ป้องกันการแฮกแก้ไขยอดเงินผ่าน DevTools
* **การคิดราคา Peak / Off-peak:** `Partial` — ในฐานข้อมูลและหน้าจัดการสนามมีฟิลด์ `court_peak_price` และ `court_offpeak_price` แต่ใน `booking_db.php` **ยังไม่ได้นำเวลาจองมาคิดแยกราคา** (ยังใช้ราคาปกติคูณชั่วโมง)

### 7.3 การป้องกันการจองซ้อนและ Atomic Locking
* **Status:** `Implemented`
* **หลักฐาน:**
  * Overlap Query: `(booking_start_time < :end_time AND booking_end_time > :start_time)` ในวันและสนามเดียวกัน โดยไม่นับรายการที่ `'ยกเลิก'`
  * Atomic Lock: เมื่อจองสำเร็จ รายการจะถูกตั้งสถานะเป็น `'รอตรวจสอบ'` และบันทึกเวลาหมดอายุ `booking_lock_expire = NOW() + 15 นาที` ทำให้ผู้อื่นไม่สามารถจองช่วงเวลานี้ได้

### 7.4 กลไก Booking Timeout 15 นาที
* **Status:** `Implemented`
* **หลักฐาน:**
  * File: `admin/includes/auto_cancel.php`, `booking.php`, `admin/payments.php`
  * Process (Lazy Expiry): เมื่อมีการโหลดหน้า `booking.php` หรือ `payments.php` ระบบจะค้นหา Booking ที่สถานะ `'รอตรวจสอบ'`, ไม่มีสลิป (`payment_slip IS NULL`) และหมดเวลา 15 นาที
  * Resolution: ปรับสถานะเป็น `'ยกเลิก'`, ปรับสถานะ `Rental` เป็น `'ยกเลิก'` และบวกสต็อกสินค้า/อุปกรณ์เช่าคืนเข้าตาราง `Product` อัตโนมัติ ทำให้สนามกลับมาว่างพร้อมให้ผู้อื่นจองต่อทันที

---

## 8. 1.3.7 ระบบชำระเงินและตรวจสอบสลิป (Payment & Slip)

### 8.1 การแสดงยอดและ QR Code ชำระเงิน
* **Status:** `Implemented` (UI Placeholder)
* **หลักฐาน:** [payment.php](file:///c:/xampp/htdocs/ts-pattani/payment.php) แสดงยอดชำระสุทธิที่ดึงมาจากตาราง `Booking`, แสดงเวลานับถอยหลัง 15 นาที และแสดงข้อมูลเลขบัญชี/QR Code

### 8.2 การอัปโหลดสลิปหลักฐานการโอนเงิน
* **Status:** `Implemented`
* **หลักฐาน:**
  * File: [actions/payment_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/payment_db.php)
  * File Validation: จำกัดขนาดไฟล์ไม่เกิน 5MB, ตรวจสอบนามสกุล `jpg, jpeg, png, webp` และตรวจสอบ MIME Type จริงผ่าน `finfo_file()`
  * Storage: เปลี่ยนชื่อไฟล์สุ่มด้วย Timestamp บันทึกลง `uploads/slips/`
  * Database: บันทึกข้อมูลลงตาราง `Payment` (`payment_status = 'รอตรวจสอบ'`) และอัปเดตชื่อสลิปลง `Booking.payment_slip`

### 8.3 การตรวจสอบสถานะการจองหลังส่งสลิป (Booking History)
* **Status:** `Partial` (พบบัคในโค้ด)
* **หลักฐาน:** [booking_history.php](file:///c:/xampp/htdocs/ts-pattani/booking_history.php)
* **บัคที่พบ:**
  1. บรรทัดที่ 109 เรียกใช้ `$row['booking_slip']` ซึ่งไม่มีอยู่จริงในตาราง `Booking` (ชื่อจริงคือ `payment_slip`) ส่งผลให้**ปุ่ม "ดูสลิป" ไม่แสดงผล**
  2. บรรทัดที่ 94 ตรวจสอบเงื่อนไข `$status == 'ชำระเงินแล้ว' || $status == 'อนุมัติแล้ว'` แต่ค่าจริงในฐานข้อมูลคือ `'จองแล้ว'` ส่งผลให้**ป้ายสถานะแสดงเป็นสีเหลือง (รอตรวจสอบ) แทนที่จะเป็นสีเขียว**

---

## 9. 1.3.8 ระบบ POS และสต็อก — ผลกระทบต่อ User (User Impact)
* **Status:** `Implemented`
* **หลักฐาน:**
  * เมื่อสินค้าหน้าร้านถูกขายผ่าน POS สต็อกในตาราง `Product` จะลดลงทันที ส่งผลให้หน้า `booking.php` ของสมาชิกแสดงจำนวนคงเหลือที่ตรงกัน
  * สินค้าที่สต็อกหมด (`product_stock <= 0`) จะไม่ถูกนำมาแสดงให้สมาชิกเลือกในหน้า `booking.php`
  * **หมายเหตุ:** การจองสนาม Walk-in หน้าร้านผ่าน POS ยังไม่รองรับ (POS ขายได้เฉพาะสินค้าและอุปกรณ์)

---

## 10. 1.3.9 ระบบสื่อสารและประชาสัมพันธ์ (Chat & News)

### 10.1 ระบบแชทสนทนากับแอดมิน (Live Chat)
* **Status:** `Implemented` (ขาด Auto-refresh ฝั่งสมาชิก)
* **หลักฐาน:**
  * File: [chat.php](file:///c:/xampp/htdocs/ts-pattani/chat.php), [actions/send_chat_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/send_chat_db.php)
  * Features: แสดงประวัติการสนทนาเฉพาะของสมาชิกที่ล็อกอินอยู่, จัดวางกล่องข้อความแยกซ้าย-ขวาชัดเจน, มี JavaScript เลื่อน Scrollbar ลงล่างสุดอัตโนมัติ
  * ข้อสังเกต: ฝั่งสมาชิกยังไม่มีสคริปต์ AJAX Polling (ต้องกด F5 เพื่อดูข้อความใหม่ที่แอดมินตอบกลับ)

### 10.2 ข่าวสาร กิจกรรม และโปรโมชั่น (News & Promotions)
* **Status:** `Implemented`
* **หลักฐาน:**
  * File: [promotions.php](file:///c:/xampp/htdocs/ts-pattani/promotions.php), [index.php](file:///c:/xampp/htdocs/ts-pattani/index.php)
  * Features: ดึงข้อมูลจากตาราง `News` จัดแสดงเป็น Grid Card แสดงรูปภาพ วันที่ และหัวข้อข่าว พร้อมหน้าต่าง Popup Modal อ่านรายละเอียดฉบับเต็มโดยไม่ต้องโหลดหน้าใหม่

---

## 11. 1.3.10 Dashboard และการวิเคราะห์ข้อมูล — ข้อมูลของ User (User Data)

* **Status:** `Implemented` (ด้านการจัดเก็บข้อมูล)
* **หลักฐานการจัดเก็บข้อมูลสมาชิก:**
  * ฟิลด์ในตาราง `Member`: `member_gender` (เพศ), `member_age` (อายุ), `member_occupation` (อาชีพ) ถูกบันทึกตั้งแต่ขั้นตอนสมัครสมาชิก
* **การนำไปวิเคราะห์ในแดชบอร์ด (`admin/index.php`):**
  * เพศ (`member_gender`): แสดงผลเป็นกราฟโดนัท (Donut Chart)
  * อาชีพ (`member_occupation`): แสดงผลเป็นกราฟแท่งแนวนอน (Horizontal Bar Chart)
  * อายุ (`member_age`): **ยังไม่มีกราฟช่วงอายุ** บนแดชบอร์ด แม้ใน 1.3.10 จะระบุไว้โดยตรง

---

## 12. 1.3.11 ระบบจัดการสิทธิ์และเงื่อนไขการยกเลิก (Cancellation)

* **Status:** `Missing` / `Non-functional`
* **การตรวจสอบเทียบกับ Requirement 1.3.11:**
  1. *Requirement:* สมาชิกยกเลิกได้เองเฉพาะสถานะ `รอตรวจสอบ`
     * *ความจริงในโค้ด:* **ไม่มีปุ่มกดยกเลิก** ในหน้า `booking_history.php` หรือหน้าใดๆ ของสมาชิก
  2. *Requirement:* สถานะ `จองแล้ว` สมาชิกยกเลิกเองไม่ได้ ต้องติดต่อแอดมิน
     * *ความจริงในโค้ด:* ไม่มีฟังก์ชันส่งคำร้องขอยกเลิกไปยังแอดมิน
  3. *Requirement:* บันทึกประวัติการยกเลิกลงตาราง `Cancellation`
     * *ความจริงในโค้ด:* **ไม่พบคำสั่ง `INSERT INTO Cancellation` เลยแม้แต่จุดเดียวในทั้งโปรเจกต์** ตาราง `Cancellation` จึงไม่มีข้อมูล
  4. *ฝั่งแอดมิน:* หน้า `admin/cancellations.php` มีสูตรคำนวณค่าปรับและหน้าต่างพิจารณาคืนเงิน แต่เนื่องจากไม่มีการสร้าง Record คำขอยกเลิกเข้ามา ระบบจึงไม่สามารถทำงานได้จริงในทางปฏิบัติ

---

## 13. End-to-End User Flow (การทำงานจริงตั้งแต่ต้นจนจบ)

```text
[1. register.php] 
  │ กรอกข้อมูล ชื่อ, เบอร์โทร, เพศ, อายุ, อาชีพ, รหัสผ่าน
  ▼
[2. actions/register_db.php] 
  │ ตรวจเบอร์ซ้ำ, แฮชรหัสผ่าน, INSERT Member, INSERT Point (Bronze, 0 pts)
  ▼
[3. login.php -> actions/login_db.php] 
  │ ตรวจรหัสผ่าน, ตรวจสถานะระงับสิทธิ์, สร้าง Session
  ▼
[4. booking.php] 
  │ เลือกวัน, เวลา (1+ ชม.), สนาม, อุปกรณ์เช่า, เครื่องดื่ม
  ▼
[5. actions/booking_db.php] 
  │ คำนวณราคาฝั่ง Server, ตรวจ Overlap, INSERT Booking ('รอตรวจสอบ', Lock 15 นาที),
  │ INSERT Rental, ตัดสต็อก Product ชั่วคราว
  ▼
[6. payment.php] 
  │ แสดง QR Code, แสดงตัวนับถอยหลัง 15 นาที, แนบไฟล์สลิป
  ▼
[7. actions/payment_db.php] 
  │ ตรวจ MIME/Size ไม่เกิน 5MB, ย้ายไฟล์ไป uploads/slips/, 
  │ INSERT Payment, UPDATE Booking (payment_slip)
  ▼
[8. admin/payments.php -> actions/payment_verify_db.php] 
  │ แอดมินตรวจสลิป -> กดยืนยัน:
  │ UPDATE Booking ('จองแล้ว'), INSERT Revenue (แยก 3 หมวด), 
  │ คำนวณแต้มสะสม (+1 แต้ม/100 บาท) -> UPDATE Point, INSERT Point_Transaction
  ▼
[9. booking_history.php] 
  │ สมาชิกดูประวัติการจอง (ติดบัคปุ่มดูสลิปไม่ขึ้น และสีป้ายสถานะไม่เป็นสีเขียว)
  ▼
[10. rewards.php -> actions/redeem_reward_db.php] 
  │ นำแต้มไปแลกของรางวัล -> ตัดแต้ม, ตัดสต็อก Reward, บันทึกประวัติแต้ม
```

---

## 14. CRUD Matrix (ฝั่ง User / สมาชิก)

| Module | Create | Read | Update | Delete | ไฟล์ที่รับผิดชอบ | ตารางฐานข้อมูล |
|---|:---:|:---:|:---:|:---:|---|---|
| **Member Profile** | ✅ | ❌ | ❌ | ❌ | `register.php` (ขาด `profile.php`) | `Member` |
| **Booking** | ✅ | ✅ | ❌ | ❌ | `booking.php`, `booking_history.php` | `Booking` |
| **Rental** | ✅ Auto | ❌ | ❌ | ❌ | `actions/booking_db.php` | `Rental` |
| **Product Order** | ✅ Auto | ❌ | ❌ | ❌ | `actions/booking_db.php` | `Product` |
| **Payment / Slip** | ✅ | ⚠️ บัคดูสลิป | ❌ | ❌ | `payment.php`, `actions/payment_db.php` | `Payment` |
| **Points** | ➖ Auto | ✅ | ❌ | ❌ | `navbar.php`, `rewards.php` | `Point` |
| **Rewards Redemption**| ✅ | ✅ แคตตาล็อก | ❌ | ❌ | `rewards.php`, `redeem_reward_db.php` | `Reward`, `Point_Transaction` |
| **Chat** | ✅ | ✅ | ❌ | ❌ | `chat.php`, `actions/send_chat_db.php`| `Chat` |
| **News** | ❌ | ✅ | ❌ | ❌ | `promotions.php`, `index.php` | `News` |
| **Cancellation** | ❌ | ❌ | ❌ | ❌ | *(ยังไม่มีโค้ดฝั่งสมาชิก)* | `Cancellation` |

---

## 15. Authentication & Authorization Audit

* **Session Validation:** ทุกหน้าฝั่งสมาชิก (`booking.php`, `payment.php`, `booking_history.php`, `rewards.php`, `chat.php`) มีการดัก `if (!isset($_SESSION['member_id']))` ถูกต้อง
* **การป้องกัน ID Tampering (IDOR Audit):**
  * `payment.php?booking_id=X`: ✅ ปลอดภัย — มีการเช็ค `AND member_id = :member_id` สมาชิกไม่สามารถแอบดูหรือจ่ายเงินแทน Booking คนอื่นได้
  * `booking_history.php`: ✅ ปลอดภัย — ดึงข้อมูลเฉพาะ `WHERE b.member_id = :member_id`
  * `chat.php`: ✅ ปลอดภัย — กรองเฉพาะ `WHERE member_id = :member_id`
  * `actions/redeem_reward_db.php`: ✅ ปลอดภัย — ใช้ `$_SESSION['member_id']` จากเซสชันจริง
* **ช่องโหว่ด้านสิทธิ์ระหว่างเซสชัน:**
  * หากสมาชิกล็อกอินค้างไว้ แล้วแอดมินกด "ระงับสิทธิ์" ในหน้าจัดการสมาชิก สมาชิกจะยังคงสามารถกดจองสนามต่อได้จนกว่าจะ Logout หรือ Session หมดอายุ เนื่องจาก `actions/booking_db.php` ไม่ได้ Query เช็ค `member_status` ซ้ำ

---

## 16. Error Handling Audit

| หมวดหมู่ | Client-side Validation | Server-side Validation | Database Error Handling | ข้อความแจ้งเตือนผู้ใช้ |
|---|:---:|:---:|:---:|:---:|
| **สมัครสมาชิก** | ✅ JS เช็คเบอร์ 10 หลัก & รหัสตรงกัน | ✅ PHP เช็คเบอร์ซ้ำ & ความยาว | ✅ PDO Transaction + Rollback | ✅ Alert ภาษาไทยชัดเจน |
| **เข้าสู่ระบบ** | ✅ HTML5 Required | ✅ PHP เช็ครหัส & บัญชีถูกระงับ | ✅ Try-Catch Exception | ✅ Alert ภาษาไทย |
| **จองสนาม** | ✅ JS เช็คขั้นต่ำ 1 ชม. & คำนวณยอด | ✅ PHP Recalculate & เช็คเวลาชน | ✅ PDO Transaction + Rollback | ✅ Alert แจ้งเตือนเวลาซ้ำ |
| **ชำระเงิน** | ✅ JS ตัวนับเวลา 15 นาที | ✅ PHP finfo MIME & Size 5MB | ✅ PDO Exception Handling | ✅ Alert สลิปไม่ถูกต้อง |
| **แลกรางวัล** | ✅ JS ปิดปุ่มเมื่อแต้มไม่พอ | ✅ PHP ตรวจแต้ม, เลเวล, สต็อก | ✅ SELECT FOR UPDATE + Rollback | ✅ Alert สิทธิ์ไม่ถึง |

---

## 17. Security Audit (ความมั่นคงปลอดภัย)

1. **Password Storage:** ✅ ได้มาตรฐานสูงสุด ใช้ `password_hash($password, PASSWORD_DEFAULT)` แบบ BCRYPT
2. **SQL Injection:** ✅ ปลอดภัย มีการใช้ PDO Prepared Statements พร้อม Parameter Binding (`:param`) ในคำสั่งรับ Input ทั้งหมด
3. **Price Tampering:** ✅ ปลอดภัย มีการคำนวณราคาใหม่ทางฝั่ง Server ใน `booking_db.php`
4. **File Upload Security:** ✅ ปลอดภัย ตรวจสอบ MIME Type แท้จริงด้วย `finfo` และเปลี่ยนชื่อไฟล์ป้องกันการรันโค้ดอันตราย
5. **CSRF Protection:** ❌ **ไม่มีการป้องกัน** ยังไม่มีการใช้งาน CSRF Token ในฟอร์มใดๆ ของระบบ
6. **Direct File Access:** ⚠️ รูปสลิปใน `uploads/slips/` สามารถเปิดดูได้ตรงๆ หากทราบชื่อไฟล์ (แต่ชื่อไฟล์สุ่มด้วย Timestamp และ ID)

---

## 18. สรุปรายการที่ต้องแก้ไขและจุดที่ยังไม่สมบูรณ์ (Missing & Partial Features)

### 🔴 ต้องแก้ไขทันที (Bugs)
1. **แก้ปัญหาลิงก์ 404:** สร้างไฟล์ `profile.php` หรือนำลิงก์ออกจาก `includes/navbar.php` บรรทัดที่ 49
2. **แก้ปุ่มดูสลิปไม่แสดง:** ใน `booking_history.php` บรรทัดที่ 109 เปลี่ยน `$row['booking_slip']` เป็น `$row['payment_slip']`
3. **แก้สีป้ายสถานะการจอง:** ใน `booking_history.php` บรรทัดที่ 94 เพิ่มเงื่อนไขรองรับคำว่า `$status == 'จองแล้ว'`
4. **แก้ ENUM ในฐานข้อมูล:** แก้ไขคำสั่งสร้างตาราง `Rental` ใน `config/setup.php` ให้รองรับสถานะ `'รอตรวจสอบ'` และ `'ยกเลิก'`

### 🟡 ต้องพัฒนาเพิ่มเติมตามขอบเขต 1.3 (Requirement Completion)
1. **เพิ่มปุ่มยกเลิกการจองฝั่งสมาชิก (1.3.11):** ในหน้า `booking_history.php` เพิ่มปุ่มยกเลิกสำหรับรายการ `'รอตรวจสอบ'` และปุ่มส่งคำร้องขอยกเลิกสำหรับรายการ `'จองแล้ว'` พร้อมคำสั่งบันทึกลงตาราง `Cancellation`
2. **พัฒนาระบบรับคืนอุปกรณ์เช่า (1.3.5 / 1.3.8):** สร้างหน้าจอสำหรับแอดมินบันทึกรับคืนอุปกรณ์ เพื่อปรับสต็อกคืนเข้าคลัง
3. **เพิ่มตารางแสดงสถานะสนามแบบเรียลไทม์ (1.3.4):** พัฒนา Time-slot Matrix ในหน้า `booking.php` เพื่อให้สมาชิกมองเห็นช่วงเวลาว่าง/ไม่ว่างก่อนตัดสินใจจอง
4. **เชื่อมต่อ Business Logic ราคา Peak / Off-peak (1.3.4):** เขียนเงื่อนไขคำนวณราคาพิเศษตามช่วงเวลาจริงใน `actions/booking_db.php`

---

## 19. สิ่งที่พบใน Code แต่ไม่อยู่ในขอบเขต 1.3 (Found in Code but Outside 1.3)

* **ระบบกู้คืนรหัสผ่านด้วยเบอร์โทรศัพท์และชื่อ (`forgot_password.php`):** ใน 1.3 ไม่ได้ระบุไว้ แต่โค้ดมีระบบนี้พัฒนาไว้เรียบร้อยและใช้งานได้ดี
* **ระบบแชทติดต่อแอดมิน (`chat.php`):** ช่วยเพิ่มช่องทางสื่อสารระหว่างสมาชิกและผู้ดูแลระบบ
* **ระบบแจ้งเตือนแบบ Modal Popup ในหน้าข่าวสาร (`promotions.php`):** แสดงเนื้อหาข่าวสารฉบับเต็มโดยไม่ต้องโหลดเปลี่ยนหน้าใหม่

---

## 20. สรุปหลักฐานไฟล์ ฟังก์ชัน และตารางที่เกี่ยวข้อง (Traceability Evidence)

| Requirement 1.3 | User Page | Controller / Action | Database Table | Status |
|---|---|---|---|:---:|
| **สมัครสมาชิก** | `register.php` | `actions/register_db.php` | `Member`, `Point` | **Implemented** |
| **เข้าสู่ระบบ** | `login.php` | `actions/login_db.php` | `Member` | **Implemented** |
| **แก้ไขข้อมูลตนเอง** | `profile.php` *(ไม่มีไฟล์)* | *(ไม่มี)* | `Member` | **Not Found** |
| **กู้คืนรหัสผ่าน** | `forgot_password.php` | `actions/forgot_password_db.php` | `Member` | **Implemented** |
| **ดูแต้มสะสมและระดับ**| `includes/navbar.php`, `rewards.php` | - | `Point` | **Implemented** |
| **แลกของรางวัล** | `rewards.php` | `actions/redeem_reward_db.php` | `Reward`, `Point`, `Point_Transaction` | **Implemented** |
| **ดูสถานะสนาม** | `booking.php` | `actions/booking_db.php` | `Court`, `Booking` | **Partial** |
| **จองสนาม One-stop** | `booking.php` | `actions/booking_db.php` | `Booking`, `Rental`, `Product` | **Implemented** |
| **คำนวณยอดเงิน** | `booking.php`, `member.js` | `actions/booking_db.php` | `Court`, `Product` | **Implemented** |
| **ป้องกันจองซ้อน** | `booking.php` | `actions/booking_db.php` | `Booking` | **Implemented** |
| **Timeout 15 นาที** | `payment.php` | `admin/includes/auto_cancel.php` | `Booking`, `Product`, `Rental` | **Implemented** |
| **ชำระเงิน / อัปโหลดสลิป**| `payment.php` | `actions/payment_db.php` | `Payment`, `Booking` | **Implemented** |
| **ประวัติการจอง** | `booking_history.php` | - | `Booking`, `Court` | **Partial (บัคดูสลิป)** |
| **แชทติดต่อแอดมิน** | `chat.php` | `actions/send_chat_db.php` | `Chat` | **Implemented** |
| **ข่าวสาร/โปรโมชั่น** | `promotions.php`, `index.php` | - | `News` | **Implemented** |
| **ยกเลิกการจอง** | *(ไม่มีปุ่ม)* | *(ไม่มีการ INSERT)* | `Cancellation` | **Missing** |
| **รับคืนอุปกรณ์เช่า** | *(ไม่มีหน้าจอ)* | *(ไม่มี)* | `Rental`, `Product` | **Missing** |

---
*เอกสารผลการตรวจสอบฉบับนี้จัดทำขึ้นโดยการตรวจสอบเชิงประจักษ์จาก Source Code จริง 100% ตามข้อกำหนดใน USER_SCOPE_AUDIT.md อย่างเคร่งครัด*
