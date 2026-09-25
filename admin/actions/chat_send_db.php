<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // รับค่าจากฟอร์มที่แอดมินพิมพ์ส่งมา
    $admin_id = $_SESSION['admin_id'];
    $member_id = intval($_POST['member_id']);
    $message = trim($_POST['message']);

    try {
        // ตรวจสอบว่าแอดมินพิมพ์ข้อความมาจริงๆ ไม่ใช่ส่งค่าว่าง
        if (empty($message) || empty($member_id)) {
            throw new Exception("กรุณาพิมพ์ข้อความก่อนส่ง");
        }

        // เตรียมคำสั่ง SQL บันทึกข้อความลงตาราง Chat
        // chat_sender จะถูกฟิกซ์เป็น 'Admin' เสมอ เพราะไฟล์นี้ทำงานฝั่งหลังบ้าน
        $sql = "INSERT INTO Chat (member_id, admin_id, chat_message, chat_sender, chat_datetime) 
                VALUES (:member_id, :admin_id, :message, 'Admin', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':member_id' => $member_id,
            ':admin_id' => $admin_id,
            ':message' => $message
        ]);

        // สำเร็จ: ไม่ต้องตั้ง Session แจ้งเตือน เพราะระบบแชทควรเด้งกลับไปหน้าแชทแบบเนียนๆ เลย
        header("Location: ../chats.php?member_id=" . $member_id);
        exit();

    } catch(Exception $e) {
        // ถ้าเกิด Error ให้แจ้งเตือนแอดมิน
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการส่งข้อความ: " . $e->getMessage();
        header("Location: ../chats.php?member_id=" . $member_id);
        exit();
    }

} else {
    // ถ้าไม่ได้กดปุ่มส่งมา (เข้ามาทาง URL ตรงๆ) ให้ดีดกลับไปหน้าแชทหลัก
    header("Location: ../chats.php");
    exit();
}
?>