<?php
// ตรวจจับชื่อไฟล์ปัจจุบันสำหรับ Active Navigation Indicator แบบไดนามิก
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$is_home = in_array($current_page, ['index.php', '']);
$is_promotions = in_array($current_page, ['promotions.php', 'promotion.php', 'news.php', 'news_detail.php']);
$is_booking = in_array($current_page, ['booking.php', 'payment.php']);
$is_rewards = in_array($current_page, ['rewards.php', 'reward_history.php']);
$is_chat = in_array($current_page, ['chat.php', 'contact.php']);
$is_profile = in_array($current_page, ['profile.php']);
$is_booking_history = in_array($current_page, ['booking_history.php']);

// ดึงข้อมูลคะแนนสะสมและระดับสมาชิกของลูกค้าที่ล็อกอินอยู่
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

<!-- ==========================================================================
     Global Navbar (T.S. Pattani Badminton Portal) - Height: 68px
     Flexbox 3 ส่วน: [Logo (ซ้าย) | Menu + ขีดใต้ทอง (กลาง) | Actions (ขวา)]
     ========================================================================== -->
<header class="global-navbar">
    <div class="navbar-inner">
        
        <!-- ปุ่มแฮมเบอร์เกอร์ (☰) สำหรับเปิด Offcanvas Drawer (มุมซ้ายบน) -->
        <button type="button" class="navbar-drawer-btn" onclick="toggleMobileDrawer()" aria-label="เปิดเมนูนำทาง" aria-controls="mobileDrawer">
            <i class="fas fa-bars"></i>
        </button>

        <!-- 1. โลโก้แบรนด์ (ซ้าย) -->
        <a href="index.php" class="navbar-brand">
            <i class="fas fa-shuttlecock brand-icon"></i>
            <span class="brand-text">T.S. PATTANI</span>
        </a>

        <!-- 2. เมนูนำทางตรงกลาง (Center Menu + ขีดใต้ทองตาม $current_page) -->
        <nav class="navbar-nav" id="navMenu">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= $is_home ? 'active' : '' ?>">
                        <i class="fas fa-home"></i>
                        <span>หน้าแรก</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="promotions.php" class="nav-link <?= $is_promotions ? 'active' : '' ?>">
                        <i class="fas fa-bullhorn"></i>
                        <span>ข่าวสาร&โปรโมชัน</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="booking.php" class="nav-link <?= $is_booking ? 'active' : '' ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span>จองสนาม</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="rewards.php" class="nav-link <?= $is_rewards ? 'active' : '' ?>">
                        <i class="fas fa-gift"></i>
                        <span>แลกของรางวัล</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="chat.php" class="nav-link <?= $is_chat ? 'active' : '' ?>">
                        <i class="fas fa-comments"></i>
                        <span>ติดต่อเรา</span>
                    </a>
                </li>

                <!-- รายการสำหรับจอมือถือ (Drawer Actions) -->
                <li class="mobile-actions-item">
                    <?php if (isset($_SESSION['member_id'])): ?>
                        <div class="mobile-user-card">
                            <div class="mobile-user-header">
                                <i class="fas fa-user-circle"></i>
                                <div class="mobile-user-meta">
                                    <span class="mobile-user-name"><?= htmlspecialchars($_SESSION['member_name'] ?? '') ?></span>
                                    <span class="badge-level level-<?= strtolower($member_level) ?>"><?= htmlspecialchars($member_level) ?></span>
                                </div>
                            </div>
                            <div class="mobile-user-points">
                                คะแนนสะสม: <strong><?= number_format($member_points) ?></strong> พอยท์
                            </div>
                            <div class="mobile-user-menu">
                                <a href="profile.php" class="<?= $is_profile ? 'active' : '' ?>"><i class="fas fa-id-card"></i> โปรไฟล์ของฉัน</a>
                                <a href="booking_history.php" class="<?= $is_booking_history ? 'active' : '' ?>"><i class="fas fa-history"></i> ประวัติการจอง</a>
                                <a href="actions/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mobile-auth-buttons">
                            <a href="register.php" class="btn-nav-register"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a>
                            <?php $login_redirect = ($current_page === 'booking.php') ? 'login.php?redirect=booking.php' : 'login.php'; ?>
                            <a href="<?= $login_redirect ?>" class="btn-nav-login"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
                        </div>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>

        <!-- 3. Actions ด้านขวา (Desktop Only - แสดงชุดเดียวตาม Session) -->
        <div class="navbar-actions">
            <?php if (isset($_SESSION['member_id'])): ?>
                <!-- ล็อกอินแล้ว: แสดง User Dropdown และปุ่มจองสนาม -->
                <div class="user-dropdown">
                    <button type="button" class="dropbtn <?= ($is_profile || $is_booking_history) ? 'active' : '' ?>" onclick="toggleDropdown()">
                        <i class="fas fa-user-circle"></i>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['member_name'] ?? '') ?></span>
                        <span class="badge-level level-<?= strtolower($member_level) ?>"><?= htmlspecialchars($member_level) ?></span>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-content" id="userDropdown">
                        <div class="user-point-info">
                            <span>คะแนนสะสม: <strong class="text-gold"><?= number_format($member_points) ?></strong> พอยท์</span>
                        </div>
                        <hr class="dropdown-divider">
                        <a href="profile.php" class="<?= $is_profile ? 'active' : '' ?>"><i class="fas fa-id-card"></i> โปรไฟล์ของฉัน</a>
                        <a href="booking_history.php" class="<?= $is_booking_history ? 'active' : '' ?>"><i class="fas fa-history"></i> ประวัติการจอง</a>
                        <a href="actions/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                    </div>
                </div>
                <a href="booking.php" class="btn-nav-cta">
                    <i class="fas fa-calendar-plus"></i>
                    <span>จองสนาม</span>
                </a>
            <?php else: ?>
                <!-- ยังไม่ล็อกอิน: แสดงปุ่มสมัครสมาชิก และปุ่มเข้าสู่ระบบ (ชุดเดียวเด็ดขาด ไม่ขึ้นซ้ำ) -->
                <a href="register.php" class="btn-nav-register">
                    <i class="fas fa-user-plus"></i>
                    <span>สมัครสมาชิก</span>
                </a>
                <?php $login_redirect = ($current_page === 'booking.php') ? 'login.php?redirect=booking.php' : 'login.php'; ?>
                <a href="<?= $login_redirect ?>" class="btn-nav-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>เข้าสู่ระบบ</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- ส่วนควบคุมบนจอมือถือ (Mobile Top Controls) -->
        <?php if (isset($_SESSION['member_id'])): ?>
            <!-- เมื่อล็อกอินแล้ว: แสดงปุ่มโปรไฟล์ย่อ (Mini Profile Pill) แทน Hamburger Menu เพื่อลดความซ้ำซ้อนกับ Bottom Nav -->
            <a href="profile.php" class="mobile-top-user-pill" aria-label="โปรไฟล์ของฉัน">
                <div class="user-pill-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-pill-info">
                    <span class="user-pill-name"><?= htmlspecialchars(mb_substr($_SESSION['member_name'] ?? 'สมาชิก', 0, 10)) ?></span>
                    <span class="user-pill-tier level-<?= strtolower($member_level) ?>"><?= htmlspecialchars($member_level) ?></span>
                </div>
            </a>
        <?php else: ?>
            <!-- เมื่อยังไม่ล็อกอิน: แสดงปุ่มเข้าสู่ระบบด่วน -->
            <?php $login_redirect = ($current_page === 'booking.php') ? 'login.php?redirect=booking.php' : 'login.php'; ?>
            <a href="<?= $login_redirect ?>" class="mobile-top-login-btn" aria-label="เข้าสู่ระบบ">
                <i class="fas fa-sign-in-alt"></i>
                <span>เข้าสู่ระบบ</span>
            </a>
        <?php endif; ?>
    </div>
</header>

<!-- ==========================================================================
     Offcanvas Drawer Menu (Mobile Navigation Panel with Admin Chat)
     ========================================================================== -->
<!-- ม่านฉากหลัง Backdrop สำหรับปิด Drawer เมื่อคลิกนอกกรอบ -->
<div class="drawer-backdrop" id="drawerBackdrop" onclick="toggleMobileDrawer()"></div>

<!-- แผง Drawer เมนูด้านข้าง -->
<aside class="offcanvas-drawer" id="mobileDrawer" aria-label="เมนูนำทางหลัก">
    <!-- ส่วนหัว Drawer -->
    <div class="drawer-header">
        <a href="index.php" class="drawer-brand">
            <i class="fas fa-shuttlecock brand-icon"></i>
            <span class="brand-text">T.S. PATTANI</span>
        </a>
        <button type="button" class="drawer-close-btn" onclick="toggleMobileDrawer()" aria-label="ปิดเมนู">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- ส่วนข้อมูลผู้ใช้ (Member / Guest) -->
    <div class="drawer-user-section">
        <?php if (isset($_SESSION['member_id'])): ?>
            <div class="drawer-user-card">
                <div class="drawer-user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="drawer-user-info">
                    <span class="drawer-user-name"><?= htmlspecialchars($_SESSION['member_name'] ?? 'สมาชิก') ?></span>
                    <div class="drawer-user-badges">
                        <span class="badge-level level-<?= strtolower($member_level) ?>"><?= htmlspecialchars($member_level) ?></span>
                        <span class="drawer-points-pill"><i class="fas fa-coins text-gold"></i> <?= number_format($member_points) ?> พอยท์</span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="drawer-guest-box">
                <p class="drawer-guest-title">ยินดีต้อนรับสู่ T.S. Pattani</p>
                <div class="drawer-auth-buttons">
                    <a href="login.php" class="btn-drawer-login"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
                    <a href="register.php" class="btn-drawer-register"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- รายการเมนูหลักภายใน Drawer -->
    <nav class="drawer-nav">
        <div class="drawer-section-title">เมนูนำทางหลัก</div>
        <ul class="drawer-menu-list">
            <li>
                <a href="index.php" class="drawer-nav-link <?= $is_home ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>หน้าแรก</span>
                </a>
            </li>
            <li>
                <a href="booking.php" class="drawer-nav-link <?= $is_booking ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>จองสนามออนไลน์</span>
                </a>
            </li>
            <li>
                <a href="promotions.php" class="drawer-nav-link <?= $is_promotions ? 'active' : '' ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>ข่าวสารและโปรโมชัน</span>
                </a>
            </li>
            <li>
                <a href="rewards.php" class="drawer-nav-link <?= $is_rewards ? 'active' : '' ?>">
                    <i class="fas fa-gift"></i>
                    <span>แลกของรางวัล (Points)</span>
                </a>
            </li>
            
            <!-- เมนูแชทติดต่อแอดมิน (แบบมาตรฐานทั่วไป) -->
            <li>
                <a href="chat.php" class="drawer-nav-link <?= $is_chat ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i>
                    <span>แชทติดต่อแอดมิน</span>
                </a>
            </li>

            <?php if (isset($_SESSION['member_id'])): ?>
                <li class="drawer-divider"></li>
                <div class="drawer-section-title">บัญชีสมาชิก</div>
                <li>
                    <a href="profile.php" class="drawer-nav-link <?= $is_profile ? 'active' : '' ?>">
                        <i class="fas fa-id-card"></i>
                        <span>โปรไฟล์ของฉัน</span>
                    </a>
                </li>
                <li>
                    <a href="booking_history.php" class="drawer-nav-link <?= $is_booking_history ? 'active' : '' ?>">
                        <i class="fas fa-history"></i>
                        <span>ประวัติการจอง</span>
                    </a>
                </li>
                <li>
                    <a href="actions/logout.php" class="drawer-nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>ออกจากระบบ</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

<!-- ผนวก Mobile Bottom Navigation Bar ส่วนกลาง -->
<?php include_once __DIR__ . '/bottom_nav.php'; ?>