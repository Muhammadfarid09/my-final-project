<?php
require_once 'includes/auth_check.php';

if (!isset($_GET['id'])) {
    header("Location: rewards.php");
    exit();
}

$reward_id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT * FROM Reward WHERE reward_id = :id");
    $stmt->execute([':id' => $reward_id]);
    $reward = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reward) {
        $_SESSION['error'] = "ไม่พบข้อมูลของรางวัลที่ต้องการแก้ไข";
        header("Location: rewards.php");
        exit();
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php
$page_title = 'แก้ไขของรางวัล - Admin T.S. Pattani';
$page_header = '<i class="fas fa-edit"></i> แก้ไขของรางวัล: ' . htmlspecialchars($reward['reward_name']);
include 'includes/header.php';
?>
            <div class="mb-20">
                <a href="rewards.php" class="text-decor-none text-gray font-medium"><i class="fas fa-arrow-left"></i> กลับไปหน้าระบบของรางวัล</a>
            </div>

            <div class="admin-card max-w-600">
                <form action="actions/reward_edit_db.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="reward_id" value="<?php echo $reward['reward_id']; ?>">
                    <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($reward['reward_image']); ?>">

                    <div class="form-group text-center mb-20">
                        <?php if (!empty($reward['reward_image'])): ?>
                            <img src="../uploads/rewards/<?php echo htmlspecialchars($reward['reward_image']); ?>" class="img-preview-lg">
                        <?php else: ?>
                            <div class="img-placeholder-lg">
                                <i class="fas fa-image fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        <br>
                        <label for="reward_image" class="btn-outline-primary"><i class="fas fa-upload"></i> เปลี่ยนรูปภาพใหม่</label>
                        <input type="file" name="reward_image" id="reward_image" class="form-control d-none" accept="image/png, image/jpeg, image/jpg" onchange="alert('เลือกไฟล์รูปภาพใหม่เรียบร้อยแล้ว (' + this.files[0].name + ')');">
                    </div>

                    <div class="form-group">
                        <label for="reward_name">ชื่อของรางวัล / โปรโมชั่น *</label>
                        <input type="text" name="reward_name" id="reward_name" class="form-control" value="<?php echo htmlspecialchars($reward['reward_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="reward_point_cost">ใช้แต้มแลก (Points) *</label>
                        <input type="number" name="reward_point_cost" id="reward_point_cost" class="form-control" min="1" value="<?php echo $reward['reward_point_cost']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="reward_min_level">ระดับสมาชิกขั้นต่ำที่แลกได้ *</label>
                        <select name="reward_min_level" id="reward_min_level" class="form-control" required>
                            <option value="Bronze" <?php if($reward['reward_min_level'] == 'Bronze') echo 'selected'; ?>>Bronze (แลกได้ทุกคน)</option>
                            <option value="Silver" <?php if($reward['reward_min_level'] == 'Silver') echo 'selected'; ?>>Silver (เฉพาะ Silver ขึ้นไป)</option>
                            <option value="Gold" <?php if($reward['reward_min_level'] == 'Gold') echo 'selected'; ?>>Gold (เฉพาะระดับ Gold เท่านั้น)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reward_stock">จำนวนสต๊อกที่แลกได้ *</label>
                        <input type="number" name="reward_stock" id="reward_stock" class="form-control" min="0" value="<?php echo $reward['reward_stock']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="reward_description">รายละเอียดเงื่อนไข (ถ้ามี)</label>
                        <textarea name="reward_description" id="reward_description" class="form-control" rows="4"><?php echo htmlspecialchars($reward['reward_description']); ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit w-100 p-15">
                        <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </form>
            </div>
<?php include 'includes/footer.php'; ?>
