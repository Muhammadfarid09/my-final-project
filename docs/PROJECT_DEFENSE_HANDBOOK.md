# คู่มือทำความเข้าใจระบบและเตรียมสอบโปรเจกต์ฉบับสมบูรณ์ (T.S. Pattani Defense Handbook & Visual Diagrams)

เอกสารฉบับนี้จัดทำขึ้นเพื่อเป็น **แผนผังความเข้าใจและคู่มือเตรียมสอบโครงงาน** สำหรับระบบบริหารจัดการและการจองสนามแบดมินตัน ที.เอส. ปัตตานี (T.S. Badminton Pattani) โดยถอดรหัสจากซอร์สโค้ดจริงทุกบรรทัด แสดงการเชื่อมโยงระดับไฟล์ การไหลของข้อมูล (Sequence Diagrams) ตรรกะอัลกอริทึมหลัก และแนวทางการตอบคำถามกรรมการสอบ

---

## 1. แผนภาพสถาปัตยกรรมระบบภาพรวม (System Architecture Overview)

```mermaid
flowchart TB
    subgraph ClientLayer["🖥️ ส่วนติดต่อผู้ใช้ (Client-Side / Presentation Layer)"]
        UI_Member["ผู้ใช้งาน / สมาชิก (Member Portal)<br/>• HTML5 Semantic Elements<br/>• Scoped CSS3 (Dark Mode #0B0F17 & Gold #FFB91D)<br/>• member.js (Wizard, Validation, Countdown 15m)"]
        UI_Admin["ผู้ดูแลระบบ / เจ้าหน้าที่ (Admin Panel)<br/>• Responsive Dashboard & POS Layout<br/>• admin.js & pos.js & equipment-returns.js<br/>• Chart.js (Charts) & SweetAlert2 (Modals)"]
    end

    subgraph SecurityLayer["🛡️ ชั้นความปลอดภัยและตัวกรองกลาง (Security & Interceptor Layer)"]
        CSRF["CSRF Protection (includes/csrf.php)<br/>• hash_equals() & require_csrf_token()"]
        AUTH["Auth & Session Gate (includes/auth_check.php)<br/>• Role-Based Access Control (RBAC)"]
        DOUBLE["Double Submit Interceptor (JS Form Interceptors)<br/>• Button Disable & Loading Spinner"]
    end

    subgraph BackendLayer["⚙️ ส่วนประมวลผลตรรกะระบบ (Server-Side / Controller Layer)"]
        PHP_Member["Frontend Action Controllers (actions/*.php)<br/>• booking_db.php (จองและล็อกเวลา)<br/>• payment_db.php (อัปโหลดสลิป)<br/>• cancel_booking_db.php / redeem_reward_db.php"]
        PHP_Admin["Admin Action Controllers (admin/actions/*.php)<br/>• pos_checkout_db.php (ขายหน้าร้าน/Walk-in)<br/>• payment_verify_db.php (ตรวจสลิป/แจกแต้ม)<br/>• equipment_return_db.php (คืนอุปกรณ์/ค่าปรับ)"]
        PHP_Core["ฟังก์ชันคำนวณกลาง (includes/functions.php)<br/>• calculate_court_booking_price()<br/>• award_member_points()"]
    end

    subgraph DatabaseLayer["🗄️ ชั้นฐานข้อมูลและการจัดเก็บ (Database & Storage Layer)"]
        MariaDB[("MariaDB / MySQL (InnoDB Engine)<br/>• ACID Transactions (BEGIN / COMMIT / ROLLBACK)<br/>• Pessimistic Row Lock (SELECT ... FOR UPDATE)<br/>• 15 ตารางหลัก (utf8mb4_unicode_ci)")]
        Storage["ระบบจัดเก็บไฟล์รูปภาพ (Local File Storage)<br/>• uploads/slips/ (สลิปโอนเงิน)<br/>• uploads/products/ & uploads/news/"]
    end

    UI_Member --> CSRF & DOUBLE
    UI_Admin --> CSRF & AUTH
    CSRF & AUTH & DOUBLE --> PHP_Member & PHP_Admin
    PHP_Member & PHP_Admin --> PHP_Core
    PHP_Member & PHP_Admin <--> MariaDB
    PHP_Member & PHP_Admin --> Storage
```

---

## 2. ตารางแมปปิ้งความเชื่อมโยงระดับไฟล์ (File Architecture Mapping)

### ก. ระบบฝั่งสมาชิก (Frontend / Member Portal)

| หน้าจอแสดงผล (View) | ไฟล์ JS ที่ควบคุม | ไฟล์ Action ปลายทาง (POST/AJAX) | ตาราง DB ที่ได้รับผลกระทบ | คำอธิบายหน้าที่ |
| :--- | :--- | :--- | :--- | :--- |
| `index.php` | `member.js` | *(Read-only)* | `Court`, `News` | แสดงข้อมูลสนามและข่าวสาร |
| `booking.php` | `member.js` | `actions/booking_db.php` | `Booking`, `Rental`, `Product` | ระบบจอง 5 ขั้นตอน ล็อกสนาม 15 นาที และตัดสต็อกเช่า |
| `payment.php` | `member.js` | `actions/payment_db.php` | `Payment`, `Booking` | แสดง QR Code, นับถอยหลัง 15 นาที และอัปโหลดสลิป |
| `booking_history.php` | `member.js` | `actions/cancel_booking_db.php` | `Cancellation`, `Booking` | ดูประวัติการจอง ตรวจสอบสถานะ และยื่นขอยกเลิก |
| `rewards.php` | `member.js` | `actions/redeem_reward_db.php` | `Point`, `Point_Transaction`, `Reward` | ดูแต้มสะสม ระดับสมาชิก และกดแลกของรางวัล |
| `news.php` | `member.js` | `actions/get_news_detail.php` (AJAX) | `News` | แสดงรายละเอียดข่าวสารและโปรโมชั่นในกล่อง Modal |
| `contact.php` | `member.js` | `actions/send_chat_db.php` | `Chat` | ส่งข้อความสนทนาถึงเจ้าหน้าที่สนาม |
| `profile.php` | `member.js` | `actions/profile_edit_db.php` | `Member` | แก้ไขข้อมูลส่วนตัวและเปลี่ยนรหัสผ่าน |
| `login.php` | `member.js` | `actions/login_db.php` | `Member` | ตรวจสอบรหัสผ่าน `password_verify` และสร้างเซสชัน |
| `register.php` | `member.js` | `actions/register_db.php` | `Member`, `Point` | สมัครสมาชิกใหม่และเปิดบัญชีแต้มสะสม |
| `forgot_password.php` | `member.js` | `actions/forgot_password_db.php` | `Password_Resets` | ขอยื่นรีเซ็ตรหัสผ่านและสร้าง Token Hash |

### ข. ระบบฝั่งผู้ดูแลระบบ (Backend / Admin Panel)

| หน้าจอแสดงผล (View) | ไฟล์ JS ที่ควบคุม | ไฟล์ Action ปลายทาง (POST/AJAX) | ตาราง DB ที่ได้รับผลกระทบ | คำอธิบายหน้าที่ |
| :--- | :--- | :--- | :--- | :--- |
| `admin/index.php` | `admin.js` (Chart.js) | *(Query กลาง)* | `Revenue`, `Booking`, `Member`, `Court` | แดชบอร์ดสรุปรายได้และกราฟสถิติ |
| `admin/pos.php` | `pos.js` | `admin/actions/pos_checkout_db.php` | `Booking`, `Rental`, `Product`, `Pos_sale`, `Revenue`, `Point` | ขายหน้าร้าน เปิดคอร์ท Walk-in และตัดสต็อกสินค้า |
| `admin/payments.php` | `admin.js` | `admin/actions/payment_verify_db.php` | `Payment`, `Booking`, `Rental`, `Revenue`, `Point`, `Point_Transaction` | ตรวจสลิป อนุมัติการจอง บันทึกรายได้ และแจกแต้ม |
| `admin/cancellations.php`| `admin.js` | `admin/actions/cancellation_action_db.php` | `Cancellation`, `Booking`, `Product`, `Point` | พิจารณาคำขอยกเลิก ปลดล็อกคอร์ท และคืนแต้ม |
| `admin/equipment_returns.php`| `equipment-returns.js` | `admin/actions/equipment_return_db.php` | `Rental`, `Product`, `Equipment_repair`, `Revenue` | ตรวจรับคืนอุปกรณ์ เช็คชำรุด คืนสต็อก และคิดค่าปรับ |
| `admin/courts.php` | `admin.js` | `court_add_db.php` / `court_edit_db.php` / `court_delete_db.php` | `Court` | จัดการข้อมูลคอร์ทสนามและอัตราค่าบริการ |
| `admin/court_repairs.php` | `admin.js` | `court_repair_add_db.php` / `court_repair_finish_db.php` | `Court_repair`, `Court` | บันทึกการส่งสนามซ่อมบำรุงและปิดปรับปรุง |
| `admin/products.php` | `admin.js` | `product_add_db.php` / `product_edit_db.php` / `product_delete_db.php` | `Product` | จัดการรายการสินค้า เครื่องดื่ม และอุปกรณ์กีฬา |
| `admin/purchases.php` | `admin.js` | `purchase_add_db.php` | `Purchase`, `Product` | บันทึกการซื้อสินค้าเข้าคลังและเพิ่มยอดสต็อก |
| `admin/members.php` | `admin.js` | `member_edit_db.php` | `Member`, `Point` | จัดการข้อมูลสมาชิก ปรับแต้ม และระงับสิทธิ์ |

---

## 3. แผนภาพเจาะลึกการไหลของข้อมูล 3 วงจรหลัก (Sequence Diagrams)

### 🏸 วงจรที่ 1: ขั้นตอนการจองสนามและการล็อกเวลา 15 นาที (Booking Flow)

```mermaid
sequenceDiagram
    autonumber
    actor User as สมาชิก (User)
    participant Wizard as booking.php (Wizard UI)
    participant JS as member.js (Client Script)
    participant Action as actions/booking_db.php
    participant DB as MariaDB (InnoDB Engine)
    participant AutoCancel as auto_cancel.php

    User->>Wizard: เลือกวันที่ และคลิกเลือกช่วงเวลาบน Grid
    Wizard->>JS: ส่งพารามิเตอร์วันและเวลา
    JS->>JS: calculateCourtBookingPrice() คำนวณราคาทันที (UI Feedback)
    JS-->>Wizard: แสดงยอดเงินและ Breakdown ให้ผู้ใช้เห็น
    User->>Wizard: เลือกอุปกรณ์เช่า และกดยืนยันการจอง
    Wizard->>Action: POST (court_id, date, time, products, csrf_token)
    
    Action->>Action: require_csrf_token() & ตรวจสอบสิทธิ์เซสชัน
    Action->>AutoCancel: เคลียร์คิวที่เกิน 15 นาทีของระบบก่อน
    Action->>DB: beginTransaction()
    Action->>DB: SELECT ... FROM Court WHERE court_id = :id FOR UPDATE (Lock แถวสนาม)
    Action->>Action: calculate_court_booking_price() คำนวณราคาจริงซ้ำบน Server
    Action->>DB: ตรวจสอบเวลาชน: (Start_New < End_Exist AND End_New > Start_Exist)
    
    alt พบเวลาชนกัน (Overlap)
        DB-->>Action: COUNT > 0
        Action->>DB: rollback()
        Action-->>Wizard: แจ้งเตือนข้อผิดพลาด "มีผู้จองแล้ว"
    else ช่วงเวลาว่าง (Available)
        DB-->>Action: COUNT = 0
        Action->>DB: INSERT INTO Booking (สถานะ 'รอตรวจสอบ', lock_expire = NOW() + 15 นาที)
        Action->>DB: INSERT INTO Rental (อุปกรณ์เช่า)
        Action->>DB: UPDATE Product SET stock = stock - qty (Atomic Stock Cut)
        Action->>DB: commit()
        Action-->>User: Redirect -> payment.php?booking_id=XXX
    end
```

---

### 💳 วงจรที่ 2: ขั้นตอนการชำระเงิน ตรวจสลิป และแจกแต้มสะสม (Payment & Verification Flow)

```mermaid
sequenceDiagram
    autonumber
    actor User as สมาชิก (User)
    participant PayPage as payment.php
    participant ActionPay as actions/payment_db.php
    actor Admin as เจ้าหน้าที่สนาม (Admin)
    participant AdminUI as admin/payments.php
    participant ActionVerify as admin/actions/payment_verify_db.php
    participant DB as MariaDB (InnoDB)

    User->>PayPage: สแกน QR Code พร้อมเพย์ตามยอดรวมจริง
    PayPage->>PayPage: member.js นับเวลาถอยหลัง 15 นาทีเทียบกับ lock_expire
    User->>PayPage: แนบไฟล์ภาพสลิปโอนเงิน และกดยืนยัน
    PayPage->>ActionPay: POST (booking_id, slip_image, csrf_token)
    ActionPay->>ActionPay: ตรวจสอบนามสกุลรูปภาพ และเปลี่ยนชื่อไฟล์แบบสุ่ม
    ActionPay->>DB: INSERT INTO Payment (สถานะ 'รอตรวจสอบ', slip_path)
    ActionPay-->>User: แสดง SweetAlert แจ้งรอตรวจสอบ และนำไปหน้าประวัติ

    Admin->>AdminUI: เปิดหน้าตรวจสอบการชำระเงิน
    AdminUI->>Admin: แสดงรูปสลิปขยายใหญ่เพื่อตรวจวันเวลาและยอดเงิน
    Admin->>AdminUI: กดปุ่ม "อนุมัติการชำระเงิน"
    AdminUI->>ActionVerify: POST (payment_id, booking_id, status='ยืนยันแล้ว')
    
    ActionVerify->>DB: beginTransaction()
    ActionVerify->>DB: UPDATE Payment SET status='ยืนยันแล้ว', admin_id, verified_at=NOW()
    ActionVerify->>DB: UPDATE Booking SET status='จองแล้ว' (สนามถูกยืนยัน 100%)
    ActionVerify->>DB: UPDATE Rental SET status='กำลังเช่า'
    ActionVerify->>DB: INSERT INTO Revenue (แยกยอดสนาม, ยอดเช่า, ยอดสินค้า, ประเภท 'ออนไลน์')
    ActionVerify->>DB: award_member_points() (ทุก 100 บาท = 1 แต้มสะสม)
    ActionVerify->>DB: UPDATE Point & INSERT INTO Point_Transaction
    ActionVerify->>DB: commit()
    ActionVerify-->>AdminUI: แจ้งเตือนอนุมัติสำเร็จ และแต้มสมาชิกเพิ่มเรียบร้อย
```

---

### 🏪 วงจรที่ 3: ระบบหลังบ้าน POS, การตรวจรับคืนอุปกรณ์ และงานซ่อมบำรุง (Backend Operations)

```mermaid
sequenceDiagram
    autonumber
    actor Admin as เจ้าหน้าที่ (Admin)
    participant POS as admin/pos.php (POS UI)
    participant ActionPOS as admin/actions/pos_checkout_db.php
    participant ReturnUI as admin/equipment_returns.php
    participant ActionReturn as admin/actions/equipment_return_db.php
    participant DB as MariaDB (InnoDB)

    Note over Admin, POS: 1. การขายหน้าร้านและเปิดคอร์ท Walk-in
    Admin->>POS: เลือกสนาม Walk-in + เครื่องดื่ม + เช่าไม้แบด
    POS->>ActionPOS: POST (cart_data JSON, customer_phone, csrf_token)
    ActionPOS->>DB: beginTransaction()
    ActionPOS->>DB: ตรวจสอบเวลาชน & INSERT INTO Booking (สถานะ 'จองแล้ว' ทันที)
    ActionPOS->>DB: INSERT INTO Rental (สถานะ 'กำลังเช่า')
    ActionPOS->>DB: UPDATE Product SET stock = stock - qty (ตัดสต็อกทันที)
    ActionPOS->>DB: INSERT INTO Pos_sale & INSERT INTO Revenue (ประเภท 'หน้าร้าน')
    ActionPOS->>DB: commit()
    ActionPOS-->>POS: พิมพ์ใบเสร็จ 80 มม. (receipt.php)

    Note over Admin, ReturnUI: 2. การตรวจรับคืนอุปกรณ์กีฬาและคิดค่าปรับ
    Admin->>ReturnUI: ลูกค้านำไม้แบดมาคืน กดตรวจรับคืน
    ReturnUI->>ActionReturn: POST (rental_id, condition_status, fine_amount)
    ActionReturn->>DB: beginTransaction()
    alt สภาพอุปกรณ์ปกติ (Normal)
        ActionReturn->>DB: UPDATE Rental SET status='คืนแล้ว'
        ActionReturn->>DB: UPDATE Product SET stock = stock + qty (คืนสต็อกเข้าคลัง)
    else สภาพอุปกรณ์ชำรุด (Damaged)
        ActionReturn->>DB: UPDATE Rental SET status='ชำรุด', fine=ค่าปรับ
        ActionReturn->>DB: INSERT INTO Equipment_repair (ส่งเข้าคิวซ่อม ไม่คืนสต็อก)
        ActionReturn->>DB: INSERT INTO Revenue (บันทึกรายได้ค่าปรับ)
    end
    ActionReturn->>DB: commit()
```

---

## 4. แผนผังตรรกะการคำนวณราคาแบบแบ่งช่วงเวลา (Pricing Logic Flowchart)

```mermaid
flowchart TD
    Start["เริ่มคำนวณราคา: calculate_court_booking_price(date, start_time, end_time)"] --> CheckDate{"ตรวจสอบวันในสัปดาห์ (Day of Week)"}
    
    CheckDate -- "วันเสาร์ หรือ วันอาทิตย์" --> WeekendRate["อัตราวันหยุดสุดสัปดาห์ (Weekend)<br/>• ทุกช่วงเวลา = 200 บาท / ชม.<br/>• total = ชั่วโมงรวม × 200"]
    
    CheckDate -- "วันจันทร์ ถึง วันศุกร์" --> CheckWindow{"ตรวจสอบช่วงเวลาที่จอง"}
    
    CheckWindow -- "ช่วงกลางวันเท่านั้น (08:00 - 17:00 น.)" --> DayRate["อัตรากลางวัน (Off-Peak)<br/>• 150 บาท / ชม.<br/>• total = ชั่วโมงกลางวัน × 150"]
    
    CheckWindow -- "ช่วงค่ำเท่านั้น (17:00 - 23:00 น.)" --> EveRate["อัตราช่วงค่ำ (Peak Hours)<br/>• 180 บาท / ชม.<br/>• total = ชั่วโมงช่วงค่ำ × 180"]
    
    CheckWindow -- "จองคาบเกี่ยวช่วงเวลา (Cross-Window เช่น 16:00 - 18:00)" --> SplitRate["คำนวณแยกตามชั่วโมงทับซ้อน (Split Window)<br/>• กลางวัน (16:00 - 17:00) = 1 ชม. × 150 = 150 ฿<br/>• ช่วงค่ำ (17:00 - 18:00) = 1 ชม. × 180 = 180 ฿<br/>• ยอดรวม = 150 + 180 = 330 บาท"]
    
    WeekendRate --> End["ส่งผลลัพธ์: total_price พร้อม Breakdown รายละเอียด"]
    DayRate --> End
    EveRate --> End
    SplitRate --> End
```

---

## 5. คลังคำตอบดักคำถามกรรมการ (Top 10 Technical Q&A Cheat Sheet)

| ลำดับ | หัวข้อคำถามของกรรมการ | แนวทางการตอบสั้นกระชับ 2-3 บรรทัด (สำหรับพูดสดในห้องสอบ) |
| :---: | :--- | :--- |
| **Q1** | **ทำไมไม่ใช้ Framework เช่น Laravel?** | "ระบบนี้ต้องการความเร็วสูงสุดและลด Overhead ของเซิร์ฟเวอร์ โดยใช้ PHP PDO ร่วมกับ Prepared Statements 100% ซึ่งปลอดภัยเทียบเท่า ORM แต่ยืดหยุ่นและดูแลโครงสร้างสถาปัตยกรรมแบบ Clean Architecture ได้ด้วยตัวเองทั้งหมดครับ" |
| **Q2** | **ป้องกันการจองชนกัน (Race Condition) อย่างไร?** | "ใช้ Pessimistic Locking โดยเปิด Database Transaction ร่วมกับ `SELECT ... FOR UPDATE` เพื่อล็อกแถวสนามใน InnoDB และใช้เงื่อนไขตรวจสอบช่วงเวลา `(Start_New < End_Existing AND End_New > Start_Existing)` รับประกันว่าคำขอแรกเท่านั้นที่ทำรายการสำเร็จครับ" |
| **Q3** | **ทำไมต้องเขียนฟังก์ชันคำนวณราคาซ้ำทั้งใน JS และ PHP?** | "เป็นการแบ่งหน้าที่ตามหลักความปลอดภัยครับ ฝั่ง JS ทำหน้าที่แสดงผลลัพธ์และราคาให้ผู้ใช้เห็นทันทีบนหน้าจอตามหลัก HCI ส่วนฝั่ง PHP เป็น Server Trust Boundary ที่ต้องคำนวณราคาจริงใหม่อีกครั้งเพื่อป้องกันการแอบแก้ไขยอดเงินผ่านเบราว์เซอร์ครับ" |
| **Q4** | **แก้ปัญหาผู้ใช้กดปุ่มซ้ำหลายครั้ง (Double Submit) อย่างไร?** | "ใช้ Global Form Interceptor ใน JavaScript ดักจับการ Submit เมื่อกดครั้งแรกปุ่มจะถูก Disable ทันทีและแสดง Loading Spinner และฝั่งเซิร์ฟเวอร์ยังมี Database Transaction ป้องกันการบันทึกข้อมูลซ้ำซ้อนอีกชั้นหนึ่งครับ" |
| **Q5** | **ระบบล็อก 15 นาทีทำงานอย่างไร ถ้าผู้ใช้ปิดเบราว์เซอร์หนี คอร์ทจะค้างไหม?** | "เมื่อกดยืนยัน ระบบจะตั้งสถานะ 'รอตรวจสอบ' และกำหนด `booking_lock_expire` ล่วงหน้า 15 นาที หากผู้ใช้ปิดหน้าจอหนี โมดูล `auto_cancel.php` จะตรวจสอบเวลาและปลดล็อกสนามคืนสู่สถานะว่างโดยอัตโนมัติในทุกครั้งที่มีการเรียกดูหรือจองสนามใหม่ครับ" |
| **Q6** | **ทำไมต้องเลือก InnoDB แทนที่จะเป็น MyISAM?** | "เพราะ InnoDB รองรับคุณสมบัติ ACID Transactions, รองรับ Foreign Key และที่สำคัญคือรองรับการล็อกระดับแถว (Row-Level Locking) ซึ่งจำเป็นต่อระบบจองสนามและ POS เพื่อป้องกันข้อมูลขัดแย้ง ในขณะที่ MyISAM ล็อกทั้งตารางทำให้ช้าและเสี่ยงต่อข้อมูลการเงินครับ" |
| **Q7** | **ระบบมีมาตรการความปลอดภัย (Web Security) อย่างไรบ้าง?** | "ระบบป้องกันช่องโหว่ OWASP ครบถ้วนครับ ป้องกัน SQL Injection ด้วย PDO Prepared Statements 100%, ป้องกัน XSS ด้วยการกรอง `htmlspecialchars()`, ป้องกัน CSRF ด้วย Token แบบใช้ครั้งเดียวในทุกคำขอ POST, และแฮชรหัสผ่านด้วย `password_hash()` BCRYPT ครับ" |
| **Q8** | **ระบบ POS ป้องกันสต็อกสินค้าติดลบได้อย่างไร?** | "ระบบใช้คำสั่งตัดสต็อกแบบมีเงื่อนไข (Atomic Decrement) คือ `UPDATE Product SET product_stock = product_stock - :qty WHERE product_id = :id AND product_stock >= :qty` หากสต็อกไม่พอระบบจะ Rollback ยกเลิกบิลทันที จึงไม่มีทางที่สต็อกจะติดลบครับ" |
| **Q9** | **จุดเด่นด้าน HCI (การปฏิสัมพันธ์ระหว่างมนุษย์กับคอมพิวเตอร์) คืออะไร?** | "ออกแบบตามหลัก Mobile-First โดยขยาย Touch Target ของปุ่มไม่ต่ำกว่า 48–50px ตามมาตรฐานสรีรศาสตร์, ใช้ Booking Wizard 5 ขั้นตอนเพื่อลดภาระการรับรู้ (Cognitive Load), และใช้สีสปอร์ต Dark Mode ตัดกับสีทอง `#FFB91D` ที่ได้มาตรฐานความต่างสี WCAG ครับ" |
| **Q10**| **ถ้ามีผู้ใช้เข้าใช้งานพร้อมกันจำนวนมาก มีแนวทางขยายระบบ (Scalability) อย่างไร?** | "ระบบแยกไฟล์ Static (CSS/JS/Images) ออกเป็นสัดส่วนพร้อมนำขึ้น CDN ได้ทันที, การเชื่อมต่อผ่าน PDO รองรับการแยก Read/Write Replicas และสามารถติดตั้ง Redis หรือ Memcached เพื่อแคชตารางเวลาสนาม ลดภาระ Query ของฐานข้อมูลได้ทันทีครับ" |

