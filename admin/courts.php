<?php
require_once 'includes/auth_check.php';

$courts = [];
try {
    $stmt = $conn->query("SELECT * FROM COURT ORDER BY court_id ASC");
    $courts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'จัดการข้อมูลและสถานะสนาม - Admin T.S. Pattani';
$page_header = '<i class="fas fa-map-marker-alt"></i> จัดการข้อมูลและสถานะสนามแบดมินตัน';
include 'includes/header.php';
?>
<div class="admin-card">
    <h4 class="section-header"><i class="fas fa-plus"></i> เพิ่มสนามแบดมินตันใหม่</h4>
    <form action="actions/court_add_db.php" method="POST">
        
        <div class="form-row-4">
            <div class="form-group">
                <label>ชื่อสนาม <span class="text-danger">*</span></label>
                <input type="text" name="court_name" class="form-control" placeholder="เช่น สนามที่ 1" required>
            </div>
            <div class="form-group">
                <label>ราคาปกติ/ชม. (฿) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="court_price_per_hour" class="form-control" placeholder="150.00" required>
            </div>
            <div class="form-group">
                <label>เวลาเปิดสนาม</label>
                <input type="time" name="court_open_time" class="form-control" value="08:00">
            </div>
            <div class="form-group">
                <label>เวลาปิดสนาม</label>
                <input type="time" name="court_close_time" class="form-control" value="22:00">
            </div>
        </div>
        
        <div class="form-row-2 form-box-highlight">
            <div class="form-group">
                <label class="text-danger">ราคาช่วง Peak/ชม. (฿) (ทางเลือก)</label>
                <input type="number" step="0.01" name="court_peak_price" class="form-control" placeholder="เช่น 200.00">
            </div>
            <div class="form-group">
                <label class="text-success">ราคาช่วง Off-peak/ชม. (฿) (ทางเลือก)</label>
                <input type="number" step="0.01" name="court_offpeak_price" class="form-control" placeholder="เช่น 120.00">
            </div>
        </div>

        <button type="submit" class="btn-submit w-100"><i class="fas fa-save"></i> บันทึกข้อมูลสนาม</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>ชื่อสนาม</th>
                <th>ราคาปกติ</th>
                <th>Peak / Off-peak</th>
                <th>เวลาทำการ</th>
                <th class="text-center">สถานะ</th>
                <th class="text-center">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($courts) > 0): ?>
                <?php foreach ($courts as $row): ?>
                <tr>
                    <td>#<?php echo $row['court_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['court_name']); ?></strong></td>
                    <td><?php echo number_format($row['court_price_per_hour'], 2); ?> ฿</td>
                    <td>
                        <span class="text-danger text-small-muted"><i class="fas fa-arrow-up"></i> Peak: <?php echo $row['court_peak_price'] ? number_format($row['court_peak_price'], 2).' ฿' : '-'; ?></span><br>
                        <span class="text-success text-small-muted"><i class="fas fa-arrow-down"></i> Off-peak: <?php echo $row['court_offpeak_price'] ? number_format($row['court_offpeak_price'], 2).' ฿' : '-'; ?></span>
                    </td>
                    <td>
                        <?php echo ($row['court_open_time'] && $row['court_close_time']) ? date('H:i', strtotime($row['court_open_time'])) . ' - ' . date('H:i', strtotime($row['court_close_time'])) : '-'; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($row['court_status'] == 'ว่าง'): ?>
                            <span class="badge badge-success">พร้อมใช้งาน</span>
                        <?php else: ?>
                            <span class="badge badge-danger">ปิดปรับปรุง</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($row['court_status'] == 'ว่าง'): ?>
                            <button onclick="openCourtEditModal(<?php echo $row['court_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['court_name'])); ?>', <?php echo $row['court_price_per_hour']; ?>, '<?php echo htmlspecialchars($row['court_open_time']); ?>', '<?php echo htmlspecialchars($row['court_close_time']); ?>', <?php echo $row['court_peak_price'] ? $row['court_peak_price'] : 'null'; ?>, <?php echo $row['court_offpeak_price'] ? $row['court_offpeak_price'] : 'null'; ?>)" class="btn-warning btn-action-small" title="แก้ไขสนาม">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="openRepairModal(<?php echo $row['court_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['court_name'])); ?>')" class="btn-danger btn-action-small" title="ปิดแจ้งซ่อม">
                                <i class="fas fa-tools"></i>
                            </button>
                            <a href="actions/court_delete_db.php?id=<?php echo $row['court_id']; ?>" class="btn-secondary btn-action-small" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ <?php echo htmlspecialchars(addslashes($row['court_name'])); ?> ?\n\nคำเตือน: หากสนามนี้เคยมีการจองแล้ว จะไม่สามารถลบได้');" title="ลบ">
                            <i class="fas fa-trash-alt"></i></a>
                        <?php else: ?>
                            <a href="actions/court_repair_finish_db.php?court_id=<?php echo $row['court_id']; ?>" class="btn-success" onclick="return confirm('การซ่อมแซมเสร็จสิ้น และพร้อมเปิดใช้งานสนามใช่หรือไม่?');">
                                <i class="fas fa-check-circle"></i> ซ่อมเสร็จแล้ว
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="table-empty-state">ยังไม่มีข้อมูลสนามในระบบ</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</main>
</div>

<!-- Modal สำหรับแจ้งซ่อมสนาม -->
<div id="repairModal" class="modal-overlay">
    <div class="modal-content">
        <h4 class="modal-header text-danger"><i class="fas fa-tools"></i> บันทึกแจ้งซ่อมบำรุงสนาม</h4>
        
        <form action="actions/court_repair_add_db.php" method="POST">
            <input type="hidden" name="court_id" id="modal_court_id">
            
            <p><strong>สนามที่ซ่อม:</strong> <span id="modal_court_name" class="text-primary-bold"></span></p>
            
            <div class="form-group">
                <label>สาเหตุที่ต้องซ่อม <span class="text-danger">*</span></label>
                <textarea name="repair_cause" class="form-control" rows="3" placeholder="เช่น พื้นยางหลุดลอก, หลอดไฟขาด" required></textarea>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label>วันที่เริ่มซ่อม <span class="text-danger">*</span></label>
                    <input type="date" name="repair_start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>คาดว่าจะเสร็จ <span class="text-danger">*</span></label>
                    <input type="date" name="repair_expected_end" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>งบประมาณการซ่อม (บาท)</label>
                <input type="number" step="0.01" name="repair_cost" class="form-control" placeholder="0.00">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeRepairModal()">ยกเลิก</button>
                <button type="submit" class="btn-confirm btn-danger">บันทึกและปิดสนาม</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal สำหรับแก้ไขข้อมูลสนาม -->
<div id="courtEditModal" class="modal-overlay">
    <div class="modal-content">
        <h4 class="modal-header"><i class="fas fa-edit"></i> แก้ไขข้อมูลสนาม</h4>
        
        <form action="actions/court_edit_db.php" method="POST">
            <input type="hidden" name="court_id" id="edit_court_id">
            
            <div class="form-row-2">
                <div class="form-group">
                    <label>ชื่อสนาม <span class="text-danger">*</span></label>
                    <input type="text" name="court_name" id="edit_court_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>ราคาปกติ/ชม. (฿) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="court_price_per_hour" id="edit_court_price" class="form-control" required>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label>เวลาเปิดสนาม</label>
                    <input type="time" name="court_open_time" id="edit_open_time" class="form-control">
                </div>
                <div class="form-group">
                    <label>เวลาปิดสนาม</label>
                    <input type="time" name="court_close_time" id="edit_close_time" class="form-control">
                </div>
            </div>
            
            <div class="form-row-2 form-box-highlight">
                <div class="form-group">
                    <label class="text-danger">ราคาช่วง Peak/ชม. (฿)</label>
                    <input type="number" step="0.01" name="court_peak_price" id="edit_peak_price" class="form-control">
                </div>
                <div class="form-group">
                    <label class="text-success">ราคาช่วง Off-peak/ชม. (฿)</label>
                    <input type="number" step="0.01" name="court_offpeak_price" id="edit_offpeak_price" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeCourtEditModal()">ยกเลิก</button>
                <button type="submit" class="btn-confirm">บันทึกการเปลี่ยนแปลง</button>
            </div>
        </form>
    </div>
</div>

<!-- Script สำหรับ Court Edit Modal -->
<script>
function openCourtEditModal(id, name, price, openTime, closeTime, peakPrice, offpeakPrice) {
    document.getElementById('edit_court_id').value = id;
    document.getElementById('edit_court_name').value = name;
    document.getElementById('edit_court_price').value = price;
    document.getElementById('edit_open_time').value = openTime;
    document.getElementById('edit_close_time').value = closeTime;
    
    if (peakPrice !== null) document.getElementById('edit_peak_price').value = peakPrice;
    if (offpeakPrice !== null) document.getElementById('edit_offpeak_price').value = offpeakPrice;
    
    document.getElementById('courtEditModal').style.display = 'flex';
}

function closeCourtEditModal() {
    document.getElementById('courtEditModal').style.display = 'none';
}
</script>

<!-- อัปเดตเวอร์ชัน JS -->
<script src="../assets/js/admin.js?v=1.20"></script>
</body>
</html>