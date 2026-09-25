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

} catch(PDOException $e) {
    die("เกิดข้อผิดพลาดในการโหลดข้อมูล: " . $e->getMessage());
}

$tier_colors = [
    'Bronze' => ['bg' => '#cd7f32', 'text' => '#ffffff'],
    'Silver' => ['bg' => '#6c757d', 'text' => '#ffffff'],
    'Gold'   => ['bg' => '#d4af37', 'text' => '#ffffff']
];
$current_tier_color = $tier_colors[$member['member_level']] ?? ['bg' => '#cd7f32', 'text' => '#ffffff'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .profile-container {
            max-width: 1050px;
            margin: 35px auto 60px;
            padding: 0 20px;
            font-family: 'Prompt', sans-serif;
        }
        .profile-header-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #ffffff;
            border-radius: 16px;
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 8px 25px rgba(30, 60, 114, 0.2);
            margin-bottom: 30px;
        }
        .profile-user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            color: #ffffff;
            border: 3px solid rgba(255, 255, 255, 0.4);
        }
        .profile-details h2 {
            margin: 0 0 6px 0;
            font-size: 24px;
            font-weight: 600;
        }
        .profile-details p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .profile-tier-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 8px;
            background-color: <?php echo $current_tier_color['bg']; ?>;
            color: <?php echo $current_tier_color['text']; ?>;
            border: 1px solid rgba(255,255,255,0.5);
        }
        .profile-stats-group {
            display: flex;
            gap: 25px;
            background: rgba(255, 255, 255, 0.12);
            padding: 15px 25px;
            border-radius: 12px;
            backdrop-filter: blur(5px);
        }
        .stat-item {
            text-align: center;
        }
        .stat-item .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #ffeb3b;
        }
        .stat-item .stat-label {
            font-size: 12px;
            opacity: 0.85;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        @media (max-width: 850px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .profile-header-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .profile-stats-group {
                width: 100%;
                justify-content: space-around;
            }
        }
        .profile-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #eaeaea;
        }
        .profile-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f2f5;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .profile-card-title i {
            color: #1e3c72;
        }
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
        }
        .form-control-custom {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .form-control-custom:focus {
            border-color: #1e3c72;
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.15);
        }
        .form-group-custom {
            margin-bottom: 16px;
        }
        .form-row-custom {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .btn-profile-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 20px;
            background: #1e3c72;
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-profile-submit:hover {
            background: #162c54;
        }
        .btn-profile-submit:active {
            transform: scale(0.99);
        }
        .btn-password-submit {
            background: #e67e22;
        }
        .btn-password-submit:hover {
            background: #d35400;
        }
        .summary-stats-box {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px dashed #cbd5e1;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .summary-stat-val {
            font-size: 20px;
            font-weight: 700;
            color: #1e3c72;
        }
        .summary-stat-lbl {
            font-size: 12px;
            color: #64748b;
        }
        .alert-box {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success-box {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error-box {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body style="background-color: #f4f6f9;">

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

        <!-- การ์ดส่วนหัวข้อมูลสมาชิก -->
        <div class="profile-header-card">
            <div class="profile-user-info">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-details">
                    <h2>
                        <?php echo htmlspecialchars($member['member_name']); ?>
                        <span class="profile-tier-badge">
                            <i class="fas fa-crown"></i> <?php echo htmlspecialchars($member['member_level']); ?>
                        </span>
                    </h2>
                    <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($member['member_phone']); ?> &bull; สมาชิกตั้งแต่ <?php echo date('d/m/Y', strtotime($member['member_created_at'])); ?></p>
                </div>
            </div>
            
            <div class="profile-stats-group">
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($member['point_balance']); ?></div>
                    <div class="stat-label">พอยท์คงเหลือ</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($member['point_total_earned']); ?></div>
                    <div class="stat-label">พอยท์สะสมตลอดชีพ</div>
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
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group-custom">
                        <label class="form-label">ชื่อ - นามสกุล <span style="color: red;">*</span></label>
                        <input type="text" name="member_name" class="form-control-custom" value="<?php echo htmlspecialchars($member['member_name']); ?>" required>
                    </div>

                    <div class="form-group-custom">
                        <label class="form-label">เบอร์โทรศัพท์ (ใช้เข้าสู่ระบบ) <span style="color: red;">*</span></label>
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

                    <div style="margin-top: 25px;">
                        <button type="submit" class="btn-profile-submit">
                            <i class="fas fa-save"></i> บันทึกข้อมูลส่วนตัว
                        </button>
                    </div>
                </form>
            </div>

            <!-- ฝั่งขวา: เปลี่ยนรหัสผ่านและสรุปกิจกรรม -->
            <div>
                <!-- การ์ดเปลี่ยนรหัสผ่าน -->
                <div class="profile-card" style="margin-bottom: 25px;">
                    <h3 class="profile-card-title">
                        <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                    </h3>

                    <form action="actions/profile_edit_db.php" method="POST">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group-custom">
                            <label class="form-label">รหัสผ่านปัจจุบัน <span style="color: red;">*</span></label>
                            <input type="password" name="old_password" class="form-control-custom" placeholder="กรอกรหัสผ่านปัจจุบัน" required>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label">รหัสผ่านใหม่ (อย่างน้อย 6 ตัวอักษร) <span style="color: red;">*</span></label>
                            <input type="password" name="new_password" class="form-control-custom" minlength="6" placeholder="กรอกรหัสผ่านใหม่" required>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label">ยืนยันรหัสผ่านใหม่ <span style="color: red;">*</span></label>
                            <input type="password" name="confirm_password" class="form-control-custom" minlength="6" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" required>
                        </div>

                        <div style="margin-top: 25px;">
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
                            <div class="summary-stat-val" style="color: #28a745;"><?php echo intval($stats['success_bookings'] ?? 0); ?></div>
                            <div class="summary-stat-lbl">จองสำเร็จ</div>
                        </div>
                        <div>
                            <div class="summary-stat-val" style="color: #dc3545;"><?php echo intval($stats['cancel_bookings'] ?? 0); ?></div>
                            <div class="summary-stat-lbl">ยกเลิก</div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <a href="booking_history.php" class="btn-profile-submit" style="background: #f8fafc; color: #1e3c72; border: 1px solid #cbd5e1; text-decoration: none; font-size: 13px;">
                            <i class="fas fa-history"></i> ดูประวัติการจอง
                        </a>
                        <a href="rewards.php" class="btn-profile-submit" style="background: #f8fafc; color: #1e3c72; border: 1px solid #cbd5e1; text-decoration: none; font-size: 13px;">
                            <i class="fas fa-gift"></i> แลกของรางวัล
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <script src="assets/js/member.js?v=1.5"></script>
</body>
</html>
