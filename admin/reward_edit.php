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
$page_header = '<i class="fas fa-edit"></i> แก้ไขของรางวัล: ' . htmlspecialchars($reward['reward_name'], ENT_QUOTES, 'UTF-8');
include 'includes/header.php';
?>

<div class="admin-card max-w-768 mx-auto">
    <div class="header-between border-b pb-15 mb-25">
        <h4 class="section-header m-0">
            <i class="fas fa-gift text-primary"></i> ข้อมูลของรางวัล #<?php echo $reward_id; ?>
        </h4>
        <a href="rewards.php" class="btn-secondary text-decor-none text-14">
            <i class="fas fa-arrow-left"></i> ย้อนกลับ
        </a>
    </div>

    <form action="actions/reward_edit_db.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
        <input type="hidden" name="reward_id" value="<?php echo intval($reward['reward_id']); ?>">
        <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($reward['reward_image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <div class="form-group text-center mb-25">
            <label class="d-block font-medium mb-12">รูปภาพของรางวัล</label>
            <?php if (!empty($reward['reward_image'])): ?>
                <img src="../uploads/rewards/<?php echo htmlspecialchars($reward['reward_image'], ENT_QUOTES, 'UTF-8'); ?>" class="img-preview-lg" alt="reward">
            <?php else: ?>
                <div class="img-placeholder-lg">
                    <i class="fas fa-image fa-3x"></i>
                </div>
            <?php endif; ?>
            <label for="reward_image" class="btn-secondary text-decor-none" style="cursor: pointer;">
                <i class="fas fa-camera"></i> เปลี่ยนรูปภาพใหม่
            </label>
            <input type="file" name="reward_image" id="reward_image" class="form-control d-none" accept="image/png, image/jpeg, image/jpg" onchange="if(this.files[0]) SwalToast('info', 'เลือกไฟล์รูปภาพใหม่: ' + this.files[0].name);">
            <p class="text-small-muted mt-8 mb-0">* รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 2MB (หากไม่ต้องการเปลี่ยนรูปภาพ ให้เว้นว่างไว้)</p>
        </div>

        <div class="form-group">
            <label for="reward_name">ชื่อของรางวัล / โปรโมชั่น <span class="text-danger">*</span></label>
            <input type="text" name="reward_name" id="reward_name" class="form-control" value="<?php echo htmlspecialchars($reward['reward_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="form-group">
            <label for="reward_point_cost">ใช้แต้มแลก (Points) <span class="text-danger">*</span></label>
            <input type="number" name="reward_point_cost" id="reward_point_cost" class="form-control" min="1" value="<?php echo intval($reward['reward_point_cost']); ?>" required>
        </div>

        <div class="form-group">
            <label for="reward_min_level">ระดับสมาชิกขั้นต่ำที่แลกได้ <span class="text-danger">*</span></label>
            <select name="reward_min_level" id="reward_min_level" class="form-control" required>
                <option value="Bronze" <?php if($reward['reward_min_level'] == 'Bronze') echo 'selected'; ?>>Bronze (แลกได้ทุกคน)</option>
                <option value="Silver" <?php if($reward['reward_min_level'] == 'Silver') echo 'selected'; ?>>Silver (เฉพาะ Silver ขึ้นไป)</option>
                <option value="Gold" <?php if($reward['reward_min_level'] == 'Gold') echo 'selected'; ?>>Gold (เฉพาะระดับ Gold เท่านั้น)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="reward_stock">จำนวนสต๊อกที่แลกได้ <span class="text-danger">*</span></label>
            <input type="number" name="reward_stock" id="reward_stock" class="form-control" min="0" value="<?php echo intval($reward['reward_stock']); ?>" required>
        </div>

        <div class="form-group">
            <label for="reward_description">รายละเอียดเงื่อนไข (ถ้ามี)</label>
            <textarea name="reward_description" id="reward_description" class="form-control" rows="4"><?php echo htmlspecialchars($reward['reward_description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="header-between mt-30 pt-20 border-t">
            <a href="rewards.php" class="btn-secondary text-decor-none">
                <i class="fas fa-times"></i> ยกเลิก
            </a>
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
