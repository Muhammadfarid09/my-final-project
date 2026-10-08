<?php
require_once 'includes/auth_check.php';

try {
    // ดึงข้อมูลการยกเลิก เชื่อมกับข้อมูลการจอง ชื่อสมาชิก และข้อมูลการชำระเงิน
    $sql = "SELECT c.*, b.booking_date, b.booking_start_time, b.booking_total_price, b.booking_status,
                   m.member_name, m.member_phone, a.admin_name,
                   p.payment_slip_image, p.payment_status, p.payment_amount
            FROM CANCELLATION c
            JOIN BOOKING b ON c.booking_id = b.booking_id
            JOIN MEMBER m ON b.member_id = m.member_id
            LEFT JOIN ADMIN a ON c.admin_id = a.admin_id
            LEFT JOIN PAYMENT p ON b.booking_id = p.booking_id
            ORDER BY c.cancel_date DESC";
    $stmt = $conn->query($sql);
    $cancellations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // คำนวณค่าปรับที่แนะนำ (อ้างอิงตามกฎการยกเลิก)
    foreach ($cancellations as &$row) {
        $booking_datetime = strtotime($row['booking_date'] . ' ' . $row['booking_start_time']);
        $cancel_datetime = strtotime($row['cancel_date']);
        $diff_hours = ($booking_datetime - $cancel_datetime) / 3600;

        // ถ้าเป็นการยกเลิกรายการที่ยังไม่ได้รับการอนุมัติ (รอตรวจสอบ) แต่มีสลิปแนบมา
        // แนะนำคืนเงิน 100% (ค่าปรับ 0%) เพราะร้านยังไม่ได้อนุมัติและปลดล็อกสนามให้ทันที
        if ($row['payment_status'] === 'รอตรวจสอบ' && !empty($row['payment_slip_image'])) {
            $penalty_percent = 0;
        } elseif ($diff_hours > 24) {
            $penalty_percent = 0;
        } elseif ($diff_hours >= 12) {
            $penalty_percent = 30;
        } elseif ($diff_hours >= 0) {
            $penalty_percent = 50;
        } else {
            $penalty_percent = 100;
        }

        $row['penalty_percent'] = $penalty_percent;
        $row['penalty_amount'] = ($row['booking_total_price'] * $penalty_percent) / 100;
        $row['suggested_refund'] = $row['booking_total_price'] - $row['penalty_amount'];
    }
    unset($row);

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'จัดการการยกเลิกการจอง - Admin T.S. Pattani';
$page_header = '<i class="fas fa-ban"></i> ระบบจัดการสิทธิ์และเงื่อนไขการยกเลิก';
include 'includes/header.php';
?>
<div class="admin-card">
    <div class="header-between mb-20">
        <h4 class="m-0 text-dark-custom"><i class="fas fa-list"></i> ประวัติคำขอยกเลิกการจอง</h4>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>รหัสจอง</th>
                <th>รายละเอียดการจอง</th>
                <th>เหตุผลการยกเลิก</th>
                <th class="text-center">ผู้ยกเลิก</th>
                <th class="text-center">สถานะคืนเงิน</th>
                <th class="text-center">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($cancellations) > 0): ?>
                <?php foreach ($cancellations as $row): 
                    $is_confirmed_payment = ($row['payment_status'] === 'ยืนยันแล้ว');
                    $has_slip = (!empty($row['payment_slip_image']));
                    $is_pending_refund = ($row['refund_status'] === 'รอดำเนินการ');
                    $can_consider_refund = ($is_pending_refund && ($is_confirmed_payment || $has_slip));
                ?>
                <tr>
                    <td><strong>#BK<?php echo $row['booking_id']; ?></strong></td>
                    <td>
                        <i class="far fa-user text-small-muted"></i> <strong><?php echo htmlspecialchars($row['member_name']); ?></strong><br>
                        <?php if (!empty($row['member_phone'])): ?>
                            <span class="text-small-muted"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($row['member_phone']); ?></span><br>
                        <?php endif; ?>
                        <i class="far fa-calendar-alt text-small-muted"></i> <?php echo date('d/m/Y', strtotime($row['booking_date'])); ?> 
                        (<?php echo date('H:i', strtotime($row['booking_start_time'])); ?>)<br>
                        <strong class="text-danger"><?php echo number_format($row['booking_total_price'], 2); ?> ฿</strong>

                        <?php if (!empty($row['refund_account_no'])): ?>
                            <div class="cancellation-refund-box">
                                <strong class="text-success"><i class="fas fa-university"></i> <?php echo htmlspecialchars($row['refund_bank']); ?>:</strong><br>
                                <span class="cancellation-account-no"><?php echo htmlspecialchars($row['refund_account_no']); ?></span><br>
                                <span class="text-gray"><?php echo htmlspecialchars($row['refund_account_name']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($row['payment_slip_image'])): ?>
                            <div class="cancellation-mt-5">
                                <a href="../uploads/slips/<?php echo htmlspecialchars($row['payment_slip_image']); ?>" target="_blank" class="text-11 text-primary"><i class="fas fa-receipt"></i> สลิปที่โอนเข้ามา</a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="text-gray text-13"><?php echo htmlspecialchars($row['cancel_reason']); ?></span><br>
                        <span class="text-small-muted"><?php echo date('d/m/Y H:i', strtotime($row['cancel_date'])); ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($row['cancel_by'] == 'สมาชิก'): ?>
                            <span class="badge badge-warning">ลูกค้า</span>
                        <?php else: ?>
                            <span class="badge badge-info">แอดมิน</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php 
                            if ($row['refund_status'] == 'รอดำเนินการ') {
                                if ($is_confirmed_payment) {
                                    echo '<span class="badge badge-warning"><i class="fas fa-hourglass-half"></i> รอพิจารณาคืนเงิน (จองแล้ว)</span>';
                                } elseif ($has_slip) {
                                    echo '<span class="badge badge-info"><i class="fas fa-receipt"></i> รอตรวจสลิป & คืนเงิน 100%</span>';
                                } else {
                                    echo '<span class="badge badge-secondary"><i class="fas fa-ban"></i> ไม่ต้องคืนเงิน (ยังไม่จ่าย)</span>';
                                }
                            } elseif ($row['refund_status'] == 'คืนแล้ว') {
                                echo '<span class="badge badge-success"><i class="fas fa-check"></i> โอนคืนแล้ว</span><br>';
                                if ($row['refund_point'] > 0) {
                                    echo '<span class="text-11 text-success">(+คืน ' . $row['refund_point'] . ' พอยท์)</span>';
                                }
                            } else {
                                echo '<span class="badge badge-danger"><i class="fas fa-times"></i> ไม่ต้องคืนเงิน</span>';
                            }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php if ($can_consider_refund): ?>
                            <!-- ปุ่มสำหรับเปิด Modal พิจารณาคืนเงิน (ทั้งรายการที่ชำระเงินแล้ว หรือแนบสลิปรอตรวจสอบ) -->
                            <button class="btn-sm-info" onclick="openRefundModal(
                                <?php echo $row['cancel_id']; ?>, 
                                <?php echo $row['booking_id']; ?>, 
                                <?php echo $row['booking_total_price']; ?>, 
                                <?php echo $row['penalty_amount']; ?>, 
                                <?php echo $row['suggested_refund']; ?>, 
                                <?php echo $row['penalty_percent']; ?>,
                                '<?php echo htmlspecialchars(addslashes($row['member_name'])); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['member_phone'] ?? '')); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['payment_slip_image'] ?? '')); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['refund_bank'] ?? '')); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['refund_account_no'] ?? '')); ?>',
                                '<?php echo htmlspecialchars(addslashes($row['refund_account_name'] ?? '')); ?>'
                            )">
                                <i class="fas fa-hand-holding-usd"></i> พิจารณา
                            </button>
                        <?php elseif ($is_pending_refund && !$is_confirmed_payment && !$has_slip): ?>
                            <span class="text-small-muted"><i class="fas fa-ban"></i> ไม่ต้องคืนเงิน</span>
                        <?php else: ?>
                            <span class="text-small-muted">ผู้บันทึก: <?php echo htmlspecialchars($row['admin_name'] ?? '-'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="table-empty-state">
                        <i class="fas fa-file-invoice empty-state-icon"></i><br>
                        ยังไม่มีประวัติการยกเลิกการจอง
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</main>
</div>

<!-- Modal สำหรับพิจารณาคืนเงิน  -->
<div id="refundModal" class="modal-overlay">
    <div class="modal-content max-w-500">
        <h4 class="modal-header"><i class="fas fa-university"></i> พิจารณาการคืนเงิน (โอนเงินคืนลูกค้า)</h4>
        
        <form action="actions/cancellation_action_db.php" method="POST" id="refundForm" onsubmit="return confirmRefundDecision(event)">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="cancel_id" id="modal_cancel_id">
            <input type="hidden" name="booking_id" id="modal_booking_id">
            <input type="hidden" name="refund_amount" id="modal_refund_amount_input" value="0">
            
            <!-- ข้อมูลบัญชีสำหรับโอนเงินคืนลูกค้า (Customer Refund Account Details) -->
            <div class="modal-refund-card">
                <div class="modal-refund-title">
                    <i class="fas fa-money-check-alt text-primary"></i> ข้อมูลบัญชีรับเงินคืนของลูกค้า
                </div>
                <div class="modal-refund-row">
                    <span><strong>ธนาคาร/ช่องทาง:</strong></span>
                    <span id="modal_refund_bank" class="badge badge-info font-bold">-</span>
                </div>
                <div class="modal-refund-row">
                    <span><strong>เลขที่บัญชี / พร้อมเพย์:</strong></span>
                    <div class="modal-refund-val-group">
                        <span id="modal_refund_acc_no" class="text-primary font-bold modal-refund-acc-text">-</span>
                        <button type="button" class="btn-sm-secondary modal-refund-btn-copy" onclick="copyRefundAccount()" title="คัดลอกเลขบัญชี">
                            <i class="far fa-copy"></i> คัดลอก
                        </button>
                    </div>
                </div>
                <div class="modal-refund-row">
                    <span><strong>ชื่อเจ้าของบัญชี:</strong></span>
                    <span id="modal_refund_acc_name" class="font-bold text-dark">-</span>
                </div>
                <div class="modal-refund-row">
                    <span><strong>เบอร์โทรสมาชิก:</strong></span>
                    <span id="modal_refund_member_phone" class="text-gray">-</span>
                </div>
                <div class="modal-refund-slip-wrap">
                    <strong>สลิปหลักฐานต้นทาง:</strong> <span id="modal_refund_slip_link">-</span>
                    <div id="modal_slip_preview_box" class="modal-refund-slip-preview-box">
                        <a id="modal_slip_preview_link" href="#" target="_blank" title="คลิกเพื่อดูรูปขนาดเต็ม">
                            <img id="modal_slip_img" src="" class="modal-refund-slip-img" alt="สลิปโอนเงิน">
                        </a>
                        <div class="modal-refund-slip-hint"><i class="fas fa-search-plus"></i> คลิกที่รูปเพื่อขยายเต็มจอ</div>
                    </div>
                </div>
            </div>

            <div class="modal-refund-form-group">
                <p class="m-0"><strong>ยอดเงินค่าจอง:</strong> <span id="modal_booking_price" class="text-dark font-bold text-16">0.00</span> ฿</p>
            </div>
            
            <div id="penalty_info_box" class="alert-warning p-15 mb-15 border-l-warning">
                <p class="m-0 mb-8"><strong>ค่าปรับยกเลิก (<span id="modal_penalty_percent">0</span>%):</strong> <span id="modal_penalty_amount" class="text-danger font-bold">0.00</span> ฿</p>
                <p class="m-0"><strong>ยอดเงินที่ต้องโอนคืนลูกค้า:</strong> <span id="modal_suggested_refund" class="text-success font-bold text-18">0.00</span> ฿</p>
            </div>
            
            <div class="form-group">
                <label>การตัดสินใจพิจารณาคืนเงิน <span class="text-danger">*</span></label>
                <select name="refund_status" id="refund_status" class="form-control" required onchange="togglePointInput()">
                    <option value="">-- เลือกการพิจารณา --</option>
                    <option value="คืนแล้ว">โอนเงินคืนเรียบร้อยแล้ว (ตรวจสลิปแล้ว / โอนเข้าบัญชีลูกค้าแล้ว)</option>
                    <option value="ไม่คืน">ไม่คืนเงิน (สลิปไม่ถูกต้อง / ไม่พบยอดเงินเข้าบัญชี / ผิดเงื่อนไข)</option>
                </select>
            </div>

            <div class="point-return-box" id="point_return_group">
                <label><i class="fas fa-star"></i> คืนเป็นพอยท์ (ทางเลือกเสริม)</label>
                <p class="text-small-muted mb-6">หากต้องการชดเชยลูกค้าเป็นคะแนนสะสม ให้ระบุจำนวนที่นี่</p>
                <input type="number" name="refund_point" class="form-control" min="0" value="0" placeholder="ระบุจำนวนพอยท์">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeRefundModal()">ยกเลิก</button>
                <button type="submit" class="btn-confirm">บันทึกผลการโอนคืน</button>
            </div>
        </form>
    </div>
</div>

<!-- เรียกใช้ไฟล์ JS ส่วนกลาง -->
<script src="../assets/js/admin.js?v=1.32"></script>
<script>
function confirmRefundDecision(e) {
    e.preventDefault();
    const status = document.getElementById('refund_status').value;
    if (!status) {
        if (typeof SwalToast === 'function') {
            SwalToast('warning', 'กรุณาเลือกการตัดสินใจพิจารณาคืนเงิน');
        } else {
            alert('กรุณาเลือกการตัดสินใจพิจารณาคืนเงิน');
        }
        return false;
    }
    const refundPrice = document.getElementById('modal_suggested_refund').innerText;
    const points = document.querySelector('input[name="refund_point"]').value || 0;
    const isRefund = (status === 'คืนแล้ว');

    SwalConfirmAction({
        title: isRefund ? 'ยืนยันการโอนเงินคืนลูกค้า?' : 'ยืนยันปฏิเสธการคืนเงิน?',
        message: isRefund 
            ? 'ยืนยันว่าได้โอนเงินคืนลูกค้าผ่านบัญชีธนาคาร/พร้อมเพย์ จำนวน <strong>' + refundPrice + ' ฿</strong> เรียบร้อยแล้ว' + (parseInt(points) > 0 ? ' และคืนแต้มชดเชย <strong>' + points + ' พอยท์</strong>' : '')
            : 'ยืนยันไม่คืนเงินค่าจอง (ปฏิเสธคำขอ)' + (parseInt(points) > 0 ? ' โดยคืนแต้มชดเชย <strong>' + points + ' พอยท์</strong>' : ''),
        consequence: 'การตัดสินใจนี้จะปรับสถานะการจอง ปลดล็อกสนาม คืนสต็อกอุปกรณ์ และปรับปรุงยอดบัญชีโอนคืนเรียบร้อย',
        type: isRefund ? 'success' : 'warning',
        confirmText: '<i class="fas fa-save mr-6"></i> ยืนยันบันทึกผล',
        onConfirm: function() {
            document.getElementById('refundForm').submit();
        }
    });
    return false;
}
</script>
</body>
</html>