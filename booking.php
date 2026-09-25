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
    // ดึงข้อมูลสนามทั้งหมดที่เปิดใช้งาน
    $stmt_court = $conn->query("SELECT * FROM COURT WHERE court_status != 'ปิดปรับปรุง'");
    $courts = $stmt_court->fetchAll(PDO::FETCH_ASSOC);

    // ดึงข้อมูลสินค้าและอุปกรณ์เช่าที่มีในสต็อก (ดึงมาทุกคอลัมน์)
    $stmt_prod = $conn->query("SELECT * FROM PRODUCT WHERE product_stock > 0 ORDER BY product_type DESC");
    $products = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองสนาม - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="background-color: #f4f6f9;">

    <!-- นำเข้า Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="booking-wrapper">
        <div class="booking-header">
            <h2><i class="fas fa-calendar-check"></i> จองสนามแบดมินตัน</h2>
            <p>กรุณาเลือกวัน เวลา สนาม และอุปกรณ์ที่คุณต้องการ (ขั้นต่ำ 1 ชั่วโมง)</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- ฟอร์มส่งไปที่ actions/booking_db.php -->
        <form action="actions/booking_db.php" method="POST" id="bookingForm" onsubmit="return validateBooking()">
            <div class="booking-grid">
                
                <!-- ฝั่งซ้าย: ข้อมูลการจองสนาม -->
                <div class="booking-left">
                    <div class="booking-card">
                        <h3>1. ระบุวันและเวลา (ขั้นต่ำ 1 ชม.)</h3>
                        <div class="form-group">
                            <label>วันที่จอง</label>
                            <input type="date" name="booking_date" id="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" onchange="calculateSummary()">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>เวลาเริ่มต้น</label>
                                <select name="start_time" id="start_time" class="form-control" required onchange="calculateSummary()">
                                    <option value="" disabled selected>เลือกเวลาเริ่ม</option>
                                    <?php for($i=9; $i<=20; $i++): $time = sprintf("%02d:00", $i); ?>
                                        <option value="<?php echo $time; ?>"><?php echo $time; ?> น.</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>เวลาสิ้นสุด</label>
                                <select name="end_time" id="end_time" class="form-control" required onchange="calculateSummary()">
                                    <option value="" disabled selected>เลือกเวลาสิ้นสุด</option>
                                    <?php for($i=10; $i<=22; $i++): $time = sprintf("%02d:00", $i); ?>
                                        <option value="<?php echo $time; ?>"><?php echo $time; ?> น.</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <small id="time-error" style="color: red; display: none;"><i class="fas fa-times-circle"></i> ต้องจองขั้นต่ำ 1 ชั่วโมงขึ้นไป</small>
                    </div>

                    <div class="booking-card" style="margin-top: 20px;">
                        <h3>2. เลือกสนาม</h3>
                        <div class="court-selector">
                            <?php foreach($courts as $court): ?>
                            <label class="court-option">
                                <input type="radio" name="court_id" value="<?php echo $court['court_id']; ?>" data-price="<?php echo $court['court_price_per_hour']; ?>" required onchange="calculateSummary()">
                                <div class="court-box">
                                    <i class="fas fa-map-marked-alt fa-2x"></i>
                                    <h4><?php echo htmlspecialchars($court['court_name']); ?></h4>
                                    <span class="price-tag"><?php echo number_format($court['court_price_per_hour']); ?> ฿/ชม.</span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- ฝั่งขวา: เลือกอุปกรณ์และสรุปยอด (One-stop Booking) -->
                <div class="booking-right">
                    
                    <div class="booking-card">
                        <h3><i class="fas fa-shopping-basket"></i> 3. บริการเสริม</h3>
                        
                        <!-- ============================================== -->
                        <!-- หมวดหมู่: สินค้าบริโภค -->
                        <!-- ============================================== -->
                        <div class="product-category-title">
                            <i class="fas fa-coffee"></i> สินค้าบริโภค (น้ำดื่ม/ลูกแบด)
                        </div>
                        <div class="product-list-booking">
                            <?php 
                            foreach($products as $prod): 
                                if($prod['product_type'] == 'สินค้าบริโภค'):
                            ?>
                            <div class="product-item">
                                <div class="prod-img">
                                    <?php if(!empty($prod['product_image'])): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($prod['product_image']); ?>" alt="product">
                                    <?php else: ?>
                                        <div class="prod-img-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="prod-info">
                                    <strong><?php echo htmlspecialchars($prod['product_name']); ?></strong>
                                    
                                    <!-- สำหรับสินค้าบริโภค แสดงแค่ยี่ห้อ (ถ้ามี) -->
                                    <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                        <?php if(!empty($prod['product_brand'])): ?>
                                            <span>ยี่ห้อ: <?php echo htmlspecialchars($prod['product_brand']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="font-size: 13px; color: #007bff; font-weight: bold; margin-top: 4px;">
                                        <?php echo number_format($prod['product_price']); ?> ฿/หน่วย
                                    </div>
                                </div>
                                <div class="prod-qty">
                                    <input type="number" name="products[<?php echo $prod['product_id']; ?>]" class="qty-input form-control" min="0" max="<?php echo $prod['product_stock']; ?>" value="0" data-price="<?php echo $prod['product_price']; ?>" data-type="<?php echo $prod['product_type']; ?>" oninput="calculateSummary()">
                                </div>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>

                        <!-- ============================================== -->
                        <!-- หมวดหมู่: อุปกรณ์เช่า -->
                        <!-- ============================================== -->
                        <div class="product-category-title" style="margin-top: 20px;">
                            <i class="fas fa-table-tennis"></i> อุปกรณ์เช่า (ไม้แบด/รองเท้า)
                        </div>
                        <div class="product-list-booking">
                            <?php 
                            foreach($products as $prod): 
                                if($prod['product_type'] == 'อุปกรณ์เช่า'):
                            ?>
                            <div class="product-item">
                                <div class="prod-img">
                                    <?php if(!empty($prod['product_image'])): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($prod['product_image']); ?>" alt="product">
                                    <?php else: ?>
                                        <div class="prod-img-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="prod-info">
                                    <strong><?php echo htmlspecialchars($prod['product_name']); ?></strong>
                                    
                                    <!-- แสดงรายละเอียด ยี่ห้อ และ รุ่น -->
                                    <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                        <?php if(!empty($prod['product_brand'])): ?>
                                            <span>ยี่ห้อ: <?php echo htmlspecialchars($prod['product_brand']); ?></span>
                                        <?php endif; ?>

                                        <?php if(!empty($prod['product_model'])): ?>
                                            <span style="margin-left: 5px;">| รุ่น: <?php echo htmlspecialchars($prod['product_model']); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- แสดงรายละเอียด ระดับ และ ไซส์ -->
                                    <div style="font-size: 11px; margin-top: 4px; display: flex; align-items: center; gap: 8px;">
                                        <?php if(!empty($prod['product_level'])): ?>
                                            <span class="badge-level <?php echo ($prod['product_level'] == 'มือโปร') ? 'level-gold' : 'level-silver'; ?>" style="font-size: 10px; padding: 2px 6px;">
                                                <i class="fas fa-medal"></i> <?php echo htmlspecialchars($prod['product_level']); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if(!empty($prod['product_size'])): ?>
                                            <span style="color: #495057; font-weight: 500; background: #e9ecef; padding: 2px 6px; border-radius: 4px; font-size: 10px;">
                                                <i class="fas fa-shoe-prints"></i> ไซส์: <?php echo htmlspecialchars($prod['product_size']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="font-size: 13px; color: #007bff; font-weight: bold; margin-top: 6px;">
                                        <?php echo number_format($prod['product_price']); ?> ฿/หน่วย
                                    </div>
                                </div>
                                <div class="prod-qty">
                                    <input type="number" name="products[<?php echo $prod['product_id']; ?>]" class="qty-input form-control" min="0" max="<?php echo $prod['product_stock']; ?>" value="0" data-price="<?php echo $prod['product_price']; ?>" data-type="<?php echo $prod['product_type']; ?>" oninput="calculateSummary()">
                                </div>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>

                    <!-- สรุปยอดรวมก่อนยืนยัน -->
                    <div class="booking-summary">
                        <h3>สรุปรายการจอง</h3>
                        <div class="summary-row">
                            <span>ระยะเวลาจอง:</span>
                            <span id="sum-hours">0 ชั่วโมง</span>
                        </div>
                        <div class="summary-row">
                            <span>ค่าสนาม:</span>
                            <span id="sum-court">0 ฿</span>
                        </div>
                        <div class="summary-row">
                            <span>ค่าเช่าอุปกรณ์:</span>
                            <span id="sum-rent">0 ฿</span>
                        </div>
                        <div class="summary-row">
                            <span>ค่าสินค้า:</span>
                            <span id="sum-product">0 ฿</span>
                        </div>
                        <hr style="margin: 15px 0; border: 0; border-top: 1px dashed #ccc;">
                        <div class="summary-row total-row">
                            <span>ยอดชำระสุทธิ:</span>
                            <span id="sum-total" style="color: #007bff; font-size: 22px;">0 ฿</span>
                        </div>

                        <!-- ซ่อนค่าผลรวมไว้เพื่อส่งไป PHP -->
                        <input type="hidden" name="total_court_price" id="input_court_price" value="0">
                        <input type="hidden" name="total_rental_price" id="input_rental_price" value="0">
                        <input type="hidden" name="total_product_price" id="input_product_price" value="0">
                        <input type="hidden" name="total_price" id="input_total_price" value="0">

                        <button type="submit" class="btn-confirm-booking" id="btnSubmitBooking" disabled>
                            ดำเนินการจองและชำระเงิน <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- เรียกใช้ไฟล์ JS ฝั่งลูกค้าที่รวมโค้ดทั้งหมดไว้แล้ว -->
    <script src="assets/js/member.js?v=1.3"></script>
</body>
</html>
