<?php
require_once 'includes/auth_check.php';

try {
    // ดึงข้อมูลประวัติการซ่อมสนาม พร้อมเชื่อมตารางเอาชื่อสนามและชื่อแอดมินมาแสดง
    $sql = "SELECT cr.*, c.court_name, a.admin_name 
            FROM COURT_REPAIR cr
            JOIN COURT c ON cr.court_id = c.court_id
            JOIN ADMIN a ON cr.admin_id = a.admin_id
            ORDER BY cr.repair_start_date DESC, cr.repair_id DESC";
    $stmt = $conn->query($sql);
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'ประวัติซ่อมบำรุงสนาม - Admin T.S. Pattani';
$page_header = '<i class=\"fas fa-tools\"></i> ประวัติการซ่อมบำรุงสนาม';
include 'includes/header.php';
?><div class="admin-card">
                <div class="header-between">
                    <h4 class="section-header"><i class="fas fa-history"></i> รายการซ่อมบำรุงทั้งหมด</h4>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>รหัสซ่อม</th>
                            <th>สนาม</th>
                            <th>สาเหตุการซ่อม</th>
                            <th>ระยะเวลาซ่อม</th>
                            <th>ค่าใช้จ่าย (฿)</th>
                            <th class="text-center">ผู้บันทึก</th>
                            <th class="text-center">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($repairs) > 0): ?>
                            <?php foreach ($repairs as $row): ?>
                            <tr>
                                <td>#REP-<?php echo $row['repair_id']; ?></td>
                                <td><strong class="text-primary-bold"><?php echo htmlspecialchars($row['court_name']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($row['repair_cause']); ?>
                                </td>
                                <td>
                                    <span class="text-small-muted"><i class="far fa-calendar-alt"></i> เริ่ม: <?php echo date('d/m/Y', strtotime($row['repair_start_date'])); ?></span><br>
                                    <?php if ($row['repair_status'] == 'เสร็จแล้ว' && $row['repair_actual_end']): ?>
                                        <span class="text-small-muted text-success"><i class="fas fa-check"></i> เสร็จจริง: <?php echo date('d/m/Y', strtotime($row['repair_actual_end'])); ?></span>
                                    <?php else: ?>
                                        <span class="text-small-muted text-danger"><i class="far fa-clock"></i> คาดว่าเสร็จ: <?php echo date('d/m/Y', strtotime($row['repair_expected_end'])); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-danger"><?php echo number_format($row['repair_cost'], 2); ?></strong>
                                </td>
                                <td class="text-center">
                                    <span class="text-small-muted"><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['admin_name']); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['repair_status'] == 'กำลังซ่อม'): ?>
                                        <span class="badge badge-warning"><i class="fas fa-tools"></i> กำลังซ่อม</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> เสร็จแล้ว</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="table-empty-state">
                                <i class="fas fa-tools empty-state-icon"></i>
                                    <span>ยังไม่มีประวัติการซ่อมบำรุงสนาม</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php include 'includes/footer.php'; ?>