<?php
require_once 'includes/auth_check.php';

try {
    // ดึงประวัติการจัดซื้อ พร้อมเชื่อมไปเอาชื่อสินค้าและชื่อแอดมินมาแสดง
    $sql = "SELECT pu.*, p.product_name, p.product_image, a.admin_name 
            FROM Purchase pu 
            JOIN Product p ON pu.product_id = p.product_id 
            JOIN Admin a ON pu.admin_id = a.admin_id 
            ORDER BY pu.purchase_date DESC, pu.purchase_id DESC";
    $stmt = $conn->query($sql);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'ประวัติจัดซื้อ/รับของเข้า - Admin T.S. Pattani';
$page_header = '<i class=\"fas fa-truck-loading\"></i> ประวัติจัดซื้อและรับของเข้าสต็อก';
include 'includes/header.php';
?>
<div class="admin-card">
    <div class="header-between mb-20">
        <h4 class="m-0 text-dark-custom"><i class="fas fa-history"></i> รายการจัดซื้อทั้งหมด</h4>
        <a href="purchase_add.php" class="btn-submit text-decor-none">
            <i class="fas fa-plus"></i> รับสินค้าเข้าสต็อก
        </a>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>วันที่รับเข้า</th>
                <th>รายการสินค้า</th>
                <th>จำนวน</th>
                <th>ต้นทุน/หน่วย</th>
                <th>ราคารวมสุทธิ</th>
                <th>ผู้ทำรายการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($purchases) > 0): ?>
                <?php foreach ($purchases as $row): ?>
                <tr>
                    <td><i class="far fa-calendar-alt text-small-muted"></i> <?php echo date('d/m/Y', strtotime($row['purchase_date'])); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br>
                        <span class="text-11 text-muted-custom">#PR-<?php echo $row['purchase_id']; ?></span>
                    </td>
                    <td><strong class="text-primary">+<?php echo $row['purchase_quantity']; ?></strong></td>
                    <td><?php echo number_format($row['purchase_price_per_unit'], 2); ?> ฿</td>
                    <td><strong class="text-danger"><?php echo number_format($row['purchase_total_price'], 2); ?> ฿</strong></td>
                    <td><span class="badge badge-info"><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['admin_name']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="table-empty-state">
                        <i class="fas fa-box-open empty-state-icon"></i><br>
                        ยังไม่มีประวัติการจัดซื้อสินค้า
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>