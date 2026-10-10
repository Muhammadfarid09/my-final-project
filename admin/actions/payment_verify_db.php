<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_csrf_token();
    
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
        if ($data['booking_status'] === 'ยกเลิก') {
            throw new Exception("รายการจองนี้ถูกยกเลิกแล้ว กรุณาไปตรวจสอบและพิจารณาคืนเงินที่เมนู 'จัดการการยกเลิก'");
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
            // 5. การสะสมคะแนน (มาตรฐานเดียวกัน: 1 คะแนน ต่อ 100 บาท) และอัปเกรดระดับสมาชิก
            if (!empty($data['member_id']) && floatval($data['booking_total_price']) >= 100) {
                award_member_points($conn, intval($data['member_id']), floatval($data['booking_total_price']), $booking_id, 'ได้รับพอยท์จากการจองสนามออนไลน์');
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