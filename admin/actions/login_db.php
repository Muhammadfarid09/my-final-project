<?php
// เริ่มต้น Session 
session_start();
// ถอยกลับไป 2 โฟลเดอร์เพื่อเรียกใช้ config.php (จาก admin/actions/ -> admin/ -> ts-pattani/config/)
require_once '../../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    try {
        // ค้นหาผู้ดูแลระบบจากเบอร์โทรศัพท์
        $sql = "SELECT * FROM Admin WHERE admin_phone = :phone";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':phone' => $phone]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // ถ้าพบข้อมูล
        if ($admin) {
            // ตรวจสอบรหัสผ่าน
            if (password_verify($password, $admin['admin_password'])) {
                
                // เก็บข้อมูลลง Session สำหรับ Admin (ตั้งชื่อตัวแปรให้ต่างจากของ Member)
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['admin_name'];
                $_SESSION['admin_role'] = $admin['admin_role'];

                // 🟢 กำหนดข้อความแจ้งเตือนสีเขียว เมื่อล็อกอินสำเร็จ
                $_SESSION['success'] = "เข้าสู่ระบบสำเร็จ ยินดีต้อนรับคุณ " . htmlspecialchars($admin['admin_name']);

                // ล็อกอินสำเร็จ พากลับไปหน้า Dashboard ของแอดมิน
                header("Location: ../index.php");
                exit();
            } else {
                // 🔴 กำหนดข้อความแจ้งเตือนสีแดง เมื่อรหัสผ่านผิด
                $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
                header("Location: ../login.php");
                exit();
            }
        } else {
            // 🔴 กำหนดข้อความแจ้งเตือนสีแดง เมื่อไม่พบเบอร์โทรศัพท์
            $_SESSION['error'] = "ไม่พบข้อมูลผู้ดูแลระบบในระบบ";
            header("Location: ../login.php");
            exit();
        }

    } catch(PDOException $e) {
        // 🔴 แจ้งเตือนกรณีฐานข้อมูลมีปัญหา
        $_SESSION['error'] = "เกิดข้อผิดพลาดของระบบ: " . $e->getMessage();
        header("Location: ../login.php");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
?>