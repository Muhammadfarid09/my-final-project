<?php
require_once '../includes/auth_check.php';

// ตรวจสอบสิทธิ์ (RBAC) - อนุญาตเฉพาะ Super Admin
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Super Admin') {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงการทำงานนี้";
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../admins.php");
    exit();
}

$admin_id_to_delete = intval($_GET['id']);

// ป้องกันไม่ให้แอดมินลบตัวเอง (รหัสเดียวกับที่ล็อคอินอยู่)
if ($admin_id_to_delete === intval($_SESSION['admin_id'])) {
    $_SESSION['error'] = "ไม่อนุญาตให้ลบบัญชีของตนเอง";
    header("Location: ../admins.php");
    exit();
}

try {
    // ป้องกันกรณีลบแอดมินคนสุดท้าย (ทางเลือกเสริม)
    // $stmt = $conn->query("SELECT COUNT(*) FROM Admin");
    // if ($stmt->fetchColumn() <= 1) { ... }

    $stmt = $conn->prepare("DELETE FROM Admin WHERE admin_id = :id");
    $stmt->execute([':id' => $admin_id_to_delete]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = "ลบข้อมูลผู้ดูแลระบบเรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "ไม่พบผู้ดูแลระบบที่ต้องการลบ";
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
}

header("Location: ../admins.php");
exit();
?>
