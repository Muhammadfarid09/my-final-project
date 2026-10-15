<?php
/**
 * T.S. Pattani Badminton - Secure Payment Slip View Handler
 * ทำหน้าที่เป็นเกตเวย์ตัวกลางในการตรวจสอบสิทธิ์ (RBAC & Ownership) ก่อนส่งรูปสลิป
 * ป้องกันการเข้าถึงโดยตรง (Direct Access) และช่องโหว่ IDOR / Path Traversal
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

// 1. ตรวจสอบการยืนยันตัวตน (Authentication Check)
$is_admin = isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
$is_member = isset($_SESSION['member_id']) && !empty($_SESSION['member_id']);

if (!$is_admin && !$is_member) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden: กรุณาเข้าสู่ระบบก่อนเปิดดูเอกสารหลักฐานการโอนเงิน');
}

// 2. รับและทำความสะอาดพารามิเตอร์ (Parameter Sanitization & Path Traversal Defense)
$filename = '';
$raw_file = trim($_GET['file'] ?? '');
$booking_id = intval($_GET['booking_id'] ?? 0);

if (!empty($raw_file)) {
    // กำจัด Directory Traversal Sequence (เช่น ../ หรือ /)
    $filename = basename($raw_file);
    
    // ตรวจสอบว่าชื่อไฟล์ประกอบด้วยอักขระที่ปลอดภัยเท่านั้น
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('400 Bad Request: รูปแบบชื่อไฟล์ไม่ถูกต้อง');
    }
} elseif ($booking_id > 0) {
    // กรณีส่งเป็น booking_id มา ค้นหาชื่อสลิปจากฐานข้อมูล
    if ($is_admin) {
        $stmt_bk = $conn->prepare("
            SELECT COALESCE(p.payment_slip_image, b.payment_slip) AS slip_file
            FROM Booking b
            LEFT JOIN Payment p ON b.booking_id = p.booking_id
            WHERE b.booking_id = :bid AND (p.payment_slip_image IS NOT NULL OR b.payment_slip IS NOT NULL)
            ORDER BY p.payment_id DESC LIMIT 1
        ");
        $stmt_bk->execute([':bid' => $booking_id]);
    } else {
        $stmt_bk = $conn->prepare("
            SELECT COALESCE(p.payment_slip_image, b.payment_slip) AS slip_file
            FROM Booking b
            LEFT JOIN Payment p ON b.booking_id = p.booking_id
            WHERE b.booking_id = :bid AND b.member_id = :mid AND (p.payment_slip_image IS NOT NULL OR b.payment_slip IS NOT NULL)
            ORDER BY p.payment_id DESC LIMIT 1
        ");
        $stmt_bk->execute([':bid' => $booking_id, ':mid' => $_SESSION['member_id']]);
    }
    $filename = $stmt_bk->fetchColumn() ?: '';
}

if (empty($filename)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('404 Not Found: ไม่พบข้อมูลสลิปที่ระบุ');
}

// 3. ตรวจสอบสิทธิ์การเข้าถึง (Authorization & Ownership Check)
// หากเป็น Admin: มีสิทธิ์ดูได้ทุกรายการ
// หากเป็น Member: ตรวจสอบว่าสลิปนี้ผูกอยู่กับรายการจองของตนเองจริงหรือไม่ (IDOR Defense)
if ($is_member && !$is_admin) {
    $stmt_auth = $conn->prepare("
        SELECT 1 
        FROM Booking b 
        LEFT JOIN Payment p ON b.booking_id = p.booking_id 
        WHERE b.member_id = :mid 
          AND (b.payment_slip = :file OR p.payment_slip_image = :file)
        LIMIT 1
    ");
    $stmt_auth->execute([
        ':mid' => $_SESSION['member_id'],
        ':file' => $filename
    ]);
    
    if (!$stmt_auth->fetchColumn()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('403 Forbidden: คุณไม่มีสิทธิ์เข้าถึงเอกสารสลิปนี้');
    }
}

// 4. ตรวจสอบความถูกต้องของพาธไฟล์บนเซิร์ฟเวอร์ (Realpath Defense)
$slips_dir = realpath(__DIR__ . '/uploads/slips');
if ($slips_dir === false) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('500 Internal Server Error: ไดเรกทอรีสลิปไม่ถูกต้อง');
}

$filepath = realpath($slips_dir . DIRECTORY_SEPARATOR . $filename);

// ยืนยันว่าไฟล์ต้องอยู่ในโฟลเดอร์ uploads/slips จริงๆ และเป็นไฟล์ที่เปิดได้
if ($filepath === false || strpos($filepath, $slips_dir) !== 0 || !is_file($filepath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('404 Not Found: ไม่พบไฟล์สลิปบนเซิร์ฟเวอร์');
}

// 5. ตรวจสอบ MIME Type ป้องกันการส่งไฟล์ประเภทอื่น (Content-Type Verification)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $filepath);
finfo_close($finfo);

$allowed_mimes = [
    'image/jpeg' => 'jpeg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif'
];

if (!isset($allowed_mimes[$mime_type])) {
    http_response_code(415);
    header('Content-Type: text/plain; charset=utf-8');
    exit('415 Unsupported Media Type: ไฟล์ไม่ใช่รูปภาพที่รองรับ');
}

// 6. ส่งรูปภาพพร้อม Header ที่ปลอดภัย (Secure Image Streaming)
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($filepath));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
header('Cache-Control: private, max-age=86400');

readfile($filepath);
exit();
