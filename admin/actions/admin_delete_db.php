<?php
require_once '../includes/auth_check.php';

// บังคับส่งคำขอผ่าน HTTP POST เท่านั้น ป้องกันการเข้าถึงผ่าน GET โดยตรง
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "ไม่อนุญาตให้ดำเนินการผ่านวิธีนี้ (Method Not Allowed)";
    header("Location: ../admins.php");
    exit();
}

// ตรวจสอบความปลอดภัย CSRF Token
require_csrf_token();

// ตรวจสอบสิทธิ์ (RBAC) - อนุญาตเฉพาะ Super Admin
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Super Admin') {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงการทำงานนี้";
    header("Location: ../index.php");
    exit();
}

$admin_id_to_delete = intval($_POST['id'] ?? 0);

if ($admin_id_to_delete <= 0) {
    header("Location: ../admins.php");
    exit();
}

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
