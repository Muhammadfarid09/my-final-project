<?php
// includes/auto_cancel.php
// ใช้สำหรับเคลียร์ Booking ที่หมดเวลาชำระเงิน (15 นาที) และยังไม่มีการอัปโหลดสลิป

try {
    // หา Booking ที่หมดเวลา
    $stmt = $conn->prepare("SELECT booking_id FROM Booking 
                            WHERE booking_status = 'รอตรวจสอบ' 
                            AND payment_slip IS NULL 
                            AND booking_lock_expire IS NOT NULL 
                            AND booking_lock_expire < NOW()");
    $stmt->execute();
    $expired_bookings = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($expired_bookings)) {
        $conn->beginTransaction();

        foreach ($expired_bookings as $b_id) {
            // 1. เปลี่ยนสถานะ Booking เป็นยกเลิก
            $stmt_cancel = $conn->prepare("UPDATE Booking SET booking_status = 'ยกเลิก' WHERE booking_id = :id");
            $stmt_cancel->execute([':id' => $b_id]);

            // 2. ดึงรายการ Rental ของ Booking นี้เพื่อคืน Stock
            $stmt_rent = $conn->prepare("SELECT product_id, rental_quantity FROM Rental WHERE booking_id = :id AND rental_status != 'ยกเลิก'");
            $stmt_rent->execute([':id' => $b_id]);
            $rentals = $stmt_rent->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rentals as $rent) {
                // คืน Stock
                $stmt_stock = $conn->prepare("UPDATE Product SET product_stock = product_stock + :qty WHERE product_id = :p_id");
                $stmt_stock->execute([
                    ':qty' => $rent['rental_quantity'],
                    ':p_id' => $rent['product_id']
                ]);
            }

            // 3. อัปเดตสถานะ Rental เป็นยกเลิก
            $stmt_cancel_rent = $conn->prepare("UPDATE Rental SET rental_status = 'ยกเลิก' WHERE booking_id = :id");
            $stmt_cancel_rent->execute([':id' => $b_id]);
        }

        $conn->commit();
    }
} catch(PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // เงียบข้อผิดพลาดไว้ไม่ให้หน้าพัง แต่ในระบบจริงควร log ไว้
    error_log("Auto-cancel error: " . $e->getMessage());
}
?>
