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
    <link rel="stylesheet" href="assets/css/style.css?v=1.5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="background-color: #f4f6f9;">

    <?php include 'includes/navbar.php'; ?>

    <div class="history-wrapper">
        
        <div class="booking-header" style="margin-bottom: 25px;">
            <h2><i class="fas fa-history"></i> ประวัติการจองสนามและคำสั่งซื้อ</h2>
            <p>ตรวจสอบสถานะการจองสนามและประวัติการชำระเงินของคุณ</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
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
                        <?php foreach($bookings as $row): ?>
                        <tr>
                            <td style="font-weight: bold;">#<?php echo $row['booking_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['court_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['booking_date'])); ?></td>
                            <td><?php echo $row['booking_start_time'] . ' - ' . $row['booking_end_time']; ?></td>
                            <td style="color: #007bff; font-weight: bold;"><?php echo number_format($row['booking_total_price'], 2); ?> ฿</td>
                            <td>
                                <?php 
                                    $status = $row['booking_status'];
                                    $status_class = 'status-pending';
                                    $display_status = $status;

                                    if ($status == 'จองแล้ว' || $status == 'ชำระเงินแล้ว' || $status == 'อนุมัติแล้ว') {
                                        $status_class = 'status-success';
                                    } elseif ($status == 'ยกเลิก') {
                                        $status_class = 'status-cancel';
                                    } elseif ($status == 'รอตรวจสอบ') {
                                        $display_status = !empty($row['payment_slip']) ? 'รอตรวจสอบสลิป' : 'รอแนบสลิป';
                                    }
                                ?>
                                <span class="badge-status <?php echo $status_class; ?>">
                                    <?php echo $display_status; ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($status == 'รอตรวจสอบ' && empty($row['payment_slip']) && strtotime($row['booking_lock_expire']) > time()): ?>
                                    <a href="payment.php?booking_id=<?php echo $row['booking_id']; ?>" class="btn-action-sm btn-pay-now">
                                        <i class="fas fa-credit-card"></i> ชำระเงิน
                                    </a>
                                <?php elseif (!empty($row['payment_slip'])): ?>
                                    <a href="uploads/slips/<?php echo $row['payment_slip']; ?>" target="_blank" class="btn-action-sm btn-view-slip">
                                        <i class="fas fa-receipt"></i> ดูสลิป
                                    </a>
                                <?php else: ?>
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

    <script src="assets/js/member.js?v=1.5"></script>
</body>
</html>
