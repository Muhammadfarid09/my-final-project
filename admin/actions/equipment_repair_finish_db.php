<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // รับค่าจาก Modal 
    $eq_repair_id = intval($_POST['eq_repair_id']);
    $action_type = $_POST['action_type']; // 'ซ่อมแล้ว' หรือ 'เสียหายถาวร'
    $repair_cost = !empty($_POST['repair_cost']) ? floatval($_POST['repair_cost']) : 0.00;

    try {
        // เริ่ม Transaction ป้องกันข้อมูลสูญหาย
        $conn->beginTransaction();

        // 1. ตรวจสอบข้อมูลการซ่อมก่อนว่ามีอยู่จริง และยังเป็นสถานะ 'กำลังซ่อม' 
        // พร้อมดึงรหัสสินค้า (product_id) และจำนวน (eq_repair_qty) มาด้วย
        $stmt_check = $conn->prepare("SELECT product_id, eq_repair_qty, eq_repair_status FROM Equipment_repair WHERE eq_repair_id = :r_id");
        $stmt_check->execute([':r_id' => $eq_repair_id]);
        $repair_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$repair_data) {
            throw new Exception("ไม่พบข้อมูลการแจ้งซ่อมในระบบ");
        }

        if ($repair_data['eq_repair_status'] != 'กำลังซ่อม') {
            throw new Exception("รายการนี้ถูกดำเนินการไปแล้ว ไม่สามารถเปลี่ยนสถานะซ้ำได้");
        }

        $product_id = $repair_data['product_id'];
        $qty = $repair_data['eq_repair_qty'];

        // 2. อัปเดตสถานะในตาราง Equipment_repair
        // เปลี่ยนสถานะ และบันทึกค่าใช้จ่าย
        $sql_update_repair = "UPDATE Equipment_repair 
                              SET eq_repair_status = :status, eq_repair_cost = :cost 
                              WHERE eq_repair_id = :r_id";
        $stmt_update_repair = $conn->prepare($sql_update_repair);
        $stmt_update_repair->execute([
            ':status' => $action_type,
            ':cost' => $repair_cost,
            ':r_id' => $eq_repair_id
        ]);

        // 3. ตรวจสอบ Action ว่าต้อง "คืนสต็อก" หรือไม่
        if ($action_type == 'ซ่อมแล้ว') {
            // ซ่อมสำเร็จ -> คืนสต็อกกลับเข้าไปในตาราง Product
            $sql_return_stock = "UPDATE Product SET product_stock = product_stock + :qty WHERE product_id = :p_id";
            $stmt_return_stock = $conn->prepare($sql_return_stock);
            $stmt_return_stock->execute([
                ':qty' => $qty,
                ':p_id' => $product_id
            ]);
            
            $_SESSION['success'] = "บันทึกซ่อมเสร็จสิ้น คืนสต็อกอุปกรณ์เข้าสู่ระบบจำนวน {$qty} ชิ้น เรียบร้อยแล้ว";
        } else {
            // เสียหายถาวร -> ไม่คืนสต็อก
            $_SESSION['success'] = "บันทึกอุปกรณ์เป็น 'เสียหายถาวร' ตัดสต็อกจำนวน {$qty} ชิ้น ออกจากระบบถาวรแล้ว";
        }

        // ยืนยันคำสั่งทั้งหมด
        $conn->commit();
        
    } catch(Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // กลับไปหน้าประวัติซ่อมอุปกรณ์
    header("Location: ../equipment_repairs.php");
    exit();

} else {
    header("Location: ../equipment_repairs.php");
    exit();
}
?>