<?php
require_once '../includes/auth_check.php';

// ตรวจสอบสิทธิ์ (RBAC) - อนุญาตเฉพาะ Super Admin
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Super Admin') {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงการทำงานนี้";
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_phone = trim($_POST['admin_phone'] ?? '');
    $admin_password_plain = $_POST['admin_password'] ?? '';
    $admin_role = $_POST['admin_role'] ?? 'Admin';

    if (empty($admin_name) || empty($admin_phone) || empty($admin_password_plain)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        header("Location: ../admin_add.php");
        exit();
    }

    try {
        // เช็คเบอร์โทรซ้ำ
        $check_stmt = $conn->prepare("SELECT admin_id FROM Admin WHERE admin_phone = :phone");
        $check_stmt->execute([':phone' => $admin_phone]);
        
        if ($check_stmt->rowCount() > 0) {
            $_SESSION['error'] = "เบอร์โทรศัพท์นี้ถูกใช้งานเป็นบัญชีแอดมินแล้ว กรุณาใช้เบอร์อื่น";
            header("Location: ../admin_add.php");
            exit();
        }

        // เข้ารหัสรหัสผ่าน
        $hashed_password = password_hash($admin_password_plain, PASSWORD_DEFAULT);

        // บันทึกข้อมูล
        $sql = "INSERT INTO Admin (admin_name, admin_phone, admin_password, admin_role) 
                VALUES (:name, :phone, :password, :role)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $admin_name,
            ':phone' => $admin_phone,
            ':password' => $hashed_password,
            ':role' => $admin_role
        ]);

        $_SESSION['success'] = "เพิ่มผู้ดูแลระบบใหม่เรียบร้อยแล้ว";
        header("Location: ../admins.php");
        exit();

    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
        header("Location: ../admin_add.php");
        exit();
    }
} else {
    header("Location: ../admins.php");
    exit();
}
?>
