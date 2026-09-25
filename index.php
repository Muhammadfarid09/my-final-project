<?php
session_start();
// เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล
require_once 'config/config.php';

// ดึงข้อมูลข่าวสาร 3 อันดับล่าสุดจากฐานข้อมูลมาแสดงผล (ตามขอบเขต 1.3.9)
try {
    $stmt = $conn->query("SELECT * FROM NEWS ORDER BY news_published_at DESC LIMIT 3");
    $news_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $news_list = [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    
    <!-- เรียกใช้แถบเมนู (Navbar) ที่เราเพิ่งสร้าง -->
    <?php include 'includes/navbar.php'; ?>

    <!-- ส่วนแบนเนอร์ต้อนรับ (Hero Section) -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>ยินดีต้อนรับสู่ T.S. Pattani Badminton</h1>
            <p>สนามแบดมินตันมาตรฐาน จองง่าย สะดวก รวดเร็ว พร้อมระบบสะสมแต้มแลกของรางวัล</p>
            <a href="booking.php" class="btn-primary-custom"><i class="fas fa-calendar-alt"></i> จองสนามเลยตอนนี้</a>
        </div>
    </section>

    <!-- ส่วนแสดงข่าวสารและประชาสัมพันธ์ -->
    <h2 class="section-title"><i class="fas fa-bullhorn"></i> ข่าวสารและกิจกรรมล่าสุด</h2>
    
    <div class="news-container">
        <?php if (count($news_list) > 0): ?>
            <?php foreach ($news_list as $news): ?>
                <div class="news-card">
                    <!-- ตรวจสอบว่ามีรูปภาพข่าวหรือไม่ -->
                    <?php if (!empty($news['news_image'])): ?>
                        <img src="uploads/news/<?php echo htmlspecialchars($news['news_image']); ?>" alt="News Image" class="news-img">
                    <?php else: ?>
                        <!-- รูปภาพเริ่มต้นกรณีแอดมินไม่ได้อัปโหลดรูป -->
                        <div class="news-img-placeholder">
                            <i class="fas fa-image fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="news-content">
                        <div class="news-date">
                            <i class="far fa-clock"></i> 
                            <!-- แปลงรูปแบบวันที่ให้ดูง่ายขึ้น -->
                            <?php echo date('d/m/Y H:i', strtotime($news['news_published_at'])); ?> น.
                        </div>
                        <h3 class="news-title"><?php echo htmlspecialchars($news['news_title']); ?></h3>
                        <p class="news-desc"><?php echo nl2br(htmlspecialchars($news['news_content'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- กรณีไม่มีข่าวสารในระบบ -->
            <div style="text-align: center; grid-column: 1 / -1; color: #6c757d; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <i class="fas fa-info-circle fa-3x" style="margin-bottom: 15px; color: #ced4da;"></i><br>
                ยังไม่มีข่าวสารหรือประกาศในขณะนี้
            </div>
        <?php endif; ?>
    </div>

    <!-- เรียกใช้ JavaScript สำหรับเมนูและการแจ้งเตือน -->
    <script src="assets/js/member.js?v=1.0"></script>
</body>
</html>
