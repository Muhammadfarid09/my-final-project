<?php
require_once 'includes/auth_check.php';

// ดึงข้อมูลข่าวสารทั้งหมด พร้อมชื่อแอดมินที่โพสต์
$news_list = [];
try {
    $sql = "SELECT n.*, a.admin_name 
            FROM News n
            LEFT JOIN Admin a ON n.admin_id = a.admin_id
            ORDER BY n.news_published_at DESC";
    $stmt = $conn->query($sql);
    $news_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลข่าวสาร: " . $e->getMessage();
}
?>
<?php
$page_title = 'จัดการข่าวสารและโปรโมชั่น - Admin T.S. Pattani';
$extra_head = <<<HTML
<!-- อัปเดตเวอร์ชัน CSS เพื่อดึงรูปแบบใหม่ -->
HTML;
$page_header = '<i class=\"fas fa-bullhorn\"></i> จัดการข่าวสาร กิจกรรม และโปรโมชั่น';
include 'includes/header.php';
?><div class="admin-card">
    <div class="header-between mb-20">
        <h3 class="m-0 text-dark-custom font-bold"><i class="fas fa-bullhorn text-primary"></i> รายการข่าวสารและโปรโมชั่น</h3>
        <button type="button" onclick="openNewsModal()" class="btn-submit">
            <i class="fas fa-plus"></i> เพิ่มประกาศ/ข่าวสารใหม่
        </button>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 135px;">ภาพปก</th>
                <th>หัวข้อข่าวสาร / กิจกรรม</th>
                <th style="width: 170px;">วันที่โพสต์</th>
                <th style="width: 165px;">ผู้ประกาศ</th>
                <th class="text-center" style="width: 190px;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($news_list) > 0): ?>
                <?php foreach ($news_list as $row): ?>
                <tr>
                    <td>
                        <?php if (!empty($row['news_image'])): ?>
                            <img src="../uploads/news/<?php echo htmlspecialchars($row['news_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($row['news_title'], ENT_QUOTES, 'UTF-8'); ?>" class="news-img-table">
                        <?php else: ?>
                            <div class="news-table-placeholder" title="ไม่มีรูปภาพประกอบ"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong class="text-dark-custom font-bold text-15"><?php echo htmlspecialchars($row['news_title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <div class="news-content-preview"><?php echo htmlspecialchars($row['news_content'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </td>
                    <td>
                        <span class="badge-silver">
                            <i class="far fa-clock"></i> 
                            <?php echo date('d/m/Y H:i', strtotime($row['news_published_at'])); ?> น.
                        </span>
                    </td>
                    <td>
                        <span class="badge-info">
                            <i class="fas fa-user-edit"></i> 
                            <?php echo htmlspecialchars($row['admin_name'] ?? 'ผู้ดูแลระบบ', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td class="text-center whitespace-nowrap">
                        <div class="table-actions-group">
                            <a href="news_edit.php?id=<?php echo intval($row['news_id']); ?>" class="btn-secondary btn-action-small" title="แก้ไข">
                                <i class="fas fa-edit"></i> แก้ไข
                            </a>
                            <button type="button" 
                                    class="btn-reject btn-action-small" 
                                    onclick="showActionConfirmModal({
                                        title: 'ยืนยันการลบข่าวสารและโปรโมชั่น',
                                        message: 'คุณกำลังจะลบข่าวสาร: <strong><?php echo htmlspecialchars(addslashes($row['news_title']), ENT_QUOTES, 'UTF-8'); ?></strong>',
                                        consequence: 'ข่าวสารและรูปภาพประกอบนี้จะถูกลบออกจากระบบอย่างถาวร และจะไม่แสดงบนหน้าเว็บไซต์อีกต่อไป',
                                        confirmText: 'ยืนยันการลบ',
                                        type: 'danger',
                                        actionUrl: 'actions/news_delete_db.php?id=<?php echo intval($row['news_id']); ?>'
                                    })"
                                    title="ลบ">
                                <i class="fas fa-trash-alt"></i> ลบ
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="table-empty-state">
                        <i class="fas fa-bullhorn empty-state-icon"></i>
                        <span>ยังไม่มีข่าวสารหรือโปรโมชั่นในระบบ</span>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: สำหรับเพิ่มข่าวสารใหม่ -->
<div id="newsAddModal" class="modal-overlay">
    <div class="modal-content max-w-600">
        <h4 class="modal-header"><i class="fas fa-plus-circle text-primary"></i> สร้างข่าวสาร/กิจกรรมใหม่</h4>
        
        <form action="actions/news_add_db.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">

            <div class="form-group">
                <label for="news_title">หัวข้อประกาศ <span class="text-danger">*</span></label>
                <input type="text" id="news_title" name="news_title" class="form-control" placeholder="เช่น โปรโมชั่นเดือนนี้, ประกาศปิดสนามปรับปรุง" required>
            </div>

            <div class="form-group">
                <label for="news_content">รายละเอียด/เนื้อหา <span class="text-danger">*</span></label>
                <textarea id="news_content" name="news_content" class="form-control" rows="5" placeholder="กรอกรายละเอียดข่าวสาร ข้อมูลโปรโมชั่น หรือเงื่อนไขกิจกรรม..." required></textarea>
            </div>

            <div class="form-group">
                <label for="news_image">รูปภาพประกอบ (ถ้ามี)</label>
                <input type="file" id="news_image" name="news_image" class="form-control" accept="image/*">
                <small class="text-small-muted d-block mt-5">* รองรับไฟล์รูปภาพ JPG, JPEG, PNG, GIF ขนาดไม่เกิน 2MB</small>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeNewsModal()">ยกเลิก</button>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> โพสต์ข่าวสาร
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>