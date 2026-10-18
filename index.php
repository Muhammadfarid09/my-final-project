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
                <div class="home-hero-grid">
                    <div class="home-hero-content">
                        <div class="home-hero-badge">
                            <span class="badge-dot-live"></span>
                            <span>T.S. BADMINTON PATTANI &bull; มาตรฐาน BWF</span>
                        </div>
                        <h1 class="home-hero-title">
                            สนามแบดมินตันมาตรฐาน BWF <br class="hero-br-desktop">
                            <span class="text-gold">ใจกลางเมืองปัตตานี</span>
                        </h1>
                        <p class="home-hero-subtitle">
                            คอร์ทพื้นยาง 5 ชั้น ซับแรงกระแทก ระบบไฟ Anti-Glare พร้อมระบบจองคิวออนไลน์ 24 ชม.
                        </p>
                        <div class="home-hero-actions">
                            <a href="booking.php" class="home-btn-primary">
                                <i class="fas fa-calendar-check"></i>
                                <span>จองสนาม</span>
                            </a>
                            <?php if (isset($_SESSION['member_id'])): ?>
                                <a href="profile.php" class="home-btn-secondary">
                                    <i class="fas fa-user-circle"></i>
                                    <span>โปรไฟล์ของฉัน</span>
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="home-btn-secondary">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>เข้าสู่ระบบ</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="home-hero-trust-chips">
                            <span class="hero-trust-item"><i class="fas fa-check-circle text-gold"></i> เช็คคอร์ทว่างเรียลไทม์</span>
                            <span class="hero-trust-item"><i class="fas fa-check-circle text-gold"></i> ล็อกคิวอัตโนมัติ</span>
                            <span class="hero-trust-item"><i class="fas fa-check-circle text-gold"></i> สะสมแต้มทุกการเล่น</span>
                        </div>
                    </div>
                    <div class="home-hero-mascot-col" aria-label="T.S. Pattani Mascot">
                        <div class="mascot-ambient-glow"></div>
                        <div class="mascot-wrapper">
                            <img src="assets/img/mascot-hero-player.png?v=<?php echo file_exists(__DIR__ . '/assets/img/mascot-hero-player.png') ? filemtime(__DIR__ . '/assets/img/mascot-hero-player.png') : time(); ?>" 
                                 alt="T.S. Pattani Badminton Mascot" 
                                 class="home-hero-mascot-img" 
                                 width="460" 
                                 height="460"
                                 loading="eager">
                            <div class="mascot-shadow"></div>
                            <div class="mascot-floating-tag">
                                <span class="tag-live-dot"></span>
                                <i class="fas fa-bolt text-gold"></i>
                                <span>พร้อมเสิร์ฟความสนุก!</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================== -->
        <!-- 2. แถบสถิติ/จุดเด่นสั้นๆ (การ์ดสีขาวแถวยาว Half Overlap) -->
        <!-- ============================================== -->
        <section class="home-stats-section">
            <div class="home-container">
                <div class="home-stats-unified-card">
                    <!-- ช่องที่ 1: คอร์ทมาตรฐาน BWF -->
                    <div class="home-stat-item">
                        <div class="home-stat-icon-wrapper">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="home-stat-number"><?php echo htmlspecialchars($court_count); ?></div>
                        <div class="home-stat-title">คอร์ทมาตรฐาน BWF</div>
                        <p class="home-stat-desc">พื้นยาง 5 ชั้น ไฟ Anti-Glare</p>
                    </div>

                    <!-- ช่องที่ 2: แมตช์ที่จองสำเร็จ -->
                    <div class="home-stat-item">
                        <div class="home-stat-icon-wrapper">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="home-stat-number"><?php echo htmlspecialchars($booking_stat_display); ?></div>
                        <div class="home-stat-title">แมตช์ที่จองสำเร็จ</div>
                        <p class="home-stat-desc">ล็อกคิวอัตโนมัติเรียลไทม์</p>
                    </div>

                    <!-- ช่องที่ 3: มาตรฐานความพึงพอใจ -->
                    <div class="home-stat-item">
                        <div class="home-stat-icon-wrapper">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="home-stat-number">100%</div>
                        <div class="home-stat-title">มาตรฐานความพึงพอใจ</div>
                        <p class="home-stat-desc">ผู้เล่นไว้วางใจสูงสุดในปัตตานี</p>
                    </div>

                    <!-- ช่องที่ 4: ระบบบริการครบวงจร -->
                    <div class="home-stat-item">
                        <div class="home-stat-icon-wrapper">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="home-stat-number">24 ชม.</div>
                        <div class="home-stat-title">ระบบบริการครบวงจร</div>
                        <p class="home-stat-desc">จองออนไลน์ตลอด 24 ชั่วโมง</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================== -->
        <!-- 3. จุดเด่นและบริการ (5 Features Matchday Style)  -->
        <!-- ============================================== -->
        <section class="home-features-section" id="features">
            <div class="home-container">
                <!-- ส่วนหัวข้อและ Tagline สไตล์ Matchday -->
                <div class="home-features-header">
                    <div class="home-features-tag">
                        <span class="home-features-tag-line"></span>
                        <span class="home-features-tag-text">T.S. BADMINTON PATTANI</span>
                        <span class="home-features-tag-line"></span>
                    </div>
                    <h2 class="home-features-title">สนามแบดมินตันหนึ่งเดียวในปัตตานี</h2>
                </div>

                <!-- กริด 5 การ์ดจุดเด่น (แบบไม่มีม็อกอัปมือถือ) -->
                <div class="home-features-grid">
                    <!-- การ์ด 1: จองง่ายไม่มีสะดุด -->
                    <div class="home-feature-card">
                        <div class="home-feature-icon-box">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="home-feature-content">
                            <h3 class="home-feature-title">จองง่ายไม่มีสะดุด</h3>
                            <p class="home-feature-desc">เช็กตารางและเลือกล็อกคอร์ดออนไลน์ได้ทันที หมดปัญหาโทรจองซ้ำซ้อน</p>
                        </div>
                    </div>

                    <!-- การ์ด 3: ยืนยันการจองทันที -->
                    <div class="home-feature-card">
                        <div class="home-feature-icon-box">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="home-feature-content">
                            <h3 class="home-feature-title">ยืนยันการจองทันที</h3>
                            <p class="home-feature-desc">สแกนจ่ายผ่าน QR Code พร้อมระบบตรวจสอบสลิป ล็อกคิวการเล่นได้แน่นอน</p>
                        </div>
                    </div>

                    <!-- การ์ด 2: เช็กตารางว่างแบบเรียลไทม์ -->
                    <div class="home-feature-card">
                        <div class="home-feature-icon-box">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="home-feature-content">
                            <h3 class="home-feature-title">เช็กตารางว่างแบบเรียลไทม์</h3>
                            <p class="home-feature-desc">อัปเดตสถานะคอร์ดว่างและคำนวณอัตราค่าบริการตามช่วงเวลาจริงแม่นยำ</p>
                        </div>
                    </div>

                    <!-- การ์ด 4: อุปกรณ์และบริการครบครัน -->
                    <div class="home-feature-card">
                        <div class="home-feature-icon-box">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="home-feature-content">
                            <h3 class="home-feature-title">อุปกรณ์และบริการครบครัน</h3>
                            <p class="home-feature-desc">มีบริการเช่าไม้แบด ลูกแบดคุณภาพมาตรฐาน และเครื่องดื่มพร้อมบริการ</p>
                        </div>
                    </div>

                    <!-- การ์ด 5: สะสมแต้มทุกการจอง (การ์ดเต็มกึ่งกลางด้านล่าง) -->
                    <div class="home-feature-card home-feature-card-wide">
                        <div class="home-feature-icon-box">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="home-feature-content">
                            <h3 class="home-feature-title">สะสมแต้มทุกการจอง</h3>
                            <p class="home-feature-desc">รับคะแนนสะสมอัตโนมัติทุกครั้งที่เข้าใช้บริการ เพื่อนำไปแลกรับของรางวัลพิเศษ</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================== -->
        <!-- 4. สิ่งอำนวยความสะดวก/จุดเด่นสนาม (Key Amenities - Clean Theme) -->
        <!-- ============================================== -->
        <section class="home-amenities-section" id="amenities">
            <div class="home-container">
                <!-- ส่วนหัวข้อและ Tagline สไตล์ Clean & Modern -->
                <div class="home-amenities-header">
                    <div class="home-amenities-tag">
                        <span class="home-amenities-tag-line"></span>
                        <span class="home-amenities-tag-text">FACILITIES & SERVICES</span>
                        <span class="home-amenities-tag-line"></span>
                    </div>
                    <h2 class="home-amenities-title">สิ่งอำนวยความสะดวกมาตรฐานสากล</h2>
                    <p class="home-amenities-desc">มาตรฐานสนามระดับสากลเพื่อประสบการณ์การเล่นที่ดีที่สุด</p>
                </div>

                <div class="home-amenities-grid">
                    <div class="home-amenity-card">
                        <div class="home-amenity-icon-box"><i class="fas fa-layer-group"></i></div>
                        <h3 class="home-amenity-title">พื้นยางมาตรฐาน BWF 5 ชั้น</h3>
                        <p class="home-amenity-desc">ซับแรงกระแทก ปกป้องข้อต่อและหัวเข่า ยึดเกาะดีเยี่ยม ปลอดภัยทุกการเคลื่อนไหว</p>
                    </div>

                    <div class="home-amenity-card">
                        <div class="home-amenity-icon-box"><i class="fas fa-eye"></i></div>
                        <h3 class="home-amenity-title">ระบบไฟ LED Anti-Glare</h3>
                        <p class="home-amenity-desc">ตัดแสงสะท้อน สว่างสม่ำเสมอทั่วทั้งคอร์ท ไม่เมื่อยล้าสายตา มองเห็นลูกชัดเจน</p>
                    </div>

                    <div class="home-amenity-card">
                        <div class="home-amenity-icon-box"><i class="fas fa-wind"></i></div>
                        <h3 class="home-amenity-title">ระบบหมุนเวียนอากาศถ่ายเท</h3>
                        <p class="home-amenity-desc">โถงอาคารสูงโปร่ง พัดลมระบายอากาศแรงดันสูงรอบทิศทาง ไม่อับชื้น เล่นได้ต่อเนื่อง</p>
                    </div>

                    <div class="home-amenity-card">
                        <div class="home-amenity-icon-box"><i class="fas fa-shower"></i></div>
                        <h3 class="home-amenity-title">ห้องน้ำ & ห้องอาบน้ำ</h3>
                        <p class="home-amenity-desc">แยกสัดส่วนชาย-หญิง สะอาด ปลอดภัย พร้อมเครื่องทำน้ำอุ่นและล็อกเกอร์เก็บของ</p>
                    </div>

                    <div class="home-amenity-card">
                        <div class="home-amenity-icon-box"><i class="fas fa-parking"></i></div>
                        <h3 class="home-amenity-title">ที่จอดรถกว้างขวาง & CCTV</h3>
                        <p class="home-amenity-desc">รองรับรถยนต์และจักรยานยนต์มากกว่า 50 คัน พร้อมกล้องวงจรปิดดูแลตลอด 24 ชม.</p>
                    </div>
                </div>
            </div>
        </section>



        <!-- ============================================== -->
        <!-- 5. พันธมิตรของเรา (Our Partners - Matchday Style)-->
        <!-- ============================================== -->
        <section class="home-partners-section" id="partners">
            <div class="home-container">
                <div class="home-partners-header">
                    <h2 class="home-partners-title">แบรนด์และอุปกรณ์มาตรฐานสากล</h2>
                    <div class="home-title-accent-line"></div>
                    <p class="home-partners-desc">
                        คัดสรรอุปกรณ์และแบรนด์ชั้นนำระดับโลก เพื่อยกระดับมาตรฐานสนาม T.S. BADMINTON PATTANI
                    </p>
                </div>
            </div>

            <!-- แถบเลื่อนแบรนด์วนลูปไร้รอยต่อ (Infinite Seamless Marquee เข้าขวาออกซ้าย) -->
            <div class="home-marquee-viewport">
                <div class="home-marquee-track">
                    <!-- ชุดที่ 1 -->
                    <div class="home-marquee-group">
                        <div class="home-partner-item" title="YONEX">
                            <img src="assets/img/brands/yonex.png" alt="YONEX" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="VICTOR">
                            <img src="assets/img/brands/victor.png" alt="VICTOR" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="LI-NING">
                            <img src="assets/img/brands/lining.png" alt="LI-NING" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="MIZUNO">
                            <img src="assets/img/brands/mizuno.png" alt="MIZUNO" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="DUNLOP">
                            <img src="assets/img/brands/dunlop.png" alt="DUNLOP" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="ASICS">
                            <img src="assets/img/brands/asics.png" alt="ASICS" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="ASHAWAY">
                            <img src="assets/img/brands/ashaway.png" alt="ASHAWAY" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="T.S. PRO SHOP">
                            <img src="assets/img/brands/ts-proshop.png" alt="T.S. PRO SHOP" class="home-partner-img" loading="lazy">
                        </div>
                    </div>

                    <!-- ชุดที่ 2 (ทำซ้ำเพื่อต่อลูปแบบไร้รอยต่อ 100%) -->
                    <div class="home-marquee-group" aria-hidden="true">
                        <div class="home-partner-item" title="YONEX">
                            <img src="assets/img/brands/yonex.png" alt="YONEX" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="VICTOR">
                            <img src="assets/img/brands/victor.png" alt="VICTOR" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="LI-NING">
                            <img src="assets/img/brands/lining.png" alt="LI-NING" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="MIZUNO">
                            <img src="assets/img/brands/mizuno.png" alt="MIZUNO" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="DUNLOP">
                            <img src="assets/img/brands/dunlop.png" alt="DUNLOP" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="ASICS">
                            <img src="assets/img/brands/asics.png" alt="ASICS" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="ASHAWAY">
                            <img src="assets/img/brands/ashaway.png" alt="ASHAWAY" class="home-partner-img" loading="lazy">
                        </div>
                        <div class="home-partner-item" title="T.S. PRO SHOP">
                            <img src="assets/img/brands/ts-proshop.png" alt="T.S. PRO SHOP" class="home-partner-img" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================== -->
        <!-- 6. CTA ปิดท้าย (Closing Call to Action)         -->
        <!-- ============================================== -->
        <section class="home-cta-section">
            <div class="home-container">
                <div class="home-cta-card">
                    <h2 class="home-cta-title">พร้อมลงสนามแล้วหรือยัง?</h2>
                    <p class="home-cta-desc">เช็คคอร์ทว่างและจองสนามออนไลน์ได้ทันที ตลอด 24 ชั่วโมง</p>
                    <a href="booking.php" class="home-cta-btn">
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
