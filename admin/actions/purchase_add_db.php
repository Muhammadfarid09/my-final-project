<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $admin_id = intval($_SESSION['admin_id']);
    $product_id = intval($_POST['product_id']);
    $qty = intval($_POST['purchase_quantity']);
    $price_per_unit = floatval($_POST['purchase_price_per_unit']);
    $purchase_date = $_POST['purchase_date'];
    
    // คำนวณยอดรวม (จำนวน x ราคาต่อหน่วย)
    $total_price = $qty * $price_per_unit;

    try {
        // เริ่มระบบ Transaction เพื่อความปลอดภัยของข้อมูล
        $conn->beginTransaction();

        // 1. บันทึกประวัติลงตาราง Purchase
        $sql_insert = "INSERT INTO Purchase (product_id, admin_id, purchase_quantity, purchase_price_per_unit, purchase_total_price, purchase_date) 
                       VALUES (:product_id, :admin_id, :qty, :price_unit, :total_price, :p_date)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->execute([
            ':product_id' => $product_id,
            ':admin_id' => $admin_id,
            ':qty' => $qty,
            ':price_unit' => $price_per_unit,
            ':total_price' => $total_price,
            ':p_date' => $purchase_date
        ]);

        // 2. บวกจำนวนสินค้าเพิ่มเข้าสต็อกในตาราง Product
        $sql_update = "UPDATE Product SET product_stock = product_stock + :qty WHERE product_id = :id";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([
            ':qty' => $qty,
            ':id' => $product_id
        ]);

        // ถ้าผ่านทั้ง 2 คำสั่งให้ยืนยัน
        $conn->commit();

        $_SESSION['success'] = "บันทึกจัดซื้อและเพิ่มสต็อกสินค้าจำนวน $qty ชิ้น เรียบร้อยแล้ว";
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
    }

    header("Location: ../purchases.php");
    exit();

} else {
    header("Location: ../purchases.php");
    exit();
}
?>