<?php
require_once '../includes/auth_check.php';

// บังคับส่งคำขอผ่าน HTTP POST เท่านั้น ป้องกันการเข้าถึงผ่าน GET โดยตรง
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "ไม่อนุญาตให้ดำเนินการผ่านวิธีนี้ (Method Not Allowed)";
    header("Location: ../news.php");
    exit();
}

// ตรวจสอบความปลอดภัย CSRF Token
require_csrf_token();

$news_id = intval($_POST['id'] ?? 0);

if ($news_id > 0) {

    try {
        // 1. ดึงข้อมูลข่าวสารมาเช็คก่อนว่ามีรูปภาพไหม เพื่อจะได้ตามไปลบไฟล์รูปทิ้ง
        $sql_check = "SELECT news_image, news_title FROM News WHERE news_id = :id";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([':id' => $news_id]);
        $news_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($news_data) {
            // ถ้ามีรูปภาพ ให้ทำการลบไฟล์ออกจากเซิร์ฟเวอร์
            if (!empty($news_data['news_image'])) {
                $file_path = '../../uploads/news/' . $news_data['news_image'];
                if (file_exists($file_path)) {
                    unlink($file_path); // คำสั่งลบไฟล์
                }
            }

            // 2. ลบข้อมูลออกจากฐานข้อมูล
            $sql_delete = "DELETE FROM News WHERE news_id = :id";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->execute([':id' => $news_id]);

            $_SESSION['success'] = "ลบข่าวสาร: " . htmlspecialchars($news_data['news_title']) . " เรียบร้อยแล้ว";
        } else {
            $_SESSION['error'] = "ไม่พบข้อมูลข่าวสารที่ต้องการลบ";
        }

    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
}

// ลบเสร็จให้เด้งกลับไปหน้าข่าวสาร
header("Location: ../news.php");
exit();
?>