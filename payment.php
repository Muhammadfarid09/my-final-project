<?php
session_start();
require_once 'config/config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามี booking_id ส่งมาหรือไม่
if (!isset($_GET['booking_id'])) {
    header("Location: booking.php");
    exit();
}

$booking_id = intval($_GET['booking_id']);
$member_id = $_SESSION['member_id'];

try {
    // ดึงข้อมูลการจองเฉพาะของลูกค้ารายนี้ และต้องอยู่ในสถานะ "รอตรวจสอบ" เท่านั้น
    $stmt = $conn->prepare("SELECT b.*, c.court_name 
                            FROM BOOKING b 
                            JOIN COURT c ON b.court_id = c.court_id 
                            WHERE b.booking_id = :booking_id 
                            AND b.member_id = :member_id 
                            AND b.booking_status = 'รอตรวจสอบ'");
    $stmt->execute([':booking_id' => $booking_id, ':member_id' => $member_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $_SESSION['error'] = "ไม่พบรายการจองนี้ หรือรายการนี้ได้ถูกชำระเงิน/ยกเลิกไปแล้ว";
        header("Location: booking.php");
        exit();
    }

    // ดึงรายการสินค้าหรืออุปกรณ์เช่าที่พ่วงกับการจองนี้
    $stmt_rent = $conn->prepare("SELECT r.*, p.product_name, p.product_type, p.product_price 
                                 FROM RENTAL r 
                                 JOIN PRODUCT p ON r.product_id = p.product_id 
                                 WHERE r.booking_id = :booking_id");
    $stmt_rent->execute([':booking_id' => $booking_id]);
    $rental_items = $stmt_rent->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="background-color: #f4f6f9;">

    <?php include 'includes/navbar.php'; ?>

    <div class="payment-wrapper" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
        <div class="booking-card" style="text-align: center; margin-bottom: 25px; background: #fff3cd; border: 1px solid #ffeeba; color: #856404;">
            <h3><i class="fas fa-clock"></i> กรุณาชำระเงินภายใน 15 นาที</h3>
            <p style="margin: 5px 0 0 0; font-size: 14px;">ระบบได้ทำการล็อกสนามไว้ให้ท่านแล้ว หากเกินเวลาการจองจะถูกยกเลิกอัตโนมัติ</p>
            <!-- ตัวจับเวลาถอยหลัง -->
            <div id="countdown" style="font-size: 24px; font-weight: bold; margin-top: 10px; color: #dc3545;">15:00</div>
        </div>

        <div class="booking-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
            
            <!-- ฝั่งซ้าย: ข้อมูลการชำระเงิน (QR Code) -->
            <div class="booking-card">
                <h3><i class="fas id="qrcode"></i> สแกน QR Code ชำระเงิน</h3>
                <div style="text-align: center; margin: 20px 0;">
                    <!-- จำลองรูป QR Code PromptPay หรือรูปบัญชีธนาคาร -->
                    <div style="background: #e9ecef; width: 200px; height: 200px; margin: 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                        <i class="fas fa-qrcode fa-5x" style="color: #6c757d;"></i>
                    </div>
                    <p style="margin-top: 15px; font-weight: 500; color: #343a40;">ธนาคารกสิกรไทย (KBANK)</p>
                    <p style="margin: 0; font-size: 18px; font-weight: bold; color: #007bff;">123-4-56789-0</p>
                    <p style="font-size: 13px; color: #666; margin-top: 5px;">ชื่อบัญชี: บจก. ที.เอส. ปัตตานี แบดมินตัน</p>
                </div>
            </div>

            <!-- ฝั่งขวา: รายละเอียดและฟอร์มแนบสลิป -->
            <div class="booking-card">
                <h3><i class="fas fa-receipt"></i> สรุปยอดชำระ</h3>
                <div class="summary-row">
                    <span>รหัสการจอง:</span>
                    <strong>#<?php echo $booking['booking_id']; ?></strong>
                </div>
                <div class="summary-row">
                    <span>สนาม:</span>
                    <strong><?php echo htmlspecialchars($booking['court_name']); ?></strong>
                </div>
                <div class="summary-row">
                    <span>วันที่/เวลา:</span>
                    <strong><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?> (<?php echo $booking['booking_start_time']; ?> - <?php echo $booking['booking_end_time']; ?>)</strong>
                </div>
                <hr style="margin: 10px 0; border: 0; border-top: 1px dashed #ccc;">
                <div class="summary-row">
                    <span>ค่าสนาม:</span>
                    <span><?php echo number_format($booking['booking_court_price'], 2); ?> ฿</span>
                </div>
                <?php if($booking['booking_rental_price'] > 0): ?>
                <div class="summary-row">
                    <span>ค่าเช่าอุปกรณ์:</span>
                    <span><?php echo number_format($booking['booking_rental_price'], 2); ?> ฿</span>
                </div>
                <?php endif; ?>
                <?php if($booking['booking_product_price'] > 0): ?>
                <div class="summary-row">
                    <span>ค่าสินค้า:</span>
                    <span><?php echo number_format($booking['booking_product_price'], 2); ?> ฿</span>
                </div>
                <?php endif; ?>
                <hr style="margin: 10px 0; border: 0; border-top: 1px dashed #ccc;">
                <div class="summary-row total-row" style="margin-bottom: 20px;">
                    <span>ยอดที่ต้องชำระสุทธิ:</span>
                    <span style="color: #28a745; font-size: 20px;"><?php echo number_format($booking['booking_total_price'], 2); ?> ฿</span>
                </div>

                <!-- ฟอร์มส่งสลิปไปที่ actions/payment_db.php -->
                <form action="actions/payment_db.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                    
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">แนบสลิปการโอนเงิน *</label>
                        <input type="file" name="payment_slip" class="form-control" accept="image/*" required style="padding: 8px;">
                    </div>

                    <button type="submit" class="btn-confirm-booking" style="background: #007bff; margin-top: 10px;">
                        ยืนยันการชำระเงิน <i class="fas fa-upload"></i>
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- เรียกใช้ไฟล์ JS หลัก และสั่งรันฟังก์ชันนับถอยหลังโดยส่งค่าเวลาเข้าไป -->
    <script src="assets/js/member.js?v=1.4"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let lockExpire = "<?php echo $booking['booking_lock_expire']; ?>";
            initPaymentCountdown(lockExpire);
        });
    </script>
</body>
</html>
