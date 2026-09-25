<?php
require_once 'includes/auth_check.php';

try {
    $sql = "SELECT er.*, p.product_name, p.product_image, a.admin_name 
            FROM Equipment_repair er
            JOIN Product p ON er.product_id = p.product_id
            JOIN Admin a ON er.admin_id = a.admin_id
            ORDER BY er.eq_repair_status ASC, er.eq_repair_date DESC";
    $stmt = $conn->query($sql);
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}
?>
<?php
$page_title = 'ประวัติซ่อมบำรุงอุปกรณ์ - Admin T.S. Pattani';
$page_header = '<i class=\"fas fa-tools\"></i> ประวัติการซ่อมบำรุงอุปกรณ์';
include 'includes/header.php';
?><div class="admin-card">
                <div class="header-between">
                    <h4 class="section-header"><i class="fas fa-clipboard-list"></i> รายการส่งซ่อมอุปกรณ์ทั้งหมด</h4>
                    <a href="products.php?tab=rental" class="btn-back-rental">
                    <i class="fas fa-arrow-left"></i>
                         <span>กลับไปหน้าอุปกรณ์เช่า</span>
                    </a>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="70">รหัส</th>
                            <th>อุปกรณ์ที่ซ่อม</th>
                            <th class="text-center">จำนวน (ชิ้น)</th>
                            <th>สาเหตุ / วันที่ส่งซ่อม</th>
                            <th>ค่าใช้จ่าย (฿)</th>
                            <th class="text-center">ผู้บันทึก</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($repairs) > 0): ?>
                            <?php foreach ($repairs as $row): ?>
                            <tr>
                                <td>#EQ-<?php echo $row['eq_repair_id']; ?></td>
                                <td>
                                    <div class="table-item-row">
                                        <?php if (!empty($row['product_image'])): ?>
                                            <img src="../uploads/products/<?php echo htmlspecialchars($row['product_image']); ?>" class="table-img-mini" alt="Product">
                                        <?php else: ?>
                                            <i class="fas fa-image table-icon-mini"></i>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <strong class="text-danger"><?php echo $row['eq_repair_qty']; ?></strong>
                                </td>
                                <td>
                                    <span class="text-small-muted text-block-alert">
                                        <i class="fas fa-exclamation-circle text-danger"></i> <?php echo htmlspecialchars($row['eq_repair_cause']); ?>
                                    </span>
                                    <span class="text-small-muted">
                                        <i class="far fa-calendar-alt"></i> วันที่ส่งซ่อม: <?php echo date('d/m/Y', strtotime($row['eq_repair_date'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['eq_repair_cost'] > 0): ?>
                                        <strong class="text-danger"><?php echo number_format($row['eq_repair_cost'], 2); ?></strong>
                                    <?php else: ?>
                                        <span class="text-small-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="text-small-muted"><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['admin_name']); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['eq_repair_status'] == 'กำลังซ่อม'): ?>
                                        <span class="badge badge-warning"><i class="fas fa-tools"></i> กำลังซ่อม</span>
                                    <?php elseif ($row['eq_repair_status'] == 'ซ่อมแล้ว'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> ซ่อมเสร็จแล้ว</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-trash-alt"></i> เสียหายถาวร</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['eq_repair_status'] == 'กำลังซ่อม'): ?>
                                        <button onclick="openEqFinishModal(<?php echo $row['eq_repair_id']; ?>, '<?php echo htmlspecialchars($row['product_name']); ?>')" class="btn-success btn-action-small">
                                            <i class="fas fa-check-circle"></i> ซ่อมเสร็จแล้ว
                                        </button><br>
                                        <button onclick="openEqBrokenModal(<?php echo $row['eq_repair_id']; ?>, '<?php echo htmlspecialchars($row['product_name']); ?>')" class="btn-secondary btn-action-small">
                                            <i class="fas fa-times-circle"></i> เสียหายถาวร
                                        </button>
                                    <?php else: ?>
                                        <span class="text-small-muted"><i class="fas fa-lock"></i> ดำเนินการแล้ว</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="table-empty-state">
                                    <i class="fas fa-tools empty-state-icon"></i>
                                    <span>ยังไม่มีประวัติการส่งซ่อมอุปกรณ์</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal 1: ซ่อมเสร็จสมบูรณ์ -->
    <div id="eqFinishModal" class="modal-overlay">
        <div class="modal-content">
            <h4 class="modal-header text-success"><i class="fas fa-check-circle"></i> ยืนยันการซ่อมอุปกรณ์เสร็จสิ้น</h4>
            
            <form action="actions/equipment_repair_finish_db.php" method="POST">
                <input type="hidden" name="eq_repair_id" id="modal_finish_id">
                <input type="hidden" name="action_type" value="ซ่อมแล้ว">
                
                <p><strong>อุปกรณ์ที่ซ่อมเสร็จ:</strong> <span id="modal_finish_name" class="text-primary-bold"></span></p>
                <p class="text-success modal-alert-text"><i class="fas fa-info-circle"></i> อุปกรณ์ชิ้นนี้จะถูกนำกลับเข้าสู่สต็อกพร้อมให้เช่าอีกครั้ง</p>
                
                <div class="form-group">
                    <label>ค่าใช้จ่ายในการซ่อม (บาท) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="repair_cost" class="form-control" placeholder="0.00" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEqModal('eqFinishModal')">ยกเลิก</button>
                    <button type="submit" class="btn-confirm btn-success">ยืนยันและคืนสต็อก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: เสียหายถาวร -->
    <div id="eqBrokenModal" class="modal-overlay">
        <div class="modal-content">
            <h4 class="modal-header text-danger"><i class="fas fa-times-circle"></i> บันทึกอุปกรณ์เสียหายถาวร</h4>
            
            <form action="actions/equipment_repair_finish_db.php" method="POST">
                <input type="hidden" name="eq_repair_id" id="modal_broken_id">
                <input type="hidden" name="action_type" value="เสียหายถาวร">
                
                <p><strong>อุปกรณ์ที่ซ่อมไม่ได้:</strong> <span id="modal_broken_name" class="text-primary-bold"></span></p>
                <p class="text-danger modal-alert-text"><i class="fas fa-exclamation-triangle"></i> อุปกรณ์ชิ้นนี้จะถูกตัดออกจากระบบอย่างถาวร และจะไม่ถูกนำกลับเข้าสต็อก</p>
                
                <div class="form-group">
                    <label>ค่าใช้จ่ายที่อาจเกิดขึ้น (บาท) (ถ้ามี)</label>
                    <input type="number" step="0.01" name="repair_cost" class="form-control" value="0.00">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEqModal('eqBrokenModal')">ยกเลิก</button>
                    <button type="submit" class="btn-confirm btn-danger">ยืนยันตัดเป็นของเสีย</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/admin.js?v=1.20"></script>
    
</body>
</html>