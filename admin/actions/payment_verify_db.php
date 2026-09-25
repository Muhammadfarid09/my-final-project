<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // รับค่าจาก Modal
    $payment_id = intval($_POST['payment_id']);
    $booking_id = intval($_POST['booking_id']);
    $action_status = $_POST['action_status']; // 'ยืนยันแล้ว' หรือ 'ปฏิเสธ'
    $admin_id = $_SESSION['admin_id'];

    try {
        // เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล (ถ้าเกิด Error กลางคิว จะยกเลิกทั้งหมดอัตโนมัติ)
        $conn->beginTransaction();

        // 1. ดึงข้อมูลการชำระเงินและการจองที่เกี่ยวข้อง เพื่อเตรียมแยกลงตารางรายรับ
        $stmt_info = $conn->prepare("
            SELECT p.payment_status, b.booking_status, b.member_id, 
                   b.booking_court_price, b.booking_rental_price, 
                   b.booking_product_price, b.booking_total_price
            FROM Payment p
            JOIN Booking b ON p.booking_id = b.booking_id
            WHERE p.payment_id = :p_id
        ");
        $stmt_info->execute([':p_id' => $payment_id]);
        $data = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            throw new Exception("ไม่พบข้อมูลการชำระเงิน");
        }
        if ($data['payment_status'] != 'รอตรวจสอบ') {
            throw new Exception("รายการนี้ถูกตรวจสอบไปแล้ว ไม่สามารถทำรายการซ้ำได้");
        }

        // ============================================
        // กรณีที่ 1: ยืนยันสลิปถูกต้อง
        // ============================================
        if ($action_status == 'ยืนยันแล้ว') {
            
            // 2. อัปเดตสถานะในตาราง Payment
            $sql_pay = "UPDATE Payment 
                        SET payment_status = 'ยืนยันแล้ว', admin_id = :a_id, payment_verified_at = NOW() 
                        WHERE payment_id = :p_id";
            $conn->prepare($sql_pay)->execute([':a_id' => $admin_id, ':p_id' => $payment_id]);

            // 3. อัปเดตสถานะในตาราง Booking เป็น 'จองแล้ว'
            $sql_book = "UPDATE Booking SET booking_status = 'จองแล้ว' WHERE booking_id = :b_id";
            $conn->prepare($sql_book)->execute([':b_id' => $booking_id]);

            // อัปเดตสถานะ Rental สำหรับการจองนี้เป็น 'กำลังเช่า' (สำหรับอุปกรณ์เช่าที่รอตรวจสอบอยู่)
            $sql_rental_active = "UPDATE Rental SET rental_status = 'กำลังเช่า' WHERE booking_id = :b_id AND rental_status = 'รอตรวจสอบ'";
            $conn->prepare($sql_rental_active)->execute([':b_id' => $booking_id]);

            // 4. บันทึกรายรับลงตาราง Revenue (แยกหมวดหมู่ชัดเจน)
            $sql_rev = "INSERT INTO Revenue (payment_id, revenue_court_amount, revenue_rental_amount, revenue_product_amount, revenue_total_amount, revenue_date, revenue_type) 
                        VALUES (:p_id, :court, :rental, :product, :total, CURDATE(), 'ออนไลน์')";
            $conn->prepare($sql_rev)->execute([
                ':p_id' => $payment_id,
                ':court' => $data['booking_court_price'],
                ':rental' => $data['booking_rental_price'],
                ':product' => $data['booking_product_price'],
                ':total' => $data['booking_total_price']
            ]);

            // 5. ระบบแจกคะแนนสะสม (Point) อัตโนมัติ 
            // ทุกๆ 100 บาท จะได้ 1 พอยท์
            $earned_points = floor($data['booking_total_price'] / 100); 

            if ($earned_points > 0) {
                $member_id = $data['member_id'];

                // 5.1 อัปเดตตาราง Point (ถ้ามีข้อมูลอยู่แล้วให้บวกเพิ่ม ถ้ายังไม่มีให้ INSERT)
                $check_point = $conn->prepare("SELECT point_id FROM Point WHERE member_id = :m_id");
                $check_point->execute([':m_id' => $member_id]);
                if ($check_point->fetch()) {
                    $sql_point = "UPDATE Point SET point_balance = point_balance + :pts, point_total_earned = point_total_earned + :pts WHERE member_id = :m_id";
                    $conn->prepare($sql_point)->execute([':pts' => $earned_points, ':m_id' => $member_id]);
                } else {
                    $sql_point = "INSERT INTO Point (member_id, point_balance, point_total_earned, member_level) VALUES (:m_id, :pts, :pts, 'Bronze')";
                    $conn->prepare($sql_point)->execute([':m_id' => $member_id, ':pts' => $earned_points]);
                }

                // 5.2 บันทึกประวัติ Point_Transaction
                $sql_pt_trans = "INSERT INTO Point_Transaction (member_id, booking_id, transaction_type, transaction_point, transaction_note) 
                                 VALUES (:m_id, :b_id, 'ได้รับ', :pts, 'ได้รับพอยท์จากการจองสนามออนไลน์')";
                $conn->prepare($sql_pt_trans)->execute([
                    ':m_id' => $member_id,
                    ':b_id' => $booking_id,
                    ':pts' => $earned_points
                ]);

                // 5.3 ตรวจสอบและอัปเกรดระดับสมาชิก (Tier Progression): Bronze -> Silver (500 pts) -> Gold (1000 pts)
                $stmt_pts = $conn->prepare("SELECT point_total_earned FROM Point WHERE member_id = :m_id");
                $stmt_pts->execute([':m_id' => $member_id]);
                $total_earned = $stmt_pts->fetchColumn() ?: 0;

                $new_level = 'Bronze';
                if ($total_earned >= 1000) {
                    $new_level = 'Gold';
                } elseif ($total_earned >= 500) {
                    $new_level = 'Silver';
                }

                $conn->prepare("UPDATE Point SET member_level = :lvl WHERE member_id = :m_id")->execute([
                    ':lvl' => $new_level,
                    ':m_id' => $member_id
                ]);
            }

            $_SESSION['success'] = "ยืนยันการชำระเงิน สำเร็จ! และระบบได้อัปเดตรายรับพร้อมแจกคะแนนสะสมเรียบร้อยแล้ว";

        // ============================================
        // กรณีที่ 2: ปฏิเสธสลิปโอนเงิน (ยอดไม่ตรง/สลิปปลอม)
        // ============================================
        } elseif ($action_status == 'ปฏิเสธ') {
            
            // อัปเดตสถานะ Payment เป็นปฏิเสธ
            $sql_pay = "UPDATE Payment 
                        SET payment_status = 'ปฏิเสธ', admin_id = :a_id, payment_verified_at = NOW() 
                        WHERE payment_id = :p_id";
            $conn->prepare($sql_pay)->execute([':a_id' => $admin_id, ':p_id' => $payment_id]);

            // อัปเดตสถานะ Booking เป็น 'ยกเลิก'
            $sql_book = "UPDATE Booking SET booking_status = 'ยกเลิก' WHERE booking_id = :b_id";
            $conn->prepare($sql_book)->execute([':b_id' => $booking_id]);

            // คืนสต็อกอุปกรณ์/สินค้าที่ถูกจองไว้ล่วงหน้า
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

            // อัปเดตสถานะการเช่าอุปกรณ์เป็น 'ยกเลิก'
            $sql_up_rent = "UPDATE Rental SET rental_status = 'ยกเลิก' WHERE booking_id = :b_id";
            $conn->prepare($sql_up_rent)->execute([':b_id' => $booking_id]);

            $_SESSION['success'] = "ปฏิเสธสลิป คืนสต็อกสินค้า/อุปกรณ์ และยกเลิกการจองเรียบร้อยแล้ว";
        }

        // ยืนยันคำสั่งทั้งหมดลงฐานข้อมูล
        $conn->commit();

    } catch(Exception $e) {
        // หากเกิด Error จะยกเลิกการกระทำทั้งหมด (Rollback) ข้อมูลจะไม่เพี้ยน
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // เด้งกลับหน้าตรวจสอบการชำระเงิน
    header("Location: ../payments.php");
    exit();

} else {
    header("Location: ../payments.php");
    exit();
}
?>