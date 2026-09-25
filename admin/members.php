<?php
require_once 'includes/auth_check.php';

$members = [];
try {
    // ดึงข้อมูลสมาชิกล่าสุดทั้งหมด พร้อมดึงแต้มและระดับจากตาราง Point
    // ใช้ LEFT JOIN เผื่อกรณีที่สมาชิกบางคนเพิ่งสมัครและยังไม่มีข้อมูลในตาราง Point
    $sql = "SELECT m.*, p.point_balance, p.member_level 
            FROM Member m 
            LEFT JOIN Point p ON m.member_id = p.member_id 
            ORDER BY m.member_created_at DESC";
    $stmt = $conn->query($sql);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'จัดการข้อมูลสมาชิก - Admin T.S. Pattani';
$extra_head = <<<HTML
<!-- อัปเดต Cache Busting เป็น v=1.20 ตามมาตรฐานโปรเจกต์เรา -->
HTML;
$page_header = '<i class=\"fas fa-users\"></i> ระบบจัดการข้อมูลสมาชิก';
include 'includes/header.php';
?><div class="admin-card">
                
                <div class="header-between mb-20">
                    <h4 class="m-0 text-dark-custom"><i class="fas fa-list"></i> รายชื่อสมาชิกทั้งหมด</h4>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert-success mb-15">
                        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-error mb-15">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อ - นามสกุล</th>
                            <th>เบอร์ติดต่อ</th>
                            <th>ระดับ (Tier)</th>
                            <th>แต้มสะสม</th>
                            <th>สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($members) > 0): ?>
                            <?php foreach ($members as $row): ?>
                            <tr>
                                <td><span class="text-small-muted">#MB<?php echo $row['member_id']; ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['member_name']); ?></strong><br>
                                    <span class="text-small-muted">สมัครเมื่อ: <?php echo date('d/m/Y', strtotime($row['member_created_at'])); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['member_phone']); ?></td>
                                <td>
                                    <?php 
                                        // ป้องกัน Error กรณี Left Join แล้วเป็นค่าว่าง ให้ใส่ค่าเริ่มต้นเป็น Bronze
                                        $level = $row['member_level'] ?? 'Bronze';
                                        
                                        if ($level == 'Bronze') {
                                            echo '<span class="badge-bronze"><i class="fas fa-medal"></i> Bronze</span>';
                                        } else if ($level == 'Silver') {
                                            echo '<span class="badge-silver"><i class="fas fa-medal"></i> Silver</span>';
                                        } else if ($level == 'Gold') {
                                            echo '<span class="badge-gold"><i class="fas fa-medal"></i> Gold</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <strong class="text-success text-16">
                                        <?php echo number_format($row['point_balance'] ?? 0); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($row['member_status'] == 'ปกติ'): ?>
                                        <span class="badge badge-success">ปกติ</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">ระงับสิทธิ์</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="member_edit.php?id=<?php echo $row['member_id']; ?>" class="btn-secondary btn-action-small">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="table-empty-state">ยังไม่มีสมาชิกในระบบ</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php include 'includes/footer.php'; ?>