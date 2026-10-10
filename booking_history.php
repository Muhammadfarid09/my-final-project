<?php
session_start();
require_once 'config/config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit();
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="page-body">

    <?php include 'includes/navbar.php'; ?>

    <div class="history-wrapper">
        
        <div class="booking-header history-header">
            <h2><i class="fas fa-history"></i> ประวัติการจองสนามและคำสั่งซื้อ</h2>
            <p>ตรวจสอบสถานะการจองสนาม ดูสลิปการโอน หรือขอยกเลิกการจอง</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle fa-lg"></i> <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle fa-lg"></i> <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
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
                <div class="history-filter-bar">
                    <button type="button" class="btn-history-filter active" onclick="filterHistoryStatus('all', this)">
                        ทั้งหมด (<?php echo count($bookings); ?>)
                    </button>
                    <button type="button" class="btn-history-filter" onclick="filterHistoryStatus('pending', this)">
                        ⏳ รอชำระ/รอตรวจ
                    </button>
                    <button type="button" class="btn-history-filter" onclick="filterHistoryStatus('success', this)">
                        ✅ จองสำเร็จ
                    </button>
                    <button type="button" class="btn-history-filter" onclick="filterHistoryStatus('cancel', this)">
                        ❌ ยกเลิก
                    </button>
                </div>
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

                            if ($status === 'ยกเลิก') {
                                $status_class = 'status-cancel';
                                $display_status = 'ยกเลิกแล้ว';
                                $can_cancel = false;
                            } elseif (!empty($row['cancel_refund_status']) && $row['cancel_refund_status'] === 'รอดำเนินการ') {
                                $status_class = 'status-pending';
                                $display_status = 'ขอยกเลิก (รอแอดมินพิจารณา)';
                                $can_cancel = false;
                            } elseif ($status === 'จองแล้ว' || $status === 'ชำระเงินแล้ว' || $status === 'อนุมัติแล้ว') {
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
                            } elseif ($status === 'รอตรวจสอบ') {
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
                        <?php 
                            $status_cat = ($status === 'ยกเลิก' || $status_class === 'status-cancel') ? 'cancel' : (($status === 'จองแล้ว' || $status === 'ชำระเงินแล้ว' || $status === 'อนุมัติแล้ว' || $status_class === 'status-completed' || $status_class === 'status-active') ? 'success' : 'pending');
                        ?>
                        <tr data-status-cat="<?php echo $status_cat; ?>">
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

                <!-- การ์ดตั๋วแสดงผลบนอุปกรณ์เคลื่อนที่ (Mobile Ticket Cards) -->
                <div class="history-mobile-cards">
                    <?php foreach($bookings as $row): 
                        $status = $row['booking_status'];
                        $slip = $row['payment_slip'] ?? '';
                        $booking_start_ts = strtotime($row['booking_date'] . ' ' . $row['booking_start_time']);
                        $booking_end_ts = strtotime($row['booking_date'] . ' ' . $row['booking_end_time']);
                        $is_lock_expired = (!empty($row['booking_lock_expire']) && strtotime($row['booking_lock_expire']) <= time());
                        $now = time();
                        $is_past = ($now >= $booking_end_ts);
                        $is_active_now = ($now >= $booking_start_ts && $now < $booking_end_ts);

                        $status_class = 'status-pending';
                        $display_status = $status;
                        $can_cancel = false;
                        $cancel_btn_text = 'ยกเลิกการจอง';

                        if ($status === 'ยกเลิก') {
                            $status_class = 'status-cancel';
                            $display_status = 'ยกเลิกแล้ว';
                            $can_cancel = false;
                        } elseif (!empty($row['cancel_refund_status']) && $row['cancel_refund_status'] === 'รอดำเนินการ') {
                            $status_class = 'status-pending';
                            $display_status = 'ขอยกเลิก (รอแอดมินพิจารณา)';
                            $can_cancel = false;
                        } elseif ($status === 'จองแล้ว' || $status === 'ชำระเงินแล้ว' || $status === 'อนุมัติแล้ว') {
                            if ($is_past) {
                                $status_class = 'status-completed';
                                $display_status = 'ใช้บริการแล้ว';
                                $can_cancel = false;
                            } elseif ($is_active_now) {
                                $status_class = 'status-active';
                                $display_status = 'กำลังใช้งาน';
                                $can_cancel = false;
                            } else {
                                $status_class = 'status-success';
                                $display_status = 'จองแล้ว';
                                $can_cancel = true;
                                $cancel_btn_text = 'ขอยกเลิก';
                            }
                        } elseif ($status === 'รอตรวจสอบ') {
                            if ($is_past) {
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

                        $status_cat = ($status === 'ยกเลิก' || $status_class === 'status-cancel') ? 'cancel' : (($status === 'จองแล้ว' || $status === 'ชำระเงินแล้ว' || $status === 'อนุมัติแล้ว' || $status_class === 'status-completed' || $status_class === 'status-active') ? 'success' : 'pending');
                    ?>
                    <div class="history-mobile-card-item" data-status-cat="<?php echo $status_cat; ?>">
                        <div class="hm-card-header">
                            <span class="hm-card-id">#BK-<?php echo $row['booking_id']; ?></span>
                            <span class="badge-status <?php echo $status_class; ?>">
                                <?php if ($status_class === 'status-completed'): ?>
                                    <i class="fas fa-check-double"></i> 
                                <?php elseif ($status_class === 'status-active'): ?>
                                    <i class="fas fa-running"></i> 
                                <?php endif; ?>
                                <?php echo $display_status; ?>
                            </span>
                        </div>
                        <div class="hm-card-court">
                            <i class="fas fa-shuttlecock text-success"></i> <?php echo htmlspecialchars($row['court_name']); ?>
                        </div>
                        <div class="hm-card-meta">
                            <div><i class="far fa-calendar-alt"></i> วันที่: <?php echo date('d/m/Y', strtotime($row['booking_date'])); ?></div>
                            <div><i class="far fa-clock"></i> เวลา: <?php echo $row['booking_start_time'] . ' - ' . $row['booking_end_time']; ?> น.</div>
                        </div>
                        <div class="hm-card-divider"></div>
                        <div class="hm-card-footer">
                            <div class="hm-card-price">
                                ยอดสุทธิ: <strong><?php echo number_format($row['booking_total_price'], 2); ?> ฿</strong>
                            </div>
                            <div class="hm-card-actions">
                                <?php if ($status === 'รอตรวจสอบ' && empty($slip) && !$is_lock_expired && !$is_past): ?>
                                    <a href="payment.php?booking_id=<?php echo $row['booking_id']; ?>" class="btn-action-sm btn-pay-now">
                                        <i class="fas fa-credit-card"></i> แนบสลิป
                                    </a>
                                <?php elseif (!empty($slip)): ?>
                                    <a href="uploads/slips/<?php echo htmlspecialchars($slip); ?>" target="_blank" class="btn-action-sm btn-view-slip">
                                        <i class="fas fa-receipt"></i> ดูสลิป
                                    </a>
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
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>

    </div>

    <!-- Modal ยืนยันการขอยกเลิกการจอง -->
    <div id="cancelModal" class="modal-cancel-overlay">
        <div class="modal-cancel-box">
            <h4 id="modal_cancel_header" class="modal-cancel-header-title">
                <i class="fas fa-exclamation-triangle"></i> <span id="modal_cancel_title_text">ขอยกเลิกการจองสนาม</span>
            </h4>

            <form action="actions/cancel_booking_db.php" method="POST">
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
            </form>
        </div>
    </div>

    <script src="assets/js/member.js?v=1.5"></script>
    <script>
    const defaultMemberName = <?php echo json_encode($default_member_name); ?>;
    const defaultMemberPhone = <?php echo json_encode($default_member_phone); ?>;

    function openCancelModal(id, court, date, time, price, status, hasSlip) {
        document.getElementById('modal_cancel_booking_id').value = id;
        document.getElementById('modal_cancel_id_text').textContent = id;
        document.getElementById('modal_cancel_court').textContent = court;
        document.getElementById('modal_cancel_date').textContent = date;
        document.getElementById('modal_cancel_time').textContent = time;
        document.getElementById('modal_cancel_price').textContent = price;

        const unverifiedNotice = document.getElementById('modal_unverified_notice');
        const unverifiedSlipNotice = document.getElementById('modal_unverified_slip_notice');
        const policyBox = document.getElementById('modal_refund_policy_box');
        const accountSection = document.getElementById('modal_refund_account_section');
        const titleText = document.getElementById('modal_cancel_title_text');
        const submitText = document.getElementById('modal_cancel_submit_text');

        const accNoInput = document.getElementById('cancel_refund_account_no');
        const accNameInput = document.getElementById('cancel_refund_account_name');

        // เติมค่าเริ่มต้นเพื่อลดภาระการพิมพ์ (Avoid Redundant User Input)
        if (accNoInput && !accNoInput.value) accNoInput.value = defaultMemberPhone;
        if (accNameInput && !accNameInput.value) accNameInput.value = defaultMemberName;

        if (status === 'รอตรวจสอบ' && !hasSlip) {
            // กรณีที่ 1: รอแนบสลิป (ยังไม่จ่าย)
            unverifiedNotice.style.display = 'block';
            unverifiedSlipNotice.style.display = 'none';
            policyBox.style.display = 'none';
            accountSection.style.display = 'none';
            if (accNoInput) accNoInput.removeAttribute('required');
            if (accNameInput) accNameInput.removeAttribute('required');
            titleText.textContent = 'ยกเลิกการจองสนาม (ก่อนชำระเงิน)';
            submitText.textContent = 'ยืนยันยกเลิกการจอง';
        } else if (status === 'รอตรวจสอบ' && hasSlip) {
            // กรณีที่ 2: แนบสลิปแล้ว แต่อยู่ระหว่างรอตรวจ (จ่ายแล้ว)
            unverifiedNotice.style.display = 'none';
            unverifiedSlipNotice.style.display = 'block';
            policyBox.style.display = 'none';
            accountSection.style.display = 'block';
            if (accNoInput) accNoInput.setAttribute('required', 'required');
            if (accNameInput) accNameInput.setAttribute('required', 'required');
            titleText.textContent = 'ขอยกเลิกและขอคืนเงิน (แนบสลิปแล้ว)';
            submitText.textContent = 'ส่งคำขอยกเลิกและขอคืนเงิน';
        } else {
            // กรณีที่ 3: จองแล้ว (แอดมินอนุมัติแล้ว)
            unverifiedNotice.style.display = 'none';
            unverifiedSlipNotice.style.display = 'none';
            policyBox.style.display = 'block';
            accountSection.style.display = 'block';
            if (accNoInput) accNoInput.setAttribute('required', 'required');
            if (accNameInput) accNameInput.setAttribute('required', 'required');
            titleText.textContent = 'ขอยกเลิกการจองสนาม (เพื่อขอคืนเงิน)';
            submitText.textContent = 'ส่งคำขอยกเลิกการจอง';
        }

        const modal = document.getElementById('cancelModal');
        modal.style.display = 'flex';
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
    }
    </script>
</body>
</html>
