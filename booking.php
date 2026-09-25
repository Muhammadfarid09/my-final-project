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
    $stmt_court = $conn->query("SELECT * FROM Court WHERE court_status != 'ปิดปรับปรุง' ORDER BY court_id ASC");
    $courts = $stmt_court->fetchAll(PDO::FETCH_ASSOC);

    // ดึงข้อมูลสินค้าและอุปกรณ์เช่าที่มีในสต็อก (ดึงมาทุกคอลัมน์)
    $stmt_prod = $conn->query("SELECT * FROM Product WHERE product_stock > 0 ORDER BY product_type DESC");
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
    <link rel="stylesheet" href="assets/css/style.css?v=1.5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .matrix-legend {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            padding: 10px 14px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 12px;
            border: 1px solid #e2e8f0;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            display: inline-block;
        }
        .legend-offpeak { background: #d4edda; border: 1px solid #c3e6cb; }
        .legend-peak { background: #fff3cd; border: 1px solid #ffeeba; }
        .legend-booked { background: #f8d7da; border: 1px solid #f5c6cb; }
        .legend-closed { background: #f1f5f9; border: 1px solid #cbd5e1; }

        .btn-matrix-date {
            padding: 6px 14px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        .btn-matrix-date.active, .btn-matrix-date:hover {
            background: #1e3c72;
            color: #ffffff;
            border-color: #1e3c72;
        }

        .matrix-table-container {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            max-height: 480px;
        }
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            white-space: nowrap;
        }
        .matrix-table th, .matrix-table td {
            padding: 8px 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .matrix-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .matrix-court-col {
            position: sticky;
            left: 0;
            background: #f8fafc;
            font-weight: 600;
            text-align: left !important;
            min-width: 110px;
            z-index: 3;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        .matrix-cell {
            cursor: pointer;
            transition: all 0.15s;
            user-select: none;
            min-width: 75px;
        }
        .matrix-cell.cell-offpeak {
            background: #d4edda;
            color: #155724;
        }
        .matrix-cell.cell-offpeak:hover {
            background: #c3e6cb;
            transform: scale(1.04);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .matrix-cell.cell-peak {
            background: #fff3cd;
            color: #856404;
        }
        .matrix-cell.cell-peak:hover {
            background: #ffeeba;
            transform: scale(1.04);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .matrix-cell.cell-booked {
            background: #f8d7da;
            color: #721c24;
            cursor: not-allowed;
            opacity: 0.8;
        }
        .matrix-cell.cell-closed {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }
        .matrix-cell.selected {
            outline: 3px solid #1e3c72;
            outline-offset: -3px;
            box-shadow: inset 0 0 10px rgba(30, 60, 114, 0.4);
            font-weight: bold;
        }
        .slot-time-header {
            font-size: 11px;
        }
        .slot-peak-tag {
            font-size: 9px;
            background: #f59e0b;
            color: #fff;
            padding: 1px 4px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 2px;
        }
        .slot-offpeak-tag {
            font-size: 9px;
            background: #10b981;
            color: #fff;
            padding: 1px 4px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 2px;
        }
    </style>
</head>
<body style="background-color: #f4f6f9;">

    <!-- นำเข้า Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="booking-wrapper">
        <div class="booking-header">
            <h2><i class="fas fa-calendar-check"></i> จองสนามแบดมินตัน</h2>
            <p>กรุณาเลือกวัน เวลา สนาม และอุปกรณ์ที่คุณต้องการ (คิดราคาตามช่วงเวลา Peak / Off-peak อัตโนมัติ)</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- ส่วนตารางความพร้อมของสนาม (Time-Slot Matrix) -->
        <!-- ============================================== -->
        <div class="booking-card" style="margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
                <div>
                    <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-th" style="color: #1e3c72;"></i> ตารางความพร้อมของสนาม (Court Availability Matrix)
                    </h3>
                    <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">
                        คลิกเลือกช่องเวลาที่ว่างเพื่อเลือกลงฟอร์มจองได้ทันที
                    </p>
                </div>

                <!-- ตัวเลือกวันที่สำหรับตาราง Matrix -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="btn-matrix-date active" onclick="setMatrixDate('today', this)">วันนี้</button>
                    <button type="button" class="btn-matrix-date" onclick="setMatrixDate('tomorrow', this)">พรุ่งนี้</button>
                    <input type="date" id="matrix_date_picker" class="form-control" style="width: auto; padding: 6px 12px; font-size: 13px;" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" onchange="onMatrixDatePickerChange(this.value)">
                </div>
            </div>

            <!-- แถบคำอธิบายสัญลักษณ์สี (Color Legend) -->
            <div class="matrix-legend">
                <div class="legend-item"><span class="legend-color legend-offpeak"></span> ว่าง: ช่วงทั่วไป (Off-peak: 09:00-17:00 น.)</div>
                <div class="legend-item"><span class="legend-color legend-peak"></span> ว่าง: ช่วงยอดนิยม (Peak: 17:00-22:00 น.)</div>
                <div class="legend-item"><span class="legend-color legend-booked"></span> ไม่ว่าง / มีผู้จองแล้ว</div>
                <div class="legend-item"><span class="legend-color legend-closed"></span> นอกเวลาทำการ</div>
            </div>

            <!-- กล่องแสดงตาราง Matrix -->
            <div class="matrix-table-container" id="matrixContainer">
                <div style="text-align: center; padding: 40px; color: #64748b;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p style="margin-top: 10px;">กำลังโหลดตารางความพร้อมของสนาม...</p>
                </div>
            </div>
        </div>

        <!-- ฟอร์มส่งไปที่ actions/booking_db.php -->
        <form action="actions/booking_db.php" method="POST" id="bookingForm" onsubmit="return validateBooking()">
            <div class="booking-grid">
                
                <!-- ฝั่งซ้าย: ข้อมูลการจองสนาม -->
                <div class="booking-left">
                    <div class="booking-card">
                        <h3>1. ระบุวันและเวลา (ขั้นต่ำ 1 ชม.)</h3>
                        <div class="form-group">
                            <label>วันที่จอง</label>
                            <input type="date" name="booking_date" id="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" onchange="if(document.getElementById('matrix_date_picker')) document.getElementById('matrix_date_picker').value = this.value; loadCourtMatrix(this.value); calculateSummary();">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>เวลาเริ่มต้น</label>
                                <select name="start_time" id="start_time" class="form-control" required onchange="calculateSummary()">
                                    <option value="" disabled selected>เลือกเวลาเริ่ม</option>
                                    <?php for($i=9; $i<=21; $i++): $time = sprintf("%02d:00", $i); ?>
                                        <option value="<?php echo $time; ?>"><?php echo $time; ?> น. <?php echo ($i >= 17) ? '(Peak)' : '(Off-peak)'; ?></option>
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
                                <input type="radio" name="court_id" value="<?php echo $court['court_id']; ?>" 
                                       data-price="<?php echo $court['court_price_per_hour']; ?>" 
                                       data-peak-price="<?php echo $court['court_peak_price']; ?>" 
                                       data-offpeak-price="<?php echo $court['court_offpeak_price']; ?>" 
                                       required onchange="calculateSummary()">
                                <div class="court-box">
                                    <i class="fas fa-map-marked-alt fa-2x"></i>
                                    <h4><?php echo htmlspecialchars($court['court_name']); ?></h4>
                                    <div style="font-size: 11px; margin-top: 4px;">
                                        <span style="color: #059669; font-weight: 600;">Off-peak: <?php echo number_format($court['court_offpeak_price']); ?> ฿</span> | 
                                        <span style="color: #d97706; font-weight: 600;">Peak: <?php echo number_format($court['court_peak_price']); ?> ฿</span>
                                    </div>
                                    <span class="price-tag" style="margin-top: 6px; font-size: 11px;">ปกติ <?php echo number_format($court['court_price_per_hour']); ?> ฿/ชม.</span>
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
                                    
                                    <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                        <?php if(!empty($prod['product_brand'])): ?>
                                            <span>ยี่ห้อ: <?php echo htmlspecialchars($prod['product_brand']); ?></span>
                                        <?php endif; ?>

                                        <?php if(!empty($prod['product_model'])): ?>
                                            <span style="margin-left: 5px;">| รุ่น: <?php echo htmlspecialchars($prod['product_model']); ?></span>
                                        <?php endif; ?>
                                    </div>

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
    <script src="assets/js/member.js?v=1.6"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let today = new Date().toISOString().split('T')[0];
            loadCourtMatrix(today);
        });
    </script>
</body>
</html>
