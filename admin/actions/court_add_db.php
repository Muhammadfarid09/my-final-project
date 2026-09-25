<?php
require_once '../includes/auth_check.php';

// ตรวจสอบว่ามีการส่งฟอร์มมาแบบ POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // รับค่าจากฟอร์มและตรวจสอบความปลอดภัยเบื้องต้น
    $court_name = trim($_POST['court_name']);
    $court_price_per_hour = floatval($_POST['court_price_per_hour']);
    
    // ฟิลด์ที่เป็นทางเลือก (Peak/Off-peak) ถ้าไม่ได้กรอกมา ให้ใส่เป็น NULL
    $court_peak_price = !empty($_POST['court_peak_price']) ? floatval($_POST['court_peak_price']) : NULL;
    $court_offpeak_price = !empty($_POST['court_offpeak_price']) ? floatval($_POST['court_offpeak_price']) : NULL;
    
    // เวลาเปิดปิด
    $court_open_time = !empty($_POST['court_open_time']) ? trim($_POST['court_open_time']) : NULL;
    $court_close_time = !empty($_POST['court_close_time']) ? trim($_POST['court_close_time']) : NULL;

    // เช็คว่ากรอกชื่อสนามและราคาปกติมาไหม (ป้องกันการข้ามการตรวจสอบของ HTML)
    if (empty($court_name) || $court_price_per_hour <= 0) {
        $_SESSION['error'] = "กรุณากรอกชื่อสนามและราคาปกติให้ถูกต้อง";
        header("Location: ../courts.php");
        exit();
    }

    try {
        // เตรียมคำสั่ง SQL สำหรับบันทึกข้อมูล
        // ตั้งค่าเริ่มต้น court_status เป็น 'ว่าง' ทันที
        $sql = "INSERT INTO COURT 
                (court_name, court_price_per_hour, court_peak_price, court_offpeak_price, court_open_time, court_close_time, court_status) 
                VALUES 
                (:name, :price, :peak_price, :offpeak_price, :open_time, :close_time, 'ว่าง')";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->execute([
            ':name' => $court_name,
            ':price' => $court_price_per_hour,
            ':peak_price' => $court_peak_price,
            ':offpeak_price' => $court_offpeak_price,
            ':open_time' => $court_open_time,
            ':close_time' => $court_close_time
        ]);

        $_SESSION['success'] = "เพิ่มข้อมูลสนามแบดมินตันใหม่เรียบร้อยแล้ว";

    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }

    // กลับไปหน้าจัดการสนาม
    header("Location: ../courts.php");
    exit();

} else {
    // ถ้าไม่ได้เข้ามาผ่าน POST (เช่น พิมพ์ URL ตรงๆ) ให้เตะกลับไปหน้าจัดการสนาม
    header("Location: ../courts.php");
    exit();
}
?>