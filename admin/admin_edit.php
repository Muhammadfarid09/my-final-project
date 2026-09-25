<?php
require_once 'includes/auth_check.php';

// ตรวจสอบสิทธิ์ (RBAC) - อนุญาตเฉพาะ Super Admin
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Super Admin') {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: index.php");
    exit();
}

$admin_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($admin_id <= 0) {
    $_SESSION['error'] = "ไม่พบรหัสผู้ดูแลระบบ";
    header("Location: admins.php");
    exit();
}

// ดึงข้อมูลผู้ดูแลระบบปัจจุบัน
try {
    $stmt = $conn->prepare("SELECT * FROM Admin WHERE admin_id = :id");
    $stmt->execute([':id' => $admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $_SESSION['error'] = "ไม่พบข้อมูลผู้ดูแลระบบ";
        header("Location: admins.php");
        exit();
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

$page_title = 'แก้ไขผู้ดูแลระบบ - Admin T.S. Pattani';
$page_header = '<i class="fas fa-user-edit"></i> แก้ไขข้อมูลผู้ดูแลระบบ';
include 'includes/header.php';
?>

<div class="admin-card max-w-600 mx-auto">
    <div class="mb-20">
        <a href="admins.php" class="text-decor-none text-muted-custom font-500">
            <i class="fas fa-arrow-left"></i> กลับไปหน้ารายชื่อผู้ดูแลระบบ
        </a>
    </div>

    <form action="actions/admin_edit_db.php" method="POST">
        <input type="hidden" name="admin_id" value="<?php echo $admin['admin_id']; ?>">

        <div class="form-group">
            <label for="admin_name">ชื่อ - นามสกุล <span class="text-danger">*</span></label>
            <input type="text" id="admin_name" name="admin_name" class="form-control" value="<?php echo htmlspecialchars($admin['admin_name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="admin_phone">เบอร์โทรศัพท์ (ใช้สำหรับเข้าสู่ระบบ) <span class="text-danger">*</span></label>
            <input type="text" id="admin_phone" name="admin_phone" class="form-control" value="<?php echo htmlspecialchars($admin['admin_phone']); ?>" required pattern="[0-9]{10}">
        </div>

        <div class="form-group">
            <label for="admin_password">รหัสผ่านใหม่ <span class="text-muted-custom">(เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)</span></label>
            <input type="password" id="admin_password" name="admin_password" class="form-control" minlength="6" placeholder="กรอกรหัสผ่านใหม่หากต้องการเปลี่ยน">
        </div>
        
        <div class="form-group">
            <label for="admin_role">ระดับสิทธิ์ <span class="text-danger">*</span></label>
            <select id="admin_role" name="admin_role" class="form-control" required>
                <option value="Admin" <?php echo $admin['admin_role'] == 'Admin' ? 'selected' : ''; ?>>Admin (แอดมินทั่วไป)</option>
                <option value="Super Admin" <?php echo $admin['admin_role'] == 'Super Admin' ? 'selected' : ''; ?>>Super Admin (เจ้าของ/ผู้จัดการ)</option>
            </select>
        </div>

        <button type="submit" class="btn-warning w-full p-10 text-16 mt-10">
            <i class="fas fa-save"></i> บันทึกการแก้ไข
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
