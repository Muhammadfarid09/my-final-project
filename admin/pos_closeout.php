<?php
/**
 * T.S. Pattani Badminton System - POS Shift Closeout & Cash Drawer Reconciliation
 * หน้าจอสรุปปิดกะการเงิน ตรวจนับเงินสดในลิ้นชัก และจัดการรายงาน Z-Report
 */
require_once 'includes/auth_check.php';

try {
    // 1. ตรวจสอบกะล่าสุดที่ปิดไปแล้ว
    $stmt_last = $conn->query("SELECT MAX(closed_at) FROM Shift_Closeout WHERE status = 'ปิดกะแล้ว'");
    $last_closed = $stmt_last->fetchColumn();
    $opened_at = $last_closed ? $last_closed : date('Y-m-d 00:00:00');
    $closed_at = date('Y-m-d H:i:s');

    // 2. ดึงยอดขายหน้าร้าน (POS Sales) ในช่วงเวลากะนี้
    $stmt_pos = $conn->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN (pos_payment_method = 'cash' OR pos_payment_method IS NULL) THEN pos_total_price ELSE 0 END), 0) AS pos_cash,
            COALESCE(SUM(CASE WHEN pos_payment_method = 'qr' THEN pos_total_price ELSE 0 END), 0) AS pos_qr,
            COALESCE(SUM(pos_total_price), 0) AS pos_total,
            COUNT(DISTINCT pos_date) AS pos_bills_count,
            COUNT(pos_id) AS pos_items_count
        FROM Pos_sale
        WHERE pos_date >= :opened_at AND pos_date <= :closed_at
    ");
    $stmt_pos->execute([':opened_at' => $opened_at, ':closed_at' => $closed_at]);
    $pos_summary = $stmt_pos->fetch(PDO::FETCH_ASSOC);

    $pos_cash = floatval($pos_summary['pos_cash'] ?? 0);
    $pos_qr = floatval($pos_summary['pos_qr'] ?? 0);
    $pos_total = floatval($pos_summary['pos_total'] ?? 0);
    $pos_items_count = intval($pos_summary['pos_items_count'] ?? 0);

    // 3. ดึงยอดเงินโอนออนไลน์ (Online Bookings Approved) ในช่วงเวลากะนี้
    $stmt_online = $conn->prepare("
        SELECT 
            COALESCE(SUM(payment_amount), 0) AS online_total,
            COUNT(payment_id) AS online_count
        FROM Payment
        WHERE payment_status = 'ยืนยันแล้ว'
          AND payment_verified_at >= :opened_at AND payment_verified_at <= :closed_at
    ");
    $stmt_online->execute([':opened_at' => $opened_at, ':closed_at' => $closed_at]);
    $online_summary = $stmt_online->fetch(PDO::FETCH_ASSOC);

    $online_transfer = floatval($online_summary['online_total'] ?? 0);
    $online_count = intval($online_summary['online_count'] ?? 0);

    // 4. ดึงยอดแยกตามแผนกจาก Revenue ของวันนี้
    $stmt_dept = $conn->prepare("
        SELECT 
            COALESCE(SUM(revenue_court_amount), 0) AS court_total,
            COALESCE(SUM(revenue_rental_amount), 0) AS rental_total,
            COALESCE(SUM(revenue_product_amount), 0) AS product_total
        FROM Revenue
        WHERE revenue_date = CURDATE()
    ");
    $stmt_dept->execute();
    $dept_summary = $stmt_dept->fetch(PDO::FETCH_ASSOC);

    $court_total = floatval($dept_summary['court_total'] ?? 0);
    $rental_total = floatval($dept_summary['rental_total'] ?? 0);
    $product_total = floatval($dept_summary['product_total'] ?? 0);

    $shift_gross_total = $pos_cash + $pos_qr + $online_transfer;
    if ($shift_gross_total === 0.00 && ($court_total + $rental_total + $product_total) > 0) {
        $shift_gross_total = $court_total + $rental_total + $product_total;
    }

    // 5. ดึงประวัติการปิดกะก่อนหน้า (Shift History)
    $stmt_history = $conn->query("
        SELECT s.*, a.admin_name 
        FROM Shift_Closeout s
        JOIN Admin a ON s.admin_id = a.admin_id
        ORDER BY s.shift_id DESC
        LIMIT 50
    ");
    $shift_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการโหลดข้อมูลปิดกะ: " . $e->getMessage();
    $shift_history = [];
}

$page_title = 'ระบบสรุปปิดกะการเงิน & Z-Report - Admin T.S. Pattani';
$page_header = '<i class="fas fa-calculator"></i> สรุปปิดกะการเงิน & ออกรายงานสิ้นวัน (Shift Closeout / Z-Report)';
include 'includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/pos-closeout.css?v=<?php echo file_exists('../assets/css/pos-closeout.css') ? filemtime('../assets/css/pos-closeout.css') : time(); ?>">

<div class="closeout-container">
    <!-- ============================================== -->
    <!-- 1. การ์ดสถิติภาพรวมประจำกะ (KPI Shift Overview) -->
    <!-- ============================================== -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #059669;">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label">เงินสดขายหน้าร้าน (Cash Sales)</span>
                <span class="stat-number text-success"><?php echo number_format($pos_cash, 2); ?> ฿</span>
                <span class="text-12 text-gray">ยอดเงินสดที่รับเข้าในกะ</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #2563eb;">
                <i class="fas fa-qrcode"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label">เงินโอนทั้งหมด (QR & Online)</span>
                <span class="stat-number text-primary"><?php echo number_format($pos_qr + $online_transfer, 2); ?> ฿</span>
                <span class="text-12 text-gray">หน้าร้าน <?php echo number_format($pos_qr, 2); ?> + ออนไลน์ <?php echo number_format($online_transfer, 2); ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(212, 175, 55, 0.15); color: #d4af37;">
                <i class="fas fa-cash-register"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label">รายรับรวมกะนี้ (Total Gross)</span>
                <span class="stat-number font-bold text-dark-custom"><?php echo number_format($shift_gross_total, 2); ?> ฿</span>
                <span class="text-12 text-gray">รวมทุกช่องทางชำระเงิน</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #7c3aed;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label">ช่วงเวลากะปัจจุบัน</span>
                <span class="stat-number text-15 font-bold text-dark-custom" style="font-size: 1.05rem;">
                    <?php echo date('H:i', strtotime($opened_at)); ?> - <?php echo date('H:i', strtotime($closed_at)); ?> น.
                </span>
                <span class="text-12 text-gray">เริ่ม <?php echo date('d/m/Y', strtotime($opened_at)); ?></span>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 2. แถบสลับแท็บ (Tabs: Active Shift vs History) -->
    <!-- ============================================== -->
    <div class="page-tabs m-0">
        <button type="button" id="tabBtnActive" class="tab-btn tab-active" onclick="switchShiftTab('active')">
            <i class="fas fa-cash-register"></i> ปิดกะปัจจุบัน (Active Shift Closeout)
        </button>
        <button type="button" id="tabBtnHistory" class="tab-btn tab-inactive" onclick="switchShiftTab('history')">
            <i class="fas fa-history"></i> ประวัติการปิดกะย้อนหลัง (Shift History)
            <span class="badge badge-info ml-6"><?php echo count($shift_history); ?></span>
        </button>
    </div>

    <!-- ============================================== -->
    <!-- TAB 1: ปิดกะปัจจุบัน (Active Shift Closeout)   -->
    <!-- ============================================== -->
    <div id="tabContentActive">
        <div class="closeout-grid">
            <!-- ฝั่งซ้าย: สรุปยอดตามแผนกและช่องทางชำระเงิน -->
            <div class="d-flex flex-column gap-20">
                <!-- สรุปแยกตามแผนก -->
                <div class="admin-card mb-0">
                    <div class="header-between mb-15">
                        <h4 class="m-0 text-dark"><i class="fas fa-layer-group text-primary"></i> สรุปรายรับแยกตามแผนก (Departments)</h4>
                    </div>

                    <ul class="dept-breakdown-list">
                        <li class="dept-breakdown-item">
                            <div class="dept-item-info">
                                <div class="dept-icon-circle dept-icon-court">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h5 class="dept-item-title">1. ค่าบริการสนามแบดมินตัน</h5>
                                    <p class="dept-item-subtitle">จองออนไลน์และเปิดสนาม Walk-in หน้าร้าน</p>
                                </div>
                            </div>
                            <span class="dept-item-amount"><?php echo number_format($court_total, 2); ?> ฿</span>
                        </li>

                        <li class="dept-breakdown-item">
                            <div class="dept-item-info">
                                <div class="dept-icon-circle dept-icon-rental">
                                    <i class="fas fa-table-tennis"></i>
                                </div>
                                <div>
                                    <h5 class="dept-item-title">2. ค่าบริการเช่าอุปกรณ์กีฬา</h5>
                                    <p class="dept-item-subtitle">ไม้แบดมินตัน และอุปกรณ์เสริม</p>
                                </div>
                            </div>
                            <span class="dept-item-amount"><?php echo number_format($rental_total, 2); ?> ฿</span>
                        </li>

                        <li class="dept-breakdown-item">
                            <div class="dept-item-info">
                                <div class="dept-icon-circle dept-icon-product">
                                    <i class="fas fa-coffee"></i>
                                </div>
                                <div>
                                    <h5 class="dept-item-title">3. สินค้าบริโภค & ลูกแบดมินตัน</h5>
                                    <p class="dept-item-subtitle">เครื่องดื่ม, ลูกแบด, กริปพันด้าม</p>
                                </div>
                            </div>
                            <span class="dept-item-amount"><?php echo number_format($product_total, 2); ?> ฿</span>
                        </li>
                    </ul>
                </div>

                <!-- สรุปแยกตามช่องทางชำระเงิน -->
                <div class="admin-card mb-0">
                    <div class="header-between mb-15">
                        <h4 class="m-0 text-dark"><i class="fas fa-wallet text-success"></i> สรุปตามช่องทางชำระเงิน (Payment Methods)</h4>
                    </div>

                    <div class="summary-box">
                        <div class="summary-row mb-10">
                            <span><i class="fas fa-money-bill-wave text-success"></i> เงินสดหน้าร้าน (Cash Sales):</span>
                            <strong class="text-success"><?php echo number_format($pos_cash, 2); ?> ฿</strong>
                        </div>
                        <div class="summary-row mb-10">
                            <span><i class="fas fa-qrcode text-primary"></i> โอนเงินหน้าร้าน (POS QR Code):</span>
                            <strong class="text-primary"><?php echo number_format($pos_qr, 2); ?> ฿</strong>
                        </div>
                        <div class="summary-row mb-15">
                            <span><i class="fas fa-mobile-alt text-info"></i> โอนเงินจองออนไลน์ (PromptPay):</span>
                            <strong class="text-info"><?php echo number_format($online_transfer, 2); ?> ฿</strong>
                        </div>
                        <hr class="divider mb-15">
                        <div class="summary-row summary-total">
                            <span class="font-bold text-dark-custom">รายรับรวมสุทธิทั้งสิ้น:</span>
                            <span class="stat-number text-primary"><?php echo number_format($shift_gross_total, 2); ?> ฿</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ฝั่งขวา: แผงตรวจนับเงินสดในลิ้นชัก (Cash Drawer Reconciliation Panel) -->
            <div class="cash-reconcile-card">
                <div class="header-between mb-10">
                    <h4 class="m-0 text-dark"><i class="fas fa-cash-register text-gold"></i> ตรวจนับเงินสดในลิ้นชัก (Cash Reconciliation)</h4>
                    <span class="badge badge-warning"><i class="fas fa-shield-alt"></i> Z-Report Audit</span>
                </div>
                <p class="text-13 text-gray m-0 mb-15">
                    กรอกจำนวนเงินสดจริงที่นับได้จากลิ้นชักเก็บเงิน เพื่อตรวจสอบความถูกต้องก่อนปิดกะส่งมอบให้เจ้าของสนาม
                </p>

                <form action="actions/pos_closeout_db.php" method="POST" id="closeoutForm" onsubmit="return confirmCloseout(event)">
                    <?php echo csrf_field(); ?>

                    <!-- เงินทอนเริ่มต้นกะ (Opening Cash Float) -->
                    <div class="reconcile-input-group mb-15">
                        <label class="reconcile-label" for="openingCashFloat">
                            <span>(+) เงินทอนเริ่มต้นกะ (Opening Float):</span>
                            <span class="text-12 text-gray">เงินสดตั้งต้นก่อนเริ่มขาย</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="opening_cash_float" id="openingCashFloat" class="reconcile-field" value="0.00" oninput="calculateReconciliation()">
                    </div>

                    <!-- ยอดขายเงินสดหน้าร้าน (POS Cash Sales) -->
                    <div class="reconcile-input-group mb-15">
                        <label class="reconcile-label">
                            <span>(+) ยอดขายเงินสดในกะ (Cash Sales):</span>
                            <span class="text-12 text-success">คำนวณจากบิล POS</span>
                        </label>
                        <input type="text" id="posCashSales" class="reconcile-field" value="<?php echo number_format($pos_cash, 2, '.', ''); ?>" readonly>
                    </div>

                    <!-- ยอดเงินสดที่ควรมีในลิ้นชัก (Expected Cash) -->
                    <div class="reconcile-input-group mb-20">
                        <label class="reconcile-label">
                            <span class="font-bold text-dark-custom">(=) เงินสดที่ควรมีในลิ้นชัก (Expected Cash):</span>
                            <span class="text-12 text-primary font-bold">เงินทอน + ยอดขายเงินสด</span>
                        </label>
                        <input type="text" id="expectedCash" class="reconcile-field font-bold" value="<?php echo number_format($pos_cash, 2, '.', ''); ?>" readonly style="background: #eef2ff; color: #1e3a8a; border-color: #c7d2fe;">
                    </div>

                    <hr class="divider mb-20">

                    <!-- เงินสดที่นับได้จริง (Actual Cash Counted) -->
                    <div class="reconcile-input-group mb-15">
                        <label class="reconcile-label" for="actualCashCounted">
                            <span class="font-bold text-dark-custom" style="font-size: 1.05rem;">
                                <i class="fas fa-hand-holding-usd text-success"></i> (✓) เงินสดที่นับได้จริง (Actual Count):
                            </span>
                            <span class="text-12 text-danger font-bold">* จำเป็นต้องระบุ</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="actual_cash" id="actualCashCounted" class="reconcile-field" placeholder="0.00" required oninput="calculateReconciliation()" style="font-size: 1.35rem; color: #0f172a; border-width: 2px;">
                    </div>

                    <!-- กล่องแสดงผลต่างเงินสด (Difference Result Box) -->
                    <div id="diffBox" class="difference-box diff-balanced mb-20">
                        <div class="diff-label">
                            <i id="diffIcon" class="fas fa-check-circle"></i>
                            <span id="diffStatus">ผลต่างเงินสด: พร้อมตรวจนับ</span>
                        </div>
                        <div class="diff-amount" id="diffAmount">0.00 ฿</div>
                    </div>

                    <!-- หมายเหตุเพิ่มเติม -->
                    <div class="reconcile-input-group mb-20">
                        <label class="reconcile-label" for="closeoutNotes">
                            <span>หมายเหตุเพิ่มเติม (ถ้ามี):</span>
                            <span class="text-12 text-gray">เช่น มีเศษเหรียญ 20 บาท หรือเงินสำรองพิเศษ</span>
                        </label>
                        <textarea name="notes" id="closeoutNotes" class="pos-search-input" rows="2" placeholder="ระบุบันทึกถึงเจ้าของสนามหรือกะถัดไป..."></textarea>
                    </div>

                    <!-- ปุ่มดำเนินการยืนยันปิดกะ -->
                    <button type="submit" id="btnSubmitCloseout" class="btn btn-primary w-100" style="padding: 1rem; font-size: 1.05rem; justify-content: center;">
                        <i class="fas fa-file-invoice-dollar"></i> ยืนยันปิดกะ & ออกรายงาน Z-Report
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- TAB 2: ประวัติการปิดกะย้อนหลัง (Shift History)   -->
    <!-- ============================================== -->
    <div id="tabContentHistory" style="display: none;">
        <div class="admin-card">
            <div class="header-between mb-20">
                <h4 class="m-0 text-dark"><i class="fas fa-history text-primary"></i> ประวัติการปิดกะการเงินย้อนหลัง (Shift History Records)</h4>
                <span class="text-13 text-gray">บันทึกประวัติการส่งมอบกะและยอดกระทบเงินสด</span>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>รหัสกะ (Shift Code)</th>
                        <th>วันที่ & ช่วงเวลา</th>
                        <th>ผู้ปิดกะ</th>
                        <th>รายรับรวม</th>
                        <th>เงินสดที่ควรมี</th>
                        <th>เงินสดจริง</th>
                        <th>ผลต่าง</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($shift_history) > 0): ?>
                        <?php foreach ($shift_history as $s): 
                            $diff_val = floatval($s['cash_difference']);
                        ?>
                        <tr>
                            <td>
                                <strong class="text-dark-custom"><?php echo htmlspecialchars($s['shift_code']); ?></strong>
                            </td>
                            <td>
                                <div><i class="far fa-calendar-alt text-small-muted"></i> <?php echo date('d/m/Y', strtotime($s['shift_date'])); ?></div>
                                <div class="text-12 text-gray">
                                    <?php echo date('H:i', strtotime($s['opened_at'])); ?> - <?php echo date('H:i', strtotime($s['closed_at'])); ?> น.
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info"><i class="fas fa-user"></i> <?php echo htmlspecialchars($s['admin_name']); ?></span>
                            </td>
                            <td>
                                <strong class="text-primary"><?php echo number_format($s['total_revenue'], 2); ?> ฿</strong>
                            </td>
                            <td><?php echo number_format($s['expected_cash'], 2); ?> ฿</td>
                            <td>
                                <strong class="text-dark-custom"><?php echo number_format($s['actual_cash'], 2); ?> ฿</strong>
                            </td>
                            <td>
                                <?php if ($diff_val == 0): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> ตรงเป๊ะ</span>
                                <?php elseif ($diff_val < 0): ?>
                                    <span class="badge badge-danger"><i class="fas fa-minus-circle"></i> ขาด <?php echo number_format(abs($diff_val), 2); ?> ฿</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><i class="fas fa-plus-circle"></i> เกิน +<?php echo number_format($diff_val, 2); ?> ฿</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="pos_zreport.php?shift_id=<?php echo $s['shift_id']; ?>" class="btn btn-secondary btn-sm" title="พิมพ์ใบสรุปปิดกะ">
                                    <i class="fas fa-print"></i> พิมพ์ Z-Report
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-30 text-gray">
                                <i class="fas fa-inbox fa-3x mb-10 d-block text-muted"></i>
                                ยังไม่มีประวัติการปิดกะในระบบ ระบบจะเริ่มบันทึกเมื่อท่านกดยืนยันปิดกะครั้งแรก
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- JavaScript Logic สำหรับแท็บและการคำนวณกระทบยอด -->
<!-- ============================================== -->
<script>
// สลับแท็บระหว่าง Active Shift และ Shift History
function switchShiftTab(tab) {
    const tabActive = document.getElementById('tabContentActive');
    const tabHistory = document.getElementById('tabContentHistory');
    const btnActive = document.getElementById('tabBtnActive');
    const btnHistory = document.getElementById('tabBtnHistory');

    if (tab === 'active') {
        tabActive.style.display = 'block';
        tabHistory.style.display = 'none';
        btnActive.className = 'tab-btn tab-active';
        btnHistory.className = 'tab-btn tab-inactive';
    } else {
        tabActive.style.display = 'none';
        tabHistory.style.display = 'block';
        btnActive.className = 'tab-btn tab-inactive';
        btnHistory.className = 'tab-btn tab-active';
    }
}

// คำนวณกระทบยอดเงินสดในลิ้นชักแบบ Real-time
function calculateReconciliation() {
    const floatInput = parseFloat(document.getElementById('openingCashFloat').value) || 0;
    const posCash = parseFloat(document.getElementById('posCashSales').value) || 0;
    
    // Expected = Float + POS Cash
    const expected = floatInput + posCash;
    document.getElementById('expectedCash').value = expected.toFixed(2);

    const actualInput = document.getElementById('actualCashCounted');
    const actualStr = actualInput.value.trim();

    const diffBox = document.getElementById('diffBox');
    const diffIcon = document.getElementById('diffIcon');
    const diffStatus = document.getElementById('diffStatus');
    const diffAmount = document.getElementById('diffAmount');

    if (actualStr === '') {
        diffBox.className = 'difference-box diff-balanced';
        diffIcon.className = 'fas fa-info-circle';
        diffStatus.innerText = 'กรุณากรอกเงินสดที่นับได้จริง';
        diffAmount.innerText = '0.00 ฿';
        return;
    }

    const actual = parseFloat(actualStr) || 0;
    const diff = actual - expected;

    diffAmount.innerText = (diff >= 0 ? '+' : '') + diff.toFixed(2) + ' ฿';

    if (Math.abs(diff) < 0.01) {
        diffBox.className = 'difference-box diff-balanced';
        diffIcon.className = 'fas fa-check-circle';
        diffStatus.innerText = 'ผลต่างเงินสด: ตรงเป๊ะ (BALANCED)';
    } else if (diff < 0) {
        diffBox.className = 'difference-box diff-short';
        diffIcon.className = 'fas fa-exclamation-triangle';
        diffStatus.innerText = 'ผลต่างเงินสด: เงินขาด (SHORT)';
    } else {
        diffBox.className = 'difference-box diff-over';
        diffIcon.className = 'fas fa-plus-circle';
        diffStatus.innerText = 'ผลต่างเงินสด: เงินเกิน (OVER)';
    }
}

// ป้องกัน Double Submission พร้อมกล่องยืนยันก่อนปิดกะ
function confirmCloseout(event) {
    const actualInput = document.getElementById('actualCashCounted');
    if (!actualInput.value.trim()) {
        alert('กรุณากรอกจำนวนเงินสดที่นับได้จริงในลิ้นชักก่อนยืนยันปิดกะ');
        actualInput.focus();
        event.preventDefault();
        return false;
    }

    const actual = parseFloat(actualInput.value) || 0;
    const expected = parseFloat(document.getElementById('expectedCash').value) || 0;
    const diff = actual - expected;
    let diffMsg = 'ยอดเงินสดตรงเป๊ะ';
    if (diff < 0) diffMsg = 'ยอดเงินสด ขาด ' + Math.abs(diff).toFixed(2) + ' บาท';
    if (diff > 0) diffMsg = 'ยอดเงินสด เกิน +' + diff.toFixed(2) + ' บาท';

    const ok = confirm("ยืนยันการปิดกะการเงินสิ้นวัน?\n\n- เงินสดที่ควรมี: " + expected.toFixed(2) + " บาท\n- เงินสดที่นับได้จริง: " + actual.toFixed(2) + " บาท\n- สถานะ: " + diffMsg + "\n\nเมื่อยืนยันแล้ว ระบบจะบันทึกประวัติและเปิดหน้าพิมพ์ใบสรุป Z-Report ทันที");
    if (!ok) {
        event.preventDefault();
        return false;
    }

    // ป้องกันการกดย้ำ
    const btn = document.getElementById('btnSubmitCloseout');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึกปิดกะ...';
    return true;
}

// เรียกคำนวณเริ่มต้นเมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    calculateReconciliation();
});
</script>

<?php include 'includes/footer.php'; ?>
