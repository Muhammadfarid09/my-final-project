<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $admin_id = intval($_SESSION['admin_id']);
    $cancel_id = intval($_POST['cancel_id']);
    $booking_id = intval($_POST['booking_id']);
    $refund_status = $_POST['refund_status'];
    $refund_point = isset($_POST['refund_point']) ? intval($_POST['refund_point']) : 0;

    try {
        $conn->beginTransaction();

        // 1. อัปเดตสถานะในตาราง CANCELLATION
        $sql_update = "UPDATE CANCELLATION 
                       SET refund_status = :r_status, 
                           refund_point = :r_point, 
                           admin_id = :admin 
                       WHERE cancel_id = :c_id";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([
            ':r_status' => $refund_status,
            ':r_point' => $refund_point,
            ':admin' => $admin_id,
            ':c_id' => $cancel_id
        ]);

        // 2. ถ้ามีการคืนพอยท์ ต้องไปบวกพอยท์กลับให้ลูกค้า (ถ้าเลือกว่าไม่คืนเงิน ก็จะไม่ทำขั้นตอนนี้)
        if ($refund_status == 'คืนแล้ว' && $refund_point > 0) {
            
            // หา member_id จากรหัสจอง
            $stmt_member = $conn->prepare("SELECT member_id FROM BOOKING WHERE booking_id = :b_id");
            $stmt_member->execute([':b_id' => $booking_id]);
            $member_id = $stmt_member->fetchColumn();

            if ($member_id) {
                // บวกพอยท์คืนในตาราง POINT
                $sql_point = "UPDATE POINT 
                              SET point_balance = point_balance + :points 
                              WHERE member_id = :m_id";
                $stmt_point = $conn->prepare($sql_point);
                $stmt_point->execute([
                    ':points' => $refund_point,
                    ':m_id' => $member_id
                ]);

                // บันทึกประวัติลงตาราง POINT_TRANSACTION 
                $sql_trans = "INSERT INTO POINT_TRANSACTION (member_id, booking_id, transaction_type, transaction_point, transaction_note, transaction_date) 
                              VALUES (:m_id, :b_id, 'ปรับโดย Admin', :points, 'คืนพอยท์จากการยกเลิกการจอง', NOW())";
                $stmt_trans = $conn->prepare($sql_trans);
                $stmt_trans->execute([
                    ':m_id' => $member_id,
                    ':b_id' => $booking_id,
                    ':points' => $refund_point
                ]);
            }
        }

        $conn->commit();
        $_SESSION['success'] = "บันทึกผลการพิจารณายกเลิกเรียบร้อยแล้ว";
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
    }

    header("Location: ../cancellations.php");
    exit();

} else {
    header("Location: ../cancellations.php");
    exit();
}
?>