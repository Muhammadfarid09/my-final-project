<?php
session_start();
require_once 'config/config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit();
}

$member_id = $_SESSION['member_id'];

try {
    // 1. ดึงข้อมูลคะแนนสะสมและระดับสมาชิกของลูกค้า
    $stmt_point = $conn->prepare("SELECT point_balance, member_level FROM Point WHERE member_id = :member_id");
    $stmt_point->execute([':member_id' => $member_id]);
    $user_point = $stmt_point->fetch(PDO::FETCH_ASSOC);

    // หากยังไม่มีข้อมูลในตาราง Point ให้กำหนดค่าเริ่มต้น
    if (!$user_point) {
        $user_point = ['point_balance' => 0, 'member_level' => 'Bronze'];
    }

    $current_points = $user_point['point_balance'];
    $current_level = $user_point['member_level'];

    // แปลง Level เป็นตัวเลขเพื่อการเปรียบเทียบ
    $level_rank = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3];
    $user_rank = $level_rank[$current_level];

    // 2. ดึงข้อมูลของรางวัลทั้งหมด
    $stmt_rewards = $conn->prepare("SELECT * FROM Reward ORDER BY reward_point_cost ASC");
    $stmt_rewards->execute();
    $rewards = $stmt_rewards->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แลกของรางวัล - T.S. Pattani</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .reward-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .reward-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }
        .reward-card:hover {
            transform: translateY(-5px);
        }
        .reward-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f8f9fa;
        }
        .reward-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .reward-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .reward-desc {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        .badge-level {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 10px;
        }
        .badge-bronze { background: #cd7f32; }
        .badge-silver { background: #c0c0c0; color: #333;}
        .badge-gold { background: #ffd700; color: #333;}
        
        .reward-cost {
            font-size: 16px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 15px;
        }
        .btn-redeem {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-redeem.active {
            background: #007bff;
            color: white;
        }
        .btn-redeem.active:hover {
            background: #0056b3;
        }
        .btn-redeem.disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }
        .user-point-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body style="background-color: #f4f6f9;">

    <?php include 'includes/navbar.php'; ?>

    <div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
        
        <!-- แจ้งเตือน -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- แผงแสดงคะแนนผู้ใช้ -->
        <div class="user-point-card">
            <div>
                <h2 style="margin: 0; font-size: 24px;">คะแนนสะสมของคุณ</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">สามารถนำไปแลกของรางวัลที่ต้องการได้</p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 36px; font-weight: bold; color: #ffd700;">
                    <?php echo number_format($current_points); ?> <i class="fas fa-coins"></i>
                </div>
                <div style="font-size: 16px;">
                    ระดับสมาชิก: 
                    <span class="badge-level badge-<?php echo strtolower($current_level); ?>" style="margin: 0;">
                        <?php echo htmlspecialchars($current_level); ?>
                    </span>
                </div>
            </div>
        </div>

        <h3 style="border-bottom: 2px solid #ddd; padding-bottom: 10px;">รายการของรางวัล (Rewards)</h3>

        <div class="reward-grid">
            <?php foreach ($rewards as $r): ?>
                <?php 
                    $req_rank = $level_rank[$r['reward_min_level']];
                    
                    // เช็คเงื่อนไขการแลก
                    $can_redeem = true;
                    $disable_reason = "";

                    if ($r['reward_stock'] <= 0) {
                        $can_redeem = false;
                        $disable_reason = "สินค้าหมด";
                    } elseif ($user_rank < $req_rank) {
                        $can_redeem = false;
                        $disable_reason = "ระดับสมาชิกไม่ถึง";
                    } elseif ($current_points < $r['reward_point_cost']) {
                        $can_redeem = false;
                        $disable_reason = "คะแนนไม่พอ";
                    }
                ?>
                <div class="reward-card">
                    <!-- รูปภาพของรางวัล -->
                    <?php if (!empty($r['reward_image'])): ?>
                        <img src="uploads/rewards/<?php echo htmlspecialchars($r['reward_image']); ?>" alt="Reward Image" class="reward-img">
                    <?php else: ?>
                        <div class="reward-img" style="display: flex; align-items: center; justify-content: center; color: #ccc;">
                            <i class="fas fa-gift fa-4x"></i>
                        </div>
                    <?php endif; ?>

                    <div class="reward-body">
                        <span class="badge-level badge-<?php echo strtolower($r['reward_min_level']); ?>">
                            ขั้นต่ำ: <?php echo htmlspecialchars($r['reward_min_level']); ?>
                        </span>
                        
                        <div class="reward-title"><?php echo htmlspecialchars($r['reward_name']); ?></div>
                        <div class="reward-desc"><?php echo htmlspecialchars($r['reward_description']); ?></div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <div class="reward-cost">
                                <i class="fas fa-coins" style="color:#ffd700;"></i> <?php echo number_format($r['reward_point_cost']); ?> คะแนน
                            </div>
                            <div style="font-size: 13px; color: #666;">
                                คงเหลือ: <?php echo $r['reward_stock']; ?> ชิ้น
                            </div>
                        </div>

                        <!-- ฟอร์มแลกของรางวัล -->
                        <form action="actions/redeem_reward_db.php" method="POST" onsubmit="return confirm('ยืนยันการแลกของรางวัลนี้ด้วย <?php echo number_format($r['reward_point_cost']); ?> คะแนนใช่หรือไม่?');">
                            <input type="hidden" name="reward_id" value="<?php echo $r['reward_id']; ?>">
                            
                            <?php if ($can_redeem): ?>
                                <button type="submit" class="btn-redeem active">แลกรางวัลเลย</button>
                            <?php else: ?>
                                <button type="button" class="btn-redeem disabled" disabled>
                                    <?php echo $disable_reason; ?>
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($rewards)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff; border-radius: 10px; color: #666;">
                    <i class="fas fa-box-open fa-3x" style="margin-bottom: 10px;"></i>
                    <p>ขณะนี้ยังไม่มีของรางวัลให้แลก</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- เรียกใช้ JS หลัก -->
    <script src="assets/js/member.js?v=1.4"></script>
</body>
</html>

