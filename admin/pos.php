<?php
require_once 'includes/auth_check.php';

try {
    // 1. ดึงข้อมูลสินค้าทั้งหมด
    $stmt_prod = $conn->query("SELECT * FROM Product ORDER BY product_id DESC");
    $products = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

    // 2. ดึงข้อมูลสนามทั้งหมดเพื่อเปิดบริการ Walk-in
    $stmt_courts = $conn->query("SELECT * FROM Court WHERE court_status != 'ปิดปรับปรุง' ORDER BY court_id ASC");
    $courts = $stmt_courts->fetchAll(PDO::FETCH_ASSOC);

    // 3. ดึงรายชื่อสมาชิกสำหรับช่องค้นหา/แนะนำ
    $stmt_members = $conn->query("SELECT member_id, member_name, member_phone FROM Member WHERE member_status = 'ปกติ' ORDER BY member_name ASC LIMIT 50");
    $members_list = $stmt_members->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'POS ขายหน้าร้าน & เปิดสนาม Walk-in - Admin T.S. Pattani';
$page_header = '<i class="fas fa-cash-register"></i> ระบบขายหน้าร้าน & เปิดสนาม Walk-in (POS)';
include 'includes/header.php';
?>
<div class="pos-container">
    <!-- ฝั่งซ้าย: แคตตาล็อกสินค้า / ช่องค้นหา / สนาม Walk-in -->
    <div class="pos-products">
        <!-- ช่องค้นหาสินค้า Real-time -->
        <div class="pos-search-wrapper">
            <i class="fas fa-search pos-search-icon"></i>
            <input type="text" id="posProductSearch" class="pos-search-input" placeholder="ค้นหาชื่อสินค้า, อุปกรณ์เช่า หรือสนาม Walk-in..." autocomplete="off" oninput="searchPosProducts()">
            <button type="button" id="posSearchClear" class="pos-search-clear" onclick="clearPosSearch()"><i class="fas fa-times-circle"></i></button>
        </div>

        <!-- แถบหมวดหมู่สินค้า -->
        <div class="page-tabs m-0 mb-15">
            <button type="button" class="tab-btn tab-active" onclick="filterProducts('all', this)">ทั้งหมด</button>
            <button type="button" class="tab-btn tab-inactive" onclick="filterProducts('สินค้าบริโภค', this)"><i class="fas fa-coffee"></i> น้ำดื่ม/ลูกแบด</button>
            <button type="button" class="tab-btn tab-inactive" onclick="filterProducts('อุปกรณ์เช่า', this)"><i class="fas fa-table-tennis"></i> อุปกรณ์เช่า</button>
            <button type="button" class="tab-btn tab-inactive" onclick="filterProducts('สนาม', this)"><i class="fas fa-map-marker-alt"></i> เปิดสนาม Walk-in</button>
        </div>

        <div class="product-grid" id="productGrid">
            
            <!-- การ์ดสนามสำหรับเปิด Walk-in -->
            <?php foreach ($courts as $court): 
                $c_base = floatval($court['court_price_per_hour']) > 0 ? floatval($court['court_price_per_hour']) : 180;
                $c_peak = floatval($court['court_peak_price']) > 0 ? floatval($court['court_peak_price']) : 200;
                $c_offpeak = floatval($court['court_offpeak_price']) > 0 ? floatval($court['court_offpeak_price']) : 150;
                $court_name_escaped = htmlspecialchars($court['court_name'], ENT_QUOTES, 'UTF-8');
            ?>
                <div class="product-card pos-court-card-active" 
                     data-type="สนาม"
                     data-name="<?php echo htmlspecialchars(mb_strtolower($court['court_name'], 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
                     onclick="openWalkInCourtModal(<?php echo $court['court_id']; ?>, '<?php echo htmlspecialchars(addslashes($court['court_name']), ENT_QUOTES, 'UTF-8'); ?>', <?php echo $c_base; ?>, <?php echo $c_peak; ?>, <?php echo $c_offpeak; ?>)">
                    
                    <div class="product-img-placeholder w-80 h-80 mb-10 pos-court-badge-selected">
                        <i class="fas fa-map-marker-alt fa-2x"></i>
                    </div>
                    
                    <h5 class="pos-court-name"><?php echo $court_name_escaped; ?></h5>
                    <div class="pos-court-rate-box">
                        <div><strong class="pos-rate-std">จ.-พ.-ศ.:</strong> <?php echo number_format($c_base); ?> ฿</div>
                        <div><strong class="pos-rate-peak">ส.-อา.:</strong> <?php echo number_format($c_peak); ?> ฿</div>
                        <div><strong class="pos-rate-spc">อ.-พฤ.:</strong> <?php echo number_format($c_offpeak); ?> ฿</div>
                    </div>
                    <p class="stock pos-court-available-hint">
                        <i class="fas fa-check-circle"></i> พร้อมเปิด Walk-in
                    </p>
                </div>
            <?php endforeach; ?>

            <!-- รายการสินค้าและอุปกรณ์เช่าปกติ -->
            <?php foreach ($products as $row): 
                $is_out = ($row['product_stock'] <= 0);
                $p_name_escaped = htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8');
            ?>
                <div class="product-card <?php echo $is_out ? 'out-of-stock' : ''; ?>" 
                     data-type="<?php echo htmlspecialchars($row['product_type'], ENT_QUOTES, 'UTF-8'); ?>"
                     data-name="<?php echo htmlspecialchars(mb_strtolower($row['product_name'], 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
                     onclick="<?php echo $is_out ? 'return false;' : "addToCart({$row['product_id']}, '" . htmlspecialchars(addslashes($row['product_name']), ENT_QUOTES, 'UTF-8') . "', {$row['product_price']}, {$row['product_stock']})"; ?>">
                    
                    <?php if (!empty($row['product_image'])): ?>
                        <img src="../uploads/products/<?php echo htmlspecialchars($row['product_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $p_name_escaped; ?>">
                    <?php else: ?>
                        <div class="product-img-placeholder w-80 h-80 mb-10">
                            <i class="fas fa-image fa-2x"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h5><?php echo $p_name_escaped; ?></h5>
                    <p class="price"><?php echo number_format($row['product_price'], 2); ?> ฿</p>
                    <p class="stock"><i class="fas fa-box"></i> คงเหลือ: <?php echo intval($row['product_stock']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ฝั่งขวา: ตะกร้าสินค้าและคิดเงิน -->
    <div class="pos-cart">
        <div class="pos-cart-header">
            <h4 class="pos-cart-title"><i class="fas fa-shopping-cart text-primary"></i> รายการสั่งซื้อ / เปิดสนาม</h4>
            <button type="button" class="btn-clear-cart" id="btnClearCart" onclick="confirmClearCart()" title="ล้างรายการทั้งหมดในตะกร้า">
                <i class="fas fa-trash-alt"></i> ล้างตะกร้า
            </button>
        </div>
        
        <div id="cartEmpty" class="text-center text-gray py-30">
            <i class="fas fa-cart-arrow-down fa-3x mb-10"></i><br>ยังไม่มีรายการในตะกร้า
        </div>

        <table class="cart-table d-none" id="cartTable">
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th class="text-center" style="width: 85px;">จำนวน</th>
                    <th class="text-right" style="width: 75px;">รวม (฿)</th>
                    <th style="width: 32px;"></th>
                </tr>
            </thead>
            <tbody id="cartBody">
            </tbody>
        </table>

        <!-- กล่องสรุปยอดสุทธิขนาดใหญ่ -->
        <div class="cart-summary">
            <div class="summary-row">
                <span>จำนวนรายการ:</span>
                <span id="totalItems" class="font-medium text-dark-custom">0 ชิ้น</span>
            </div>
            <div class="summary-row summary-total">
                <span class="pos-total-label">ยอดสุทธิ:</span>
                <span id="totalPrice" class="pos-total-amount">0.00 ฿</span>
            </div>
        </div>

        <form action="actions/pos_checkout_db.php" method="POST" id="checkoutForm" onsubmit="return validateCheckout(event)">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="cart_data" id="cartDataInput">
            <input type="hidden" name="total_amount" id="totalAmountInput">
            <input type="hidden" name="payment_method" id="paymentMethodInput" value="cash">

            <!-- ข้อมูลลูกค้า / สมาชิกสะสมแต้มและบันทึกประวัติเช่า -->
            <div class="pos-customer-box mb-15">
                <label class="text-13 text-gray font-bold mb-6 d-block">
                    <i class="fas fa-user-check text-primary"></i> ลูกค้า / สมาชิก (สะสมแต้ม & ประวัติเช่า):
                </label>
                <div class="pos-search-wrapper mb-0">
                    <i class="fas fa-phone-alt pos-search-icon"></i>
                    <input type="text" name="customer_phone" id="posCustomerPhone" class="pos-search-input" placeholder="เบอร์โทรศัพท์ 10 หลัก (ถ้ามี)..." list="posMemberList" autocomplete="off" maxlength="10">
                    <datalist id="posMemberList">
                        <?php foreach ($members_list as $m): ?>
                            <option value="<?php echo htmlspecialchars($m['member_phone'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($m['member_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($m['member_phone'], ENT_QUOTES, 'UTF-8'); ?>)</option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>

            <!-- เลือกวิธีชำระเงิน -->
            <div class="payment-methods">
                <button type="button" class="payment-btn active" id="btnPayCash" onclick="selectPaymentMethod('cash')"><i class="fas fa-money-bill-wave"></i> เงินสด</button>
                <button type="button" class="payment-btn" id="btnPayQR" onclick="selectPaymentMethod('qr')"><i class="fas fa-qrcode"></i> โอนเงิน (QR)</button>
            </div>

            <!-- ส่วนชำระเงินสด -->
            <div id="cashPaymentSection">
                <label class="text-13 text-gray font-bold mb-6 d-block">รับเงินสดมา (บาท):</label>
                <input type="number" id="cashReceived" class="money-input" placeholder="0.00" step="0.01" oninput="calculateChange()">
                
                <!-- ปุ่มลัดจำนวนเงินสด (Quick Cash Presets) -->
                <div class="quick-cash-presets">
                    <button type="button" class="btn-quick-cash" onclick="setQuickCash('exact')">พอดี</button>
                    <button type="button" class="btn-quick-cash" onclick="setQuickCash(100)">100</button>
                    <button type="button" class="btn-quick-cash" onclick="setQuickCash(500)">500</button>
                    <button type="button" class="btn-quick-cash" onclick="setQuickCash(1000)">1,000</button>
                </div>

                <div class="summary-row mb-15 font-bold" id="changeRow">
                    <span>เงินทอน:</span>
                    <span id="changeAmount" class="text-danger-custom font-bold text-16">0.00 ฿</span>
                </div>
            </div>

            <!-- ส่วนชำระแบบ QR Code -->
            <div id="qrPaymentSection" class="d-none">
                <p class="m-0 mb-10 text-14 font-bold text-dark-custom">สแกนเพื่อชำระเงิน (จำลอง)</p>
                <div class="qr-placeholder pos-cart-empty-text">
                    <i class="fas fa-qrcode fa-5x text-primary mb-10"></i>
                    <p class="text-12 text-muted-custom">พร้อมเพย์ 099-324-1657 (ที.เอส. ปัตตานี)</p>
                </div>
            </div>

            <button type="submit" class="btn-checkout" id="btnCheckout" disabled>
                <i class="fas fa-check-circle"></i> ยืนยันการชำระเงิน & ออกใบเสร็จ
            </button>
        </form>
    </div>
</div>
</main>
</div>

<!-- Modal สำหรับตั้งค่าเปิดสนาม Walk-in -->
<div id="walkInCourtModal" class="modal-overlay pos-modal-backdrop">
    <div class="modal-content pos-modal-card">
        <h4 class="pos-modal-header">
            <i class="fas fa-calendar-plus text-primary"></i> เปิดสนาม Walk-in หน้าเคาน์เตอร์
        </h4>

        <input type="hidden" id="modal_court_id">
        <input type="hidden" id="modal_court_base_price">
        <input type="hidden" id="modal_court_peak_price">
        <input type="hidden" id="modal_court_offpeak_price">

        <div class="pos-member-info-box">
            <div><strong>สนาม:</strong> <span id="modal_court_name" class="font-bold text-primary pos-member-name"></span></div>
            <div><strong>อัตราค่าบริการ:</strong> <span id="modal_court_price_rate" class="font-bold"></span> บาท / ชั่วโมง <span id="modal_court_rate_badge" class="pos-tier-badge-pill"></span></div>
        </div>

        <div class="form-group pos-form-group-mb">
            <label class="font-bold pos-form-label-sm">วันที่ใช้งาน:</label>
            <input type="date" id="modal_court_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" onchange="calculateCourtTotal()">
        </div>

        <div class="pos-payment-methods-grid">
            <div class="form-group">
                <label class="font-bold pos-form-label-sm">เวลาเริ่มต้น:</label>
                <select id="modal_court_start_time" class="form-control" onchange="calculateCourtTotal()">
                    <?php 
                    $curr_hour = intval(date('H'));
                    for ($h = 8; $h <= 22; $h++): 
                        $time_str = sprintf('%02d:00', $h);
                        $sel = ($h === max(8, min(22, $curr_hour))) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $time_str; ?>" <?php echo $sel; ?>><?php echo $time_str; ?> น.</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="font-bold pos-form-label-sm">จำนวนชั่วโมง:</label>
                <select id="modal_court_hours" class="form-control" onchange="calculateCourtTotal()">
                    <option value="1" selected>1 ชั่วโมง</option>
                    <option value="2">2 ชั่วโมง</option>
                    <option value="3">3 ชั่วโมง</option>
                    <option value="4">4 ชั่วโมง</option>
                </select>
            </div>
        </div>

        <div class="form-group pos-form-group-mb-15">
            <label class="font-bold pos-form-label-sm">เบอร์โทรลูกค้า (ถ้าเป็นสมาชิกจะได้รับพอยท์):</label>
            <input type="text" id="modal_court_member_phone" class="form-control" maxlength="10" placeholder="ระบุเบอร์โทร 10 หลัก (ถ้ามี)">
        </div>

        <div class="pos-net-summary-box">
            <span class="pos-net-summary-label">ยอดรวมค่าสนาม:</span>
            <span class="pos-net-summary-val"><span id="modal_court_total_price">0.00</span> ฿</span>
        </div>

        <div class="pos-modal-footer-btns">
            <button type="button" class="btn-cancel" onclick="closeWalkInCourtModal()">ยกเลิก</button>
            <button type="button" class="btn-submit btn-pos-primary" onclick="addCourtToCart()">
                <i class="fas fa-cart-plus"></i> เพิ่มสนามลงบิล POS
            </button>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['print_receipt_id']) || isset($_SESSION['print_booking_id'])): ?>
    <input type="hidden" id="trigger_receipt_id" value="<?php echo $_SESSION['print_receipt_id'] ?? ''; ?>">
    <input type="hidden" id="trigger_booking_id" value="<?php echo $_SESSION['print_booking_id'] ?? ''; ?>">
    <?php 
    unset($_SESSION['print_receipt_id']); 
    unset($_SESSION['print_booking_id']); 
    ?>
<?php endif; ?>

<!-- เรียกใช้ไฟล์ JS กลางและโมดูลเฉพาะทาง POS -->
<script src="../assets/js/admin.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/admin.js'); ?>"></script>
<script src="../assets/js/pos.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/pos.js'); ?>"></script>
</body>
</html>