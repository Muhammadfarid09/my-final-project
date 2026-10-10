<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'รูปแบบคำขอไม่ถูกต้อง']);
    exit();
}

$member_phone = trim($_POST['member_phone'] ?? '');
$password = $_POST['member_password'] ?? '';

try {
    if (empty($member_phone) || empty($password)) {
        throw new Exception("กรุณากรอกเบอร์โทรศัพท์และรหัสผ่านให้ครบถ้วน");
    }

    if (!preg_match('/^[0-9]{10}$/', $member_phone)) {
        throw new Exception("เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก");
    }

    $stmt = $conn->prepare("SELECT * FROM Member WHERE member_phone = :phone");
    $stmt->execute([':phone' => $member_phone]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member || !password_verify($password, $member['member_password'])) {
        throw new Exception("เบอร์โทรศัพท์หรือรหัสผ่านไม่ถูกต้อง");
    }

    if ($member['member_status'] === 'ระงับสิทธิ์') {
        throw new Exception("บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ");
    }

    // สร้าง Session ข้อมูลสมาชิก
    $_SESSION['member_id'] = intval($member['member_id']);
    $_SESSION['member_name'] = $member['member_name'];
    $_SESSION['member_phone'] = $member['member_phone'];

    echo json_encode([
        'status' => 'success',
        'message' => 'เข้าสู่ระบบสำเร็จ',
        'member_id' => intval($member['member_id']),
        'member_name' => $member['member_name'],
        'member_phone' => $member['member_phone']
    ]);
    exit();

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
}
