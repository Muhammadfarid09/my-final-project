<?php
require_once 'includes/auth_check.php';

try {
    // ดึงประวัติการใช้แต้มแลกของรางวัล
    $sql = "SELECT pt.*, m.member_name, m.member_phone 
            FROM Point_Transaction pt
            JOIN Member m ON pt.member_id = m.member_id
            WHERE pt.transaction_type = 'ใช้' AND pt.transaction_note LIKE 'แลกของรางวัล:%'
            ORDER BY pt.transaction_date DESC";
    $stmt = $conn->query($sql);
    $redemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}

$page_title = 'ประวัติการแลกรางวัล - Admin T.S. Pattani';
$page_header = '<i class="fas fa-gift"></i> ประวัติการแลกของรางวัล';
include 'includes/header.php';
?>

<div class="admin-card">
    <div class="header-between mb-20">
        <h4 class="m-0 text-dark-custom"><i class="fas fa-history"></i> ประวัติการใช้คะแนนสะสม</h4>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>รหัสทำรายการ</th>
                <th>ข้อมูลสมาชิก</th>
                <th>รายละเอียดการแลก</th>
                <th class="text-right">แต้มที่ใช้</th>
                <th class="text-center">วันที่ทำรายการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($redemptions)): ?>
                <?php foreach ($redemptions as $row): ?>
                <tr>
                    <td><strong>#TX<?php echo $row['transaction_id']; ?></strong></td>
                    <td>
                        <div class="font-medium"><?php echo htmlspecialchars($row['member_name']); ?></div>
                        <div class="text-small-muted"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($row['member_phone']); ?></div>
                    </td>
                    <td>
                        <span class="text-primary font-medium">
                            <?php echo htmlspecialchars(str_replace('แลกของรางวัล: ', '', $row['transaction_note'])); ?>
                        </span>
                    </td>
                    <td class="text-right">
                        <strong class="text-danger">-<?php echo number_format($row['transaction_point']); ?></strong>
                    </td>
                    <td class="text-center">
                        <span class="text-small-muted"><?php echo date('d/m/Y H:i', strtotime($row['transaction_date'])); ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="table-empty-state">
                        <i class="fas fa-gift empty-state-icon"></i><br>
                        ยังไม่มีประวัติการแลกของรางวัล
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</main>
</div>
<?php include 'includes/footer.php'; ?>
