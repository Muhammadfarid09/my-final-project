<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // รับค่าจากฟอร์ม
    $news_id = intval($_POST['news_id']);
    $news_title = trim($_POST['news_title']);
    $news_content = trim($_POST['news_content']);
    $old_image = trim($_POST['old_image']); // ชื่อไฟล์รูปเก่า (ถ้ามี)
    
    // ตั้งค่ารูปที่จะอัปเดต เริ่มต้นให้เป็นรูปเก่าไว้ก่อน
    $update_image = $old_image;

    try {
        // ตรวจสอบข้อมูลเบื้องต้น
        if (empty($news_id) || empty($news_title) || empty($news_content)) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน");
        }

        // ระบบจัดการอัปโหลดรูปภาพ (ถ้ามีการแนบไฟล์ใหม่มา)
        if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $file_name = $_FILES['new_image']['name'];
            $file_tmp = $_FILES['new_image']['tmp_name'];
            $file_size = $_FILES['new_image']['size'];
            
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

            // ตั้งชื่อไฟล์ใหม่ป้องกันชื่อซ้ำ
            $new_file_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = '../../uploads/news/' . $new_file_name;

            // ทำการย้ายไฟล์ใหม่เข้าเซิร์ฟเวอร์
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $update_image = $new_file_name; // ให้ชื่อรูปที่จะอัปเดตเป็นรูปใหม่

                // เมื่ออัปโหลดรูปใหม่สำเร็จ ให้ทำการ "ลบรูปเก่าทิ้ง" ทันที (ถ้ามีรูปเก่า)
                if (!empty($old_image)) {
                    $old_file_path = '../../uploads/news/' . $old_image;
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                }
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดรูปภาพใหม่");
            }
        }

        // เตรียมคำสั่ง SQL บันทึกข้อมูลลงตาราง News
        // อ้างอิง Data Dictionary: อัปเดต Title, Content และ Image โดยไม่ต้องเปลี่ยนวันที่โพสต์ (news_published_at)
        $sql = "UPDATE News 
                SET news_title = :title, 
                    news_content = :content, 
                    news_image = :image 
                WHERE news_id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title' => $news_title,
            ':content' => $news_content,
            ':image' => $update_image,
            ':id' => $news_id
        ]);

        $_SESSION['success'] = "แก้ไขข่าวสาร: " . htmlspecialchars($news_title) . " เรียบร้อยแล้ว";

    } catch(Exception $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // กลับไปหน้าข่าวสารหลัก ไม่ว่าจะสำเร็จหรือพังก็ตาม
    header("Location: ../news.php");
    exit();

} else {
    header("Location: ../news.php");
    exit();
}
?>