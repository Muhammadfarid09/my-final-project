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
    <!-- ฝั่งซ้าย: รายการสินค้าและสนาม -->
    <div class="pos-products">
        <div class="page-tabs m-0">
            <button type="button" class="tab-btn tab-active" onclick="filterProducts('all', this)">ทั้งหมด</button>
            <button type="button" class="tab-btn tab-inactive" onclick="filterProducts('สินค้าบริโภค', this)"><i class="fas fa-coffee"></i> น้ำดื่ม/ลูกแบด</button>
            <button type="button" class="tab-btn tab-inactive" onclick="filterProducts('อุปกรณ์เช่า', this)"><i class="fas fa-table-tennis"></i> อุปกรณ์เช่า</button>
            <button type="button" class="tab-btn tab-inactive" onclick="filterProducts('สนาม', this)"><i class="fas fa-map-marker-alt"></i> เปิดสนาม Walk-in</button>
        </div>

        <div class="product-grid" id="productGrid">
            
            <!-- การ์ดสนามสำหรับเปิด Walk-in -->
            <?php foreach ($courts as $court): ?>
                <div class="product-card" 
                     data-type="สนาม"
                     style="border: 2px solid #2563eb; background: #f8fafc;"
                     onclick="openWalkInCourtModal(<?php echo $court['court_id']; ?>, '<?php echo htmlspecialchars(addslashes($court['court_name'])); ?>', <?php echo $court['court_price_per_hour']; ?>)">
                    
                    <div class="product-img-placeholder w-80 h-80 mb-10" style="background: #eff6ff; color: #2563eb;">
                        <i class="fas fa-map-marker-alt fa-2x"></i>
                    </div>
                    
                    <h5 style="color: #1e3c72; font-size: 15px; margin: 5px 0;"><?php echo htmlspecialchars($court['court_name']); ?></h5>
                    <p class="price" style="color: #2563eb; font-weight: bold;"><?php echo number_format($court['court_price_per_hour'], 2); ?> ฿ / ชม.</p>
                    <p class="stock" style="color: #059669; font-weight: 500;">
                        <i class="fas fa-check-circle"></i> พร้อมเปิด Walk-in
                    </p>
                </div>
            <?php endforeach; ?>

            <!-- รายการสินค้าและอุปกรณ์เช่าปกติ -->
            <?php foreach ($products as $row): 
                $is_out = ($row['product_stock'] <= 0);
            ?>
                <div class="product-card <?php echo $is_out ? 'out-of-stock' : ''; ?>" 
                     data-type="<?php echo htmlspecialchars($row['product_type']); ?>"
                     onclick="addToCart(<?php echo $row['product_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['product_name'])); ?>', <?php echo $row['product_price']; ?>, <?php echo $row['product_stock']; ?>)">
                    
                    <?php if (!empty($row['product_image'])): ?>
                        <img src="../uploads/products/<?php echo htmlspecialchars($row['product_image']); ?>" alt="Img">
                    <?php else: ?>
                        <div class="product-img-placeholder w-80 h-80 mb-10">
                            <i class="fas fa-image fa-2x"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h5><?php echo htmlspecialchars($row['product_name']); ?></h5>
                    <p class="price"><?php echo number_format($row['product_price'], 2); ?> ฿</p>
                    <p class="stock"><i class="fas fa-box"></i> คงเหลือ: <?php echo $row['product_stock']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ฝั่งขวา: ตะกร้าสินค้าและคิดเงิน -->
    <div class="pos-cart">
        <h4 class="mt-0 border-b-2 pb-10"><i class="fas fa-shopping-cart"></i> รายการสั่งซื้อ / เปิดสนาม</h4>
        
        <div id="cartEmpty" class="text-center text-gray py-30">
            <i class="fas fa-cart-arrow-down fa-3x mb-10"></i><br>ยังไม่มีรายการในตะกร้า
        </div>

        <table class="cart-table" id="cartTable" style="display: none;">
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th class="text-center">จำนวน/เวลา</th>
                    <th class="text-right">รวม</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="cartBody">
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="summary-row">
                <span>จำนวนรายการ:</span>
                <span id="totalItems">0 ชิ้น</span>
            </div>
            <div class="summary-row summary-total">
                <span>ยอดสุทธิ:</span>
                <span id="totalPrice">0.00 ฿</span>
            </div>
        </div>

        <form action="actions/pos_checkout_db.php" method="POST" id="checkoutForm" onsubmit="return validateCheckout()">
            <input type="hidden" name="cart_data" id="cartDataInput">
            <input type="hidden" name="total_amount" id="totalAmountInput">
            <input type="hidden" name="payment_method" id="paymentMethodInput" value="cash">

            <!-- เลือกวิธีชำระเงิน -->
            <div class="payment-methods">
                <button type="button" class="payment-btn active" id="btnPayCash" onclick="selectPaymentMethod('cash')"><i class="fas fa-money-bill-wave"></i> เงินสด</button>
                <button type="button" class="payment-btn" id="btnPayQR" onclick="selectPaymentMethod('qr')"><i class="fas fa-qrcode"></i> โอนเงิน (QR)</button>
            </div>

            <!-- ส่วนชำระเงินสด -->
            <div id="cashPaymentSection">
                <label class="text-14 text-gray font-bold">รับเงินสดมา (บาท):</label>
                <input type="number" id="cashReceived" class="money-input" placeholder="0.00" step="0.01" oninput="calculateChange()">
                
                <div class="summary-row mb-15 font-bold text-danger">
                    <span>เงินทอน:</span>
                    <span id="changeAmount">0.00 ฿</span>
                </div>
            </div>

            <!-- ส่วนชำระแบบ QR Code -->
            <div id="qrPaymentSection" style="display: none;">
                <p class="m-0 mb-10 text-14 font-bold text-dark-custom">สแกนเพื่อชำระเงิน (จำลอง)</p>
                <div class="qr-placeholder" style="text-align: center; padding: 20px;">
                    <i class="fas fa-qrcode fa-5x text-primary mb-10"></i>
                    <p class="text-12 text-muted">พร้อมเพย์ 099-324-1657 (ที.เอส. ปัตตานี)</p>
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
<div id="walkInCourtModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; border-radius: 12px; width: 90%; max-width: 480px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.25);">
        <h4 style="margin: 0 0 15px 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; color: #1e3c72; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-calendar-plus text-primary"></i> เปิดสนาม Walk-in หน้าเคาน์เตอร์
        </h4>

        <input type="hidden" id="modal_court_id">
        <input type="hidden" id="modal_court_base_price">

        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 15px; margin-bottom: 18px; font-size: 14px;">
            <div><strong>สนาม:</strong> <span id="modal_court_name" class="font-bold text-primary" style="font-size: 16px;"></span></div>
            <div><strong>อัตราค่าบริการ:</strong> <span id="modal_court_price_rate" class="font-bold"></span> บาท / ชั่วโมง</div>
        </div>

        <div class="form-group" style="margin-bottom: 12px;">
            <label class="font-bold" style="font-size: 13px;">วันที่ใช้งาน:</label>
            <input type="date" id="modal_court_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="form-group">
                <label class="font-bold" style="font-size: 13px;">เวลาเริ่มต้น:</label>
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
                <label class="font-bold" style="font-size: 13px;">จำนวนชั่วโมง:</label>
                <select id="modal_court_hours" class="form-control" onchange="calculateCourtTotal()">
                    <option value="1" selected>1 ชั่วโมง</option>
                    <option value="2">2 ชั่วโมง</option>
                    <option value="3">3 ชั่วโมง</option>
                    <option value="4">4 ชั่วโมง</option>
                </select>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label class="font-bold" style="font-size: 13px;">เบอร์โทรลูกค้า (ถ้าเป็นสมาชิกจะได้รับพอยท์):</label>
            <input type="text" id="modal_court_member_phone" class="form-control" maxlength="10" placeholder="ระบุเบอร์โทร 10 หลัก (ถ้ามี)">
        </div>

        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 14px; font-weight: bold; color: #475569;">ยอดรวมค่าสนาม:</span>
            <span style="font-size: 20px; font-weight: bold; color: #2563eb;"><span id="modal_court_total_price">0.00</span> ฿</span>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn-cancel" onclick="closeWalkInCourtModal()">ยกเลิก</button>
            <button type="button" class="btn-submit" onclick="addCourtToCart()" style="background: #2563eb;">
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

<!-- เรียกใช้ไฟล์ JS กลาง -->
<script src="../assets/js/admin.js?v=1.25"></script>

<script>
function openWalkInCourtModal(courtId, courtName, courtPrice) {
    document.getElementById('modal_court_id').value = courtId;
    document.getElementById('modal_court_name').textContent = courtName;
    document.getElementById('modal_court_price_rate').textContent = parseFloat(courtPrice).toFixed(2);
    document.getElementById('modal_court_base_price').value = courtPrice;
    calculateCourtTotal();
    document.getElementById('walkInCourtModal').style.display = 'flex';
}

function closeWalkInCourtModal() {
    document.getElementById('walkInCourtModal').style.display = 'none';
}

function calculateCourtTotal() {
    let rate = parseFloat(document.getElementById('modal_court_base_price').value) || 0;
    let hours = parseInt(document.getElementById('modal_court_hours').value) || 1;
    let total = rate * hours;
    document.getElementById('modal_court_total_price').textContent = total.toFixed(2);
}

function addCourtToCart() {
    let courtId = document.getElementById('modal_court_id').value;
    let courtName = document.getElementById('modal_court_name').textContent;
    let rate = parseFloat(document.getElementById('modal_court_base_price').value) || 0;
    let hours = parseInt(document.getElementById('modal_court_hours').value) || 1;
    let date = document.getElementById('modal_court_date').value;
    let startTime = document.getElementById('modal_court_start_time').value;
    let phone = document.getElementById('modal_court_member_phone').value.trim();

    let startHour = parseInt(startTime.split(':')[0]);
    let endHour = startHour + hours;
    let endTimeStr = (endHour < 10 ? '0' : '') + endHour + ':00';
    let totalPrice = rate * hours;
    let itemUniqueId = 'court_' + courtId + '_' + Date.now();

    posCart.push({
        id: itemUniqueId,
        is_court: true,
        court_id: courtId,
        name: 'เปิดสนาม ' + courtName + ' (' + startTime + ' - ' + endTimeStr + ')',
        price: totalPrice,
        qty: 1,
        maxStock: 1,
        hours: hours,
        booking_date: date,
        start_time: startTime + ':00',
        end_time: endTimeStr + ':00',
        member_phone: phone
    });

    closeWalkInCourtModal();
    updateCartUI();
    calculateChange();
}
</script>
</body>
</html>