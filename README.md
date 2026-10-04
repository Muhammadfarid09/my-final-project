# 🏸 เว็บแอปพลิเคชันระบบจัดการสนามแบดมินตัน T.S. Pattani
### (T.S. Pattani Badminton Court Management System)

[![PHP Version](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Web Server](https://img.shields.io/badge/Server-Apache%20%2F%20XAMPP-FB7A24?style=flat&logo=apache&logoColor=white)](https://www.apachefriends.org/)
[![Frontend](https://img.shields.io/badge/Frontend-HTML5%20%7C%20CSS3%20%7C%20JS-E34F26?style=flat&logo=html5&logoColor=white)](https://developer.mozilla.org/)
[![UI Library](https://img.shields.io/badge/UI-Bootstrap%205-7952B3?style=flat&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Charts](https://img.shields.io/badge/Charts-Chart.js-FF6384?style=flat&logo=chart.js&logoColor=white)](https://www.chartjs.org/)

---

## 📌 ข้อมูลโครงงาน (Project Information)

* **ชื่อโครงงาน:** เว็บแอปพลิเคชันระบบจัดการสนามแบดมินตัน T.S. Pattani
* **ผู้จัดทำ:** นายมูฮัมหมัดฟาริศ เจะเลาะ (รหัสนักศึกษา: 6620610016)
* **หลักสูตร:** วิทยาศาสตรบัณฑิต สาขาวิชาคอมพิวเตอร์และวิทยาการสารสนเทศเพื่อการจัดการ
* **คณะ:** คณะวิทยาการสื่อสาร มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตปัตตานี
* **อาจารย์ที่ปรึกษา:** ผศ.ดร.ซัมรี เจ๊ะอารน
* **ปีการศึกษา:** 2569

---

## 📖 บทคัดย่อและความเป็นมา (Project Background)

สนามแบดมินตัน T.S. Pattani เดิมบริหารจัดการด้วยการจดบันทึกลงสมุดและการรับจองผ่านแอปพลิเคชัน Line ซึ่งก่อให้เกิดปัญหาความล่าช้า การจองเวลาซ้อนกัน ความผิดพลาดในการคำนวณเงิน ตลอดจนการขาดข้อมูลสารสนเทศสนับสนุนการตัดสินใจของผู้ประกอบการ 

โครงงานนี้จึงได้พัฒนา **เว็บแอปพลิเคชันระบบจัดการสนามแบดมินตัน T.S. Pattani** แบบครบวงจร (Full-stack Web Application) เพื่อยกระดับการให้บริการสู่ระบบดิจิทัล รองรับการใช้งานของลูกค้าผ่านสมาร์ทโฟนและคอมพิวเตอร์ตลอด 24 ชั่วโมง พร้อมระบบหลังบ้านที่ครอบคลุมการจัดการสนาม สต็อกสินค้า จุดขายหน้าร้าน (POS) การเงิน บัญชี และระบบวิเคราะห์ข้อมูลเชิงลึก (Business Intelligence Analytics)

---

## ⭐ ฟีเจอร์หลักของระบบ (Key Features)

### 1. ฝั่งสมาชิก / ลูกค้า (Member Portal)
* **One-stop Booking:** จองสนามแบดมินตัน เช่าอุปกรณ์กีฬา (ไม้แบด/รองเท้า) และซื้อสินค้าบริโภค (น้ำดื่ม/ลูกแบด) จบในขั้นตอนเดียว
* **Time-Slot Availability Matrix:** ตารางแสดงความพร้อมของสนามแบบเรียลไทม์ 4 สถานะ (ว่าง, รอตรวจสอบ, จองแล้ว, ปิดปรับปรุง)
* **Day-of-Week Dynamic Pricing:** คำนวณราคาค่าสนามอัตโนมัติตามประเภทวัน:
  * **เสาร์ - อาทิตย์ (วันหยุด):** 200 บาท / ชั่วโมง
  * **จันทร์, พุธ, ศุกร์ (วันปกติ):** 180 บาท / ชั่วโมง
  * **อังคาร, พฤหัสบดี (วันโปรโมชั่นลดพิเศษ):** 150 บาท / ชั่วโมง
* **Anti-Overlap & Atomic Locking:** ระบบป้องกันการจองซ้อนและล็อกเวลาชำระเงินอัตโนมัติ 15 นาที
* **PromptPay QR Code & Slip Upload:** ชำระเงินสะดวกพร้อมระบบตรวจสอบไฟล์สลิป ป้องกันการปลอมแปลง (MIME validation)
* **Member Profile & Point History:** ดูและแก้ไขข้อมูลส่วนตัว พร้อมตารางประวัติการรับ-ใช้พอยท์สะสม
* **Loyalty Program:** สะสมคะแนนจากการใช้บริการ เลื่อนระดับสถานะ (Bronze, Silver, Gold) และแลกของรางวัล
* **Live Chat Support:** แชทสนทนากับแอดมินแบบเรียลไทม์ด้วยระบบ Asynchronous AJAX Polling (อัปเดตทุก 3 วินาที)
* **Booking Cancellation:** สมาชิกสามารถกดยกเลิกการจองได้เองก่อนถึงเวลาใช้งาน พร้อมระบบคืนสต็อกอุปกรณ์เช่าทันที

### 2. ฝั่งผู้ดูแลระบบ (Admin Dashboard & POS)
* **จุดขายหน้าร้าน (POS) & เปิดสนาม Walk-in:** บันทึกการขายสินค้า บริการเช่า และเปิดสนาม Walk-in หน้าเคาน์เตอร์ พร้อมพิมพ์ใบเสร็จความร้อน 80mm
* **ระบบตรวจรับคืนอุปกรณ์ (Equipment Return):** ตรวจสอบสภาพอุปกรณ์ คำนวณค่าปรับส่งคืนล่าช้า ส่งเข้าคิวซ่อมบำรุงอัตโนมัติเมื่อชำรุด และปรับสต็อกคืนคลัง
* **ระบบจัดการการซ่อมบำรุง:** บันทึกประวัติและติดตามค่าใช้จ่ายการแจ้งซ่อมสนามและอุปกรณ์เช่า
* **ระบบพิจารณายกเลิกและคืนเงิน (Cancellation Management):** ตรวจสอบคำขอยกเลิกและพิจารณาคืนเงิน/คืนพอยท์ตามสัดส่วนเวลา
* **ระบบจัดการสต็อกและการจัดซื้อ (Inventory & Purchases):** ควบคุมสต็อกสินค้า แจ้งเตือนสินค้าใกล้หมด และบันทึกประวัติการจัดซื้อ
* **Business Intelligence Dashboard:** วิเคราะห์สถิติผ่านกราฟิก Chart.js ครบวงจร:
  * สรุปรายรับแยก 3 หมวดหมู่ (ค่าสนาม / ค่าเช่า / ค่าสินค้า)
  * กราฟเปรียบเทียบรายได้ย้อนหลัง 12 เดือน (Monthly Revenue)
  * กราฟวิเคราะห์พฤติกรรมลูกค้า: สถิติเพศ, สถิติอาชีพ, **สถิติช่วงอายุ (Demographic Age Groups)**
  * กราฟช่วงเวลาที่มีการใช้บริการหนาแน่นที่สุด (Peak Usage Hours)
  * ส่งออกรายงานรายรับเป็นไฟล์ CSV / Excel

---

## 🛠️ เทคโนโลยีที่ใช้ในการพัฒนา (Technology Stack)

| ส่วนของระบบ | เทคโนโลยีที่เลือกใช้ |
| :--- | :--- |
| **Server & Runtime** | Apache (XAMPP Stack), PHP 8.x |
| **Database** | MySQL / MariaDB (InnoDB Engine, Foreign Key Cascading, UTF-8 MB4) |
| **Backend Architecture** | Native PHP (MVC-inspired, PDO Prepared Statements, ACID Transactions) |
| **Frontend UI** | HTML5, CSS3, JavaScript (ES6+), Bootstrap 5, Font Awesome 6 |
| **Data Visualization** | Chart.js 4.x |
| **Version Control** | Git & GitHub (`Muhammadfarid09/my-final-project`) |

---

## 🚀 ขั้นตอนการติดตั้งและทดสอบระบบ (Installation Guide)

### 1. สภาพแวดล้อมที่ต้องการ (Requirements)
* [XAMPP](https://www.apachefriends.org/) (รองรับ PHP 8.0 ขึ้นไป และ MySQL)
* เว็บเบราว์เซอร์มาตรฐาน (Google Chrome, Microsoft Edge, Firefox, Safari)

### 2. ดาวน์โหลดซอร์สโค้ด (Clone Repository)
นำโฟลเดอร์โปรเจกต์ไปวางไว้ในโฟลเดอร์ `htdocs` ของ XAMPP:
```bash
cd C:\xampp\htdocs
git clone https://github.com/Muhammadfarid09/my-final-project.git ts-pattani
```

### 3. ติดตั้งฐานข้อมูล (Database Setup)
1. เปิดโปรแกรม **XAMPP Control Panel** และกด Start บริการ **Apache** และ **MySQL**
2. เปิดเบราว์เซอร์แล้วเข้าไปที่ URL ด้านล่างนี้เพื่อรันสคริปต์สร้างฐานข้อมูลและตารางอัตโนมัติ:
   ```text
   http://localhost/ts-pattani/config/setup.php
   ```
3. (ทางเลือก) หากต้องการตรวจสอบการอัปเกรดโครงสร้างตารางล่าสุด:
   ```text
   http://localhost/ts-pattani/config/update_database.php
   ```

### 4. สร้างบัญชีผู้ดูแลระบบเริ่มต้น (Seed Admin)
เปิด Command Prompt / PowerShell แล้วรันคำสั่ง:
```bash
C:\xampp\php\php.exe C:\xampp\htdocs\ts-pattani\config\seed_admin.php
```

---

## 🔑 บัญชีสำหรับเข้าทดสอบระบบ (Test Accounts)

### 1. บัญชีผู้ดูแลระบบ (Admin)
* **หน้าเข้าสู่ระบบ:** [http://localhost/ts-pattani/admin/login.php](http://localhost/ts-pattani/admin/login.php)
* **เบอร์โทรศัพท์:** `0993241657`
* **รหัสผ่าน:** `admin1234`
* **สิทธิ์:** Super Admin (เข้าถึงได้ทุกเมนูหลังบ้าน)

### 2. บัญชีสมาชิกตัวอย่าง (Member)
* **หน้าเข้าสู่ระบบ:** [http://localhost/ts-pattani/login.php](http://localhost/ts-pattani/login.php)
* **เบอร์โทรศัพท์:** `0993241657` *(หรือสามารถกด "สมัครสมาชิกใหม่" ได้ทันทีที่หน้าเว็บ)*
* **รหัสผ่าน:** บัญชีที่ลงทะเบียน หรือทดสอบระบบกู้คืนรหัสผ่านด้วยตนเองผ่าน `forgot_password.php`

---

## 📂 โครงสร้างไดเรกทอรีของโปรเจกต์ (Project Structure)

```text
ts-pattani/
│
├── actions/                  # สคริปต์ประมวลผลฝั่งสมาชิก (Controllers & Business Logic)
│   ├── booking_db.php        # ประมวลผลการจองสนาม คำนวณราคา ป้องกันจองซ้อน
│   ├── cancel_booking_db.php # ระบบยกเลิกการจองและบันทึก Cancellation
│   ├── get_court_matrix.php  # JSON API ส่งสถานะ Time-Slot Matrix & ราคาประจำวัน
│   ├── payment_db.php        # อัปโหลดและตรวจสอบไฟล์สลิปโอนเงิน
│   └── ...
│
├── admin/                    # หน้าจอและระบบหลังบ้านของผู้ดูแลระบบ
│   ├── actions/              # สคริปต์ประมวลผลฝั่งแอดมิน
│   ├── includes/             # ส่วนประกอบ UI หลังบ้าน (Sidebar, Header, Auth Check)
│   ├── index.php             # แดชบอร์ดสรุปรายรับและกราฟสถิติวิเคราะห์ข้อมูล
│   ├── pos.php               # จุดขายหน้าร้าน & เปิดสนาม Walk-in
│   ├── equipment_returns.php # ตรวจรับคืนอุปกรณ์เช่าและคำนวณค่าปรับ
│   ├── cancellations.php     # ตรวจสอบและพิจารณาคืนเงินจากการยกเลิก
│   └── ...
│
├── assets/                   # ไฟล์ Static Assets
│   ├── css/                  # สไตล์ชีท (global.css, admin.css, style.css)
│   └── js/                   # สคริปต์หน้าบ้าน (member.js, admin.js)
│
├── config/                   # การตั้งค่าฐานข้อมูลและสคริปต์เริ่มต้นระบบ
│   ├── config.php            # การเชื่อมต่อฐานข้อมูล PDO
│   ├── setup.php             # สคริปต์ติดตั้งตารางฐานข้อมูลทั้งหมด
│   └── seed_admin.php        # สคริปต์สร้างแอดมินเริ่มต้น
│
├── docs/                     # เอกสารการพัฒนาระบบ, บันทึกรายวัน และกฎการออกแบบ UI/UX (PROJECT_HCI_RULES.md)
├── uploads/                  # โฟลเดอร์จัดเก็บไฟล์อัปโหลด (สลิป, รูปข่าว, รูปสินค้า)
├── booking.php               # หน้าจอจองสนามแบบ One-stop พร้อม Time-slot Matrix
├── booking_history.php       # ประวัติการจองและติดตามสถานะ
├── chat.php                  # หน้าจอแชทสนทนากับแอดมิน
├── profile.php               # หน้าโปรไฟล์ส่วนตัวและประวัติคะแนนสะสม
├── rewards.php               # แคตตาล็อกแลกของรางวัล
└── README.md                 # เอกสารแนะนำและคู่มือการใช้งานโครงงาน
```

---

## 📄 ลิขสิทธิ์และสิทธิ์การใช้งาน (License & Credits)

โครงงานนี้พัฒนาขึ้นเพื่อการศึกษาตามหลักสูตรวิทยาศาสตรบัณฑิต คณะวิทยาการสื่อสาร มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตปัตตานี  
© 2026 นายมูฮัมหมัดฟาริศ เจะเลาะ — สงวนลิขสิทธิ์ทั้งหมด
