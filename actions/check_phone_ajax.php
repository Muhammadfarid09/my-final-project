<?php
/**
 * T.S. Pattani Badminton - AJAX Phone Lookup Endpoint
 * ตรวจสอบความถูกต้องและการมีอยู่ของเบอร์โทรศัพท์สมาชิกสำหรับ Two-Step Login
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

require_once '../config/config.php';
require_once '../includes/functions.php';

$phone = trim($_POST['member_phone'] ?? '');

if (empty($phone)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกเบอร์โทรศัพท์']);
    exit();
}

$clean_phone = preg_replace('/[^0-9]/', '', $phone);
if (strlen($clean_phone) !== 10 || substr($clean_phone, 0, 1) !== '0') {
    echo json_encode(['status' => 'error', 'message' => 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง (ต้องเป็นตัวเลข 10 หลักขึ้นต้นด้วย 0)']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT member_id, member_name, member_phone, member_status FROM Member WHERE member_phone = :phone");
    $stmt->execute([':phone' => $clean_phone]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($member) {
        if ($member['member_status'] === 'ระงับสิทธิ์') {
            echo json_encode([
                'status' => 'error',
                'message' => 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ'
            ]);
            exit();
        }

        echo json_encode([
            'status' => 'success',
            'exists' => true,
            'member_name' => $member['member_name'],
            'member_phone' => $clean_phone,
            'masked_phone' => mask_phone_number($clean_phone)
        ]);
        exit();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่พบเบอร์โทรศัพท์นี้ในระบบ กรุณาตรวจสอบหรือสมัครสมาชิกใหม่'
        ]);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูล กรุณาลองใหม่อีกครั้ง'
    ]);
    exit();
}
