<?php
// ==============================================================================
// T.S. Pattani Badminton Club - Database Setup & Migration Script
// ไฟล์สำหรับติดตั้งโครงสร้างฐานข้อมูลเริ่มต้น (Schema Definition & Initial Data)
// มาตรฐาน: InnoDB, utf8mb4_unicode_ci พร้อมฟิลด์ระบบล่าสุดครบ 19 ตาราง
// ==============================================================================

$host = "localhost";
$username = "root"; 
$password = ""; 

try {
    // 1. เชื่อมต่อ MySQL Server
    $conn = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. สร้างฐานข้อมูล ts_pattani_db หากยังไม่มี
    $sql_db = "CREATE DATABASE IF NOT EXISTS `ts_pattani_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql_db);

    // 3. เลือกใช้งานฐานข้อมูล ts_pattani_db
    $conn->exec("USE `ts_pattani_db`");

    // ==============================================================================
    // 1. ตารางข้อมูลสมาชิก (Member)
    // ใช้สำหรับ: จัดเก็บข้อมูลโปรไฟล์สมาชิก บัญชีผู้ใช้งาน สถิติอายุ/อาชีพ และสถานะการระงับสิทธิ์
    // ==============================================================================
    $sql_table_member = "
        CREATE TABLE IF NOT EXISTS `Member` (
            `member_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `member_gender` ENUM('ชาย', 'หญิง', 'อื่นๆ') DEFAULT 'อื่นๆ',
            `member_name` VARCHAR(100) NOT NULL,
            `member_phone` VARCHAR(10) NOT NULL UNIQUE,
            `member_password` VARCHAR(255) NOT NULL,
            `member_age` INT(3) DEFAULT NULL,
            `member_occupation` VARCHAR(100) DEFAULT NULL,
            `member_status` ENUM('ปกติ', 'ระงับสิทธิ์') DEFAULT 'ปกติ',
            `member_created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_member);

    // ==============================================================================
    // 2. ตารางผู้ดูแลระบบ (Admin)
    // ใช้สำหรับ: จัดเก็บข้อมูลบัญชีแอดมิน เจ้าหน้าที่สนาม สิทธิ์บทบาท และรหัสผ่านความปลอดภัยสูง
    // ==============================================================================
    $sql_table_admin = "
        CREATE TABLE IF NOT EXISTS `Admin` (
            `admin_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `admin_name` VARCHAR(100) NOT NULL,
            `admin_phone` VARCHAR(10) NOT NULL UNIQUE,
            `admin_password` VARCHAR(255) NOT NULL,
            `admin_role` VARCHAR(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_admin);

    // ==============================================================================
    // 3. ตารางคำขอรีเซ็ตรหัสผ่าน (Password_Resets)
    // ใช้สำหรับ: จัดเก็บ Token Hash แบบใช้ครั้งเดียว พร้อมวันหมดอายุในการกู้คืนรหัสผ่านสมาชิก
    // ==============================================================================
    $sql_table_password_resets = "
        CREATE TABLE IF NOT EXISTS `Password_Resets` (
            `reset_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `member_id` INT(11) NOT NULL,
            `token_hash` VARCHAR(64) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `used` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_password_resets);

    // ==============================================================================
    // 4. ตารางข้อมูลสนามแบดมินตัน (Court)
    // ใช้สำหรับ: จัดเก็บรายชื่อคอร์ท สถานะความพร้อม (ว่าง/รอตรวจสอบ/จองแล้ว/ปิดปรับปรุง) และอัตราค่าบริการ
    // ==============================================================================
    $sql_table_court = "
        CREATE TABLE IF NOT EXISTS `Court` (
            `court_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `court_name` VARCHAR(50) NOT NULL,
            `court_status` ENUM('ว่าง', 'รอตรวจสอบ', 'จองแล้ว', 'ปิดปรับปรุง') NOT NULL DEFAULT 'ว่าง',
            `court_price_per_hour` DECIMAL(8,2) NOT NULL,
            `court_peak_price` DECIMAL(8,2) DEFAULT NULL,
            `court_offpeak_price` DECIMAL(8,2) DEFAULT NULL,
            `court_open_time` TIME DEFAULT NULL,
            `court_close_time` TIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_court);

    // ==============================================================================
    // 5. ตารางการจองสนาม (Booking)
    // ใช้สำหรับ: จัดเก็บรายการจอง วันเวลา คอร์ท ยอดรวมเงิน กลไก Concurrency Lock (15 นาที) และไฟล์สลิป
    // ==============================================================================
    $sql_table_booking = "
        CREATE TABLE IF NOT EXISTS `Booking` (
            `booking_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `member_id` INT(11) NULL,
            `booking_nickname` VARCHAR(50) NULL DEFAULT NULL,
            `court_id` INT(11) NOT NULL,
            `booking_date` DATE NOT NULL,
            `booking_start_time` TIME NOT NULL,
            `booking_end_time` TIME NOT NULL,
            `booking_status` ENUM('รอตรวจสอบ', 'จองแล้ว', 'ยกเลิก') NOT NULL DEFAULT 'รอตรวจสอบ',
            `booking_court_price` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            `booking_rental_price` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            `booking_product_price` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            `booking_total_price` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            `booking_created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `booking_lock_expire` DATETIME DEFAULT NULL,
            `payment_slip` VARCHAR(255) DEFAULT NULL,
            FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) ON DELETE CASCADE,
            FOREIGN KEY (`court_id`) REFERENCES `Court`(`court_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_booking);

    // ==============================================================================
    // 6. ตารางสินค้าและอุปกรณ์เช่า (Product)
    // ใช้สำหรับ: จัดเก็บแคตตาล็อกสินค้า เครื่องดื่ม อุปกรณ์เช่า ราคา จำนวนสต็อก และจุดสั่งซื้อขั้นต่ำ
    // ==============================================================================
    $sql_table_product = "
        CREATE TABLE IF NOT EXISTS `Product` (
            `product_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `product_name` VARCHAR(100) NOT NULL,
            `product_image` VARCHAR(255) NULL,
            `product_type` ENUM('อุปกรณ์เช่า', 'สินค้าบริโภค') NOT NULL,
            `product_price` DECIMAL(8,2) NOT NULL,
            `product_stock` INT(11) NOT NULL DEFAULT 0,
            `product_brand` VARCHAR(100) NULL,
            `product_model` VARCHAR(100) NULL,
            `product_size` VARCHAR(20) NULL,
            `product_level` ENUM('มือใหม่', 'มือโปร') NULL,
            `product_min_stock` INT(11) NOT NULL DEFAULT 5
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_product);

    // ==============================================================================
    // 7. ตารางข้อมูลรายการเช่าอุปกรณ์ (Rental)
    // ใช้สำหรับ: ติดตามสถานะการเช่าไม้แบดมินตัน การคืน การคิดค่าปรับส่งคืนช้า และการชำรุดเสียหาย
    // ==============================================================================
    $sql_table_rental = "
        CREATE TABLE IF NOT EXISTS `Rental` (
            `rental_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT(11) NULL DEFAULT NULL,
            `member_id` INT(11) NULL DEFAULT NULL,
            `product_id` INT(11) NOT NULL,
            `rental_quantity` INT(11) NOT NULL,
            `rental_start_time` DATETIME NOT NULL,
            `rental_return_time` DATETIME DEFAULT NULL,
            `rental_status` ENUM('รอตรวจสอบ', 'กำลังเช่า', 'คืนแล้ว', 'ชำรุด', 'สูญหาย', 'ยกเลิก') NOT NULL DEFAULT 'กำลังเช่า',
            `rental_fine` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            `rental_fine_reason` VARCHAR(255) DEFAULT NULL,
            `rental_actual_return` DATETIME DEFAULT NULL,
            FOREIGN KEY (`booking_id`) REFERENCES `Booking`(`booking_id`) ON DELETE CASCADE,
            FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) ON DELETE SET NULL,
            FOREIGN KEY (`product_id`) REFERENCES `Product`(`product_id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_rental);

    // ==============================================================================
    // 8. ตารางข้อมูลการชำระเงิน (Payment)
    // ใช้สำหรับ: บันทึกหลักฐานสลิปโอนเงิน ยอดชำระ เวลาที่โอน และผลการตรวจสอบยืนยันโดยแอดมิน
    // ==============================================================================
    $sql_table_payment = "
        CREATE TABLE IF NOT EXISTS `Payment` (
            `payment_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT(11) NOT NULL,
            `admin_id` INT(11) DEFAULT NULL,
            `payment_slip_image` VARCHAR(255) NOT NULL,
            `payment_amount` DECIMAL(8,2) NOT NULL,
            `payment_transfer_time` DATETIME NOT NULL,
            `payment_status` ENUM('รอตรวจสอบ', 'ยืนยันแล้ว', 'ปฏิเสธ') NOT NULL DEFAULT 'รอตรวจสอบ',
            `payment_verified_at` DATETIME DEFAULT NULL,
            FOREIGN KEY (`booking_id`) REFERENCES `Booking`(`booking_id`) ON DELETE CASCADE,
            FOREIGN KEY (`admin_id`) REFERENCES `Admin`(`admin_id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_payment);

    // ==============================================================================
    // 9. ตารางข้อมูลรายรับรวม (Revenue)
    // ใช้สำหรับ: บันทึกข้อมูลทางการเงินเพื่อออกรายงาน แยกหมวดค่าสนาม ค่าเช่า ค่าสินค้า ทั้งออนไลน์และหน้าร้าน
    // ==============================================================================
    $sql_table_revenue = "
        CREATE TABLE IF NOT EXISTS `Revenue` (
            `revenue_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `payment_id` INT(11) DEFAULT NULL,
            `revenue_court_amount` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            `revenue_rental_amount` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            `revenue_product_amount` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            `revenue_total_amount` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            `revenue_date` DATE NOT NULL,
            `revenue_type` ENUM('ออนไลน์', 'หน้าร้าน') NOT NULL,
            FOREIGN KEY (`payment_id`) REFERENCES `Payment`(`payment_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_revenue);

    // ==============================================================================
    // 10. ตารางข้อมูลคะแนนสะสมและระดับสมาชิก (Point)
    // ใช้สำหรับ: บันทึกคะแนนสะสมคงเหลือ คะแนนรวมตลอดชีพ และระดับสมาชิก Loyalty Tier (Bronze, Silver, Gold)
    // ==============================================================================
    $sql_table_point = "
        CREATE TABLE IF NOT EXISTS `Point` (
            `point_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `member_id` INT(11) NOT NULL,
            `point_balance` INT(11) NOT NULL DEFAULT 0,
            `point_total_earned` INT(11) NOT NULL DEFAULT 0,
            `member_level` ENUM('Bronze', 'Silver', 'Gold') NOT NULL DEFAULT 'Bronze',
            `point_updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_point);

    // ==============================================================================
    // 11. ตารางประวัติการได้/ใช้คะแนน (Point_Transaction)
    // ใช้สำหรับ: บันทึกสมุดบัญชีแต้ม (Ledger) ทุกรายการที่ได้รับ ใช้แลกของรางวัล หรือปรับปรุงโดยแอดมิน
    // ==============================================================================
    $sql_table_point_transaction = "
        CREATE TABLE IF NOT EXISTS `Point_Transaction` (
            `transaction_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `member_id` INT(11) NOT NULL,
            `booking_id` INT(11) DEFAULT NULL,
            `transaction_type` ENUM('ได้รับ', 'ใช้', 'ปรับโดย Admin') NOT NULL,
            `transaction_point` INT(11) NOT NULL,
            `transaction_note` VARCHAR(255) DEFAULT NULL,
            `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) ON DELETE CASCADE,
            FOREIGN KEY (`booking_id`) REFERENCES `Booking`(`booking_id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_point_transaction);

    // ==============================================================================
    // 12. ตารางข้อมูลของรางวัล (Reward)
    // ใช้สำหรับ: จัดเก็บแคตตาล็อกของรางวัล แต้มที่ต้องใช้ ระดับสมาชิกขั้นต่ำ และจำนวนของรางวัลคงเหลือ
    // ==============================================================================
    $sql_table_reward = "
        CREATE TABLE IF NOT EXISTS `Reward` (
            `reward_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `reward_name` VARCHAR(100) NOT NULL,
            `reward_image` VARCHAR(255) DEFAULT NULL,
            `reward_point_cost` INT(11) NOT NULL,
            `reward_min_level` ENUM('Bronze', 'Silver', 'Gold') NOT NULL,
            `reward_stock` INT(11) NOT NULL DEFAULT 0,
            `reward_description` VARCHAR(255) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_reward);

    // ==============================================================================
    // 13. ตารางบันทึกการซ่อมบำรุงสนาม (Court_repair)
    // ใช้สำหรับ: บันทึกประวัติและค่าใช้จ่ายในการซ่อมแซมสนาม วันที่ดำเนินการ และสถานะการซ่อมบำรุง
    // ==============================================================================
    $sql_table_court_repair = "
        CREATE TABLE IF NOT EXISTS `Court_repair` (
            `repair_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `court_id` INT(11) NOT NULL,
            `admin_id` INT(11) NOT NULL,
            `repair_cause` VARCHAR(255) NOT NULL,
            `repair_start_date` DATE NOT NULL,
            `repair_expected_end` DATE DEFAULT NULL,
            `repair_actual_end` DATE DEFAULT NULL,
            `repair_cost` DECIMAL(8,2) DEFAULT 0.00,
            `repair_status` ENUM('กำลังซ่อม', 'เสร็จแล้ว') NOT NULL DEFAULT 'กำลังซ่อม',
            FOREIGN KEY (`court_id`) REFERENCES `Court`(`court_id`) ON DELETE CASCADE,
            FOREIGN KEY (`admin_id`) REFERENCES `Admin`(`admin_id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_court_repair);

    // ==============================================================================
    // 14. ตารางบันทึกการซ่อมบำรุงอุปกรณ์ (Equipment_repair)
    // ใช้สำหรับ: บันทึกประวัติอุปกรณ์ที่ส่งซ่อม/ชำรุดเสียหายถาวร สาเหตุ และค่าใช้จ่ายในการซ่อมแซม
    // ==============================================================================
    $sql_table_equipment_repair = "
        CREATE TABLE IF NOT EXISTS `Equipment_repair` (
            `eq_repair_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT(11) NOT NULL,
            `admin_id` INT(11) NOT NULL,
            `eq_repair_qty` INT(11) NOT NULL DEFAULT 1,
            `eq_repair_cause` VARCHAR(255) NOT NULL,
            `eq_repair_date` DATE NOT NULL,
            `eq_repair_status` ENUM('กำลังซ่อม', 'ซ่อมแล้ว', 'เสียหายถาวร') NOT NULL DEFAULT 'กำลังซ่อม',
            `eq_repair_cost` DECIMAL(8,2) DEFAULT 0.00,
            FOREIGN KEY (`product_id`) REFERENCES `Product`(`product_id`) ON DELETE CASCADE,
            FOREIGN KEY (`admin_id`) REFERENCES `Admin`(`admin_id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_equipment_repair);

    // ==============================================================================
    // 15. ตารางประวัติการจัดซื้ออุปกรณ์เข้าสต็อก (Purchase)
    // ใช้สำหรับ: บันทึกรายการสั่งซื้อสินค้าเข้าคลัง จำนวน ราคาต่อหน่วย และยอดเงินลงทุนซื้อสินค้า
    // ==============================================================================
    $sql_table_purchase = "
        CREATE TABLE IF NOT EXISTS `Purchase` (
            `purchase_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT(11) NOT NULL,
            `admin_id` INT(11) NOT NULL,
            `purchase_quantity` INT(11) NOT NULL,
            `purchase_price_per_unit` DECIMAL(8,2) NOT NULL,
            `purchase_total_price` DECIMAL(8,2) NOT NULL,
            `purchase_date` DATE NOT NULL,
            FOREIGN KEY (`product_id`) REFERENCES `Product`(`product_id`) ON DELETE CASCADE,
            FOREIGN KEY (`admin_id`) REFERENCES `Admin`(`admin_id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_purchase);

    // ==============================================================================
    // 16. ตารางบันทึกการขายสินค้าหน้าร้าน (Pos_sale)
    // ใช้สำหรับ: บันทึกรายการขายสินค้า/เครื่องดื่มหน้าร้าน POS ยอดเงิน และแอดมินผู้ทำรายการ
    // ==============================================================================
    $sql_table_pos = "
        CREATE TABLE IF NOT EXISTS `Pos_sale` (
            `pos_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `admin_id` INT(11) NOT NULL,
            `product_id` INT(11) NOT NULL,
            `pos_quantity` INT(11) NOT NULL,
            `pos_total_price` DECIMAL(8,2) NOT NULL,
            `pos_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`admin_id`) REFERENCES `Admin`(`admin_id`) ON DELETE RESTRICT,
            FOREIGN KEY (`product_id`) REFERENCES `Product`(`product_id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_pos);

    // ==============================================================================
    // 17. ตารางบันทึกข้อความแชท (Chat)
    // ใช้สำหรับ: จัดเก็บบทสนทนาระหว่างสมาชิกกับเจ้าหน้าที่แอดมิน วันเวลา และสถานะผู้ส่ง
    // ==============================================================================
    $sql_table_chat = "
        CREATE TABLE IF NOT EXISTS `Chat` (
            `chat_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `member_id` INT(11) NOT NULL,
            `admin_id` INT(11) DEFAULT NULL,
            `chat_message` TEXT NOT NULL,
            `chat_sender` ENUM('สมาชิก', 'Admin') NOT NULL,
            `chat_datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) ON DELETE CASCADE,
            FOREIGN KEY (`admin_id`) REFERENCES `Admin`(`admin_id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_chat);

    // ==============================================================================
    // 18. ตารางข้อมูลข่าวสารและประชาสัมพันธ์ (News)
    // ใช้สำหรับ: จัดเก็บข่าวสาร โปรโมชันส่วนลด กิจกรรมสนาม สถานะเผยแพร่ และหมวดหมู่ข่าว
    // ==============================================================================
    $sql_table_news = "
        CREATE TABLE IF NOT EXISTS `News` (
            `news_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `admin_id` INT(11) NOT NULL,
            `news_title` VARCHAR(255) NOT NULL,
            `news_content` TEXT NOT NULL,
            `news_image` VARCHAR(255) DEFAULT NULL,
            `news_published_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            `news_type` ENUM('โปรโมชั่น', 'ข่าวสารกิจกรรม') NOT NULL DEFAULT 'ข่าวสารกิจกรรม',
            FOREIGN KEY (`admin_id`) REFERENCES `Admin`(`admin_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_news);

    // ==============================================================================
    // 19. ตารางประวัติการยกเลิกการจอง (Cancellation)
    // ใช้สำหรับ: บันทึกข้อมูลเหตุผลการยกเลิก ผู้ยกเลิก ข้อมูลบัญชีขอเงินคืน และสถานะการคืนแต้ม
    // ==============================================================================
    $sql_table_cancellation = "
        CREATE TABLE IF NOT EXISTS `Cancellation` (
            `cancel_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT(11) NOT NULL,
            `admin_id` INT(11) DEFAULT NULL,
            `cancel_reason` VARCHAR(255) NOT NULL,
            `cancel_by` ENUM('สมาชิก', 'Admin') NOT NULL,
            `refund_status` ENUM('คืนแล้ว', 'ไม่คืน', 'รอดำเนินการ') DEFAULT 'รอดำเนินการ',
            `refund_bank` VARCHAR(50) DEFAULT NULL,
            `refund_account_no` VARCHAR(30) DEFAULT NULL,
            `refund_account_name` VARCHAR(100) DEFAULT NULL,
            `refund_point` INT(11) DEFAULT 0,
            `cancel_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`booking_id`) REFERENCES `Booking`(`booking_id`) ON DELETE CASCADE,
            FOREIGN KEY (`admin_id`) REFERENCES `Admin`(`admin_id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_cancellation);

    // ==============================================================================
    // 20. บันทึกบัญชีผู้ดูแลระบบ (Admin) เริ่มต้น (Seed Initial Administrator)
    // ==============================================================================
    $default_admin_name = "เจ้าของสนาม (Admin)";
    $default_admin_phone = "0993241657"; 
    $default_admin_plain = "admin1234"; 
    $default_admin_role = "Super Admin";

    $stmt_check_admin = $conn->prepare("SELECT `admin_id` FROM `Admin` WHERE `admin_phone` = :phone");
    $stmt_check_admin->execute([':phone' => $default_admin_phone]);
    
    if ($stmt_check_admin->rowCount() === 0) {
        $default_admin_hashed = password_hash($default_admin_plain, PASSWORD_DEFAULT);
        $stmt_insert_admin = $conn->prepare("
            INSERT INTO `Admin` (`admin_name`, `admin_phone`, `admin_password`, `admin_role`) 
            VALUES (:name, :phone, :password, :role)
        ");
        $stmt_insert_admin->execute([
            ':name'     => $default_admin_name,
            ':phone'    => $default_admin_phone,
            ':password' => $default_admin_hashed,
            ':role'     => $default_admin_role
        ]);
    }

    // ==============================================================================
    // 21. แสดงผลลัพธ์สรุปสั้นๆ กระชับ (Clean Single Summary Output)
    // ==============================================================================
    if (php_sapi_name() === 'cli') {
        echo "🎉 ติดตั้งฐานข้อมูล 'ts_pattani_db' สำเร็จ (19 ตารางครบถ้วน พร้อมบัญชี Admin เริ่มต้น)\n";
    } else {
        echo "<div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; max-width: 600px; margin: 40px auto; padding: 24px; background: #ECFDF5; border: 1px solid #10B981; border-radius: 12px; color: #065F46; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center;'>";
        echo "<h2 style='margin: 0 0 10px 0; font-size: 20px; color: #047857;'>🎉 ติดตั้งฐานข้อมูล 'ts_pattani_db' สำเร็จเรียบร้อยแล้ว!</h2>";
        echo "<p style='margin: 0; font-size: 14.5px; color: #065F46;'>โครงสร้างฐานข้อมูลครบถ้วนทั้ง 19 ตาราง พร้อมบันทึกบัญชีผู้ดูแลระบบเริ่มต้นเรียบร้อยแล้ว</p>";
        echo "</div>";
    }

} catch(PDOException $e) {
    if (php_sapi_name() === 'cli') {
        echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    } else {
        echo "<div style='font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; background: #FEF2F2; border: 1px solid #EF4444; border-radius: 10px; color: #991B1B;'>";
        echo "<h3 style='margin: 0 0 8px 0;'>❌ เกิดข้อผิดพลาดในการตั้งค่าฐานข้อมูล</h3>";
        echo "<p style='margin: 0; font-size: 14px;'>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
        echo "</div>";
    }
}