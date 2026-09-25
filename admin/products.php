<?php
require_once 'includes/auth_check.php';

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'consumable';
$filter_type = ($active_tab == 'rental') ? 'อุปกรณ์เช่า' : 'สินค้าบริโภค';

$products = [];
try {
    if ($active_tab == 'rental') {
        // ใช้ LEFT JOIN เพื่อให้ระบบทำงานได้แม้จะยังไม่มีข้อมูลการซ่อม (หรือไม่มีสินค้า)
        // และใช้ eq_repair_status, eq_repair_qty ตามโครงสร้างใน Data Dictionary ตาราง 13
        $sql = "SELECT p.*, 
                COALESCE((SELECT SUM(er.eq_repair_qty) 
                          FROM Equipment_repair er 
                          WHERE er.product_id = p.product_id 
                          AND er.eq_repair_status = 'กำลังซ่อม'), 0) as stock_in_repair 
                FROM Product p 
                WHERE p.product_type = :type 
                ORDER BY p.product_id DESC";
    } else {
        $sql = "SELECT * FROM Product WHERE product_type = :type ORDER BY product_id DESC";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':type' => $filter_type]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'จัดการสินค้าและอุปกรณ์ - Admin T.S. Pattani';
$page_header = '<i class=\"fas fa-box-open\"></i> จัดการคลังสินค้าและอุปกรณ์เช่า';
include 'includes/header.php';
?><div class="admin-card">
                
                <div class="header-between">
                    <div class="page-tabs">
                        <a href="?tab=consumable" class="tab-btn <?php echo $active_tab == 'consumable' ? 'tab-active' : 'tab-inactive'; ?>">
                            <i class="fas fa-coffee"></i> สินค้าบริโภค
                        </a>
                        <a href="?tab=rental" class="tab-btn <?php echo $active_tab == 'rental' ? 'tab-active' : 'tab-inactive'; ?>">
                            <i class="fas fa-table-tennis"></i> อุปกรณ์เช่า
                        </a>
                    </div>
            
                    <div class="flex-center gap-10">
                        <?php if ($active_tab == 'rental'): ?>
                            <a href="equipment_repairs.php" class="btn-secondary text-decor-none">
                                <i class="fas fa-history"></i> ประวัติการซ่อมอุปกรณ์
                            </a>
                        <?php endif; ?>
                        
                        <a href="product_add.php?type=<?php echo urlencode($filter_type); ?>" class="btn-submit text-decor-none">
                            <i class="fas fa-plus"></i> เพิ่ม<?php echo htmlspecialchars($filter_type); ?>
                        </a>
                    </div>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="text-center" width="70">รูปภาพ</th>
                            <th>ID & ชื่อรายการ</th>
                            <th class="text-center"><?php echo $active_tab == 'rental' ? 'ราคาเช่า (฿)' : 'ราคาขาย (฿)'; ?></th>
                            <th class="text-center"><?php echo $active_tab == 'rental' ? 'สต็อกที่เช่าได้' : 'จำนวนคงเหลือ'; ?></th>
                            
                            <?php if ($active_tab == 'rental'): ?>
                                <th class="text-center">สต็อกที่กำลังซ่อม</th>
                                <th>รายละเอียด</th>
                            <?php endif; ?>
                            
                            <th class="text-center" width="180">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $row): ?>
                            <tr>
                                <td class="text-center">
                                    <?php if (!empty($row['product_image'])): ?>
                                        <img src="../uploads/products/<?php echo htmlspecialchars($row['product_image']); ?>" class="product-img-thumbnail" alt="Product">
                                    <?php else: ?>
                                        <div class="product-img-placeholder mx-auto">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-small-muted">#PD<?php echo $row['product_id']; ?></span><br>
                                    <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                                    <?php if ($active_tab == 'rental' && !empty($row['product_brand'])) echo "<br><span class='text-small-muted'>ยี่ห้อ: " . htmlspecialchars($row['product_brand']) . "</span>"; ?>
                                </td>
                                <td class="text-center"><strong class="text-success"><?php echo number_format($row['product_price'], 2); ?></strong></td>
                                
                                <td class="text-center">
                                    <?php 
                                        if ($row['product_stock'] <= $row['product_min_stock']) {
                                            echo "<span class='badge badge-danger'><i class='fas fa-exclamation-triangle'></i> เหลือ " . $row['product_stock'] . " ชิ้น</span>";
                                        } else {
                                            echo "<strong>" . $row['product_stock'] . " ชิ้น</strong>";
                                        }
                                    ?>
                                </td>
                                
                                <?php if ($active_tab == 'rental'): ?>
                                <td class="text-center">
                                    <?php if ($row['stock_in_repair'] > 0): ?>
                                        <span class="badge badge-warning"><i class="fas fa-tools"></i> ซ่อมอยู่ <?php echo $row['stock_in_repair']; ?> ชิ้น</span>
                                    <?php else: ?>
                                        <span class="text-small-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-small-muted">
                                        <?php 
                                            $details = [];
                                            if (!empty($row['product_model'])) $details[] = "รุ่น: " . $row['product_model'];
                                            if (!empty($row['product_size'])) $details[] = "ไซส์: " . $row['product_size'];
                                            echo !empty($details) ? htmlspecialchars(implode(" | ", $details)) : "-";
                                        ?>
                                    </span>
                                </td>
                                <?php endif; ?>

                                <td class="text-center">
                                    
                                    <?php if ($active_tab == 'rental' && $row['product_stock'] > 0): ?>
                                        <button onclick="openEqRepairModal(<?php echo $row['product_id']; ?>, '<?php echo htmlspecialchars($row['product_name']); ?>', <?php echo $row['product_stock']; ?>)" class="btn-danger btn-action-small">
                                            <i class="fas fa-tools"></i> ส่งซ่อม
                                        </button>
                                    <?php endif; ?>

                                    <a href="product_edit.php?id=<?php echo $row['product_id']; ?>" class="btn-warning btn-action-small text-decor-none">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </a>
                                    <a href="actions/product_delete_db.php?id=<?php echo $row['product_id']; ?>&tab=<?php echo $active_tab; ?>" class="btn-danger btn-action-small text-decor-none" onclick="return confirm('ยืนยันการลบ: <?php echo htmlspecialchars($row['product_name']); ?> ?');">
                                        <i class="fas fa-trash"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $active_tab == 'rental' ? '7' : '5'; ?>" class="table-empty-state">
                                    <i class="fas fa-box-open empty-state-icon"></i>
                                    <span>ยังไม่มีรายการในหมวดหมู่นี้</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- Modal สำหรับแจ้งซ่อมอุปกรณ์ (เฉพาะแท็บอุปกรณ์เช่า) -->
    <?php if ($active_tab == 'rental'): ?>
    <div id="eqRepairModal" class="modal-overlay">
        <div class="modal-content">
            <h4 class="modal-header text-danger"><i class="fas fa-tools"></i> บันทึกส่งซ่อมอุปกรณ์</h4>
            
            <form action="actions/equipment_repair_add_db.php" method="POST">
                <input type="hidden" name="product_id" id="modal_eq_id">
                
                <p><strong>อุปกรณ์ที่ส่งซ่อม:</strong> <span id="modal_eq_name" class="text-primary-bold"></span></p>
                <p class="text-small-muted">สต็อกที่สามารถส่งซ่อมได้สูงสุด: <span id="modal_eq_max_stock">0</span> ชิ้น</p>
                
                <div class="form-group">
                    <label>จำนวนที่ส่งซ่อม (ชิ้น) <span class="text-danger">*</span></label>
                    <input type="number" name="repair_qty" id="modal_eq_qty" class="form-control" min="1" required>
                </div>

                <div class="form-group">
                    <label>สาเหตุที่พัง / ส่งซ่อม <span class="text-danger">*</span></label>
                    <textarea name="repair_cause" class="form-control" rows="2" placeholder="เช่น เอ็นขาด, ไม้หัก, พื้นรองเท้าหลุด" required></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEqRepairModal()">ยกเลิก</button>
                    <button type="submit" class="btn-confirm btn-danger">ตัดสต็อกและส่งซ่อม</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="../assets/js/admin.js?v=1.20"></script>
</body>
</html>