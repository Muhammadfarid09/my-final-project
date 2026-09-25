<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = intval($_POST['product_id']);
    $product_type = $_POST['product_type'];
    $tab_return = $_POST['tab_return'];
    
    // รับค่าทั่วไป
    $product_name = trim($_POST['product_name']);
    $product_price = floatval($_POST['product_price']);
    $product_stock = intval($_POST['product_stock']);
    $product_min_stock = intval($_POST['product_min_stock']);
    
    // รับค่าเฉพาะอุปกรณ์เช่า
    $product_brand = isset($_POST['product_brand']) ? trim($_POST['product_brand']) : NULL;
    $product_model = isset($_POST['product_model']) ? trim($_POST['product_model']) : NULL;
    $product_size = isset($_POST['product_size']) ? trim($_POST['product_size']) : NULL;
    $product_level = (isset($_POST['product_level']) && $_POST['product_level'] !== '') ? $_POST['product_level'] : NULL;

    // จัดการเรื่องรูปภาพ
    $old_image = $_POST['old_image'];
    $final_image_name = $old_image; // ค่าตั้งต้นคือใช้รูปเดิม

    $upload_path = '../../uploads/products/';

    // ตรวจสอบว่ามีการอัปโหลดรูปภาพใหม่เข้ามาหรือไม่ (error 0 คืออัปโหลดสำเร็จ)
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $final_image_name = 'PD_' . uniqid() . '.' . $ext;
        
        // ถ้ายังไม่มีโฟลเดอร์ให้สร้างขึ้นมาใหม่
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        // ย้ายรูปใหม่เข้าโฟลเดอร์
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path . $final_image_name)) {
            // ลบรูปภาพเก่าทิ้งเพื่อประหยัดพื้นที่เซิร์ฟเวอร์
            if (!empty($old_image) && file_exists($upload_path . $old_image)) {
                unlink($upload_path . $old_image);
            }
        } else {
            // ถ้าอัปโหลดรูปใหม่พลาด ให้กลับไปใช้ชื่อรูปเดิม
            $final_image_name = $old_image;
        }
    }

    try {
        // อัปเดตข้อมูลลงตาราง Product
        $sql = "UPDATE Product SET 
                product_name = :name, 
                product_price = :price, 
                product_stock = :stock, 
                product_min_stock = :min_stock, 
                product_brand = :brand, 
                product_model = :model, 
                product_size = :size, 
                product_level = :level, 
                product_image = :image 
                WHERE product_id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $product_name,
            ':price' => $product_price,
            ':stock' => $product_stock,
            ':min_stock' => $product_min_stock,
            ':brand' => $product_brand,
            ':model' => $product_model,
            ':size' => $product_size,
            ':level' => $product_level,
            ':image' => $final_image_name,
            ':id' => $product_id
        ]);

        $_SESSION['success'] = "อัปเดตข้อมูลรายการ {$product_name} สำเร็จเรียบร้อย";
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดต: " . $e->getMessage();
    }
    
    // บันทึกเสร็จให้เด้งกลับไปที่หน้าสินค้าและเปิดแท็บเดิมทันที
    header("Location: ../products.php?tab=" . $tab_return);
    exit();

} else {
    header("Location: ../products.php");
    exit();
}
?>