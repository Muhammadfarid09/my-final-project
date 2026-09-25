<?php
require_once 'includes/auth_check.php';

try {
    // ดึงประวัติการขาย โดยจับกลุ่มบิลที่มีวัน-เวลา (pos_date) ตรงกัน ให้เป็นบิลเดียวกัน
    // ใช้ MIN(pos_id) เพื่อเอา ID หนึ่งตัวไปส่งให้หน้า receipt.php ดึงข้อมูลต่อ
    $sql = "SELECT 
                MIN(pos.pos_id) as ref_pos_id,
                pos.pos_date,
                SUM(pos.pos_quantity) as total_items,
                SUM(pos.pos_total_price) as bill_total,
                a.admin_name
            FROM Pos_sale pos
            JOIN Admin a ON pos.admin_id = a.admin_id
            GROUP BY pos.pos_date, a.admin_name
            ORDER BY pos.pos_date DESC";
            
    $stmt = $conn->query($sql);
    $history_bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'ประวัติการขายหน้าร้าน - Admin T.S. Pattani';
$page_header = '<i class="fas fa-receipt"></i> ประวัติการขายหน้าร้าน (POS)';
include 'includes/header.php';
?>
<div class="admin-card">
    <div class="header-between mb-20">
        <h4 class="m-0 text-dark"><i class="fas fa-list-alt"></i> รายการบิลทั้งหมด</h4>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>วัน-เวลาที่ขาย</th>
                <th class="text-center">จำนวนชิ้นรวม</th>
                <th>ยอดเงินสุทธิ</th>
                <th>พนักงานขาย</th>
                <th class="text-center">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($history_bills) > 0): ?>
                <?php foreach ($history_bills as $bill): ?>
                <tr>
                    <td>
                        <i class="far fa-clock text-small-muted"></i> 
                        <?php echo date('d/m/Y H:i', strtotime($bill['pos_date'])); ?>
                    </td>
                    <td class="text-center">
                        <?php echo $bill['total_items']; ?> ชิ้น
                    </td>
                    <td>
                        <strong class="text-success"><?php echo number_format($bill['bill_total'], 2); ?> ฿</strong>
                    </td>
                    <td>
                        <span class="badge badge-info"><i class="fas fa-user"></i> <?php echo htmlspecialchars($bill['admin_name']); ?></span>
                    </td>
                    <td class="text-center">
                        <!-- ปุ่มกดแล้วเรียก JavaScript ให้เปิดหน้าต่างใบเสร็จ -->
                        <button onclick="openReceipt(<?php echo $bill['ref_pos_id']; ?>)" class="btn-info">
                            <i class="fas fa-print"></i> ดูใบเสร็จ
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="table-empty-state">
                        <i class="fas fa-receipt fa-2x text-muted-custom mb-10"></i><br>
                        ยังไม่มีประวัติการขาย
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>