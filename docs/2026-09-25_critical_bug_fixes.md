# บันทึกการทำงาน: แก้ไข Bug เร่งด่วนและสร้างหน้า Profile (2026-09-25)

## 1. ทำอะไรบ้าง (What Was Done)
* **แก้ไขลิงก์ Action ที่เรียกไฟล์ไม่มีอยู่จริง (404 Error):**
  - ไฟล์ [admin/equipment_repairs.php](file:///c:/xampp/htdocs/ts-pattani/admin/equipment_repairs.php): แก้ไขฟอร์ม Modal ยืนยันซ่อมเสร็จและฟอร์ม Modal เสียหายถาวร จาก `actions/equipment_court_repair_finish_db.php` เป็น `actions/equipment_repair_finish_db.php`
  - ไฟล์ [admin/products.php](file:///c:/xampp/htdocs/ts-pattani/admin/products.php): แก้ไขฟอร์ม Modal บันทึกส่งซ่อมอุปกรณ์เช่า จาก `actions/equipment_court_repair_add_db.php` เป็น `actions/equipment_repair_add_db.php`
* **แก้ไขการแสดงผลสลิปและปุ่มชำระเงินในประวัติการจอง:**
  - ไฟล์ [booking_history.php](file:///c:/xampp/htdocs/ts-pattani/booking_history.php): 
    - เปลี่ยนการอ้างอิงฟิลด์สลิปจาก `$row['booking_slip']` (ซึ่งไม่มีใน DB) เป็น `$row['payment_slip']` (ฟิลด์จริงในตาราง `Booking`)
    - เพิ่มการเช็คสถานะการจองให้ครอบคลุม `'จองแล้ว'` (แสดงสีเขียว success)
    - เพิ่มเงื่อนไขแยกสถานะ `'รอแนบสลิป'` (สำหรับรายการที่ยังไม่ชำระเงินและยังไม่หมดเวลาล็อก) กับ `'รอตรวจสอบสลิป'` (สำหรับรายการที่แนบสลิปแล้ว กำลังรอแอดมินอนุมัติ) พร้อมแสดงปุ่ม "แนบสลิป" หรือ "ดูสลิป" อย่างถูกต้อง
* **แก้ไข Path รูปภาพข่าวสารในหน้าจัดการแอดมิน:**
  - ไฟล์ [admin/news.php](file:///c:/xampp/htdocs/ts-pattani/admin/news.php): แก้ไข Path แสดงรูปหน้าตารางรายการข่าวสารจาก `../assets/images/news/` เป็น `../uploads/news/` เพื่อให้แสดงรูปภาพจริงที่อัปโหลดตรงกับโฟลเดอร์ปลายทาง
* **สร้างหน้าระบบโปรไฟล์สมาชิก (Member Profile & Security):**
  - สร้างไฟล์ [profile.php](file:///c:/xampp/htdocs/ts-pattani/profile.php): หน้าดูและแก้ไขข้อมูลส่วนตัวของสมาชิก ประกอบด้วย Header แสดงระดับสมาชิก (Tier Bronze/Silver/Gold), แต้มสะสมปัจจุบัน, แต้มสะสมตลอดชีพ, ฟอร์มแก้ไขข้อมูลส่วนตัว, ฟอร์มเปลี่ยนรหัสผ่านที่ต้องยืนยันรหัสผ่านเดิม และกล่องสรุปสถิติการจอง
  - สร้างไฟล์ [actions/profile_edit_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/profile_edit_db.php): Backend Controller สำหรับตรวจสอบ Validation (เบอร์โทร 10 หลัก, เช็คเบอร์โทรซ้ำกับสมาชิกอื่น, ตรวจสอบรหัสผ่านเดิมด้วย `password_verify`, แฮชรหัสผ่านใหม่ด้วย `password_hash`)

---

## 2. ระบบเป็นอย่างไร (How the System Works)
* **กลไกการส่งซ่อมและตัดสต็อก (Equipment Repair Flow):**
  - เมื่อแอดมินส่งซ่อมอุปกรณ์จากหน้า [admin/products.php](file:///c:/xampp/htdocs/ts-pattani/admin/products.php) ข้อมูลจะถูกส่งไปยัง [admin/actions/equipment_repair_add_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/equipment_repair_add_db.php) ซึ่งจะทำการตัด `product_stock` ออกทันที และเพิ่มรายการลงตาราง `Equipment_repair` สถานะ 'กำลังซ่อม'
  - เมื่อซ่อมเสร็จหรือของเสียถาวร แอดมินกดทำรายการจาก [admin/equipment_repairs.php](file:///c:/xampp/htdocs/ts-pattani/admin/equipment_repairs.php) ข้อมูลจะส่งไปยัง [admin/actions/equipment_repair_finish_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/equipment_repair_finish_db.php) ถ้าเลือก 'ซ่อมแล้ว' ระบบจะบวกคืนสต็อกให้ หากเลือก 'เสียหายถาวร' จะตัดทิ้งจากระบบอย่างสมบูรณ์
* **กลไกการแสดงสถานะและการตรวจสอบสลิปการจอง (Booking Lifecycle):**
  - สมาชิกจองสนาม -> สร้างรายการใน `Booking` สถานะ 'รอตรวจสอบ', `payment_slip` เป็น NULL -> หน้า [booking_history.php](file:///c:/xampp/htdocs/ts-pattani/booking_history.php) แสดงป้าย "รอแนบสลิป" และปุ่ม "แนบสลิป"
  - สมาชิกอัปโหลดสลิปผ่าน [payment.php](file:///c:/xampp/htdocs/ts-pattani/payment.php) -> บันทึกชื่อสลิปลง `payment_slip` ในตาราง `Booking` และบันทึกลงตาราง `Payment` -> หน้า [booking_history.php](file:///c:/xampp/htdocs/ts-pattani/booking_history.php) แสดงป้าย "รอตรวจสอบสลิป" และปุ่ม "ดูสลิป"
  - แอดมินตรวจสอบสลิปใน [admin/payments.php](file:///c:/xampp/htdocs/ts-pattani/admin/payments.php) และกดยืนยัน -> สถานะใน `Booking` กลายเป็น 'จองแล้ว' -> หน้า [booking_history.php](file:///c:/xampp/htdocs/ts-pattani/booking_history.php) แสดงป้ายสีเขียว "จองสำเร็จ" และปุ่ม "ดูสลิป"
* **กลไกการจัดการโปรไฟล์และความปลอดภัย (Member Profile & Password):**
  - สมาชิกสามารถคลิกที่ชื่อโปรไฟล์ตนเองบน Navbar เพื่อเข้าสู่ [profile.php](file:///c:/xampp/htdocs/ts-pattani/profile.php)
  - การแก้ไขข้อมูลจะมีการตรวจสอบความปลอดภัย (Prevent duplicate phone, sanitization)
  - การเปลี่ยนรหัสผ่านมีการตรวจสอบความถูกต้องของรหัสผ่านเดิมด้วย PHP BCRYPT Hash ก่อนยินยอมให้อัปเดตรหัสผ่านใหม่

---

## 3. การใช้งานอย่างไร (How to Use / Test)
* **1. ทดสอบการส่งซ่อมและตัดสต็อก (ฝั่ง Admin):**
  - เข้าสู่ระบบแอดมินที่ `http://localhost/ts-pattani/admin/login.php`
  - ไปที่เมนู "จัดการสินค้า & อุปกรณ์" -> แท็บ "อุปกรณ์สำหรับเช่า" -> กดปุ่ม "แจ้งซ่อม" บนอุปกรณ์ที่ต้องการ -> กรอกจำนวนและสาเหตุ -> กดยืนยัน
  - ผลลัพธ์: สต็อกสินค้าจะลดลง และระบบจะพาไปยังหน้า "แจ้งซ่อมอุปกรณ์" พร้อมรายการส่งซ่อมใหม่
  - ที่หน้า "แจ้งซ่อมอุปกรณ์" -> กดปุ่ม "ซ่อมเสร็จ" -> กรอกค่าซ่อม -> กดยืนยัน
  - ผลลัพธ์: ระบบคืนสต็อกอุปกรณ์กลับเข้าสู่สินค้าเช่าทันที ไม่เกิดข้อผิดพลาด 404
* **2. ทดสอบดูรูปภาพข่าวสาร (ฝั่ง Admin):**
  - ไปที่เมนู "จัดการข่าวสาร" (`admin/news.php`)
  - ผลลัพธ์: รูปภาพประกอบข่าวสารที่อัปโหลดไว้จะแสดงในตารางอย่างสมบูรณ์ ไม่เป็นรูปเสีย/รูปแตก
* **3. ทดสอบหน้าโปรไฟล์สมาชิก (ฝั่ง Member):**
  - เข้าสู่ระบบสมาชิกที่ `http://localhost/ts-pattani/login.php`
  - กดที่เมนูดรอปดาวน์ชื่อผู้ใช้บน Navbar มุมขวาบน -> เลือก "โปรไฟล์ของฉัน" หรือเข้าตรงที่ `http://localhost/ts-pattani/profile.php`
  - ผลลัพธ์: เข้าสู่หน้าโปรไฟล์ได้ ไม่เกิด 404 สามารถดูระดับ Tier, พอยท์, แก้ไขเบอร์โทร/ชื่อ และทดสอบเปลี่ยนรหัสผ่านได้จริง
* **4. ทดสอบหน้าประวัติการจอง (ฝั่ง Member):**
  - ไปที่เมนู "ประวัติการจอง" (`booking_history.php`)
  - ผลลัพธ์: หากยังไม่แนบสลิป จะมีปุ่ม "แนบสลิป" นำไปหน้าชำระเงิน หากแนบสลิปแล้ว จะขึ้นสถานะ "รอตรวจสอบสลิป" และมีปุ่มสีฟ้า "ดูสลิป" สามารถกดเปิดดูรูปสลิปขนาดใหญ่ได้

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)
* **ปัญหาที่ 1: ชื่อไฟล์ Action ไม่ตรงกัน (Broken file paths)**
  - *สาเหตุ:* โค้ด HTML ชี้ไปยัง `actions/equipment_court_repair_*.php` แต่ชื่อไฟล์จริงในโฟลเดอร์คือ `actions/equipment_repair_*.php` ทำให้เกิด HTTP 404 Not Found
  - *การแก้ไข:* แก้ไข Form action ให้ตรงกับชื่อไฟล์ PHP จริงที่อยู่ในไดเรกทอรี
* **ปัญหาที่ 2: สลิปการโอนเงินไม่แสดงในหน้าประวัติการจอง**
  - *สาเหตุ:* โค้ดเดิมดึง `$row['booking_slip']` ซึ่งฟิลด์นี้ไม่มีอยู่ในตาราง `Booking` (ฟิลด์จริงคือ `payment_slip`) และการเช็คเงื่อนไขสถานะไม่ได้รองรับสถานะ `'จองแล้ว'` และไม่แยกเคสที่แนบสลิปแล้วแต่ยังรออนุมัติ
  - *การแก้ไข:* เปลี่ยนเป็น `$row['payment_slip']` และเขียน Logic การแสดงป้ายสถานะกับปุ่มกดใหม่ทั้งหมด
* **ปัญหาที่ 3: ลิงก์โปรไฟล์ใน Navbar กดแล้ว 404**
  - *สาเหตุ:* Navbar ฝั่งสมาชิกลิงก์ไปยัง `profile.php` แต่ยังไม่มีการสร้างไฟล์นี้ในระบบ
  - *การแก้ไข:* สร้างหน้า `profile.php` และคอนโทรลเลอร์ `actions/profile_edit_db.php` ขึ้นมาใหม่อย่างสมบูรณ์ พร้อมเชื่อมโยงระบบพอยท์ สถิติการจอง และการเปลี่ยนรหัสผ่าน
* **ปัญหาที่ 4: รูปข่าวสารแตกในหน้า Admin News**
  - *สาเหตุ:* หน้า `admin/news.php` ดึงรูปจาก `../assets/images/news/` ซึ่งเป็นโฟลเดอร์ที่ไม่ถูกต้อง (ระบบอัปโหลดจริงไปที่ `../uploads/news/`)
  - *การแก้ไข:* เปลี่ยน Path เป็น `../uploads/news/`
