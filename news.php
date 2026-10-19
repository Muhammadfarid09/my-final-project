<?php
session_start();
require_once 'config/config.php';

// ดึงข้อมูลข่าวสารและโปรโมชันเฉพาะรายการที่เปิดใช้งาน (status = 'active')
try {
    $stmt = $conn->prepare("
        SELECT * FROM News 
        WHERE (status = :status OR status IS NULL)
        ORDER BY news_published_at DESC
    ");
    $stmt->execute([':status' => 'active']);
    $news_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการโหลดข้อมูล: " . htmlspecialchars($e->getMessage()));
}

// ตรวจสอบ Parameter ID กรณีเปิดลิงก์ตรงมายังข่าวเจาะจง (?id=...)
$initial_news_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข่าวสารและโปรโมชัน - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.7">
    <link rel="stylesheet" href="assets/css/navbar.css?v=<?php echo file_exists('assets/css/navbar.css') ? filemtime('assets/css/navbar.css') : time(); ?>">
    <link rel="stylesheet" href="assets/css/news.css?v=<?php echo file_exists('assets/css/news.css') ? filemtime('assets/css/news.css') : time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="page-body">

    <?php include_once 'includes/navbar.php'; ?>

    <main class="news-page-wrapper" data-initial-id="<?php echo $initial_news_id; ?>">
        
        <!-- แบนเนอร์ส่วนหัว ธีม Black & Gold -->
        <div class="news-header-banner">
            <div class="news-banner-content">
                <span class="news-banner-badge"><i class="fas fa-sparkles"></i> สิทธิพิเศษและอัปเดตล่าสุด</span>
                <h1 class="news-banner-title"><i class="fas fa-bullhorn text-gold"></i> ข่าวสารและโปรโมชัน</h1>
                <p class="news-banner-desc">ติดตามสิทธิพิเศษ ส่วนลดค่าคอร์ทแบดมินตัน และกิจกรรมความเคลื่อนไหวจาก T.S. Pattani</p>
            </div>
        </div>

        <!-- แท็บตัวกรอง (HCI Interactive Filter Tabs) -->
        <div class="promo-filter-wrapper">
            <div class="promo-filter-tabs" role="tablist">
                <button type="button" class="btn-filter-tab active" data-filter="all" role="tab" aria-selected="true">
                    <i class="fas fa-th-large"></i> ทั้งหมด
                </button>
                <button type="button" class="btn-filter-tab" data-filter="โปรโมชั่น" role="tab" aria-selected="false">
                    <i class="fas fa-tags"></i> โปรโมชั่น
                </button>
                <button type="button" class="btn-filter-tab" data-filter="ข่าวสารกิจกรรม" role="tab" aria-selected="false">
                    <i class="fas fa-calendar-alt"></i> ข่าวสารและกิจกรรม
                </button>
            </div>
        </div>

        <!-- กริดการ์ดข่าวสารและโปรโมชัน -->
        <div class="news-grid" id="newsGrid">
            <?php if (empty($news_list)): ?>
                <div class="news-empty-state">
                    <i class="fas fa-bullhorn fa-3x news-empty-icon"></i>
                    <h3 class="news-empty-title">ยังไม่มีข่าวสารหรือโปรโมชันในขณะนี้</h3>
                    <p class="news-empty-text">โปรดติดตามสิทธิพิเศษและกิจกรรมใหม่ๆ ได้ที่นี่เร็วๆ นี้</p>
                </div>
            <?php else: ?>
                <?php foreach ($news_list as $news): 
                    $news_type = $news['news_type'] ?? 'ข่าวสารกิจกรรม';
                    $is_promo = ($news_type === 'โปรโมชั่น');
                    $badge_label = $is_promo ? 'โปรโมชั่นพิเศษ' : 'ข่าวสาร & กิจกรรม';
                    $badge_icon = $is_promo ? 'fa-tags' : 'fa-bullhorn';
                ?>
                    <article class="news-card" data-type="<?php echo htmlspecialchars($news_type); ?>">
                        <div class="news-img-wrapper">
                            <span class="news-badge">
                                <i class="fas <?php echo $badge_icon; ?>"></i> <?php echo $badge_label; ?>
                            </span>
                            <?php if (!empty($news['news_image'])): ?>
                                <img src="uploads/news/<?php echo htmlspecialchars($news['news_image']); ?>" 
                                     class="news-img" 
                                     alt="<?php echo htmlspecialchars($news['news_title']); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="news-img-placeholder">
                                    <i class="fas fa-shuttlecock"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="news-body">
                            <div class="news-date">
                                <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($news['news_published_at'])); ?> น.
                            </div>
                            <h2 class="news-title"><?php echo htmlspecialchars($news['news_title']); ?></h2>
                            <p class="news-content"><?php echo htmlspecialchars(strip_tags($news['news_content'])); ?></p>
                            
                            <div class="news-card-footer">
                                <button type="button" 
                                        class="btn-read-more" 
                                        onclick="openNewsDetailModal(<?php echo intval($news['news_id']); ?>)">
                                    <span>อ่านรายละเอียด</span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- เก็บข้อมูลดิบแบบซ่อนไว้เพื่อเปิด Modal ทันทีโดยไม่ต้องรอ Request ซ้ำ -->
                        <template id="news-data-<?php echo intval($news['news_id']); ?>">
                            <div class="template-payload"
                                 data-id="<?php echo intval($news['news_id']); ?>"
                                 data-title="<?php echo htmlspecialchars($news['news_title'], ENT_QUOTES, 'UTF-8'); ?>"
                                 data-date="<?php echo date('d/m/Y H:i', strtotime($news['news_published_at'])); ?>"
                                 data-type="<?php echo htmlspecialchars($badge_label, ENT_QUOTES, 'UTF-8'); ?>"
                                 data-image="<?php echo !empty($news['news_image']) ? 'uploads/news/' . htmlspecialchars($news['news_image'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                 data-content="<?php echo htmlspecialchars(nl2br($news['news_content']), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </template>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Empty State จำเพาะกรณีตัวกรองไม่พบข้อมูล -->
        <div id="filterEmptyState" class="news-empty-state d-none">
            <i class="fas fa-search fa-3x news-empty-icon"></i>
            <h3 class="news-empty-title">ไม่พบรายการในหมวดหมู่นี้</h3>
            <p class="news-empty-text">ยังไม่มีข้อมูลข่าวสารหรือโปรโมชันสำหรับหมวดหมู่ที่คุณเลือก</p>
        </div>

    </main>

    <!-- Modal รายละเอียดข่าวสารและโปรโมชัน (Z-Index 2000) -->
    <div id="newsModal" class="news-modal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="news-modal-overlay" onclick="closeNewsDetailModal()"></div>
        <div class="news-modal-content">
            <div class="news-modal-header">
                <div class="news-modal-header-meta">
                    <span id="modalTypeBadge" class="modal-type-badge"></span>
                    <h3 id="modalTitle" class="news-modal-title">หัวข้อข่าว</h3>
                </div>
                <button type="button" class="news-close-btn" onclick="closeNewsDetailModal()" aria-label="ปิดหน้าต่าง">&times;</button>
            </div>
            <div class="news-modal-body">
                <div class="news-modal-date">
                    <i class="far fa-clock"></i> โพสต์เมื่อ: <span id="modalDate"></span> น.
                </div>
                <div id="modalImgContainer" class="modal-img-container d-none">
                    <img id="modalImg" src="" class="news-modal-img" alt="ภาพประกอบ">
                </div>
                <div id="modalContent" class="news-modal-text"></div>
            </div>
            <div class="news-modal-footer">
                <button type="button" class="btn-modal-close" onclick="closeNewsDetailModal()">
                    <i class="fas fa-times"></i> ปิดหน้าต่าง
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/member.js?v=<?php echo file_exists('assets/js/member.js') ? filemtime('assets/js/member.js') : time(); ?>"></script>
</body>
</html>

