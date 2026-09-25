<?php
require_once '../includes/auth_check.php';

if (isset($_GET['id'])) {
    $court_id = intval($_GET['id']);

    try {
        // 1. เช็คก่อนว่าสนามนี้เคยมีการจอง (BOOKING) หรือยัง
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM BOOKING WHERE court_id = :id");
        $stmt_check->execute([':id' => $court_id]);
        $has_booking = $stmt_check->fetchColumn();

        if ($has_booking > 0) {
            // ถ้าเคยมีคนจองแล้ว ห้ามลบเด็ดขาด (เพราะจะทำให้ประวัติบิลรายรับพัง)
            $_SESSION['error'] = "ไม่สามารถลบสนามได้ เนื่องจากสนามนี้เคยมีประวัติการจองในระบบแล้ว (แนะนำให้เปลี่ยนสถานะเป็น ปิดปรับปรุง แทน)";
        } else {
            // ถ้าสนามใหม่เอี่ยม (หรือเผลอสร้างผิด) ยังไม่มีคนจอง ให้ลบได้เลย
            
            // 2. ลบประวัติการแจ้งซ่อมที่อาจจะเผลอกดเล่นๆ ก่อน (เพื่อกัน Foreign Key Error)
            $stmt_del_repair = $conn->prepare("DELETE FROM COURT_REPAIR WHERE court_id = :id");
            $stmt_del_repair->execute([':id' => $court_id]);

            // 3. ลบสนามออกจากตาราง COURT
            $stmt_del_court = $conn->prepare("DELETE FROM COURT WHERE court_id = :id");
            $stmt_del_court->execute([':id' => $court_id]);

            $_SESSION['success'] = "ลบข้อมูลสนามออกจากระบบเรียบร้อยแล้ว";
        }
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบ: " . $e->getMessage();
    }
}

header("Location: ../courts.php");
exit();
?>