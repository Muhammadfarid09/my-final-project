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
    <form action="actions/court_add_db.php" method="POST" id="addCourtForm" onsubmit="return handleAddCourtSubmit(event)">
        <?php echo csrf_field(); ?>
        <!-- โซนที่ 1: ข้อมูลสนามและเวลาเปิด-ปิดทำการ -->
        <div class="court-form-info-grid">
            <div class="form-group court-name-group">
                <label>ชื่อสนาม <span class="text-danger">*</span></label>
                <input type="text" name="court_name" class="form-control" placeholder="เช่น สนามที่ 1" required>
            </div>
            <div class="form-group">
                <label>เวลาเปิดสนาม</label>
                <input type="time" name="court_open_time" class="form-control" value="08:00">
            </div>
            <div class="form-group">
                <label>เวลาปิดสนาม</label>
                <input type="time" name="court_close_time" class="form-control" value="23:00">
            </div>
        </div>
        
        <!-- โซนที่ 2: โครงสร้างราคาค่าเช่าสนาม (3 คอลัมน์สมดุล) -->
        <div class="court-pricing-box">
            <div class="court-pricing-header">
                <i class="fas fa-tags text-muted"></i> กำหนดโครงสร้างราคาค่าเช่าสนาม (บาท/ชั่วโมง)
            </div>
            <div class="form-row-3">
                <div class="form-group mb-0">
                    <label class="text-success"><i class="fas fa-sun text-warning mr-6"></i> ราคากลางวัน จ.-ศ. (08:00-17:00)</label>
                    <input type="number" step="0.01" min="0" name="court_offpeak_price" class="form-control" placeholder="เช่น 150.00">
                </div>
                <div class="form-group mb-0">
                    <label class="text-dark-custom font-weight-600"><i class="fas fa-moon text-primary mr-6"></i> ราคาช่วงเย็น จ.-ศ. (17:00-23:00) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="court_price_per_hour" class="form-control" placeholder="เช่น 180.00" required>
                </div>
                <div class="form-group mb-0">
                    <label class="text-danger"><i class="fas fa-star text-gold mr-6"></i> ราคาวันหยุด ส.-อา. ทุกช่วงเวลา</label>
                    <input type="number" step="0.01" min="0" name="court_peak_price" class="form-control" placeholder="เช่น 200.00">
                </div>
            </div>
        </div>

        <!-- แถบปุ่มดำเนินการ (ชิดขวา สง่างาม สมดุล) -->
        <div class="court-form-actions">
            <button type="reset" class="btn-cancel">
                <i class="fas fa-redo-alt mr-6"></i> ล้างข้อมูล
            </button>
            <button type="submit" class="btn-submit">
                <i class="fas fa-save mr-6"></i> บันทึกข้อมูลสนาม
            </button>
        </div>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>ชื่อสนาม</th>
                <th>เย็น จ.-ศ. (17:00-23:00)</th>
                <th>ส.-อา. / กลางวัน จ.-ศ.</th>
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
                    <td><span class="court-rate-pill"><?php echo number_format($row['court_price_per_hour'], 2); ?> ฿</span></td>
                    <td>
                        <span class="text-small-muted text-rate-peak"><i class="fas fa-star"></i> ส.-อา.: <?php echo $row['court_peak_price'] ? number_format($row['court_peak_price'], 2).' ฿' : '-'; ?></span><br>
                        <span class="text-small-muted text-rate-special"><i class="fas fa-sun"></i> กลางวัน: <?php echo $row['court_offpeak_price'] ? number_format($row['court_offpeak_price'], 2).' ฿' : '-'; ?></span>
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
                            <button type="button" 
                                    class="btn-secondary btn-action-small" 
                                    onclick="showActionConfirmModal({
                                        title: 'ยืนยันการลบสนามแบดมินตัน',
                                        message: 'คุณกำลังจะลบ <strong><?php echo htmlspecialchars(addslashes($row['court_name'])); ?></strong> ออกจากระบบ',
                                        consequence: 'คำเตือน: หากสนามนี้เคยมีประวัติการจองแล้วจะไม่สามารถลบได้ หากไม่มีประวัติ ข้อมูลสนามจะถูกลบถาวร',
                                        confirmText: 'ยืนยันการลบ',
                                        type: 'danger',
                                        actionUrl: 'actions/court_delete_db.php?id=<?php echo $row['court_id']; ?>'
                                    })" 
                                    title="ลบ">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php else: ?>
                            <button type="button" 
                                    class="btn-success" 
                                    onclick="showActionConfirmModal({
                                        title: 'ยืนยันการเปิดใช้งานสนาม',
                                        message: 'ยืนยันว่าการซ่อมบำรุง <strong><?php echo htmlspecialchars(addslashes($row['court_name'])); ?></strong> เสร็จสิ้นเรียบร้อยแล้ว',
                                        consequence: 'สถานะสนามจะเปลี่ยนเป็น &quot;พร้อมใช้งาน&quot; ทันที และสมาชิกจะสามารถทำการจองสนามนี้ได้ตามปกติ',
                                        confirmText: 'ยืนยันเปิดใช้งาน',
                                        type: 'success',
                                        actionUrl: 'actions/court_repair_finish_db.php?court_id=<?php echo $row['court_id']; ?>'
                                    })">
                                <i class="fas fa-check-circle"></i> ซ่อมเสร็จแล้ว
                            </button>
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
        
        <form action="actions/court_repair_add_db.php" method="POST" id="courtRepairForm" onsubmit="return handleRepairSubmit(event)">
            <?php echo csrf_field(); ?>
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
    <div class="modal-content modal-content-lg">
        <h4 class="modal-header"><i class="fas fa-edit"></i> แก้ไขข้อมูลสนาม</h4>
        
        <form action="actions/court_edit_db.php" method="POST" id="courtEditForm" onsubmit="return handleCourtEditSubmit(event)">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="court_id" id="edit_court_id">
            
            <!-- โซนที่ 1: ข้อมูลสนามและเวลาเปิด-ปิดทำการ -->
            <div class="court-form-info-grid">
                <div class="form-group court-name-group">
                    <label>ชื่อสนาม <span class="text-danger">*</span></label>
                    <input type="text" name="court_name" id="edit_court_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>เวลาเปิดสนาม</label>
                    <input type="time" name="court_open_time" id="edit_open_time" class="form-control">
                </div>
                <div class="form-group">
                    <label>เวลาปิดสนาม</label>
                    <input type="time" name="court_close_time" id="edit_close_time" class="form-control">
                </div>
            </div>
            
            <!-- โซนที่ 2: โครงสร้างราคาค่าเช่าสนาม (3 คอลัมน์สมดุล) -->
            <div class="court-pricing-box">
                <div class="court-pricing-header">
                    <i class="fas fa-tags text-muted"></i> กำหนดโครงสร้างราคาค่าเช่าสนาม (บาท/ชั่วโมง)
                </div>
                <div class="form-row-3">
                    <div class="form-group mb-0">
                        <label class="text-success"><i class="fas fa-sun text-warning mr-6"></i> ราคากลางวัน จ.-ศ. (08:00-17:00)</label>
                        <input type="number" step="0.01" min="0" name="court_offpeak_price" id="edit_offpeak_price" class="form-control">
                    </div>
                    <div class="form-group mb-0">
                        <label class="text-dark-custom font-weight-600"><i class="fas fa-moon text-primary mr-6"></i> ราคาช่วงเย็น จ.-ศ. (17:00-23:00) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="court_price_per_hour" id="edit_court_price" class="form-control" required>
                    </div>
                    <div class="form-group mb-0">
                        <label class="text-danger"><i class="fas fa-star text-gold mr-6"></i> ราคาวันหยุด ส.-อา. ทุกช่วงเวลา</label>
                        <input type="number" step="0.01" min="0" name="court_peak_price" id="edit_peak_price" class="form-control">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeCourtEditModal()">ยกเลิก</button>
                <button type="submit" class="btn-confirm">บันทึกการเปลี่ยนแปลง</button>
            </div>
        </form>
    </div>
</div>

<!-- อัปเดตเวอร์ชัน JS -->
<script src="../assets/js/admin.js?v=<?php echo filemtime('../assets/js/admin.js'); ?>"></script>
</body>
</html>