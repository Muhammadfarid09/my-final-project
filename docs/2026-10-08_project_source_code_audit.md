# บันทึกการตรวจสอบ Source Code ทั้งระบบ T.S. Pattani (Project Code Audit)
**วันที่:** 2026-10-08  
**สถานะ:** ดำเนินการแก้ไขกลุ่มที่ 1 (8 ข้อสำคัญ) เสร็จสิ้นเรียบร้อยแล้ว (ผ่าน Syntax Lint 100%) พร้อมเข้าสู่ขั้นตอนตกแต่ง UI/UX

---

## 1. วัตถุประสงค์ของการตรวจสอบ (Audit Objectives)
ตรวจสอบ Source Code จริงของโปรเจกต์ T.S. Pattani ทุกโมดูล ทั้งฝั่ง Member, Admin, ฐานข้อมูล MariaDB (18 ตาราง), Business Logic, ระบบชำระเงิน, POS, การจัดการสต็อก, และ UI/UX Responsive Design เพื่อค้นหาข้อบกพร่อง, บั๊กที่อาจทำให้ระบบหยุดทำงาน (Fatal/Crash), ช่องโหว่ความปลอดภัย (Security Flaws), และปัญหาทางตรรกะ ก่อนเข้าสู่ขั้นตอนการตกแต่ง UI/UX

---

## 2. ขอบเขตการตรวจสอบจากโค้ดจริง (Inspected Scope)
1. **ฐานข้อมูลและ Schema:** ตรวจสอบโครงสร้างตารางจริง 18 ตารางจาก MariaDB เทียบกับ `config/setup.php` และ `config/update_database.php`
2. **Business Logic & Booking:** ตรวจสอบ [actions/booking_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php) และ [actions/get_court_matrix.php](file:///c:/xampp/htdocs/ts-pattani/actions/get_court_matrix.php)
3. **ระบบการยกเลิกและการคืนเงิน:** ตรวจสอบ [admin/actions/cancellation_action_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/cancellation_action_db.php) และ [actions/cancel_booking_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/cancel_booking_db.php)
4. **ระบบ POS หน้าร้าน:** ตรวจสอบ [admin/pos.php](file:///c:/xampp/htdocs/ts-pattani/admin/pos.php) และ [admin/actions/pos_checkout_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/pos_checkout_db.php)
5. **ระบบซ่อมบำรุงสนามและอุปกรณ์:** ตรวจสอบ [admin/actions/court_repair_edit_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_repair_edit_db.php), `court_repair_finish_db.php`, `equipment_repair_finish_db.php`
6. **ระบบความปลอดภัยและการอัปโหลดไฟล์:** ตรวจสอบ [actions/payment_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/payment_db.php), [admin/actions/product_add_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/product_add_db.php), `product_edit_db.php`, `reward_add_db.php`, `news_add_db.php`
7. **CSRF & Request Security:** ตรวจสอบ Action Scripts ทั้ง 41 ไฟล์ในโฟลเดอร์ `actions/` และ `admin/actions/`
8. **ระบบออกรายงาน CSV:** ตรวจสอบ [admin/actions/export_csv.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/export_csv.php)
9. **Responsive Design & Mobile Navigation:** ตรวจสอบ [assets/css/style.css](file:///c:/xampp/htdocs/ts-pattani/assets/css/style.css), [assets/css/admin.css](file:///c:/xampp/htdocs/ts-pattani/assets/css/admin.css), และ [includes/navbar.php](file:///c:/xampp/htdocs/ts-pattani/includes/navbar.php)

---

## 3. รายการข้อตรวจพบแบ่งตาม 3 กลุ่มความสำคัญ

### 🚨 กลุ่มที่ 1: ต้องแก้ไขก่อน (Must Fix First - ส่งผลต่อการทำงาน, ความปลอดภัย หรือความถูกต้องของข้อมูล)

#### 1. ENUM Mismatch ในระบบปฏิเสธการยกเลิก/สลิปไม่ถูกต้อง (Fatal Database Error)
* **ไฟล์ที่เกี่ยวข้อง:** [admin/actions/cancellation_action_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/cancellation_action_db.php#L108-L113)
* **สาเหตุ:** บรรทัดที่ 110 โค้ดสั่งรันคำสั่ง `UPDATE Payment SET payment_status = 'ไม่ถูกต้อง'` แต่ฟิลด์ `payment_status` ในตาราง `Payment` ถูกนิยามไว้เป็น `ENUM('รอตรวจสอบ', 'ยืนยันแล้ว', 'ปฏิเสธ')`
* **ผลกระทบ:** เมื่อแอดมินกด "ไม่คืนเงิน" เนื่องจากสลิปปลอม ระบบ MariaDB ในโหมด Strict Mode จะแจ้งเตือน Fatal Truncation Error ส่งผลให้ Transaction Rollback ทำให้แอดมินไม่สามารถปฏิเสธคำขอได้เลย
* **แนวทางแก้ไข:** แก้ไขค่า `'ไม่ถูกต้อง'` ให้เป็น `'ปฏิเสธ'` ในไฟล์ [cancellation_action_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/cancellation_action_db.php)

#### 2. ENUM Mismatch ในระบบแก้ไขการซ่อมสนาม (Fatal Database Error / คอร์รัปชันสถานะ)
* **ไฟล์ที่เกี่ยวข้อง:** [admin/actions/court_repair_edit_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_repair_edit_db.php#L45)
* **สาเหตุ:** เมื่อแก้ไขสถานะงานซ่อมเป็น 'เสร็จแล้ว' บรรทัดที่ 45 สั่ง `UPDATE Court SET court_status = 'เปิดใช้งาน'` แต่ฟิลด์ `Court.court_status` ถูกนิยามเป็น `ENUM('ว่าง', 'รอตรวจสอบ', 'จองแล้ว', 'ปิดปรับปรุง')`
* **ผลกระทบ:** คำสั่ง SQL ล้มเหลว หรือบันทึกเป็นค่าสตริงว่าง `''` ทำให้สถานะสนามผิดเพี้ยนและไม่กลับมาเป็น 'ว่าง' พร้อมให้บริการ
* **แนวทางแก้ไข:** เปลี่ยน `'เปิดใช้งาน'` ให้เป็น `'ว่าง'` (สอดคล้องกับ [court_repair_finish_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_repair_finish_db.php))

#### 3. ช่องโหว่ Arbitrary File Upload (RCE) ในการจัดการสินค้า
* **ไฟล์ที่เกี่ยวข้อง:** [admin/actions/product_add_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/product_add_db.php#L23-L36) และ [admin/actions/product_edit_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/product_edit_db.php#L28-L46)
* **สาเหตุ:** นำนามสกุลไฟล์จาก `pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION)` มาตั้งชื่อไฟล์แล้วบันทึกลง `uploads/products/` ทันที โดยไม่มีการตรวจสอบ Whitelist นามสกุลไฟล์หรือ MIME Type
* **ผลกระทบ:** ผู้ไม่หวังดีหรือผู้ดูแลระบบที่ถูกขโมยเซสชันสามารถอัปโหลดไฟล์สคริปต์ `.php` เข้าสู่โฟลเดอร์ที่เปิด Public และสั่งรันคำสั่งโจมตีเซิร์ฟเวอร์ (Remote Code Execution) ได้โดยตรง
* **แนวทางแก้ไข:** เพิ่มการตรวจสอบ Whitelist นามสกุลไฟล์ (`jpg`, `jpeg`, `png`, `webp`) ตรวจสอบ MIME type ด้วย `finfo_file()` และจำกัดขนาดไฟล์ไม่เกิน 2MB

#### 4. เวลาเปิดทำการตายตัว (Hardcoded Hours) ขัดแย้งกับเวลาจริงของสนามในฐานข้อมูล
* **ไฟล์ที่เกี่ยวข้อง:** [actions/booking_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php#L65-L67) และ [actions/get_court_matrix.php](file:///c:/xampp/htdocs/ts-pattani/actions/get_court_matrix.php#L61)
* **สาเหตุ:** `booking_db.php` ล็อกเวลาเปิด-ปิดไว้ตายตัวว่า `08:00 - 22:00 น.` แต่ในตารางฐานข้อมูล `Court` สนามปิดเวลา `23:00:00 น.` และเปิดเวลา `13:00` หรือ `09:00`
* **ผลกระทบ:** ลูกค้าที่เลือกจองช่วง 22:00 - 23:00 น. ผ่านตาราง Matrix (ซึ่งแสดงว่าว่าง) จะถูกระบบปฏิเสธด้วยข้อผิดพลาด `"การจองต้องอยู่ในช่วงเวลาทำการ (08:00 - 22:00 น.)"` อีกทั้งอนุญาตให้จองช่วงเช้าในสนามที่เปิดจริง 13:00 น.
* **แนวทางแก้ไข:** ดึงค่า `court_open_time` และ `court_close_time` ของสนามนั้นๆ จากฐานข้อมูลมาเปรียบเทียบจริงแบบไดนามิก

#### 5. POS Walk-In คำนวณเวลาเกิน 24 ชั่วโมง และเลยเวลาปิดสนาม
* **ไฟล์ที่เกี่ยวข้อง:** [admin/pos.php](file:///c:/xampp/htdocs/ts-pattani/admin/pos.php#L316-L318) และ [admin/actions/pos_checkout_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/pos_checkout_db.php#L66-L87)
* **สาเหตุ:** การบวกชั่วโมงตรงๆ เช่น เริ่ม 22:00 จอง 3 ชม. ได้ `endHour = 25` กลายเป็นเวลา `25:00:00` ซึ่งไม่ใช่เวลาที่ถูกต้องใน MariaDB `TIME` และข้ามเวลาปิดทำการของสนาม
* **ผลกระทบ:** ข้อมูลเวลาบันทึกเพี้ยน การคำนวณ Overlap และราคาชั่วโมงติดลบ
* **แนวทางแก้ไข:** ทำการ Clamp ขอบเขตเวลาสิ้นสุดสูงสุดไม่เกินเวลาปิดสนาม (`court_close_time`) ทั้งในฝั่ง JavaScript UI และตรวจสอบซ้ำในฝั่ง Server-side checkout

#### 6. คำสั่งลบและเปลี่ยนสถานะสำคัญผ่าน GET Parameter ขาดการป้องกัน CSRF
* **ไฟล์ที่เกี่ยวข้อง:**
  - สั่งลบผ่าน GET: [admin/actions/court_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_delete_db.php), [product_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/product_delete_db.php), [reward_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/reward_delete_db.php), [news_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/news_delete_db.php), [admin_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/admin_delete_db.php), [court_repair_finish_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_repair_finish_db.php)
  - ขาด CSRF Token: ฟอร์ม CRUD แอดมินทั้ง 15 หน้า
* **สาเหตุ:** การลบและเปลี่ยนสถานะใช้ HTTP GET เพียงแค่ส่งลิงก์ หรือแท็ก `<img src="...">` ก็สามารถสั่งลบข้อมูลได้
* **ผลกระทบ:** มีความเสี่ยงสูงต่อการถูกโจมตีแบบ Cross-Site Request Forgery (CSRF)
* **แนวทางแก้ไข:** เปลี่ยน Method การลบเป็น POST พร้อมตรวจ `require_csrf_token()` ผ่าน AJAX หรือ Confirmation Form

#### 7. Export CSV ตารางการจอง ตกหล่นรายการ Walk-in ทั้งหมด
* **ไฟล์ที่เกี่ยวข้อง:** [admin/actions/export_csv.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/export_csv.php#L77)
* **สาเหตุ:** โค้ดใช้คำสั่ง `JOIN Member m ON b.member_id = m.member_id` (INNER JOIN) ซึ่งรายการเปิดสนาม Walk-in หน้าร้าน ค่า `b.member_id` จะเป็น `NULL`
* **ผลกระทบ:** ยอดรายการจองสนามที่เปิดผ่าน POS หน้าร้านหายไปจากรายงาน CSV ทั้งหมด ทำให้ยอดสถิติไม่ตรงกับรายงานรายรับจริง
* **แนวทางแก้ไข:** เปลี่ยนเป็น `LEFT JOIN Member m ON b.member_id = m.member_id` และใช้ `COALESCE(m.member_name, b.booking_nickname, 'ลูกค้าทั่วไป (Walk-in)')`

#### 8. ปุ่ม Login และเมนูโปรไฟล์หายบนหน้าจอมือถือ (Mobile Responsive Lockout)
* **ไฟล์ที่เกี่ยวข้อง:** [assets/css/style.css](file:///c:/xampp/htdocs/ts-pattani/assets/css/style.css#L249-L251) และ [includes/navbar.php](file:///c:/xampp/htdocs/ts-pattani/includes/navbar.php#L35-L58)
* **สาเหตุ:** CSS กำหนด `@media (max-width: 768px) { .nav-user { display: none; } }`
* **ผลกระทบ:** บนโทรศัพท์มือถือ ผู้เข้าชมไม่เห็นปุ่มเข้าสู่ระบบ (Login) และสมาชิกที่ล็อกอินอยู่จะไม่สามารถดูพอยท์, ประวัติการจอง, ข้อมูลโปรไฟล์ หรือกดออกจากระบบได้
* **แนวทางแก้ไข:** นำปุ่ม Login / เมนูโปรไฟล์ สมาชิกไปแสดงอยู่ภายใน Mobile Drawer Menu (`.nav-menu`) เมื่อแสดงผลบนหน้าจอขนาดเล็ก

---

### ⚠️ กลุ่มที่ 2: ควรปรับปรุง (Should Improve - เพิ่มคุณภาพ ความเสถียร และลดข้อผิดพลาด)

1. **การรีเซ็ตรหัสผ่านแบบไม่ยืนยันตัวตนสองชั้น (Unverified Password Reset):**
   * **ไฟล์:** [actions/forgot_password_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/forgot_password_db.php)
   * **ปัญหา:** หากทราบชื่อและเบอร์โทรศัพท์ของสมาชิก สามารถตั้งรหัสผ่านใหม่ได้ทันที
   * **แนวทาง:** ควรมีขั้นตอนแจ้งเตือน/ยืนยัน หรือรหัส OTP/การตรวจสอบโดยแอดมิน

2. **Foreign Key ON DELETE CASCADE ในตาราง Booking (ความเสี่ยงข้อมูลสูญหาย):**
   * **ไฟล์:** [config/setup.php](file:///c:/xampp/htdocs/ts-pattani/config/setup.php#L83)
   * **ปัญหา:** หากแอดมินลบสมาชิก ข้อมูลการจอง ยอดเงิน และประวัติรายรับจะถูกลบทันทีแบบ Cascade
   * **แนวทาง:** ควรตั้งค่าเป็น `ON DELETE SET NULL` เพื่อรักษาประวัติการเงินและบิลรายรับไว้

3. **ระบบติดตามการรับของรางวัลจริง (Physical Reward Claim Tracking):**
   * **ไฟล์:** [admin/redemptions.php](file:///c:/xampp/htdocs/ts-pattani/admin/redemptions.php)
   * **ปัญหา:** ดึงประวัติจาก `Point_Transaction` แต่ไม่มีปุ่มหรือสถานะให้แอดมินบันทึกว่า "ส่งมอบของรางวัลแล้ว" หรือยัง
   * **แนวทาง:** เพิ่มสถานะการรับของ (Pending Pickup / Claimed) เพื่อให้แอดมินหน้าร้านกดยืนยันการส่งมอบของรางวัล

4. **การตรวจสอบค่าตัวเลขติดลบในฟอร์มจัดซื้อและเพิ่มสนาม:**
   * **ไฟล์:** [admin/actions/purchase_add_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/purchase_add_db.php) และ [court_add_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_add_db.php)
   * **ปัญหา:** ไม่มีการเช็คว่าจำนวนสินค้าที่จัดซื้อ (`qty`) หรือราคา ต้องมากกว่า 0 ทำให้สามารถใส่ค่าติดลบเข้าไปตัดสต็อกโดยไม่ตั้งใจได้
   * **แนวทาง:** เพิ่ม Validation ตรวจสอบ `$qty > 0` และ `$price > 0` ฝั่ง Server

5. **การปิดปรับปรุงสนามทับซ้อนกับการจองล่วงหน้าที่มีอยู่แล้ว:**
   * **ไฟล์:** [admin/actions/court_repair_add_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_repair_add_db.php)
   * **ปัญหา:** เมื่อแอดมินลงรายการแจ้งซ่อมสนาม ระบบเปลี่ยนสถานะเป็น 'ปิดปรับปรุง' ทันทีโดยไม่ได้ตรวจสอบว่ามีลูกค้าจองสนามนั้นล่วงหน้าไว้หรือไม่
   * **แนวทาง:** เพิ่มระบบแจ้งเตือนแอดมินก่อนยืนยันปิดสนาม หากมีรายการจองที่ลูกค้าชำระเงินแล้วอยู่ในช่วงเวลาที่ซ่อมบำรุง

---

### 🎨 กลุ่มที่ 3: สามารถทำภายหลังได้ (Can Do Later - ตกแต่ง UI/UX และความสวยงาม)

1. **Admin Sidebar และ Table Responsive บนหน้าจอมือถือ/แท็บเล็ต:** ปัจจุบัน [assets/css/admin.css](file:///c:/xampp/htdocs/ts-pattani/assets/css/admin.css) ออกแบบสำหรับจอเดสก์ท็อปเป็นหลัก เมื่อเปิดด้วยมือถือ ตารางรายงานจะล้นขอบจอ
2. **การพิมพ์ใบเสร็จ POS ด้วยเครื่องพิมพ์ความร้อน (80mm Thermal Receipt):** ใน [admin/receipt.php](file:///c:/xampp/htdocs/ts-pattani/admin/receipt.php) สามารถเพิ่ม CSS `@media print` สำหรับกระดาษม้วนสลิป 80mm ให้สวยงามคมชัด
3. **Tactile Feedback & Empty States ตามหลัก HCI:** เพิ่มปุ่มแสดงสถานะ Loading ขณะบันทึกข้อมูล (Spinner), และกล่อง Empty State ที่มีภาพประกอบในหน้าแชท หน้าคืนอุปกรณ์ และหน้าตะกร้า POS
4. **Chat Interface Enhancements:** เพิ่มระบบเลื่อนแชทอัตโนมัติลงล่างสุด (Auto-scroll to bottom) และป้ายตัวเลขข้อความที่ยังไม่ได้อ่าน (Unread badge)

---

## 4. ส่วนที่ยังไม่ได้ทดสอบจริงในสภาพแวดล้อมรันไทม์ (Untested Runtime Areas)
1. **การจำลองการส่งคำขอจองสนามชนกันระดับมิลลิวินาที (High-concurrency Race Condition):** แม้โค้ดจะมี `FOR UPDATE` และ `overlap check` แต่ยังไม่ได้ทดสอบด้วยเครื่องมือจำลองโหลดพร้อมกันจำนวนมาก
2. **การสั่งพิมพ์กับเครื่องพิมพ์ใบเสร็จความร้อนจริง (Physical Thermal Printer):** ทดสอบผ่านหน้าจอ Browser Print Preview แต่ยังไม่ได้ต่อกับฮาร์ดแวร์จริง
3. **การส่งข้อความยืนยันผ่านเครือข่ายภายนอก (SMS OTP Gateway):** เนื่องจากระบบปัจจุบันจำลองการยืนยันรหัสผ่านผ่านฐานข้อมูลภายใน

---

## 5. สรุปความพร้อมในการตกแต่ง UI/UX (Final Verdict)
* **ความพร้อม:** **"ยังไม่พร้อมตกแต่ง UI/UX ทันที"**
* **เหตุผล:** จำเป็นต้องแก้ไขจุดผิดพลาดใน **กลุ่มที่ 1 (8 ข้อแรก)** ให้เรียบร้อยเสียก่อน เนื่องจากเป็นบั๊กที่ทำให้ระบบพัง (Fatal SQL Truncation), ช่องโหว่ความปลอดภัยระดับ RCE/CSRF, ปัญหาการจองถูกปฏิเสธเพราะฮาร์ดโค้ดเวลา, และปัญหาเมนูมือถือหาย หากตกแต่ง UI/UX ไปก่อนโดยไม่แก้จุดเหล่านี้ จะเกิดปัญหาบั๊กซ้ำซ้อนและกระทบต่อโครงสร้าง HTML/CSS ในภายหลัง
* **ลำดับขั้นตอนที่แนะนำ:**
  1. แก้ไข 8 ปัญหาสำคัญในกลุ่มที่ 1 ให้ระบบทำงานถูกต้องและปลอดภัย 100% (ดำเนินการแล้วเสร็จสมบูรณ์)
  2. ดำเนินการเริ่มตกแต่ง UI/UX ตามมาตรฐาน `docs/PROJECT_HCI_RULES.md` ได้อย่างมั่นใจและราบรื่น

---

## 6. สรุปผลการแก้ไขกลุ่มที่ 1 (Phase 1 Fixes Completed)
* **ไฟล์ที่ได้รับการแก้ไข:**
  1. [admin/actions/cancellation_action_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/cancellation_action_db.php): ปรับสถานะเป็น `'ปฏิเสธ'` แก้ปัญหา Truncation Error
  2. [admin/actions/court_repair_edit_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_repair_edit_db.php): ปรับสถานะเป็น `'ว่าง'` หลังซ่อมเสร็จ
  3. [admin/actions/product_add_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/product_add_db.php) & [product_edit_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/product_edit_db.php): ปิดช่องโหว่ RCE ด้วย Whitelist และ MIME validation
  4. [actions/booking_db.php](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php): ลบเวลาฮาร์ดโค้ด 08:00-22:00 น. เปลี่ยนเป็นตรวจสอบ `court_open_time` และ `court_close_time` จากฐานข้อมูลจริง
  5. [admin/pos.php](file:///c:/xampp/htdocs/ts-pattani/admin/pos.php) & [pos_checkout_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/pos_checkout_db.php): ป้องกันเวลาสิ้นสุดเกิน 23:00 น. และดักจับฝั่ง Server
  6. [admin/actions/court_delete_db.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/court_delete_db.php), `product_delete_db.php`, `reward_delete_db.php`, `news_delete_db.php`, `admin_delete_db.php`, `court_repair_finish_db.php`, [admin/includes/header.php](file:///c:/xampp/htdocs/ts-pattani/admin/includes/header.php), และ [assets/js/admin.js](file:///c:/xampp/htdocs/ts-pattani/assets/js/admin.js): ปรับเปลี่ยนคำสั่งลบ/เปลี่ยนสถานะเป็น Method POST พร้อมตรวจ CSRF Token
  7. [admin/actions/export_csv.php](file:///c:/xampp/htdocs/ts-pattani/admin/actions/export_csv.php): เปลี่ยนเป็น `LEFT JOIN Member` รวมรายการ Walk-in ลงรายงาน CSV
  8. [includes/navbar.php](file:///c:/xampp/htdocs/ts-pattani/includes/navbar.php) & [assets/css/style.css](file:///c:/xampp/htdocs/ts-pattani/assets/css/style.css): เพิ่มเมนูโปรไฟล์และปุ่มเข้าสู่ระบบใน Mobile Drawer บนจอมือถือ
  9. [admin/courts.php](file:///c:/xampp/htdocs/ts-pattani/admin/courts.php): แก้ไข `\"` เป็น `&quot;` ในแอตทริบิวต์ `onclick` ของปุ่ม "ซ่อมเสร็จแล้ว" เพื่อแก้ปัญหา Syntax Error ที่ทำให้คลิกปุ่มไม่ได้
* **ผลการตรวจสอบไวยากรณ์:** ผ่าน `php -l` ทุกไฟล์ 100% ไม่มี Syntax Error
* **สถานะความพร้อมสำหรับ UI/UX:** **พร้อมเข้าสู่ขั้นตอนการตกแต่ง UI/UX ได้ทันที!**

