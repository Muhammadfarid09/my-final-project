<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/config.php';
require_once 'admin/includes/auto_cancel.php';

// ตรวจสอบสถานะการเข้าสู่ระบบแบบ Non-blocking (อนุญาตให้ผู้ใช้ทั่วไปดูและเลือกสนามได้)
$is_logged_in = false;
$default_nickname = '';

if (isset($_SESSION['member_id']) && !empty($_SESSION['member_id'])) {
    if (isset($conn)) {
        try {
            $stmt_check = $conn->prepare("SELECT member_name, member_status FROM Member WHERE member_id = :mid");
            $stmt_check->execute([':mid' => $_SESSION['member_id']]);
            $member_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
            if ($member_data && $member_data['member_status'] !== 'ระงับสิทธิ์') {
                $is_logged_in = true;
                $default_nickname = htmlspecialchars($member_data['member_name'] ?? '');
            } else {
                unset($_SESSION['member_id'], $_SESSION['member_name'], $_SESSION['member_phone']);
            }
        } catch (PDOException $e) {
            // ปล่อยให้ระบบทำงานต่อ
        }
    }
}

// ตรวจสอบและกู้คืนข้อมูลการจองชั่วคราว (Pending Booking) หลังล็อกอินสำเร็จ
$pending_booking_json = '';
$restored_booking_alert = false;
if ($is_logged_in && !empty($_SESSION['pending_booking'])) {
    $pending_booking_json = json_encode($_SESSION['pending_booking'], JSON_UNESCAPED_UNICODE);
    $restored_booking_alert = true;
    unset($_SESSION['pending_booking']);
}


try {
    // ดึงข้อมูลสนามทั้งหมด
    $stmt_court = $conn->query("SELECT * FROM Court ORDER BY court_id ASC");
    $courts = $stmt_court->fetchAll(PDO::FETCH_ASSOC);

    // ดึงข้อมูลสินค้าและอุปกรณ์เช่าที่มีในสต็อก
    $stmt_prod = $conn->query("SELECT * FROM Product WHERE product_stock > 0 ORDER BY product_type DESC, product_id ASC");
    $products = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองสนามแบดมินตัน - T.S. Pattani</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.1">
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <link rel="stylesheet" href="assets/css/booking-wizard.css?v=<?php echo filemtime(__DIR__ . '/assets/css/booking-wizard.css'); ?>">
    <link rel="stylesheet" href="assets/css/responsive-mobile.css?v=1.0">
    <link rel="stylesheet" href="assets/css/navbar.css?v=<?php echo filemtime('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="booking-page-body" data-logged-in="<?php echo $is_logged_in ? 'true' : 'false'; ?>">

    <!-- นำเข้า Navbar -->
    <?php include_once 'includes/navbar.php'; ?>

    <div class="wizard-page-wrapper">
        
        <!-- Header Banner -->
        <div class="wizard-header-banner">
            <h2><i class="fas fa-calendar-check"></i> จองสนามแบดมินตัน</h2>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if ($restored_booking_alert): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>ยินดีต้อนรับกลับมาคุณ <?php echo htmlspecialchars($_SESSION['member_name'] ?? ''); ?>! ข้อมูลการจองที่คุณเลือกไว้ได้รับการกู้คืนเรียบร้อยแล้ว กรุณาตรวจสอบและกดยืนยันเพื่อไปหน้าชำระเงิน</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($pending_booking_json)): ?>
            <script id="pendingBookingData" type="application/json">
                <?php echo $pending_booking_json; ?>
            </script>
        <?php endif; ?>


        <!-- ============================================== -->
        <!-- 5-Step Progress Stepper (HCI & Progressive Disclosure) -->
        <!-- ============================================== -->
        <div class="wizard-stepper-box">
            <div class="wizard-stepper-nav">
                <div class="wizard-stepper-track">
                    <div class="wizard-stepper-fill" id="stepperFillBar"></div>
                </div>

                <!-- Step 1 -->
                <div class="wizard-step-node active" id="stepperNode1" onclick="jumpToWizardStep(1)">
                    <div class="wizard-step-circle">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="wizard-step-label">1. วันที่</div>
                    <div class="wizard-step-sublabel">เลือกวันจอง</div>
                </div>

                <!-- Step 2 -->
                <div class="wizard-step-node upcoming" id="stepperNode2" onclick="jumpToWizardStep(2)">
                    <div class="wizard-step-circle">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="wizard-step-label">2. เวลาเริ่ม</div>
                    <div class="wizard-step-sublabel">เวลาเปิดทำการ</div>
                </div>

                <!-- Step 3 -->
                <div class="wizard-step-node upcoming" id="stepperNode3" onclick="jumpToWizardStep(3)">
                    <div class="wizard-step-circle">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="wizard-step-label">3. คอร์ท & ชั่วโมง</div>
                    <div class="wizard-step-sublabel">เลือกสนามและเวลา</div>
                </div>

                <!-- Step 4 -->
                <div class="wizard-step-node upcoming" id="stepperNode4" onclick="jumpToWizardStep(4)">
                    <div class="wizard-step-circle">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <div class="wizard-step-label">4. บริการเสริม</div>
                    <div class="wizard-step-sublabel">อุปกรณ์ & สินค้า</div>
                </div>

                <!-- Step 5 -->
                <div class="wizard-step-node upcoming" id="stepperNode5" onclick="jumpToWizardStep(5)">
                    <div class="wizard-step-circle">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="wizard-step-label">5. สรุป & ยืนยัน</div>
                    <div class="wizard-step-sublabel">ตรวจสอบและส่งจอง</div>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- ฟอร์มหลักสำหรับส่งข้อมูลไปยัง actions/booking_db.php -->
        <!-- ============================================== -->
        <form action="actions/booking_db.php" method="POST" id="bookingForm" onsubmit="return handleBookingFormSubmit(event)">
            <?php echo csrf_field(); ?>
            
            <!-- Hidden inputs สำหรับเก็บสถานะการเลือก -->
            <input type="hidden" name="booking_date" id="input_booking_date" value="<?php echo date('Y-m-d'); ?>">
            <input type="hidden" name="start_time" id="input_start_time" value="">
            <input type="hidden" name="end_time" id="input_end_time" value="">
            <input type="hidden" name="court_id" id="input_court_id" value="">
            <input type="hidden" name="booking_nickname" id="input_booking_nickname" value="<?php echo $default_nickname; ?>">
            <input type="hidden" name="total_court_price" id="input_court_price" value="0">
            <input type="hidden" name="total_rental_price" id="input_rental_price" value="0">
            <input type="hidden" name="total_product_price" id="input_product_price" value="0">
            <input type="hidden" name="total_price" id="input_total_price" value="0">

            <!-- ============================================== -->
            <!-- Step 1: เลือกวันที่ (Interactive Visual Calendar) -->
            <!-- ============================================== -->
            <div class="wizard-panel active" id="stepPanel1">
                <div class="wizard-card">
                    <div class="wizard-card-header">
                        <h3><i class="fas fa-calendar-alt"></i> ขั้นตอนที่ 1: เลือกวันที่ต้องการใช้บริการ</h3>
                        <p class="wizard-card-desc">คลิกเลือกวันที่จากปฏิทิน (วันที่ในอดีตถูกปิดใช้งาน) พร้อมตรวจสอบอัตราค่าบริการประจำวัน</p>
                    </div>

                    <div class="calendar-picker-container">
                        <!-- ปุ่มทางลัด วันนี้ / พรุ่งนี้ -->
                        <div class="calendar-quick-bar">
                            <button type="button" class="btn-quick-date active" id="btnQuickToday" onclick="selectQuickDate('today')">
                                <i class="fas fa-calendar-day"></i> วันนี้
                            </button>
                            <button type="button" class="btn-quick-date" id="btnQuickTomorrow" onclick="selectQuickDate('tomorrow')">
                                <i class="fas fa-calendar-plus"></i> พรุ่งนี้
                            </button>
                        </div>

                        <!-- ปฏิทินแสดงเดือนและวันที่ 1-31 -->
                        <div class="calendar-box">
                            <div class="calendar-nav-bar">
                                <button type="button" class="cal-nav-btn" onclick="navigateCalendarMonth(-1)" title="เดือนก่อนหน้า">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <span class="cal-month-title" id="calMonthTitle">-</span>
                                <button type="button" class="cal-nav-btn" onclick="navigateCalendarMonth(1)" title="เดือนถัดไป">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>

                            <div class="calendar-grid" id="calendarGrid">
                                <!-- ส่วนแสดงหัววัน อา. - ส. และวันที่ 1-31 จะสร้างด้วย JavaScript -->
                            </div>
                        </div>

                        <!-- ป้ายบอกราคาของวันที่เลือกเพียงบรรทัดเดียวอย่างชัดเจน -->
                        <div class="date-price-banner" id="datePriceBanner">
                            <i class="fas fa-tag"></i> 
                            <span id="datePriceText">กำลังตรวจสอบอัตราค่าบริการ...</span>
                        </div>
                    </div>

                    <div class="wizard-nav-actions flex-end">
                        <button type="button" class="btn-wizard-next" id="btnStep1Next" onclick="goToStep(2)">
                            ถัดไป: เลือกเวลาเริ่มต้น <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- Step 2: เลือกเวลาเริ่มต้น (Operating Hours Grid) -->
            <!-- ============================================== -->
            <div class="wizard-panel" id="stepPanel2">
                <div class="wizard-card">
                    <div class="wizard-card-header">
                        <h3><i class="fas fa-clock"></i> ขั้นตอนที่ 2: เลือกเวลาเริ่มต้น</h3>
                        <p class="wizard-card-desc">คลิกเลือกช่วงเวลาเริ่มต้นที่ต้องการเข้าใช้งาน ระบบจะนำท่านเข้าสู่การเลือกสนามทันที</p>
                    </div>

                    <div class="time-picker-wrapper">
                        <!-- กริดปุ่มช่วงเวลาเปิดทำการ -->
                        <div class="time-grid-container" id="timeSlotGrid">
                            <!-- สร้างรายการช่วงเวลาด้วย JavaScript -->
                        </div>
                    </div>

                    <div class="wizard-nav-actions">
                        <button type="button" class="btn-wizard-back" onclick="goToStep(1)">
                            <i class="fas fa-arrow-left"></i> ย้อนกลับ
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- Step 3: เลือกคอร์ท & ชั่วโมงต่อเนื่อง (Consecutive Slot) -->
            <!-- ============================================== -->
            <div class="wizard-panel" id="stepPanel3">
                <div class="wizard-card step3-wrapper">
                    <div class="wizard-card-header">
                        <h3><i class="fas fa-map-marked-alt"></i> ขั้นตอนที่ 3: เลือกสนามแบดมินตันและชั่วโมงการเล่น</h3>
                        <p class="wizard-card-desc">คลิกเลือกสนามที่ต้องการในชั่วโมงแรก และสามารถเลื่อนลงด้านล่างเพื่อเลือกชั่วโมงต่อเนื่องได้ทันที</p>
                    </div>

                    <!-- ช่องกรอกชื่อเล่น / ชื่อก๊วน (PDPA Protection) -->
                    <div class="nickname-box">
                        <label for="step3_nickname" class="nickname-label">
                            <i class="fas fa-user-tag text-success"></i> 
                            ชื่อเล่น / ชื่อก๊วนผู้จอง:
                            <span class="pdpa-badge">PDPA</span>
                        </label>
                        <div class="nickname-input-group">
                            <input type="text" id="step3_nickname" class="nickname-control" maxlength="30" placeholder="เช่น ต้น, ก๊วนเพื่อนสุขใจ (ไม่เกิน 30 ตัวอักษร)" value="<?php echo $default_nickname; ?>" oninput="syncBookingNickname(this.value)">
                            <div class="nickname-hint">
                                <i class="fas fa-shield-alt"></i> ระบบจะแสดงเฉพาะชื่อเล่นนี้บนการ์ดสนามเพื่อคุ้มครองความเป็นส่วนตัวตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล
                            </div>
                        </div>
                    </div>

                    <!-- บล็อกแสดงคอร์ทในแต่ละชั่วโมง (Hour 1 + Scroll Multi-Hour) -->
                    <div id="courtHourBlocksContainer">
                        <!-- เนื้อหาบล็อกชั่วโมง 1 และชั่วโมงถัดไปจะถูกเรนเดอร์ด้วย JavaScript -->
                    </div>

                    <div class="wizard-nav-actions">
                        <button type="button" class="btn-wizard-back" onclick="goToStep(2)">
                            <i class="fas fa-arrow-left"></i> เปลี่ยนเวลาเริ่มต้น
                        </button>
                    </div>
                </div>

                <!-- Sticky Bottom Summary Bar (ตรึงล่างสุดของจอเสมอ) -->
                <div class="sticky-summary-bar" id="stickySummaryBar">
                    <div class="sticky-summary-inner">
                        <div class="sticky-metrics-group">
                            <div class="sticky-metric-pill sticky-metric-total">
                                <i class="fas fa-tag"></i>
                                <span id="stickyTotalDisplay">[ Total 0 ฿ ]</span>
                            </div>
                            <div class="sticky-submetrics-row">
                                <div class="sticky-metric-pill">
                                    <i class="fas fa-clock"></i>
                                    <span id="stickyHoursDisplay">[ 0 Hour ]</span>
                                </div>
                                <div class="sticky-metric-pill">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span id="stickyCourtDisplay">[ ยังไม่ได้เลือกสนาม ]</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-sticky-next" id="btnStickyNext" disabled onclick="goToStep(4)">
                            ต่อไป: บริการเสริม <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- Step 4: บริการเสริมและอุปกรณ์เช่า (Add-ons Optional) -->
            <!-- ============================================== -->
            <div class="wizard-panel" id="stepPanel4">
                <div class="wizard-card step4-wrapper">
                    <div class="wizard-card-header">
                        <h3><i class="fas fa-shopping-basket"></i> ขั้นตอนที่ 4: บริการเสริมและอุปกรณ์เช่า (ทางเลือกเสริม)</h3>
                        <p class="wizard-card-desc">เลือกน้ำดื่ม ลูกแบด หรืออุปกรณ์เช่าตามต้องการ หรือกด "ข้ามขั้นตอนนี้" หากมีอุปกรณ์พร้อมแล้ว</p>
                    </div>

                    <!-- แถบย่อสรุปข้อมูลสนาม วัน และเวลาที่เลือกไว้ -->
                    <div class="compact-selection-strip" id="step4SummaryStrip">
                        <div class="compact-strip-item">
                            <i class="fas fa-calendar-day text-success"></i>
                            <span id="stripDate">-</span>
                        </div>
                        <div class="compact-strip-item">
                            <i class="fas fa-clock text-success"></i>
                            <span id="stripTime">-</span>
                        </div>
                        <div class="compact-strip-item">
                            <i class="fas fa-map-marked-alt text-success"></i>
                            <span id="stripCourt">-</span>
                        </div>
                    </div>

                    <!-- หมวดหมู่: สินค้าบริโภค -->
                    <div class="addon-category-title">
                        <i class="fas fa-coffee"></i> สินค้าบริโภค (น้ำดื่ม/ลูกแบด)
                    </div>
                    <div class="addon-cards-grid">
                        <?php 
                        $has_consumer = false;
                        foreach($products as $prod): 
                            if($prod['product_type'] == 'สินค้าบริโภค'):
                                $has_consumer = true;
                                $prod_data_json = json_encode([
                                    "id" => $prod["product_id"],
                                    "name" => $prod["product_name"],
                                    "brand" => $prod["product_brand"] ?? "",
                                    "model" => $prod["product_model"] ?? "",
                                    "size" => $prod["product_size"] ?? "",
                                    "level" => $prod["product_level"] ?? "",
                                    "price" => floatval($prod["product_price"]),
                                    "stock" => intval($prod["product_stock"]),
                                    "type" => $prod["product_type"],
                                    "image" => !empty($prod["product_image"]) ? "uploads/products/" . $prod["product_image"] : ""
                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                        ?>
                        <div class="addon-item-card" id="card_prod_<?php echo $prod['product_id']; ?>">
                            <div class="addon-card-header">
                                <div class="addon-item-thumb addon-thumb-clickable" 
                                     onclick='openProductDetailModal(<?php echo $prod_data_json; ?>)'
                                     title="คลิกเพื่อดูรายละเอียด">
                                    <?php if(!empty($prod['product_image'])): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($prod['product_image']); ?>" alt="<?php echo htmlspecialchars($prod['product_name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-tint fa-2x"></i>
                                    <?php endif; ?>
                                    <span class="thumb-zoom-hint"><i class="fas fa-search-plus"></i></span>
                                </div>
                                <div class="addon-item-main-info">
                                    <div class="addon-card-title-row">
                                        <span class="addon-item-name addon-item-name-clickable" 
                                              onclick='openProductDetailModal(<?php echo $prod_data_json; ?>)'
                                              title="คลิกเพื่อดูรายละเอียด">
                                            <?php echo htmlspecialchars($prod['product_name']); ?>
                                        </span>
                                    </div>
                                    <div class="addon-card-meta-row">
                                        <?php if(!empty($prod['product_brand'])): ?>
                                            <span class="addon-meta-brand"><?php echo htmlspecialchars($prod['product_brand']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="addon-card-stock-row">
                                        <span class="addon-stock-pill <?php echo $prod['product_stock'] <= 3 ? 'stock-low' : 'stock-available'; ?>">
                                            <i class="fas fa-boxes"></i> คงเหลือ <?php echo $prod['product_stock']; ?> ชิ้น
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="addon-card-divider"></div>

                            <div class="addon-card-actions">
                                <div class="addon-card-pricing">
                                    <div class="addon-price-group">
                                        <span class="addon-price-val"><?php echo number_format($prod['product_price']); ?></span>
                                        <span class="addon-price-unit">฿/หน่วย</span>
                                    </div>
                                    <button type="button" class="btn-addon-detail" onclick='openProductDetailModal(<?php echo $prod_data_json; ?>)' title="คลิกเพื่อดูรายละเอียด">
                                        <i class="fas fa-info-circle"></i> ดูสเปก
                                    </button>
                                </div>
                                <div class="addon-stepper-control">
                                    <button type="button" class="btn-stepper-adj" onclick="adjustProductQty(<?php echo $prod['product_id']; ?>, -1)" aria-label="ลดจำนวน">-</button>
                                    <input type="number" name="products[<?php echo $prod['product_id']; ?>]" id="prod_qty_<?php echo $prod['product_id']; ?>" class="addon-stepper-input qty-input" value="0" min="0" max="<?php echo $prod['product_stock']; ?>" data-price="<?php echo $prod['product_price']; ?>" data-name="<?php echo htmlspecialchars($prod['product_name']); ?>" data-type="<?php echo $prod['product_type']; ?>" onchange="onProductQtyChange(<?php echo $prod['product_id']; ?>)">
                                    <button type="button" class="btn-stepper-adj" onclick="adjustProductQty(<?php echo $prod['product_id']; ?>, 1)" aria-label="เพิ่มจำนวน">+</button>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endif;
                        endforeach; 
                        if(!$has_consumer):
                        ?>
                            <p class="text-muted">ไม่มีสินค้าบริโภคพร้อมจำหน่ายในขณะนี้</p>
                        <?php endif; ?>
                    </div>

                    <!-- หมวดหมู่: อุปกรณ์เช่า -->
                    <div class="addon-category-title">
                        <i class="fas fa-table-tennis"></i> อุปกรณ์เช่า (ไม้แบด/รองเท้า)
                    </div>
                    <div class="addon-cards-grid">
                        <?php 
                        $has_rental = false;
                        foreach($products as $prod): 
                            if($prod['product_type'] == 'อุปกรณ์เช่า'):
                                $has_rental = true;
                                $prod_data_json = json_encode([
                                    "id" => $prod["product_id"],
                                    "name" => $prod["product_name"],
                                    "brand" => $prod["product_brand"] ?? "",
                                    "model" => $prod["product_model"] ?? "",
                                    "size" => $prod["product_size"] ?? "",
                                    "level" => $prod["product_level"] ?? "",
                                    "price" => floatval($prod["product_price"]),
                                    "stock" => intval($prod["product_stock"]),
                                    "type" => $prod["product_type"],
                                    "image" => !empty($prod["product_image"]) ? "uploads/products/" . $prod["product_image"] : ""
                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                        ?>
                        <div class="addon-item-card" id="card_prod_<?php echo $prod['product_id']; ?>">
                            <div class="addon-card-header">
                                <div class="addon-item-thumb addon-thumb-clickable" 
                                     onclick='openProductDetailModal(<?php echo $prod_data_json; ?>)'
                                     title="คลิกเพื่อดูสเปกและภาพขยาย">
                                    <?php if(!empty($prod['product_image'])): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($prod['product_image']); ?>" alt="<?php echo htmlspecialchars($prod['product_name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-medal fa-2x"></i>
                                    <?php endif; ?>
                                    <span class="thumb-zoom-hint"><i class="fas fa-search-plus"></i></span>
                                </div>
                                <div class="addon-item-main-info">
                                    <div class="addon-card-title-row">
                                        <span class="addon-item-name addon-item-name-clickable" 
                                              onclick='openProductDetailModal(<?php echo $prod_data_json; ?>)'
                                              title="คลิกเพื่อดูสเปก">
                                            <?php echo htmlspecialchars($prod['product_name']); ?>
                                        </span>
                                        <?php if (!empty($prod['product_level'])): ?>
                                            <span class="badge-racket-level <?php echo $prod['product_level'] === 'มือใหม่' ? 'level-beginner' : 'level-pro'; ?>" title="ระดับ<?php echo htmlspecialchars($prod['product_level']); ?>">
                                                <i class="<?php echo $prod['product_level'] === 'มือใหม่' ? 'fas fa-seedling' : 'fas fa-crown'; ?>"></i> <?php echo htmlspecialchars($prod['product_level']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="addon-card-meta-row">
                                        <?php if(!empty($prod['product_brand'])): ?>
                                            <span class="addon-meta-brand"><?php echo htmlspecialchars($prod['product_brand']); ?></span>
                                        <?php endif; ?>
                                        <?php if(!empty($prod['product_model'])): ?>
                                            <span class="addon-meta-model"><?php echo htmlspecialchars($prod['product_model']); ?></span>
                                        <?php endif; ?>
                                        <?php if(!empty($prod['product_size'])): ?>
                                            <span class="addon-meta-size">ไซส์: <?php echo htmlspecialchars($prod['product_size']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="addon-card-stock-row">
                                        <span class="addon-stock-pill <?php echo $prod['product_stock'] <= 3 ? 'stock-low' : 'stock-available'; ?>">
                                            <i class="fas fa-boxes"></i> คงเหลือ <?php echo $prod['product_stock']; ?> ชิ้น
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="addon-card-divider"></div>

                            <div class="addon-card-actions">
                                <div class="addon-card-pricing">
                                    <div class="addon-price-group">
                                        <span class="addon-price-val"><?php echo number_format($prod['product_price']); ?></span>
                                        <span class="addon-price-unit">฿/หน่วย</span>
                                    </div>
                                    <button type="button" class="btn-addon-detail" onclick='openProductDetailModal(<?php echo $prod_data_json; ?>)' title="คลิกเพื่อดูสเปก">
                                        <i class="fas fa-info-circle"></i> ดูสเปก
                                    </button>
                                </div>
                                <div class="addon-stepper-control">
                                    <button type="button" class="btn-stepper-adj" onclick="adjustProductQty(<?php echo $prod['product_id']; ?>, -1)" aria-label="ลดจำนวน">-</button>
                                    <input type="number" name="products[<?php echo $prod['product_id']; ?>]" id="prod_qty_<?php echo $prod['product_id']; ?>" class="addon-stepper-input qty-input" value="0" min="0" max="<?php echo $prod['product_stock']; ?>" data-price="<?php echo $prod['product_price']; ?>" data-name="<?php echo htmlspecialchars($prod['product_name']); ?>" data-type="<?php echo $prod['product_type']; ?>" onchange="onProductQtyChange(<?php echo $prod['product_id']; ?>)">
                                    <button type="button" class="btn-stepper-adj" onclick="adjustProductQty(<?php echo $prod['product_id']; ?>, 1)" aria-label="เพิ่มจำนวน">+</button>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endif;
                        endforeach; 
                        if(!$has_rental):
                        ?>
                            <p class="text-muted">ไม่มีอุปกรณ์เช่าพร้อมให้บริการในขณะนี้</p>
                        <?php endif; ?>
                    </div>

                    <div class="wizard-nav-actions">
                        <button type="button" class="btn-wizard-back" onclick="goToStep(3)">
                            <i class="fas fa-arrow-left"></i> ย้อนกลับไปเลือกสนาม
                        </button>
                        <button type="button" class="btn-wizard-next" id="btnStep4Next" onclick="goToStep(5)">
                            ถัดไป: ตรวจสอบรายการ <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- Step 5: ตรวจสอบรายการและสรุปยอด (Review & Submit) -->
            <!-- ============================================== -->
            <div class="wizard-panel" id="stepPanel5">
                <div class="wizard-card step5-wrapper">
                    <div class="wizard-card-header">
                        <h3><i class="fas fa-receipt"></i> ขั้นตอนที่ 5: ตรวจสอบรายการและยืนยันการจอง</h3>
                        <p class="wizard-card-desc">ตรวจสอบรายละเอียดการจอง ค่าสนาม และบริการเสริมทั้งหมดก่อนเข้าสู่ขั้นตอนการชำระเงิน</p>
                    </div>

                    <div class="invoice-card">
                        <div class="invoice-header-row">
                            <div class="invoice-header-title">
                                <i class="fas fa-file-invoice-dollar"></i> ใบสรุปรายการจองสนาม
                            </div>
                            <?php if ($is_logged_in): ?>
                                <span class="pdpa-badge"><i class="fas fa-user-check"></i> สมาชิก: <?php echo htmlspecialchars($_SESSION['member_name'] ?? ''); ?></span>
                            <?php else: ?>
                                <span class="pdpa-badge badge-guest"><i class="fas fa-user-clock"></i> ผู้ใช้งานทั่วไป (ยังไม่เข้าสู่ระบบ)</span>
                            <?php endif; ?>
                        </div>

                        <!-- รายละเอียดการจองสนาม -->
                        <div class="invoice-detail-block">
                            <div class="invoice-detail-row">
                                <span>ชื่อเล่น / ชื่อก๊วนผู้จอง:</span>
                                <strong id="invNickname">-</strong>
                            </div>
                            <div class="invoice-detail-row">
                                <span>วันที่จอง:</span>
                                <strong id="invDate">-</strong>
                            </div>
                            <div class="invoice-detail-row">
                                <span>ช่วงเวลาที่เข้าเล่น:</span>
                                <strong id="invTime">-</strong>
                            </div>
                            <div class="invoice-detail-row">
                                <span>สนามที่จอง:</span>
                                <strong id="invCourt">-</strong>
                            </div>
                        </div>

                        <!-- ตารางแจกแจงรายการค่าใช้จ่าย -->
                        <table class="invoice-breakdown-table">
                            <thead>
                                <tr>
                                    <th>รายการ</th>
                                    <th>จำนวน</th>
                                    <th>อัตราค่าบริการ</th>
                                    <th>รวม (บาท)</th>
                                </tr>
                            </thead>
                            <tbody id="invoiceTableBody">
                                <!-- เรนเดอร์ด้วย JavaScript -->
                            </tbody>
                        </table>

                        <!-- แถบยอดชำระสุทธิ -->
                        <div class="invoice-total-strip">
                            <span class="invoice-total-label">ยอดชำระสุทธิทั้งหมด:</span>
                            <span class="invoice-total-amount" id="invGrandTotal">0 ฿</span>
                        </div>

                        <!-- คำเตือนเวลาล็อกสนาม 15 นาที -->
                        <div class="payment-warning-strip">
                            <i class="fas fa-stopwatch fa-lg text-danger"></i>
                            <div>
                                <strong>ระบบจะล็อกสนามให้ท่านเป็นเวลา 15 นาที:</strong><br>
                                เมื่อกดยืนยัน ระบบจะนำท่านไปสู่หน้าชำระเงินเพื่อโอนเงินและแนบสลิป
                            </div>
                        </div>

                        <?php if (!$is_logged_in): ?>
                            <!-- กล่องแจ้งเตือน Auth Gate สำหรับผู้ใช้ที่ยังไม่ล็อกอิน -->
                            <div class="guest-auth-gate-card" id="guestAuthGateCard">
                                <div class="guest-auth-header">
                                    <div class="guest-auth-icon">
                                        <i class="fas fa-user-lock"></i>
                                    </div>
                                    <div class="guest-auth-text">
                                        <h4 class="guest-auth-title">กรุณาเข้าสู่ระบบหรือสมัครสมาชิกก่อนชำระเงิน</h4>
                                        <p class="guest-auth-desc">
                                            ระบบได้จัดเตรียมสนามและบริการเสริมที่คุณเลือกไว้เรียบร้อยแล้ว เข้าสู่ระบบเพื่อยืนยันสิทธิ์การจอง สะสมแต้ม และดำเนินการชำระเงิน
                                        </p>
                                    </div>
                                </div>
                                <div class="guest-auth-actions">
                                    <button type="button" class="btn-guest-login" onclick="submitGuestBooking('login')">
                                        <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                                    </button>
                                    <button type="button" class="btn-guest-register" onclick="submitGuestBooking('register')">
                                        <i class="fas fa-user-plus"></i> สมัครสมาชิกใหม่
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="wizard-nav-actions">
                            <button type="button" class="btn-wizard-back" onclick="goToStep(4)">
                                <i class="fas fa-arrow-left"></i> ย้อนกลับไปแก้ไข
                            </button>
                            <?php if ($is_logged_in): ?>
                                <button type="submit" class="btn-submit-booking-final" id="btnFinalSubmitBooking">
                                    ยืนยันการจองและไปหน้าชำระเงิน <i class="fas fa-arrow-right"></i>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-submit-booking-final btn-guest-trigger" id="btnFinalSubmitBooking" onclick="submitGuestBooking('login')">
                                    <i class="fas fa-lock mr-6"></i> เข้าสู่ระบบเพื่อยืนยันและชำระเงิน <i class="fas fa-arrow-right"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>


    <!-- เรียกใช้ JavaScript สำหรับควบคุม Wizard (ระบบจะเริ่มต้นอัตโนมัติเมื่อพบ .wizard-page-wrapper) -->
    
    <!-- ==========================================================================
         Modal แสดงรายละเอียดอุปกรณ์และสเปก (Product Detail Modal)
         Strict Separation of Concerns & Black-Gold Brand Theme
         ========================================================================== -->
    <div id="productDetailModal" class="product-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="productDetailModalTitle">
        <div class="product-modal-card">
            <!-- Modal Header -->
            <div class="product-modal-header">
                <h4 class="product-modal-title" id="productDetailModalTitle">
                    <i class="fas fa-info-circle text-gold"></i> รายละเอียดอุปกรณ์และสเปก
                </h4>
                <button type="button" class="product-modal-close-btn" onclick="closeProductDetailModal()" aria-label="ปิดหน้าต่าง">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="product-modal-body">
                <div class="product-modal-grid">
                    <!-- Left: Large Image Box -->
                    <div class="product-modal-image-box">
                        <img id="detail_prod_image" src="" alt="" style="display: none;">
                        <div id="detail_prod_placeholder" class="product-modal-placeholder">
                            <i class="fas fa-table-tennis fa-3x text-gold"></i>
                        </div>
                    </div>

                    <!-- Right: Specs & Info -->
                    <div class="product-modal-info">
                        <div class="product-badge-row">
                            <span class="badge-prod-type" id="detail_prod_type_badge">อุปกรณ์เช่า</span>
                            <span id="detail_prod_level_badge" class="badge-racket-level" style="display: none;"></span>
                        </div>

                        <h3 class="product-modal-name" id="detail_prod_name">-</h3>

                        <div class="product-modal-spec-list">
                            <div class="product-spec-item">
                                <span class="product-spec-label">ยี่ห้อ:</span>
                                <span class="product-spec-val" id="detail_prod_brand">-</span>
                            </div>
                            <div class="product-spec-item">
                                <span class="product-spec-label">รุ่น:</span>
                                <span class="product-spec-val text-gold" id="detail_prod_model">-</span>
                            </div>
                            <div class="product-spec-item" id="detail_prod_size_row" style="display: none;">
                                <span class="product-spec-label">ไซส์:</span>
                                <span class="product-spec-val" id="detail_prod_size">-</span>
                            </div>
                        </div>

                        <div class="product-modal-price-box">
                            <div>
                                <span class="product-spec-label">อัตราค่าบริการ:</span>
                                <div class="product-modal-price" id="detail_prod_price">0.00 ฿ / หน่วย</div>
                            </div>
                            <div class="product-modal-stock" id="detail_prod_stock">คงเหลือ 0 ชิ้น</div>
                        </div>
                    </div>
                </div>

                <!-- Callout Boxes: Playing Characteristics & Recommendations -->
                <div id="detail_callout_beginner" class="product-feature-callout feature-beginner" style="display: none;">
                    <div class="callout-header">
                        <i class="fas fa-seedling text-success"></i>
                        <span>ความเหมาะสม: ระดับมือใหม่ (Beginner / ฝึกซ้อม / เพื่อสุขภาพ)</span>
                    </div>
                    <ul class="callout-list">
                        <li><strong>ความยืดหยุ่นของก้าน:</strong> ก้านยืดหยุ่นสูง (Flexible Shaft) ช่วยซับแรงกระแทก ตีง่าย สบายแขน</li>
                        <li><strong>จุดเด่นในการเล่น:</strong> จุด Sweet Spot กว้าง เซฟแรง ตีลูกถึงหลังคอร์ทได้ง่ายโดยไม่ต้องออกแรงตบเยอะ</li>
                        <li><strong>เหมาะสำหรับ:</strong> ผู้เริ่มต้นเล่นแบดมินตัน, ผู้เล่นออกกำลังกายทั่วไป, สุภาพสตรี และเยาวชน</li>
                    </ul>
                </div>

                <div id="detail_callout_pro" class="product-feature-callout feature-pro" style="display: none;">
                    <div class="callout-header">
                        <i class="fas fa-crown text-gold"></i>
                        <span>ความเหมาะสม: ระดับมือโปร (Advanced / แข่งขัน / สไตล์บุก)</span>
                    </div>
                    <ul class="callout-list">
                        <li><strong>ความยืดหยุ่นของก้าน:</strong> ก้านแข็งปานกลาง-แข็ง (Medium-Stiff) คืนตัวไว คุมทิศทางลูกได้แม่นยำสูง</li>
                        <li><strong>จุดเด่นในการเล่น:</strong> หัวหนัก (Head-Heavy) เพิ่มพลังตบ (Smash Power) สปีดลูกพุ่งเร็ว ตอบสนองเกมบุกหนักหน่วง</li>
                        <li><strong>เหมาะสำหรับ:</strong> ผู้เล่นระดับกลางถึงระดับสูง, นักกีฬา, ผู้ที่มีทักษะการส่งแรงจากข้อมือได้คล่อง</li>
                    </ul>
                </div>

                <div id="detail_callout_shoes" class="product-feature-callout feature-neutral" style="display: none;">
                    <div class="callout-header">
                        <i class="fas fa-shoe-prints text-gold"></i>
                        <span>คุณสมบัติรองเท้ากีฬาแบดมินตันมาตรฐาน</span>
                    </div>
                    <ul class="callout-list">
                        <li><strong>พื้นยาง Non-Marking:</strong> เกาะหนึบ ไม่ลื่นไถล ป้องกันรอยขีดข่วนบนพื้นยางคอร์ทมาตรฐาน</li>
                        <li><strong>ระบบ Cushioning:</strong> ลดแรงกระแทกบริเวณส้นเท้า ถนอมข้อเท้าและหัวเข่าขณะเปลี่ยนทิศทางกะทันหัน</li>
                    </ul>
                </div>

                <div id="detail_callout_general" class="product-feature-callout feature-neutral" style="display: none;">
                    <div class="callout-header">
                        <i class="fas fa-check-circle text-gold"></i>
                        <span>สินค้าคุณภาพมาตรฐานสนามแบดมินตัน ที.เอส. ปัตตานี</span>
                    </div>
                    <ul class="callout-list">
                        <li>สินค้าผ่านการคัดสรรคุณภาพและความสมบูรณ์พร้อมใช้งานสำหรับการลงคอร์ท</li>
                    </ul>
                </div>
            </div>

            <!-- Modal Footer (Stepper & Action) -->
            <div class="product-modal-footer">
                <div class="modal-stepper-wrap">
                    <span class="modal-stepper-label">จำนวนที่ต้องการเช่า:</span>
                    <div class="addon-stepper-control">
                        <button type="button" class="btn-stepper-adj" onclick="adjustDetailModalQty(-1)">-</button>
                        <input type="number" id="detail_modal_qty_input" class="addon-stepper-input" value="0" min="0" readonly>
                        <button type="button" class="btn-stepper-adj" onclick="adjustDetailModalQty(1)">+</button>
                    </div>
                </div>

                <button type="button" class="btn-modal-done" onclick="closeProductDetailModal()">
                    <i class="fas fa-check"></i> เรียบร้อย
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/member.js?v=<?php echo filemtime('assets/js/member.js'); ?>"></script>
</body>
</html>
