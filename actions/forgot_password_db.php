<?php
session_start();
require_once '../config/config.php';
require_once '../includes/csrf.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. ตรวจสอบ CSRF Token เพื่อความปลอดภัย
    require_csrf_token();

    $phone = trim($_POST['member_phone'] ?? '');

    try {
        if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) {
            throw new Exception("กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้องครบ 10 หลัก");
        }

        // 2. สร้างตาราง Password_Resets อัตโนมัติหากยังไม่มี (Auto Migration)
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `Password_Resets` (
                `reset_id` INT AUTO_INCREMENT PRIMARY KEY,
                `member_id` INT NOT NULL,
                `token_hash` VARCHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `used` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_token_hash` (`token_hash`),
                KEY `idx_member_id` (`member_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 3. ค้นหาสมาชิกด้วย Prepared Statement (SQL Injection Prevention)
        $stmt = $conn->prepare("SELECT member_id, member_phone, member_name FROM Member WHERE member_phone = :phone AND member_status = 'ปกติ' LIMIT 1");
        $stmt->execute([':phone' => $phone]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($member) {
            // ยกเลิกโทเค็นเดิมที่ยังไม่หมดอายุของสมาชิกรายนี้
            $stmt_cancel = $conn->prepare("UPDATE Password_Resets SET used = 1 WHERE member_id = :member_id AND used = 0");
            $stmt_cancel->execute([':member_id' => $member['member_id']]);

            // 4. สร้าง Cryptographic Token สุ่ม 32 ไบต์ (256-bit Entropy)
            $raw_token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $raw_token);
            $expires_at = date('Y-m-d H:i:s', time() + (30 * 60)); // อายุ 30 นาที

            // 5. บันทึก Token Hash ลงฐานข้อมูล
            $stmt_insert = $conn->prepare("INSERT INTO Password_Resets (member_id, token_hash, expires_at) VALUES (:member_id, :token_hash, :expires_at)");
            $stmt_insert->execute([
                ':member_id' => $member['member_id'],
                ':token_hash' => $token_hash,
                ':expires_at' => $expires_at
            ]);

            // บันทึกลิงก์จำลองสำหรับโหมดสาธิตการสอบโครงงาน (Demo Mode)
            $_SESSION['demo_reset_link'] = "reset_password.php?token=" . urlencode($raw_token);
        }

        // 6. User Enumeration Prevention: แสดงข้อความเดียวกันเสมอ ไม่ว่าพบเบอร์ในระบบหรือไม่
        $_SESSION['success'] = "หากมีข้อมูลบัญชีนี้ในระบบ เราได้ดำเนินการส่งคำแนะนำและลิงก์สำหรับตั้งรหัสผ่านใหม่ให้ท่านเรียบร้อยแล้ว (กรุณาทำรายการภายใน 30 นาที)";
        header("Location: ../forgot_password.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../forgot_password.php");
        exit();
    }
} else {
    header("Location: ../forgot_password.php");
    exit();
}
