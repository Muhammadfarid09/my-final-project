<?php
session_start();
require_once 'config/config.php';

// ดึงข้อมูลข่าวสารทั้งหมดเรียงจากใหม่ไปเก่า
try {
    $stmt = $conn->prepare("SELECT * FROM News ORDER BY news_published_at DESC");
    $stmt->execute();
    $news_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข่าวสารและโปรโมชัน - T.S. Pattani</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.7">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="page-body">

    <?php include 'includes/navbar.php'; ?>

    <div class="container news-page-wrapper">
        
        <div class="news-header-banner">
            <h1><i class="fas fa-bullhorn"></i> ข่าวสารและโปรโมชัน</h1>
            <p>ติดตามความเคลื่อนไหวและสิทธิพิเศษจาก T.S. Pattani Badminton</p>
        </div>

        <div class="news-grid">
            <?php if(empty($news_list)): ?>
                <div class="news-empty-state">
                    <i class="fas fa-newspaper fa-4x news-empty-icon"></i>
                    <p class="news-empty-text">ยังไม่มีข่าวสารหรือโปรโมชันในขณะนี้</p>
                </div>
            <?php else: ?>
                <?php foreach($news_list as $news): ?>
                    <div class="news-card">
                        <?php if (!empty($news['news_image'])): ?>
                            <img src="uploads/news/<?php echo htmlspecialchars($news['news_image']); ?>" class="news-img" alt="News Image">
                        <?php else: ?>
                            <div class="news-img news-img-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>

                        <div class="news-body">
                            <div class="news-date"><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($news['news_published_at'])); ?></div>
                            <div class="news-title"><?php echo htmlspecialchars($news['news_title']); ?></div>
                            <div class="news-content"><?php echo htmlspecialchars(strip_tags($news['news_content'])); ?></div>
                            
                            <!-- ปุ่มส่งข้อมูลเปิด Modal -->
                            <a href="javascript:void(0)" class="btn-read-more" 
                               onclick="openNewsModal('<?php echo htmlspecialchars(addslashes($news['news_title'])); ?>', 
                                                      '<?php echo date('d/m/Y H:i', strtotime($news['news_published_at'])); ?>', 
                                                      '<?php echo !empty($news['news_image']) ? 'uploads/news/'.htmlspecialchars(addslashes($news['news_image'])) : ''; ?>', 
                                                      '<?php echo htmlspecialchars(addslashes(nl2br($news['news_content']))); ?>')">
                                อ่านเพิ่มเติม <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Modal สำหรับแสดงข่าวเต็ม -->
    <div id="newsModal" class="news-modal">
        <div class="news-modal-content">
            <div class="news-modal-header">
                <h3 id="modalTitle" class="news-modal-title">หัวข้อข่าว</h3>
                <span class="news-close-btn" onclick="closeNewsModal()">&times;</span>
            </div>
            <div class="news-modal-body">
                <div class="news-modal-date"><i class="far fa-clock"></i> <span id="modalDate"></span></div>
                <img id="modalImg" src="" class="news-modal-img d-none" alt="News Image">
                <p id="modalContent"></p>
            </div>
        </div>
    </div>

    <script src="assets/js/member.js?v=1.4"></script>
    <script>
        function openNewsModal(title, date, imgUrl, content) {
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalDate').innerText = date;
            document.getElementById('modalContent').innerHTML = content;
            
            var imgTag = document.getElementById('modalImg');
            if(imgUrl !== "") {
                imgTag.src = imgUrl;
                imgTag.classList.remove('d-none');
            } else {
                imgTag.classList.add('d-none');
            }
            
            document.getElementById('newsModal').style.display = 'block';
        }

        function closeNewsModal() {
            document.getElementById('newsModal').style.display = 'none';
        }

        // ปิด Modal ถอยคลิกพื้นหลัง
        window.onclick = function(event) {
            var modal = document.getElementById('newsModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

