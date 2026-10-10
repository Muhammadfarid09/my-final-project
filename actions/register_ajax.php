<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'รูปแบบคำขอไม่ถูกต้อง']);
    exit();
}

$member_name = trim($_POST['member_name'] ?? '');
$member_phone = trim($_POST['member_phone'] ?? '');
$password = $_POST['member_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$member_gender = $_POST['member_gender'] ?? 'อื่นๆ';
$member_age = !empty($_POST['member_age']) ? intval($_POST['member_age']) : null;
$member_occupation = trim($_POST['member_occupation'] ?? '');

try {
    if (empty($member_name) || empty($member_phone) || empty($password)) {
        throw new Exception("กรุณากรอกชื่อ-นามสกุล, เบอร์โทรศัพท์ และรหัสผ่านให้ครบถ้วน");
    }

    if (!preg_match('/^[0-9]{10}$/', $member_phone)) {
        throw new Exception("เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก");
    }

    if ($password !== $confirm_password) {
        throw new Exception("รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน");
    }

    if (mb_strlen($password, 'UTF-8') < 4) {
        throw new Exception("รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร");
    }

    // ตรวจสอบเบอร์โทรซ้ำในระบบ
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM Member WHERE member_phone = :phone");
    $stmt_check->execute([':phone' => $member_phone]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("เบอร์โทรศัพท์นี้ถูกใช้งานในระบบแล้ว กรุณาเข้าสู่ระบบด้วยเบอร์นี้");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $conn->beginTransaction();

    // บันทึกลงตาราง Member
    $sql_member = "INSERT INTO Member (member_name, member_phone, member_gender, member_age, member_occupation, member_password, member_status) 
                   VALUES (:name, :phone, :gender, :age, :occupation, :password, 'ปกติ')";
    $stmt_member = $conn->prepare($sql_member);
    $stmt_member->execute([
        ':name' => $member_name,
        ':phone' => $member_phone,
        ':gender' => $member_gender,
        ':age' => $member_age,
        ':occupation' => $member_occupation,
        ':password' => $hashed_password
    ]);

    $new_member_id = intval($conn->lastInsertId());

    // เปิดบัญชี Point ให้ลูกค้าอัตโนมัติ (0 พอยท์ ระดับ Bronze)
    $sql_point = "INSERT INTO Point (member_id, point_balance, point_total_earned, member_level) 
                  VALUES (:member_id, 0, 0, 'Bronze')";
    $stmt_point = $conn->prepare($sql_point);
    $stmt_point->execute([':member_id' => $new_member_id]);

    $conn->commit();

    // บันทึก Session สมาชิกอัตโนมัติทันที
    $_SESSION['member_id'] = $new_member_id;
    $_SESSION['member_name'] = $member_name;
    $_SESSION['member_phone'] = $member_phone;

    echo json_encode([
        'status' => 'success',
        'message' => 'สมัครสมาชิกและเข้าสู่ระบบสำเร็จ',
        'member_id' => $new_member_id,
        'member_name' => $member_name,
        'member_phone' => $member_phone
    ]);
    exit();

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
}
