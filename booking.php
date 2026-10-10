<?php
session_start();
require_once 'config/config.php';
require_once 'admin/includes/auto_cancel.php';

// บังคับล็อกอิน หากยังไม่ล็อกอินให้ไปหน้า login
if (!isset($_SESSION['member_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนทำการจองสนาม";
    header("Location: login.php");
    exit();
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

$default_nickname = htmlspecialchars($_SESSION['member_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองสนามแบดมินตัน - T.S. Pattani</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.1">
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <link rel="stylesheet" href="assets/css/booking-wizard.css?v=1.0">
    <link rel="stylesheet" href="assets/css/responsive-mobile.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="booking-page-body">

    <!-- นำเข้า Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="wizard-page-wrapper">
        
        <!-- Header Banner -->
        <div class="wizard-header-banner">
            <h2><i class="fas fa-calendar-check"></i> จองสนามแบดมินตัน</h2>
            <p>ระบบจองสนามและบริการเสริมทีละขั้นตอน พร้อมคำนวณราคาจริงตามวันในสัปดาห์</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
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
                            <div class="sticky-metric-pill">
                                <i class="fas fa-clock"></i>
                                <span id="stickyHoursDisplay">[ 0 Hour ]</span>
                            </div>
                            <div class="sticky-metric-pill">
                                <i class="fas fa-map-marker-alt"></i>
                                <span id="stickyCourtDisplay">[ ยังไม่ได้เลือกสนาม ]</span>
                            </div>
                            <div class="sticky-metric-pill sticky-metric-total">
                                <i class="fas fa-tag"></i>
                                <span id="stickyTotalDisplay">[ Total 0 ฿ ]</span>
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
                        ?>
                        <div class="addon-item-card">
                            <div class="addon-item-thumb">
                                <?php if(!empty($prod['product_image'])): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($prod['product_image']); ?>" alt="<?php echo htmlspecialchars($prod['product_name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-tint fa-2x"></i>
                                <?php endif; ?>
                            </div>
                            <div class="addon-item-details">
                                <div class="addon-item-name"><?php echo htmlspecialchars($prod['product_name']); ?></div>
                                <div class="addon-item-meta">
                                    <?php if(!empty($prod['product_brand'])): ?>
                                        <span>ยี่ห้อ: <?php echo htmlspecialchars($prod['product_brand']); ?></span>
                                    <?php endif; ?>
                                    <span>(คงเหลือ <?php echo $prod['product_stock']; ?>)</span>
                                </div>
                                <div class="addon-item-price"><?php echo number_format($prod['product_price']); ?> ฿/หน่วย</div>
                            </div>
                            <div class="addon-stepper-control">
                                <button type="button" class="btn-stepper-adj" onclick="adjustProductQty(<?php echo $prod['product_id']; ?>, -1)">-</button>
                                <input type="number" name="products[<?php echo $prod['product_id']; ?>]" id="prod_qty_<?php echo $prod['product_id']; ?>" class="addon-stepper-input qty-input" value="0" min="0" max="<?php echo $prod['product_stock']; ?>" data-price="<?php echo $prod['product_price']; ?>" data-name="<?php echo htmlspecialchars($prod['product_name']); ?>" data-type="<?php echo $prod['product_type']; ?>" onchange="onProductQtyChange(<?php echo $prod['product_id']; ?>)">
                                <button type="button" class="btn-stepper-adj" onclick="adjustProductQty(<?php echo $prod['product_id']; ?>, 1)">+</button>
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
                        ?>
                        <div class="addon-item-card">
                            <div class="addon-item-thumb">
                                <?php if(!empty($prod['product_image'])): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($prod['product_image']); ?>" alt="<?php echo htmlspecialchars($prod['product_name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-medal fa-2x"></i>
                                <?php endif; ?>
                            </div>
                            <div class="addon-item-details">
                                <div class="addon-item-name"><?php echo htmlspecialchars($prod['product_name']); ?></div>
                                <div class="addon-item-meta">
                                    <?php if(!empty($prod['product_brand'])): ?>
                                        <span><?php echo htmlspecialchars($prod['product_brand']); ?></span>
                                    <?php endif; ?>
                                    <?php if(!empty($prod['product_size'])): ?>
                                        <span>ไซส์: <?php echo htmlspecialchars($prod['product_size']); ?></span>
                                    <?php endif; ?>
                                    <span>(คงเหลือ <?php echo $prod['product_stock']; ?>)</span>
                                </div>
                                <div class="addon-item-price"><?php echo number_format($prod['product_price']); ?> ฿/หน่วย</div>
                            </div>
                            <div class="addon-stepper-control">
                                <button type="button" class="btn-stepper-adj" onclick="adjustProductQty(<?php echo $prod['product_id']; ?>, -1)">-</button>
                                <input type="number" name="products[<?php echo $prod['product_id']; ?>]" id="prod_qty_<?php echo $prod['product_id']; ?>" class="addon-stepper-input qty-input" value="0" min="0" max="<?php echo $prod['product_stock']; ?>" data-price="<?php echo $prod['product_price']; ?>" data-name="<?php echo htmlspecialchars($prod['product_name']); ?>" data-type="<?php echo $prod['product_type']; ?>" onchange="onProductQtyChange(<?php echo $prod['product_id']; ?>)">
                                <button type="button" class="btn-stepper-adj" onclick="adjustProductQty(<?php echo $prod['product_id']; ?>, 1)">+</button>
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
                        <div>
                            <button type="button" class="btn-wizard-skip" onclick="goToStep(5)">
                                ข้ามขั้นตอนนี้ <i class="fas fa-forward"></i>
                            </button>
                            <button type="button" class="btn-wizard-next" onclick="goToStep(5)">
                                ถัดไป: ตรวจสอบรายการ <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
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
                            <span class="pdpa-badge"><i class="fas fa-user-check"></i> ข้อมูลผู้จองถูกต้อง</span>
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

                        <div class="wizard-nav-actions">
                            <button type="button" class="btn-wizard-back" onclick="goToStep(4)">
                                <i class="fas fa-arrow-left"></i> กลับไปแก้ไขบริการเสริม
                            </button>
                            <button type="submit" class="btn-submit-booking-final" id="btnFinalSubmitBooking">
                                ยืนยันการจองและไปหน้าชำระเงิน <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <!-- เรียกใช้ JavaScript สำหรับควบคุม Wizard -->
    <script src="assets/js/member.js?v=2.0"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // เริ่มต้นระบบ 5-Step Booking Wizard
            if (typeof initBookingWizard === 'function') {
                initBookingWizard();
            }
        });
    </script>
</body>
</html>
