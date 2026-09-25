<?php
require_once 'includes/auth_check.php';

// รับประเภทสินค้ามาจาก URL (ถ้าไม่มีให้เป็น 'สินค้าบริโภค')
$product_type = isset($_GET['type']) ? $_GET['type'] : 'สินค้าบริโภค';
// กำหนดแท็บที่จะให้เด้งกลับไป
$tab_return = ($product_type == 'อุปกรณ์เช่า') ? 'rental' : 'consumable';
$icon = ($product_type == 'อุปกรณ์เช่า') ? 'fa-table-tennis' : 'fa-coffee';
?>
<?php
$page_title = 'เพิ่มรายการ - Admin T.S. Pattani';
$page_header = '<i class="fas ' . $icon . '"></i> เพิ่ม' . htmlspecialchars($product_type) . 'ใหม่';
include 'includes/header.php';
?>

            <div class="admin-card max-w-800 mx-auto">
                
                <div class="header-between mb-20 border-b-2 pb-10">
                    <h4 class="m-0">กรอกข้อมูลรายละเอียด</h4>
                    <a href="products.php?tab=<?php echo $tab_return; ?>" class="btn-secondary text-decor-none text-14">
                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                    </a>
                </div>

                <!-- ฟอร์มส่งข้อมูลไปที่ product_add_db.php (แนบรูปภาพได้) -->
                <form action="actions/product_add_db.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_type" value="<?php echo htmlspecialchars($product_type); ?>">
                    <input type="hidden" name="tab_return" value="<?php echo $tab_return; ?>">

                    <div class="grid-2 gap-20">
                        <!-- ฝั่งซ้าย: ข้อมูลทั่วไป -->
                        <div>
                            <div class="form-group">
                                <label for="product_name">ชื่อรายการ <span class="text-danger">*</span></label>
                                <input type="text" name="product_name" id="product_name" class="form-control" required placeholder="เช่น น้ำดื่มคริสตัล, ไม้แบด Yonex Astrox">
                            </div>

                            <div class="grid-2 gap-20">
                                <div class="form-group">
                                    <label for="product_price">ราคา (บาท) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="product_price" id="product_price" class="form-control" required placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label for="product_stock">จำนวนสต็อก <span class="text-danger">*</span></label>
                                    <input type="number" name="product_stock" id="product_stock" class="form-control" required placeholder="เช่น 50" value="0">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="product_min_stock">จุดแจ้งเตือนสินค้าใกล้หมด (ชิ้น) <span class="text-danger">*</span></label>
                                <input type="number" name="product_min_stock" id="product_min_stock" class="form-control" required placeholder="เช่น 10" value="5">
                            </div>
                        </div>

                        <!-- ฝั่งขวา: รูปภาพ -->
                        <div>
                            <div class="form-group">
                                <label for="product_image">รูปภาพประกอบ</label>
                                <div class="p-20 text-center" style="border: 2px dashed #CBD5E1; border-radius: 8px; background: #F8FAFC; height: calc(100% - 30px); display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <i class="fas fa-cloud-upload-alt text-gray-light" style="font-size: 48px; margin-bottom: 15px; color: #94A3B8;"></i>
                                    <input type="file" name="product_image" id="product_image" class="form-control" accept="image/jpeg, image/png, image/webp" style="max-width: 250px;">
                                    <small class="text-muted-custom mt-10 d-block">รองรับไฟล์ JPG, PNG, WEBP (ไม่เกิน 5MB)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= แสดงฟิลด์เฉพาะอุปกรณ์เช่า ================= -->
                    <?php if ($product_type == 'อุปกรณ์เช่า'): ?>
                        <hr class="hr-dashed my-20">
                        <h5 class="text-dark mb-15"><i class="fas fa-list-ul text-primary"></i> รายละเอียดสเปกอุปกรณ์</h5>
                        
                        <div class="grid-2 gap-20">
                            <div class="form-group">
                                <label for="product_brand">ยี่ห้อ (Brand)</label>
                                <input type="text" name="product_brand" id="product_brand" class="form-control" placeholder="เช่น Yonex, Victor, Li-Ning">
                            </div>

                            <div class="form-group">
                                <label for="product_model">รุ่น (Model)</label>
                                <input type="text" name="product_model" id="product_model" class="form-control" placeholder="เช่น Astrox 99, SHB-65Z">
                            </div>

                            <div class="form-group">
                                <label for="product_size">ไซส์ (Size) - สำหรับรองเท้า</label>
                                <input type="text" name="product_size" id="product_size" class="form-control" placeholder="เช่น 40, 41, 42 EU">
                            </div>

                            <div class="form-group">
                                <label for="product_level">ระดับการใช้งาน (เฉพาะไม้แบด)</label>
                                <select name="product_level" id="product_level" class="form-control">
                                    <option value="">-- ไม่ระบุ --</option>
                                    <option value="มือใหม่">มือใหม่ (ก้านอ่อน/น้ำหนักเบา)</option>
                                    <option value="มือโปร">มือโปร (ก้านแข็ง/หัวหนัก)</option>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                    <!-- ================= จบฟิลด์เฉพาะอุปกรณ์เช่า ================= -->

                    <div class="text-center mt-20">
                        <button type="submit" class="btn-submit px-30 py-10 text-16">
                            <i class="fas fa-save"></i> บันทึกข้อมูล
                        </button>
                    </div>
                </form>

            </div>
        <?php include 'includes/footer.php'; ?>