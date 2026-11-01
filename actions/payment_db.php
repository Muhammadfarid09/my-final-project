<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_csrf_token();
    $booking_id = intval($_POST['booking_id']);
    $member_id = $_SESSION['member_id'];

    try {
        // 1. ตรวจสอบและอัปโหลดไฟล์สลิปหลักฐานการโอนเงิน (DRY Helper)
        if (!isset($_FILES['payment_slip']) || $_FILES['payment_slip']['error'] == UPLOAD_ERR_NO_FILE) {
            throw new Exception("กรุณาแนบสลิปหลักฐานการโอนเงิน");
        }

        $new_filename = handle_secure_image_upload($_FILES['payment_slip'], "../uploads/slips/", 5);

        // 4. ดึงข้อมูลการจองและตรวจสอบสถานะ
        $stmt_price = $conn->prepare("SELECT booking_total_price, booking_status, booking_lock_expire FROM Booking WHERE booking_id = :booking_id AND member_id = :member_id");
        $stmt_price->execute([':booking_id' => $booking_id, ':member_id' => $member_id]);
        $booking = $stmt_price->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception("ไม่พบข้อมูลการจองนี้ในระบบ");
        }

        if ($booking['booking_status'] !== 'รอตรวจสอบ') {
            throw new Exception("รายการจองนี้ไม่อยู่ในสถานะรอชำระเงิน (อาจได้รับการยืนยันหรือยกเลิกไปแล้ว)");
        }

        if (!empty($booking['booking_lock_expire']) && strtotime($booking['booking_lock_expire']) < time()) {
            throw new Exception("หมดเวลาการชำระเงินสำหรับการจองนี้แล้ว (เกินกำหนด 15 นาที) กรุณาทำรายการจองใหม่");
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

        // ส่งการแจ้งเตือนสลิปใหม่เข้า Discord Webhook
        try {
            $stmt_notif = $conn->prepare("
                SELECT b.booking_date, b.booking_start_time, b.booking_end_time, c.court_name, m.member_name, m.member_phone
                FROM Booking b
                JOIN Court c ON b.court_id = c.court_id
                JOIN Member m ON b.member_id = m.member_id
                WHERE b.booking_id = :b_id
            ");
            $stmt_notif->execute([':b_id' => $booking_id]);
            $notif_data = $stmt_notif->fetch(PDO::FETCH_ASSOC);

            if ($notif_data) {
                $time_str = date('H:i', strtotime($notif_data['booking_start_time'])) . ' - ' . date('H:i', strtotime($notif_data['booking_end_time'])) . ' น.';
                send_discord_alert(
                    '🏸 [รายการจองใหม่] แนบสลิปโอนเงินรอตรวจสอบ!',
                    'มีลูกค้าทำรายการจองสนามและแนบสลิปหลักฐานการโอนเงินเข้ามาในระบบ กรุณาตรวจสอบและอนุมัติการจอง',
                    [
                        ['name' => '👤 ผู้จอง', 'value' => $notif_data['member_name'] . ' (' . $notif_data['member_phone'] . ')', 'inline' => true],
                        ['name' => '🏟️ สนาม', 'value' => $notif_data['court_name'], 'inline' => true],
                        ['name' => '📅 วันและเวลา', 'value' => date('d/m/Y', strtotime($notif_data['booking_date'])) . ' (' . $time_str . ')', 'inline' => false],
                        ['name' => '💰 ยอดเงินที่โอน', 'value' => number_format($booking_total_price, 2) . ' ฿', 'inline' => true],
                        ['name' => '🔗 รหัสการจอง', 'value' => '#' . $booking_id, 'inline' => true]
                    ],
                    16759069 // สีทอง Gold #FFAE1D
                );
            }
        } catch (Throwable $notif_err) {
            error_log("Discord notification error: " . $notif_err->getMessage());
        }
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