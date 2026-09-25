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
                <div class="header-between">
                    <h4 class="section-header"><i class="fas fa-list-alt"></i> รายการข่าวสารทั้งหมด</h4>
                    <button onclick="openNewsModal()" class="btn-submit">
                        <i class="fas fa-plus"></i> เพิ่มประกาศ/ข่าวสารใหม่
                    </button>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="100">ภาพปก</th>
                            <th>หัวข้อข่าวสาร / กิจกรรม</th>
                            <th>วันที่โพสต์</th>
                            <th>ผู้ประกาศ</th>
                            <th class="text-center" width="150">จัดการ</th>
                        </tr>
                    </thead>
                                        <tbody>
                        <?php if (count($news_list) > 0): ?>
                            <?php foreach ($news_list as $row): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($row['news_image'])): ?>
                                        <img src="../uploads/news/<?php echo htmlspecialchars($row['news_image']); ?>" alt="News Image" class="news-img-table">
                                    <?php else: ?>
                                        <div class="news-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['news_title']); ?></strong><br>
                                    <span class="news-content-preview"><?php echo htmlspecialchars($row['news_content']); ?></span>
                                </td>
                                <td>
                                    <span class="text-small-muted">
                                        <i class="far fa-clock"></i> 
                                        <?php echo date('d/m/Y H:i', strtotime($row['news_published_at'])); ?>
                                    </span>
                                </td>
                                <td><span class="text-small-muted"><i class="fas fa-user-edit"></i> <?php echo htmlspecialchars($row['admin_name']); ?></span></td>
                                <td class="text-center">
                                    <!-- ปุ่มแก้ไขและลบข่าว -->
                                    <a href="news_edit.php?id=<?php echo $row['news_id']; ?>" class="btn-secondary btn-action-small">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </a>
                                    <a href="actions/news_delete_db.php?id=<?php echo $row['news_id']; ?>" class="btn-danger btn-action-small" onclick="return confirm('ยืนยันการลบข่าวสาร: <?php echo htmlspecialchars($row['news_title']); ?> ?');">
                                        <i class="fas fa-trash"></i> ลบ
                                    </a>
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
        </main>
    </div>

    <!-- Modal: สำหรับเพิ่มข่าวสารใหม่ -->
    <div id="newsAddModal" class="modal-overlay">
        <div class="modal-content max-w-600">
            <h4 class="modal-header"><i class="fas fa-plus-circle"></i> สร้างข่าวสาร/กิจกรรมใหม่</h4>
            
            <form action="actions/news_add_db.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>หัวข้อประกาศ <span class="text-danger">*</span></label>
                    <input type="text" name="news_title" class="form-control" placeholder="เช่น โปรโมชั่นเดือนนี้, ประกาศปิดสนามปรับปรุง" required>
                </div>

                <div class="form-group">
                    <label>รายละเอียด/เนื้อหา <span class="text-danger">*</span></label>
                    <textarea name="news_content" class="form-control" rows="5" placeholder="กรอกรายละเอียดข่าวสาร..." required></textarea>
                </div>

                <div class="form-group">
                    <label>รูปภาพประกอบ (ถ้ามี)</label>
                    <input type="file" name="news_image" class="form-control" accept="image/*">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeNewsModal()">ยกเลิก</button>
                    <button type="submit" class="btn-confirm btn-submit">โพสต์ข่าวสาร</button>
                </div>
            </form>
        </div>
    </div>

    <!-- เรียกใช้ไฟล์ JS กลาง -->
    <script src="../assets/js/admin.js?v=1.20"></script>
</body>
</html>