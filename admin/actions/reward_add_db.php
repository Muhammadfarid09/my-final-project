<?php
require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reward_name = trim($_POST['reward_name']);
    $reward_point_cost = intval($_POST['reward_point_cost']);
    $reward_min_level = $_POST['reward_min_level'];
    $reward_stock = intval($_POST['reward_stock']);
    $reward_description = trim($_POST['reward_description']);
    $reward_image = null; // ค่าเริ่มต้นถ้าไม่มีการอัปโหลดรูป

    if (empty($reward_name) || $reward_point_cost < 1) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลชื่อและแต้มให้ถูกต้อง";
        header("Location: ../rewards.php");
        exit();
    }

    try {
        // จัดการเรื่องรูปภาพ (ถ้ามีการอัปโหลด)
        if (isset($_FILES['reward_image']) && $_FILES['reward_image']['error'] == UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png']; // อนุญาตเฉพาะไฟล์ภาพ
            $file_info = pathinfo($_FILES['reward_image']['name']);
            $ext = strtolower($file_info['extension']);
            
            // เช็คนามสกุลไฟล์
            if (in_array($ext, $allowed_ext)) {
                // สร้างชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำกัน (เช่น reward_1691234567.jpg)
                $new_filename = 'reward_' . time() . '.' . $ext;
                
                // กำหนด Path ปลายทาง (ย้อนกลับ 2 โฟลเดอร์ไปยัง root)
                $upload_path = '../../uploads/rewards/' . $new_filename;
                
                // ย้ายไฟล์จาก temp ไปยังโฟลเดอร์เป้าหมาย
                if (move_uploaded_file($_FILES['reward_image']['tmp_name'], $upload_path)) {
                    $reward_image = $new_filename;
                } else {
                    $_SESSION['error'] = "ไม่สามารถอัปโหลดรูปภาพได้ กรุณาตรวจสอบสิทธิ์ของโฟลเดอร์ uploads/rewards/";
                    header("Location: ../rewards.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "อัปโหลดรูปภาพได้เฉพาะไฟล์นามสกุล JPG หรือ PNG เท่านั้น";
                header("Location: ../rewards.php");
                exit();
            }
        }

        // บันทึกข้อมูลลงตาราง Reward 
        $sql = "INSERT INTO Reward (reward_name, reward_image, reward_point_cost, reward_min_level, reward_stock, reward_description) 
                VALUES (:name, :image, :cost, :level, :stock, :desc)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $reward_name,
            ':image' => $reward_image, // ใส่ชื่อไฟล์ภาพลงไป
            ':cost' => $reward_point_cost,
            ':level' => $reward_min_level,
            ':stock' => $reward_stock,
            ':desc' => $reward_description
        ]);

        $_SESSION['success'] = "เพิ่มของรางวัล '{$reward_name}' เข้าสู่ระบบเรียบร้อยแล้ว";
        header("Location: ../rewards.php");
        exit();

    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: ../rewards.php");
        exit();
    }
} else {
    header("Location: ../rewards.php");
    exit();
}
?>