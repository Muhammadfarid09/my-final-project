<?php
require_once '../includes/auth_check.php';

// บังคับส่งคำขอผ่าน HTTP POST เท่านั้น ป้องกันการเข้าถึงผ่าน GET โดยตรง
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "ไม่อนุญาตให้ดำเนินการผ่านวิธีนี้ (Method Not Allowed)";
    header("Location: ../rewards.php");
    exit();
}

// ตรวจสอบความปลอดภัย CSRF Token
require_csrf_token();

$reward_id = intval($_POST['id'] ?? 0);

if ($reward_id > 0) {

    try {
        // ดึงชื่อไฟล์รูปมาเพื่อที่จะลบออกจากโฟลเดอร์ uploads/rewards ก่อน
        $stmt_img = $conn->prepare("SELECT reward_image FROM Reward WHERE reward_id = :id");
        $stmt_img->execute([':id' => $reward_id]);
        $img = $stmt_img->fetchColumn();

        if ($img && file_exists('../../uploads/rewards/' . $img)) {
            unlink('../../uploads/rewards/' . $img);
        }

        // ลบแถวข้อมูลออกจากฐานข้อมูล
        $stmt = $conn->prepare("DELETE FROM Reward WHERE reward_id = :id");
        $stmt->execute([':id' => $reward_id]);

        $_SESSION['success'] = "ลบของรางวัลออกจากระบบเรียบร้อยแล้ว";

    } catch(PDOException $e) {
        $_SESSION['error'] = "ไม่สามารถลบของรางวัลได้ (อาจมีข้อมูลที่ผูกพันกันอยู่): " . $e->getMessage();
    }
}

header("Location: ../rewards.php");
exit();
?>
