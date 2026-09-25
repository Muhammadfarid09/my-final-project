<?php
session_start();
require_once '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = trim($_POST['member_phone'] ?? '');
    $name = trim($_POST['member_name'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        if (empty($phone) || empty($name) || empty($new_password) || empty($confirm_password)) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน");
        }

        if ($new_password !== $confirm_password) {
            throw new Exception("รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน");
        }

        if (strlen($new_password) < 6) {
            throw new Exception("รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร");
        }

        // ตรวจสอบว่ามีข้อมูลผู้ใช้ที่ตรงกับเบอร์โทรและชื่อหรือไม่
        $stmt = $conn->prepare("SELECT member_id FROM Member WHERE member_phone = :phone AND member_name = :name");
        $stmt->execute([':phone' => $phone, ':name' => $name]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($member) {
            // เข้ารหัสรหัสผ่านใหม่
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // อัปเดตรหัสผ่าน
            $stmt_update = $conn->prepare("UPDATE Member SET member_password = :password WHERE member_id = :id");
            $stmt_update->execute([
                ':password' => $hashed_password,
                ':id' => $member['member_id']
            ]);

            $_SESSION['success'] = "รีเซ็ตรหัสผ่านสำเร็จ! กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่";
            header("Location: ../login.php");
            exit();
        } else {
            throw new Exception("ไม่พบข้อมูลสมาชิก หรือชื่อ-นามสกุลไม่ตรงกับที่ลงทะเบียนไว้");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../forgot_password.php");
        exit();
    }
} else {
    header("Location: ../forgot_password.php");
    exit();
}
?>
