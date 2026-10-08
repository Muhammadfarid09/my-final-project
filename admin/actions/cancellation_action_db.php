<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. ตรวจสอบ CSRF Token
    require_csrf_token();

    $admin_id = intval($_SESSION['admin_id']);
    $cancel_id = intval($_POST['cancel_id'] ?? 0);
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $refund_status = trim($_POST['refund_status'] ?? '');
    $refund_point = isset($_POST['refund_point']) ? intval($_POST['refund_point']) : 0;
    $refund_amount = isset($_POST['refund_amount']) ? floatval($_POST['refund_amount']) : 0.00;

    if ($cancel_id <= 0 || $booking_id <= 0 || empty($refund_status)) {
        $_SESSION['error'] = "ข้อมูลคำขอไม่ครบถ้วน";
        header("Location: ../cancellations.php");
        exit();
    }

    try {
        $conn->beginTransaction();

        // 2. ดึงข้อมูลคำขอยกเลิกและการจองที่เกี่ยวข้อง
        $stmt_info = $conn->prepare("
            SELECT c.*, b.booking_status, b.member_id, b.booking_court_price, 
                   b.booking_rental_price, b.booking_product_price, b.booking_total_price,
                   p.payment_status, p.payment_id, p.payment_slip_image
            FROM Cancellation c
            JOIN Booking b ON c.booking_id = b.booking_id
            LEFT JOIN Payment p ON b.booking_id = p.booking_id
            WHERE c.cancel_id = :c_id
            FOR UPDATE
        ");
        $stmt_info->execute([':c_id' => $cancel_id]);
        $c_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$c_info) {
            throw new Exception("ไม่พบข้อมูลคำขอยกเลิกนี้ในระบบ");
        }

        if ($c_info['refund_status'] !== 'รอดำเนินการ') {
            throw new Exception("คำขอนี้ได้รับการพิจารณาไปแล้ว ไม่สามารถดำเนินการซ้ำได้");
        }

        // ตรวจสอบความถูกต้องก่อนอนุญาตให้คืนเงิน
        $is_confirmed_payment = ($c_info['payment_status'] === 'ยืนยันแล้ว');
        $has_slip = (!empty($c_info['payment_slip_image']));

        if ($refund_status === 'คืนแล้ว' && !$is_confirmed_payment && !$has_slip) {
            throw new Exception("ไม่สามารถบันทึกคืนเงินได้ เนื่องจากรายการนี้ไม่มีหลักฐานการชำระเงินหรือสลิปในระบบ");
        }

        $member_id = intval($c_info['member_id']);

        // 3. อัปเดตสถานะในตาราง Cancellation
        $sql_update = "
            UPDATE Cancellation 
            SET refund_status = :r_status, 
                refund_point = :r_point, 
                admin_id = :admin 
            WHERE cancel_id = :c_id
        ";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([
            ':r_status' => $refund_status,
            ':r_point' => $refund_point,
            ':admin' => $admin_id,
            ':c_id' => $cancel_id
        ]);

        // 4. อัปเดตสถานะ Booking เป็น 'ยกเลิก' (ปลดล็อกสนามให้ว่างสำหรับผู้อื่น)
        $conn->prepare("UPDATE Booking SET booking_status = 'ยกเลิก' WHERE booking_id = :b_id")
             ->execute([':b_id' => $booking_id]);

        // 5. คืนสต็อกอุปกรณ์เช่ากลับเข้าสู่คลังสินค้า (ถ้ามีและยังค้างอยู่ในสถานะกำลังเช่า/รอตรวจสอบ)
        $stmt_rentals = $conn->prepare("
            SELECT product_id, rental_quantity 
            FROM Rental 
            WHERE booking_id = :b_id AND rental_status IN ('กำลังเช่า', 'รอตรวจสอบ')
        ");
        $stmt_rentals->execute([':b_id' => $booking_id]);
        $rentals = $stmt_rentals->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rentals as $rent) {
            $conn->prepare("UPDATE Product SET product_stock = product_stock + :qty WHERE product_id = :p_id")
                 ->execute([
                     ':qty' => $rent['rental_quantity'],
                     ':p_id' => $rent['product_id']
                 ]);
        }

        // อัปเดตตาราง Rental เป็น 'ยกเลิก'
        $conn->prepare("UPDATE Rental SET rental_status = 'ยกเลิก' WHERE booking_id = :b_id AND rental_status != 'ยกเลิก'")
             ->execute([':b_id' => $booking_id]);

        // 6. จัดการสถานะใน Payment (กรณีสลิปยังรอตรวจสอบ) เพื่อบันทึกประวัติการตรวจสอบของแอดมิน
        if (!empty($c_info['payment_id']) && $c_info['payment_status'] === 'รอตรวจสอบ') {
            if ($refund_status === 'คืนแล้ว') {
                // สลิปถูกต้องและโอนเงินคืนแล้ว -> ปรับเป็น 'ยืนยันแล้ว' เพื่อเป็นหลักฐานว่าสลิปได้รับการตรวจรับรอง
                $conn->prepare("
                    UPDATE Payment 
                    SET payment_status = 'ยืนยันแล้ว', admin_id = :admin, payment_verified_at = NOW() 
                    WHERE payment_id = :p_id
                ")->execute([':admin' => $admin_id, ':p_id' => $c_info['payment_id']]);
            } else {
                // ปฏิเสธการคืนเงิน (สลิปปลอมหรือไม่มียอดโอนจริง)
                $conn->prepare("
                    UPDATE Payment 
                    SET payment_status = 'ปฏิเสธ', admin_id = :admin, payment_verified_at = NOW() 
                    WHERE payment_id = :p_id
                ")->execute([':admin' => $admin_id, ':p_id' => $c_info['payment_id']]);
            }
        }

        // 7. การจัดการคะแนนสะสม (Loyalty Points)
        // 7.1 ถ้าแอดมินระบุให้คืนพอยท์ชดเชย
        if ($refund_point > 0 && $member_id > 0) {
            $conn->prepare("UPDATE Point SET point_balance = point_balance + :points WHERE member_id = :m_id")
                 ->execute([':points' => $refund_point, ':m_id' => $member_id]);

            $conn->prepare("
                INSERT INTO Point_Transaction (member_id, booking_id, transaction_type, transaction_point, transaction_note, transaction_date) 
                VALUES (:m_id, :b_id, 'ปรับโดย Admin', :points, 'คืนพอยท์ชดเชยจากการยกเลิกการจองสนาม', NOW())
            ")->execute([
                ':m_id' => $member_id,
                ':b_id' => $booking_id,
                ':points' => $refund_point
            ]);
        }

        // 7.2 ดึงคะแนนสะสมที่เคยแจกไปจากบิลนี้คืน (เฉพาะกรณีที่เดิมสถานะเป็น 'จองแล้ว' ซึ่งเคยแจกแต้มตอนยืนยันชำระเงิน)
        if ($c_info['booking_status'] === 'จองแล้ว' && $member_id > 0) {
            $orig_earned_points = floor(floatval($c_info['booking_total_price']) / 100);
            if ($orig_earned_points > 0) {
                $conn->prepare("
                    UPDATE Point 
                    SET point_balance = GREATEST(0, point_balance - :pts),
                        point_total_earned = GREATEST(0, point_total_earned - :pts)
                    WHERE member_id = :m_id
                ")->execute([':pts' => $orig_earned_points, ':m_id' => $member_id]);

                $conn->prepare("
                    INSERT INTO Point_Transaction (member_id, booking_id, transaction_type, transaction_point, transaction_note, transaction_date) 
                    VALUES (:m_id, :b_id, 'ปรับโดย Admin', :pts, 'ดึงคะแนนคืนเนื่องจากยกเลิกรายการจอง', NOW())
                ")->execute([
                    ':m_id' => $member_id,
                    ':b_id' => $booking_id,
                    ':pts' => -$orig_earned_points
                ]);
            }
        }

        // 8. การจัดการรายรับ (Revenue Reversal) กรณีที่มีการโอนเงินคืนลูกค้า
        // ข้อควรระวัง: บันทึกยอดติดลบใน Revenue เฉพาะกรณีที่รายการนี้เคยถูกบันทึกรายรับเข้าระบบมาก่อนเท่านั้น (คือ booking_status เดิมคือ 'จองแล้ว')
        // หากเป็นการยกเลิกรายการที่ยังไม่เคยยืนยัน (สถานะเดิมคือ 'รอตรวจสอบ') ยอดเงินไม่เคยเข้า Revenue จึงต้องไม่บันทึกติดลบซ้ำ เพื่อป้องกันยอดรายรับติดลบผิดพลาด
        if ($refund_status === 'คืนแล้ว' && $refund_amount > 0 && $c_info['booking_status'] === 'จองแล้ว') {
            $pay_id = !empty($c_info['payment_id']) ? intval($c_info['payment_id']) : null;
            // บันทึกรายการคืนเงินเป็นยอดติดลบใน Revenue เพื่อให้ยอดสุทธิในบัญชีถูกต้อง
            $sql_rev = "
                INSERT INTO Revenue 
                (payment_id, revenue_court_amount, revenue_rental_amount, revenue_product_amount, revenue_total_amount, revenue_date, revenue_type) 
                VALUES (:p_id, 0.00, 0.00, 0.00, :neg_total, CURDATE(), 'ออนไลน์')
            ";
            $conn->prepare($sql_rev)->execute([
                ':p_id' => $pay_id,
                ':neg_total' => -$refund_amount
            ]);
        }

        $conn->commit();
        $_SESSION['success'] = "บันทึกผลการพิจารณาเรียบร้อยแล้ว! (ปลดล็อกสนาม คืนสต็อกอุปกรณ์ และปรับปรุงยอดบัญชีโอนคืนเรียบร้อย)";

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
    }

    header("Location: ../cancellations.php");
    exit();

} else {
    header("Location: ../cancellations.php");
    exit();
}
?>