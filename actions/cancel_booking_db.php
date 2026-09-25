<?php
session_start();
require_once '../config/config.php';

// ตรวจสอบการเข้าสู่ระบบสมาชิก
if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.php");
    exit();
}

$member_id = intval($_SESSION['member_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $cancel_reason = trim($_POST['cancel_reason'] ?? '');

    if ($booking_id <= 0) {
        $_SESSION['error'] = "ไม่พบรหัสการจองที่ต้องการยกเลิก";
        header("Location: ../booking_history.php");
        exit();
    }

    if (empty($cancel_reason)) {
        $_SESSION['error'] = "กรุณาระบุเหตุผลในการขอยกเลิกการจอง";
        header("Location: ../booking_history.php");
        exit();
    }

    try {
        $conn->beginTransaction();

        // 1. ตรวจสอบข้อมูลการจองและสิทธิ์ความเป็นเจ้าของ
        $stmt_check = $conn->prepare("
            SELECT * FROM Booking 
            WHERE booking_id = :b_id AND member_id = :m_id
        ");
        $stmt_check->execute([':b_id' => $booking_id, ':m_id' => $member_id]);
        $booking = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception("ไม่พบข้อมูลการจองนี้ หรือคุณไม่มีสิทธิ์ยกเลิกรายการนี้");
        }

        if ($booking['booking_status'] === 'ยกเลิก') {
            throw new Exception("รายการจองนี้ถูกยกเลิกไปแล้ว");
        }

        // ตรวจสอบว่ายังไม่ถึงเวลาเริ่มใช้งานสนาม
        $booking_time = strtotime($booking['booking_date'] . ' ' . $booking['booking_start_time']);
        if ($booking_time <= time()) {
            throw new Exception("ไม่สามารถยกเลิกได้ เนื่องจากถึงเวลาหรือเลยเวลาเริ่มใช้งานสนามแล้ว");
        }

        // 2. คืนสต็อกอุปกรณ์เช่า (ถ้ามีการเช่าไว้)
        $stmt_rentals = $conn->prepare("
            SELECT product_id, rental_quantity 
            FROM Rental 
            WHERE booking_id = :b_id AND rental_status IN ('กำลังเช่า', 'รอตรวจสอบ')
        ");
        $stmt_rentals->execute([':b_id' => $booking_id]);
        $rentals = $stmt_rentals->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rentals as $rent) {
            $sql_restore = "UPDATE Product SET product_stock = product_stock + :qty WHERE product_id = :p_id";
            $conn->prepare($sql_restore)->execute([
                ':qty' => $rent['rental_quantity'],
                ':p_id' => $rent['product_id']
            ]);
        }

        // อัปเดตสถานะในตาราง Rental
        $sql_up_rent = "UPDATE Rental SET rental_status = 'คืนแล้ว' WHERE booking_id = :b_id";
        $conn->prepare($sql_up_rent)->execute([':b_id' => $booking_id]);

        // 3. อัปเดตสถานะการจองเป็น 'ยกเลิก'
        $sql_booking = "UPDATE Booking SET booking_status = 'ยกเลิก' WHERE booking_id = :b_id";
        $conn->prepare($sql_booking)->execute([':b_id' => $booking_id]);

        // 4. บันทึกประวัติคำขอยกเลิกลงตาราง Cancellation
        $sql_cancel = "
            INSERT INTO Cancellation 
            (booking_id, admin_id, cancel_reason, cancel_by, refund_status, refund_point, cancel_date)
            VALUES 
            (:b_id, NULL, :reason, 'สมาชิก', 'รอดำเนินการ', 0, NOW())
        ";
        $stmt_cancel = $conn->prepare($sql_cancel);
        $stmt_cancel->execute([
            ':b_id' => $booking_id,
            ':reason' => $cancel_reason
        ]);

        $conn->commit();
        $_SESSION['success'] = "ขอยกเลิกการจองเรียบร้อยแล้ว แอดมินจะตรวจสอบและพิจารณาคืนเงิน/พอยท์ตามเงื่อนไข";

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: ../booking_history.php");
    exit();

} else {
    header("Location: ../booking_history.php");
    exit();
}
?>
