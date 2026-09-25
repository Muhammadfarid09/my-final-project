<?php
require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // รับค่าจากฟอร์มให้ตรงกับ Database
    $repair_id = intval($_POST['repair_id']);
    $court_id = intval($_POST['court_id']);
    $repair_cause = trim($_POST['repair_cause']);
    $repair_status = $_POST['repair_status'];
    
    // รับค่าวันที่เสร็จและค่าใช้จ่าย (ถ้าว่างให้เป็น NULL และ 0 ตามลำดับ)
    $repair_actual_end = !empty($_POST['repair_actual_end']) ? $_POST['repair_actual_end'] : NULL;
    $repair_cost = !empty($_POST['repair_cost']) ? floatval($_POST['repair_cost']) : 0.00;

    if (empty($court_id) || empty($repair_cause) || empty($repair_status)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบถ้วน";
        header("Location: ../court_repair_edit.php?id=" . $repair_id);
        exit();
    }

    try {
        // อัปเดตข้อมูลให้ตรงกับ Column ในตาราง Court_repair
        $sql = "UPDATE Court_repair SET 
                court_id = :court_id, 
                repair_cause = :cause, 
                repair_actual_end = :actual_end,
                repair_cost = :cost,
                repair_status = :status 
                WHERE repair_id = :id";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':court_id' => $court_id,
            ':cause' => $repair_cause,
            ':actual_end' => $repair_actual_end,
            ':cost' => $repair_cost,
            ':status' => $repair_status,
            ':id' => $repair_id
        ]);

        // --- ลอจิกพิเศษ: เปิด/ปิดสนามอัตโนมัติ ---
        if ($repair_status == 'เสร็จแล้ว') {
            // ถ้าซ่อมเสร็จ เปิดสนามอัตโนมัติ
            $update_court = $conn->prepare("UPDATE Court SET court_status = 'เปิดใช้งาน' WHERE court_id = :court_id");
            $update_court->execute([':court_id' => $court_id]);
        } 
        else if ($repair_status == 'กำลังซ่อม') {
            // ถ้ากำลังซ่อม ปิดสนามอัตโนมัติ
            $update_court = $conn->prepare("UPDATE Court SET court_status = 'ปิดปรับปรุง' WHERE court_id = :court_id");
            $update_court->execute([':court_id' => $court_id]);
        }

        $_SESSION['success'] = "อัปเดตสถานะ/ปิดงานซ่อมสนามเรียบร้อยแล้ว";
        header("Location: ../court_repairs.php");
        exit();

    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: ../court_repair_edit.php?id=" . $repair_id);
        exit();
    }
} else {
    header("Location: ../court_repairs.php");
    exit();
}
?>