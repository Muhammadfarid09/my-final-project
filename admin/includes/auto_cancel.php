<?php
// includes/auto_cancel.php
// ใช้สำหรับเคลียร์ Booking ที่หมดเวลาชำระเงิน (15 นาที) และยังไม่มีการอัปโหลดสลิป

if (!function_exists('trigger_auto_cancel')) {
    function trigger_auto_cancel(PDO $conn): int {
        $cancelled_count = 0;
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
                    $b_id = intval($b_id);

                    // 1. คืนสต็อกอุปกรณ์และยกเลิกสถานะ Booking / Rental ผ่านฟังก์ชันกลาง (DRY)
                    cancel_booking_and_restore_stock($conn, $b_id);

                    // 2. บันทึกประวัติลงตาราง Cancellation เพื่อความโปร่งใสตามข้อกำหนด 1.3.11
                    $check_existing = $conn->prepare("SELECT cancel_id FROM Cancellation WHERE booking_id = :id");
                    $check_existing->execute([':id' => $b_id]);
                    if (!$check_existing->fetch()) {
                        $stmt_log_cancel = $conn->prepare("
                            INSERT INTO Cancellation 
                            (booking_id, admin_id, cancel_reason, cancel_by, refund_status, refund_point, cancel_date)
                            VALUES 
                            (:id, NULL, 'ระบบยกเลิกอัตโนมัติเนื่องจากหมดเวลาชำระเงิน (15 นาที)', 'Admin', 'ไม่คืน', 0, NOW())
                        ");
                        $stmt_log_cancel->execute([':id' => $b_id]);
                    }
                    $cancelled_count++;
                }

                $conn->commit();
            }
        } catch(PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Auto-cancel error: " . $e->getMessage());
        }
        return $cancelled_count;
    }
}

// เรียกใช้งานทันทีเมื่อมีการ include ไฟล์นี้ (Backward Compatibility)
if (isset($conn)) {
    trigger_auto_cancel($conn);
}
?>
