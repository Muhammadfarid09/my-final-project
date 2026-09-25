<?php
// ฟังก์ชันเล็กๆ เพื่อเช็คว่าตอนนี้อยู่หน้าไหน จะได้ใส่คลาส active ให้เมนูถูกตัว
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="admin-sidebar">
    <div class="brand">
        <h2>T.S. Pattani</h2>
        <span><i class="fas fa-circle"></i> ระบบผู้ดูแลระบบ</span>
    </div>
    
    <ul class="admin-menu">
        <li>
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> <span>แดชบอร์ด & สถิติ</span>
            </a>
        </li>
        
        <li class="menu-label">การจัดการหลัก</li>
        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'Super Admin'): ?>
        <li>
            <a href="admins.php" class="<?php echo ($current_page == 'admins.php' || $current_page == 'admin_add.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> <span>จัดการผู้ดูแลระบบ</span>
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="members.php" class="<?php echo ($current_page == 'members.php' || $current_page == 'member_edit.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> <span>จัดการข้อมูลสมาชิก</span>
            </a>
        </li>
        <li>
            <a href="rewards.php" class="<?php echo ($current_page == 'rewards.php' || $current_page == 'reward_edit.php') ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i> <span>จัดการของรางวัล</span>
            </a>
        </li>
        <li>
            <a href="redemptions.php" class="<?php echo ($current_page == 'redemptions.php') ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> <span>ประวัติแลกรางวัล</span>
            </a>
        </li>

        <li class="menu-label">จัดการสนาม & การจอง</li>
        <li>
            <a href="courts.php" class="<?php echo ($current_page == 'courts.php') ? 'active' : ''; ?>">
                <i class="fas fa-map-marker-alt"></i> <span>จัดการสนามแบดมินตัน</span>
            </a>
        </li>
        <li>
            <a href="court_repairs.php" class="<?php echo ($current_page == 'court_repairs.php') ? 'active' : ''; ?>">
                <i class="fas fa-tools"></i> <span>บันทึกการแจ้งซ่อมสนาม</span>
            </a>
        </li>
        <li>
            <a href="cancellations.php" class="<?php echo ($current_page == 'cancellations.php') ? 'active' : ''; ?>">
                <i class="fas fa-ban"></i> <span>ระบบพิจารณายกเลิก</span>
            </a>
        </li>

        <li class="menu-label">คลังสินค้า & อุปกรณ์</li>
        <li>
            <a href="products.php" class="<?php echo ($current_page == 'products.php' || strpos($current_page, 'product_') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-box-open"></i> <span>จัดการสินค้าและอุปกรณ์</span>
            </a>
        </li>
        <li>
            <a href="equipment_repairs.php" class="<?php echo ($current_page == 'equipment_repairs.php') ? 'active' : ''; ?>">
                <i class="fas fa-wrench"></i> <span>บันทึกซ่อมบำรุงอุปกรณ์</span>
            </a>
        </li>
        <li>
            <a href="equipment_returns.php" class="<?php echo ($current_page == 'equipment_returns.php') ? 'active' : ''; ?>">
                <i class="fas fa-undo-alt"></i> <span>ตรวจรับคืนอุปกรณ์</span>
            </a>
        </li>
        <li>
            <a href="purchases.php" class="<?php echo ($current_page == 'purchases.php' || $current_page == 'purchase_add.php') ? 'active' : ''; ?>">
                <i class="fas fa-truck-loading"></i> <span>บันทึกจัดซื้อ (รับของเข้า)</span>
            </a>
        </li>
        
        <li class="menu-label">ธุรกรรม & การขาย</li>
        <li>
            <a href="payments.php" class="<?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i> <span>ตรวจสอบสลิป & การจอง</span>
            </a>
        </li>
        <li>
            <a href="pos.php" class="<?php echo ($current_page == 'pos.php' || $current_page == 'pos_sales.php') ? 'active' : ''; ?>">
                <i class="fas fa-cash-register"></i> <span>ขายหน้าร้าน (POS)</span>
            </a>
        </li>
        <li>
            <a href="pos_history.php" class="<?php echo ($current_page == 'pos_history.php') ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i> <span>ประวัติขายหน้าร้าน (บิล)</span>
            </a>
        </li>
        
        <li class="menu-label">สื่อสาร & ประชาสัมพันธ์</li>
        <li>
            <a href="chats.php" class="<?php echo ($current_page == 'chats.php') ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> <span>ระบบตอบแชทลูกค้า</span>
            </a>
        </li>
        <li>
            <a href="news.php" class="<?php echo ($current_page == 'news.php' || $current_page == 'news_edit.php') ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> <span>จัดการข่าวสาร & ประกาศ</span>
            </a>
        </li>
        
        <li class="mt-30">
            <a href="actions/logout_db.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
        </li>
    </ul>
</aside>