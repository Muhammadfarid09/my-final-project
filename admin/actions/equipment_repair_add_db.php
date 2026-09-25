<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $admin_id = intval($_SESSION['admin_id']);
    $product_id = intval($_POST['product_id']);
    $repair_qty = intval($_POST['repair_qty']);
    $repair_cause = trim($_POST['repair_cause']);

    if ($repair_qty <= 0) {
        $_SESSION['error'] = "จำนวนที่ส่งซ่อมต้องมากกว่า 0 ชิ้น";
        header("Location: ../products.php?tab=rental");
        exit();
    }

    try {
        $conn->beginTransaction();

        $stmt_check = $conn->prepare("SELECT product_stock, product_name FROM Product WHERE product_id = :p_id");
        $stmt_check->execute([':p_id' => $product_id]);
        $product = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("ไม่พบข้อมูลอุปกรณ์นี้ในระบบ");
        }

        if ($product['product_stock'] < $repair_qty) {
            throw new Exception("ไม่สามารถส่งซ่อมได้ เนื่องจากสต็อกของ '" . $product['product_name'] . "' มีไม่เพียงพอ");
        }

        // บันทึกข้อมูลลงตาราง Equipment_repair ตามชื่อคอลัมน์จริงของคุณ
        $sql_insert = "INSERT INTO Equipment_repair 
                       (product_id, admin_id, eq_repair_qty, eq_repair_cause, eq_repair_date, eq_repair_status) 
                       VALUES 
                       (:p_id, :a_id, :qty, :cause, CURDATE(), 'กำลังซ่อม')";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->execute([
            ':p_id' => $product_id,
            ':a_id' => $admin_id,
            ':qty' => $repair_qty,
            ':cause' => $repair_cause
        ]);

        // ตัดสต็อกออกจากตาราง Product
        $sql_update = "UPDATE Product SET product_stock = product_stock - :qty WHERE product_id = :p_id";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([
            ':qty' => $repair_qty,
            ':p_id' => $product_id
        ]);

        $conn->commit();
        $_SESSION['success'] = "บันทึกแจ้งซ่อมและตัดสต็อกเรียบร้อยแล้วจำนวน {$repair_qty} ชิ้น";
        
    } catch(Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: ../products.php?tab=rental");
    exit();

} else {
    header("Location: ../products.php?tab=rental");
    exit();
}
?>