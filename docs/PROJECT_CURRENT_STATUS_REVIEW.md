# 📋 รายงานสรุปสถานะการพัฒนา จุดที่ต้องแก้ไข และแนวทางปรับปรุงระบบ
## เว็บแอปพลิเคชันระบบจัดการสนามแบดมินตัน T.S. Pattani (Senior Project Review)
**วันที่วิเคราะห์:** 25 กันยายน 2569  
**การประเมิน:** ตรวจสอบโครงสร้าง Source Code, Database Schema, Business Logic และเทียบเคียงกับขอบเขตงานหัวข้อ 1.3

---

## 📌 สารบัญ
1. [บทสรุปภาพรวมโครงการ (Executive Summary)](#1-บทสรุปภาพรวมโครงการ-executive-summary)
2. [สิ่งที่พัฒนาเสร็จสมบูรณ์แล้ว (What Has Been Completed)](#2-สิ่งที่พัฒนาเสร็จสมบูรณ์แล้ว-what-has-been-completed)
3. [จุดผิดพลาดและบัคในโค้ดที่ต้องแก้ไขทันที (Critical Bugs & Broken Links)](#3-จุดผิดพลาดและบัคในโค้ดที่ต้องแก้ไขทันที-critical-bugs--broken-links)
4. [ฟังก์ชันที่ยังขาดตามขอบเขตข้อกำหนด 1.3 (Requirement Gaps)](#4-ฟังก์ชันที่ยังขาดตามขอบเขตข้อกำหนด-13-requirement-gaps)
5. [ข้อบกพร่องด้านความปลอดภัยและ Business Logic (Security & Robustness)](#5-ข้อบกพร่องด้านความปลอดภัยและ-business-logic-security--robustness)
6. [ตารางตรวจสอบ CRUD Matrix ทุกโมดูล](#6-ตารางตรวจสอบ-crud-matrix-ทุกโมดูล)
7. [แผนงานและลำดับความสำคัญในการปรับปรุง (Actionable Roadmap)](#7-แผนงานและลำดับความสำคัญในการปรับปรุง-actionable-roadmap)

---

## 1. บทสรุปภาพรวมโครงการ (Executive Summary)

โปรเจกต์ **T.S. Pattani** เป็นระบบบริหารจัดการสนามแบดมินตันครบวงจร พัฒนาด้วยสถาปัตยกรรม **PHP Native (Procedural + PDO)** ร่วมกับฐานข้อมูลเชิงสัมพันธ์ **MySQL/MariaDB (InnoDB Engine)** 18 ตาราง และระบบตกแต่งหน้าจอแบบ **Custom Vanilla CSS (Modern SaaS Theme)** โดยไม่ต้องพึ่งพาเฟรมเวิร์กขนาดใหญ่

ภาพรวมระบบในปัจจุบัน **พัฒนาฟังก์ชันหลักไปแล้วมากกว่า 85%** ครอบคลุมงานส่วนใหญ่ทั้งฝั่งลูกค้า (Member) และฝั่งผู้ดูแลระบบ (Admin) อย่างไรก็ตาม จากการสแกนโค้ดทุกไฟล์อย่างละเอียด **พบบัคระดับวิกฤต (Critical Bugs) ที่ทำให้บางหน้าจอขึ้น 404 Not Found**, จุดบกพร่องของชื่อคอลัมน์และพาธรูปภาพ, รวมถึง **Business Logic บางส่วนที่ยังขาดหายไปตามขอบเขตหัวข้อ 1.3** เช่น ระบบรับคืนอุปกรณ์เช่า, ขั้นตอนการยกเลิกการจองจริง, และกราฟช่วงอายุบนแดชบอร์ด

---

## 2. สิ่งที่พัฒนาเสร็จสมบูรณ์แล้ว (What Has Been Completed)

### 2.1 ระบบสมาชิกและการยืนยันตัวตน (Authentication & Authorization)
* **การสมัครสมาชิก ([register.php](file:///c:/xampp/htdocs/ts-pattani/register.php)):** ตรวจสอบเบอร์โทรซ้ำ 10 หลัก, เข้ารหัสผ่านด้วย `password_hash()`, และเปิดบัญชีแต้มเริ่มต้น (Tier Bronze) ลงตาราง `Point` อัตโนมัติผ่าน Database Transaction
* **การเข้าสู่ระบบ ([login.php](file:///c:/xampp/htdocs/ts-pattani/login.php)):** ตรวจสอบรหัสผ่านด้วย `password_verify()` พร้อมตรวจสอบสถานะบัญชี (`member_status = 'ระงับสิทธิ์'`)
* **ระบบกู้คืนรหัสผ่าน ([forgot_password.php](file:///c:/xampp/htdocs/ts-pattani/forgot_password.php)):** ยืนยันตัวตนด้วยเบอร์โทรและชื่อ-นามสกุลจริง พร้อมอัปเดตรหัสผ่านใหม่แบบแฮชลงตาราง `Member`
* **ระบบผู้ดูแลระบบและ RBAC ([admin/admins.php](file:///c:/xampp/htdocs/ts-pattani/admin/admins.php)):** แบ่งระดับสิทธิ์ชัดเจนระหว่าง `Admin` ทั่วไป และ `Super Admin`, มีระบบเพิ่ม/แก้ไขข้อมูล/เปลี่ยนรหัสผ่านแอดมิน ([admin_edit.php](file:///c:/xampp/htdocs/ts-pattani/admin/admin_edit.php)) และล็อกไม่ให้แอดมินลบตนเอง

### 2.2 ระบบจัดการสนามและงานซ่อมบำรุง (Court Management)
* **จัดการข้อมูลสนาม ([admin/courts.php](file:///c:/xampp/htdocs/ts-pattani/admin/courts.php)):** เพิ่มสนาม, แก้ไขข้อมูลผ่าน Modal Popup, กำหนดราคาปกติ/Peak/Off-peak และเวลาทำการ
* **การป้องกันความปลอดภัยฐานข้อมูล ([admin/actions/court_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_delete_db.php)):** ป้องกันการลบสนามที่เคยมีประวัติการจองในตาราง `Booking`
* **ระบบบันทึกซ่อมสนาม ([admin/court_repairs.php](file:///c:/xampp/htdocs/ts-pattani/admin/court_repairs.php)):** บันทึกสาเหตุ, วันที่เริ่ม-คาดว่าจะเสร็จ, เปลี่ยนสถานะสนามเป็น `ปิดปรับปรุง` และบันทึกค่าใช้จ่ายเมื่อซ่อมเสร็จ

### 2.3 เครื่องยนต์การจองสนามและการล็อกเวลา (Booking Engine & Locking)
* **จองแบบ One-stop Service ([booking.php](file:///c:/xampp/htdocs/ts-pattani/booking.php)):** เลือกวัน เวลา (ขั้นต่ำ 1 ชม.) สนาม อุปกรณ์เช่า และสินค้าบริโภคในหน้าเดียว
* **การป้องกันการจองเวลาซ้อน (Overlap Protection):** ตรวจสอบเงื่อนไข `(booking_start_time < :end_time AND booking_end_time > :start_time)` ในวันและสนามเดียวกัน
* **การล็อกสนามชั่วคราว (Atomic Lock 15 นาที):** เมื่อสร้างการจอง ระบบจะล็อกสถานะเป็น `รอตรวจสอบ` พร้อมบันทึก `booking_lock_expire` (+15 นาที)
* **Server-side Price Calculation ([actions/booking_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php)):** คำนวณราคาใหม่จากฐานข้อมูล ป้องกันการปลอมแปลงราคาผ่าน Web Inspector
* **ระบบ Lazy Auto-cancel ([admin/includes/auto_cancel.php](file:///c:/xampp/htdocs/ts-pattani/admin/includes/auto_cancel.php)):** เคลียร์การจองที่หมดเวลา 15 นาทีโดยไม่อัปโหลดสลิป พร้อมบวกสต็อกสินค้า/อุปกรณ์เช่าคืนให้อัตโนมัติเมื่อมีการโหลดหน้า `booking.php` หรือ `payments.php`

### 2.4 ระบบชำระเงินและตรวจสอบสลิป (Payment & Slip Verification)
* **หน้าชำระเงิน ([payment.php](file:///c:/xampp/htdocs/ts-pattani/payment.php)):** แสดง QR Code พร้อมตัวจับเวลานับถอยหลัง 15 นาที
* **ระบบอัปโหลดสลิป ([actions/payment_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/payment_db.php)):** ตรวจสอบ MIME Type (`finfo`) และจำกัดขนาดไม่เกิน 5MB บันทึกลงตาราง `Payment`
* **การตรวจสอบของแอดมิน ([admin/payments.php](file:///c:/xampp/htdocs/ts-pattani/admin/payments.php)):** เปิดดูสลิปภาพใหญ่ผ่าน Modal และกด `ยืนยันแล้ว` หรือ `ปฏิเสธ`
* **การแยกรายรับทางบัญชีอัตโนมัติ ([admin/actions/payment_verify_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/payment_verify_db.php)):** แยกลงตาราง `Revenue` เป็น 3 คอลัมน์ (ค่าสนาม, ค่าเช่า, ค่าสินค้า) และคำนวณแต้มสะสมให้สมาชิก (100 บาท = 1 พอยท์) บันทึกลง `Point` และ `Point_Transaction`

### 2.5 ระบบคะแนนสะสมและแลกของรางวัล (Loyalty & Rewards)
* **แคตตาล็อกของรางวัล ([rewards.php](file:///c:/xampp/htdocs/ts-pattani/rewards.php)):** แสดงการ์ดของรางวัล ตรวจสอบเงื่อนไขพอยท์และระดับสมาชิก (Bronze, Silver, Gold) ปิดปุ่มหากสิทธิ์ไม่พอ
* **ความปลอดภัยในการแลกของ ([actions/redeem_reward_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/redeem_reward_db.php)):** ใช้ `SELECT ... FOR UPDATE` และ Database Transaction ป้องกันปัญหา Concurrency / แย่งแลกจนสต็อกติดลบ
* **การจัดการของรางวัลฝั่งแอดมิน ([admin/rewards.php](file:///c:/xampp/htdocs/ts-pattani/admin/rewards.php)):** เพิ่ม/แก้ไข/ลบของรางวัล พร้อมลบไฟล์รูปภาพเก่าออกจากเซิร์ฟเวอร์
* **ประวัติการแลกรางวัล ([admin/redemptions.php](file:///c:/xampp/htdocs/ts-pattani/admin/redemptions.php)):** หน้าแสดงประวัติการใช้คะแนนแลกของรางวัลทั้งหมด เพื่อให้แอดมินตรวจเช็กและจ่ายของให้ลูกค้า

### 2.6 ระบบขายหน้าร้าน (POS) และสต็อกสินค้า
* **ระบบแคชเชียร์ POS ([admin/pos.php](file:///c:/xampp/htdocs/ts-pattani/admin/pos.php)):** แยกแท็บสินค้า/อุปกรณ์เช่า, คำนวณเงินทอน, เลือกวิธีชำระ (เงินสด/QR) และตัดสต็อกสินค้าทันที
* **พิมพ์ใบเสร็จความร้อน 80mm ([admin/receipt.php](file:///c:/xampp/htdocs/ts-pattani/admin/receipt.php)):** รองรับการสั่งพิมพ์ทันทีหลังชำระเงิน
* **ประวัติการขาย POS ([admin/pos_history.php](file:///c:/xampp/htdocs/ts-pattani/admin/pos_history.php)):** ดูย้อนหลังและพิมพ์ใบเสร็จซ้ำได้
* **การจัดซื้อสินค้าเข้าคลัง ([admin/purchases.php](file:///c:/xampp/htdocs/ts-pattani/admin/purchases.php)):** บันทึกรับสินค้าและคำนวณต้นทุน พร้อมบวกสต็อกเข้าตาราง `Product` อัตโนมัติ

### 2.7 ระบบสื่อสาร ข่าวสาร และแดชบอร์ด
* **แชทติดต่อแอดมิน ([chat.php](file:///c:/xampp/htdocs/ts-pattani/chat.php) & [admin/chats.php](file:///c:/xampp/htdocs/ts-pattani/admin/chats.php)):** รับส่งข้อความแยกฝั่งผู้ใช้-แอดมิน พร้อมระบบ AJAX Polling ฝั่งแอดมินดึงข้อความใหม่ทุก 5 วินาที
* **ข่าวสารและโปรโมชัน ([promotions.php](file:///c:/xampp/htdocs/ts-pattani/promotions.php) & [admin/news.php](file:///c:/xampp/htdocs/ts-pattani/admin/news.php)):** แสดงข่าวสารแบบ Grid Card พร้อม Popup Modal อ่านเนื้อหาฉบับเต็ม และระบบเพิ่ม/แก้ไข/ลบข่าวสารหลังบ้าน
* **แดชบอร์ดสรุปสถิติ ([admin/index.php](file:///c:/xampp/htdocs/ts-pattani/admin/index.php)):** การ์ดสรุป 4 ตัวเลข, กราฟแท่งรายรับ 7 วัน, กราฟโดนัทสัดส่วนรายได้ 3 หมวด, ลูกค้าประจำ Top 5, กราฟสัดส่วนเพศ และกราฟสัดส่วนอาชีพ (Chart.js)
* **การส่งออกรายงาน ([admin/actions/export_csv.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/export_csv.php)):** Export รายการจอง, สมาชิก, และรายได้เป็นไฟล์ `.csv` รองรับ UTF-8 BOM เปิดใน Microsoft Excel ไม่เป็นภาษาต่างดาว

---

## 3. จุดผิดพลาดและบัคในโค้ดที่ต้องแก้ไขทันที (Critical Bugs & Broken Links)

จากการตรวจสอบ Source Code เชิงลึก พบข้อผิดพลาดที่ส่งผลต่อการทำงานของระบบ (Runtime Errors) ดังนี้:

### 🔴 1. ลิงก์ Action ส่งซ่อมและปิดงานซ่อมอุปกรณ์ผิดพลาด (เกิดข้อผิดพลาด 404 Not Found)
* **ตำแหน่งที่พบ:**
  * [admin/products.php#L164](file:///c:/xampp/htdocs/ts-pattani/admin/products.php#L164): 
    `<form action="actions/equipment_court_repair_add_db.php" method="POST">`
  * [admin/equipment_repairs.php#L121](file:///c:/xampp/htdocs/ts-pattani/admin/equipment_repairs.php#L121) และ [#L146](file:///c:/xampp/htdocs/ts-pattani/admin/equipment_repairs.php#L146):
    `<form action="actions/equipment_court_repair_finish_db.php" method="POST">`
* **สาเหตุ:** มีคำว่า `court_` เกินเข้ามาในชื่อไฟล์ Action แต่ไฟล์ที่เขียน Logic อยู่จริงในโฟลเดอร์คือ:
  * `admin/actions/equipment_repair_add_db.php`
  * `admin/actions/equipment_repair_finish_db.php`
* **ผลกระทบ:** เมื่อแอดมินกดส่งซ่อมอุปกรณ์ในหน้า `products.php` หรือกดยืนยันซ่อมเสร็จ/ตัดทิ้งในหน้า `equipment_repairs.php` เว็บเบราว์เซอร์จะขึ้น **404 Not Found** ส่งผลให้ระบบซ่อมอุปกรณ์ใช้งานไม่ได้

### 🔴 2. เมนูนำทางมีลิงก์ไปยังไฟล์ที่ไม่มีอยู่จริง (`profile.php`)
* **ตำแหน่งที่พบ:** [includes/navbar.php#L49](file:///c:/xampp/htdocs/ts-pattani/includes/navbar.php#L49)
  `<a href="profile.php"><i class="fas fa-id-card"></i> โปรไฟล์ของฉัน</a>`
* **สาเหตุ:** มีการใส่ลิงก์ "โปรไฟล์ของฉัน" ใน Dropdown เมนู แต่ในโปรเจกต์ **ยังไม่มีไฟล์ `profile.php`**
* **ผลกระทบ:** เมื่อลูกค้าล็อกอินแล้วกดเมนูนี้ จะพบหน้า **404 Not Found** ทันที

### 🔴 3. ปุ่ม "ดูสลิป" ในหน้าประวัติการจองไม่แสดงผล (อ้างอิงชื่อคอลัมน์ผิด)
* **ตำแหน่งที่พบ:** [booking_history.php#L109-L111](file:///c:/xampp/htdocs/ts-pattani/booking_history.php#L109-L111)
  ```php
  <?php elseif (!empty($row['booking_slip'])): ?>
      <a href="uploads/slips/<?php echo $row['booking_slip']; ?>" target="_blank" class="btn-action-sm btn-view-slip">
          <i class="fas fa-receipt"></i> ดูสลิป
      </a>
  ```
* **สาเหตุ:** ในตาราง `Booking` คอลัมน์ที่เก็บชื่อสลิปคือ **`payment_slip`** ไม่ใช่ `booking_slip`
* **ผลกระทบ:** ลูกค้าที่อัปโหลดสลิปแล้ว จะไม่มีปุ่ม "ดูสลิป" ปรากฏในหน้าประวัติการจองเลย

### 🔴 4. ป้ายสถานะการจองในหน้าประวัติการจองแสดงสีผิดเพี้ยน
* **ตำแหน่งที่พบ:** [booking_history.php#L94](file:///c:/xampp/htdocs/ts-pattani/booking_history.php#L94)
  ```php
  if ($status == 'ชำระเงินแล้ว' || $status == 'อนุมัติแล้ว') {
      $status_class = 'status-success';
  } elseif ($status == 'ยกเลิก') {
      $status_class = 'status-cancel';
  }
  ```
* **สาเหตุ:** ค่า ENUM ในตาราง `Booking` เมื่อแอดมินอนุมัติแล้วคือ **`'จองแล้ว'`** แต่โค้ดไปดักคำว่า `'ชำระเงินแล้ว'` หรือ `'อนุมัติแล้ว'`
* **ผลกระทบ:** รายการที่ได้รับการอนุมัติ (`'จองแล้ว'`) จะตกไปอยู่ที่เงื่อนไข `status-pending` (แสดงเป็นป้ายสีเหลืองว่ากำลังรอตรวจ) ทำให้ลูกค้าสับสนว่าแอดมินอนุมัติหรือยัง

### 🔴 5. รูปภาพปกข่าวสารในตารางแอดมินไม่แสดงผล (Broken Image)
* **ตำแหน่งที่พบ:** [admin/news.php#L48](file:///c:/xampp/htdocs/ts-pattani/admin/news.php#L48)
  `<img src="../assets/images/news/<?php echo htmlspecialchars($row['news_image']); ?>" ...>`
* **สาเหตุ:** พาธโฟลเดอร์ที่บันทึกรูปข่าวสารจริงคือ `../uploads/news/` (สอดคล้องกับ `news_add_db.php`, `news_edit.php` และ `promotions.php`) แต่หน้า `news.php` ดึงจาก `../assets/images/news/`
* **ผลกระทบ:** รูปภาพปกข่าวสารในตารางของแอดมินจะแสดงเป็นรูปภาพเสีย (Broken Icon) ทั้งหมด

### 🔴 6. ENUM Status ของตาราง Rental ใน `setup.php` ไม่ครอบคลุม
* **ตำแหน่งที่พบ:** [config/setup.php#L230](file:///c:/xampp/htdocs/ts-pattani/config/setup.php#L230)
  `` `rental_status` ENUM('กำลังเช่า', 'คืนแล้ว') NOT NULL DEFAULT 'กำลังเช่า' ``
* **สาเหตุ:** ใน [actions/booking_db.php#L154](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php#L154) บันทึกเป็น `'รอตรวจสอบ'` และใน [admin/includes/auto_cancel.php#L38](file:///c:/xampp/htdocs/ts-pattani/admin/includes/auto_cancel.php#L38) อัปเดตเป็น `'ยกเลิก'`
* **ผลกระทบ:** หากติดตั้งฐานข้อมูลใหม่บน MySQL Strict Mode คำสั่ง INSERT หรือ UPDATE ตาราง `Rental` จะเกิด Fatal Error `Data truncated for column 'rental_status'`

---

## 4. ฟังก์ชันที่ยังขาดตามขอบเขตข้อกำหนด 1.3 (Requirement Gaps)

เมื่อเทียบเคียงกับข้อกำหนดใน **บทที่ 1 หัวข้อ 1.3 (ขอบเขตการทำงานของระบบ)** พบรายการที่ยังไม่มี หรือมีไม่ครบถ้วนดังนี้:

| หัวข้อ Requirement | ข้อกำหนดตามเอกสารโครงงาน | สถานะในโค้ดปัจจุบัน | สิ่งที่ต้องเพิ่มเติม |
|---|---|:---:|---|
| **1.3.5 / 1.3.8<br>ระบบคืนอุปกรณ์เช่า** | แอดมินต้องตรวจอุปกรณ์ที่ยังไม่คืน และมีปุ่มบันทึกรับคืนอุปกรณ์เพื่อปรับ Stock กลับเข้าคลัง | ❌ **ยังไม่มี** | ปัจจุบันเมื่อลูกค้าจอง สต็อกจะถูกตัด แต่**ไม่มีหน้าจอหรือปุ่มรับคืนอุปกรณ์** ทำให้สต็อกอุปกรณ์เช่าไม่เคยถูกบวกกลับเข้ามา |
| **1.3.11 / 1.3.7<br>ระบบยกเลิกการจอง** | สมาชิกยกเลิกได้เองเฉพาะสถานะ `รอตรวจสอบ`, สถานะ `จองแล้ว` ต้องติดต่อแอดมิน พร้อมบันทึกประวัติลง `Cancellation` | ⚠️ **ไม่สมบูรณ์** | มีหน้า [cancellations.php](file:///c:/xampp/htdocs/ts-pattani/admin/cancellations.php) และโค้ดพิจารณาคืนเงิน แต่**ไม่มีโค้ด `INSERT INTO Cancellation` เลยแม้แต่จุดเดียว** ลูกค้าและแอดมินไม่มีปุ่มสร้างคำขอยกเลิก |
| **1.3.10<br>Customer Analytics** | แดชบอร์ดต้องมี **กราฟช่วงอายุ** และ **กราฟอาชีพ** ของลูกค้าที่มาใช้บริการ | ⚠️ **ขาดช่วงอายุ** | หน้าแดชบอร์ดมีกราฟเพศ และกราฟอาชีพแล้ว แต่**ขาดกราฟช่วงอายุ** (Age Demographic) ทั้งที่มีคอลัมน์ `member_age` ในตาราง `Member` |
| **1.3.10<br>การติดตามค่าใช้จ่าย** | ติดตามค่าใช้จ่ายซ่อมบำรุงสนาม และค่าใช้จ่ายจัดซื้อสินค้า | ⚠️ **ยังไม่รวมศูนย์** | มีตารางเก็บ `repair_cost` และ `purchase_total_price` แต่บนแดชบอร์ดไม่ได้นำมาสรุปเป็นยอด "ค่าใช้จ่ายรวม" หรือ "กำไรสุทธิ" |
| **1.3.4<br>สถานะสนามแบบเรียลไทม์** | หน้าจองต้องแสดงสถานะสนาม (ว่าง, รอตรวจสอบ, จองแล้ว, ปิดปรับปรุง) ตามช่วงเวลาที่เลือก | ⚠️ **ยังไม่มีตารางเวลา** | หน้า `booking.php` เป็นการเลือกจาก Radio Button หากเวลาชนจะแจ้งเตือนเมื่อกด Submit ยังไม่มี Time-Slot Matrix แบบภาพรวม |
| **1.3.4 / 1.3.6<br>ราคา Peak / Off-peak** | ระบบรองรับการคิดราคาช่วงเวลาเร่งด่วนและช่วงปกติ | ⚠️ **มีเฉพาะ UI แอดมิน** | แอดมินตั้งราคา Peak/Off-peak ได้ในตาราง `Court` แต่ใน `booking_db.php` **ยังไม่ได้นำเวลาจองมาคิดแยกราคา** (ยังใช้ราคาปกติคูณชั่วโมงเสมอ) |
| **1.3.8<br>การจอง Walk-in บน POS** | ระบบ POS รองรับการบันทึกการจองสนามสำหรับลูกค้าที่เดินเข้ามาหน้าร้าน | ⚠️ **มีเฉพาะขายของ** | หน้า POS รองรับเฉพาะการขายสินค้าและเช่าอุปกรณ์ ยังไม่มีฟังก์ชันเปิดคอร์ตให้ลูกค้า Walk-in |
| **1.3.2<br>ประวัติของสมาชิก** | สมาชิกดูข้อมูลส่วนตัว และประวัติการแลกของรางวัลของตนเอง | ⚠️ **ยังไม่มี** | สมาชิกดูประวัติการจองได้ แต่ยังไม่มีหน้าดูประวัติการแลกของรางวัลของตนเอง (ดูได้เฉพาะแอดมินใน `redemptions.php`) |

---

## 5. ข้อบกพร่องด้านความปลอดภัยและ Business Logic (Security & Robustness)

### 5.1 ด้านความมั่นคงปลอดภัย (Security)
1. **ขาดระบบป้องกัน CSRF (Cross-Site Request Forgery):**
   * ฟอร์มส่งข้อมูลสำคัญทุกจุด (เช่น การโอนสิทธิ์แอดมิน, เปลี่ยนรหัสผ่าน, ยืนยันสลิป, ลบข้อมูล) ยังไม่มีการสร้างและตรวจสอบ `$_SESSION['csrf_token']`
2. **การบังคับใช้สถานะ "ระงับสิทธิ์" ระหว่างเซสชันยังไม่รัดกุม:**
   * หน้า [login_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/login_db.php) ดักจับสมาชิกที่ถูกระงับสิทธิ์ได้ถูกต้อง แต่ในหน้า [booking.php](file:///c:/xampp/htdocs/ts-pattani/booking.php) และ [actions/booking_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php) ตรวจสอบเพียง `isset($_SESSION['member_id'])` โดยไม่ได้ Query ซ้ำว่าสถานะปัจจุบันยังเป็น `ปกติ` อยู่หรือไม่ หากสมาชิกล็อกอินค้างไว้แล้วแอดมินเพิ่งไประงับสิทธิ์ สมาชิกจะยังสามารถจองสนามได้ต่อจนกว่าจะหลุด Session

### 5.2 ด้าน Business Logic
1. **การปฏิเสธสลิปไม่คืนสต็อกอุปกรณ์เช่า:**
   * ใน [admin/actions/payment_verify_db.php#L104-L106](file:///c:/xampp/htdocs/ts-pattani/admin/actions/payment_verify_db.php#L104-L106) เมื่อแอดมินกด "ปฏิเสธสลิป" สถานะการจองเปลี่ยนเป็น `'ยกเลิก'` แต่**ไม่มีการบวกสต็อกสินค้า/อุปกรณ์เช่ากลับเข้าตาราง `Product`** (สต็อกจะจมหายไป)
2. **การพิจารณาคืนเงินใน `cancellation_action_db.php` ไม่ปลดสถานะการจอง:**
   * เมื่อแอดมินอนุมัติคืนเงินคำขอยกเลิก โค้ดมีการคืนแต้มให้ลูกค้า แต่**ไม่ได้เปลี่ยนสถานะ `Booking` ให้เป็น `'ยกเลิก'`** และไม่ได้นำยอดเงินออกจาก `Revenue`
3. **ระบบแชทฝั่งสมาชิกไม่มี Auto-refresh:**
   * ฝั่งแอดมินมีการทำ AJAX Polling ทุก 5 วินาทีเรียบร้อยแล้ว แต่ฝั่งสมาชิก [chat.php](file:///c:/xampp/htdocs/ts-pattani/chat.php) ยังต้องกด Refresh หน้าจอเอง (F5) เมื่อแอดมินตอบกลับ

---

## 6. ตารางตรวจสอบ CRUD Matrix ทุกโมดูล

| โมดูล (Module) | Create | Read | Update | Delete | สถานะปัจจุบัน | ไฟล์หลักที่เกี่ยวข้อง |
|---|:---:|:---:|:---:|:---:|:---:|---|
| **สมาชิก (Member)** | ✅ | ✅ | ✅ | ➖ | **สมบูรณ์** | `register.php`, `admin/members.php`, `member_edit.php` |
| **โปรไฟล์สมาชิก (Profile)** | ❌ | ❌ | ❌ | ❌ | 🔴 **ยังไม่ได้สร้าง** | ขาด `profile.php` (มีลิงก์ค้างใน Navbar) |
| **แต้มสะสม (Point & Tier)** | ✅ | ✅ | ✅ | ➖ | **สมบูรณ์** | `Point`, `Point_Transaction`, `admin/member_edit_db.php` |
| **ของรางวัล (Reward)** | ✅ | ✅ | ✅ | ✅ | **สมบูรณ์** | `admin/rewards.php`, `reward_edit.php`, `rewards.php` |
| **ประวัติแลกรางวัล (Redemption)** | ✅ | ⚠️ Admin ดูได้ | ➖ | ➖ | ⚠️ **ขาดฝั่งสมาชิก** | `actions/redeem_reward_db.php`, `admin/redemptions.php` |
| **สนาม (Court)** | ✅ | ✅ | ✅ | ✅ | **สมบูรณ์** | `admin/courts.php`, `court_add_db.php`, `court_edit_db.php` |
| **ซ่อมสนาม (Court Repair)** | ✅ | ✅ | ✅ | ➖ | **สมบูรณ์** | `admin/court_repairs.php`, `court_repair_add_db.php` |
| **สินค้าและอุปกรณ์ (Product)** | ✅ | ✅ | ✅ | ✅ | **สมบูรณ์** | `admin/products.php`, `product_add.php`, `product_edit.php` |
| **จัดซื้อเข้าสต็อก (Purchase)** | ✅ | ✅ | ➖ | ➖ | **สมบูรณ์** | `admin/purchases.php`, `purchase_add.php` |
| **ซ่อมอุปกรณ์ (Eq. Repair)** | 🔴 บัค 404 | ✅ | 🔴 บัค 404 | ➖ | 🔴 **ติดบัคชื่อ Action** | `admin/equipment_repairs.php`, `products.php` |
| **การคืนอุปกรณ์เช่า (Return)** | ❌ | ❌ | ❌ | ❌ | 🔴 **ยังไม่มีระบบ** | ขาดหน้าจอจัดการอุปกรณ์ค้างคืนและการรับคืน |
| **การจองสนาม (Booking)** | ✅ | ✅ | ✅ | ➖ | **สมบูรณ์** | `booking.php`, `actions/booking_db.php`, `booking_history.php` |
| **ชำระเงินและสลิป (Payment)** | ✅ | ✅ | ✅ | ➖ | **สมบูรณ์** | `payment.php`, `payment_db.php`, `admin/payments.php` |
| **รายได้ระบบ (Revenue)** | ✅ | ✅ | ➖ | ➖ | **สมบูรณ์** | `Revenue`, `admin/actions/payment_verify_db.php`, `pos_checkout_db.php` |
| **ขายหน้าร้าน (POS)** | ✅ | ✅ | ➖ | ➖ | **สมบูรณ์ (ขาด Walk-in)** | `admin/pos.php`, `pos_history.php`, `receipt.php` |
| **แชทสนทนา (Chat)** | ✅ | ✅ | ➖ | ➖ | **สมบูรณ์ (ขาด Auto-refresh สมาชิก)** | `chat.php`, `admin/chats.php`, `chat_messages_db.php` |
| **ข่าวสาร (News)** | ✅ | ⚠️ บัคภาพ | ✅ | ✅ | ⚠️ **บัคพาธรูปแอดมิน** | `promotions.php`, `admin/news.php`, `news_edit.php` |
| **การยกเลิกการจอง (Cancellation)** | ❌ | ✅ | ⚠️ | ➖ | 🔴 **ขาดจุดสร้างคำขอ** | `admin/cancellations.php`, `cancellation_action_db.php` |
| **ผู้ดูแลระบบ (Admin RBAC)** | ✅ | ✅ | ✅ | ✅ | **สมบูรณ์** | `admin/admins.php`, `admin_add.php`, `admin_edit.php` |
| **แดชบอร์ดและการวิเคราะห์** | ➖ | ⚠️ | ➖ | ➖ | ⚠️ **ขาดกราฟช่วงอายุและค่าใช้จ่าย** | `admin/index.php`, `admin/actions/export_csv.php` |

---

## 7. แผนงานและลำดับความสำคัญในการปรับปรุง (Actionable Roadmap)

### 🚀 ระยะที่ 1: แก้ไขบัคและข้อผิดพลาดวิกฤตทันที (Quick Wins & Bug Fixes)
1. **แก้ชื่อไฟล์ Action ในระบบซ่อมอุปกรณ์:**
   * แก้ [admin/products.php#L164](file:///c:/xampp/htdocs/ts-pattani/admin/products.php#L164) ให้ชี้ไปที่ `actions/equipment_repair_add_db.php`
   * แก้ [admin/equipment_repairs.php#L121, #L146](file:///c:/xampp/htdocs/ts-pattani/admin/equipment_repairs.php#L121) ให้ชี้ไปที่ `actions/equipment_repair_finish_db.php`
2. **แก้ไขหน้าประวัติการจอง ([booking_history.php](file:///c:/xampp/htdocs/ts-pattani/booking_history.php)):**
   * เปลี่ยนตัวแปร `$row['booking_slip']` เป็น `$row['payment_slip']` เพื่อให้ปุ่มดูสลิปทำงาน
   * ปรับเงื่อนไขการตรวจป้ายสถานะให้รองรับคำว่า `$status == 'จองแล้ว'` เพื่อให้แสดงสีเขียวอย่างถูกต้อง
3. **แก้ไขพาธรูปภาพในตารางข่าวสาร ([admin/news.php](file:///c:/xampp/htdocs/ts-pattani/admin/news.php#L48)):**
   * เปลี่ยนจาก `../assets/images/news/` เป็น `../uploads/news/`
4. **สร้างหน้าโปรไฟล์สมาชิก (`profile.php`):**
   * สร้างหน้าสำหรับให้สมาชิกดูและแก้ไขข้อมูลส่วนตัว (ชื่อ, เบอร์โทร, เพศ, อายุ, อาชีพ) และเปลี่ยนรหัสผ่าน เพื่อปิดจุดลิงก์ 404 ใน Navbar
5. **ปรับปรุง ENUM ของตาราง `Rental` ใน `config/setup.php`:**
   * เพิ่มค่า `'รอตรวจสอบ'` และ `'ยกเลิก'` ในคอลัมน์ `rental_status`

---

### 🛠️ ระยะที่ 2: เติมเต็ม Business Logic ตามขอบเขต 1.3 (Requirement Completion)
1. **พัฒนาระบบรับคืนอุปกรณ์เช่า (Equipment Return):**
   * เพิ่มหน้า `admin/rentals.php` แสดงรายการอุปกรณ์ที่อยู่ระหว่างการเช่า (`rental_status = 'กำลังเช่า'`)
   * มีปุ่ม **"รับคืนอุปกรณ์"** เพื่อปรับสถานะเป็น `'คืนแล้ว'`, บันทึกเวลาคืนจริง (`rental_return_time = NOW()`) และบวกสต็อกสินค้ากลับเข้าตาราง `Product` อัตโนมัติ
2. **พัฒนาระบบขอยกเลิกการจอง (Cancellation Flow):**
   * ฝั่งสมาชิก: เพิ่มปุ่ม **"ยกเลิกการจอง"** ในหน้า [booking_history.php](file:///c:/xampp/htdocs/ts-pattani/booking_history.php)
     * หากสถานะเป็น `'รอตรวจสอบ'` ให้ยกเลิกได้ทันที คืนสต็อกอุปกรณ์ และปลดล็อกสนาม
     * หากสถานะเป็น `'จองแล้ว'` ให้เปิด Modal ให้ระบุเหตุผล แล้วบันทึกคำขอลงตาราง `Cancellation` (สถานะ `'รอดำเนินการ'`)
   * ฝั่งแอดมิน: เมื่อกดอนุมัติคืนเงินใน `cancellation_action_db.php` ให้สั่ง `UPDATE Booking SET booking_status = 'ยกเลิก'` และบวกสต็อกอุปกรณ์เช่าคืนให้ครบถ้วน
3. **เพิ่มกราฟช่วงอายุ (Age Demographic) บนแดชบอร์ด ([admin/index.php](file:///c:/xampp/htdocs/ts-pattani/admin/index.php)):**
   * เขียนคำสั่ง SQL จัดกลุ่มอายุ เช่น `< 20 ปี`, `20-29 ปี`, `30-39 ปี`, `40 ปีขึ้นไป` และนำมาวาดกราฟแท่งหรือโดนัทด้วย Chart.js ร่วมกับกราฟเพศและอาชีพที่มีอยู่เดิม
4. **เพิ่มสรุปค่าใช้จ่ายและกำไรสุทธิบนแดชบอร์ด:**
   * สรุปยอดค่าซ่อมสนาม (`Court_repair`), ซ่อมอุปกรณ์ (`Equipment_repair`) และยอดจัดซื้อ (`Purchase`) เพื่อแสดงช่อง "ค่าใช้จ่ายรวม" และคำนวณ "รายได้สุทธิ (กำไร)"

---

### 🛡️ ระยะที่ 3: ยกระดับความมั่นคงปลอดภัยและประสบการณ์ผู้ใช้ (Security & UX Polish)
1. **ป้องกันสมาชิกถูกระงับสิทธิ์ระหว่างการใช้งาน:**
   * ตรวจสอบ `member_status` จากฐานข้อมูลใน [actions/booking_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php) เสมอก่อนสร้างรายการจอง
2. **คืนสต็อกอุปกรณ์เมื่อปฏิเสธสลิป:**
   * ใน [admin/actions/payment_verify_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/payment_verify_db.php) เมื่อสลิปถูกปฏิเสธ ให้ดึงรายการ `Rental` ที่ผูกกับ Booking นั้นมาบวกสต็อกคืนใน `Product`
3. **Auto-refresh แชทฝั่งสมาชิก:**
   * นำสคริปต์ AJAX Polling มาติดตั้งใน [chat.php](file:///c:/xampp/htdocs/ts-pattani/chat.php) ให้ดึงข้อความใหม่ทุก 5-10 วินาที เพื่อให้สมาชิกเห็นข้อความตอบกลับของแอดมินได้แบบเรียลไทม์
4. **ติดตั้ง CSRF Token ป้องกันการโจมตี:**
   * ฝัง Token สุ่มใน Session และส่งผ่าน Hidden Input ในฟอร์มสำคัญทุกหน้า

---
*เอกสารฉบับนี้จัดทำขึ้นจากการตรวจสอบ Source Code เชิงลึก สามารถใช้อ้างอิงเป็นเกณฑ์ในการเตรียมตัวสอบโครงงานและใช้เป็นแนวทางพัฒนาต่อยอดให้สมบูรณ์ 100%*
