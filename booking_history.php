<?php
require_once 'includes/auth_check.php';

$member_id = $_SESSION['member_id'];


try {
    $stmt = $conn->prepare("SELECT b.*, c.court_name, cn.cancel_id, cn.refund_status as cancel_refund_status 
                            FROM BOOKING b 
                            JOIN COURT c ON b.court_id = c.court_id 
                            LEFT JOIN (
                                SELECT booking_id, cancel_id, refund_status 
                                FROM CANCELLATION 
                                WHERE refund_status = 'รอดำเนินการ'
                            ) cn ON b.booking_id = cn.booking_id
                            WHERE b.member_id = :member_id 
                            ORDER BY b.booking_created_at DESC");
    $stmt->execute([':member_id' => $member_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงข้อมูลสมาชิกเพื่อนำมา Auto-fill ในฟอร์มขอยกเลิก (HCI: Avoid Redundant User Input)
    $stmt_mem = $conn->prepare("SELECT member_name, member_phone FROM Member WHERE member_id = :member_id");
    $stmt_mem->execute([':member_id' => $member_id]);
    $current_member = $stmt_mem->fetch(PDO::FETCH_ASSOC);
    $default_member_name = $current_member['member_name'] ?? '';
    $default_member_phone = $current_member['member_phone'] ?? '';

    // ดึงข้อมูลรายการอุปกรณ์/สินค้าที่ผูกกับการจองของสมาชิก เพื่อนำไปแสดงในใบเสร็จ (E-Receipt) แบบ Pre-fetch
    $stmt_items = $conn->prepare("
        SELECT r.booking_id, r.rental_quantity, p.product_name, p.product_price, p.product_type 
        FROM Rental r 
        JOIN Product p ON r.product_id = p.product_id 
        JOIN Booking b ON r.booking_id = b.booking_id 
        WHERE b.member_id = :member_id
    ");
    $stmt_items->execute([':member_id' => $member_id]);
    $booking_items_grouped = $stmt_items->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการจอง - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.7">
    <link rel="stylesheet" href="assets/css/navbar.css?v=<?php echo filemtime('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="assets/css/booking-history.css?v=<?php echo filemtime('assets/css/booking-history.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="page-body">

    <?php include_once 'includes/navbar.php'; ?>

    <main class="history-wrapper">
        
        <div class="booking-header history-header">
            <h2><i class="fas fa-history text-gold"></i> ประวัติการจองสนามและคำสั่งซื้อ</h2>
            <p>ตรวจสอบสถานะการจองสนาม ดูสลิปการโอน หรือขอยกเลิกการจอง</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle fa-lg"></i> <div><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle fa-lg"></i> <div><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <div class="booking-card history-card">
            <?php if (empty($bookings)): ?>
                <div class="history-empty-box">
                    <i class="fas fa-folder-open fa-3x history-empty-icon"></i>
                    <p>ยังไม่มีประวัติการจองสนามในระบบ</p>
                    <a href="booking.php" class="btn-confirm-booking btn-empty-book">ไปจองสนามเลย</a>
                </div>
            <?php else: ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>รหัสจอง</th>
                            <th>สนาม</th>
                            <th>วันที่จอง</th>
                            <th>เวลา</th>
                            <th>ยอดสุทธิ</th>
                            <th>สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bookings as $row): 
                            $status = $row['booking_status'];
                            $slip = $row['payment_slip'] ?? '';
                            
                            $booking_start_ts = strtotime($row['booking_date'] . ' ' . $row['booking_start_time']);
                            $booking_end_ts = strtotime($row['booking_date'] . ' ' . $row['booking_end_time']);
                            $is_lock_expired = (!empty($row['booking_lock_expire']) && strtotime($row['booking_lock_expire']) <= time());
                            $now = time();

                            // ตรวจสอบช่วงเวลาตามหลัก HCI (Nielsen's Error Prevention)
                            $is_past = ($now >= $booking_end_ts); // ผ่านพ้นเวลาไปแล้ว (อดีต)
                            $is_active_now = ($now >= $booking_start_ts && $now < $booking_end_ts); // กำลังใช้งานอยู่ในขณะนี้
                            $is_future = ($now < $booking_start_ts); // ยังไม่ถึงเวลาใช้งาน (อนาคต)

                            $status_class = 'status-pending';
                            $display_status = $status;
                            $can_cancel = false;
                            $cancel_btn_text = 'ยกเลิกการจอง';

                            if ($status === STATUS_BOOKING_CANCELLED) {
                                $status_class = 'status-cancel';
                                $display_status = 'ยกเลิกแล้ว';
                                $can_cancel = false;
                            } elseif (!empty($row['cancel_refund_status']) && $row['cancel_refund_status'] === 'รอดำเนินการ') {
                                $status_class = 'status-pending';
                                $display_status = 'ขอยกเลิก (รอแอดมินพิจารณา)';
                                $can_cancel = false;
                            } elseif ($status === STATUS_BOOKING_CONFIRMED) {
                                if ($is_past) {
                                    // จองแล้ว และเวลาผ่านไปแล้ว -> แสดง "ใช้บริการแล้ว" / "เสร็จสิ้น"
                                    $status_class = 'status-completed';
                                    $display_status = 'ใช้บริการแล้ว';
                                    $can_cancel = false; // ปิดปุ่มยกเลิกตามหลัก Error Prevention
                                } elseif ($is_active_now) {
                                    // กำลังอยู่ในช่วงเวลาเตะ
                                    $status_class = 'status-active';
                                    $display_status = 'กำลังใช้งาน';
                                    $can_cancel = false;
                                } else {
                                    // ยังไม่ถึงเวลาใช้งาน (อนาคต) -> สามารถกดยกเลิกได้
                                    $status_class = 'status-success';
                                    $display_status = 'จองแล้ว';
                                    $can_cancel = true;
                                    $cancel_btn_text = 'ขอยกเลิก';
                                }
                            } elseif ($status === STATUS_BOOKING_PENDING) {
                                if ($is_past) {
                                    // รายการค้างที่ไม่ได้รับการอนุมัติจนเลยเวลา
                                    $status_class = 'status-cancel';
                                    $display_status = 'เลยเวลาจอง';
                                    $can_cancel = false;
                                } elseif (!empty($slip)) {
                                    $status_class = 'status-pending';
                                    $display_status = 'รอตรวจสอบสลิป';
                                    $can_cancel = true;
                                    $cancel_btn_text = 'ยกเลิกการจอง';
                                } else {
                                    if ($is_lock_expired) {
                                        $status_class = 'status-cancel';
                                        $display_status = 'หมดเวลาชำระเงิน';
                                        $can_cancel = false;
                                    } else {
                                        $status_class = 'status-pending';
                                        $display_status = 'รอแนบสลิป';
                                        $can_cancel = true;
                                        $cancel_btn_text = 'ยกเลิกการจอง';
                                    }
                                }
                            }
                        ?>
                        <tr>
                            <td class="booking-id-cell">#<?php echo $row['booking_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['court_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['booking_date'])); ?></td>
                            <td><?php echo $row['booking_start_time'] . ' - ' . $row['booking_end_time']; ?></td>
                            <td class="booking-price-cell"><?php echo number_format($row['booking_total_price'], 2); ?> ฿</td>
                            <td>
                                <span class="badge-status <?php echo $status_class; ?>">
                                    <?php if ($status_class === 'status-completed'): ?>
                                        <i class="fas fa-check-double"></i> 
                                    <?php elseif ($status_class === 'status-active'): ?>
                                        <i class="fas fa-running"></i> 
                                    <?php endif; ?>
                                    <?php echo $display_status; ?>
                                </span>
                            </td>
                            <td class="booking-action-cell">
                                <div class="booking-action-wrap">
                                    <?php if ($status === 'รอตรวจสอบ' && empty($slip) && !$is_lock_expired && !$is_past): ?>
                                        <a href="payment.php?booking_id=<?php echo $row['booking_id']; ?>" class="btn-action-sm btn-pay-now">
                                            <i class="fas fa-credit-card"></i> แนบสลิป
                                        </a>
                                    <?php elseif (!empty($slip)): ?>
                                        <a href="uploads/slips/<?php echo htmlspecialchars($slip); ?>" target="_blank" class="btn-action-sm btn-view-slip">
                                            <i class="fas fa-receipt"></i> ดูสลิป
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($row['booking_status'] === 'จองแล้ว'): ?>
                                        <button type="button" class="btn-action-sm btn-view-receipt" 
                                                onclick='openReceiptModal(<?php echo json_encode([
                                                    "id" => $row["booking_id"],
                                                    "court" => $row["court_name"],
                                                    "date" => date("d/m/Y", strtotime($row["booking_date"])),
                                                    "time" => substr($row["booking_start_time"], 0, 5) . " - " . substr($row["booking_end_time"], 0, 5),
                                                    "court_price" => number_format($row["booking_court_price"], 2),
                                                    "rental_price" => number_format($row["booking_rental_price"], 2),
                                                    "product_price" => number_format($row["booking_product_price"], 2),
                                                    "total_price" => number_format($row["booking_total_price"], 2),
                                                    "customer_name" => !empty($row["booking_nickname"]) ? ($default_member_name ? $default_member_name . " (" . $row["booking_nickname"] . ")" : $row["booking_nickname"]) : $default_member_name,
                                                    "customer_phone" => $default_member_phone,
                                                    "created_at" => date("d/m/Y H:i", strtotime($row["booking_created_at"])),
                                                    "items" => $booking_items_grouped[$row["booking_id"]] ?? []
                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                            <i class="fas fa-file-invoice"></i> ใบเสร็จ
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($can_cancel): ?>
                                        <button type="button" class="btn-cancel-booking" 
                                                onclick="openCancelModal(
                                                    <?php echo $row['booking_id']; ?>,
                                                    '<?php echo htmlspecialchars(addslashes($row['court_name'])); ?>',
                                                    '<?php echo date('d/m/Y', strtotime($row['booking_date'])); ?>',
                                                    '<?php echo $row['booking_start_time'] . ' - ' . $row['booking_end_time']; ?>',
                                                    '<?php echo number_format($row['booking_total_price'], 2); ?>',
                                                    '<?php echo $status; ?>',
                                                    <?php echo !empty($slip) ? 'true' : 'false'; ?>
                                                )">
                                            <i class="fas fa-times-circle"></i> <?php echo $cancel_btn_text; ?>
                                        </button>
                                    <?php elseif ($status === 'ยกเลิก'): ?>
                                        <span class="booking-refund-wait"><i class="fas fa-ban"></i> ยกเลิกแล้ว</span>
                                    <?php elseif ($status_class === 'status-completed'): ?>
                                        <span class="badge-completed"><i class="fas fa-check-circle text-primary"></i> เสร็จสิ้น</span>
                                    <?php elseif ($status_class === 'status-active'): ?>
                                        <span class="booking-refund-done"><i class="fas fa-play-circle"></i> กำลังใช้งาน</span>
                                    <?php elseif ($is_past || $is_lock_expired): ?>
                                        <span class="booking-refund-rej"><i class="fas fa-clock"></i> หมดเวลา</span>
                                    <?php else: ?>
                                        <span class="booking-dash-mute">-</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </main>

    <!-- Modal ยืนยันการขอยกเลิกการจอง -->
    <div id="cancelModal" class="modal-cancel-overlay"
         data-default-name="<?php echo htmlspecialchars($default_member_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
         data-default-phone="<?php echo htmlspecialchars($default_member_phone ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <div class="modal-cancel-box">
            <h4 id="modal_cancel_header" class="modal-cancel-header-title">
                <i class="fas fa-exclamation-triangle"></i> <span id="modal_cancel_title_text">ขอยกเลิกการจองสนาม</span>
            </h4>

            <form action="actions/cancel_booking_db.php" method="POST" id="cancelBookingForm" onsubmit="return handleCancelBookingSubmit(event)">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="booking_id" id="modal_cancel_booking_id">

                <!-- รายละเอียดการจอง -->
                <div class="modal-cancel-summary-box">
                    <div><strong>รหัสจอง:</strong> #<span id="modal_cancel_id_text"></span></div>
                    <div><strong>สนาม:</strong> <span id="modal_cancel_court"></span></div>
                    <div><strong>วัน-เวลา:</strong> <span id="modal_cancel_date"></span> (<span id="modal_cancel_time"></span>)</div>
                    <div><strong>ยอดเงินรวม:</strong> <span id="modal_cancel_price" class="modal-cancel-price"></span> ฿</div>
                </div>

                <!-- กรณีที่ 1: รอตรวจสอบ + ยังไม่แนบสลิป (ยังไม่จ่าย) -->
                <div id="modal_unverified_notice" class="modal-cancel-notice notice-unverified">
                    <strong><i class="fas fa-info-circle"></i> ข้อชี้แจงการยกเลิก:</strong>
                    <div class="modal-notice-sub">รายการนี้ยังไม่ได้แนบสลิปชำระเงิน เมื่อกดยกเลิก ระบบจะปล่อย Slot สนามและคืนสต็อกอุปกรณ์ทันที <strong>(ไม่มีการคืนเงินเนื่องจากยังไม่ได้ชำระเงิน)</strong></div>
                </div>

                <!-- กรณีที่ 2: รอตรวจสอบ + แนบสลิปแล้ว (จ่ายแล้ว แต่รอตรวจ) -->
                <div id="modal_unverified_slip_notice" class="modal-cancel-notice notice-unverified-slip">
                    <strong><i class="fas fa-receipt"></i> ข้อชี้แจงการขอคืนเงิน:</strong>
                    <div class="modal-notice-sub">ท่านได้แนบสลิปชำระเงินไว้แล้ว เมื่อกดยกเลิก ระบบจะปล่อย Slot สนามทันที และส่งเรื่องให้ผู้ดูแลระบบตรวจสอบสลิปเพื่อดำเนินการ <strong class="booking-refund-done">โอนเงินคืน 100%</strong> ตามบัญชีที่ท่านระบุด้านล่าง</div>
                </div>

                <!-- กรณีที่ 3: จองแล้ว (ยืนยันชำระเงินแล้ว) -->
                <div id="modal_refund_policy_box" class="modal-cancel-notice notice-policy">
                    <strong><i class="fas fa-info-circle"></i> เงื่อนไขและนโยบายการขอคืนเงิน:</strong>
                    <ul class="cancel-policy-list">
                        <li>ยกเลิกก่อนเวลาใช้งานมากกว่า 24 ชม.: โอนเงินคืน 100%</li>
                        <li>ยกเลิกก่อนเวลาใช้งาน 12 - 24 ชม.: หักค่าธรรมเนียม 30% (โอนคืน 70%)</li>
                        <li>ยกเลิกก่อนเวลาใช้งานน้อยกว่า 12 ชม.: หักค่าธรรมเนียม 50% (โอนคืน 50%)</li>
                    </ul>
                    <div class="cancel-policy-note">*ผู้ดูแลระบบจะพิจารณาและโอนเงินคืนตามข้อมูลบัญชีที่ระบุด้านล่าง</div>
                </div>

                <!-- กล่องระบุข้อมูลบัญชีสำหรับรับเงินโอนคืน (HCI: Avoid Redundant User Input) -->
                <div id="modal_refund_account_section" class="modal-refund-account-box">
                    <div class="cancel-refund-heading">
                        <i class="fas fa-university"></i> ข้อมูลบัญชีสำหรับรับเงินโอนคืน (ผ่านธนาคาร / พร้อมเพย์)
                    </div>

                    <div class="form-group cancel-form-group">
                        <label class="cancel-label">
                            ธนาคาร หรือ ช่องทางรับเงิน <span class="required-star">*</span>
                        </label>
                        <select name="refund_bank" id="cancel_refund_bank" class="form-control cancel-select-bank">
                            <option value="พร้อมเพย์ (PromptPay)">พร้อมเพย์ (PromptPay)</option>
                            <option value="ธนาคารกสิกรไทย (KBANK)">ธนาคารกสิกรไทย (KBANK)</option>
                            <option value="ธนาคารไทยพาณิชย์ (SCB)">ธนาคารไทยพาณิชย์ (SCB)</option>
                            <option value="ธนาคารกรุงไทย (KTB)">ธนาคารกรุงไทย (KTB)</option>
                            <option value="ธนาคารกรุงเทพ (BBL)">ธนาคารกรุงเทพ (BBL)</option>
                            <option value="ธนาคารกรุงศรีอยุธยา (BAY)">ธนาคารกรุงศรีอยุธยา (BAY)</option>
                            <option value="ธนาคารทหารไทยธนชาต (ttb)">ธนาคารทหารไทยธนชาต (ttb)</option>
                            <option value="ธนาคารออมสิน (GSB)">ธนาคารออมสิน (GSB)</option>
                        </select>
                    </div>

                    <div class="form-group cancel-form-group">
                        <label class="cancel-label">
                            เลขที่บัญชี หรือ เบอร์พร้อมเพย์ <span class="required-star">*</span>
                        </label>
                        <input type="text" name="refund_account_no" id="cancel_refund_account_no" class="form-control cancel-input-text" placeholder="เช่น 08xxxxxxxx หรือ เลขบัญชีธนาคาร">
                    </div>

                    <div class="form-group cancel-form-group-last">
                        <label class="cancel-label">
                            ชื่อ - นามสกุล เจ้าของบัญชี <span class="required-star">*</span>
                        </label>
                        <input type="text" name="refund_account_name" id="cancel_refund_account_name" class="form-control cancel-input-text" placeholder="ระบุชื่อเจ้าของบัญชีสำหรับเทียบยอดโอน">
                    </div>
                </div>

                <div class="form-group cancel-form-group-reason">
                    <label class="cancel-label-lg">
                        ระบุเหตุผลในการขอยกเลิก <span class="required-star">*</span>
                    </label>
                    <textarea name="cancel_reason" class="form-control" rows="3" placeholder="เช่น ติดธุระด่วน, สภาพอากาศไม่เอื้ออำนวย, เพื่อนร่วมทีมไม่สะดวก" required></textarea>
                </div>

                <div class="modal-cancel-btn-group">
                    <button type="button" onclick="closeCancelModal()" class="btn-modal-cancel-dismiss">
                        ปิด
                    </button>
                    <button type="submit" id="modal_cancel_submit_btn" class="btn-modal-cancel-confirm">
                        <i class="fas fa-check"></i> <span id="modal_cancel_submit_text">ยืนยันการขอยกเลิก</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <!-- ==========================================================================
         Modal แสดงใบเสร็จรับเงินอิเล็กทรอนิกส์ (E-Receipt Modal)
         Strict Separation of Concerns & Black-Gold Brand Identity
         ========================================================================== -->
    <div id="receiptModal" class="receipt-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="receiptModalTitle">
        <div class="receipt-modal-card">
            <!-- Modal Header (Close button for screen view) -->
            <button type="button" class="receipt-modal-close-btn" onclick="closeReceiptModal()" aria-label="ปิดหน้าต่าง">
                <i class="fas fa-times"></i>
            </button>

            <!-- Printable Receipt Container -->
            <div class="receipt-paper" id="receiptPrintArea">
                <!-- Receipt Header -->
                <div class="receipt-brand-header">
                    <div class="receipt-brand-logo">
                        <i class="fas fa-feather-alt text-gold"></i>
                    </div>
                    <div class="receipt-brand-info">
                        <h3 class="receipt-brand-title" id="receiptModalTitle">สนามแบดมินตัน ที.เอส. ปัตตานี</h3>
                        <p class="receipt-brand-eng">T.S. PATTANI BADMINTON COURT</p>
                        <p class="receipt-brand-contact">
                            <i class="fas fa-map-marker-alt"></i> ถนนหนองจิก ต.รูสะมิแล อ.เมือง จ.ปัตตานี 94000 &nbsp;|&nbsp;
                            <i class="fas fa-phone-alt"></i> 073-xxx-xxxx
                        </p>
                    </div>
                    <div class="receipt-badge-title">
                        <span class="receipt-doc-badge">ใบเสร็จรับเงินอิเล็กทรอนิกส์</span>
                        <span class="receipt-doc-sub">ELECTRONIC RECEIPT</span>
                    </div>
                </div>

                <div class="receipt-divider-gold"></div>

                <!-- Receipt Meta & Customer Details -->
                <div class="receipt-meta-grid">
                    <div class="receipt-meta-col">
                        <div class="receipt-meta-row">
                            <span class="receipt-meta-label">เลขที่ใบเสร็จ:</span>
                            <strong class="receipt-meta-value text-gold" id="receipt_id_text">#REC-BK0000</strong>
                        </div>
                        <div class="receipt-meta-row">
                            <span class="receipt-meta-label">วันที่ทำรายการ:</span>
                            <span class="receipt-meta-value" id="receipt_created_at">-</span>
                        </div>
                        <div class="receipt-meta-row">
                            <span class="receipt-meta-label">ช่องทางชำระเงิน:</span>
                            <span class="receipt-meta-value">โอนเงินผ่านธนาคาร / QR Code</span>
                        </div>
                    </div>
                    <div class="receipt-meta-col">
                        <div class="receipt-meta-row">
                            <span class="receipt-meta-label">ชื่อผู้จอง:</span>
                            <strong class="receipt-meta-value" id="receipt_customer_name">-</strong>
                        </div>
                        <div class="receipt-meta-row">
                            <span class="receipt-meta-label">เบอร์โทรศัพท์:</span>
                            <span class="receipt-meta-value" id="receipt_customer_phone">-</span>
                        </div>
                        <div class="receipt-meta-row">
                            <span class="receipt-meta-label">สถานะการชำระ:</span>
                            <span class="receipt-status-pill">
                                <i class="fas fa-check-circle"></i> ชำระเงินเรียบร้อยแล้ว (PAID)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Court Reservation Info Box -->
                <div class="receipt-court-box">
                    <div class="receipt-court-item">
                        <i class="fas fa-table-tennis text-gold"></i>
                        <div>
                            <span class="receipt-court-label">สนามที่จอง:</span>
                            <strong id="receipt_court_name" class="receipt-court-val">-</strong>
                        </div>
                    </div>
                    <div class="receipt-court-item">
                        <i class="far fa-calendar-alt text-gold"></i>
                        <div>
                            <span class="receipt-court-label">วันที่ใช้งาน:</span>
                            <strong id="receipt_booking_date" class="receipt-court-val">-</strong>
                        </div>
                    </div>
                    <div class="receipt-court-item">
                        <i class="far fa-clock text-gold"></i>
                        <div>
                            <span class="receipt-court-label">ช่วงเวลา:</span>
                            <strong id="receipt_booking_time" class="receipt-court-val">-</strong>
                        </div>
                    </div>
                </div>

                <!-- Breakdown Table -->
                <div class="receipt-table-wrapper">
                    <table class="receipt-items-table">
                        <thead>
                            <tr>
                                <th class="receipt-col-seq">ลำดับ</th>
                                <th class="receipt-col-desc">รายการ (Description)</th>
                                <th class="receipt-col-qty">จำนวน</th>
                                <th class="receipt-col-unit">ราคาต่อหน่วย</th>
                                <th class="receipt-col-total">รวมเงิน (฿)</th>
                            </tr>
                        </thead>
                        <tbody id="receipt_items_body">
                            <!-- Injected dynamically via JavaScript -->
                        </tbody>
                        <tfoot>
                            <tr class="receipt-subtotal-row">
                                <td colspan="4" class="text-right">ค่าบริการสนาม (Court Fee):</td>
                                <td class="text-right" id="receipt_court_subtotal">0.00</td>
                            </tr>
                            <tr class="receipt-subtotal-row" id="receipt_rental_row">
                                <td colspan="4" class="text-right">ค่าเช่าอุปกรณ์ (Equipment Rental):</td>
                                <td class="text-right" id="receipt_rental_subtotal">0.00</td>
                            </tr>
                            <tr class="receipt-subtotal-row" id="receipt_product_row">
                                <td colspan="4" class="text-right">ค่าสินค้า/เครื่องดื่ม (Products):</td>
                                <td class="text-right" id="receipt_product_subtotal">0.00</td>
                            </tr>
                            <tr class="receipt-total-highlight-row">
                                <td colspan="4" class="text-right font-bold">ยอดรวมสุทธิ (Total Amount Paid):</td>
                                <td class="text-right font-bold text-gold receipt-grand-total" id="receipt_grand_total">0.00 ฿</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Receipt Note & Sign off -->
                <div class="receipt-footer-note">
                    <div class="receipt-note-left">
                        <p class="receipt-note-title"><i class="fas fa-info-circle text-gold"></i> ข้อความชี้แจง:</p>
                        <ul class="receipt-note-list">
                            <li>กรุณาแสดงใบเสร็จนี้หรือแจ้งชื่อ-เบอร์โทรแก่เจ้าหน้าที่ก่อนลงสนาม</li>
                            <li>เอกสารนี้เป็นใบเสร็จรับเงินอิเล็กทรอนิกส์ที่ออกโดยระบบอัตโนมัติ</li>
                        </ul>
                    </div>
                    <div class="receipt-note-right text-center">
                        <div class="receipt-stamp-box">
                            <i class="fas fa-stamp text-gold"></i>
                            <span>T.S. PATTANI</span>
                            <small class="receipt-stamp-approved">VERIFIED & PAID</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons (Screen only) -->
            <div class="receipt-actions-footer">
                <button type="button" class="btn-receipt-dismiss" onclick="closeReceiptModal()">
                    <i class="fas fa-times"></i> ปิด
                </button>
                <button type="button" class="btn-receipt-print" onclick="window.print()">
                    <i class="fas fa-print"></i> พิมพ์ / บันทึก PDF
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/member.js?v=<?php echo filemtime('assets/js/member.js'); ?>"></script>
</body>
</html>
