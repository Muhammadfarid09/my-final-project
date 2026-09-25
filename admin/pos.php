<?php
require_once 'includes/auth_check.php';

try {
    // ดึงข้อมูลสินค้าทั้งหมดมาเพื่อแสดงบนหน้าจอ POS
    $stmt = $conn->query("SELECT * FROM Product ORDER BY product_id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'POS ขายหน้าร้าน - Admin T.S. Pattani';
$page_header = '<i class=\"fas fa-cash-register\"></i> ระบบขายหน้าร้าน (POS)';
include 'includes/header.php';
?>
<div class="pos-container">
    <!-- ฝั่งซ้าย: รายการสินค้า -->
    <div class="pos-products">
        <div class="page-tabs m-0">
            <button type="button" class="tab-btn tab-active" onclick="filterProducts('all', this)">ทั้งหมด</button>
            <button type="button" class="tab-btn tab-inactive" onclick="filterProducts('สินค้าบริโภค', this)"><i class="fas fa-coffee"></i> น้ำดื่ม/ลูกแบด</button>
            <button type="button" class="tab-btn tab-inactive" onclick="filterProducts('อุปกรณ์เช่า', this)"><i class="fas fa-table-tennis"></i> อุปกรณ์เช่า</button>
        </div>

        <div class="product-grid" id="productGrid">
            <?php foreach ($products as $row): 
                $is_out = ($row['product_stock'] <= 0);
            ?>
                <div class="product-card <?php echo $is_out ? 'out-of-stock' : ''; ?>" 
                     data-type="<?php echo htmlspecialchars($row['product_type']); ?>"
                     onclick="addToCart(<?php echo $row['product_id']; ?>, '<?php echo htmlspecialchars($row['product_name']); ?>', <?php echo $row['product_price']; ?>, <?php echo $row['product_stock']; ?>)">
                    
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
        <h4 class="mt-0 border-b-2 pb-10"><i class="fas fa-shopping-cart"></i> รายการสั่งซื้อ</h4>
        
        <div id="cartEmpty" class="text-center text-gray py-30">
            <i class="fas fa-cart-arrow-down fa-3x mb-10"></i><br>ยังไม่มีสินค้าในตะกร้า
        </div>

        <table class="cart-table" id="cartTable" style="display: none;">
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th class="text-center">จำนวน</th>
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

            <!-- ส่วนชำระแบบ QR Code (นับถอยหลัง) -->
            <div id="qrPaymentSection">
                <p class="m-0 mb-10 text-14 font-bold text-dark-custom">สแกนเพื่อชำระเงิน (จำลอง)</p>
                
                <div class="qr-placeholder">
                    <img src="" id="qrCodeImage" alt="QR Code" style="display: none;">
                    <i class="fas fa-qrcode fa-3x text-gray-light" id="qrIconPlaceholder"></i>
                </div>
                
                <p class="m-0 text-13 text-gray">กรุณาชำระเงินภายในเวลา:</p>
                <div class="countdown-timer" id="qrTimer">05:00</div>
                <p class="m-0 text-12 text-muted-custom">*ระบบจะยกเลิกบิลอัตโนมัติหากหมดเวลา</p>
            </div>

            <button type="submit" class="btn-checkout" id="btnCheckout" disabled>
                <i class="fas fa-check-circle"></i> ยืนยันการชำระเงิน
            </button>
        </form>
    </div>
</div>
</main>
</div>

<?php if (isset($_SESSION['print_receipt_id'])): ?>
<input type="hidden" id="trigger_receipt_id" value="<?php echo $_SESSION['print_receipt_id']; ?>">
<?php unset($_SESSION['print_receipt_id']); ?>
<?php endif; ?>

<!-- เรียกใช้ไฟล์ JS กลางไฟล์เดียว -->
<script src="../assets/js/admin.js?v=1.21"></script>
</body>
</html>