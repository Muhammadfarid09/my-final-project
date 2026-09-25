<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_type = $_POST['product_type'];
    $tab_return = $_POST['tab_return'];
    
    // รับค่าทั่วไป
    $product_name = trim($_POST['product_name']);
    $product_price = floatval($_POST['product_price']);
    $product_stock = intval($_POST['product_stock']);
    $product_min_stock = intval($_POST['product_min_stock']);
    
    // รับค่าเฉพาะอุปกรณ์เช่า (ถ้าเป็นสินค้าบริโภค ค่าเหล่านี้จะเป็น NULL)
    $product_brand = isset($_POST['product_brand']) ? trim($_POST['product_brand']) : NULL;
    $product_model = isset($_POST['product_model']) ? trim($_POST['product_model']) : NULL;
    $product_size = isset($_POST['product_size']) ? trim($_POST['product_size']) : NULL;
    $product_level = (isset($_POST['product_level']) && $_POST['product_level'] !== '') ? $_POST['product_level'] : NULL;

    $new_image_name = NULL;

    // ระบบจัดการอัปโหลดรูปภาพ
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        // สร้างชื่อรูปใหม่แบบสุ่ม ป้องกันรูปชื่อซ้ำกันทับกัน
        $new_image_name = 'PD_' . uniqid() . '.' . $ext;
        
        // ตรวจสอบและสร้างโฟลเดอร์สำหรับเก็บรูปสินค้า (ถ้ายังไม่มี)
        $upload_path = '../../uploads/products/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        // ย้ายรูปจากไฟล์ชั่วคราวเข้าสู่โฟลเดอร์
        move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path . $new_image_name);
    }

    try {
        // เพิ่มข้อมูลลงตาราง Product
        $sql = "INSERT INTO Product (product_name, product_type, product_price, product_stock, product_min_stock, 
                                     product_brand, product_model, product_size, product_level, product_image) 
                VALUES (:name, :type, :price, :stock, :min_stock, :brand, :model, :size, :level, :image)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $product_name,
            ':type' => $product_type,
            ':price' => $product_price,
            ':stock' => $product_stock,
            ':min_stock' => $product_min_stock,
            ':brand' => $product_brand,
            ':model' => $product_model,
            ':size' => $product_size,
            ':level' => $product_level,
            ':image' => $new_image_name
        ]);

        $_SESSION['success'] = "เพิ่มรายการ {$product_name} เข้าสู่ระบบสำเร็จ";
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
    }
    
    // บันทึกเสร็จให้เด้งกลับไปที่หน้าสินค้าและเปิดแท็บที่เกี่ยวข้องทันที
    header("Location: ../products.php?tab=" . $tab_return);
    exit();

} else {
    header("Location: ../products.php");
    exit();
}
?>