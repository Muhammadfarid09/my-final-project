<?php
require_once 'includes/auth_check.php';

try {
    // ดึงข้อมูลการยกเลิก เชื่อมกับข้อมูลการจองและชื่อสมาชิก
    $sql = "SELECT c.*, b.booking_date, b.booking_start_time, b.booking_total_price, m.member_name, a.admin_name 
            FROM CANCELLATION c
            JOIN BOOKING b ON c.booking_id = b.booking_id
            JOIN MEMBER m ON b.member_id = m.member_id
            LEFT JOIN ADMIN a ON c.admin_id = a.admin_id
            ORDER BY c.cancel_date DESC";
    $stmt = $conn->query($sql);
    $cancellations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // คำนวณค่าปรับที่แนะนำ (อ้างอิงตามกฎการยกเลิก)
    foreach ($cancellations as &$row) {
        $booking_datetime = strtotime($row['booking_date'] . ' ' . $row['booking_start_time']);
        $cancel_datetime = strtotime($row['cancel_date']);
        $diff_hours = ($booking_datetime - $cancel_datetime) / 3600;

        if ($diff_hours > 24) {
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
                <?php foreach ($cancellations as $row): ?>
                <tr>
                    <td><strong>#BK<?php echo $row['booking_id']; ?></strong></td>
                    <td>
                        <i class="far fa-user text-small-muted"></i> <?php echo htmlspecialchars($row['member_name']); ?><br>
                        <i class="far fa-calendar-alt text-small-muted"></i> <?php echo date('d/m/Y', strtotime($row['booking_date'])); ?> 
                        (<?php echo date('H:i', strtotime($row['booking_start_time'])); ?>)<br>
                        <strong class="text-danger"><?php echo number_format($row['booking_total_price'], 2); ?> ฿</strong>
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
                                echo '<span class="badge badge-warning"><i class="fas fa-hourglass-half"></i> รอดำเนินการ</span>';
                            } elseif ($row['refund_status'] == 'คืนแล้ว') {
                                echo '<span class="badge badge-success"><i class="fas fa-check"></i> คืนเงินแล้ว</span><br>';
                                if ($row['refund_point'] > 0) {
                                    echo '<span class="text-11 text-success">(+คืน ' . $row['refund_point'] . ' พอยท์)</span>';
                                }
                            } else {
                                echo '<span class="badge badge-danger"><i class="fas fa-times"></i> ไม่คืนเงิน</span>';
                            }
                        ?>
                    </td>
                    <td class="text-center">
                        <?php if ($row['refund_status'] == 'รอดำเนินการ'): ?>
                            <!-- ปุ่มสำหรับเปิด Modal พิจารณาคืนเงิน -->
                            <button class="btn-sm-info" onclick="openRefundModal(<?php echo $row['cancel_id']; ?>, <?php echo $row['booking_id']; ?>, <?php echo $row['booking_total_price']; ?>, <?php echo $row['penalty_amount']; ?>, <?php echo $row['suggested_refund']; ?>, <?php echo $row['penalty_percent']; ?>)">
                                <i class="fas fa-hand-holding-usd"></i> พิจารณา
                            </button>
                        <?php else: ?>
                            <span class="text-small-muted">ผู้อนุมัติ: <?php echo htmlspecialchars($row['admin_name'] ?? '-'); ?></span>
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
    <div class="modal-content">
        <h4 class="modal-header"><i class="fas fa-hand-holding-usd"></i> พิจารณาการคืนเงิน</h4>
        
        <form action="actions/cancellation_action_db.php" method="POST">
            <input type="hidden" name="cancel_id" id="modal_cancel_id">
            <input type="hidden" name="booking_id" id="modal_booking_id">
            
            <p><strong>ยอดจองที่ยกเลิก:</strong> <span id="modal_booking_price" class="text-danger font-bold text-18">0.00</span> ฿</p>
            
            <div id="penalty_info_box" class="alert-warning p-15 mb-15 border-l-warning">
                <p class="m-0 mb-10"><strong>ค่าปรับยกเลิก (<span id="modal_penalty_percent">0</span>%):</strong> <span id="modal_penalty_amount" class="text-danger font-bold">0.00</span> ฿</p>
                <p class="m-0"><strong>ยอดเงินที่ควรคืนลูกค้า:</strong> <span id="modal_suggested_refund" class="text-success font-bold text-16">0.00</span> ฿</p>
            </div>
            
            <div class="form-group">
                <label>การตัดสินใจพิจารณาคืนเงิน <span class="text-danger">*</span></label>
                <select name="refund_status" id="refund_status" class="form-control" required onchange="togglePointInput()">
                    <option value="">-- เลือกการพิจารณา --</option>
                    <option value="คืนแล้ว">คืนเงิน (โอนเงินสดคืนลูกค้าแล้ว)</option>
                    <option value="ไม่คืน">ไม่คืนเงิน (ผิดเงื่อนไขการยกเลิก)</option>
                </select>
            </div>

            <div class="point-return-box" id="point_return_group">
                <label><i class="fas fa-star"></i> คืนเป็นพอยท์ (ทางเลือกเสริม)</label>
                <p>หากต้องการชดเชยลูกค้าเป็นคะแนนสะสมแทนเงินสด ให้ระบุจำนวนที่นี่</p>
                <input type="number" name="refund_point" class="form-control" min="0" value="0" placeholder="ระบุจำนวนพอยท์">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeRefundModal()">ยกเลิก</button>
                <button type="submit" class="btn-confirm">บันทึกการตัดสินใจ</button>
            </div>
        </form>
    </div>
</div>

<!-- เรียกใช้ไฟล์ JS ส่วนกลาง -->
<script src="../assets/js/admin.js?v=1.20"></script>
</body>
</html>