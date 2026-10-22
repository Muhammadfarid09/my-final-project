<?php
require_once 'includes/auth_check.php';

try {
    // ดึงข้อมูลการยกเลิก เชื่อมกับข้อมูลการจอง ชื่อสมาชิก สนาม และข้อมูลการชำระเงิน
    $sql = "SELECT c.*, b.booking_date, b.booking_start_time, b.booking_end_time, b.booking_total_price, b.booking_status,
                   ct.court_name,
                   m.member_name, m.member_phone, a.admin_name,
                   p.payment_slip_image, p.payment_status, p.payment_amount
            FROM CANCELLATION c
            JOIN BOOKING b ON c.booking_id = b.booking_id
            LEFT JOIN COURT ct ON b.court_id = ct.court_id
            JOIN MEMBER m ON b.member_id = m.member_id
            LEFT JOIN ADMIN a ON c.admin_id = a.admin_id
            LEFT JOIN PAYMENT p ON b.booking_id = p.booking_id
            ORDER BY c.cancel_date DESC";
    $stmt = $conn->query($sql);
    $cancellations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // สรุปตัวเลขสถิติภาพรวม (KPI Summary)
    $total_cancellations = count($cancellations);
    $pending_refund_count = 0;
    $refunded_count = 0;
    $no_refund_count = 0;

    // คำนวณค่าปรับที่แนะนำ (อ้างอิงตามกฎการยกเลิก)
    foreach ($cancellations as &$row) {
        if ($row['refund_status'] === 'รอดำเนินการ') {
            $pending_refund_count++;
        } elseif ($row['refund_status'] === 'คืนแล้ว') {
            $refunded_count++;
        } else {
            $no_refund_count++;
        }

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
$page_header = '<i class="fas fa-ban"></i> ระบบพิจารณาและประวัติการยกเลิกการจอง';
include 'includes/header.php';
?>

<!-- กล่องสรุปสถานะการยกเลิก (KPI Summary Cards) -->
<div class="stats-grid mb-25 stat-card-grid">
    <div class="stat-card stat-card-box border-blue">
        <div class="stat-card-title"><i class="fas fa-list-alt text-primary"></i> รายการยกเลิกทั้งหมด</div>
        <div class="stat-card-num text-blue"><?php echo number_format($total_cancellations); ?> <span class="stat-card-unit">รายการ</span></div>
    </div>
    <div class="stat-card stat-card-box border-yellow">
        <div class="stat-card-title"><i class="fas fa-hourglass-half text-warning"></i> รอพิจารณาคืนเงิน</div>
        <div class="stat-card-num text-yellow"><?php echo number_format($pending_refund_count); ?> <span class="stat-card-unit">รายการ</span></div>
    </div>
    <div class="stat-card stat-card-box border-green">
        <div class="stat-card-title"><i class="fas fa-check-circle text-success"></i> โอนเงินคืนแล้ว</div>
        <div class="stat-card-num text-green"><?php echo number_format($refunded_count); ?> <span class="stat-card-unit">รายการ</span></div>
    </div>
    <div class="stat-card stat-card-box border-red">
        <div class="stat-card-title"><i class="fas fa-ban text-danger"></i> ไม่ต้องคืนเงิน / ไม่อนุมัติ</div>
        <div class="stat-card-num text-red"><?php echo number_format($no_refund_count); ?> <span class="stat-card-unit">รายการ</span></div>
    </div>
</div>

<div class="admin-card">
    <div class="header-between mb-20">
        <h4 class="m-0 text-dark-custom"><i class="fas fa-list"></i> ประวัติคำขอยกเลิกและการพิจารณาคืนเงิน</h4>
    </div>

    <div class="table-responsive">
        <table class="admin-table table-cancellations">
            <thead>
                <tr>
                    <th style="width: 18%;">รหัส &amp; ข้อมูลการจอง</th>
                    <th style="width: 17%;">ผู้จอง</th>
                    <th style="width: 22%;">บัญชีรับเงินคืน</th>
                    <th style="width: 20%;">เหตุผล &amp; วันที่ยกเลิก</th>
                    <th style="width: 12%;" class="text-center">สถานะคืนเงิน</th>
                    <th style="width: 11%;" class="text-center">จัดการ</th>
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
                        <!-- 1. รหัส & ข้อมูลการจอง -->
                        <td>
                            <div class="bk-code-pill">#BK<?php echo $row['booking_id']; ?></div>
                            <?php if (!empty($row['court_name'])): ?>
                                <div class="bk-court-name"><i class="fas fa-table-tennis text-muted"></i> <?php echo htmlspecialchars($row['court_name']); ?></div>
                            <?php endif; ?>
                            <div class="text-small-muted mt-2">
                                <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($row['booking_date'])); ?>
                            </div>
                            <div class="text-small-muted">
                                <i class="far fa-clock"></i> <?php echo date('H:i', strtotime($row['booking_start_time'])); ?><?php echo !empty($row['booking_end_time']) ? ' - ' . date('H:i', strtotime($row['booking_end_time'])) : ''; ?>
                            </div>
                            <div class="bk-total-price mt-4">
                                ยอดจอง: <strong><?php echo number_format($row['booking_total_price'], 2); ?> ฿</strong>
                            </div>
                        </td>

                        <!-- 2. ข้อมูลผู้จอง -->
                        <td>
                            <div class="member-name-text">
                                <i class="far fa-user text-muted"></i> <strong><?php echo htmlspecialchars($row['member_name']); ?></strong>
                            </div>
                            <?php if (!empty($row['member_phone'])): ?>
                                <div class="text-small-muted mt-2">
                                    <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($row['member_phone']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($row['payment_slip_image'])): ?>
                                <div class="mt-6">
                                    <a href="../uploads/slips/<?php echo htmlspecialchars($row['payment_slip_image']); ?>" target="_blank" class="btn-slip-pill" title="คลิกดูภาพสลิปที่โอนเข้ามา">
                                        <i class="fas fa-receipt"></i> สลิปโอนเข้า
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- 3. บัญชีรับเงินคืน -->
                        <td>
                            <?php if (!empty($row['refund_account_no'])): ?>
                                <div class="cancellation-refund-box-modern">
                                    <div class="refund-bank-tag">
                                        <i class="fas fa-university"></i> <?php echo htmlspecialchars($row['refund_bank']); ?>
                                    </div>
                                    <div class="refund-acc-no">
                                        <code><?php echo htmlspecialchars($row['refund_account_no']); ?></code>
                                    </div>
                                    <div class="refund-acc-holder text-small-muted" title="ชื่อเจ้าของบัญชี">
                                        <i class="fas fa-user-check"></i> <?php echo htmlspecialchars($row['refund_account_name']); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-small-muted"><i class="fas fa-minus-circle"></i> ไม่มีบัญชีคืนเงิน</span>
                            <?php endif; ?>
                        </td>

                        <!-- 4. เหตุผล & เวลาที่ยกเลิก -->
                        <td>
                            <div class="cancel-reason-text">
                                <i class="fas fa-quote-left text-muted"></i> <?php echo htmlspecialchars($row['cancel_reason']); ?>
                            </div>
                            <div class="text-small-muted mt-4">
                                <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($row['cancel_date'])); ?>
                            </div>
                            <div class="mt-4">
                                <?php if ($row['cancel_by'] == 'สมาชิก'): ?>
                                    <span class="badge badge-warning"><i class="fas fa-user"></i> ลูกค้า</span>
                                <?php else: ?>
                                    <span class="badge badge-info"><i class="fas fa-user-shield"></i> แอดมิน</span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- 5. สถานะการคืนเงิน -->
                        <td class="text-center">
                            <?php 
                                if ($row['refund_status'] == 'รอดำเนินการ') {
                                    if ($is_confirmed_payment) {
                                        echo '<span class="badge badge-warning"><i class="fas fa-hourglass-half"></i> รอพิจารณาคืนเงิน</span><div class="text-11 text-warning mt-2 font-medium">(ชำระเงินแล้ว)</div>';
                                    } elseif ($has_slip) {
                                        echo '<span class="badge badge-info"><i class="fas fa-receipt"></i> รอตรวจสลิป</span><div class="text-11 text-info mt-2 font-medium">(คืน 100%)</div>';
                                    } else {
                                        echo '<span class="badge badge-secondary"><i class="fas fa-ban"></i> ไม่ต้องคืนเงิน</span><div class="text-11 text-muted mt-2 font-medium">(ยังไม่ชำระ)</div>';
                                    }
                                } elseif ($row['refund_status'] == 'คืนแล้ว') {
                                    echo '<span class="badge badge-success"><i class="fas fa-check-circle"></i> โอนคืนแล้ว</span>';
                                    if ($row['refund_point'] > 0) {
                                        echo '<div class="text-11 text-success mt-2 font-medium">(+คืน ' . $row['refund_point'] . ' พอยท์)</div>';
                                    }
                                } else {
                                    echo '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> ไม่ต้องคืนเงิน</span>';
                                }
                            ?>
                        </td>

                        <!-- 6. จัดการ -->
                        <td class="text-center">
                            <?php if ($can_consider_refund): ?>
                                <button type="button" class="btn-sm-info btn-consider-action" onclick="openRefundModal(
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
                                <span class="text-small-muted"><i class="fas fa-ban"></i> ไม่ต้องทำรายการ</span>
                            <?php else: ?>
                                <div class="text-small-muted text-12">
                                    <i class="fas fa-user-check text-success"></i><br>
                                    ผู้บันทึก:<br><strong><?php echo htmlspecialchars($row['admin_name'] ?? '-'); ?></strong>
                                </div>
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
                    <span><strong>ชื่อสมาชิก:</strong></span>
                    <span id="modal_refund_member_name" class="font-bold text-dark">-</span>
                </div>
                <div class="modal-refund-row">
                    <span><strong>เบอร์โทรสมาชิก:</strong></span>
                    <span id="modal_refund_member_phone" class="text-gray">-</span>
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
<script src="../assets/js/admin.js?v=<?php echo filemtime('../assets/js/admin.js'); ?>"></script>
</body>
</html>