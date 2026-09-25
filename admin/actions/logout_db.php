<?php
// เริ่มต้น Session เพื่อให้สามารถเข้าถึงตัวแปร $_SESSION ได้
session_start();

// ลบตัวแปร Session ทั้งหมดที่เกี่ยวข้องกับ Admin ออก
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);

// 🟢 สร้างข้อความแจ้งเตือนสีเขียวเพื่อบอกว่าออกจากระบบสำเร็จ (จะไปโชว์ที่หน้า login.php)
$_SESSION['success'] = "ออกจากระบบเรียบร้อยแล้ว หวังว่าจะได้พบกันใหม่ครับ";

// เด้งกลับไปที่หน้าล็อกอินของแอดมิน
header("Location: ../login.php");
exit();
?>