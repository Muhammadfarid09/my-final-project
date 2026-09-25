<?php
require_once '../includes/auth_check.php';

if (isset($_GET['id'])) {
    $reward_id = intval($_GET['id']);

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
