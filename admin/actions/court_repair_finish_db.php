<?php
require_once '../includes/auth_check.php';

if (isset($_GET['court_id'])) {
    $court_id = intval($_GET['court_id']);

    try {
        $conn->beginTransaction();

        // 1. อัปเดตตาราง COURT_REPAIR เฉพาะรายการที่ยัง 'กำลังซ่อม' ของสนามนี้ ให้เป็น 'เสร็จแล้ว'
        // และบันทึกวันที่เสร็จจริง (repair_actual_end) เป็นวันที่ปัจจุบัน
        $sql_update_repair = "UPDATE COURT_REPAIR 
                              SET repair_status = 'เสร็จแล้ว', repair_actual_end = CURDATE() 
                              WHERE court_id = :c_id AND repair_status = 'กำลังซ่อม'";
        $stmt_update_repair = $conn->prepare($sql_update_repair);
        $stmt_update_repair->execute([':c_id' => $court_id]);

        // 2. เปลี่ยนสถานะสนามในตาราง COURT ให้กลับมาเป็น 'ว่าง' พร้อมให้บริการ
        $sql_update_court = "UPDATE COURT SET court_status = 'ว่าง' WHERE court_id = :c_id";
        $stmt_update_court = $conn->prepare($sql_update_court);
        $stmt_update_court->execute([':c_id' => $court_id]);

        $conn->commit();
        $_SESSION['success'] = "อัปเดตสถานะการซ่อมเสร็จสิ้น สนามพร้อมใช้งานแล้ว";
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: ../courts.php");
    exit();

} else {
    header("Location: ../courts.php");
    exit();
}
?>