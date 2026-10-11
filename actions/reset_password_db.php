<?php
session_start();
require_once '../config/config.php';
require_once '../includes/csrf.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. ตรวจสอบความปลอดภัย CSRF Token
    require_csrf_token();

    $raw_token = trim($_POST['token'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        if (empty($raw_token)) {
            throw new Exception("ไม่พบโทเค็นสำหรับการตั้งรหัสผ่านใหม่");
        }

        if (empty($new_password) || empty($confirm_password)) {
            throw new Exception("กรุณากรอกรหัสผ่านใหม่และยืนยันรหัสผ่าน");
        }

        if (strlen($new_password) < 6) {
            throw new Exception("รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร");
        }

        if ($new_password !== $confirm_password) {
            throw new Exception("รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน");
        }

        // 2. ตรวจสอบความถูกต้องและอายุของโทเค็นในฐานข้อมูล
        $token_hash = hash('sha256', $raw_token);

        $stmt = $conn->prepare("
            SELECT reset_id, member_id 
            FROM Password_Resets 
            WHERE token_hash = :hash 
              AND used = 0 
              AND expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute([':hash' => $token_hash]);
        $reset_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset_record) {
            throw new Exception("ลิงก์สำหรับตั้งรหัสผ่านใหม่หมดอายุแล้ว หรือถูกใช้งานไปแล้ว กรุณาส่งคำขอใหม่อีกครั้ง");
        }

        $member_id = intval($reset_record['member_id']);
        $reset_id = intval($reset_record['reset_id']);

        // 3. เริ่ม Transaction ปรับปรุงรหัสผ่านและปิดใช้งานโทเค็น
        $conn->beginTransaction();

        // เข้ารหัสรหัสผ่านใหม่ด้วย BCRYPT มาตรฐานสูง
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // อัปเดตรหัสผ่านสมาชิก
        $stmt_update = $conn->prepare("UPDATE Member SET member_password = :password WHERE member_id = :member_id");
        $stmt_update->execute([
            ':password' => $hashed_password,
            ':member_id' => $member_id
        ]);

        // มาร์กโทเค็นว่าถูกใช้งานแล้ว (Anti-Replay Attack)
        $stmt_used = $conn->prepare("UPDATE Password_Resets SET used = 1 WHERE reset_id = :reset_id");
        $stmt_used->execute([':reset_id' => $reset_id]);

        $conn->commit();

        $_SESSION['success'] = "ตั้งรหัสผ่านใหม่สำเร็จเรียบร้อยแล้ว! กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่ของท่าน";
        header("Location: ../login.php");
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        
        // หากโทเค็นยังส่งมาอยู่ ให้พากลับไปหน้า reset_password.php?token=... แต่ถ้าโทเค็นหายให้ไป forgot_password.php
        if (!empty($raw_token)) {
            header("Location: ../reset_password.php?token=" . urlencode($raw_token));
        } else {
            header("Location: ../forgot_password.php");
        }
        exit();
    }
} else {
    header("Location: ../forgot_password.php");
    exit();
}
