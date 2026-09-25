<?php
require_once 'includes/auth_check.php';

try {
    // ดึงรายชื่อสินค้าทั้งหมดมาใส่ใน Dropdown ให้แอดมินเลือก
    $stmt = $conn->query("SELECT product_id, product_name, product_stock FROM Product ORDER BY product_name ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'รับของเข้าสต็อก - Admin T.S. Pattani';
$page_header = '<i class="fas fa-plus-circle"></i> ฟอร์มรับสินค้าเข้าสต็อก';
include 'includes/header.php';
?>

            <div class="admin-card max-w-600 mx-auto">
                <div class="header-between border-b-2 pb-10 mb-20">
                    <h4 class="m-0">บันทึกข้อมูลจัดซื้อ</h4>
                    <a href="purchases.php" class="btn-secondary text-decor-none"><i class="fas fa-arrow-left"></i> ย้อนกลับ</a>
                </div>

                <form action="actions/purchase_add_db.php" method="POST">
                    
                    <div class="form-group">
                        <label for="product_id">เลือกรายการสินค้าที่รับเข้า <span class="text-danger">*</span></label>
                        <select name="product_id" id="product_id" class="form-control" required>
                            <option value="">-- กรุณาเลือกสินค้า --</option>
                            <?php foreach($products as $p): ?>
                                <option value="<?php echo $p['product_id']; ?>">
                                    <?php echo htmlspecialchars($p['product_name']); ?> (สต็อกเดิม: <?php echo $p['product_stock']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="purchase_date">วันที่รับเข้า <span class="text-danger">*</span></label>
                        <!-- กำหนดค่าเริ่มต้นให้เป็นวันที่ของวันนี้ -->
                        <input type="date" name="purchase_date" id="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="grid-2 gap-20">
                        <div class="form-group">
                            <label for="purchase_quantity">จำนวนที่รับเข้า (ชิ้น) <span class="text-danger">*</span></label>
                            <input type="number" name="purchase_quantity" id="purchase_quantity" class="form-control" min="1" required placeholder="0" oninput="calcTotal()">
                        </div>

                        <div class="form-group">
                            <label for="purchase_price_per_unit">ราคาต้นทุน/หน่วย (บาท) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="purchase_price_per_unit" id="purchase_price_per_unit" class="form-control" min="0" required placeholder="0.00" oninput="calcTotal()">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>ยอดรวมสุทธิ (ระบบคำนวณอัตโนมัติ)</label>
                        <input type="text" id="display_total" class="form-control text-right text-danger font-bold text-18" value="0.00 ฿" readonly>
                    </div>

                    <div class="text-center mt-20">
                        <button type="submit" class="btn-submit w-100 p-15">
                            <i class="fas fa-save"></i> ยืนยันการรับเข้าสต็อก
                        </button>
                    </div>
                </form>
            </div>
        <?php include 'includes/footer.php'; ?>