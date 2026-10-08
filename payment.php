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
    <link rel="stylesheet" href="assets/css/style.css?v=1.7">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="page-body">

    <?php include 'includes/navbar.php'; ?>

    <div class="payment-wrapper payment-page-container">
        <!-- ข้อความแจ้งเตือน Flash Messages -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle fa-lg"></i>
            <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle fa-lg"></i>
            <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        </div>
        <?php endif; ?>
        <div class="booking-card payment-countdown-card">
            <h3><i class="fas fa-clock"></i> กรุณาชำระเงินภายใน 15 นาที</h3>
            <p class="payment-countdown-subtitle">ระบบได้ทำการล็อกสนามไว้ให้ท่านแล้ว หากเกินเวลาการจองจะถูกยกเลิกอัตโนมัติ</p>
            <!-- ตัวจับเวลาถอยหลัง -->
            <div id="countdown" class="payment-countdown-val">15:00</div>
        </div>

        <div class="booking-grid payment-grid-two-cols">
            
            <!-- ฝั่งซ้าย: ข้อมูลการชำระเงิน (QR Code) -->
            <div class="booking-card">
                <h3><i class="fas fa-qrcode"></i> สแกน QR Code ชำระเงิน</h3>
                <div class="payment-qr-wrapper">
                    <!-- จำลองรูป QR Code PromptPay หรือรูปบัญชีธนาคาร -->
                    <div class="payment-qr-placeholder">
                        <i class="fas fa-qrcode fa-5x payment-qr-icon"></i>
                    </div>
                    <p class="payment-bank-title">ธนาคารกสิกรไทย (KBANK)</p>
                    <div class="payment-account-row">
                        <span id="bankAccountNo" class="payment-account-number">123-4-56789-0</span>
                        <button type="button" class="btn-copy-account" onclick="copyBankAccount('123-4-56789-0')" title="คัดลอกเลขบัญชี">
                            <i class="fas fa-copy"></i> คัดลอก
                        </button>
                        <span id="copyFeedback" class="copy-tooltip"><i class="fas fa-check"></i> คัดลอกแล้ว!</span>
                    </div>
                    <p class="payment-account-holder">ชื่อบัญชี: บจก. ที.เอส. ปัตตานี แบดมินตัน</p>
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
                <hr class="payment-divider">
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
                <hr class="payment-divider">
                <div class="summary-row total-row payment-total-row">
                    <span>ยอดที่ต้องชำระสุทธิ:</span>
                    <span class="payment-total-val"><?php echo number_format($booking['booking_total_price'], 2); ?> ฿</span>
                </div>

                <!-- ฟอร์มส่งสลิปไปที่ actions/payment_db.php -->
                <form action="actions/payment_db.php" method="POST" enctype="multipart/form-data" id="paymentForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                    
                    <div class="form-group">
                        <label class="payment-upload-label">แนบสลิปการโอนเงิน *</label>
                        <!-- Slip Upload Dropzone with Live Preview -->
                        <div id="slipUploadBox" class="slip-upload-box">
                            <input type="file" id="paymentSlipInput" name="payment_slip" accept="image/jpeg,image/png,image/webp" class="payment-file-input" required>
                            
                            <div id="uploadPrompt" class="upload-prompt">
                                <i class="fas fa-cloud-upload-alt fa-3x payment-upload-icon"></i>
                                <div class="payment-upload-text">คลิกหรือลากไฟล์สลิปมาวางที่นี่</div>
                                <div class="payment-upload-hint">รองรับ JPG, PNG, WEBP (ไม่เกิน 5MB)</div>
                            </div>

                            <div id="previewContainer" class="preview-container">
                                <img id="slipPreviewImg" src="" alt="สลิปโอนเงิน" class="preview-img">
                                <div id="fileInfoText" class="payment-file-info"></div>
                                <button type="button" class="btn-change-file" id="btnChangeSlip">
                                    <i class="fas fa-sync-alt"></i> เปลี่ยนรูปภาพ
                                </button>
                            </div>
                        </div>
                        <div id="clientErrorMsg" class="payment-client-error"></div>
                    </div>

                    <button type="submit" id="btnSubmitPayment" class="btn-confirm-booking btn-payment-submit">
                        ยืนยันการชำระเงิน <i class="fas fa-upload"></i>
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- เรียกใช้ไฟล์ JS หลัก และสั่งรันฟังก์ชันนับถอยหลังโดยส่งค่าเวลาเข้าไป -->
    <script src="assets/js/member.js?v=1.5"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let lockExpire = "<?php echo $booking['booking_lock_expire']; ?>";
            initPaymentCountdown(lockExpire);
            initSlipUpload();
        });
    </script>
</body>
</html>
