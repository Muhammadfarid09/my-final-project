<?php
require_once 'includes/auth_check.php';

// 1. ตรวจสอบว่ามี booking_id ส่งมาหรือไม่
if (!isset($_GET['booking_id'])) {
    header("Location: booking.php");
    exit();
}

$booking_id = intval($_GET['booking_id']);
$member_id = intval($_SESSION['member_id']);

try {
    // 2. ดึงข้อมูลการจองเฉพาะของลูกค้ารายนี้ และต้องอยู่ในสถานะ "รอตรวจสอบ" เท่านั้น (IDOR Prevention)
    $stmt = $conn->prepare("SELECT b.*, c.court_name 
                            FROM Booking b 
                            JOIN Court c ON b.court_id = c.court_id 
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

    // 3. ตรวจสอบเวลาล็อกสนาม 15 นาที หากหมดเวลาไม่อนุญาตให้ชำระเงิน
    if (!empty($booking['booking_lock_expire']) && strtotime($booking['booking_lock_expire']) < time()) {
        $_SESSION['error'] = "หมดเวลาการชำระเงินสำหรับการจองนี้แล้ว (เกินกำหนด 15 นาที) กรุณาทำรายการจองใหม่";
        header("Location: booking.php");
        exit();
    }

    // 4. ดึงรายการสินค้าหรืออุปกรณ์เช่าที่พ่วงกับการจองนี้
    $stmt_rent = $conn->prepare("SELECT r.*, p.product_name, p.product_type, p.product_price 
                                 FROM Rental r 
                                 JOIN Product p ON r.product_id = p.product_id 
                                 WHERE r.booking_id = :booking_id");
    $stmt_rent->execute([':booking_id' => $booking_id]);
    $rental_items = $stmt_rent->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.8">
    <link rel="stylesheet" href="assets/css/navbar.css?v=<?php echo filemtime('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="assets/css/payment.css?v=<?php echo filemtime('assets/css/payment.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="page-body">

    <?php include_once 'includes/navbar.php'; ?>

    <main class="payment-wrapper payment-page-container">
        
        <!-- ข้อความแจ้งเตือน Flash Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error" role="alert">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <div><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle fa-lg"></i>
                <div><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            </div>
        <?php endif; ?>

        <!-- กล่องนับถอยหลัง 15 นาที Dark Luxury Strip -->
        <div class="payment-countdown-card">
            <div class="countdown-info-wrap">
                <div class="countdown-icon-box">
                    <i class="fas fa-clock fa-spin-pulse"></i>
                </div>
                <div class="countdown-text-box">
                    <h4>กรุณาชำระเงินและแนบสลิปภายใน 15 นาที</h4>
                    <p>ระบบได้ทำการล็อกสนามไว้ให้ท่านแล้ว หากเกินเวลาการจองจะถูกยกเลิกอัตโนมัติ</p>
                </div>
            </div>
            <!-- ตัวจับเวลาถอยหลัง -->
            <div class="countdown-timer-badge">
                <span id="countdown" class="countdown-digits">15:00</span>
            </div>
        </div>

        <div class="payment-grid-two-cols">
            
            <!-- ฝั่งซ้าย: ข้อมูลการชำระเงิน (QR Code PromptPay) -->
            <div class="payment-card">
                <h3 class="payment-card-title">
                    <i class="fas fa-qrcode"></i> สแกน QR Code ชำระเงิน
                </h3>

                <div class="payment-qr-wrapper">
                    <!-- การ์ด QR Code สีขาวคมชัด -->
                    <div class="payment-qr-frame">
                        <div class="promptpay-header">
                            <i class="fas fa-mobile-alt"></i> พร้อมเพย์ (PromptPay)
                        </div>
                        <img src="https://promptpay.io/0812345678/<?php echo htmlspecialchars($booking['booking_total_price']); ?>.png" 
                             alt="PromptPay QR Code" 
                             class="payment-qr-img"
                             onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=210x210&data=0812345678';"
                             loading="lazy">
                        <span class="payment-qr-hint">สแกนผ่านแอป Mobile Banking ทุกธนาคาร</span>
                    </div>

                    <div class="payment-bank-info">
                        <p class="payment-bank-title">ธนาคารกสิกรไทย (KBANK)</p>
                        <div class="payment-account-row">
                            <span id="bankAccountNo" class="payment-account-number">123-4-56789-0</span>
                            <button type="button" class="btn-copy-account" onclick="copyBankAccount('123-4-56789-0')" title="คัดลอกเลขบัญชี">
                                <i class="fas fa-copy"></i> คัดลอก
                            </button>
                            <span id="copyFeedback" class="copy-tooltip"><i class="fas fa-check-circle"></i> คัดลอกแล้ว!</span>
                        </div>
                        <p class="payment-account-holder">ชื่อบัญชี: บจก. ที.เอส. ปัตตานี แบดมินตัน</p>
                    </div>
                </div>
            </div>

            <!-- ฝั่งขวา: รายละเอียดการจองและฟอร์มแนบสลิป -->
            <div class="payment-card">
                <h3 class="payment-card-title">
                    <i class="fas fa-receipt"></i> สรุปรายละเอียดการจอง
                </h3>

                <div class="summary-row">
                    <span>รหัสการจอง:</span>
                    <strong>#<?php echo htmlspecialchars($booking['booking_id']); ?></strong>
                </div>
                <div class="summary-row">
                    <span>สนาม:</span>
                    <strong><?php echo htmlspecialchars($booking['court_name']); ?></strong>
                </div>
                <div class="summary-row">
                    <span>วันที่ / เวลา:</span>
                    <strong><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?> (<?php echo htmlspecialchars($booking['booking_start_time']); ?> - <?php echo htmlspecialchars($booking['booking_end_time']); ?>)</strong>
                </div>

                <hr class="payment-divider">

                <div class="summary-row">
                    <span>ค่าบริการสนาม:</span>
                    <span><?php echo number_format($booking['booking_court_price'], 2); ?> ฿</span>
                </div>
                <?php if ($booking['booking_rental_price'] > 0): ?>
                    <div class="summary-row">
                        <span>ค่าเช่าอุปกรณ์:</span>
                        <span><?php echo number_format($booking['booking_rental_price'], 2); ?> ฿</span>
                    </div>
                <?php endif; ?>
                <?php if ($booking['booking_product_price'] > 0): ?>
                    <div class="summary-row">
                        <span>ค่าสินค้า/เครื่องดื่ม:</span>
                        <span><?php echo number_format($booking['booking_product_price'], 2); ?> ฿</span>
                    </div>
                <?php endif; ?>

                <div class="payment-total-row">
                    <span class="payment-total-label">ยอดที่ต้องชำระสุทธิ:</span>
                    <span class="payment-total-val"><?php echo number_format($booking['booking_total_price'], 2); ?> ฿</span>
                </div>

                <!-- ฟอร์มส่งสลิปไปที่ actions/payment_db.php -->
                <form action="actions/payment_db.php" method="POST" enctype="multipart/form-data" id="paymentForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                    
                    <div class="form-group">
                        <label class="payment-upload-label">
                            แนบสลิปหลักฐานการโอนเงิน <span class="required-star">*</span>
                        </label>
                        
                        <!-- Slip Upload Dropzone with Live Preview -->
                        <div id="slipUploadBox" class="slip-upload-box">
                            <input type="file" id="paymentSlipInput" name="payment_slip" accept="image/jpeg,image/png,image/webp" class="payment-file-input" required>
                            
                            <div id="uploadPrompt" class="upload-prompt">
                                <i class="fas fa-cloud-arrow-up payment-upload-icon"></i>
                                <div class="payment-upload-text">คลิกหรือลากไฟล์สลิปมาวางที่นี่</div>
                                <div class="payment-upload-hint">รองรับไฟล์ JPG, PNG, WEBP (ขนาดไม่เกิน 5MB)</div>
                            </div>

                            <div id="previewContainer" class="preview-container">
                                <img id="slipPreviewImg" src="" alt="ตัวอย่างสลิปโอนเงิน" class="preview-img">
                                <div id="fileInfoText" class="payment-file-info"></div>
                                <button type="button" class="btn-change-file" id="btnChangeSlip">
                                    <i class="fas fa-sync-alt"></i> เปลี่ยนรูปภาพ
                                </button>
                            </div>
                        </div>
                        <div id="clientErrorMsg" class="payment-client-error"></div>
                    </div>

                    <button type="submit" id="btnSubmitPayment" class="btn-payment-submit">
                        ยืนยันการชำระเงิน <i class="fas fa-shield-alt"></i>
                    </button>
                </form>
            </div>

        </div>
    </main>

    <!-- เรียกใช้ไฟล์ JS หลัก และสั่งรันฟังก์ชันนับถอยหลังโดยส่งค่าเวลาเข้าไป -->
    <script src="assets/js/member.js?v=<?php echo filemtime('assets/js/member.js'); ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let lockExpire = "<?php echo htmlspecialchars($booking['booking_lock_expire'] ?? ''); ?>";
            initPaymentCountdown(lockExpire);
            initSlipUpload();
        });
    </script>
</body>
</html>
