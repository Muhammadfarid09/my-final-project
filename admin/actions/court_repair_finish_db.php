<?php
require_once '../includes/auth_check.php';

// บังคับส่งคำขอผ่าน HTTP POST เท่านั้น ป้องกันการเข้าถึงผ่าน GET โดยตรง
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "ไม่อนุญาตให้ดำเนินการผ่านวิธีนี้ (Method Not Allowed)";
    header("Location: ../courts.php");
    exit();
}

// ตรวจสอบความปลอดภัย CSRF Token
require_csrf_token();

$court_id = intval($_POST['court_id'] ?? 0);

if ($court_id > 0) {

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