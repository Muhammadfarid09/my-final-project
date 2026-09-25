<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $cart_data_json = $_POST['cart_data'];
    $total_amount = floatval($_POST['total_amount']);
    
    // ดึงรหัสแอดมินคนที่กำลังใช้งาน POS อยู่ เพื่อเอาไปบันทึกลง Pos_sale
    $admin_id = intval($_SESSION['admin_id']); 

    $cart_items = json_decode($cart_data_json, true);

    if (empty($cart_items)) {
        $_SESSION['error'] = "ไม่พบรายการสินค้าในตะกร้า";
        header("Location: ../pos.php");
        exit();
    }

    try {
        $conn->beginTransaction();

        $revenue_rental = 0.00;
        $revenue_product = 0.00;

        foreach ($cart_items as $item) {
            $product_id = intval($item['id']);
            $qty = intval($item['qty']);
            $item_total_price = floatval($item['price']) * $qty;

            // 1. วนลูปหักสต็อกสินค้า
            $sql_update = "UPDATE Product SET product_stock = product_stock - :qty WHERE product_id = :id";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->execute([
                ':qty' => $qty,
                ':id' => $product_id
            ]);

            // 2. บันทึกประวัติการขายลงตาราง Pos_sale (รายชิ้น)
            $sql_pos = "INSERT INTO Pos_sale (admin_id, product_id, pos_quantity, pos_total_price) 
                        VALUES (:admin, :product, :qty, :price)";
            $stmt_pos = $conn->prepare($sql_pos);
            $stmt_pos->execute([
                ':admin' => $admin_id,
                ':product' => $product_id,
                ':qty' => $qty,
                ':price' => $item_total_price
            ]);

            // 3. ดึงประเภทสินค้าเพื่อแยกเงินเข้าระบบบัญชี (Revenue)
            $stmt_check_type = $conn->prepare("SELECT product_type FROM Product WHERE product_id = :id");
            $stmt_check_type->execute([':id' => $product_id]);
            $prod_type = $stmt_check_type->fetchColumn();

            if ($prod_type === 'อุปกรณ์เช่า') {
                $revenue_rental += $item_total_price;
            } else {
                $revenue_product += $item_total_price;
            }
        }

        // 4. บันทึกยอดรายรับรวมเข้าตาราง Revenue
        $sql_revenue = "INSERT INTO Revenue 
                        (payment_id, revenue_court_amount, revenue_rental_amount, revenue_product_amount, revenue_total_amount, revenue_date, revenue_type) 
                        VALUES 
                        (NULL, 0.00, :rental_amt, :product_amt, :total_amt, CURDATE(), 'หน้าร้าน')";
        
        $stmt_revenue = $conn->prepare($sql_revenue);
        $stmt_revenue->execute([
            ':rental_amt' => $revenue_rental,
            ':product_amt' => $revenue_product,
            ':total_amt' => $total_amount
        ]);

        // --- ส่วนที่เพิ่มเข้ามาใหม่ ---
        // ดึง ID ล่าสุดที่เพิ่งบันทึกลง Pos_sale เพื่อส่งไปหน้าใบเสร็จ
        $last_pos_id = $conn->lastInsertId();
        
        $conn->commit();

        $_SESSION['success'] = "ชำระเงินสำเร็จ!";
        // ส่ง ID กลับไปด้วย เพื่อให้รู้ว่าต้องเปิดใบเสร็จบิลไหน
        $_SESSION['print_receipt_id'] = $last_pos_id;
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการทำรายการ: " . $e->getMessage();
    }

    header("Location: ../pos.php");
    exit();

} else {
    header("Location: ../pos.php");
    exit();
}
?>