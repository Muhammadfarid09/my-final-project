<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // รับค่าจากฟอร์ม
    $news_title = trim($_POST['news_title']);
    $news_content = trim($_POST['news_content']);
    $admin_id = $_SESSION['admin_id'];
    
    // ตั้งค่าเริ่มต้นสำหรับรูปภาพ
    $news_image = null;

    try {
        // ตรวจสอบข้อมูลเบื้องต้น
        if (empty($news_title) || empty($news_content)) {
            throw new Exception("กรุณากรอกหัวข้อและเนื้อหาข่าวสารให้ครบถ้วน");
        }

        // ระบบจัดการอัปโหลดรูปภาพ (ถ้ามีการแนบไฟล์มา)
        if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] == 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $file_name = $_FILES['news_image']['name'];
            $file_tmp = $_FILES['news_image']['tmp_name'];
            $file_size = $_FILES['news_image']['size'];
            
            // หา Extension ของไฟล์
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // ตรวจสอบนามสกุลไฟล์
            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception("อัปโหลดได้เฉพาะไฟล์รูปภาพ (JPG, JPEG, PNG, GIF) เท่านั้น");
            }

            // ตรวจสอบขนาดไฟล์ (ไม่เกิน 2MB)
            if ($file_size > 2097152) {
                throw new Exception("ขนาดไฟล์รูปภาพต้องไม่เกิน 2MB");
            }

            // ตั้งชื่อไฟล์ใหม่ป้องกันชื่อซ้ำ (ใช้เวลา + สุ่มตัวเลข)
            $new_file_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            
            // ระบุโฟลเดอร์ปลายทาง (อย่าลืมสร้างโฟลเดอร์ uploads/news/ ไว้ด้วยนะครับ)
            $upload_path = '../../uploads/news/' . $new_file_name;

            // ทำการย้ายไฟล์
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $news_image = $new_file_name; // เก็บชื่อไฟล์ใหม่เตรียมบันทึกลง DB
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ กรุณาตรวจสอบสิทธิ์ของโฟลเดอร์ uploads/news");
            }
        }

        // เตรียมคำสั่ง SQL บันทึกข้อมูลลงตาราง News (ตาม Data Dictionary ตาราง 16)
        $sql = "INSERT INTO News (admin_id, news_title, news_content, news_image, news_published_at) 
                VALUES (:admin_id, :title, :content, :image, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':admin_id' => $admin_id,
            ':title' => $news_title,
            ':content' => $news_content,
            ':image' => $news_image
        ]);

        $_SESSION['success'] = "เพิ่มข่าวสาร/ประกาศ เรียบร้อยแล้ว";

    } catch(Exception $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // กลับไปหน้าข่าวสาร
    header("Location: ../news.php");
    exit();

} else {
    header("Location: ../news.php");
    exit();
}
?>