<?php
require_once 'includes/auth_check.php';

// ตรวจสอบสิทธิ์ (RBAC) - อนุญาตเฉพาะ Super Admin
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Super Admin') {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: index.php");
    exit();
}

$page_title = 'เพิ่มผู้ดูแลระบบ - Admin T.S. Pattani';
$page_header = '<i class="fas fa-user-plus"></i> เพิ่มผู้ดูแลระบบคนใหม่';
include 'includes/header.php';
?>

<div class="admin-card max-w-600 mx-auto">
    <div class="mb-20">
        <a href="admins.php" class="text-decor-none text-muted-custom font-500">
            <i class="fas fa-arrow-left"></i> กลับไปหน้ารายชื่อผู้ดูแลระบบ
        </a>
    </div>

    <form action="actions/admin_add_db.php" method="POST">
        <div class="form-group">
            <label for="admin_name">ชื่อ - นามสกุล <span class="text-danger">*</span></label>
            <input type="text" id="admin_name" name="admin_name" class="form-control" required placeholder="เช่น สมชาย ใจดี">
        </div>

        <div class="form-group">
            <label for="admin_phone">เบอร์โทรศัพท์ (ใช้สำหรับเข้าสู่ระบบ) <span class="text-danger">*</span></label>
            <input type="text" id="admin_phone" name="admin_phone" class="form-control" required placeholder="099xxxxxxx" pattern="[0-9]{10}">
            <small class="text-muted-custom d-block mt-5">* เบอร์โทรศัพท์จะต้องไม่ซ้ำกับแอดมินที่มีอยู่แล้วในระบบ</small>
        </div>

        <div class="form-group">
            <label for="admin_password">รหัสผ่าน <span class="text-danger">*</span></label>
            <input type="password" id="admin_password" name="admin_password" class="form-control" required minlength="6" placeholder="อย่างน้อย 6 ตัวอักษร">
        </div>
        
        <div class="form-group">
            <label for="admin_role">ระดับสิทธิ์ <span class="text-danger">*</span></label>
            <select id="admin_role" name="admin_role" class="form-control" required>
                <option value="Admin">Admin (แอดมินทั่วไป)</option>
                <option value="Super Admin">Super Admin (เจ้าของ/ผู้จัดการ)</option>
            </select>
        </div>

        <button type="submit" class="btn-submit w-full p-10 text-16 mt-10">
            <i class="fas fa-save"></i> บันทึกข้อมูล
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
