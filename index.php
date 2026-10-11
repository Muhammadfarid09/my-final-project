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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="page-body">
    
    <!-- ============================================== -->
    <!-- 1. แถบเมนูหลัก (Premium Black & Gold Navbar)   -->
    <!-- ============================================== -->
    <header class="navbar-dark-header">
        <div class="nav-dark-container">
            <!-- โลโก้สนาม -->
            <a href="index.php" class="nav-dark-brand">
                <i class="fas fa-shuttlecock text-gold"></i>
                <span>T.S. PATTANI</span>
            </a>
            
            <!-- เมนูนำทางตรงกลาง -->
            <ul class="nav-dark-menu" id="navMenu">
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> หน้าแรก</a></li>
                <li><a href="promotions.php"><i class="fas fa-bullhorn"></i> ข่าวสาร&โปรโมชัน</a></li>
                <li><a href="booking.php"><i class="fas fa-calendar-check"></i> จองสนาม</a></li>
                <li><a href="rewards.php"><i class="fas fa-gift"></i> แลกของรางวัล</a></li>
                <li><a href="chat.php"><i class="fas fa-comments"></i> ติดต่อเรา</a></li>
            </ul>

            <!-- ปุ่ม Actions ด้านขวา -->
            <div class="nav-dark-actions">
                <?php if (isset($_SESSION['member_id'])): ?>
                    <!-- กรณีสมาชิกเข้าสู่ระบบแล้ว -->
                    <div class="user-dropdown">
                        <button type="button" class="dropbtn-dark" onclick="toggleDropdown()">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($_SESSION['member_name']); ?></span>
                            <span class="badge-level level-<?php echo strtolower($member_level); ?>"><?php echo $member_level; ?></span>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-content dropdown-content-dark" id="userDropdown">
                            <div class="user-point-info-dark">
                                <span>คะแนนสะสม: <strong class="text-gold"><?php echo number_format($member_points); ?></strong> พอยท์</span>
                            </div>
                            <hr class="dropdown-divider-dark">
                            <a href="profile.php"><i class="fas fa-id-card"></i> โปรไฟล์ของฉัน</a>
                            <a href="booking_history.php"><i class="fas fa-history"></i> ประวัติการจอง</a>
                            <a href="actions/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                        </div>
                    </div>
                    <a href="booking.php" class="btn-nav-gold">
                        <i class="fas fa-calendar-plus"></i>
                        <span>จองสนาม</span>
                    </a>
                <?php else: ?>
                    <!-- กรณีผู้เยี่ยมชมทั่วไป (Guest) -->
                    <a href="register.php" class="btn-nav-ghost">
                        <i class="fas fa-user-plus"></i>
                        <span>สมัครสมาชิก</span>
                    </a>
                    <a href="login.php" class="btn-nav-gold">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>เข้าสู่ระบบ</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- ปุ่มเปิดเมนูสำหรับมือถือ (Hamburger Button) -->
            <button type="button" class="nav-dark-toggle" id="navDarkToggle" onclick="toggleMobileMenu()" aria-label="เปิดเมนูนำทาง">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- ============================================== -->
    <!-- 2. Hero Section & Key Highlights (Black & Gold) -->
    <!-- ============================================== -->
    <section class="hero-dark-section">
        <div class="hero-dark-container">
            
            <!-- ป้ายกำกับเกียรติยศ -->
            <div class="hero-dark-badge">
                <i class="fas fa-crown text-gold"></i>
                <span>สนามแบดมินตันมาตรฐาน BWF อันดับ 1 ในปัตตานี</span>
            </div>

            <!-- พาดหัวหลัก H1 คมชัด -->
            <h1 class="hero-dark-title">
                ยกระดับทุกการแข่งขัน <br>
                สัมผัสประสบการณ์ <span class="text-gold-highlight">สนามแบดมินตันระดับพรีเมียม</span>
            </h1>

            <!-- สโลแกน / คำอธิบาย -->
            <p class="hero-dark-subtitle">
                คอร์ทพื้นยางสังเคราะห์ 5 ชั้นซับแรงกระแทก ระบบไฟ LED Anti-Glare ตัดแสงสะท้อนถนอมสายตา พร้อมระบบจองคิวออนไลน์เรียลไทม์ และสะสมแต้มแลกรับของรางวัลแบบครบวงจร
            </p>

            <!-- ปุ่มคู่ Action (Dual CTAs) -->
            <div class="hero-dark-actions">
                <a href="booking.php" class="btn-dark-primary-gold">
                    <i class="fas fa-calendar-check"></i>
                    <span>จองสนามทันที</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <?php if (isset($_SESSION['member_id'])): ?>
                    <a href="booking_history.php" class="btn-dark-outline">
                        <i class="fas fa-history"></i>
                        <span>ประวัติการจองของฉัน</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-dark-outline">
                        <i class="fas fa-user-lock"></i>
                        <span>เข้าสู่ระบบสมาชิก</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- แดชบอร์ดสถิติสนาม 4 การ์ด (Stadium Live Stats Dashboard) -->
            <div class="hero-stats-dashboard">
                <div class="stat-dashboard-card">
                    <div class="stat-card-top">
                        <span class="stat-number"><?php echo htmlspecialchars($court_count); ?></span>
                        <div class="stat-badge-icon"><i class="fas fa-layer-group"></i></div>
                    </div>
                    <h3 class="stat-card-title">คอร์ทมาตรฐาน BWF</h3>
                    <p class="stat-card-sub">พื้นยางสังเคราะห์ 5 ชั้น · ไฟ LED Anti-Glare</p>
                </div>

                <div class="stat-dashboard-card">
                    <div class="stat-card-top">
                        <span class="stat-number"><?php echo htmlspecialchars($booking_stat_display); ?></span>
                        <div class="stat-badge-icon"><i class="fas fa-calendar-check"></i></div>
                    </div>
                    <h3 class="stat-card-title">แมตช์ที่จองสำเร็จ</h3>
                    <p class="stat-card-sub">ระบบจองอัตโนมัติเรียลไทม์ · ล็อกคอร์ท 100%</p>
                </div>

                <div class="stat-dashboard-card">
                    <div class="stat-card-top">
                        <span class="stat-number">100%</span>
                        <div class="stat-badge-icon"><i class="fas fa-star"></i></div>
                    </div>
                    <h3 class="stat-card-title">มาตรฐานความพึงพอใจ</h3>
                    <p class="stat-card-sub">สนามอันดับ 1 ที่นักกีฬาไว้วางใจในปัตตานี</p>
                </div>

                <div class="stat-dashboard-card">
                    <div class="stat-card-top">
                        <span class="stat-number">24 ชม.</span>
                        <div class="stat-badge-icon"><i class="fas fa-clock"></i></div>
                    </div>
                    <h3 class="stat-card-title">ระบบบริการครบวงจร</h3>
                    <p class="stat-card-sub">จองคิวออนไลน์ 24 ชม. · Pro Shop · ห้องอาบน้ำ</p>
                </div>
            </div>

        </div>
    </section>

    <!-- ============================================== -->
    <!-- 2. Brand & Sponsor Marquee (Infinite CSS Slider) -->
    <!-- ============================================== -->
    <section class="brand-marquee-section">
        <div class="marquee-header">
            <h4 class="marquee-heading">พันธมิตรและอุปกรณ์กีฬาที่ได้รับความไว้วางใจ</h4>
        </div>

        <div class="marquee-container">
            <div class="marquee-track">
                <!-- ชุดที่ 1 -->
                <div class="marquee-item">
                    <div class="brand-banner-card" title="YONEX">
                        <img src="assets/img/brands/yonex.png" alt="YONEX" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="VICTOR">
                        <img src="assets/img/brands/victor.png" alt="VICTOR" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="LI-NING">
                        <img src="assets/img/brands/lining.png" alt="LI-NING" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="MIZUNO">
                        <img src="assets/img/brands/mizuno.png" alt="MIZUNO" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="DUNLOP">
                        <img src="assets/img/brands/dunlop.png" alt="DUNLOP" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="ASICS">
                        <img src="assets/img/brands/asics.png" alt="ASICS" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="T.S. PRO SHOP">
                        <img src="assets/img/brands/ts-proshop.png" alt="T.S. PRO SHOP" class="brand-banner-img" loading="lazy">
                    </div>
                </div>

                <!-- ชุดที่ 2 (ทำซ้ำเพื่อต่อลูปแบบไร้รอยต่อ Infinite Marquee) -->
                <div class="marquee-item">
                    <div class="brand-banner-card" title="YONEX">
                        <img src="assets/img/brands/yonex.png" alt="YONEX" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="VICTOR">
                        <img src="assets/img/brands/victor.png" alt="VICTOR" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="LI-NING">
                        <img src="assets/img/brands/lining.png" alt="LI-NING" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="MIZUNO">
                        <img src="assets/img/brands/mizuno.png" alt="MIZUNO" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="DUNLOP">
                        <img src="assets/img/brands/dunlop.png" alt="DUNLOP" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="ASICS">
                        <img src="assets/img/brands/asics.png" alt="ASICS" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
                <div class="marquee-item">
                    <div class="brand-banner-card" title="T.S. PRO SHOP">
                        <img src="assets/img/brands/ts-proshop.png" alt="T.S. PRO SHOP" class="brand-banner-img" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================== -->
    <!-- 3. Top Players Leaderboard (Podium Ranking Top 3) -->
    <!-- ============================================== -->
    <section class="leaderboard-section">
        <div class="section-header-center">
            <span class="section-badge"><i class="fas fa-medal"></i> ทำเนียบเกียรติยศ</span>
            <h2 class="section-main-title">Top Players Leaderboard</h2>
            <p class="section-sub-desc">
                3 อันดับสมาชิกผู้เล่นยอดเยี่ยมที่ไว้วางใจและเข้าใช้บริการสนาม T.S. Pattani สูงสุด
            </p>
        </div>

        <div class="podium-container">
            <!-- อันดับ 2: เหรียญเงิน (Silver - ฝั่งซ้ายบน Desktop) -->
            <?php if ($rank2_player): ?>
            <div class="podium-card podium-rank-2">
                <div class="podium-avatar-wrap">
                    <i class="fas fa-user-ninja"></i>
                    <span class="podium-rank-badge">2</span>
                </div>
                <h3 class="podium-player-name" title="<?php echo htmlspecialchars($rank2_player['member_name']); ?>">
                    <?php echo htmlspecialchars($rank2_player['member_name']); ?>
                </h3>
                <div class="podium-phone-masked">
                    <i class="fas fa-phone-alt mr-6"></i><?php echo htmlspecialchars(mask_phone_number($rank2_player['member_phone'])); ?>
                </div>
                <div class="podium-meta-row">
                    <span class="podium-level-pill level-<?php echo strtolower($rank2_player['member_level']); ?>">
                        <?php echo htmlspecialchars($rank2_player['member_level']); ?>
                    </span>
                </div>
                <div class="podium-pedestal">
                    <span class="pedestal-count"><?php echo intval($rank2_player['booking_count']); ?> แมตช์</span>
                    <span class="pedestal-label">รองชนะเลิศอันดับ 1</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- อันดับ 1: เหรียญทอง (Gold - ตรงกลางยกสูงบน Desktop) -->
            <?php if ($rank1_player): ?>
            <div class="podium-card podium-rank-1">
                <div class="podium-crown">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="podium-avatar-wrap">
                    <i class="fas fa-user-astronaut"></i>
                    <span class="podium-rank-badge">1</span>
                </div>
                <h3 class="podium-player-name" title="<?php echo htmlspecialchars($rank1_player['member_name']); ?>">
                    <?php echo htmlspecialchars($rank1_player['member_name']); ?>
                </h3>
                <div class="podium-phone-masked">
                    <i class="fas fa-phone-alt mr-6"></i><?php echo htmlspecialchars(mask_phone_number($rank1_player['member_phone'])); ?>
                </div>
                <div class="podium-meta-row">
                    <span class="podium-level-pill level-<?php echo strtolower($rank1_player['member_level']); ?>">
                        <?php echo htmlspecialchars($rank1_player['member_level']); ?>
                    </span>
                </div>
                <div class="podium-pedestal">
                    <span class="pedestal-count"><?php echo intval($rank1_player['booking_count']); ?> แมตช์</span>
                    <span class="pedestal-label">จองสำเร็จสูงสุดอันดับ 1</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- อันดับ 3: เหรียญทองแดง (Bronze - ฝั่งขวาบน Desktop) -->
            <?php if ($rank3_player): ?>
            <div class="podium-card podium-rank-3">
                <div class="podium-avatar-wrap">
                    <i class="fas fa-user-check"></i>
                    <span class="podium-rank-badge">3</span>
                </div>
                <h3 class="podium-player-name" title="<?php echo htmlspecialchars($rank3_player['member_name']); ?>">
                    <?php echo htmlspecialchars($rank3_player['member_name']); ?>
                </h3>
                <div class="podium-phone-masked">
                    <i class="fas fa-phone-alt mr-6"></i><?php echo htmlspecialchars(mask_phone_number($rank3_player['member_phone'])); ?>
                </div>
                <div class="podium-meta-row">
                    <span class="podium-level-pill level-<?php echo strtolower($rank3_player['member_level']); ?>">
                        <?php echo htmlspecialchars($rank3_player['member_level']); ?>
                    </span>
                </div>
                <div class="podium-pedestal">
                    <span class="pedestal-count"><?php echo intval($rank3_player['booking_count']); ?> แมตช์</span>
                    <span class="pedestal-label">รองชนะเลิศอันดับ 2</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============================================== -->
    <!-- 4. Court Highlights & Amenities (จุดเด่นสนาม BWF) -->
    <!-- ============================================== -->
    <section class="amenities-section" id="amenities">
        <div class="section-header-center">
            <span class="section-badge"><i class="fas fa-check-circle"></i> มาตรฐานระดับสากล</span>
            <h2 class="section-main-title">จุดเด่นและสิ่งอำนวยความสะดวกครบครัน</h2>
            <p class="section-sub-desc">
                ออกแบบมาเพื่อนักแบดมินตันทุกระดับ ตั้งแต่มือสมัครเล่น ก๊วนก๊วนเพื่อน ไปจนถึงนักกีฬาแข่งขัน
            </p>
        </div>

        <div class="amenities-grid">
            <div class="amenity-card">
                <div class="amenity-icon-wrap">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h3 class="amenity-title">พื้นยางมาตรฐาน BWF 5 ชั้น</h3>
                <p class="amenity-desc">
                    พื้นยางสังเคราะห์หนานุ่ม ซับแรงกระแทกจากทุกจังหวะกระโดด ช่วยปกป้องข้อต่อและหัวเข่า ยึดเกาะพื้นผิวดีเยี่ยม ปลอดภัยไร้กังวล
                </p>
            </div>

            <div class="amenity-card">
                <div class="amenity-icon-wrap">
                    <i class="fas fa-eye"></i>
                </div>
                <h3 class="amenity-title">ระบบไฟ LED Anti-Glare</h3>
                <p class="amenity-desc">
                    ออกแบบทิศทางแสงสว่างให้ตัดแสงสะท้อนเข้าตา มองเห็นวิถีลูกขนไก่ได้คมชัดทุกลูก สว่างสม่ำเสมอทั่วทั้งคอร์ท ไม่เมื่อยล้าสายตา
                </p>
            </div>

            <div class="amenity-card">
                <div class="amenity-icon-wrap">
                    <i class="fas fa-wind"></i>
                </div>
                <h3 class="amenity-title">ระบบหมุนเวียนอากาศถ่ายเท</h3>
                <p class="amenity-desc">
                    โถงอาคารหลังคาสูงโปร่ง ติดตั้งพัดลมระบายอากาศแรงดันสูงรอบทิศทาง อากาศถ่ายเทสะดวก ไม่อับชื้น เล่นได้สบายยาวนาน
                </p>
            </div>

            <div class="amenity-card">
                <div class="amenity-icon-wrap">
                    <i class="fas fa-shower"></i>
                </div>
                <h3 class="amenity-title">ห้องน้ำ & ห้องอาบน้ำแยกสัดส่วน</h3>
                <p class="amenity-desc">
                    ห้องน้ำและห้องอาบน้ำแยกชาย-หญิง สะอาด ปลอดภัย ดูแลความสะอาดสม่ำเสมอ พร้อมเครื่องทำน้ำอุ่นและล็อกเกอร์เก็บสัมภาระ
                </p>
            </div>

            <div class="amenity-card">
                <div class="amenity-icon-wrap">
                    <i class="fas fa-parking"></i>
                </div>
                <h3 class="amenity-title">ที่จอดรถกว้างขวาง & CCTV</h3>
                <p class="amenity-desc">
                    ลานจอดรถยนต์และจักรยานยนต์รองรับมากกว่า 50 คัน เข้า-ออกสะดวกสบาย พร้อมระบบกล้องวงจรปิด CCTV ดูแลความปลอดภัยตลอด 24 ชม.
                </p>
            </div>

            <div class="amenity-card">
                <div class="amenity-icon-wrap">
                    <i class="fas fa-store"></i>
                </div>
                <h3 class="amenity-title">Pro Shop & จุดบริการเครื่องดื่ม</h3>
                <p class="amenity-desc">
                    บริการขึ้นเอ็นไม้แบดโดยช่างผู้ชำนาญ จำหน่ายลูกแบดมินตัน กริปพันด้าม เครื่องดื่มเย็นสดชื่น พร้อมบริการเช่าไม้และรองเท้า
                </p>
            </div>
        </div>
    </section>

    <!-- ============================================== -->
    <!-- 5. How It Works (3 ขั้นตอนการจองง่ายๆ) -->
    <!-- ============================================== -->
    <section class="how-it-works-section" id="how-it-works">
        <div class="section-header-center">
            <span class="section-badge"><i class="fas fa-bolt"></i> สะดวก รวดเร็ว</span>
            <h2 class="section-main-title">3 ขั้นตอนง่ายๆ ในการจองสนาม</h2>
            <p class="section-sub-desc">
                ระบบจองออนไลน์ที่รวดเร็ว ล็อกเวลาได้แน่นอน ไม่ต้องรอคิวหน้าสนาม
            </p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <span class="step-num-pill">ขั้นตอนที่ 1</span>
                <div class="step-icon-circle">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 class="step-title">เลือกวันและเวลา</h3>
                <p class="step-desc">
                    เช็คตารางสนามว่างแบบ Real-time ล่วงหน้าได้ทันที พร้อมดูอัตราค่าบริการตามประเภทวันปกติและวันโปรโมชัน
                </p>
            </div>

            <div class="step-card">
                <span class="step-num-pill">ขั้นตอนที่ 2</span>
                <div class="step-icon-circle">
                    <i class="fas fa-table-tennis"></i>
                </div>
                <h3 class="step-title">เลือกคอร์ท & อุปกรณ์เสริม</h3>
                <p class="step-desc">
                    เลือกสนามที่ชอบ และสามารถสั่งเครื่องดื่มเย็น หรือเลือกเช่าไม้แบดมินตันและรองเท้าเพิ่มได้ในที่เดียว
                </p>
            </div>

            <div class="step-card">
                <span class="step-num-pill">ขั้นตอนที่ 3</span>
                <div class="step-icon-circle">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h3 class="step-title">สแกนจ่ายรับคิวทันที</h3>
                <p class="step-desc">
                    โอนเงินชำระผ่าน QR Code พร้อมแนบสลิป ระบบจะล็อกสนามให้ท่านเป็นเวลา 15 นาที และพร้อมเข้าเล่นได้ทันที
                </p>
            </div>
        </div>

        <div class="steps-cta-bar">
            <a href="booking.php" class="btn-dark-primary-gold">
                <i class="fas fa-play-circle"></i>
                <span>ทดลองจองสนามตอนนี้</span>
                <i class="fas fa-arrow-right mr-6"></i>
            </a>
        </div>
    </section>

    <!-- ============================================== -->
    <!-- 6. News & Announcements (ข่าวสารและกิจกรรมล่าสุด) -->
    <!-- ============================================== -->
    <section class="amenities-section">
        <div class="section-header-center">
            <span class="section-badge"><i class="fas fa-bullhorn"></i> ประกาศสำคัญ</span>
            <h2 class="section-main-title">ข่าวสารและโปรโมชันล่าสุด</h2>
            <p class="section-sub-desc">
                ติดตามข่าวสารการแข่งขัน กิจกรรมพิเศษ และโปรโมชันสุดคุ้มจากทางสนาม
            </p>
        </div>

        <div class="news-container">
            <?php if (count($news_list) > 0): ?>
                <?php foreach ($news_list as $news): ?>
                    <div class="news-card">
                        <?php if (!empty($news['news_image'])): ?>
                            <img src="uploads/news/<?php echo htmlspecialchars($news['news_image']); ?>" alt="News Image" class="news-img">
                        <?php else: ?>
                            <div class="news-img-placeholder">
                                <i class="fas fa-image fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="news-content">
                            <div class="news-date">
                                <i class="far fa-clock"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($news['news_published_at'])); ?> น.
                            </div>
                            <h3 class="news-title"><?php echo htmlspecialchars($news['news_title']); ?></h3>
                            <p class="news-desc"><?php echo nl2br(htmlspecialchars($news['news_content'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="news-empty-state">
                    <i class="fas fa-info-circle fa-3x news-empty-icon"></i><br>
                    ยังไม่มีข่าวสารหรือประกาศในขณะนี้
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============================================== -->
    <!-- 7. Location, Hours & Contact (ที่ตั้งและเวลาเปิด-ปิด) -->
    <!-- ============================================== -->
    <section class="location-hours-section">
        <div class="section-header-center">
            <span class="section-badge"><i class="fas fa-map-marked-alt"></i> การเดินทาง & ติดต่อ</span>
            <h2 class="section-main-title">เวลาเปิดทำการและที่ตั้งสนาม</h2>
            <p class="section-sub-desc">
                ตั้งอยู่ใจกลางเมืองปัตตานี เดินทางสะดวก พร้อมสิ่งอำนวยความสะดวกครบครัน
            </p>
        </div>

        <div class="location-grid">
            <!-- กล่องเวลาเปิด-ปิดและช่องทางติดต่อ -->
            <div class="info-card">
                <div>
                    <h3 class="info-card-title">
                        <i class="fas fa-clock text-accent"></i> เวลาเปิดให้บริการ
                    </h3>
                    
                    <div class="schedule-table">
                        <div class="schedule-row">
                            <span class="schedule-day">วันจันทร์ - วันศุกร์</span>
                            <span class="schedule-time">09:00 - 24:00 น.</span>
                        </div>
                        <div class="schedule-row">
                            <span class="schedule-day">วันเสาร์ - วันอาทิตย์</span>
                            <span class="schedule-time">08:00 - 24:00 น.</span>
                        </div>
                        <div class="schedule-row">
                            <span class="schedule-day">วันหยุดนักขัตฤกษ์</span>
                            <span class="schedule-time">เปิดให้บริการตามปกติ</span>
                        </div>
                    </div>

                    <h3 class="info-card-title mt-20">
                        <i class="fas fa-headset text-accent"></i> ช่องทางการติดต่อ
                    </h3>
                    <div class="contact-chips-grid">
                        <a href="tel:0812345678" class="contact-chip-item">
                            <i class="fas fa-phone-alt text-success"></i> 081-234-5678
                        </a>
                        <a href="https://line.me" target="_blank" rel="noopener noreferrer" class="contact-chip-item">
                            <i class="fab fa-line text-success"></i> @tspattani
                        </a>
                        <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="contact-chip-item">
                            <i class="fab fa-facebook-f text-accent"></i> T.S. Pattani
                        </a>
                        <a href="chat.php" class="contact-chip-item">
                            <i class="fas fa-comment-dots text-primary"></i> แชทกับเจ้าหน้าที่
                        </a>
                    </div>
                </div>
            </div>

            <!-- กล่องแผนที่และการเดินทาง -->
            <div class="info-card">
                <div>
                    <h3 class="info-card-title">
                        <i class="fas fa-map-pin text-danger"></i> แผนที่และการเดินทาง
                    </h3>
                    <p class="footer-desc mb-16">
                        <strong>ที่อยู่:</strong> 123 ถนนเจริญประดิษฐ์ ตำบลรูสะมิแล อำเภอเมือง จังหวัดปัตตานี 94000 (ใกล้กับมหาวิทยาลัยสงขลานครินทร์ วิทยาเขตปัตตานี)
                    </p>
                    
                    <div class="map-visual-box">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.916853248356!2d101.24072387588383!3d6.866038419385521!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31b269588b3f63e3%3A0x6b0983199c0957b!2sPattani%20Stadium!5e0!3m2!1sen!2sth!4v1700000000000!5m2!1sen!2sth" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade"
                            title="T.S. Pattani Badminton Location Map">
                        </iframe>
                    </div>

                    <a href="https://maps.google.com" target="_blank" rel="noopener noreferrer" class="btn-location-action">
                        <i class="fas fa-directions"></i> เปิดแอปพลิเคชัน Google Maps นำทาง
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================== -->
    <!-- 8. Site Footer (ส่วนท้ายของเว็บไซต์) -->
    <!-- ============================================== -->
    <?php include 'includes/footer.php'; ?>

    <!-- เรียกใช้ JavaScript ส่วนกลาง -->
    <script src="assets/js/member.js?v=<?php echo filemtime('assets/js/member.js'); ?>"></script>
</body>
</html>
