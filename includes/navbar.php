<?php
// ดึงข้อมูลคะแนนสะสมและระดับสมาชิกของลูกค้าที่ล็อกอินอยู่ (ตามขอบเขตข้อ 1.3.3)
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
?>

<nav class="navbar">
    <div class="nav-container">
        <!-- โลโก้เว็บไซต์ -->
        <a href="index.php" class="nav-brand">
            <i class="fas fa-shuttlecock"></i> T.S. Pattani
        </a>
        
        <!-- เมนูหลัก -->
        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php"><i class="fas fa-home"></i> หน้าแรก</a></li>
            <li><a href="promotions.php"><i class="fas fa-bullhorn"></i> ข่าวสาร&โปรโมชัน</a></li>
            <li><a href="booking.php"><i class="fas fa-calendar-check"></i> จองสนาม</a></li>
            <li><a href="rewards.php"><i class="fas fa-gift"></i> แลกของรางวัล</a></li>
            <li><a href="chat.php"><i class="fas fa-comments"></i> ติดต่อเรา</a></li>
        </ul>

        <!-- ส่วนของข้อมูลผู้ใช้งาน -->
        <div class="nav-user">
            <?php if (isset($_SESSION['member_id'])): ?>
                <!-- กรณีล็อกอินแล้ว แสดงชื่อและ Dropdown -->
                <div class="user-dropdown">
                    <button class="dropbtn" onclick="toggleDropdown()">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['member_name']); ?>
                        <span class="badge-level level-<?php echo strtolower($member_level); ?>"><?php echo $member_level; ?></span>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-content" id="userDropdown">
                        <div class="user-point-info">
                            <span>คะแนนสะสม: <strong><?php echo number_format($member_points); ?></strong> พอยท์</span>
                        </div>
                        <hr>
                        <a href="profile.php"><i class="fas fa-id-card"></i> โปรไฟล์ของฉัน</a>
                        <a href="booking_history.php"><i class="fas fa-history"></i> ประวัติการจอง</a>
                        <a href="actions/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- กรณีเข้าชมทั่วไป (ยังไม่ล็อกอิน) -->
                <a href="login.php" class="btn-login-nav"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
            <?php endif; ?>
        </div>
        
        <!-- ปุ่มแฮมเบอร์เกอร์ สำหรับมือถือ -->
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>