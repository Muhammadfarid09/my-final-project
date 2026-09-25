<?php
$host = "localhost";
$username = "root"; 
$password = ""; 

try {
    // 1. เชื่อมต่อ MySQL Server
    $conn = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. สร้างฐานข้อมูล ts_pattani_db
    $sql_db = "CREATE DATABASE IF NOT EXISTS ts_pattani_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql_db);
    echo "<h3>1. สร้างฐานข้อมูล 'ts_pattani_db' สำเร็จ!</h3>";

    // 3. เลือกใช้งานฐานข้อมูล
    $conn->exec("USE ts_pattani_db");

    // 1.ตารางข้อมูลสมาชิก (Member)
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->exec($sql_table_member);
    echo "<h3>2. สร้างตาราง 'Member' สำเร็จ!</h3>";

    // 2.ตารางผู้ดูแลระบบ (Admin)
    $sql_table_admin = "
        CREATE TABLE IF NOT EXISTS `Admin` (
            `admin_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `admin_name` VARCHAR(100) NOT NULL,
            `admin_phone` VARCHAR(10) NOT NULL UNIQUE,
            `admin_password` VARCHAR(255) NOT NULL,
            `admin_role` VARCHAR(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->exec($sql_table_admin);
    echo "<h3>3. สร้างตาราง 'Admin' สำเร็จ!</h3>";

    // 3.ตารางข้อมูลสนาม (Court)
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->exec($sql_table_court);
    echo "<h3>4. สร้างตาราง 'Court' สำเร็จ!</h3>";

    // 4.ตารางการจอง (Booking) 
    $sql_table_booking = "
        CREATE TABLE IF NOT EXISTS `Booking` (
            `booking_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `member_id` INT(11) NOT NULL,
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
    echo "<h3>5. สร้างตาราง 'Booking' สร้างสำเร็จ!</h3>";

    // 7.ตารางข้อมูลสินค้าและอุปกรณ์เช่า (Product)
    $sql_product = "CREATE TABLE IF NOT EXISTS Product (
        product_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(100) NOT NULL,
        product_image VARCHAR(255) NULL,
        product_type ENUM('อุปกรณ์เช่า', 'สินค้าบริโภค') NOT NULL,
        product_price DECIMAL(8,2) NOT NULL,
        product_stock INT(11) NOT NULL DEFAULT 0,
        product_brand VARCHAR(100) NULL,
        product_model VARCHAR(100) NULL,
        product_size VARCHAR(20) NULL,
        product_level ENUM('มือใหม่', 'มือโปร') NULL,
        product_min_stock INT(11) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $conn->exec($sql_product);
    echo "<h3>6. สร้างตาราง 'Product' สร้างสำเร็จ!</h3>";

    // 11.ตารางข้อมูลของรางวัลและโปรโมชั่น (Reward)
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
    echo "<h3>7. สร้างตาราง 'Reward' สร้างสำเร็จ!</h3>";

    // 9.ตารางข้อมูลคะแนนสะสม (Point)
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
    echo "<h3>8. สร้างตาราง 'Point' สร้างสำเร็จ!</h3>";

    // 12.ตารางข้อมูลการซ่อมบำรุงสนาม (Court_repair)
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
    echo "<h3>9. สร้างตาราง 'Court_repair' สร้างสำเร็จ!</h3>";

    // 13.ตารางข้อมูลการซ่อมบำรุงอุปกรณ์ (Equipment_repair)
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
    echo "<h3>13. สร้างตาราง 'Equipment_repair' สร้างสำเร็จ!</h3>";

    // 14.ตารางข้อมูลการจัดซื้ออุปกรณ์ (Purchase)
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
    echo "<h3>11. สร้างตาราง 'Purchase' สร้างสำเร็จ!</h3>";

    // 15.ตารางข้อมูลข้อความแชท (Chat)
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
    echo "<h3>12. สร้างตาราง 'Chat' สร้างสำเร็จ!</h3>";
    
    // 16.ตารางข้อมูลข่าวสารและประชาสัมพันธ์ (News)
    $sql_table_news = "
        CREATE TABLE IF NOT EXISTS `News` (
            `news_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `admin_id` INT(11) NOT NULL,
            `news_title` VARCHAR(255) NOT NULL,
            `news_content` TEXT NOT NULL,
            `news_image` VARCHAR(255) DEFAULT NULL,
            `news_published_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`admin_id`) REFERENCES `Admin`(`admin_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_news);
    echo "<h3>13. สร้างตาราง 'News' สร้างสำเร็จ!</h3>";

    // 8.ตารางข้อมูลรายการเช่าอุปกรณ์ (Rental)
    $sql_table_rental = "
        CREATE TABLE IF NOT EXISTS `Rental` (
            `rental_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT(11) NOT NULL,
            `product_id` INT(11) NOT NULL,
            `rental_quantity` INT(11) NOT NULL,
            `rental_start_time` DATETIME NOT NULL,
            `rental_return_time` DATETIME DEFAULT NULL,
            `rental_status` ENUM('กำลังเช่า', 'คืนแล้ว') NOT NULL DEFAULT 'กำลังเช่า',
            FOREIGN KEY (`booking_id`) REFERENCES `Booking`(`booking_id`) ON DELETE CASCADE,
            FOREIGN KEY (`product_id`) REFERENCES `Product`(`product_id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_rental);
    echo "<h3>14. สร้างตาราง 'Rental' สร้างสำเร็จ!</h3>";

    // 10.ตารางข้อมูลประวัติการได้รับและใช้คะแนน (Point_Transaction)
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
    echo "<h3>15. สร้างตาราง 'Point_Transaction' สร้างสำเร็จ!</h3>";

    // 17.ตารางข้อมูลประวัติการยกเลิก (Cancellation)
    $sql_table_cancellation = "
        CREATE TABLE IF NOT EXISTS `Cancellation` (
            `cancel_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT(11) NOT NULL,
            `admin_id` INT(11) DEFAULT NULL,
            `cancel_reason` VARCHAR(255) NOT NULL,
            `cancel_by` ENUM('สมาชิก', 'Admin') NOT NULL,
            `refund_status` ENUM('คืนแล้ว', 'ไม่คืน', 'รอดำเนินการ') DEFAULT 'รอดำเนินการ',
            `refund_point` INT(11) DEFAULT 0,
            `cancel_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`booking_id`) REFERENCES `Booking`(`booking_id`) ON DELETE CASCADE,
            FOREIGN KEY (`admin_id`) REFERENCES `Admin`(`admin_id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->exec($sql_table_cancellation);
    echo "<h3>16. สร้างตาราง 'Cancellation' สร้างสำเร็จ!</h3>";

    // 18.ตารางข้อมูลการขายหน้าร้าน (Pos_sale)
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
    echo "<h3>17. สร้างตาราง 'Pos_sale' สร้างสำเร็จ!</h3>";

    // 5.ตารางข้อมูลการชำระเงิน (Payment)
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
    echo "<h3>18. สร้างตาราง 'Payment' สร้างสำเร็จ!</h3>";

    // 6.ตารางข้อมูลรายรับ (Revenue)
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
    echo "<h3>19. สร้างตาราง 'Revenue' สร้างสำเร็จ!</h3>";

} catch(PDOException $e) {
    echo "<h3 style='color:red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</h3>";
}
?>