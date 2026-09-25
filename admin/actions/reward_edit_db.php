<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reward_id = intval($_POST['reward_id']);
    $reward_name = trim($_POST['reward_name']);
    $reward_point_cost = intval($_POST['reward_point_cost']);
    $reward_min_level = $_POST['reward_min_level'];
    $reward_stock = intval($_POST['reward_stock']);
    $reward_description = trim($_POST['reward_description']);
    $old_image = $_POST['old_image'];
    $new_image = $old_image;

    // การอัปโหลดรูปภาพใหม่ (ถ้ามีการเลือกรูป)
    if (isset($_FILES['reward_image']) && $_FILES['reward_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['reward_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_image = uniqid('reward_') . '.' . $ext;
            $upload_path = '../../uploads/rewards/';
            
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0777, true);
            }

            if (move_uploaded_file($_FILES['reward_image']['tmp_name'], $upload_path . $new_image)) {
                // ลบรูปเก่าทิ้งเพื่อไม่ให้เปลืองพื้นที่เซิร์ฟเวอร์
                if (!empty($old_image) && file_exists($upload_path . $old_image)) {
                    unlink($upload_path . $old_image);
                }
            } else {
                $new_image = $old_image; // อัปโหลดไม่ผ่านให้ใช้รูปเก่า
            }
        } else {
            $_SESSION['error'] = "รูปแบบไฟล์ภาพไม่รองรับ (รองรับเฉพาะ JPG, PNG)";
            header("Location: ../reward_edit.php?id=" . $reward_id);
            exit();
        }
    }

    try {
        $sql = "UPDATE Reward SET 
                reward_name = :name, 
                reward_point_cost = :cost, 
                reward_min_level = :level, 
                reward_stock = :stock, 
                reward_description = :desc, 
                reward_image = :image 
                WHERE reward_id = :id";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $reward_name,
            ':cost' => $reward_point_cost,
            ':level' => $reward_min_level,
            ':stock' => $reward_stock,
            ':desc' => $reward_description,
            ':image' => $new_image,
            ':id' => $reward_id
        ]);

        $_SESSION['success'] = "อัปเดตข้อมูลของรางวัล '{$reward_name}' สำเร็จแล้ว";
        header("Location: ../rewards.php");
        exit();

    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดต: " . $e->getMessage();
        header("Location: ../reward_edit.php?id=" . $reward_id);
        exit();
    }
} else {
    header("Location: ../rewards.php");
    exit();
}
?>
