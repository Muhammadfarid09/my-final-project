# 📝 บันทึกประวัติการพัฒนาและปรับปรุงระบบ (System Changelog)

**โครงการ:** เว็บแอปพลิเคชันระบบจัดการสนามแบดมินตัน T.S. Pattani  
**สถานะการดำเนินงาน:** พัฒนาฟีเจอร์หลักและแก้ไขจุดบกพร่องตามแผนงานสมบูรณ์ 6 หมวดหมู่

---

## 📊 สรุปภาพรวมงานที่ดำเนินการแล้ว (Implementation Summary)

| ลำดับ  | รายการระบบ / งานที่ทำ                                           |    ฝั่งผู้ใช้งาน    |    สถานะ     | ไฟล์ที่เกี่ยวข้องหลัก                                                                                                                                                                                                                                                                                                                                                              |
| :----: | :-------------------------------------------------------------- | :-----------------: | :----------: | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **1**  | **แก้ไขบัควิกฤตและเสริมความปลอดภัย (Critical Bug Fixes)**       |   Member / Admin    | ✅ เรียบร้อย | [actions/payment_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/payment_db.php), [admin/actions/payment_verify_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/payment_verify_db.php), [actions/booking_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php)                                                                                            |
| **2**  | **ระบบกู้คืนรหัสผ่าน (Forgot Password)**                        |   สมาชิก (Member)   | ✅ เรียบร้อย | [forgot_password.php](file:///c:/xampp/htdocs/ts-pattani/forgot_password.php), [actions/forgot_password_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/forgot_password_db.php), [login.php](file:///c:/xampp/htdocs/ts-pattani/login.php)                                                                                                                                      |
| **3**  | **ระบบแลกของรางวัลฝั่งสมาชิก (Rewards Redemption)**             |   สมาชิก (Member)   | ✅ เรียบร้อย | [rewards.php](file:///c:/xampp/htdocs/ts-pattani/rewards.php), [actions/redeem_reward_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/redeem_reward_db.php), [includes/navbar.php](file:///c:/xampp/htdocs/ts-pattani/includes/navbar.php)                                                                                                                                      |
| **4**  | **ระบบแชทติดต่อแอดมิน (Customer Support Chat)**                 |   สมาชิก (Member)   | ✅ เรียบร้อย | [chat.php](file:///c:/xampp/htdocs/ts-pattani/chat.php), [actions/send_chat_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/send_chat_db.php), [includes/navbar.php](file:///c:/xampp/htdocs/ts-pattani/includes/navbar.php)                                                                                                                                                    |
| **5**  | **หน้ารวมข่าวสารและโปรโมชัน (News & Promotions)**               |   สมาชิก (Member)   | ✅ เรียบร้อย | [promotions.php](file:///c:/xampp/htdocs/ts-pattani/promotions.php), [includes/navbar.php](file:///c:/xampp/htdocs/ts-pattani/includes/navbar.php)                                                                                                                                                                                                                                 |
| **6**  | **ระบบจัดการของรางวัลฝั่งแอดมิน (Admin Rewards Management)**    | ผู้ดูแลระบบ (Admin) | ✅ เรียบร้อย | [admin/rewards.php](file:///c:/xampp/htdocs/ts-pattani/admin/rewards.php), [admin/reward_edit.php](file:///c:/xampp/htdocs/ts-pattani/admin/reward_edit.php), [admin/actions/reward_edit_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/reward_edit_db.php), [admin/actions/reward_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/reward_delete_db.php) |
| **7**  | **ปรับปรุงโครงสร้างสถาปัตยกรรมโค้ด (Architecture Refactoring)** |       System        | ✅ เรียบร้อย | `admin/includes/*`, `config/seed_admin.php`, `admin/actions/*`, `admin/*.php`                                                                                                                                                                                                                                                                                                      |
| **8**  | **ระบบจัดการผู้ดูแลระบบ (Admin Management)**                    | ผู้ดูแลระบบ (Admin) | ✅ เรียบร้อย | `admin/admins.php`, `admin/admin_add.php`, `admin/actions/admin_add_db.php`                                                                                                                                                                                                                                                                                                        |
| **9**  | **ปรับปรุงโค้ด CSS ซ้ำซ้อน (CSS Refactoring & Optimization)**   |       System        | ✅ เรียบร้อย | `assets/css/global.css`, `assets/css/admin.css`, `assets/css/style.css`, `*.php`                                                                                                                                                                                                                                                                                                   |
| **10** | **ปรับมาตรฐานปุ่ม UI ฝั่งแอดมิน (UI Standardization)**          | ผู้ดูแลระบบ (Admin) | ✅ เรียบร้อย | `admin/members.php`, `admin/news.php`, `admin/products.php`, `admin/rewards.php`                                                                                                                                                                                                                                                                                                   |
| **11** | **แก้ไขบัควิกฤตตามผล Audit (Phase 1: Critical Fixes)**        | System / Admin      | ✅ เรียบร้อย | `admin/admin_edit.php`, `admin/payments.php`, `booking.php`, `admin/includes/auto_cancel.php`, `admin/cancellations.php`, `assets/js/admin.js`                                                                                                                                                                                                                                 |
| **12** | **เพิ่มฟีเจอร์สำคัญตามผล Audit (Phase 2: High Priority)**     | ผู้ดูแลระบบ (Admin) | ✅ เรียบร้อย | `admin/redemptions.php`, `admin/chats.php`, `admin/actions/chat_messages_db.php`, `admin/actions/court_delete_db.php`, `admin/courts.php`, `admin/actions/court_edit_db.php`                                                                                                                                                                                   |
| **13** | **เพิ่มฟีเจอร์เสริมตามผล Audit (Phase 3: Medium Priority)**   | ผู้ดูแลระบบ (Admin) | ✅ เรียบร้อย | `admin/index.php`, `admin/actions/export_csv.php`                                                                                                                                                                                   |

---

## 🔍 รายละเอียดการพัฒนาแยกตามโมดูล (Detailed Changelog)

### 1. แก้ไขบัคระดับวิกฤตและเสริมความปลอดภัย (Critical Bug Fixes & Security)

- **การส่งสลิปชำระเงิน ([actions/payment_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/payment_db.php)):**
  - แก้ไขคำสั่ง SQL ให้ทำการ `INSERT` ข้อมูลการชำระเงินลงตาราง `Payment` โดยตรง เพื่อให้รูปสลิปและข้อมูลยอดเงินไปแสดงบนหน้าตรวจสอบของแอดมิน
  - แก้ไขการบันทึกชื่อไฟล์รูปสลิปลงคอลัมน์ `payment_slip` ในตาราง `Booking` อย่างถูกต้อง
- **การยืนยันสลิปและการแจกแต้ม ([admin/actions/payment_verify_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/payment_verify_db.php)):**
  - ตัดคำสั่ง `UPDATE` แต้มในตาราง `Member` ออก เนื่องจากในโครงสร้างฐานข้อมูลจริงแยกตารางแต้มไว้ที่ `Point`
  - ปรับปรุงให้ระบบอัปเดตคะแนนสะสมและบันทึกประวัติการรับแต้มลงตาราง `Point` และ `Point_Transaction` อย่างถูกต้อง 100%
- **ป้องกันการปลอมแปลงราคา (Price Tampering Protection ใน [actions/booking_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php)):**
  - ยกเลิกการรับค่ายอดเงินรวมจากหน้าบ้าน (POST Form) ซึ่งเสี่ยงต่อการถูกแก้ไขผ่าน Inspect/DevTools
  - เปลี่ยนมาเป็นการคำนวณราคาใหม่ทางฝั่งเซิร์ฟเวอร์ (Server-side Recalculation) โดยดึงราคาอ้างอิงจากตาราง `Court` และ `Product` ร่วมกับจำนวนชั่วโมงที่จองจริง
- **อุดช่องโหว่การอัปโหลดไฟล์ (File Upload Security ใน [actions/payment_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/payment_db.php)):**
  - เพิ่มการตรวจสอบขนาดไฟล์รูปภาพสลิปให้ไม่เกิน 5MB
  - เพิ่มการตรวจสอบ MIME Type ชนิดของไฟล์อย่างละเอียดผ่านฟังก์ชัน `finfo` เพื่อป้องกันการฝังมัลแวร์หรือการปลอมแปลงนามสกุลไฟล์
- **ปิดกั้นไฟล์ที่เสี่ยงต่อความปลอดภัย (Hardcoded Credentials):**
  - ระงับการเข้าถึงสคริปต์ [admin/add_admin.php](file:///c:/xampp/htdocs/ts-pattani/admin/add_admin.php) เพื่อป้องกันไม่ให้บุคคลภายนอกเข้าถึงรหัสผ่านเริ่มต้น (Hardcoded Password) ที่ค้างอยู่ในโค้ด

---

### 2. ระบบกู้คืนรหัสผ่าน (Forgot Password System)

- **หน้าจอ UI กู้คืนรหัสผ่าน ([forgot_password.php](file:///c:/xampp/htdocs/ts-pattani/forgot_password.php)):**
  - สร้างหน้าจอ Form สวยงามรองรับ Responsive ด้วยธีมหลักของ T.S. Pattani
  - มีช่องกรอก **เบอร์โทรศัพท์** และ **ชื่อ-นามสกุล** ที่ลงทะเบียนไว้เพื่อใช้ยืนยันตัวตน
  - มีช่องให้กำหนด **รหัสผ่านใหม่** และ **ยืนยันรหัสผ่านใหม่** พร้อม JavaScript Client-side Validation ตรวจสอบความถูกต้องก่อนกดส่ง
- **ระบบประมวลผลหลังบ้าน ([actions/forgot_password_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/forgot_password_db.php)):**
  - ตรวจสอบข้อมูลในตาราง `Member` แบบ Prepared Statement
  - เข้ารหัสผ่านใหม่ด้วยมาตรฐานความปลอดภัยสูง `password_hash($new_password, PASSWORD_DEFAULT)`
  - ส่งกลับไปยังหน้าเข้าสู่ระบบพร้อม Flash Session Alert แจ้งเตือนสถานะสำเร็จ
- **เชื่อมโยงจุดเข้าใช้งาน ([login.php](file:///c:/xampp/htdocs/ts-pattani/login.php)):**
  - เพิ่มลิงก์ _"ลืมรหัสผ่าน?"_ เหนือช่องกรอกรหัสผ่านในหน้า Login ให้ผู้ใช้เข้าถึงได้สะดวกรวดเร็ว

---

### 3. ระบบแลกของรางวัลฝั่งสมาชิก (Member Rewards Catalog & Redemption)

- **หน้าจอแคตตาล็อกของรางวัล ([rewards.php](file:///c:/xampp/htdocs/ts-pattani/rewards.php)):**
  - แสดงการ์ดของรางวัลพร้อมรูปภาพ, จำนวนแต้มที่ต้องใช้, จำนวนสต็อกคงเหลือ, และระดับสมาชิกขั้นต่ำที่สามารถแลกได้ (Bronze, Silver, Gold)
  - แสดงกล่องแดชบอร์ดสรุปคะแนนสะสมและระดับสมาชิกปัจจุบันของผู้ใช้งาน
  - มีระบบตรวจสอบสิทธิ์บน UI ล่วงหน้า: ปุ่มแลกรางวัลจะถูก Disabled และระบุสาเหตุชัดเจนหากแต้มไม่พอ, ระดับสมาชิกไม่ถึง หรือสินค้าหมด
- **ระบบประมวลผลการแลกรางวัล ([actions/redeem_reward_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/redeem_reward_db.php)):**
  - ใช้กลไก **Database Transaction (`BEGIN`, `COMMIT`, `ROLLBACK`)** พร้อมการล็อกแถวข้อมูลแบบ **`SELECT ... FOR UPDATE`** เพื่อป้องกันการแย่งสิทธิ์แลกของพร้อมกันจนสต็อกติดลบ (Race Condition)
  - หักสต็อกของรางวัลในตาราง `Reward`
  - หักแต้มสะสมในตาราง `Point`
  - บันทึกประวัติการใช้แต้มลงตาราง `Point_Transaction` พร้อมเหตุผลการทำรายการ

---

### 4. ระบบแชทติดต่อแอดมินฝั่งสมาชิก (Live Chat Support)

- **หน้าต่างสนทนาฝั่งลูกค้า ([chat.php](file:///c:/xampp/htdocs/ts-pattani/chat.php)):**
  - ดีไซน์ UI ในรูปแบบกล่องแชทเรียลไทม์ จัดวางฝั่งข้อความผู้ใช้ (ชิดขวา สีเขียว) และแอดมิน (ชิดซ้าย สีขาว) สวยงาม
  - ดึงข้อมูลประวัติการสนทนาจากตาราง `Chat` โดยกรองเฉพาะบทสนทนาระหว่างสมาชิกล็อกอินกับแอดมิน พร้อมแสดงวันเวลาส่ง
  - มี JavaScript เลื่อน Scrollbar ลงด้านล่างสุดโดยอัตโนมัติ (Auto Scroll to Bottom) เมื่อโหลดหน้าเว็บ
- **ระบบส่งข้อความ ([actions/send_chat_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/send_chat_db.php)):**
  - ตรวจสอบความถูกต้องของข้อความ ป้องกันการส่งข้อความว่าง
  - บันทึกข้อความลงตาราง `Chat` โดยระบุผู้ส่งเป็น `'สมาชิก'` และมีสถานะใหม่อย่างถูกต้อง
- **การทำงานร่วมกับฝั่งแอดมิน:** เชื่อมโยงเข้ากับระบบแอดมินเดิมใน [admin/chats.php](file:///c:/xampp/htdocs/ts-pattani/admin/chats.php) ทำให้แอดมินมองเห็นและตอบกลับแชทได้ทันที

---

### 5. หน้ารวมข่าวสารและโปรโมชัน (News & Promotions Portal)

- **หน้าแสดงข่าวสารและโปรโมชัน ([promotions.php](file:///c:/xampp/htdocs/ts-pattani/promotions.php)):**
  - ดึงข้อมูลข่าวสารจากตาราง `News` มาจัดเรียงตามวันที่ประกาศล่าสุด
  - ออกแบบการแสดงผลเป็น Grid Card สไตล์ทันสมัย พร้อมรูปภาพปก วันที่ และเนื้อหาย่อ
  - ติดตั้งระบบ **Modal Popup (หน้าต่างป๊อปอัปอ่านข่าวฉบับเต็ม)** เมื่อผู้ใช้คลิก _"อ่านเพิ่มเติม"_ เพื่อแสดงเนื้อหาและรูปภาพขนาดใหญ่โดยไม่ต้องโหลดเปลี่ยนหน้าใหม่
- **แถบเมนูนำทาง ([includes/navbar.php](file:///c:/xampp/htdocs/ts-pattani/includes/navbar.php)):**
  - เพิ่มปุ่มเมนู _"ข่าวสาร&โปรโมชัน"_ บน Navbar ของทุกหน้า ให้ลูกค้าคลิกเข้ามาอ่านได้ตลอดเวลา

---

### 6. ระบบจัดการของรางวัลฝั่งผู้ดูแลระบบ (Admin Rewards Management)

- **ปรับปรุงหน้ารวมของรางวัล ([admin/rewards.php](file:///c:/xampp/htdocs/ts-pattani/admin/rewards.php)):**
  - ปรับปุ่มในตารางจากเดิมที่เป็นปุ่มตัวอย่าง (Placeholder) ให้เชื่อมต่อไปยังหน้าแก้ไขและลบข้อมูลได้จริง
  - ติดตั้ง JavaScript Confirmation ป้องกันการเผลอกดลบโดยไม่ตั้งใจ
- **หน้าจอแก้ไขของรางวัล ([admin/reward_edit.php](file:///c:/xampp/htdocs/ts-pattani/admin/reward_edit.php)):**
  - สร้างหน้าจอแบบฟอร์มเฉพาะสำหรับดึงข้อมูลของรางวัลเดิมมาแสดงและแก้ไขได้ทุกฟิลด์ (ชื่อ, แต้มที่ใช้, สต็อก, ระดับสมาชิก, รูปภาพ)
  - แสดงตัวอย่างรูปภาพเดิม และมีระบบเลือกอัปโหลดรูปภาพใหม่เพื่อแทนที่
- **สคริปต์ประมวลผลการแก้ไขและลบ ([admin/actions/reward_edit_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/reward_edit_db.php) & [reward_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/reward_delete_db.php)):**
  - ดำเนินการอัปเดต (UPDATE) และลบ (DELETE) ข้อมูลอย่างถูกต้องปลอดภัยด้วย PDO Prepared Statements
  - มีฟังก์ชันตรวจสอบและลบไฟล์รูปภาพเก่าออกจากโฟลเดอร์ `uploads/rewards/` ทันทีเมื่อมีการเปลี่ยนรูปใหม่หรือลบของรางวัล เพื่อป้องกันไฟล์ขยะค้างในเซิร์ฟเวอร์

---

### 7. การปรับปรุงโครงสร้างสถาปัตยกรรมโค้ดและลบจุดอ่อน (Architecture Refactoring)

- **ย้าย/ซ่อนไฟล์ความเสี่ยงความปลอดภัย ([config/seed_admin.php](file:///c:/xampp/htdocs/ts-pattani/config/seed_admin.php)):**
  - ลบไฟล์ `admin/add_admin.php` ที่มีรหัสผ่านฮาร์ดโค้ดออกจากโฟลเดอร์ที่เข้าถึงได้ผ่านเว็บเบราว์เซอร์
  - แปลงสคริปต์ให้สามารถรันได้ผ่าน Command Line (CLI) เท่านั้น เพื่อใช้สำหรับการ Seed ผู้ดูแลระบบคนแรก ปิดช่องโหว่การสร้างบัญชีซ้ำซ้อน 100%
- **รวมศูนย์การตรวจสอบสิทธิ์ Session Middleware ([admin/includes/auth_check.php](file:///c:/xampp/htdocs/ts-pattani/admin/includes/auth_check.php)):**
  - สร้างไฟล์รวมคำสั่งเรียกใช้ `session_start()`, `require_once config.php` และตรวจสอบ `$_SESSION['admin_id']`
  - นำไปแทนที่โค้ดกว่า 4 บรรทัดที่ถูกคัดลอกซ้ำซ้อนในสคริปต์หน้าจอและการทำงานเบื้องหลังของแอดมินทั้งหมดกว่า 40 ไฟล์ ให้เหลือบรรทัดเดียว
- **แยกส่วนโค้ดร่าง HTML Boilerplate ออกเป็นไฟล์แม่แบบ ([admin/includes/header.php](file:///c:/xampp/htdocs/ts-pattani/admin/includes/header.php) และ [footer.php](file:///c:/xampp/htdocs/ts-pattani/admin/includes/footer.php)):**
  - แยกแท็ก `<head>`, แท็กจัดการ Layout ต่างๆ (`<div class="admin-layout">`), Sidebar และส่วนแสดง Alert Success/Error ย้ายเข้ามาอยู่ใน `header.php` เพื่อลดบรรทัดโค้ดซ้ำซ้อนใน 20 หน้าแอดมิน
  - ย้ายแท็กปิด HTML และสคริปต์ Javascript ไว้ที่ `footer.php` ทำให้ตอนนี้หากต้องการอัปเดตเวอร์ชัน Cache-Busting ของ CSS สามารถแก้ไขที่ไฟล์เดียวได้เลย
- **จัดทำมาตรฐานการตั้งชื่อไฟล์ใหม่ (Naming Convention ใน [admin/actions/](file:///c:/xampp/htdocs/ts-pattani/admin/actions)):**
  - เปลี่ยนชื่อไฟล์ประมวลผลฐานข้อมูล (Controller) ที่เคยตั้งชื่อตามกริยาผสมคำนามแบบไร้มาตรฐาน (เช่น `add_court_db.php`, `repair_finish_db.php`) ให้กลายเป็นรูปแบบมาตรฐาน **`[module]_[action]_db.php`** ทั้งหมด (เช่น `court_add_db.php`, `court_repair_finish_db.php`)
  - อัปเดตลิงก์ จุดเรียกใช้งาน และฟอร์มทั้งหมดใน 20 ไฟล์ ให้ชี้ไปหาเป้าหมายใหม่ได้อย่างไร้รอยต่อ

---

### 8. ระบบจัดการผู้ดูแลระบบ (Admin Management)

- **หน้ารายชื่อผู้ดูแลระบบ ([admin/admins.php](file:///c:/xampp/htdocs/ts-pattani/admin/admins.php)):**
  - แสดงรายชื่อผู้ดูแลระบบทั้งหมด (ID, ชื่อ, เบอร์โทรศัพท์, ระดับสิทธิ์)
  - มีปุ่มเพิ่มแอดมินคนใหม่ และปุ่มลบแอดมินเดิม
  - **Security:** ปุ่มลบของแอดมินที่กำลังล็อกอินอยู่จะถูก Disable ไว้ เพื่อป้องกันการลบตัวเองออกจากระบบ
- **แบบฟอร์มเพิ่มแอดมินใหม่ ([admin/admin_add.php](file:///c:/xampp/htdocs/ts-pattani/admin/admin_add.php)):**
  - ฟอร์มรับข้อมูล ชื่อ, เบอร์โทรศัพท์ (Username), รหัสผ่าน และระดับสิทธิ์ (Admin / Super Admin)
- **สคริปต์ประมวลผลเพิ่มและลบ ([admin/actions/admin_add_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/admin_add_db.php) & [admin_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/admin_delete_db.php)):**
  - เข้ารหัสผ่านอย่างปลอดภัยด้วย `password_hash()` ก่อนบันทึกลงฐานข้อมูลเสมอ
  - ป้องกันการเพิ่มแอดมินด้วยเบอร์โทรศัพท์ที่ซ้ำกับในระบบที่มีอยู่แล้ว
- **ระบบจำกัดสิทธิ์ (Role-Based Access Control - RBAC):**
  - ล็อกหน้าจอและการทำงานเบื้องหลังของ `admins.php`, `admin_add.php` และสคริปต์ประมวลผลให้เข้าถึงได้เฉพาะ `Super Admin` เท่านั้น
  - หาก `Admin` ระดับทั่วไปพยายามเข้าถึง จะถูกดีดกลับไปหน้าแดชบอร์ดหลักทันที
- **อัปเดตเมนูนำทาง ([admin/includes/sidebar.php](file:///c:/xampp/htdocs/ts-pattani/admin/includes/sidebar.php)):**
  - เพิ่มเมนู "จัดการผู้ดูแลระบบ" ลงใน Sidebar ของระบบหลังบ้าน โดยจะแสดงให้เฉพาะคนที่เป็น `Super Admin` เห็นเท่านั้น

---

### 9. ปรับปรุงโค้ด CSS ซ้ำซ้อน (CSS Refactoring & Optimization)

- **สร้างไฟล์จัดเก็บสไตล์ส่วนกลาง ([assets/css/global.css](file:///c:/xampp/htdocs/ts-pattani/assets/css/global.css)):**
  - ย้ายคลาสหลักที่ใช้ซ้ำซ้อนกันในหน้าบ้านและระบบแอดมินมารวมไว้ที่เดียว เช่น ฟอร์ม (`.form-control`, `.form-group`), แจ้งเตือน (`.alert`), ป้ายสถานะ (`.badge`) และปุ่มต่างๆ
  - ใช้เทคนิคจับกลุ่มคลาส (Comma-separated Selectors) ใน `global.css` เพื่อยุบรวมคลาสปุ่มที่หน้าตาเหมือนกัน (เช่น `.btn-submit, .btn-register, .btn-confirm-booking`) ให้ใช้สไตล์เดียวกันโดยไม่ต้องไปตามแก้แท็ก `<button>` ในหน้า PHP ทุกไฟล์
- **ล้างโค้ดที่ซ้ำซ้อน (Duplicate Removal):**
  - ลบโค้ด CSS ขององค์ประกอบที่ซ้ำซ้อนกันออกจาก `admin.css` และ `style.css` ทำให้ไฟล์เบาขึ้นอย่างมาก (Optimized Load Time)
- **เชื่อมต่อกับไฟล์ที่เกี่ยวข้องทั้งหมด:**
  - เพิ่มโค้ด `<link rel="stylesheet" href="assets/css/global.css">` ลงไปในส่วน `<head>` ของไฟล์ฝั่งผู้ใช้งาน (Frontend) ทั้ง 10 ไฟล์ และฝั่งแอดมิน `admin/includes/header.php` เพื่อให้ทั้งสองฝั่งเรียกใช้ดีไซน์ส่วนกลางได้อย่างถูกต้อง

---

### 10. ปรับมาตรฐานปุ่ม UI ฝั่งแอดมิน (UI Standardization)

- **ปรับแต่งตารางแสดงข้อมูล (Admin Data Tables):**
  - ลบคำสั่ง Inline CSS (`style="..."`) ที่ถูกฝังไว้แบบกระจัดกระจายในปุ่มต่างๆ ออกทั้งหมด
  - ปรับมาตรฐานปุ่ม Action ในหน้าระบบแอดมิน 5 หน้าหลัก (`members.php`, `news.php`, `products.php`, `rewards.php`, `admins.php`) ให้มีดีไซน์และขนาดตรงกันตามหลัก HCI/UX/UI
  - ใช้คลาสส่วนกลาง `.btn-warning` สำหรับปุ่ม "แก้ไข" และ `.btn-danger` สำหรับปุ่ม "ลบ" ควบคู่กับ `.btn-action-small` เพื่อความเป็นระเบียบและง่ายต่อการบำรุงรักษาในอนาคต

---

### 11. แก้ไขบัควิกฤตตามผล Audit (Phase 1: Critical Fixes)

- **ระบบจัดการข้อมูลแอดมินส่วนตัว ([admin/admin_edit.php](file:///c:/xampp/htdocs/ts-pattani/admin/admin_edit.php)):**
  - สร้างหน้าจอให้ Super Admin เข้าไปแก้ไขข้อมูลชื่อ เบอร์โทรศัพท์ สถานะสิทธิ์ หรือเปลี่ยน/รีเซ็ตรหัสผ่านของแอดมินคนอื่นๆ ได้
- **ระบบคืนสต็อกและยกเลิกอัตโนมัติ Lazy Expiry ([admin/includes/auto_cancel.php](file:///c:/xampp/htdocs/ts-pattani/admin/includes/auto_cancel.php)):**
  - สร้างสคริปต์ตรวจสอบรายการจองที่หมดเวลา 15 นาที และยังไม่แนบสลิปชำระเงิน
  - ฝังการทำงานแบบ Lazy Loading เข้าไปใน [admin/payments.php](file:///c:/xampp/htdocs/ts-pattani/admin/payments.php) และ [booking.php](file:///c:/xampp/htdocs/ts-pattani/booking.php) ทำให้เมื่อโหลดหน้านั้น ระบบจะยกเลิกการจองและบวกจำนวนอุปกรณ์เช่ากลับเข้าสต็อกให้อัตโนมัติ ป้องกันปัญหาสต็อกจม (Stock Hoarding)
- **ปรับปรุงการแสดงผลค่าปรับยกเลิกจอง ([admin/cancellations.php](file:///c:/xampp/htdocs/ts-pattani/admin/cancellations.php)):**
  - เพิ่มโค้ดคำนวณ % ค่าปรับ อ้างอิงจากชั่วโมงที่ลูกค้ายกเลิกล่วงหน้า (เช่น คืน 100% ถ้าเกิน 24 ชม., คืน 70% ถ้า 12-24 ชม., คืน 50% ถ้าน้อยกว่า 12 ชม.)
  - อัปเดต Modal การพิจารณาคืนเงินของแอดมินให้แสดง **ค่าปรับยกเลิก** และ **ยอดเงินที่ควรคืนลูกค้า** อย่างชัดเจน เพื่อใช้ประกอบการตัดสินใจโอนเงินคืนนอกระบบ

---

### 12. เพิ่มฟีเจอร์สำคัญตามผล Audit (Phase 2: High Priority)

- **หน้าประวัติการแลกรางวัล (Redemption History) ([admin/redemptions.php](file:///c:/xampp/htdocs/ts-pattani/admin/redemptions.php)):**
  - สร้างไฟล์ใหม่เพื่อแสดงประวัติการใช้คะแนนแลกของรางวัลของสมาชิกทั้งหมด
  - เพิ่มเมนู **ประวัติแลกรางวัล** เข้าไปที่ Sidebar ของผู้ดูแลระบบ เพื่อใช้อ้างอิงการจ่ายของรางวัลให้ลูกค้า
- **แชทรีเฟรชอัตโนมัติ (Chat Auto-refresh) ([admin/chats.php](file:///c:/xampp/htdocs/ts-pattani/admin/chats.php)):**
  - สร้าง API `admin/actions/chat_messages_db.php` คืนค่าข้อความแชทที่มาใหม่เป็นรูปแบบ JSON
  - ปรับปรุงหน้า `admin/chats.php` เพิ่ม JavaScript (AJAX Fetch) ให้ดึงข้อความใหม่ทุกๆ 5 วินาที ทำให้ข้อความเด้งขึ้นมาในกล่องทันทีโดยที่แอดมินไม่ต้องกดรีเฟรชหน้าเว็บ (F5)
- **ตรวจสอบการป้องกันการลบสนามที่มีประวัติการจอง (FK Check) ([admin/actions/court_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_delete_db.php)):**
  - ดำเนินการตรวจสอบเงื่อนไข พบว่าระบบเดิมได้มีการเขียนคำสั่ง `SELECT COUNT(*) FROM BOOKING WHERE court_id = :id` ไว้อย่างรัดกุมแล้ว
  - ป้องกันไม่ให้แอดมินลบสนามที่เคยมีคนจอง (เพื่อป้องกันฐานข้อมูลการเงินพัง) โดยแนะนำให้เปลี่ยนสถานะเป็น ปิดปรับปรุง แทน ถือว่าผ่านตามมาตรฐานความปลอดภัย
- **Modal แก้ไขสนามแบดมินตัน (Court Edit) ([admin/courts.php](file:///c:/xampp/htdocs/ts-pattani/admin/courts.php)):**
  - อัปเดตหน้าจัดการสนาม เพิ่มฟอร์มแก้ไขข้อมูลแบบ Modal (Popup) ขึ้นมาตรงกลางหน้าจอ พร้อมปุ่มกด "แก้ไข"
  - สร้างไฟล์ `admin/actions/court_edit_db.php` เพื่อรับค่าไปอัปเดตข้อมูล (ชื่อสนาม, ราคาปกติ, ราคา Peak/Off-peak, เวลาเปิด-ปิด) ลงฐานข้อมูล
  - ลดขั้นตอนการทำงานของแอดมิน ทำให้ไม่ต้องเปลี่ยนหน้าไปมาบ่อยๆ สอดคล้องกับมาตรฐาน UI ของระบบ

---

### 13. เพิ่มฟีเจอร์เสริมตามผล Audit (Phase 3: Medium Priority)

- **ระบบส่งออกข้อมูลรายงาน (Export CSV) ([admin/actions/export_csv.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/export_csv.php)):**
  - เพิ่มปุ่มกด **"Export การจอง"**, **"Export สมาชิก"** และ **"Export รายได้"** บนหน้า Dashboard
  - สร้างสคริปต์กลางในการแปลงข้อมูลจากฐานข้อมูลให้กลายเป็นตารางไฟล์ `.csv` 
  - เพิ่ม Byte Order Mark (BOM) บังคับให้เป็น UTF-8 เพื่อป้องกันปัญหาภาษาไทยกลายเป็นภาษาต่างดาวเมื่อเปิดใน Microsoft Excel
- **วิเคราะห์ข้อมูลลูกค้าประจำ (Customer Analytics) ([admin/index.php](file:///c:/xampp/htdocs/ts-pattani/admin/index.php)):**
  - เพิ่มตารางใหม่ในหน้าแดชบอร์ดสรุปยอดสถิติ **"ลูกค้าประจำยอดฮิต (Top 5)"**
  - อาศัยการ Query ข้อมูลการจอง (`Booking`) ไปเชื่อมกับข้อมูลสมาชิก (`Member`) และข้อมูลคะแนนสะสม (`Point`) ด้วยคำสั่ง `LEFT JOIN` เพื่อดึง **ระดับสมาชิก (member_level)** ที่ถูกต้องมาจัดแสดงผลอย่างแม่นยำ
  - **[เพิ่มเติม]** วิเคราะห์ข้อมูลประชากรศาสตร์ลูกค้า (Demographic Analytics) โดยเพิ่มกราฟโดนัทแสดงสัดส่วน **การจองแบ่งตามเพศ** และกราฟแท่งแสดงสัดส่วน **การจองแบ่งตามกลุ่มอาชีพ** ช่วยให้ผู้บริหารวิเคราะห์ทิศทางธุรกิจได้ดียิ่งขึ้นโดยไม่ละเมิดความเป็นส่วนตัว (PDPA)

### 14. ปรับปรุงหน้าตาผู้ใช้งานระดับผู้บริหาร (UI Redesign)
- **ปรับแต่ง CSS (SaaS Modern Dashboard) ([assets/css/admin.css](file:///c:/xampp/htdocs/ts-pattani/assets/css/admin.css)):**
  - ดีไซน์ Sidebar ใหม่เป็นโทนสี Deep Navy (`#0F172A`)
  - อัปเกรด Font หลักเป็น `Inter` สำหรับฝั่งแอดมิน เพื่อความดูเป็นมืออาชีพ
  - ปรับกล่อง (Cards) และ ตาราง (Tables) ให้เป็นขอบโค้งมน (border-radius) พร้อมเงาสะท้อน (box-shadow) ที่เบาบาง
  - Override สีป้ายสถานะ (Badges) และปุ่มกดให้เป็น Soft colors (Pastel) 
  - **Refactor `rewards.php`:** ทำการล้าง Inline CSS ที่ตกค้าง เปลี่ยนมาใช้ Class ที่สร้างใหม่ใน `admin.css` แทน (อาทิ `.badge-gold`, `.table-img-md`) และแก้ปัญหาปุ่มจัดการ (Action buttons) ซ้อนทับกันในตาราง ทำให้โค้ดสะอาดและหน้าตาตรงกับธีม SaaS 100%
### 15. ปรับปรุงคุณภาพโค้ดและโครงสร้าง (Phase 4: Clean Architecture)
- **ล้างโค้ด Inline CSS แบบฝังตรงทั้งหมด (Inline CSS Cleanup):**
  - ลบแอตทริบิวต์ `style="..."` ที่ฝังอยู่ในไฟล์ระบบฝั่ง Admin ทุกไฟล์ (เช่น `product_add.php`, `cancellations.php`, `admin_edit.php`, ฯลฯ) 
  - ย้ายและแปลงสไตล์ทั้งหมดไปสร้างเป็น **Utility Classes** กลาง (เช่น `.mb-20`, `.text-danger`, `.w-full`, `.text-center`) ในไฟล์ `assets/css/admin.css`
  - ทำให้ UI สะอาด อ่านโค้ด HTML ได้ง่ายขึ้นอย่างมาก และที่สำคัญคือสามารถ **ควบคุมและปรับแต่งดีไซน์ทุกหน้าของ Admin ได้จากที่เดียว** (Centralized Styling) ซึ่งมีประโยชน์มากสำหรับการแก้ไขงานในช่วงสอบโปรเจกต์
  - *(ไฟล์เดียวที่ถูกยกเว้นการลบ Inline CSS คือ `admin/receipt.php` เพื่อการพิมพ์ใบเสร็จผ่านเครื่องปริ้นท์ความร้อน 80mm ได้อย่างมีประสิทธิภาพ)*
- **ปรับปรุงความสม่ำเสมอของ UI (UI/UX Consistency) ระบบจัดการสินค้า:**
  - แก้ไขไฟล์ `admin/product_add.php`, `admin/product_edit.php` และ `admin/purchase_add.php` ให้เปลี่ยนมาใช้โครงสร้างแม่แบบ (`includes/header.php`) ทำให้ระบบสามารถดึงสไตล์จาก `global.css` มาแสดงผลฟอร์มได้อย่างถูกต้องสวยงาม (กล่องข้อความโค้งมน, ปุ่มบันทึกข้อมูลแบบมาตรฐาน SaaS)
  - จัดระเบียบ Layout การเพิ่มและแก้ไขสินค้าเป็น 2 คอลัมน์ (2-Column Grid) และปรับ Layout หน้าเพิ่มสต็อกให้เป็นระเบียบ
  - ปรับปรุงตารางใน `admin/products.php` ให้แสดงคำหัวตารางแบบไดนามิก (เช่น "ราคาขาย" สำหรับสินค้าบริโภค และ "ราคาเช่า" สำหรับอุปกรณ์เช่า) พร้อมจัดกึ่งกลางเนื้อหาตารางให้ตรงกัน 100%
- **ปรับปรุงหน้าเข้าสู่ระบบผู้ดูแลระบบ (Admin Login Redesign):**
  - ออกแบบเลย์เอาต์หน้า `admin/login.php` ใหม่ทั้งหมดเป็นรูปแบบ 2 ฝั่ง (Split-Screen Layout) แบบพรีเมียม
  - ฝั่งขวาใช้สไตล์ Glassmorphism (โปร่งแสงเบลอพื้นหลัง) สำหรับฟอร์มกรอกข้อมูล พร้อมปรับปุ่มให้ทันสมัย
  - ฝั่งซ้ายออกแบบให้มีเส้นโค้งเว้า (Curved Divider) เล่นสีพื้นหลังด้วย Dark Slate (`#0f172a`) ให้เข้ากับตีมแอดมิน 
  - สร้างและใช้งานภาพโลโก้สนามแบดมินตันแบบ Minimal Vector แทนไอคอนเก่าเพื่อความสวยงาม (`assets/img/ts-pattani-generated.jpg`)
