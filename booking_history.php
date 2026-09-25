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
    $stmt = $conn->prepare("SELECT b.*, c.court_name 
                            FROM BOOKING b 
                            JOIN COURT c ON b.court_id = c.court_id 
                            WHERE b.member_id = :member_id 
                            ORDER BY b.booking_created_at DESC");
    $stmt->execute([':member_id' => $member_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .btn-cancel-booking {
            background: #ef4444;
            color: #ffffff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }
        .btn-cancel-booking:hover {
            background: #dc2626;
        }
        .modal-cancel-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal-cancel-box {
            background: #ffffff;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            animation: fadeInModal 0.2s ease-out;
        }
        @keyframes fadeInModal {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body style="background-color: #f4f6f9;">

    <?php include 'includes/navbar.php'; ?>

    <div class="history-wrapper">
        
        <div class="booking-header" style="margin-bottom: 25px;">
            <h2><i class="fas fa-history"></i> ประวัติการจองสนามและคำสั่งซื้อ</h2>
            <p>ตรวจสอบสถานะการจองสนาม ดูสลิปการโอน หรือขอยกเลิกการจอง</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-circle fa-lg"></i> <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-exclamation-circle fa-lg"></i> <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <div class="booking-card" style="padding: 20px; overflow-x: auto;">
            <?php if (empty($bookings)): ?>
                <div style="text-align: center; padding: 40px; color: #6c757d;">
                    <i class="fas fa-folder-open fa-3x" style="margin-bottom: 10px;"></i>
                    <p>ยังไม่มีประวัติการจองสนามในระบบ</p>
                    <a href="booking.php" class="btn-confirm-booking" style="display: inline-block; width: auto; padding: 10px 20px; margin-top: 10px; text-decoration: none;">ไปจองสนามเลย</a>
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
                            <th style="text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bookings as $row): 
                            $status = $row['booking_status'];
                            $slip = $row['payment_slip'] ?? '';
                            $booking_start_ts = strtotime($row['booking_date'] . ' ' . $row['booking_start_time']);
                            $is_lock_expired = (!empty($row['booking_lock_expire']) && strtotime($row['booking_lock_expire']) <= time());
                            $can_cancel = ($status !== 'ยกเลิก' && $booking_start_ts > time());
                        ?>
                        <tr>
                            <td style="font-weight: bold;">#<?php echo $row['booking_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['court_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['booking_date'])); ?></td>
                            <td><?php echo $row['booking_start_time'] . ' - ' . $row['booking_end_time']; ?></td>
                            <td style="color: #007bff; font-weight: bold;"><?php echo number_format($row['booking_total_price'], 2); ?> ฿</td>
                            <td>
                                <?php 
                                    $status_class = 'status-pending';
                                    $display_status = $status;

                                    if ($status == 'จองแล้ว' || $status == 'ชำระเงินแล้ว' || $status == 'อนุมัติแล้ว') {
                                        $status_class = 'status-success';
                                        $display_status = 'จองแล้ว';
                                    } elseif ($status == 'ยกเลิก') {
                                        $status_class = 'status-cancel';
                                    } elseif ($status == 'รอตรวจสอบ') {
                                        if (!empty($slip)) {
                                            $display_status = 'รอตรวจสอบสลิป';
                                        } elseif ($is_lock_expired) {
                                            $display_status = 'หมดเวลาชำระเงิน';
                                            $status_class = 'status-cancel';
                                        } else {
                                            $display_status = 'รอแนบสลิป';
                                        }
                                    }
                                ?>
                                <span class="badge-status <?php echo $status_class; ?>">
                                    <?php echo $display_status; ?>
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <?php if ($status == 'รอตรวจสอบ' && empty($slip) && !$is_lock_expired): ?>
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
                                                '<?php echo number_format($row['booking_total_price'], 2); ?>'
                                            )">
                                        <i class="fas fa-times"></i> ขอยกเลิก
                                    </button>
                                <?php elseif ($status === 'ยกเลิก'): ?>
                                    <span style="color: #94a3b8; font-size: 12px;"><i class="fas fa-ban"></i> ยกเลิกแล้ว</span>
                                <?php elseif (empty($slip)): ?>
                                    <span style="color: #adb5bd; font-size: 12px;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

    <!-- Modal ยืนยันการขอยกเลิกการจอง -->
    <div id="cancelModal" class="modal-cancel-overlay">
        <div class="modal-cancel-box">
            <h4 style="margin: 0 0 15px 0; color: #dc2626; border-bottom: 2px solid #fee2e2; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-exclamation-triangle"></i> ขอยกเลิกการจองสนาม
            </h4>

            <form action="actions/cancel_booking_db.php" method="POST">
                <input type="hidden" name="booking_id" id="modal_cancel_booking_id">

                <!-- รายละเอียดการจอง -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 15px; margin-bottom: 15px; font-size: 14px;">
                    <div><strong>รหัสจอง:</strong> #<span id="modal_cancel_id_text"></span></div>
                    <div><strong>สนาม:</strong> <span id="modal_cancel_court"></span></div>
                    <div><strong>วัน-เวลา:</strong> <span id="modal_cancel_date"></span> (<span id="modal_cancel_time"></span>)</div>
                    <div><strong>ยอดเงินรวม:</strong> <span id="modal_cancel_price" style="color: #0284c7; font-weight: bold;"></span> ฿</div>
                </div>

                <!-- นโยบายการคืนเงิน -->
                <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 12px; color: #92400e;">
                    <strong><i class="fas fa-info-circle"></i> เงื่อนไขและนโยบายการยกเลิก:</strong>
                    <ul style="margin: 5px 0 0 18px; padding: 0;">
                        <li>ยกเลิกก่อนเวลาใช้งานมากกว่า 24 ชม.: ได้รับการพิจารณาคืนเงิน 100%</li>
                        <li>ยกเลิกก่อนเวลาใช้งาน 12 - 24 ชม.: หักค่าธรรมเนียม 30% (คืน 70%)</li>
                        <li>ยกเลิกก่อนเวลาใช้งานน้อยกว่า 12 ชม.: หักค่าธรรมเนียม 50% (คืน 50%)</li>
                    </ul>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #334155;">
                        ระบุเหตุผลในการขอยกเลิก <span style="color: red;">*</span>
                    </label>
                    <textarea name="cancel_reason" class="form-control" rows="3" placeholder="เช่น ติดธุระด่วน, สภาพอากาศไม่เอื้ออำนวย, เพื่อนร่วมทีมไม่สะดวก" style="width: 100%; box-sizing: border-box; border-radius: 8px; border: 1px solid #cbd5e1; padding: 8px 12px; font-family: inherit;" required></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeCancelModal()" style="padding: 10px 18px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; cursor: pointer; font-family: inherit;">
                        ปิด
                    </button>
                    <button type="submit" style="padding: 10px 18px; border-radius: 6px; border: none; background: #dc2626; color: white; cursor: pointer; font-weight: 600; font-family: inherit;">
                        <i class="fas fa-check"></i> ยืนยันการขอยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/member.js?v=1.5"></script>
    <script>
    function openCancelModal(id, court, date, time, price) {
        document.getElementById('modal_cancel_booking_id').value = id;
        document.getElementById('modal_cancel_id_text').textContent = id;
        document.getElementById('modal_cancel_court').textContent = court;
        document.getElementById('modal_cancel_date').textContent = date;
        document.getElementById('modal_cancel_time').textContent = time;
        document.getElementById('modal_cancel_price').textContent = price;

        const modal = document.getElementById('cancelModal');
        modal.style.display = 'flex';
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
    }
    </script>
</body>
</html>
