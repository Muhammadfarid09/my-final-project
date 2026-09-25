<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $admin_id = intval($_SESSION['admin_id']);
    $court_id = intval($_POST['court_id']);
    $repair_cause = $_POST['repair_cause'];
    $repair_start_date = $_POST['repair_start_date'];
    $repair_expected_end = $_POST['repair_expected_end'];
    $repair_cost = !empty($_POST['repair_cost']) ? floatval($_POST['repair_cost']) : 0.00;

    try {
        $conn->beginTransaction();

        // 1. บันทึกข้อมูลลงตารางซ่อมบำรุงสนาม (COURT_REPAIR)
        $sql_insert = "INSERT INTO COURT_REPAIR 
                       (court_id, admin_id, repair_cause, repair_start_date, repair_expected_end, repair_cost, repair_status) 
                       VALUES 
                       (:c_id, :a_id, :cause, :start_date, :exp_end, :cost, 'กำลังซ่อม')";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->execute([
            ':c_id' => $court_id,
            ':a_id' => $admin_id,
            ':cause' => $repair_cause,
            ':start_date' => $repair_start_date,
            ':exp_end' => $repair_expected_end,
            ':cost' => $repair_cost
        ]);

        // 2. เปลี่ยนสถานะสนามในตาราง COURT ให้เป็น 'ปิดปรับปรุง'
        $sql_update = "UPDATE COURT SET court_status = 'ปิดปรับปรุง' WHERE court_id = :c_id";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([':c_id' => $court_id]);

        $conn->commit();
        $_SESSION['success'] = "บันทึกแจ้งซ่อมและปิดสนามเรียบร้อยแล้ว";
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
    }

    header("Location: ../courts.php");
    exit();

} else {
    header("Location: ../courts.php");
    exit();
}
?>