<?php
require_once '../includes/auth_check.php';

// ตรวจสอบสิทธิ์ (RBAC) - อนุญาตเฉพาะ Super Admin
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Super Admin') {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = intval($_POST['admin_id']);
    $admin_name = trim($_POST['admin_name']);
    $admin_phone = trim($_POST['admin_phone']);
    $admin_role = $_POST['admin_role'];
    $admin_password = trim($_POST['admin_password']); // รหัสผ่านใหม่ (ถ้ามี)

    if (empty($admin_name) || empty($admin_phone) || empty($admin_role) || $admin_id <= 0) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
        header("Location: ../admin_edit.php?id=" . $admin_id);
        exit();
    }

    try {
        // ตรวจสอบเบอร์โทรซ้ำ (ยกเว้นไอดีของตัวเอง)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Admin WHERE admin_phone = :phone AND admin_id != :id");
        $stmt->execute([':phone' => $admin_phone, ':id' => $admin_id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "เบอร์โทรศัพท์นี้ถูกใช้งานโดยผู้ดูแลระบบท่านอื่นแล้ว";
            header("Location: ../admin_edit.php?id=" . $admin_id);
            exit();
        }

        // กรณีมีการเปลี่ยนรหัสผ่าน
        if (!empty($admin_password)) {
            if (strlen($admin_password) < 6) {
                $_SESSION['error'] = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
                header("Location: ../admin_edit.php?id=" . $admin_id);
                exit();
            }
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $sql = "UPDATE Admin SET admin_name = :name, admin_phone = :phone, admin_role = :role, admin_password = :password WHERE admin_id = :id";
            $params = [
                ':name' => $admin_name,
                ':phone' => $admin_phone,
                ':role' => $admin_role,
                ':password' => $hashed_password,
                ':id' => $admin_id
            ];
        } else {
            // กรณีไม่มีการเปลี่ยนรหัสผ่าน
            $sql = "UPDATE Admin SET admin_name = :name, admin_phone = :phone, admin_role = :role WHERE admin_id = :id";
            $params = [
                ':name' => $admin_name,
                ':phone' => $admin_phone,
                ':role' => $admin_role,
                ':id' => $admin_id
            ];
        }

        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute($params)) {
            // ถ้าแอดมินที่ถูกแก้ไขคือตัวเอง ให้รักษาสถานะ role ใน session ให้เป็นอัพเดทล่าสุด
            if ($_SESSION['admin_id'] == $admin_id) {
                $_SESSION['admin_role'] = $admin_role;
                $_SESSION['admin_name'] = $admin_name;
            }
            $_SESSION['success'] = "อัปเดตข้อมูลผู้ดูแลระบบสำเร็จ";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
        }

    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "คำขอไม่ถูกต้อง";
}

header("Location: ../admins.php");
exit();
?>
