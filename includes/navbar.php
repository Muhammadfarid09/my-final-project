<?php
// ตรวจจับชื่อไฟล์ปัจจุบันสำหรับ Active Navigation Item แบบอัตโนมัติ
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$is_home = in_array($current_page, ['index.php', '']);
$is_promotions = in_array($current_page, ['promotions.php', 'news.php', 'news_detail.php']);
$is_booking = in_array($current_page, ['booking.php', 'payment.php']);
$is_rewards = in_array($current_page, ['rewards.php', 'reward_history.php']);
$is_chat = in_array($current_page, ['chat.php', 'contact.php']);

// ดึงข้อมูลคะแนนสะสมและระดับสมาชิกของลูกค้าที่ล็อกอินอยู่ (ตามขอบเขตข้อ 1.3.3)
if (!isset($member_points) || !isset($member_level)) {
    $member_points = 0;
    $member_level = 'Bronze';

    if (isset($_SESSION['member_id']) && isset($conn)) {
        try {
            $stmt_nav = $conn->prepare("SELECT point_balance, member_level FROM Point WHERE member_id = :mid");
            $stmt_nav->execute([':mid' => $_SESSION['member_id']]);
            $nav_data = $stmt_nav->fetch(PDO::FETCH_ASSOC);
            
            if ($nav_data) {
                $member_points = $nav_data['point_balance'];
                $member_level = $nav_data['member_level'];
            }
        } catch (PDOException $e) {
            $member_points = 0;
            $member_level = 'Bronze';
        }
    }
}
?>

<header class="navbar-dark-header navbar">
    <div class="nav-dark-container nav-container">
        <!-- โลโก้เว็บไซต์ -->
        <a href="index.php" class="nav-dark-brand nav-brand">
            <i class="fas fa-shuttlecock text-gold"></i>
            <span>T.S. PATTANI</span>
        </a>
        
        <!-- เมนูหลักแบบบล็อกช่องพร้อม Indicator เส้นใต้สีทอง -->
        <ul class="nav-dark-menu nav-menu" id="navMenu">
            <li>
                <a href="index.php" class="<?php echo $is_home ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>หน้าแรก</span>
                </a>
            </li>
            <li>
                <a href="promotions.php" class="<?php echo $is_promotions ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>ข่าวสาร&โปรโมชัน</span>
                </a>
            </li>
            <li>
                <a href="booking.php" class="<?php echo $is_booking ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>จองสนาม</span>
                </a>
            </li>
            <li>
                <a href="rewards.php" class="<?php echo $is_rewards ? 'active' : ''; ?>">
                    <i class="fas fa-gift"></i>
                    <span>แลกของรางวัล</span>
                </a>
            </li>
            <li>
                <a href="chat.php" class="<?php echo $is_chat ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i>
                    <span>ติดต่อเรา</span>
                </a>
            </li>

            <!-- รายการเมนูสำหรับจอมือถือ (User Info หรือ เข้าสู่ระบบ/สมัครสมาชิก) -->
            <li class="mobile-nav-user-item">
                <?php if (isset($_SESSION['member_id'])): ?>
                    <div class="mobile-user-card">
                        <div class="mobile-user-info">
                            <i class="fas fa-user-circle text-gold"></i>
                            <span class="mobile-user-name"><?php echo htmlspecialchars($_SESSION['member_name'] ?? ''); ?></span>
                            <span class="badge-level level-<?php echo strtolower($member_level); ?>"><?php echo htmlspecialchars($member_level); ?></span>
                        </div>
                        <div class="mobile-user-points">
                            คะแนนสะสม: <strong class="text-gold"><?php echo number_format($member_points); ?></strong> พอยท์
                        </div>
                        <hr class="mobile-user-divider">
                        <div class="mobile-user-links">
                            <a href="profile.php"><i class="fas fa-id-card"></i> โปรไฟล์ของฉัน</a>
                            <a href="booking_history.php"><i class="fas fa-history"></i> ประวัติการจอง</a>
                            <a href="actions/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mobile-login-box">
                        <?php $nav_login_href = ($current_page === 'booking.php') ? 'login.php?redirect=booking.php' : 'login.php'; ?>
                        <a href="register.php" class="btn-nav-ghost mobile-btn-ghost"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a>
                        <a href="<?php echo $nav_login_href; ?>" class="btn-nav-gold mobile-btn-login"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
                    </div>
                <?php endif; ?>
            </li>
        </ul>

        <!-- ปุ่ม Actions ด้านขวา (Desktop) -->
        <div class="nav-dark-actions nav-user">
            <?php if (isset($_SESSION['member_id'])): ?>
                <!-- กรณีสมาชิกเข้าสู่ระบบแล้ว -->
                <div class="user-dropdown">
                    <button type="button" class="dropbtn-dark dropbtn" onclick="toggleDropdown()">
                        <i class="fas fa-user-circle text-gold"></i>
                        <span><?php echo htmlspecialchars($_SESSION['member_name'] ?? ''); ?></span>
                        <span class="badge-level level-<?php echo strtolower($member_level); ?>"><?php echo htmlspecialchars($member_level); ?></span>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-content dropdown-content-dark" id="userDropdown">
                        <div class="user-point-info-dark user-point-info">
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
                <?php $nav_login_href = ($current_page === 'booking.php') ? 'login.php?redirect=booking.php' : 'login.php'; ?>
                <a href="register.php" class="btn-nav-ghost">
                    <i class="fas fa-user-plus"></i>
                    <span>สมัครสมาชิก</span>
                </a>
                <a href="<?php echo $nav_login_href; ?>" class="btn-nav-gold btn-login-nav">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>เข้าสู่ระบบ</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- ปุ่มเปิดเมนูสำหรับมือถือ (Hamburger Button) -->
        <button type="button" class="nav-dark-toggle mobile-menu-btn" id="navDarkToggle" onclick="toggleMobileMenu()" aria-label="เปิดเมนูนำทาง">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>