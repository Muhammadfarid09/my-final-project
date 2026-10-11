<?php
session_start();
require_once 'config/config.php';

// ดึงข้อมูลข่าวสารและโปรโมชันที่เผยแพร่แล้ว (Safe Query ผ่าน Prepared Statements)
$news_list = [];
try {
    $stmt = $conn->prepare("
        SELECT n.*, a.admin_name 
        FROM News n 
        LEFT JOIN Admin a ON n.admin_id = a.admin_id 
        WHERE n.news_published_at <= NOW() 
        ORDER BY n.news_published_at DESC
    ");
    $stmt->execute();
    $news_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Promotions Query Error: " . $e->getMessage());
    $news_list = [];
}

// คีย์เวิร์ดสำหรับตรวจจับการเป็นโปรโมชั่นอัตโนมัติ
$promo_keywords = ['โปรโมชั่น', 'โปรโมชัน', 'ส่วนลด', 'พิเศษ', 'สิทธิพิเศษ', 'คูปอง', 'แถม', 'ฟรี', 'discount', 'promotion', 'voucher', 'ลด'];

// นับจำนวนสำหรับ Filter Tabs
$count_all = count($news_list);
$count_promo = 0;
$count_news = 0;

foreach ($news_list as &$item) {
    $text_to_search = mb_strtolower($item['news_title'] . ' ' . $item['news_content'], 'UTF-8');
    $is_promo = false;
    foreach ($promo_keywords as $keyword) {
        if (mb_strpos($text_to_search, mb_strtolower($keyword, 'UTF-8')) !== false) {
            $is_promo = true;
            break;
        }
    }
    $item['is_promo'] = $is_promo;
    $item['category'] = $is_promo ? 'promo' : 'news';
    
    if ($is_promo) {
        $count_promo++;
    } else {
        $count_news++;
    }
}
unset($item);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข่าวสารและโปรโมชัน - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=<?php echo filemtime('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="assets/css/navbar.css?v=<?php echo filemtime('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="assets/css/promotions.css?v=<?php echo filemtime('assets/css/promotions.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="page-body">

    <!-- แถบนำทางส่วนกลาง (Global Navbar 68px) -->
    <?php include_once 'includes/navbar.php'; ?>

    <main class="promotions-wrapper">
        
        <!-- 1. ส่วนหัวแบนเนอร์ข่าวสารและโปรโมชัน (Header Banner) -->
        <header class="promo-header-banner">
            <span class="promo-header-badge">
                <i class="fas fa-bullhorn"></i> ข่าวสารและสิทธิประโยชน์
            </span>
            <h1 class="promo-header-title">
                ข่าวสาร & <span class="text-gold-highlight">โปรโมชันพิเศษ</span>
            </h1>
            <p class="promo-header-desc">
                ติดตามข่าวสารการแข่งขัน กิจกรรมสนามแบดมินตัน และข้อเสนอโปรโมชันสุดคุ้มสำหรับสมาชิก T.S. Pattani
            </p>
        </header>

        <!-- 2. แถบตัวกรอง (Filter Tabs: ทั้งหมด | โปรโมชั่น | ข่าวสารและกิจกรรม) -->
        <section class="promo-filter-bar" aria-label="ตัวกรองประเภทข่าวสาร">
            <button type="button" class="promo-filter-btn active" data-filter="all">
                <i class="fas fa-layer-group"></i> ทั้งหมด 
                <span class="filter-count"><?= $count_all ?></span>
            </button>
            <button type="button" class="promo-filter-btn" data-filter="promo">
                <i class="fas fa-tag"></i> โปรโมชัน 
                <span class="filter-count"><?= $count_promo ?></span>
            </button>
            <button type="button" class="promo-filter-btn" data-filter="news">
                <i class="fas fa-newspaper"></i> ข่าวสาร & กิจกรรม 
                <span class="filter-count"><?= $count_news ?></span>
            </button>
        </section>

        <!-- 3. กริดแสดงรายการการ์ดข่าวสารและโปรโมชัน (Cards Grid) -->
        <section class="promo-grid" id="promoCardsGrid">
            <?php if (empty($news_list)): ?>
                <div class="promo-empty-state">
                    <i class="fas fa-newspaper promo-empty-icon"></i>
                    <h3 class="promo-empty-text">ยังไม่มีข่าวสารหรือโปรโมชันในขณะนี้</h3>
                    <p>กรุณากลับมาติดตามความเคลื่อนไหวและสิทธิพิเศษใหม่ๆ ได้เร็วๆ นี้</p>
                </div>
            <?php else: ?>
                <?php foreach ($news_list as $news): 
                    $news_id = (int)$news['news_id'];
                    $title = htmlspecialchars($news['news_title'] ?? '', ENT_QUOTES, 'UTF-8');
                    $content = htmlspecialchars($news['news_content'] ?? '', ENT_QUOTES, 'UTF-8');
                    $published_date = date('d/m/Y H:i', strtotime($news['news_published_at']));
                    $author = !empty($news['admin_name']) ? htmlspecialchars($news['admin_name'], ENT_QUOTES, 'UTF-8') : 'ผู้ดูแลระบบ';
                    $has_image = !empty($news['news_image']) && file_exists('uploads/news/' . $news['news_image']);
                    $img_src = $has_image ? 'uploads/news/' . htmlspecialchars($news['news_image'], ENT_QUOTES, 'UTF-8') : '';
                    $is_promo = $news['is_promo'];
                    $category = $news['category'];
                ?>
                    <article class="promo-card" data-category="<?= $category ?>">
                        <!-- ส่วนรูปภาพหน้าปกและ Badge -->
                        <div class="promo-card-thumb-wrap">
                            <?php if ($has_image): ?>
                                <img src="<?= $img_src ?>" alt="<?= $title ?>" class="promo-card-img" loading="lazy">
                            <?php else: ?>
                                <div class="promo-card-placeholder">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>

                            <!-- ป้ายกำกับสิทธิพิเศษ (Promo Badge) -->
                            <?php if ($is_promo): ?>
                                <span class="promo-badge badge-gold-promo">
                                    <i class="fas fa-tag"></i> โปรโมชัน
                                </span>
                            <?php else: ?>
                                <span class="promo-badge badge-news-event">
                                    <i class="fas fa-bullhorn"></i> ข่าวสาร
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- ส่วนเนื้อหาในการ์ด -->
                        <div class="promo-card-body">
                            <div class="promo-card-meta">
                                <span><i class="far fa-clock"></i> <?= $published_date ?> น.</span>
                                <span class="dot-separator">•</span>
                                <span><i class="fas fa-user-shield"></i> <?= $author ?></span>
                            </div>

                            <h2 class="promo-card-title" title="<?= $title ?>"><?= $title ?></h2>
                            <p class="promo-card-desc"><?= $content ?></p>

                            <!-- ปุ่ม Actions ท้ายการ์ด -->
                            <div class="promo-card-footer">
                                <?php if ($is_promo): ?>
                                    <a href="booking.php" class="btn-promo-primary">
                                        <i class="fas fa-calendar-plus"></i> จองสนามใช้สิทธิ์
                                    </a>
                                    <button type="button" class="btn-promo-outline trigger-modal"
                                            data-title="<?= $title ?>"
                                            data-date="<?= $published_date ?>"
                                            data-author="<?= $author ?>"
                                            data-img="<?= $img_src ?>"
                                            data-content="<?= nl2br($content) ?>"
                                            data-is-promo="1">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn-promo-primary trigger-modal"
                                            data-title="<?= $title ?>"
                                            data-date="<?= $published_date ?>"
                                            data-author="<?= $author ?>"
                                            data-img="<?= $img_src ?>"
                                            data-content="<?= nl2br($content) ?>"
                                            data-is-promo="0">
                                        อ่านรายละเอียด <i class="fas fa-arrow-right"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

    </main>

    <!-- 4. Modal สำหรับแสดงเนื้อหาข่าวสาร/โปรโมชั่นฉบับเต็ม (Black & Gold Blur Dialog) -->
    <div id="newsModal" class="news-modal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="news-modal-content">
            <header class="news-modal-header">
                <h3 id="modalTitle" class="news-modal-title">
                    <i class="fas fa-bullhorn"></i> รายละเอียดข่าวสาร
                </h3>
                <span class="news-close-btn" id="modalCloseBtn" aria-label="ปิดหน้าต่าง">&times;</span>
            </header>

            <div class="news-modal-body">
                <div class="news-modal-meta">
                    <span><i class="far fa-clock"></i> <span id="modalDate"></span></span>
                    <span class="dot-separator">•</span>
                    <span><i class="fas fa-user-shield"></i> <span id="modalAuthor"></span></span>
                    <span id="modalBadgeSlot"></span>
                </div>

                <img id="modalImg" src="" class="news-modal-img" alt="ภาพประกอบข่าว" style="display: none;">

                <div id="modalContent" class="news-modal-text"></div>
            </div>

            <footer class="news-modal-footer">
                <a href="booking.php" id="modalBookingBtn" class="btn-promo-primary" style="display: none;">
                    <i class="fas fa-calendar-plus"></i> จองสนามเลย
                </a>
                <button type="button" class="btn-promo-outline" id="modalDismissBtn">
                    ปิดหน้าต่าง
                </button>
            </footer>
        </div>
    </div>

    <script>
        (function() {
            // 1. ระบบ Filter Tabs (ทั้งหมด | โปรโมชั่น | ข่าวสาร)
            const filterButtons = document.querySelectorAll('.promo-filter-btn');
            const promoCards = document.querySelectorAll('.promo-card');

            filterButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const selectedFilter = this.getAttribute('data-filter');

                    promoCards.forEach(card => {
                        const cardCategory = card.getAttribute('data-category');
                        if (selectedFilter === 'all' || cardCategory === selectedFilter) {
                            card.style.display = 'flex';
                            card.style.opacity = '1';
                        } else {
                            card.style.display = 'none';
                            card.style.opacity = '0';
                        }
                    });
                });
            });

            // 2. ระบบ Modal แสดงรายละเอียดข่าว/โปรโมชั่น
            const modal = document.getElementById('newsModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalDate = document.getElementById('modalDate');
            const modalAuthor = document.getElementById('modalAuthor');
            const modalImg = document.getElementById('modalImg');
            const modalContent = document.getElementById('modalContent');
            const modalBookingBtn = document.getElementById('modalBookingBtn');
            const modalBadgeSlot = document.getElementById('modalBadgeSlot');
            const closeBtn = document.getElementById('modalCloseBtn');
            const dismissBtn = document.getElementById('modalDismissBtn');

            function openModal(data) {
                modalTitle.textContent = data.title;
                modalDate.textContent = data.date + ' น.';
                modalAuthor.textContent = data.author;
                modalContent.innerHTML = data.content;

                if (data.img && data.img.trim() !== '') {
                    modalImg.src = data.img;
                    modalImg.style.display = 'block';
                } else {
                    modalImg.src = '';
                    modalImg.style.display = 'none';
                }

                if (data.isPromo === '1') {
                    modalBookingBtn.style.display = 'inline-flex';
                    modalBadgeSlot.innerHTML = '<span class="promo-badge badge-gold-promo" style="position:static; margin-left:auto;"><i class="fas fa-tag"></i> โปรโมชัน</span>';
                } else {
                    modalBookingBtn.style.display = 'none';
                    modalBadgeSlot.innerHTML = '<span class="promo-badge badge-news-event" style="position:static; margin-left:auto;"><i class="fas fa-bullhorn"></i> ข่าวสาร</span>';
                }

                modal.style.display = 'block';
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            // ผูก Event กับปุ่ม Trigger Modal
            document.querySelectorAll('.trigger-modal').forEach(btn => {
                btn.addEventListener('click', function() {
                    const data = {
                        title: this.getAttribute('data-title'),
                        date: this.getAttribute('data-date'),
                        author: this.getAttribute('data-author'),
                        img: this.getAttribute('data-img'),
                        content: this.getAttribute('data-content'),
                        isPromo: this.getAttribute('data-is-promo')
                    };
                    openModal(data);
                });
            });

            closeBtn.addEventListener('click', closeModal);
            dismissBtn.addEventListener('click', closeModal);

            // ปิดเมื่อคลิกนอกกล่อง Modal หรือกดปุ่ม Escape
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            window.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.style.display === 'block') {
                    closeModal();
                }
            });
        })();
    </script>
</body>
</html>
