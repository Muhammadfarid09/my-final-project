<?php
require_once 'includes/auth_check.php';

// ตรวจสอบว่าส่ง ID ข่าวสารมาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: news.php");
    exit();
}

$news_id = intval($_GET['id']);
$news_data = [];

try {
    // ดึงข้อมูลข่าวสารเดิมมาแสดง
    $stmt = $conn->prepare("SELECT * FROM News WHERE news_id = :id");
    $stmt->execute([':id' => $news_id]);
    $news_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$news_data) {
        $_SESSION['error'] = "ไม่พบข่าวสารที่ต้องการแก้ไข";
        header("Location: news.php");
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
<?php
require_once 'includes/auth_check.php';

// ตรวจสอบว่าส่ง ID ข่าวสารมาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: news.php");
    exit();
}

$news_id = intval($_GET['id']);
$news_data = [];

try {
    // ดึงข้อมูลข่าวสารเดิมมาแสดง
    $stmt = $conn->prepare("SELECT * FROM News WHERE news_id = :id");
    $stmt->execute([':id' => $news_id]);
    $news_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$news_data) {
        $_SESSION['error'] = "ไม่พบข่าวสารที่ต้องการแก้ไข";
        header("Location: news.php");
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("Location: news.php");
    exit();
}
?>
<?php
$page_title = 'แก้ไขข่าวสาร - Admin T.S. Pattani';
$page_header = '<i class=\"fas fa-edit\"></i> แก้ไขข่าวสารและโปรโมชั่น';
include 'includes/header.php';
?><div class="admin-card max-w-800 mx-auto">
                <div class="header-between border-b pb-15 mb-20">
                    <h4 class="section-header m-0"><i class="fas fa-pen-nib"></i> ข้อมูลข่าวสาร #<?php echo $news_id; ?></h4>
                    <a href="news.php" class="btn-cancel text-decor-none"><i class="fas fa-arrow-left"></i> ย้อนกลับ</a>
                </div>

                <form action="actions/news_edit_db.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="news_id" value="<?php echo $news_data['news_id']; ?>">
                    <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($news_data['news_image'] ?? ''); ?>">

                    <div class="form-group">
                        <label>หัวข้อประกาศ <span class="text-danger">*</span></label>
                        <input type="text" name="news_title" class="form-control" value="<?php echo htmlspecialchars($news_data['news_title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>รายละเอียด/เนื้อหา <span class="text-danger">*</span></label>
                        <textarea name="news_content" class="form-control" rows="8" required><?php echo htmlspecialchars($news_data['news_content']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>รูปภาพประกอบปัจจุบัน</label>
                        <div class="img-preview-container">
                            <?php if (!empty($news_data['news_image'])): ?>
                                <img src="../uploads/news/<?php echo htmlspecialchars($news_data['news_image']); ?>" alt="Current Image">
                                <p class="text-small-muted mt-10">นี่คือรูปภาพที่ใช้งานอยู่ หากไม่ต้องการเปลี่ยน ไม่ต้องอัปโหลดใหม่</p>
                            <?php else: ?>
                                <p class="text-small-muted my-20"><i class="fas fa-image fa-2x d-block mb-10"></i> ข่าวนี้ยังไม่มีรูปภาพประกอบ</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>อัปโหลดรูปภาพใหม่ (แทนที่รูปเดิม)</label>
                        <input type="file" name="new_image" class="form-control" accept="image/*">
                        <small class="text-small-muted text-primary">* รองรับไฟล์ JPG, PNG, GIF ขนาดไม่เกิน 2MB หากอัปโหลดใหม่ ระบบจะลบรูปเก่าทิ้งอัตโนมัติ</small>
                    </div>

                    <div class="mt-30 text-right">
                        <button type="submit" class="btn-submit px-20 py-10 text-16">
                            <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>
                </form>
            </div>
        <?php include 'includes/footer.php'; ?>