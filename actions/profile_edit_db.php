<?php
session_start();
require_once '../config/config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.php");
    exit();
}

$member_id = intval($_SESSION['member_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    // ==========================================
    // กรณีที่ 1: แก้ไขข้อมูลส่วนตัวทั่วไป
    // ==========================================
    if ($action === 'update_profile') {
        $member_name = trim($_POST['member_name'] ?? '');
        $member_phone = trim($_POST['member_phone'] ?? '');
        $member_gender = $_POST['member_gender'] ?? 'อื่นๆ';
        $member_age = !empty($_POST['member_age']) ? intval($_POST['member_age']) : null;
        $member_occupation = trim($_POST['member_occupation'] ?? '');

        try {
            if (empty($member_name) || empty($member_phone)) {
                throw new Exception("กรุณากรอกชื่อ-นามสกุล และเบอร์โทรศัพท์ให้ครบถ้วน");
            }

            if (!preg_match('/^[0-9]{10}$/', $member_phone)) {
                throw new Exception("รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง กรุณากรอกตัวเลข 10 หลัก");
            }

            if ($member_age !== null && ($member_age < 5 || $member_age > 120)) {
                throw new Exception("กรุณากรอกอายุให้ถูกต้อง (ระหว่าง 5 - 120 ปี)");
            }

            // ตรวจสอบเบอร์โทรซ้ำกับสมาชิกท่านอื่น
            $stmt_check = $conn->prepare("SELECT member_id FROM Member WHERE member_phone = :phone AND member_id != :mid");
            $stmt_check->execute([':phone' => $member_phone, ':mid' => $member_id]);
            if ($stmt_check->fetch()) {
                throw new Exception("เบอร์โทรศัพท์ {$member_phone} นี้ถูกใช้งานโดยบัญชีอื่นในระบบแล้ว");
            }

            // อัปเดตข้อมูลลงฐานข้อมูล
            $sql = "UPDATE Member 
                    SET member_name = :name, 
                        member_phone = :phone, 
                        member_gender = :gender, 
                        member_age = :age, 
                        member_occupation = :occupation 
                    WHERE member_id = :mid";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $member_name,
                ':phone' => $member_phone,
                ':gender' => $member_gender,
                ':age' => $member_age,
                ':occupation' => $member_occupation,
                ':mid' => $member_id
            ]);

            // อัปเดต Session
            $_SESSION['member_name'] = $member_name;
            $_SESSION['success'] = "บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว";

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: ../profile.php");
        exit();
    }

    // ==========================================
    // กรณีที่ 2: เปลี่ยนรหัสผ่าน
    // ==========================================
    if ($action === 'change_password') {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        try {
            if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("กรุณากรอกข้อมูลรหัสผ่านให้ครบทุกช่อง");
            }

            if (strlen($new_password) < 6) {
                throw new Exception("รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน");
            }

            // ดึงรหัสผ่านเดิมจากฐานข้อมูลมาตรวจสอบ
            $stmt_pwd = $conn->prepare("SELECT member_password FROM Member WHERE member_id = :mid");
            $stmt_pwd->execute([':mid' => $member_id]);
            $current_hash = $stmt_pwd->fetchColumn();

            if (!password_verify($old_password, $current_hash)) {
                throw new Exception("รหัสผ่านเดิมไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง");
            }

            // ตรวจสอบว่ารหัสผ่านใหม่ไม่ซ้ำกับรหัสผ่านเดิม
            if (password_verify($new_password, $current_hash)) {
                throw new Exception("รหัสผ่านใหม่ต้องไม่เหมือนกับรหัสผ่านเดิม");
            }

            // เข้ารหัสผ่านใหม่และอัปเดต
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE Member SET member_password = :hash WHERE member_id = :mid");
            $stmt_update->execute([':hash' => $new_hash, ':mid' => $member_id]);

            $_SESSION['success'] = "เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว";

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: ../profile.php");
        exit();
    }
}

// ถ้าเข้าถึงไฟล์ตรงๆ ให้ redirect กลับหน้าโปรไฟล์
header("Location: ../profile.php");
exit();
?>
