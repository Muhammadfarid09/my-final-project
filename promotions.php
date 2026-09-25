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
    <link rel="stylesheet" href="assets/css/style.css?v=1.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .news-header-banner {
            background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 40px;
        }
        .news-header-banner h1 { margin: 0 0 10px 0; font-size: 32px; }
        .news-header-banner p { margin: 0; font-size: 18px; opacity: 0.9; }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        .news-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .news-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #eee;
        }
        .news-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .news-date {
            font-size: 12px;
            color: #888;
            margin-bottom: 10px;
        }
        .news-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        .news-content {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .btn-read-more {
            display: inline-block;
            padding: 8px 15px;
            background: #f8f9fa;
            color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
            border: 1px solid #ddd;
            transition: all 0.2s;
        }
        .btn-read-more:hover {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }

        /* Modal อ่านข่าวเต็มๆ */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.5); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: none;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            overflow: hidden;
            animation: slideIn 0.3s;
        }
        @keyframes slideIn {
            from {transform: translateY(-50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        .modal-header {
            padding: 15px 20px;
            background: #1e3c72;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .close-btn {
            color: white;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-btn:hover { color: #ddd; }
        .modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        .modal-body img {
            width: 100%;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .modal-body p {
            line-height: 1.6;
            color: #444;
        }
    </style>
</head>
<body style="background-color: #f4f6f9;">

    <?php include 'includes/navbar.php'; ?>

    <div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
        
        <div class="news-header-banner">
            <h1><i class="fas fa-bullhorn"></i> ข่าวสารและโปรโมชัน</h1>
            <p>ติดตามความเคลื่อนไหวและสิทธิพิเศษจาก T.S. Pattani Badminton</p>
        </div>

        <div class="news-grid">
            <?php if(empty($news_list)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: #fff; border-radius: 10px; color: #999;">
                    <i class="fas fa-newspaper fa-4x" style="margin-bottom: 15px;"></i>
                    <p style="font-size: 18px;">ยังไม่มีข่าวสารหรือโปรโมชันในขณะนี้</p>
                </div>
            <?php else: ?>
                <?php foreach($news_list as $news): ?>
                    <div class="news-card">
                        <?php if (!empty($news['news_image'])): ?>
                            <img src="uploads/news/<?php echo htmlspecialchars($news['news_image']); ?>" class="news-img" alt="News Image">
                        <?php else: ?>
                            <div class="news-img" style="display:flex; align-items:center; justify-content:center; color:#ccc; font-size:40px;">
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
    <div id="newsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle" style="margin:0; font-size: 18px;">หัวข้อข่าว</h3>
                <span class="close-btn" onclick="closeNewsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div style="font-size: 13px; color: #888; margin-bottom: 15px;"><i class="far fa-clock"></i> <span id="modalDate"></span></div>
                <img id="modalImg" src="" style="display:none;" alt="News Image">
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
                imgTag.style.display = "block";
            } else {
                imgTag.style.display = "none";
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

