<?php
session_start();
// เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล
require_once 'config/config.php';

$is_guest = !isset($_SESSION['member_id']);
$upcoming_booking = null;
$total_active_courts = 6;

// ดึงข้อมูลข่าวสาร 3 อันดับล่าสุดจากฐานข้อมูลมาแสดงผล (ตามขอบเขต 1.3.9)
try {
    $stmt = $conn->query("SELECT * FROM NEWS ORDER BY news_published_at DESC LIMIT 3");
    $news_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $news_list = [];
}

// หากเป็นสมาชิกที่เข้าสู่ระบบแล้ว ให้ดึงข้อมูลการจองถัดไปที่กำลังจะมาถึง
if (!$is_guest) {
    try {
        $stmt_up = $conn->prepare("
            SELECT b.*, c.court_name 
            FROM BOOKING b 
            JOIN COURT c ON b.court_id = c.court_id 
            WHERE b.member_id = :mid 
              AND b.booking_status IN ('จองแล้ว', 'รอตรวจสอบ')
              AND (b.booking_date > CURDATE() OR (b.booking_date = CURDATE() AND b.booking_end_time >= CURTIME()))
            ORDER BY b.booking_date ASC, b.booking_start_time ASC 
            LIMIT 1
        ");
        $stmt_up->execute([':mid' => $_SESSION['member_id']]);
        $upcoming_booking = $stmt_up->fetch(PDO::FETCH_ASSOC);

        // ดึงแต้มและระดับสมาชิกสำหรับแสดงในหัวการ์ด
        $stmt_pt = $conn->prepare("SELECT point_balance, member_level FROM Point WHERE member_id = :mid");
        $stmt_pt->execute([':mid' => $_SESSION['member_id']]);
        $member_point_data = $stmt_pt->fetch(PDO::FETCH_ASSOC);
        $user_points = $member_point_data['point_balance'] ?? 0;
        $user_level = $member_point_data['member_level'] ?? 'Bronze';

    } catch(PDOException $e) {
        $upcoming_booking = null;
        $user_points = 0;
        $user_level = 'Bronze';
    }
}

// ตรวจสอบจำนวนสนามที่เปิดให้บริการปกติ
try {
    $stmt_c = $conn->query("SELECT COUNT(*) FROM COURT WHERE court_status = 'ปกติ'");
    $total_active_courts = $stmt_c->fetchColumn() ?: 6;
} catch(Exception $e) {
    $total_active_courts = 6;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.2">
    <link rel="stylesheet" href="assets/css/style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="<?php echo $is_guest ? 'is-guest' : 'page-body'; ?>">
    
    <!-- แถบเมนูด้านบน (Navbar) -->
    <?php include 'includes/navbar.php'; ?>

    <?php if ($is_guest): ?>
        <!-- ============================================== -->
        <!-- 1. LANDING PAGE สำหรับผู้เยี่ยมชม (Guest View)  -->
        <!-- ============================================== -->
        <section class="guest-hero-section">
            <div class="guest-badge-pill">
                <i class="fas fa-certificate"></i> สนามแบดมินตันมาตรฐานระดับแข่งขัน • ปัตตานี
            </div>
            <h1 class="guest-hero-title">
                T.S. Pattani Badminton Club
            </h1>
            <p class="guest-hero-desc">
                สนามแบดมินตันพื้นยางพรีเมียมรองรับแรงกระแทก ระบบไฟมาตรฐาน BWF พร้อมระบบจองสนามออนไลน์ สะสมแต้มแลกของรางวัล และบริการเช่าอุปกรณ์ครบวงจร
            </p>
            <div class="guest-hero-actions">
                <a href="login.php" class="btn-guest-primary">
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบเพื่อเริ่มจองสนาม
                </a>
                <a href="register.php" class="btn-guest-outline">
                    <i class="fas fa-user-plus"></i> สมัครสมาชิกใหม่
                </a>
            </div>
        </section>

        <!-- ไฮไลต์สิ่งอำนวยความสะดวก 4 การ์ด -->
        <div class="facility-section-wrapper">
            <div class="facility-grid">
                <div class="facility-card">
                    <div class="facility-icon-box">
                        <i class="fas fa-vector-square"></i>
                    </div>
                    <div>
                        <div class="facility-title">6 สนามมาตรฐาน BWF</div>
                        <div class="facility-desc">พื้นยางเกรดพรีเมียม ช่วยลดแรงกระแทกและถนอมข้อต่อ</div>
                    </div>
                </div>
                <div class="facility-card">
                    <div class="facility-icon-box">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div>
                        <div class="facility-title">ระบบไฟ LED ถนอมสายตา</div>
                        <div class="facility-desc">แสงสว่างครอบคลุมทุกคอร์ท ปราศจากแสงสะท้อนรบกวน</div>
                    </div>
                </div>
                <div class="facility-card">
                    <div class="facility-icon-box">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div>
                        <div class="facility-title">เช่าอุปกรณ์ & จำหน่ายสินค้า</div>
                        <div class="facility-desc">มีไม้แบด รองเท้า ลูกขนไก่ และเครื่องดื่มเย็นพร้อมบริการ</div>
                    </div>
                </div>
                <div class="facility-card">
                    <div class="facility-icon-box">
                        <i class="fas fa-award"></i>
                    </div>
                    <div>
                        <div class="facility-title">สะสมแต้มแลกของรางวัล</div>
                        <div class="facility-desc">ทุกบาทที่ใช้จ่ายสะสมแต้มพอยท์เพื่อรับชั่วโมงฟรีและสินค้า</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- กล่องสรุปอัตราค่าบริการและเวลาเปิดทำการ -->
        <div class="rates-preview-wrapper">
            <div class="rates-card">
                <div class="rates-header">
                    <div class="rates-header-title">
                        <i class="fas fa-clock text-success"></i> เวลาเปิดทำการและอัตราค่าบริการ
                    </div>
                    <div class="rates-header-time">
                        <i class="fas fa-door-open"></i> เปิดทุกวัน 08:00 - 22:00 น.
                    </div>
                </div>
                <div class="rates-row-grid">
                    <div class="rate-item-box">
                        <div class="rate-item-type">วันธรรมดา (จ., พ., ศ.)</div>
                        <div class="rate-item-price">180 <span class="rate-item-unit">฿/ชม.</span></div>
                    </div>
                    <div class="rate-item-box">
                        <div class="rate-item-type">วันโปรโมชัน (อ., พฤ.)</div>
                        <div class="rate-item-price">120 <span class="rate-item-unit">฿/ชม.</span></div>
                    </div>
                    <div class="rate-item-box">
                        <div class="rate-item-type">วันหยุดสุดสัปดาห์ (ส.-อา.)</div>
                        <div class="rate-item-price">200 <span class="rate-item-unit">฿/ชม.</span></div>
                    </div>
                    <div class="rate-item-box">
                        <div class="rate-item-type">ช่วงเวลาพีก (17:00-22:00)</div>
                        <div class="rate-item-price">+20-40 <span class="rate-item-unit">฿/ชม.</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Floating CTA Bar สำหรับผู้เยี่ยมชมบนสมาร์ทโฟน -->
        <div class="guest-sticky-bar">
            <div class="guest-sticky-text">
                <i class="fas fa-shuttlecock text-warning"></i> พร้อมลงสนามหรือยัง?
            </div>
            <div class="guest-sticky-btn-group">
                <a href="login.php" class="btn-sticky-login"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
                <a href="register.php" class="btn-sticky-reg">สมัครสมาชิก</a>
            </div>
        </div>

    <?php else: ?>
        <!-- ============================================== -->
        <!-- 2. MEMBER HOME DASHBOARD (สมาชิกที่ล็อกอินแล้ว) -->
        <!-- ============================================== -->
        <div class="member-home-wrapper">
            
            <!-- แถบต้อนรับข้อมูลสมาชิก -->
            <div class="member-header-strip">
                <div class="member-header-user">
                    <div class="member-avatar-circle">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="member-welcome-text">ยินดีต้อนรับกลับมา</div>
                        <div class="member-welcome-name"><?php echo htmlspecialchars($_SESSION['member_name']); ?></div>
                    </div>
                </div>
                <div class="member-status-pills">
                    <span class="member-tier-pill level-<?php echo strtolower($user_level); ?>">
                        <i class="fas fa-medal"></i> <?php echo $user_level; ?>
                    </span>
                    <a href="rewards.php" class="member-point-pill">
                        <i class="fas fa-star text-warning"></i> <?php echo number_format($user_points); ?> พอยท์
                    </a>
                </div>
            </div>

            <!-- การ์ดตั๋วการจองสนามถัดไป (Upcoming Game Ticket Style) -->
            <div class="upcoming-ticket-box">
                <?php if ($upcoming_booking): ?>
                    <div class="upcoming-ticket-card">
                        <div class="ticket-badge-row">
                            <span class="ticket-type-label">
                                <i class="fas fa-ticket-alt"></i> นัดหมายถัดไปของคุณ
                            </span>
                            <?php if ($upcoming_booking['booking_status'] === 'จองแล้ว'): ?>
                                <span class="ticket-status-badge ticket-status-booked">
                                    <i class="fas fa-check-circle"></i> ยืนยันการจองแล้ว
                                </span>
                            <?php else: ?>
                                <span class="ticket-status-badge ticket-status-pending">
                                    <i class="fas fa-clock"></i> รอตรวจสอบสลิป
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="ticket-court-title">
                            <i class="fas fa-shuttlecock text-success"></i> <?php echo htmlspecialchars($upcoming_booking['court_name']); ?>
                        </div>
                        <div class="ticket-details-grid">
                            <div class="ticket-detail-item">
                                <div class="ticket-detail-label">วันที่เล่น</div>
                                <div class="ticket-detail-val"><?php echo date('d/m/Y', strtotime($upcoming_booking['booking_date'])); ?></div>
                            </div>
                            <div class="ticket-detail-item">
                                <div class="ticket-detail-label">ช่วงเวลา</div>
                                <div class="ticket-detail-val"><?php echo substr($upcoming_booking['booking_start_time'], 0, 5); ?> - <?php echo substr($upcoming_booking['booking_end_time'], 0, 5); ?> น.</div>
                            </div>
                            <div class="ticket-detail-item">
                                <div class="ticket-detail-label">รหัสการจอง</div>
                                <div class="ticket-detail-val">#<?php echo $upcoming_booking['booking_id']; ?></div>
                            </div>
                        </div>
                        <div class="ticket-dashed-row"></div>
                        <div class="ticket-action-row">
                            <div class="ticket-price-total">
                                ยอดรวม: <strong><?php echo number_format($upcoming_booking['booking_total_price']); ?> ฿</strong>
                            </div>
                            <div>
                                <a href="booking_history.php" class="btn-ticket-view">
                                    ดูรายละเอียด <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-upcoming-card">
                        <i class="fas fa-calendar-plus fa-3x no-upcoming-icon"></i>
                        <div class="no-upcoming-title">ยังไม่มีนัดหมายการจองสนามเร็วๆ นี้</div>
                        <div class="no-upcoming-desc">เลือกวันและเวลาที่สะดวก แล้วเริ่มจองสนามเพื่อออกกำลังกายได้ทันที</div>
                        <a href="booking.php" class="btn-book-quick">
                            <i class="fas fa-calendar-check"></i> จองสนามตอนนี้เลย
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- กริด 4 เมนูลัด (Quick Action 4-Grid) -->
            <div class="quick-action-box">
                <div class="quick-action-grid">
                    <a href="booking.php" class="quick-action-btn">
                        <div class="quick-icon-round icon-booking">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span class="quick-action-label">จองสนาม</span>
                    </a>
                    <a href="rewards.php" class="quick-action-btn">
                        <div class="quick-icon-round icon-rewards">
                            <i class="fas fa-gift"></i>
                        </div>
                        <span class="quick-action-label">แลกรางวัล</span>
                    </a>
                    <a href="booking_history.php" class="quick-action-btn">
                        <div class="quick-icon-round icon-history">
                            <i class="fas fa-history"></i>
                        </div>
                        <span class="quick-action-label">ประวัติการจอง</span>
                    </a>
                    <a href="chat.php" class="quick-action-btn">
                        <div class="quick-icon-round icon-chat">
                            <i class="fas fa-comments"></i>
                        </div>
                        <span class="quick-action-label">ติดต่อเรา</span>
                    </a>
                </div>
            </div>

            <!-- แถบสถานะสนามเปิดให้บริการ Real-time -->
            <div class="court-status-strip">
                <div class="court-status-pill">
                    <span class="court-status-dot"></span>
                    สนามเปิดให้บริการปกติ <?php echo $total_active_courts; ?> คอร์ท
                </div>
                <div>
                    <i class="far fa-clock"></i> เวลาเปิด: 08:00 - 22:00 น.
                </div>
            </div>

        </div>
    <?php endif; ?>

    <!-- ส่วนแสดงข่าวสารและประชาสัมพันธ์ (แสดงผลทั้ง Guest และ Member) -->
    <div class="news-section-wrapper">
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
                                <?php echo date('d/m/Y H:i', strtotime($news['news_published_at'])); ?> น.
                            </div>
                            <h3 class="news-title"><?php echo htmlspecialchars($news['news_title']); ?></h3>
                            <p class="news-desc"><?php echo nl2br(htmlspecialchars($news['news_content'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- กรณีไม่มีข่าวสารในระบบ -->
                <div class="news-empty-state">
                    <i class="fas fa-info-circle fa-3x news-empty-icon"></i><br>
                    ยังไม่มีข่าวสารหรือประกาศในขณะนี้
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- เรียกใช้ JavaScript สำหรับเมนูและการแจ้งเตือน -->
    <script src="assets/js/member.js?v=2.1"></script>
</body>
</html>
