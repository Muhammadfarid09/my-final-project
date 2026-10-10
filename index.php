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
    
    <!-- แถบเมนูหลัก (Navbar) -->
    <?php include 'includes/navbar.php'; ?>

    <!-- ============================================== -->
    <!-- 1. Hero Section (ส่วนแบนเนอร์ต้อนรับหลัก) -->
    <!-- ============================================== -->
    <section class="landing-hero-section">
        <div class="hero-container">
            <div class="hero-badge-tag">
                <i class="fas fa-star"></i>
                <span>สนามแบดมินตันมาตรฐานสากล BWF อันดับ 1 ในปัตตานี</span>
            </div>

            <h1 class="hero-title">
                ยกระดับทุกการแข่งขัน <br>
                สัมผัสประสบการณ์ <span class="hero-title-gradient">สนามแบดมินตันระดับโปร</span>
            </h1>

            <p class="hero-subtitle">
                พื้นยางสังเคราะห์ 5 ชั้นเกรดแข่งขัน ระบบไฟ LED Anti-Glare ตัดแสงสะท้อนถนอมสายตา บรรยากาศโปร่งสบาย พร้อมระบบจองสนามและสะสมแต้มแลกของรางวัลแบบครบวงจร
            </p>

            <div class="hero-actions">
                <a href="booking.php" class="btn-hero-primary">
                    <i class="fas fa-calendar-check"></i>
                    <span>จองสนามทันที</span>
                    <i class="fas fa-arrow-right mr-6"></i>
                </a>
                <a href="#amenities" class="btn-hero-secondary">
                    <i class="fas fa-shield-alt"></i>
                    <span>ดูจุดเด่นสนาม & วิธีการจอง</span>
                </a>
            </div>

            <!-- แถบสถิติสำคัญด้านล่าง Hero -->
            <div class="hero-stats-strip">
                <div class="hero-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-th-large"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">10 คอร์ทมาตรฐาน</span>
                        <span class="stat-label">พื้นยาง BWF เกรดแข่งขัน</span>
                    </div>
                </div>

                <div class="hero-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">LED Anti-Glare</span>
                        <span class="stat-label">ตัดแสงสะท้อน สว่างสบายตา</span>
                    </div>
                </div>

                <div class="hero-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">เปิดบริการทุกวัน</span>
                        <span class="stat-label">09:00 - 24:00 น.</span>
                    </div>
                </div>

                <div class="hero-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">สะสมแต้มได้ทันที</span>
                        <span class="stat-label">แลกของรางวัล & ส่วนลด</span>
                    </div>
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
                    <span class="brand-badge brand-badge-yonex"><i class="fas fa-feather-alt text-accent"></i> YONEX</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-victor"><i class="fas fa-shield-alt text-danger"></i> VICTOR</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-lining"><i class="fas fa-bolt text-warning"></i> LI-NING</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-mizuno"><i class="fas fa-running text-accent"></i> MIZUNO</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-dunlop"><i class="fas fa-circle-notch text-success"></i> DUNLOP</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-ashaway"><i class="fas fa-certificate text-primary"></i> ASHAWAY</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-ts"><i class="fas fa-award text-warning"></i> T.S. PRO SHOP</span>
                </div>

                <!-- ชุดที่ 2 (ทำซ้ำเพื่อต่อลูปแบบไร้รอยต่อ Infinite Marquee) -->
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-yonex"><i class="fas fa-feather-alt text-accent"></i> YONEX</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-victor"><i class="fas fa-shield-alt text-danger"></i> VICTOR</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-lining"><i class="fas fa-bolt text-warning"></i> LI-NING</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-mizuno"><i class="fas fa-running text-accent"></i> MIZUNO</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-dunlop"><i class="fas fa-circle-notch text-success"></i> DUNLOP</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-ashaway"><i class="fas fa-certificate text-primary"></i> ASHAWAY</span>
                </div>
                <div class="marquee-item">
                    <span class="brand-badge brand-badge-ts"><i class="fas fa-award text-warning"></i> T.S. PRO SHOP</span>
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
            <a href="booking.php" class="btn-hero-primary">
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
