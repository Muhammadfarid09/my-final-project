<?php
require_once 'includes/auth_check.php';
require_once 'includes/auto_cancel.php';

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

$payments = [];
try {
    // ดึงข้อมูลการชำระเงิน เชื่อมกับตาราง Booking, Member และ Court เพื่อเอาข้อมูลมาโชว์แอดมิน
    if ($active_tab == 'pending') {
        $status_filter = "p.payment_status = 'รอตรวจสอบ' AND b.booking_status != 'ยกเลิก'";
    } else {
        $status_filter = "p.payment_status != 'รอตรวจสอบ' OR (p.payment_status = 'รอตรวจสอบ' AND b.booking_status = 'ยกเลิก')";
    }

    $sql = "SELECT p.*, 
            b.booking_date, b.booking_start_time, b.booking_end_time, b.booking_total_price, 
            m.member_name, c.court_name, a.admin_name 
            FROM Payment p
            JOIN Booking b ON p.booking_id = b.booking_id
            JOIN Member m ON b.member_id = m.member_id
            JOIN Court c ON b.court_id = c.court_id
            LEFT JOIN Admin a ON p.admin_id = a.admin_id
            WHERE $status_filter
            ORDER BY p.payment_transfer_time ASC"; // เรียงจากสลิปที่ส่งมาก่อนขึ้นก่อน

    $stmt = $conn->query($sql);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}
?>
<?php
$page_title = 'ตรวจสอบการชำระเงิน - Admin T.S. Pattani';
$page_header = '<i class=\"fas fa-file-invoice-dollar\"></i> ตรวจสอบการชำระเงิน (สลิปโอนเงิน)';
include 'includes/header.php';
?><div class="admin-card">
                
                <div class="header-between">
                    <div class="page-tabs">
                        <a href="?tab=pending" class="tab-btn <?php echo $active_tab == 'pending' ? 'tab-active' : 'tab-inactive'; ?>">
                            <i class="fas fa-clock"></i> รอตรวจสอบ
                        </a>
                        <a href="?tab=history" class="tab-btn <?php echo $active_tab == 'history' ? 'tab-active' : 'tab-inactive'; ?>">
                            <i class="fas fa-history"></i> ประวัติการตรวจสอบ
                        </a>
                    </div>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="80">ดูสลิป</th>
                            <th>ข้อมูลการจอง</th>
                            <th>ผู้จอง</th>
                            <th>ยอดที่ต้องจ่าย</th>
                            <th>ยอด/เวลาที่โอนจริง</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($payments) > 0): ?>
                            <?php foreach ($payments as $row): ?>
                            <tr>
                                <td>
                                    <!-- กดที่รูปเล็ก จะไปเปิด Modal ให้ดูสลิปใบใหญ่ๆ -->
                                    <?php 
                                        $slip_name = htmlspecialchars($row['payment_slip_image']); 
                                        $amount = number_format($row['payment_amount'], 2);
                                        $time = date('d/m/Y H:i', strtotime($row['payment_transfer_time']));
                                        $member = htmlspecialchars($row['member_name']);
                                    ?>
                                    <img src="../uploads/slips/<?php echo $slip_name; ?>" class="slip-thumbnail" alt="Slip" 
                                         onclick="openVerifyModal(<?php echo $row['payment_id']; ?>, '<?php echo $slip_name; ?>', '<?php echo $amount; ?>', '<?php echo $time; ?>', <?php echo $row['booking_id']; ?>, '<?php echo $member; ?>')">
                                </td>
                                <td>
                                    <span class="text-small-muted">#BK-<?php echo $row['booking_id']; ?></span><br>
                                    <strong><?php echo htmlspecialchars($row['court_name']); ?></strong><br>
                                    <span class="text-small-muted">
                                        <i class="far fa-calendar"></i> <?php echo date('d/m/Y', strtotime($row['booking_date'])); ?> 
                                        (<?php echo date('H:i', strtotime($row['booking_start_time'])) . ' - ' . date('H:i', strtotime($row['booking_end_time'])); ?>)
                                    </span>
                                </td>
                                <td><i class="fas fa-user text-small-muted"></i> <?php echo $member; ?></td>
                                <td><strong class="text-gray"><?php echo number_format($row['booking_total_price'], 2); ?> ฿</strong></td>
                                <td>
                                    <strong class="text-success"><?php echo $amount; ?> ฿</strong><br>
                                    <span class="text-small-muted"><i class="fas fa-clock"></i> <?php echo $time; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['payment_status'] == 'รอตรวจสอบ'): ?>
                                        <span class="badge badge-warning">รอตรวจสอบ</span>
                                    <?php elseif ($row['payment_status'] == 'ยืนยันแล้ว'): ?>
                                        <span class="badge badge-success">ยืนยันแล้ว</span><br>
                                        <span class="text-small-muted text-11">โดย <?php echo htmlspecialchars($row['admin_name']); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">ปฏิเสธ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['payment_status'] == 'รอตรวจสอบ'): ?>
                                        <button onclick="openVerifyModal(<?php echo $row['payment_id']; ?>, '<?php echo $slip_name; ?>', '<?php echo $amount; ?>', '<?php echo $time; ?>', <?php echo $row['booking_id']; ?>, '<?php echo $member; ?>')" class="btn-submit btn-action-small">
                                            <i class="fas fa-search-dollar"></i> ตรวจสอบ
                                        </button>
                                    <?php else: ?>
                                        <span class="text-small-muted"><i class="fas fa-lock"></i> ตรวจสอบแล้ว</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="table-empty-state">
                                    <i class="fas fa-file-invoice-dollar empty-state-icon"></i>
                                    <span>ไม่มีรายการสลิปที่รอตรวจสอบ</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal สำหรับตรวจสลิปและกดยืนยัน -->
    <div id="verifyPaymentModal" class="modal-overlay">
        <div class="modal-content max-w-500">
            <h4 class="modal-header"><i class="fas fa-search-dollar"></i> ตรวจสอบหลักฐานการโอนเงิน</h4>
            
            <!-- รูปสลิปใบใหญ่ -->
            <div class="slip-preview-box">
                <img id="preview_slip_img" src="" class="slip-preview-img" alt="Payment Slip Preview">
            </div>

            <!-- กล่องแสดงข้อมูลสรุปให้แอดมินเทียบตาเปล่าง่ายๆ -->
            <div class="payment-info-grid">
                <div class="info-box">
                    <div class="text-small-muted">ผู้จอง (รหัสอ้างอิง)</div>
                    <strong id="info_member_name"></strong> <span id="info_booking_id" class="text-small-muted"></span>
                </div>
                <div class="info-box">
                    <div class="text-small-muted">ยอดที่โอนมา</div>
                    <strong class="text-success" id="info_amount"></strong>
                </div>
                <div class="info-box grid-col-span-2">
                    <div class="text-small-muted">วัน/เวลา ตามสลิป</div>
                    <strong id="info_time"></strong>
                </div>
            </div>

            <form action="actions/payment_verify_db.php" method="POST" id="verifyPaymentForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="payment_id" id="modal_payment_id">
                <input type="hidden" name="booking_id" id="modal_booking_id">
                
                <div class="modal-footer flex-between-center mt-20">
                    <!-- กดปุ่มแดง = ปฏิเสธสลิป / ส่งค่า action_status = ปฏิเสธ -->
                    <input type="hidden" name="action_status" id="verify_action_status" value="ยืนยันแล้ว">
                    <button type="button" class="btn-danger" onclick="confirmRejectPayment()">
                        <i class="fas fa-times"></i> ปฏิเสธสลิป
                    </button>
                    
                    <div class="flex-center gap-10">
                        <button type="button" class="btn-cancel" onclick="closeVerifyModal()">ปิด</button>
                        <!-- กดปุ่มเขียว = ยืนยันสลิปผ่าน SweetAlert2 -->
                        <button type="button" onclick="confirmApprovePayment()" class="btn-success">
                            <i class="fas fa-check"></i> ยืนยันยอดถูกต้อง
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- เรียกใช้ไฟล์ JS กลาง -->
    <script src="../assets/js/admin.js?v=<?php echo filemtime('../assets/js/admin.js'); ?>"></script>
    <script>
    (function() {
        let isVerifySubmitting = false;

        window.confirmApprovePayment = function() {
            if (isVerifySubmitting) return;
            let bookingId = document.getElementById('info_booking_id') ? document.getElementById('info_booking_id').innerText : '';
            let memberName = document.getElementById('info_member_name') ? document.getElementById('info_member_name').innerText : '';
            let amount = document.getElementById('info_amount') ? document.getElementById('info_amount').innerText : '';
            
            SwalConfirmAction({
                title: 'ยืนยันยอดชำระเงินถูกต้อง?',
                message: 'ยืนยันว่าการชำระเงินของ <strong>' + memberName + '</strong> (รายการ ' + bookingId + ') ยอดเงิน <strong>' + amount + '</strong> ถูกต้องเรียบร้อยแล้ว',
                consequence: 'สถานะการจองจะเปลี่ยนเป็น "จองแล้ว" และระบบจะบันทึกรายได้พร้อมเพิ่มแต้มสะสมให้สมาชิกทันที',
                confirmText: '<i class="fas fa-check-circle mr-6"></i> ยืนยันยอดถูกต้อง',
                type: 'success',
                onConfirm: function() {
                    if (isVerifySubmitting) return;
                    isVerifySubmitting = true;
                    const buttons = document.querySelectorAll('#verifyPaymentForm button');
                    buttons.forEach(btn => btn.disabled = true);
                    document.getElementById('verify_action_status').value = 'ยืนยันแล้ว';
                    document.getElementById('verifyPaymentForm').submit();
                }
            });
        };

        window.confirmRejectPayment = function() {
            if (isVerifySubmitting) return;
            let bookingId = document.getElementById('info_booking_id') ? document.getElementById('info_booking_id').innerText : '';
            let memberName = document.getElementById('info_member_name') ? document.getElementById('info_member_name').innerText : '';
            
            SwalConfirmDelete({
                title: 'ยืนยันการปฏิเสธสลิปการโอนเงิน?',
                message: 'คุณกำลังจะปฏิเสธสลิปสำหรับรายการจอง <strong>' + bookingId + '</strong> (' + memberName + ')',
                consequence: 'คำเตือน: เมื่อปฏิเสธสลิป รายการจองนี้จะถูกยกเลิก และ Slot เวลาของสนามจะถูกปล่อยคืนสู่ระบบทันที',
                confirmText: 'ยืนยันปฏิเสธสลิป',
                onConfirm: function() {
                    if (isVerifySubmitting) return;
                    isVerifySubmitting = true;
                    const buttons = document.querySelectorAll('#verifyPaymentForm button');
                    buttons.forEach(btn => btn.disabled = true);
                    document.getElementById('verify_action_status').value = 'ปฏิเสธ';
                    document.getElementById('verifyPaymentForm').submit();
                }
            });
        };
    })();
    </script>
</body>
</html>