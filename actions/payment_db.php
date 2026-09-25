<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_id = intval($_POST['booking_id']);
    $member_id = $_SESSION['member_id'];

    try {
        // 1. ตรวจสอบว่ามีการอัปโหลดไฟล์สลิปมาหรือไม่
        if (!isset($_FILES['payment_slip']) || $_FILES['payment_slip']['error'] == UPLOAD_ERR_NO_FILE) {
            throw new Exception("กรุณาแนบสลิปหลักฐานการโอนเงิน");
        }

        $file = $_FILES['payment_slip'];
        
        // ตรวจสอบขนาดไฟล์รูปภาพ (ไม่เกิน 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("ขนาดไฟล์รูปภาพสลิปต้องไม่เกิน 5MB");
        }
        
        // 2. ตรวจสอบนามสกุลและ MIME Type ไฟล์รูปภาพ
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception("รองรับเฉพาะไฟล์รูปภาพ (JPG, JPEG, PNG, WEBP) เท่านั้น");
        }
        
        // ตรวจสอบ MIME Type จริงๆ เพื่อป้องกันการปลอมแปลงนามสกุลไฟล์
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime_type, $allowed_mimes)) {
            throw new Exception("รูปแบบไฟล์ไม่ถูกต้อง หรือไฟล์ถูกดัดแปลงนามสกุล");
        }

        // 3. สร้างชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำ
        $new_filename = "slip_" . $booking_id . "_" . time() . "." . $file_extension;
        $upload_dir = "../uploads/slips/";

        // สร้างโฟลเดอร์ uploads/slips/ หากยังไม่มี
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $upload_path = $upload_dir . $new_filename;

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดรูปภาพสลิป");
        }

        // 4. ดึงข้อมูลยอดรวมการจอง
        $stmt_price = $conn->prepare("SELECT booking_total_price FROM Booking WHERE booking_id = :booking_id AND member_id = :member_id");
        $stmt_price->execute([':booking_id' => $booking_id, ':member_id' => $member_id]);
        $booking = $stmt_price->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception("ไม่พบข้อมูลการจอง");
        }

        $booking_total_price = $booking['booking_total_price'];

        // 5. บันทึกข้อมูลลงตาราง Payment
        $stmt_payment = $conn->prepare("INSERT INTO Payment (booking_id, payment_slip_image, payment_amount, payment_transfer_time, payment_status) 
                                        VALUES (:booking_id, :slip, :amount, NOW(), 'รอตรวจสอบ')");
        $stmt_payment->execute([
            ':booking_id' => $booking_id,
            ':slip' => $new_filename,
            ':amount' => $booking_total_price
        ]);

        // 6. อัปเดตตาราง Booking (เก็บชื่อสลิป)
        $stmt = $conn->prepare("UPDATE Booking 
                                SET payment_slip = :slip 
                                WHERE booking_id = :booking_id 
                                AND member_id = :member_id");
        
        $stmt->execute([
            ':slip' => $new_filename,
            ':booking_id' => $booking_id,
            ':member_id' => $member_id
        ]);

        // สำเร็จ พาไปหน้าประวัติการจองหรือหน้าแรก พร้อมข้อความแจ้งเตือน
        $_SESSION['success'] = "อัปโหลดสลิปสำเร็จ! รอผู้ดูแลระบบตรวจสอบและอนุมัติการจอง";
        header("Location: ../booking_history.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../payment.php?booking_id=" . $booking_id);
        exit();
    }
} else {
    header("Location: ../booking.php");
    exit();
}
?>