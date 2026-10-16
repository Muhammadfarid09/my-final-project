<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// เรียกใช้ไฟล์ตั้งค่าฐานข้อมูลและฟังก์ชันกลาง
require_once 'config/config.php';
require_once 'includes/functions.php';

// 1. ดึงข้อมูล 3 อันดับสมาชิกที่จองสำเร็จสูงสุด (Top Players Leaderboard)
$top_players = get_top_booking_members($conn, 3);
$rank1_player = $top_players[0] ?? null;
$rank2_player = $top_players[1] ?? null;
$rank3_player = $top_players[2] ?? null;

// 2. ดึงข้อมูลข่าวสารและโปรโมชัน 3 อันดับล่าสุด
try {
    $stmt_news = $conn->query("SELECT * FROM News ORDER BY news_published_at DESC LIMIT 3");
    $news_list = $stmt_news->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $news_list = [];
}

// 3. ดึงข้อมูลคะแนนสะสมและระดับสมาชิก (กรณีเข้าสู่ระบบแล้ว สำหรับ Navbar)
$member_points = 0;
$member_level = 'Bronze';
if (isset($_SESSION['member_id']) && isset($conn)) {
    $stmt_nav = $conn->prepare("SELECT point_balance, member_level FROM Point WHERE member_id = :mid");
    $stmt_nav->execute([':mid' => $_SESSION['member_id']]);
    $nav_data = $stmt_nav->fetch(PDO::FETCH_ASSOC);
    if ($nav_data) {
        $member_points = $nav_data['point_balance'];
        $member_level = $nav_data['member_level'];
    }
}

// 4. ดึงข้อมูลสถิติสนามสำหรับ Live Stats Dashboard
$court_count = 10;
try {
    $stmt_court_count = $conn->query("SELECT COUNT(*) FROM court WHERE court_status != 'ปิดปรับปรุง'");
    $court_count_db = $stmt_court_count->fetchColumn();
    if ($court_count_db > 0) {
        $court_count = $court_count_db;
    }
} catch (PDOException $e) {
    $court_count = 10;
}

$booking_stat_display = '500+';
try {
    $stmt_book_count = $conn->query("SELECT COUNT(*) FROM booking WHERE booking_status = 'จองแล้ว'");
    $actual_bookings = $stmt_book_count->fetchColumn();
    $total_stat = max(500, 500 + intval($actual_bookings));
    $booking_stat_display = number_format($total_stat) . '+';
} catch (PDOException $e) {
    $booking_stat_display = '500+';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>T.S. Pattani Badminton - สนามแบดมินตันมาตรฐาน BWF ปัตตานี</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=<?php echo filemtime('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="assets/css/responsive-mobile.css?v=<?php echo filemtime('assets/css/responsive-mobile.css'); ?>">
    <link rel="stylesheet" href="assets/css/navbar.css?v=<?php echo filemtime('assets/css/navbar.css'); ?>">
    <link rel="stylesheet" href="assets/css/promotions.css?v=<?php echo filemtime('assets/css/promotions.css'); ?>">
    <link rel="stylesheet" href="assets/css/home.css?v=<?php echo filemtime('assets/css/home.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="page-body">
    
    <!-- ============================================== -->
    <!-- 1. แถบเมนูหลัก (Global Navbar)                  -->
    <!-- ============================================== -->
    <?php include_once 'includes/navbar.php'; ?>

    <main class="home-page-wrap">
        <!-- ============================================== -->
        <!-- 1. Hero Section (Clean & Focused)              -->
        <!-- ============================================== -->
        <section class="home-hero-section">
            <div class="home-container">
                <div class="home-hero-content">
                    <h1 class="home-hero-title">
                        สนามแบดมินตันมาตรฐาน BWF <span class="text-gold">ใจกลางเมืองปัตตานี</span>
                    </h1>
                    <p class="home-hero-subtitle">
                        คอร์ทพื้นยาง 5 ชั้น ซับแรงกระแทก ระบบไฟ Anti-Glare พร้อมระบบจองคิวออนไลน์ 24 ชม.
                    </p>
                    <div class="home-hero-actions">
                        <a href="booking.php" class="home-btn-primary">
                            <i class="fas fa-calendar-check"></i>
                            <span>จองสนาม</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================== -->
        <!-- 2. แถบสถิติ/จุดเด่นสั้นๆ (4 บล็อกแนวนอน)      -->
        <!-- ============================================== -->
        <section class="home-stats-section">
            <div class="home-container">
                <div class="home-stats-grid">
                    <div class="home-stat-card">
                        <span class="home-stat-number"><?php echo htmlspecialchars($court_count); ?></span>
                        <div class="home-stat-title">คอร์ทมาตรฐาน BWF</div>
                        <p class="home-stat-desc">พื้นยาง 5 ชั้น ไฟ Anti-Glare</p>
                    </div>

                    <div class="home-stat-card">
                        <span class="home-stat-number"><?php echo htmlspecialchars($booking_stat_display); ?></span>
                        <div class="home-stat-title">แมตช์ที่จองสำเร็จ</div>
                        <p class="home-stat-desc">ล็อกคิวอัตโนมัติเรียลไทม์</p>
                    </div>

                    <div class="home-stat-card">
                        <span class="home-stat-number">100%</span>
                        <div class="home-stat-title">มาตรฐานความพึงพอใจ</div>
                        <p class="home-stat-desc">ผู้เล่นไว้วางใจสูงสุดในปัตตานี</p>
                    </div>

                    <div class="home-stat-card">
                        <span class="home-stat-number">24 ชม.</span>
                        <div class="home-stat-title">ระบบบริการครบวงจร</div>
                        <p class="home-stat-desc">จองออนไลน์ตลอด 24 ชั่วโมง</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================== -->
        <!-- 3. 3 ขั้นตอนการใช้งาน (3-Step Booking Guide)   -->
        <!-- ============================================== -->
        <section class="home-section" id="how-it-works">
            <div class="home-container">
                <div class="home-section-header">
                    <h2 class="home-section-title">3 ขั้นตอนการจองสนาม</h2>
                    <p class="home-section-desc">จองคิวออนไลน์ง่าย สะดวก รวดเร็ว ล็อกเวลาได้แน่นอน</p>
                </div>

                <div class="home-steps-grid">
                    <div class="home-step-card">
                        <span class="home-step-number">01</span>
                        <h3 class="home-step-title">เลือกวันและเวลา</h3>
                        <p class="home-step-desc">เช็คตารางสนามว่างแบบ Real-time พร้อมอัตราค่าบริการรายชั่วโมง</p>
                    </div>

                    <div class="home-step-card">
                        <span class="home-step-number">02</span>
                        <h3 class="home-step-title">เลือกคอร์ทและอุปกรณ์</h3>
                        <p class="home-step-desc">เลือกสนามที่ชอบ พร้อมบริการเช่าอุปกรณ์และเครื่องดื่ม</p>
                    </div>

                    <div class="home-step-card">
                        <span class="home-step-number">03</span>
                        <h3 class="home-step-title">สแกนจ่ายรับคิวทันที</h3>
                        <p class="home-step-desc">ชำระผ่าน QR Code พร้อมแนบสลิปเพื่อยืนยันคิวทันที</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================== -->
        <!-- 4. สิ่งอำนวยความสะดวก/จุดเด่นสนาม (Key Amenities) -->
        <!-- ============================================== -->
        <section class="home-section" id="amenities">
            <div class="home-container">
                <div class="home-section-header">
                    <h2 class="home-section-title">สิ่งอำนวยความสะดวก</h2>
                    <p class="home-section-desc">มาตรฐานสนามระดับสากลเพื่อประสบการณ์การเล่นที่ดีที่สุด</p>
                </div>

                <div class="home-amenities-grid">
                    <div class="home-amenity-card">
                        <div class="home-amenity-icon"><i class="fas fa-layer-group"></i></div>
                        <h3 class="home-amenity-title">พื้นยางมาตรฐาน BWF 5 ชั้น</h3>
                        <p class="home-amenity-desc">ซับแรงกระแทก ปกป้องข้อต่อและหัวเข่า ยึดเกาะดีเยี่ยม ปลอดภัยทุกการเคลื่อนไหว</p>
                    </div>

                    <div class="home-amenity-card">
                        <div class="home-amenity-icon"><i class="fas fa-eye"></i></div>
                        <h3 class="home-amenity-title">ระบบไฟ LED Anti-Glare</h3>
                        <p class="home-amenity-desc">ตัดแสงสะท้อน สว่างสม่ำเสมอทั่วทั้งคอร์ท ไม่เมื่อยล้าสายตา มองเห็นลูกชัดเจน</p>
                    </div>

                    <div class="home-amenity-card">
                        <div class="home-amenity-icon"><i class="fas fa-wind"></i></div>
                        <h3 class="home-amenity-title">ระบบหมุนเวียนอากาศถ่ายเท</h3>
                        <p class="home-amenity-desc">โถงอาคารสูงโปร่ง พัดลมระบายอากาศแรงดันสูงรอบทิศทาง ไม่อับชื้น เล่นได้ต่อเนื่อง</p>
                    </div>

                    <div class="home-amenity-card">
                        <div class="home-amenity-icon"><i class="fas fa-shower"></i></div>
                        <h3 class="home-amenity-title">ห้องน้ำ & ห้องอาบน้ำ</h3>
                        <p class="home-amenity-desc">แยกสัดส่วนชาย-หญิง สะอาด ปลอดภัย พร้อมเครื่องทำน้ำอุ่นและล็อกเกอร์เก็บของ</p>
                    </div>

                    <div class="home-amenity-card">
                        <div class="home-amenity-icon"><i class="fas fa-parking"></i></div>
                        <h3 class="home-amenity-title">ที่จอดรถกว้างขวาง & CCTV</h3>
                        <p class="home-amenity-desc">รองรับรถยนต์และจักรยานยนต์มากกว่า 50 คัน พร้อมกล้องวงจรปิดดูแลตลอด 24 ชม.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================== -->
        <!-- 5. ข่าวสารและโปรโมชัน (News & Announcements)    -->
        <!-- ============================================== -->
        <section class="home-section" id="news">
            <div class="home-container">
                <div class="home-section-header header-split">
                    <div>
                        <h2 class="home-section-title">ข่าวสารและโปรโมชัน</h2>
                        <p class="home-section-desc">อัปเดตกิจกรรมพิเศษและสิทธิประโยชน์ล่าสุดจากทางสนาม</p>
                    </div>
                    <a href="promotions.php" class="home-link-gold">
                        <span>ดูทั้งหมด</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="home-news-grid">
                    <?php if (!empty($news_list) && count($news_list) > 0): ?>
                        <?php foreach ($news_list as $news): ?>
                            <article class="home-news-card">
                                <?php if (!empty($news['news_image'])): ?>
                                    <img src="uploads/news/<?php echo htmlspecialchars($news['news_image']); ?>" alt="<?php echo htmlspecialchars($news['news_title']); ?>" class="home-news-thumb" loading="lazy">
                                <?php else: ?>
                                    <div class="home-news-thumb-placeholder">
                                        <i class="fas fa-newspaper fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="home-news-body">
                                    <div class="home-news-meta">
                                        <i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($news['news_published_at'])); ?>
                                    </div>
                                    <h3 class="home-news-title"><?php echo htmlspecialchars($news['news_title']); ?></h3>
                                    <p class="home-news-desc"><?php echo htmlspecialchars(strip_tags($news['news_content'])); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="home-news-empty">
                            ยังไม่มีข่าวสารหรือประกาศในขณะนี้
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ============================================== -->
        <!-- 6. สถานที่และเวลาทำการ (Location & Hours)       -->
        <!-- ============================================== -->
        <section class="home-section" id="location">
            <div class="home-container">
                <div class="home-section-header">
                    <h2 class="home-section-title">เวลาทำการและที่ตั้ง</h2>
                    <p class="home-section-desc">ตั้งอยู่ใจกลางเมืองปัตตานี เดินทางสะดวก พร้อมสิ่งอำนวยความสะดวกครบครัน</p>
                </div>

                <div class="home-location-grid">
                    <div class="home-info-box">
                        <div>
                            <h3 class="home-info-title"><i class="fas fa-clock"></i> เวลาเปิดให้บริการ</h3>
                            <div class="home-schedule-table">
                                <div class="home-schedule-row">
                                    <span class="home-schedule-day">วันจันทร์ - วันศุกร์</span>
                                    <span class="home-schedule-time">09:00 - 24:00 น.</span>
                                </div>
                                <div class="home-schedule-row">
                                    <span class="home-schedule-day">วันเสาร์ - วันอาทิตย์</span>
                                    <span class="home-schedule-time">08:00 - 24:00 น.</span>
                                </div>
                                <div class="home-schedule-row">
                                    <span class="home-schedule-day">วันหยุดนักขัตฤกษ์</span>
                                    <span class="home-schedule-time">เปิดให้บริการตามปกติ</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="home-info-title"><i class="fas fa-headset"></i> ช่องทางการติดต่อ</h3>
                            <div class="home-contact-chips">
                                <a href="tel:0812345678" class="home-chip">
                                    <i class="fas fa-phone-alt"></i> 081-234-5678
                                </a>
                                <a href="https://line.me" target="_blank" rel="noopener noreferrer" class="home-chip">
                                    <i class="fab fa-line"></i> @tspattani
                                </a>
                                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="home-chip">
                                    <i class="fab fa-facebook-f"></i> T.S. Pattani
                                </a>
                                <a href="chat.php" class="home-chip">
                                    <i class="fas fa-comment-dots"></i> แชทกับเรา
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="home-info-box">
                        <div>
                            <h3 class="home-info-title"><i class="fas fa-map-marker-alt"></i> แผนที่การเดินทาง</h3>
                            <p class="home-address-text">123 ถนนเจริญประดิษฐ์ ตำบลรูสะมิแล อำเภอเมือง จังหวัดปัตตานี 94000 (ใกล้กับมหาวิทยาลัยสงขลานครินทร์ วิทยาเขตปัตตานี)</p>
                            <div class="home-map-container">
                                <iframe 
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.916853248356!2d101.24072387588383!3d6.866038419385521!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31b269588b3f63e3%3A0x6b0983199c0957b!2sPattani%20Stadium!5e0!3m2!1sen!2sth!4v1700000000000!5m2!1sen!2sth" 
                                    allowfullscreen="" 
                                    loading="lazy" 
                                    referrerpolicy="no-referrer-when-downgrade"
                                    title="T.S. Pattani Badminton Location Map">
                                </iframe>
                            </div>
                        </div>
                        <a href="https://maps.google.com" target="_blank" rel="noopener noreferrer" class="home-map-btn">
                            <i class="fas fa-directions"></i> นำทางด้วย Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================== -->
        <!-- 7. CTA ปิดท้าย (Closing Call to Action)         -->
        <!-- ============================================== -->
        <section class="home-cta-section">
            <div class="home-container">
                <div class="home-cta-card">
                    <h2 class="home-cta-title">พร้อมลงสนามแล้วหรือยัง?</h2>
                    <p class="home-cta-desc">เช็คคอร์ทว่างและจองสนามออนไลน์ได้ทันที ตลอด 24 ชั่วโมง</p>
                    <a href="booking.php" class="home-btn-primary">
                        <i class="fas fa-calendar-check"></i>
                        <span>จองสนามเลย</span>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- ============================================== -->
    <!-- ส่วนท้ายของเว็บไซต์ (Global Footer & Scripts)  -->
    <!-- ============================================== -->
    <?php include 'includes/footer.php'; ?>

    <!-- เรียกใช้ JavaScript ส่วนกลาง -->
    <script src="assets/js/member.js?v=<?php echo filemtime('assets/js/member.js'); ?>"></script>
</body>
</html>
