<?php
require_once 'includes/auth_check.php';

// ตรวจสอบสิทธิ์ (RBAC) - อนุญาตเฉพาะ Super Admin
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Super Admin') {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: index.php");
    exit();
}

// ดึงข้อมูลผู้ดูแลระบบทั้งหมด
try {
    $stmt = $conn->query("SELECT * FROM Admin ORDER BY admin_id ASC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php
$page_title = 'จัดการผู้ดูแลระบบ - Admin T.S. Pattani';
$page_header = '<i class="fas fa-user-shield"></i> จัดการผู้ดูแลระบบ';
include 'includes/header.php';
?>

<div class="admin-card">
    <div class="header-between mb-20">
        <h4 class="section-header m-0"><i class="fas fa-list"></i> รายชื่อผู้ดูแลระบบทั้งหมด</h4>
        <a href="admin_add.php" class="btn-submit text-decor-none">
            <i class="fas fa-plus"></i> เพิ่มผู้ดูแลระบบคนใหม่
        </a>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อ - นามสกุล</th>
                    <th>เบอร์โทรศัพท์ (Username)</th>
                    <th>ระดับสิทธิ์</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($admins) > 0): ?>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo $admin['admin_id']; ?></td>
                            <td><?php echo htmlspecialchars($admin['admin_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['admin_phone']); ?></td>
                            <td>
                                <?php if ($admin['admin_role'] == 'Super Admin'): ?>
                                    <span class="badge badge-success">
                                        <?php echo htmlspecialchars($admin['admin_role']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($admin['admin_role'] ?? 'Admin'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <a href="admin_edit.php?id=<?php echo $admin['admin_id']; ?>" class="btn-secondary btn-action-small" title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($admin['admin_id'] == $_SESSION['admin_id']): ?>
                                    <button class="btn-action-small btn-disabled" disabled title="ไม่สามารถลบตัวเองได้">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <a href="actions/admin_delete_db.php?id=<?php echo $admin['admin_id']; ?>" 
                                       class="btn-danger btn-action-small" 
                                       onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ดูแลระบบ: <?php echo htmlspecialchars($admin['admin_name']); ?> ?');" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">ไม่พบข้อมูลผู้ดูแลระบบ</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
