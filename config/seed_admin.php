<?php
// Script สำหรับสร้างบัญชีผู้ดูแลระบบ (Admin) เริ่มต้น
// ต้องรันผ่าน Command Line (CLI) เท่านั้น เพื่อความปลอดภัย

if (php_sapi_name() !== 'cli') {
    die("⚠️ Error: This script can only be run from the command line.");
}

require_once __DIR__ . '/config.php';

$admin_name = "เจ้าของสนาม (Admin)";
$admin_phone = "0993241657"; 
$admin_password_plain = "admin1234"; 
$admin_role = "Super Admin";

$admin_password_hashed = password_hash($admin_password_plain, PASSWORD_DEFAULT);

try {
    $check_sql = "SELECT * FROM Admin WHERE admin_phone = :phone";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->execute([':phone' => $admin_phone]);
    
    if ($stmt_check->rowCount() > 0) {
        echo "มีบัญชีนี้ในระบบแล้ว\n";
    } else {
        $sql = "INSERT INTO Admin (admin_name, admin_phone, admin_password, admin_role) 
                VALUES (:name, :phone, :password, :role)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $admin_name,
            ':phone' => $admin_phone,
            ':password' => $admin_password_hashed,
            ':role' => $admin_role
        ]);
        echo "🎉 เพิ่มผู้ดูแลระบบคนแรกสำเร็จ!\n";
    }
} catch(PDOException $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
}
?>
