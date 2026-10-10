<?php
session_start();
require_once 'config/config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit();
}

$member_id = intval($_SESSION['member_id']);

try {
    // 1. ดึงข้อมูลสมาชิกและแต้มสะสม
    $stmt = $conn->prepare("
        SELECT m.*, 
               COALESCE(p.point_balance, 0) AS point_balance, 
               COALESCE(p.point_total_earned, 0) AS point_total_earned, 
               COALESCE(p.member_level, 'Bronze') AS member_level 
        FROM Member m
        LEFT JOIN Point p ON m.member_id = p.member_id
        WHERE m.member_id = :mid
    ");
    $stmt->execute([':mid' => $member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // 2. ดึงสถิติการใช้งานของสมาชิก
    $stmt_stats = $conn->prepare("
        SELECT 
            COUNT(*) AS total_bookings,
            SUM(CASE WHEN booking_status = 'จองแล้ว' THEN 1 ELSE 0 END) AS success_bookings,
            SUM(CASE WHEN booking_status = 'ยกเลิก' THEN 1 ELSE 0 END) AS cancel_bookings
        FROM Booking 
        WHERE member_id = :mid
    ");
    $stmt_stats->execute([':mid' => $member_id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // 3. ดึงประวัติรายการพอยท์สะสม (Point Transaction) ล่าสุด 10 รายการ
    $stmt_point_trans = $conn->prepare("
        SELECT pt.*, b.booking_date, b.booking_start_time
        FROM Point_Transaction pt
        LEFT JOIN Booking b ON pt.booking_id = b.booking_id
        WHERE pt.member_id = :mid
        ORDER BY pt.transaction_date DESC, pt.transaction_id DESC
        LIMIT 10
    ");
    $stmt_point_trans->execute([':mid' => $member_id]);
    $point_transactions = $stmt_point_trans->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาดในการโหลดข้อมูล: " . $e->getMessage());
}

$tier_badge_classes = [
    'Bronze' => 'tier-bronze',
    'Silver' => 'tier-silver',
    'Gold'   => 'tier-gold'
];
$current_tier_class = $tier_badge_classes[$member['member_level']] ?? 'tier-bronze';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.7">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="page-body">

    <?php include 'includes/navbar.php'; ?>

    <div class="profile-container">

        <!-- กล่องแจ้งเตือนผลลัพธ์ -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-box alert-success-box">
                <i class="fas fa-check-circle fa-lg"></i>
                <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-box alert-error-box">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <!-- บัตรสมาชิกดิจิทัล (Virtual Digital Member Card) -->
        <div class="virtual-member-card virtual-card-<?php echo strtolower($member['member_level'] ?? 'bronze'); ?>">
            <div class="virtual-card-top">
                <div class="virtual-card-club"><i class="fas fa-certificate"></i> T.S. PATTANI BADMINTON CLUB</div>
                <div class="virtual-card-badge"><i class="fas fa-crown"></i> <?php echo htmlspecialchars($member['member_level'] ?? 'Bronze'); ?> TIER</div>
            </div>
            <div class="virtual-card-mid">
                <div class="virtual-card-name"><?php echo htmlspecialchars($member['member_name']); ?></div>
                <div class="virtual-card-phone"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($member['member_phone']); ?> &bull; สมาชิกตั้งแต่ <?php echo date('d/m/Y', strtotime($member['member_created_at'])); ?></div>
            </div>
            <div class="virtual-card-bottom">
                <div>
                    <div class="virtual-card-point-label">พอยท์คงเหลือสำหรับแลกรางวัล</div>
                    <div class="virtual-card-points"><?php echo number_format($member['point_balance'] ?? 0); ?> <span class="virtual-card-unit">พอยท์ (สะสม <?php echo number_format($member['point_total_earned'] ?? 0); ?>)</span></div>
                </div>
                <div>
                    <a href="rewards.php" class="btn-redeem-chip"><i class="fas fa-gift"></i> แลกของรางวัล</a>
                </div>
            </div>
        </div>

        <!-- กริดฟอร์ม 2 ฝั่ง -->
        <div class="profile-grid">
            
            <!-- ฝั่งซ้าย: ข้อมูลส่วนตัว -->
            <div class="profile-card">
                <h3 class="profile-card-title">
                    <i class="fas fa-user-edit"></i> ข้อมูลส่วนตัว
                </h3>

                <form action="actions/profile_edit_db.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group-custom">
                        <label class="form-label">ชื่อ - นามสกุล <span class="required-star">*</span></label>
                        <input type="text" name="member_name" class="form-control-custom" value="<?php echo htmlspecialchars($member['member_name']); ?>" required>
                    </div>

                    <div class="form-group-custom">
                        <label class="form-label">เบอร์โทรศัพท์ (ใช้เข้าสู่ระบบ) <span class="required-star">*</span></label>
                        <input type="text" name="member_phone" class="form-control-custom" value="<?php echo htmlspecialchars($member['member_phone']); ?>" maxlength="10" pattern="[0-9]{10}" placeholder="08xxxxxxxx" required>
                    </div>

                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label class="form-label">เพศ</label>
                            <select name="member_gender" class="form-control-custom">
                                <option value="ชาย" <?php if($member['member_gender'] == 'ชาย') echo 'selected'; ?>>ชาย</option>
                                <option value="หญิง" <?php if($member['member_gender'] == 'หญิง') echo 'selected'; ?>>หญิง</option>
                                <option value="อื่นๆ" <?php if($member['member_gender'] == 'อื่นๆ') echo 'selected'; ?>>อื่นๆ</option>
                            </select>
                        </div>
                        <div class="form-group-custom">
                            <label class="form-label">อายุ (ปี)</label>
                            <input type="number" name="member_age" class="form-control-custom" value="<?php echo htmlspecialchars($member['member_age'] ?? ''); ?>" min="5" max="120" placeholder="ระบุอายุ">
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <label class="form-label">อาชีพ</label>
                        <input type="text" name="member_occupation" class="form-control-custom" value="<?php echo htmlspecialchars($member['member_occupation'] ?? ''); ?>" placeholder="เช่น นักเรียน, ข้าราชการ, พนักงานบริษัท">
                    </div>

                    <div class="profile-btn-mt">
                        <button type="submit" class="btn-profile-submit">
                            <i class="fas fa-save"></i> บันทึกข้อมูลส่วนตัว
                        </button>
                    </div>
                </form>
            </div>

            <!-- ฝั่งขวา: เปลี่ยนรหัสผ่านและสรุปกิจกรรม -->
            <div>
                <!-- การ์ดเปลี่ยนรหัสผ่าน -->
                <div class="profile-card profile-card-mb">
                    <h3 class="profile-card-title">
                        <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                    </h3>

                    <form action="actions/profile_edit_db.php" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group-custom">
                            <label class="form-label">รหัสผ่านปัจจุบัน <span class="required-star">*</span></label>
                            <input type="password" name="old_password" class="form-control-custom" placeholder="กรอกรหัสผ่านปัจจุบัน" required>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label">รหัสผ่านใหม่ (อย่างน้อย 6 ตัวอักษร) <span class="required-star">*</span></label>
                            <input type="password" name="new_password" class="form-control-custom" minlength="6" placeholder="กรอกรหัสผ่านใหม่" required>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label">ยืนยันรหัสผ่านใหม่ <span class="required-star">*</span></label>
                            <input type="password" name="confirm_password" class="form-control-custom" minlength="6" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" required>
                        </div>

                        <div class="profile-btn-mt">
                            <button type="submit" class="btn-profile-submit btn-password-submit">
                                <i class="fas fa-lock"></i> เปลี่ยนรหัสผ่าน
                            </button>
                        </div>
                    </form>
                </div>

                <!-- การ์ดภาพรวมสถิติของฉัน -->
                <div class="profile-card">
                    <h3 class="profile-card-title">
                        <i class="fas fa-chart-line"></i> กิจกรรมของฉัน
                    </h3>

                    <div class="summary-stats-box">
                        <div>
                            <div class="summary-stat-val"><?php echo intval($stats['total_bookings'] ?? 0); ?></div>
                            <div class="summary-stat-lbl">จองทั้งหมด</div>
                        </div>
                        <div>
                            <div class="summary-stat-val stat-green"><?php echo intval($stats['success_bookings'] ?? 0); ?></div>
                            <div class="summary-stat-lbl">จองสำเร็จ</div>
                        </div>
                        <div>
                            <div class="summary-stat-val stat-red"><?php echo intval($stats['cancel_bookings'] ?? 0); ?></div>
                            <div class="summary-stat-lbl">ยกเลิก</div>
                        </div>
                    </div>

                    <div class="profile-action-links">
                        <a href="booking_history.php" class="btn-profile-submit btn-profile-outline">
                            <i class="fas fa-history"></i> ประวัติการจอง
                        </a>
                        <a href="rewards.php" class="btn-profile-submit btn-profile-outline">
                            <i class="fas fa-gift"></i> แลกของรางวัล
                        </a>
                    </div>
                </div>

            </div>

        </div>

        <!-- การ์ดประวัติคะแนนสะสม (Point Transaction History) -->
        <div class="profile-card profile-card-mt">
            <div class="profile-card-title space-between">
                <div><i class="fas fa-coins coins-icon"></i> ประวัติคะแนนสะสม (Point History)</div>
                <span class="profile-subtitle-hint">10 รายการล่าสุด</span>
            </div>

            <?php if (empty($point_transactions)): ?>
                <div class="profile-point-empty">
                    <i class="fas fa-receipt fa-2x"></i>
                    <p>ยังไม่มีประวัติการทำรายการคะแนนสะสม</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="profile-point-table">
                        <thead>
                            <tr>
                                <th>วัน-เวลา</th>
                                <th>ประเภทรายการ</th>
                                <th>รายละเอียด</th>
                                <th class="text-right">จำนวนคะแนน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($point_transactions as $pt): 
                                $is_earned = ($pt['transaction_type'] === 'ได้รับ');
                                $is_used = ($pt['transaction_type'] === 'ใช้');
                                $pt_type_class = $is_earned ? 'point-badge-earned' : ($is_used ? 'point-badge-used' : 'point-badge-admin');
                                $pt_val_class = $is_earned ? 'point-val-earned' : ($is_used ? 'point-val-used' : 'point-val-admin');
                                $pt_prefix = $is_earned ? '+' : ($is_used ? '-' : '');
                            ?>
                                <tr>
                                    <td class="profile-point-date">
                                        <?php echo date('d/m/Y H:i', strtotime($pt['transaction_date'])); ?>
                                    </td>
                                    <td>
                                        <span class="point-type-badge <?php echo $pt_type_class; ?>">
                                            <?php echo htmlspecialchars($pt['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td class="profile-point-desc">
                                        <?php echo htmlspecialchars($pt['transaction_note'] ?? '-'); ?>
                                        <?php if (!empty($pt['booking_id'])): ?>
                                            <span class="point-booking-ref">(การจอง #<?php echo $pt['booking_id']; ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="profile-point-amount <?php echo $pt_val_class; ?>">
                                        <?php echo $pt_prefix . number_format($pt['transaction_point']); ?> พอยท์
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="assets/js/member.js?v=1.5"></script>
</body>
</html>
