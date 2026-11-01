<?php
/**
 * T.S. Pattani Badminton System - POS Shift Z-Report View & Print
 * ออกแบบสำหรับพิมพ์ใบสรุปปิดกะสิ้นวันมาตรฐานขนาดสลิปความร้อน 80mm หรือเอกสารทางการ
 */
require_once 'includes/auth_check.php';

$shift_id = intval($_GET['shift_id'] ?? 0);
$auto_print = !empty($_GET['auto_print']);

if ($shift_id <= 0) {
    // ดึงกะล่าสุด
    $stmt_latest = $conn->query("SELECT shift_id FROM Shift_Closeout ORDER BY shift_id DESC LIMIT 1");
    $shift_id = intval($stmt_latest->fetchColumn());
}

if ($shift_id <= 0) {
    $_SESSION['error'] = "ไม่พบข้อมูลประวัติการปิดกะในระบบ";
    header("Location: pos_closeout.php");
    exit();
}

$stmt_shift = $conn->prepare("
    SELECT s.*, a.admin_name, a.admin_role
    FROM Shift_Closeout s
    JOIN Admin a ON s.admin_id = a.admin_id
    WHERE s.shift_id = :id
    LIMIT 1
");
$stmt_shift->execute([':id' => $shift_id]);
$shift = $stmt_shift->fetch(PDO::FETCH_ASSOC);

if (!$shift) {
    $_SESSION['error'] = "ไม่พบข้อมูลกะที่ต้องการพิมพ์";
    header("Location: pos_closeout.php");
    exit();
}

$page_title = 'ใบสรุปปิดกะสิ้นวัน (Z-Report) - ' . htmlspecialchars($shift['shift_code']);
$page_header = '<i class="fas fa-file-invoice-dollar"></i> ใบสรุปปิดกะสิ้นวัน (Z-Report)';
include 'includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/pos-closeout.css?v=<?php echo file_exists('../assets/css/pos-closeout.css') ? filemtime('../assets/css/pos-closeout.css') : time(); ?>">

<div class="header-between mb-20 no-print">
    <div>
        <a href="pos_closeout.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> กลับหน้าระบบปิดกะ
        </a>
        <a href="pos.php" class="btn btn-info ml-10">
            <i class="fas fa-cash-register"></i> หน้าขายหน้าร้าน (POS)
        </a>
    </div>
    <div class="d-flex gap-10">
        <button type="button" onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> สั่งพิมพ์ใบสรุปปิดกะ (Z-Report)
        </button>
    </div>
</div>

<!-- ============================================== -->
<!-- ใบสรุปปิดกะขนาดสลิปความร้อนมาตรฐาน (80mm Z-Report) -->
<!-- ============================================== -->
<div class="zreport-wrapper">
    <div class="zreport-header">
        <h2 class="zreport-title">T.S. BADMINTON PATTANI</h2>
        <p class="zreport-subtitle">สนามแบดมินตันมาตรฐาน BWF ปัตตานี</p>
        <p class="zreport-subtitle">โทร. 099-324-1657</p>
        <span class="zreport-badge">DAILY SHIFT CLOSEOUT (Z-REPORT)</span>
    </div>

    <table class="zreport-meta-table">
        <tr>
            <td class="zreport-meta-label">รหัสกะ (Shift Code):</td>
            <td class="zreport-meta-val"><?php echo htmlspecialchars($shift['shift_code']); ?></td>
        </tr>
        <tr>
            <td class="zreport-meta-label">วันที่ดำเนินการ:</td>
            <td class="zreport-meta-val"><?php echo date('d/m/Y', strtotime($shift['shift_date'])); ?></td>
        </tr>
        <tr>
            <td class="zreport-meta-label">เริ่มกะ (Opened):</td>
            <td class="zreport-meta-val"><?php echo date('d/m/Y H:i', strtotime($shift['opened_at'])); ?> น.</td>
        </tr>
        <tr>
            <td class="zreport-meta-label">ปิดกะ (Closed):</td>
            <td class="zreport-meta-val"><?php echo date('d/m/Y H:i', strtotime($shift['closed_at'])); ?> น.</td>
        </tr>
        <tr>
            <td class="zreport-meta-label">ผู้ปิดกะ (Staff):</td>
            <td class="zreport-meta-val"><?php echo htmlspecialchars($shift['admin_name']); ?> (<?php echo htmlspecialchars($shift['admin_role']); ?>)</td>
        </tr>
    </table>

    <hr class="zreport-divider">

    <div class="zreport-section-title">สรุปรายรับแยกตามแผนก (Departments)</div>
    <div class="zreport-line">
        <span>1. ค่าบริการสนามแบดมินตัน:</span>
        <span><?php echo number_format($shift['court_sales'], 2); ?> ฿</span>
    </div>
    <div class="zreport-line">
        <span>2. ค่าเช่าอุปกรณ์กีฬา:</span>
        <span><?php echo number_format($shift['rental_sales'], 2); ?> ฿</span>
    </div>
    <div class="zreport-line">
        <span>3. สินค้า & เครื่องดื่ม:</span>
        <span><?php echo number_format($shift['product_sales'], 2); ?> ฿</span>
    </div>

    <hr class="zreport-divider">

    <div class="zreport-section-title">สรุปช่องทางชำระเงิน (Payment Methods)</div>
    <div class="zreport-line">
        <span>1. เงินสดหน้าร้าน (Cash Sales):</span>
        <span><?php echo number_format($shift['pos_cash_sales'], 2); ?> ฿</span>
    </div>
    <div class="zreport-line">
        <span>2. โอนเงินหน้าร้าน (POS QR Code):</span>
        <span><?php echo number_format($shift['pos_qr_sales'], 2); ?> ฿</span>
    </div>
    <div class="zreport-line">
        <span>3. โอนเงินจองออนไลน์ (PromptPay):</span>
        <span><?php echo number_format($shift['online_transfer_sales'], 2); ?> ฿</span>
    </div>

    <hr class="zreport-divider-double">

    <div class="zreport-line-bold">
        <span>รายรับรวมทั้งสิ้น (TOTAL GROSS):</span>
        <span><?php echo number_format($shift['total_revenue'], 2); ?> ฿</span>
    </div>
    <div class="zreport-line">
        <span>จำนวนรายการธุรกรรมทั้งหมด:</span>
        <span><?php echo number_format($shift['total_transactions']); ?> รายการ</span>
    </div>

    <hr class="zreport-divider-double">

    <div class="zreport-section-title">การตรวจนับเงินสดในลิ้นชัก (Cash Drawer Audit)</div>
    <div class="zreport-line">
        <span>(+) เงินทอนเริ่มต้นกะ (Opening Float):</span>
        <span><?php echo number_format($shift['opening_cash_float'], 2); ?> ฿</span>
    </div>
    <div class="zreport-line">
        <span>(+) ยอดขายเงินสดหน้าร้าน (Cash Sales):</span>
        <span><?php echo number_format($shift['pos_cash_sales'], 2); ?> ฿</span>
    </div>
    <div class="zreport-line-bold">
        <span>(=) เงินสดที่ควรมีในลิ้นชัก (Expected):</span>
        <span><?php echo number_format($shift['expected_cash'], 2); ?> ฿</span>
    </div>
    <div class="zreport-line-bold">
        <span>(✓) เงินสดที่นับได้จริง (Actual Count):</span>
        <span><?php echo number_format($shift['actual_cash'], 2); ?> ฿</span>
    </div>

    <?php 
    $diff = floatval($shift['cash_difference']);
    $diff_color = '#059669';
    $diff_status = 'ตรงเป๊ะ (BALANCED)';
    if ($diff < 0) {
        $diff_color = '#dc2626';
        $diff_status = 'เงินขาด (SHORT) ' . number_format(abs($diff), 2) . ' ฿';
    } elseif ($diff > 0) {
        $diff_color = '#d97706';
        $diff_status = 'เงินเกิน (OVER) +' . number_format($diff, 2) . ' ฿';
    }
    ?>
    <div class="zreport-line-bold" style="color: <?php echo $diff_color; ?>;">
        <span>ผลต่างเงินสด (Discrepancy):</span>
        <span><?php echo $diff_status; ?></span>
    </div>

    <?php if (!empty($shift['notes'])): ?>
    <hr class="zreport-divider">
    <div class="zreport-section-title">หมายเหตุเพิ่มเติม (Notes)</div>
    <div class="zreport-line text-small-muted">
        <span><?php echo nl2br(htmlspecialchars($shift['notes'])); ?></span>
    </div>
    <?php endif; ?>

    <div class="zreport-signatures">
        <div>
            <div class="zreport-sig-line"></div>
            <div class="text-center font-medium">ลงชื่อ .................................................... ผู้ส่งมอบกะ</div>
            <div class="text-center text-small-muted">( <?php echo htmlspecialchars($shift['admin_name']); ?> )</div>
        </div>
        <div>
            <div class="zreport-sig-line"></div>
            <div class="text-center font-medium">ลงชื่อ .................................................... ผู้รับมอบกะ / เจ้าของสนาม</div>
            <div class="text-center text-small-muted">( .................................................... )</div>
        </div>
    </div>

    <div class="zreport-footer-notes">
        พิมพ์เมื่อ: <?php echo date('d/m/Y H:i:s'); ?> น. • T.S. Badminton POS System
    </div>
</div>

<?php if ($auto_print): ?>
<script>
window.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        window.print();
    }, 600);
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

