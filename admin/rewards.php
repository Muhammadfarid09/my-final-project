<?php
require_once 'includes/auth_check.php';

$rewards = [];
try {
    // ดึงข้อมูลของรางวัลทั้งหมด
    $stmt = $conn->query("SELECT * FROM Reward ORDER BY FIELD(reward_min_level, 'Bronze', 'Silver', 'Gold'), reward_point_cost ASC");
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
<?php
$page_title = 'จัดการของรางวัล - Admin T.S. Pattani';
$page_header = '<i class="fas fa-gift"></i> ระบบระดับสมาชิก & ของรางวัล (Rewards)';
include 'includes/header.php';
?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success mb-15">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error mb-15">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="layout-grid-sidebar">
                
                <!-- ฝั่งซ้าย: ฟอร์มเพิ่มของรางวัล -->
                <div class="admin-card h-fit">
                    <h4 class="section-header">
                        <i class="fas fa-plus-circle"></i> เพิ่มของรางวัลใหม่
                    </h4>
                    
                    <form action="actions/reward_add_db.php" method="POST" enctype="multipart/form-data">
                        
                        <div class="form-group">
                            <label for="reward_image">รูปภาพของรางวัล</label>
                            <input type="file" name="reward_image" id="reward_image" class="form-control" accept="image/png, image/jpeg, image/jpg">
                            <small class="text-muted-custom">รองรับไฟล์ JPG, PNG</small>
                        </div>

                        <div class="form-group">
                            <label for="reward_name">ชื่อของรางวัล / โปรโมชั่น *</label>
                            <input type="text" name="reward_name" id="reward_name" class="form-control" placeholder="เช่น พวงกุญแจไม้แบด" required>
                        </div>

                        <div class="form-group">
                            <label for="reward_point_cost">ใช้แต้มแลก (Points) *</label>
                            <input type="number" name="reward_point_cost" id="reward_point_cost" class="form-control" min="1" value="100" required>
                        </div>

                        <div class="form-group">
                            <label for="reward_min_level">ระดับสมาชิกขั้นต่ำที่แลกได้ *</label>
                            <select name="reward_min_level" id="reward_min_level" class="form-control" required>
                                <option value="Bronze">Bronze (แลกได้ทุกคน)</option>
                                <option value="Silver">Silver (เฉพาะ Silver ขึ้นไป)</option>
                                <option value="Gold">Gold (เฉพาะระดับ Gold เท่านั้น)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="reward_stock">จำนวนสต๊อกที่แลกได้ *</label>
                            <input type="number" name="reward_stock" id="reward_stock" class="form-control" value="10" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="reward_description">รายละเอียดเงื่อนไข (ถ้ามี)</label>
                            <textarea name="reward_description" id="reward_description" class="form-control" rows="3" placeholder="เช่น จำกัด 1 สิทธิ์ต่อ 1 ใบเสร็จ"></textarea>
                        </div>

                        <button type="submit" class="btn-success w-100">
                            <i class="fas fa-save"></i> บันทึกเข้าระบบ
                        </button>
                    </form>
                </div>

                <!-- ฝั่งขวา: ตารางแสดงรายการของรางวัล -->
                <div class="admin-card">
                    <h4 class="section-header">
                        <i class="fas fa-list"></i> แคตตาล็อกของรางวัลทั้งหมด
                    </h4>
                    
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th width="15%">ระดับขั้นต่ำ</th>
                                <th width="40%">ของรางวัล</th>
                                <th width="15%">แต้มที่ใช้</th>
                                <th width="15%">สต๊อกคงเหลือ</th>
                                <th width="15%">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rewards) > 0): ?>
                                <?php foreach ($rewards as $row): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            if ($row['reward_min_level'] == 'Bronze') {
                                                echo '<span class="badge-bronze"><i class="fas fa-medal"></i> Bronze</span>';
                                            } else if ($row['reward_min_level'] == 'Silver') {
                                                echo '<span class="badge-silver"><i class="fas fa-medal"></i> Silver</span>';
                                            } else if ($row['reward_min_level'] == 'Gold') {
                                                echo '<span class="badge-gold"><i class="fas fa-medal"></i> Gold</span>';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="table-item-row">
                                            <?php if (!empty($row['reward_image'])): ?>
                                                <img src="../uploads/rewards/<?php echo htmlspecialchars($row['reward_image']); ?>" 
                                                     class="table-img-md" alt="reward">
                                            <?php else: ?>
                                                <div class="table-img-placeholder-md">
                                                    <i class="fas fa-image fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div>
                                                <strong class="text-dark-custom"><?php echo htmlspecialchars($row['reward_name']); ?></strong><br>
                                                <span class="text-12 text-gray"><?php echo htmlspecialchars($row['reward_description']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong class="text-success-custom text-16"><?php echo number_format($row['reward_point_cost']); ?></strong></td>
                                    <td>
                                        <?php if($row['reward_stock'] > 0): ?>
                                            <span class="badge-info"><?php echo $row['reward_stock']; ?> สิทธิ์</span>
                                        <?php else: ?>
                                            <span class="badge-danger">หมด</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="flex gap-8 flex-wrap">
                                            <a href="reward_edit.php?id=<?php echo $row['reward_id']; ?>" class="btn-secondary btn-action-small">
                                                <i class="fas fa-edit"></i> แก้ไข
                                            </a>
                                            <a href="actions/reward_delete_db.php?id=<?php echo $row['reward_id']; ?>" class="btn-reject btn-action-small" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบของรางวัลนี้? (หากลบแล้วจะไม่สามารถกู้คืนได้)');">
                                                <i class="fas fa-trash"></i> ลบ
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center-muted p-20">ยังไม่มีของรางวัลในระบบ</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        <?php include 'includes/footer.php'; ?>