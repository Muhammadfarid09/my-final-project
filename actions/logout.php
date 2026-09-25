<?php
session_start();

// ล้างค่าตัวแปร Session ทั้งหมด
$_SESSION = array();

// ทำลาย Session cookie ถ้ามี
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลาย Session ในฝั่ง Server
session_destroy();

// เริ่ม Session ใหม่ชั่วคราวเพื่อส่งข้อความแจ้งเตือนไปหน้า Login
session_start();
$_SESSION['success'] = "ออกจากระบบเรียบร้อยแล้ว";
header("Location: ../login.php");
exit();
?>