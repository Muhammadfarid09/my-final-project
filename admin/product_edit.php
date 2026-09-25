<?php
require_once 'includes/auth_check.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // ดึงข้อมูลสินค้าเดิมมาแสดงในฟอร์ม
    $sql = "SELECT * FROM Product WHERE product_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error'] = "ไม่พบข้อมูลสินค้ารายการนี้";
        header("Location: products.php");
        exit();
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("Location: products.php");
    exit();
}

// กำหนดแท็บเพื่อใช้ในการกดปุ่มย้อนกลับให้ถูกแท็บ
$tab_return = ($product['product_type'] == 'อุปกรณ์เช่า') ? 'rental' : 'consumable';
?>
<?php
$page_title = 'แก้ไขรายการ - Admin T.S. Pattani';
$page_header = '<i class="fas fa-edit"></i> แก้ไขข้อมูล' . htmlspecialchars($product['product_type']);
include 'includes/header.php';
?>

            <div class="admin-card max-w-800 mx-auto">
                
                <div class="header-between mb-20 border-b-2 pb-10">
                    <h4 class="m-0">รหัสอ้างอิง: #PD<?php echo $product['product_id']; ?></h4>
                    <a href="products.php?tab=<?php echo $tab_return; ?>" class="btn-secondary text-decor-none text-14">
                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                    </a>
                </div>

                <!-- ฟอร์มส่งข้อมูลไปที่ product_edit_db.php -->
                <form action="actions/product_edit_db.php" method="POST" enctype="multipart/form-data">
                    
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <input type="hidden" name="product_type" value="<?php echo htmlspecialchars($product['product_type']); ?>">
                    <input type="hidden" name="tab_return" value="<?php echo $tab_return; ?>">
                    <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($product['product_image']); ?>">

                    <div class="grid-2 gap-20">
                        <!-- ฝั่งซ้าย: ข้อมูลทั่วไป -->
                        <div>
                            <div class="form-group">
                                <label for="product_name">ชื่อรายการ <span class="text-danger">*</span></label>
                                <input type="text" name="product_name" id="product_name" class="form-control" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                            </div>

                            <div class="grid-2 gap-20">
                                <div class="form-group">
                                    <label for="product_price">ราคา (บาท) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="product_price" id="product_price" class="form-control" value="<?php echo $product['product_price']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="product_stock">จำนวนสต็อก <span class="text-danger">*</span></label>
                                    <input type="number" name="product_stock" id="product_stock" class="form-control" value="<?php echo $product['product_stock']; ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="product_min_stock">จุดแจ้งเตือนสินค้าใกล้หมด (ชิ้น) <span class="text-danger">*</span></label>
                                <input type="number" name="product_min_stock" id="product_min_stock" class="form-control" value="<?php echo $product['product_min_stock']; ?>" required>
                            </div>
                        </div>

                        <!-- ฝั่งขวา: รูปภาพ -->
                        <div>
                            <div class="form-group text-center">
                                <label>รูปภาพประกอบ</label>
                                <div class="p-20" style="border: 2px dashed #CBD5E1; border-radius: 8px; background: #F8FAFC; height: calc(100% - 30px); display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <?php if (!empty($product['product_image'])): ?>
                                        <img src="../uploads/products/<?php echo htmlspecialchars($product['product_image']); ?>" class="img-preview-lg mx-auto d-block" alt="Current Image">
                                    <?php else: ?>
                                        <i class="fas fa-image text-gray-light" style="font-size: 48px; margin-bottom: 15px; color: #94A3B8;"></i>
                                    <?php endif; ?>
                                    
                                    <input type="file" name="product_image" id="product_image" class="form-control mt-10" accept="image/jpeg, image/png, image/webp" style="max-width: 250px;">
                                    <small class="text-muted-custom mt-10 d-block">อัปโหลดรูปใหม่เพื่อแทนที่รูปเดิม</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= แสดงฟิลด์เฉพาะอุปกรณ์เช่า ================= -->
                    <?php if ($product['product_type'] == 'อุปกรณ์เช่า'): ?>
                        <hr class="hr-dashed my-20">
                        <h5 class="text-dark mb-15"><i class="fas fa-list-ul text-primary"></i> รายละเอียดสเปกอุปกรณ์</h5>
                        
                        <div class="grid-2 gap-20">
                            <div class="form-group">
                                <label for="product_brand">ยี่ห้อ (Brand)</label>
                                <input type="text" name="product_brand" id="product_brand" class="form-control" value="<?php echo htmlspecialchars($product['product_brand']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="product_model">รุ่น (Model)</label>
                                <input type="text" name="product_model" id="product_model" class="form-control" value="<?php echo htmlspecialchars($product['product_model']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="product_size">ไซส์ (Size) - สำหรับรองเท้า</label>
                                <input type="text" name="product_size" id="product_size" class="form-control" value="<?php echo htmlspecialchars($product['product_size']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="product_level">ระดับการใช้งาน</label>
                                <select name="product_level" id="product_level" class="form-control">
                                    <option value="" <?php echo empty($product['product_level']) ? 'selected' : ''; ?>>-- ไม่ระบุ --</option>
                                    <option value="มือใหม่" <?php echo ($product['product_level'] == 'มือใหม่') ? 'selected' : ''; ?>>มือใหม่</option>
                                    <option value="มือโปร" <?php echo ($product['product_level'] == 'มือโปร') ? 'selected' : ''; ?>>มือโปร</option>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                    <!-- ================= จบฟิลด์เฉพาะอุปกรณ์เช่า ================= -->

                    </div>

                    <div class="text-center mt-30">
                        <button type="submit" class="btn-warning px-30 py-10 text-16">
                            <i class="fas fa-save"></i> บันทึกการแก้ไข
                        </button>
                    </div>
                </form>

            </div>
        <?php include 'includes/footer.php'; ?>