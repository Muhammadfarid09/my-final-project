<?php
// เริ่มต้น Session 
session_start();
// ถอยกลับไป 2 โฟลเดอร์เพื่อเรียกใช้ config.php (จาก admin/actions/ -> admin/ -> ts-pattani/config/)
require_once '../../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_csrf_token();
    
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    try {
        // ตรวจสอบ Rate Limiting ป้องกัน Brute-Force สำหรับ Admin
        $throttle = check_login_throttle($conn, $phone, 'Admin');
        if (!$throttle['allowed']) {
            $_SESSION['error'] = $throttle['message'];
            header("Location: ../login.php");
            exit();
        }

        // ค้นหาผู้ดูแลระบบจากเบอร์โทรศัพท์
        $sql = "SELECT * FROM Admin WHERE admin_phone = :phone";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':phone' => $phone]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // ถ้าพบข้อมูล
        if ($admin) {
            // ตรวจสอบรหัสผ่าน
            if (password_verify($password, $admin['admin_password'])) {
                
                // ล้างประวัติการพยายามล็อกอินผิดพลาดเมื่อสำเร็จ
                clear_login_throttle($conn, $phone, 'Admin');

                // เก็บข้อมูลลง Session สำหรับ Admin (ตั้งชื่อตัวแปรให้ต่างจากของ Member)
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['admin_name'];
                $_SESSION['admin_role'] = $admin['admin_role'];

                // 🟢 กำหนดข้อความแจ้งเตือนสีเขียว เมื่อล็อกอินสำเร็จ
                $_SESSION['success'] = "เข้าสู่ระบบสำเร็จ ยินดีต้อนรับคุณ " . htmlspecialchars($admin['admin_name']);
                log_activity($conn, 'ADMIN_LOGIN', 'Admin', $admin['admin_id'], 'เข้าสู่ระบบสำเร็จผ่านเบอร์ ' . $phone, 'Admin', $admin['admin_id'], $admin['admin_name']);

                // ล็อกอินสำเร็จ พากลับไปหน้า Dashboard ของแอดมิน
                header("Location: ../index.php");
                exit();
            } else {
                // 🔴 บันทึกความล้มเหลวและแจ้งเตือนจำนวนครั้งที่เหลือ
                $fail = record_login_failure($conn, $phone, 'Admin', 'รหัสผ่านผู้ดูแลระบบไม่ถูกต้อง');
                $_SESSION['error'] = $fail['message'];
                header("Location: ../login.php");
                exit();
            }
        } else {
            // 🔴 บันทึกความล้มเหลวและแจ้งเตือนจำนวนครั้งที่เหลือ
            $fail = record_login_failure($conn, $phone, 'Admin', 'ไม่พบเบอร์โทรผู้ดูแลระบบ');
            $_SESSION['error'] = $fail['message'];
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