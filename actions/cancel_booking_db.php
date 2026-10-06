<?php
session_start();
// เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล
require_once '../config/config.php';

// ตรวจสอบการเข้าสู่ระบบสมาชิก
if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.php");
    exit();
}

$member_id = intval($_SESSION['member_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. ตรวจสอบ CSRF Token
    require_csrf_token();

    $booking_id = intval($_POST['booking_id'] ?? 0);
    $cancel_reason = trim($_POST['cancel_reason'] ?? '');
    $refund_bank = trim($_POST['refund_bank'] ?? '');
    $refund_account_no = trim($_POST['refund_account_no'] ?? '');
    $refund_account_name = trim($_POST['refund_account_name'] ?? '');

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
            FOR UPDATE
        ");
        $stmt_check->execute([':b_id' => $booking_id, ':m_id' => $member_id]);
        $booking = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception("ไม่พบข้อมูลการจองนี้ หรือคุณไม่มีสิทธิ์ดำเนินการกับรายการนี้");
        }

        if ($booking['booking_status'] === 'ยกเลิก') {
            throw new Exception("รายการจองนี้ถูกยกเลิกไปแล้ว");
        }

        // ตรวจสอบว่ารายการจองยังไม่สิ้นสุดเวลาใช้งาน
        $booking_end_ts = strtotime($booking['booking_date'] . ' ' . $booking['booking_end_time']);
        if ($booking_end_ts <= time()) {
            throw new Exception("ไม่สามารถยกเลิกได้ เนื่องจากรายการจองนี้ได้เสร็จสิ้นการใช้บริการไปแล้ว");
        }

        // ตรวจสอบว่าเคยส่งคำขอยกเลิกที่ยังค้างพิจารณาอยู่หรือไม่
        $stmt_existing = $conn->prepare("
            SELECT cancel_id FROM Cancellation 
            WHERE booking_id = :b_id AND refund_status = 'รอดำเนินการ'
        ");
        $stmt_existing->execute([':b_id' => $booking_id]);
        if ($stmt_existing->fetch()) {
            throw new Exception("คุณได้ส่งคำขอยกเลิกรายการนี้ไปแล้ว กำลังรอผู้ดูแลระบบตรวจสอบและพิจารณา");
        }

        $booking_status = $booking['booking_status'];
        $has_slip = !empty($booking['payment_slip']);

        // ========================================================
        // กรณีที่ 1: สถานะ "รอตรวจสอบ" (ยังไม่ได้รับการอนุมัติ)
        // ========================================================
        if ($booking_status === 'รอตรวจสอบ') {
            // คืนสต็อกอุปกรณ์เช่า (ถ้ามี)
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

            // อัปเดตสถานะในตาราง Rental เป็น 'ยกเลิก'
            $conn->prepare("UPDATE Rental SET rental_status = 'ยกเลิก' WHERE booking_id = :b_id")
                 ->execute([':b_id' => $booking_id]);

            // อัปเดตสถานะการจองเป็น 'ยกเลิก' เพื่อปล่อย Slot สนามว่างทันที
            $conn->prepare("UPDATE Booking SET booking_status = 'ยกเลิก' WHERE booking_id = :b_id")
                 ->execute([':b_id' => $booking_id]);

            // 1.1 กรณียังไม่ได้แนบสลิป (ลูกค้ายังไม่ได้จ่ายเงิน) -> ยกเลิกทันที ไม่ต้องคืนเงิน
            if (!$has_slip) {
                $sql_cancel = "
                    INSERT INTO Cancellation 
                    (booking_id, admin_id, cancel_reason, cancel_by, refund_status, refund_bank, refund_account_no, refund_account_name, refund_point, cancel_date)
                    VALUES 
                    (:b_id, NULL, :reason, 'สมาชิก', 'ไม่คืน', NULL, NULL, NULL, 0, NOW())
                ";
                $conn->prepare($sql_cancel)->execute([
                    ':b_id' => $booking_id,
                    ':reason' => $cancel_reason . ' (ยกเลิกก่อนชำระเงิน)'
                ]);

                $conn->commit();
                $_SESSION['success'] = "ยกเลิกรายการจองเรียบร้อยแล้ว ระบบได้ปลดล็อกสนามและคืนอุปกรณ์เรียบร้อย";

            // 1.2 กรณีแนบสลิปแล้ว (ลูกค้าจ่ายเงินแล้ว แต่รอตรวจ) -> ต้องส่งคำขอให้แอดมินตรวจสลิปและโอนเงินคืน 100%
            } else {
                if (empty($refund_account_no) || empty($refund_account_name)) {
                    throw new Exception("กรุณาระบุเลขที่บัญชีและชื่อบัญชีสำหรับรับเงินโอนคืนให้ครบถ้วน");
                }

                $sql_cancel = "
                    INSERT INTO Cancellation 
                    (booking_id, admin_id, cancel_reason, cancel_by, refund_status, refund_bank, refund_account_no, refund_account_name, refund_point, cancel_date)
                    VALUES 
                    (:b_id, NULL, :reason, 'สมาชิก', 'รอดำเนินการ', :r_bank, :r_acc, :r_name, 0, NOW())
                ";
                $conn->prepare($sql_cancel)->execute([
                    ':b_id' => $booking_id,
                    ':reason' => $cancel_reason . ' (ลูกค้ายกเลิกหลังแนบสลิปชำระเงิน - รอตรวจสลิปและโอนเงินคืน 100%)',
                    ':r_bank' => $refund_bank ?: 'พร้อมเพย์ (PromptPay)',
                    ':r_acc' => $refund_account_no,
                    ':r_name' => $refund_account_name
                ]);

                $conn->commit();
                $_SESSION['success'] = "ส่งคำขอยกเลิกเรียบร้อยแล้ว เนื่องจากท่านได้แนบสลิปชำระเงินไว้ ผู้ดูแลระบบจะตรวจสอบสลิปและดำเนินการโอนเงินคืน 100% ตามบัญชีที่ท่านระบุ";
            }

        // ========================================================
        // กรณีที่ 2: สถานะ "จองแล้ว" (ได้รับการตรวจสอบและอนุมัติการชำระเงินแล้ว)
        // สมาชิกส่งคำร้องขอให้ Admin พิจารณาโอนเงินคืนตามเงื่อนไขเวลา
        // ========================================================
        } elseif ($booking_status === 'จองแล้ว') {
            // ตรวจสอบความถูกต้องว่ามีการยืนยันชำระเงินใน Payment จริง
            $stmt_pay_check = $conn->prepare("SELECT payment_status FROM Payment WHERE booking_id = :b_id");
            $stmt_pay_check->execute([':b_id' => $booking_id]);
            $pay_check = $stmt_pay_check->fetch(PDO::FETCH_ASSOC);

            if (!$pay_check || $pay_check['payment_status'] !== 'ยืนยันแล้ว') {
                throw new Exception("ไม่สามารถส่งคำขอยกเลิกได้ เนื่องจากรายการนี้ยังไม่ได้รับการยืนยันการชำระเงินที่สมบูรณ์");
            }

            if (empty($refund_account_no) || empty($refund_account_name)) {
                throw new Exception("กรุณาระบุเลขที่บัญชีและชื่อบัญชีสำหรับรับเงินโอนคืนให้ครบถ้วน");
            }

            // ส่งคำร้องขอเข้าสู่ตาราง Cancellation ให้ Admin พิจารณา
            $sql_cancel = "
                INSERT INTO Cancellation 
                (booking_id, admin_id, cancel_reason, cancel_by, refund_status, refund_bank, refund_account_no, refund_account_name, refund_point, cancel_date)
                VALUES 
                (:b_id, NULL, :reason, 'สมาชิก', 'รอดำเนินการ', :r_bank, :r_acc, :r_name, 0, NOW())
            ";
            $conn->prepare($sql_cancel)->execute([
                ':b_id' => $booking_id,
                ':reason' => $cancel_reason,
                ':r_bank' => $refund_bank ?: 'พร้อมเพย์ (PromptPay)',
                ':r_acc' => $refund_account_no,
                ':r_name' => $refund_account_name
            ]);

            $conn->commit();
            $_SESSION['success'] = "ส่งคำขอยกเลิกการจองเรียบร้อยแล้ว! รายการของคุณอยู่ในคิวพิจารณาของผู้ดูแลระบบ (แอดมินจะพิจารณาโอนเงินคืนตามเงื่อนไขเวลา)";
        } else {
            throw new Exception("สถานะการจองไม่ถูกต้อง ไม่สามารถดำเนินการได้");
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: ../booking_history.php");
    exit();

} else {
    header("Location: ../booking_history.php");
    exit();
}
?>
