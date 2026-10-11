<?php
/**
 * T.S. Pattani Badminton - Mobile Bottom Navigation Bar
 * แสดงผลเฉพาะสมาชิกที่เข้าสู่ระบบแล้ว บนหน้าจอสมาร์ตโฟน (<= 768px)
 */

if (!isset($_SESSION['member_id']) && !isset($_SESSION['user_id'])) {
    return;
}

$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$is_home_nav = in_array($current_page, ['index.php', '']);
$is_booking_nav = in_array($current_page, ['booking.php', 'payment.php']);
$is_promotions_nav = in_array($current_page, ['promotions.php', 'promotion.php', 'news.php', 'news_detail.php']);
$is_rewards_nav = in_array($current_page, ['rewards.php', 'reward_history.php']);
$is_profile_nav = in_array($current_page, ['profile.php', 'booking_history.php']);
?>

<nav class="mobile-bottom-nav" aria-label="เมนูนำทางหลักบนมือถือ">
    <a href="index.php" class="bottom-nav-item <?= $is_home_nav ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>หน้าแรก</span>
    </a>
    <a href="booking.php" class="bottom-nav-item <?= $is_booking_nav ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i>
        <span>จองสนาม</span>
    </a>
    <a href="promotions.php" class="bottom-nav-item <?= $is_promotions_nav ? 'active' : '' ?>">
        <i class="fas fa-bullhorn"></i>
        <span>โปรโมชัน</span>
    </a>
    <a href="rewards.php" class="bottom-nav-item <?= $is_rewards_nav ? 'active' : '' ?>">
        <i class="fas fa-gift"></i>
        <span>ของรางวัล</span>
    </a>
    <a href="profile.php" class="bottom-nav-item <?= $is_profile_nav ? 'active' : '' ?>">
        <i class="fas fa-user"></i>
        <span>โปรไฟล์</span>
    </a>
</nav>
