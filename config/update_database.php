<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปเดตโครงสร้างฐานข้อมูล - T.S. Pattani</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: #f8fafc;
            color: #334155;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }
        .update-box {
            background: #ffffff;
            width: 100%;
            max-width: 650px;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            padding: 30px;
            border: 1px solid #e2e8f0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .header h2 {
            margin: 0 0 8px 0;
            color: #1e3c72;
            font-size: 22px;
        }
        .log-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .log-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .log-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .log-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .log-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .btn-home {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            background: #1e3c72;
            color: #ffffff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 25px;
            transition: background 0.2s;
        }
        .btn-home:hover {
            background: #162c54;
        }
    </style>
</head>
<body>

<div class="update-box">
    <div class="header">
        <i class="fas fa-database fa-3x" style="color: #2563eb; margin-bottom: 12px;"></i>
        <h2>ระบบตรวจสอบ & อัปเดตโครงสร้างฐานข้อมูล</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Database Migration & Health Check (ts_pattani_db)</p>
    </div>

    <div>
    <?php
    $logs = [];

    try {
        // 1. ตรวจสอบตาราง Booking (ให้ member_id เป็น NULL ได้สำหรับลูกค้า Walk-in)
        $cols_booking = $conn->query("SHOW COLUMNS FROM Booking")->fetchAll(PDO::FETCH_ASSOC);
        $member_id_col = null;
        foreach ($cols_booking as $col) {
            if ($col['Field'] === 'member_id') {
                $member_id_col = $col;
                break;
            }
        }

        if ($member_id_col && $member_id_col['Null'] === 'NO') {
            $conn->exec("ALTER TABLE Booking MODIFY COLUMN member_id INT(11) NULL");
            echo "<div class='log-item log-success'><i class='fas fa-check-circle'></i> [ตาราง Booking] ปรับปรุงฟิลด์ member_id ให้รองรับค่าว่าง (NULL) สำหรับลูกค้า Walk-in สำเร็จ</div>";
        } else {
            echo "<div class='log-item log-info'><i class='fas fa-check'></i> [ตาราง Booking] ฟิลด์ member_id รองรับลูกค้า Walk-in อยู่แล้ว</div>";
        }

        // 2. ตรวจสอบตาราง Rental (คอลัมน์ค่าปรับและการตรวจรับคืน)
        $cols_rental = $conn->query("SHOW COLUMNS FROM Rental")->fetchAll(PDO::FETCH_COLUMN);

        // 2.1 rental_fine
        if (!in_array('rental_fine', $cols_rental)) {
            $conn->exec("ALTER TABLE Rental ADD COLUMN rental_fine DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER rental_status");
            echo "<div class='log-item log-success'><i class='fas fa-check-circle'></i> [ตาราง Rental] เพิ่มคอลัมน์ rental_fine (ค่าปรับ) สำเร็จ</div>";
        } else {
            echo "<div class='log-item log-info'><i class='fas fa-check'></i> [ตาราง Rental] มีคอลัมน์ rental_fine อยู่แล้ว</div>";
        }

        // 2.2 rental_fine_reason
        if (!in_array('rental_fine_reason', $cols_rental)) {
            $conn->exec("ALTER TABLE Rental ADD COLUMN rental_fine_reason VARCHAR(255) NULL AFTER rental_fine");
            echo "<div class='log-item log-success'><i class='fas fa-check-circle'></i> [ตาราง Rental] เพิ่มคอลัมน์ rental_fine_reason (เหตุผลค่าปรับ) สำเร็จ</div>";
        } else {
            echo "<div class='log-item log-info'><i class='fas fa-check'></i> [ตาราง Rental] มีคอลัมน์ rental_fine_reason อยู่แล้ว</div>";
        }

        // 2.3 rental_actual_return
        if (!in_array('rental_actual_return', $cols_rental)) {
            $conn->exec("ALTER TABLE Rental ADD COLUMN rental_actual_return DATETIME NULL AFTER rental_fine_reason");
            echo "<div class='log-item log-success'><i class='fas fa-check-circle'></i> [ตาราง Rental] เพิ่มคอลัมน์ rental_actual_return (เวลาคืนจริง) สำเร็จ</div>";
        } else {
            echo "<div class='log-item log-info'><i class='fas fa-check'></i> [ตาราง Rental] มีคอลัมน์ rental_actual_return อยู่แล้ว</div>";
        }

        // 2.4 ปรับค่า ENUM สถานะการเช่า
        $conn->exec("ALTER TABLE Rental MODIFY COLUMN rental_status ENUM('รอตรวจสอบ','กำลังเช่า','คืนแล้ว','ชำรุด','สูญหาย') NOT NULL DEFAULT 'กำลังเช่า'");
        echo "<div class='log-item log-success'><i class='fas fa-check-circle'></i> [ตาราง Rental] ปรับปรุงสถานะ rental_status ให้รองรับ ชำรุด/สูญหาย สำเร็จ</div>";

        // 3. ตรวจสอบตาราง Cancellation
        $check_cancel = $conn->query("SHOW TABLES LIKE 'Cancellation'")->fetch();
        if ($check_cancel) {
            echo "<div class='log-item log-info'><i class='fas fa-check'></i> [ตาราง Cancellation] ตรวจพบตารางรองรับการยกเลิกเรียบร้อยแล้ว</div>";
        } else {
            $conn->exec("
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
            ");
            echo "<div class='log-item log-success'><i class='fas fa-check-circle'></i> [ตาราง Cancellation] สร้างตารางใหม่เรียบร้อยแล้ว</div>";
        }

        echo "<div style='text-align: center; margin-top: 25px; padding: 15px; background: #ecfdf5; border-radius: 8px; border: 1px solid #a7f3d0;'>
                <i class='fas fa-shield-alt fa-2x' style='color: #059669; margin-bottom: 6px;'></i>
                <div style='font-size: 16px; font-weight: bold; color: #065f46;'>ฐานข้อมูลของคุณเป็นเวอร์ชันล่าสุดและพร้อมใช้งาน 100%!</div>
                <div style='font-size: 13px; color: #047857; margin-top: 4px;'>ข้อมูลเก่าไม่สูญหาย โครงสร้างระบบตรงตามขอบเขตโปรเจกต์ทั้งหมดแล้ว</div>
              </div>";

    } catch (Exception $e) {
        echo "<div class='log-item log-error'><i class='fas fa-exclamation-triangle'></i> เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>
    </div>

    <a href="../admin/index.php" class="btn-home"><i class="fas fa-arrow-left"></i> กลับสู่หน้าหลักผู้ดูแลระบบ</a>
</div>

</body>
</html>
