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
    $stmt_point = $conn->prepare("SELECT point_balance, point_total_earned, member_level FROM Point WHERE member_id = :member_id");
    $stmt_point->execute([':member_id' => $member_id]);
    $user_point = $stmt_point->fetch(PDO::FETCH_ASSOC);

    // หากยังไม่มีข้อมูลในตาราง Point ให้กำหนดค่าเริ่มต้น
    if (!$user_point) {
        $user_point = ['point_balance' => 0, 'point_total_earned' => 0, 'member_level' => 'Bronze'];
    }

    $current_points = intval($user_point['point_balance']);
    $total_earned = intval($user_point['point_total_earned'] ?? $current_points);
    $current_level = $user_point['member_level'] ?? 'Bronze';

    // แปลง Level เป็นตัวเลขเพื่อการเปรียบเทียบ
    $level_rank = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3];
    $user_rank = $level_rank[$current_level] ?? 1;

    // คำนวณความคืบหน้าสู่ระดับถัดไป (Tier Progression)
    // Bronze -> Silver (500 pts) -> Gold (1000 pts)
    $next_level_name = '';
    $points_to_next = 0;
    $progress_percent = 0;

    if ($current_level === 'Bronze') {
        $next_level_name = 'Silver';
        $points_to_next = max(0, 500 - $total_earned);
        $progress_percent = min(100, max(0, round(($total_earned / 500) * 100)));
    } elseif ($current_level === 'Silver') {
        $next_level_name = 'Gold';
        $points_to_next = max(0, 1000 - $total_earned);
        $progress_percent = min(100, max(0, round((($total_earned - 500) / 500) * 100)));
    } else {
        $next_level_name = 'Gold';
        $points_to_next = 0;
        $progress_percent = 100;
    }

    // 2. ดึงข้อมูลของรางวัลทั้งหมด
    $stmt_rewards = $conn->prepare("SELECT * FROM Reward ORDER BY FIELD(reward_min_level, 'Bronze', 'Silver', 'Gold'), reward_point_cost ASC");
    $stmt_rewards->execute();
    $rewards = $stmt_rewards->fetchAll(PDO::FETCH_ASSOC);

    // 3. ดึงประวัติการแลกของรางวัลของผู้ใช้
    $stmt_history = $conn->prepare("SELECT * FROM Point_Transaction 
                                    WHERE member_id = :member_id 
                                    AND transaction_type = 'ใช้' 
                                    AND transaction_note LIKE 'แลกของรางวัล%' 
                                    ORDER BY transaction_date DESC");
    $stmt_history->execute([':member_id' => $member_id]);
    $redemptions = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แลกของรางวัล - T.S. Pattani</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.1">
    <link rel="stylesheet" href="assets/css/style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="booking-page-body">

    <!-- นำเข้า Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="rewards-page-wrapper">
        
        <!-- แจ้งเตือนสถานะการทำรายการ -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- 1. แผงสรุปคะแนนและระดับสมาชิก (Loyalty Hero Card) -->
        <!-- ============================================== -->
        <div class="rewards-hero-card">
            <div class="rewards-hero-info">
                <h2 class="rewards-hero-title">
                    <i class="fas fa-gift"></i> สิทธิประโยชน์และของรางวัล
                </h2>
                <p class="rewards-hero-desc">
                    สะสมคะแนนจากการจองสนามและซื้อสินค้า เพื่อแลกรับของรางวัลและสิทธิพิเศษสุดคุ้ม
                </p>

                <!-- หลอดแสดงความคืบหน้าการเลื่อนระดับ (Tier Progress) -->
                <div class="loyalty-progress-container">
                    <div class="loyalty-progress-label">
                        <?php if ($current_level === 'Gold'): ?>
                            <span>ระดับสมาชิก: <strong>สูงสุดแล้ว (Gold Tier)</strong></span>
                            <span>สะสมครบ 1,000+ แต้ม</span>
                        <?php else: ?>
                            <span>เลื่อนสู่ระดับ <strong><?php echo $next_level_name; ?></strong>: อีก <strong><?php echo number_format($points_to_next); ?></strong> แต้ม</span>
                            <span><?php echo $progress_percent; ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="loyalty-progress-track">
                        <div class="loyalty-progress-fill" id="loyaltyProgressFill" data-progress="<?php echo $progress_percent; ?>"></div>
                    </div>
                </div>
            </div>

            <div class="rewards-hero-stats">
                <div class="rewards-coins-display">
                    <?php echo number_format($current_points); ?> <i class="fas fa-coins"></i>
                </div>
                <div class="rewards-level-row">
                    <span>ระดับสมาชิก:</span>
                    <span class="loyalty-badge <?php echo strtolower($current_level); ?>">
                        <i class="fas fa-medal"></i> <?php echo htmlspecialchars($current_level); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- 2. แท็บสลับมุมมอง (Catalog / My Redemptions) -->
        <!-- ============================================== -->
        <div class="rewards-tab-nav">
            <button type="button" class="rewards-tab-btn active" data-tab="tabCatalog" onclick="switchRewardsTab('tabCatalog')">
                <i class="fas fa-store"></i> ของรางวัลทั้งหมด
                <span class="rewards-tab-count"><?php echo count($rewards); ?></span>
            </button>
            <button type="button" class="rewards-tab-btn" data-tab="tabHistory" onclick="switchRewardsTab('tabHistory')">
                <i class="fas fa-history"></i> ประวัติการแลกของฉัน
                <span class="rewards-tab-count"><?php echo count($redemptions); ?></span>
            </button>
        </div>

        <!-- ============================================== -->
        <!-- 3. แท็บที่ 1: รายการของรางวัลทั้งหมด (Catalog) -->
        <!-- ============================================== -->
        <div class="rewards-tab-pane active" id="tabCatalog">
            <div class="rewards-catalog-grid">
                <?php foreach ($rewards as $r): ?>
                    <?php 
                        $req_rank = $level_rank[$r['reward_min_level']] ?? 1;
                        $cost = intval($r['reward_point_cost']);
                        $stock = intval($r['reward_stock']);

                        // วิเคราะห์สถานะความพร้อมในการแลก
                        $is_stock_out = ($stock <= 0);
                        $is_urgent_stock = ($stock > 0 && $stock <= 3);
                        $is_level_locked = ($user_rank < $req_rank);
                        $is_shortfall = (!$is_stock_out && !$is_level_locked && $current_points < $cost);
                        $can_redeem = (!$is_stock_out && !$is_level_locked && !$is_shortfall);

                        $shortfall_points = max(0, $cost - $current_points);
                        $reward_img_escaped = htmlspecialchars($r['reward_image'] ?? '', ENT_QUOTES);
                        $reward_name_escaped = htmlspecialchars($r['reward_name'], ENT_QUOTES);
                    ?>
                    <div class="reward-item-card">
                        
                        <!-- รูปภาพของรางวัลและป้ายสถานะ -->
                        <div class="reward-item-thumb-box">
                            <?php if (!empty($r['reward_image'])): ?>
                                <img src="uploads/rewards/<?php echo htmlspecialchars($r['reward_image']); ?>" alt="<?php echo $reward_name_escaped; ?>" class="reward-item-img">
                            <?php else: ?>
                                <i class="fas fa-gift reward-placeholder-icon"></i>
                            <?php endif; ?>

                            <!-- ป้ายระดับขั้นต่ำ -->
                            <span class="loyalty-badge <?php echo strtolower($r['reward_min_level']); ?> badge-tier-req">
                                <i class="fas fa-medal"></i> ขั้นต่ำ: <?php echo htmlspecialchars($r['reward_min_level']); ?>
                            </span>

                            <!-- ป้ายเตือนสินค้าใกล้หมด / สินค้าหมด -->
                            <?php if ($is_stock_out): ?>
                                <span class="badge-out-stock">
                                    <i class="fas fa-times-circle"></i> สินค้าหมด
                                </span>
                            <?php elseif ($is_urgent_stock): ?>
                                <span class="badge-urgent-stock">
                                    <i class="fas fa-fire"></i> เหลือเพียง <?php echo $stock; ?> ชิ้น!
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="reward-item-body">
                            <h4 class="reward-item-title"><?php echo htmlspecialchars($r['reward_name']); ?></h4>
                            <div class="reward-item-desc">
                                <?php echo htmlspecialchars($r['reward_description'] ?? 'ของรางวัลพิเศษสำหรับสมาชิก T.S. Pattani'); ?>
                            </div>

                            <div class="reward-item-footer">
                                <div class="reward-meta-row">
                                    <div class="reward-cost-tag">
                                        <i class="fas fa-coins"></i> <?php echo number_format($cost); ?> คะแนน
                                    </div>
                                    <div class="reward-stock-text">
                                        คงเหลือ: <strong><?php echo number_format($stock); ?></strong> ชิ้น
                                    </div>
                                </div>

                                <!-- ปุ่มดำเนินการแลกรางวัลตามเงื่อนไข (Visibility of System Status) -->
                                <?php if ($can_redeem): ?>
                                    <button type="button" class="btn-redeem-action active" onclick="openRedeemModal(<?php echo $r['reward_id']; ?>, '<?php echo $reward_name_escaped; ?>', <?php echo $cost; ?>, '<?php echo $reward_img_escaped; ?>', <?php echo $current_points; ?>)">
                                        แลกของรางวัล <i class="fas fa-arrow-right"></i>
                                    </button>
                                <?php elseif ($is_stock_out): ?>
                                    <button type="button" class="btn-redeem-action out-of-stock" disabled>
                                        <i class="fas fa-box-open"></i> สินค้าหมดชั่วคราว
                                    </button>
                                <?php elseif ($is_level_locked): ?>
                                    <button type="button" class="btn-redeem-action locked" disabled>
                                        <i class="fas fa-lock"></i> ระดับไม่ถึง (ต้องใช้ <?php echo htmlspecialchars($r['reward_min_level']); ?>)
                                    </button>
                                <?php elseif ($is_shortfall): ?>
                                    <button type="button" class="btn-redeem-action shortfall" disabled>
                                        <i class="fas fa-coins"></i> ขาดอีก <?php echo number_format($shortfall_points); ?> คะแนน
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>

                <?php if (empty($rewards)): ?>
                    <div class="rewards-empty-box">
                        <i class="fas fa-box-open rewards-empty-icon"></i>
                        <h4 class="rewards-empty-title">ขณะนี้ยังไม่มีของรางวัลให้แลก</h4>
                        <p class="rewards-empty-desc">ผู้ดูแลระบบกำลังเตรียมของรางวัลชุดใหม่ กรุณากลับมาตรวจสอบอีกครั้งในภายหลัง</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- 4. แท็บที่ 2: ประวัติการแลกของฉัน (My Redemptions) -->
        <!-- ============================================== -->
        <div class="rewards-tab-pane" id="tabHistory">
            <?php if (!empty($redemptions)): ?>
                <div class="redemptions-container">
                    <div class="redemptions-table-box">
                        <table class="redemptions-table">
                            <thead>
                                <tr>
                                    <th>วันที่ทำรายการ</th>
                                    <th>รหัสอ้างอิงการแลก</th>
                                    <th>รายการของรางวัล</th>
                                    <th>คะแนนที่ใช้</th>
                                    <th>สถานะการรับของ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($redemptions as $rdm): ?>
                                    <?php 
                                        $note_clean = str_replace('แลกของรางวัล: ', '', $rdm['transaction_note']);
                                        $date_formatted = date('d/m/Y H:i น.', strtotime($rdm['transaction_date']));
                                        $ref_code = 'RDM-' . str_pad($rdm['transaction_id'], 5, '0', STR_PAD_LEFT);
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="far fa-calendar-alt text-muted"></i> <?php echo $date_formatted; ?>
                                        </td>
                                        <td>
                                            <span class="badge-rdm-code">#<?php echo $ref_code; ?></span>
                                        </td>
                                        <td>
                                            <strong><i class="fas fa-gift text-primary"></i> <?php echo htmlspecialchars($note_clean); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge-rdm-points">- <?php echo number_format($rdm['transaction_point']); ?> คะแนน</span>
                                        </td>
                                        <td>
                                            <span class="badge-rdm-status">
                                                <i class="fas fa-store"></i> รับได้ที่เคาน์เตอร์สนาม
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="rewards-empty-box">
                    <i class="fas fa-receipt rewards-empty-icon"></i>
                    <h4 class="rewards-empty-title">คุณยังไม่เคยมีประวัติการแลกของรางวัล</h4>
                    <p class="rewards-empty-desc">สะสมคะแนนจากการจองสนามและเลือกของรางวัลที่คุณชื่นชอบได้ทันที</p>
                    <button type="button" class="btn-empty-action" onclick="switchRewardsTab('tabCatalog')">
                        <i class="fas fa-gift"></i> ไปเลือกดูของรางวัล
                    </button>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ============================================== -->
    <!-- 5. Modal ยืนยันการแลกของรางวัล (Custom Modal) -->
    <!-- ============================================== -->
    <div id="rewardConfirmModal" class="redeem-modal-overlay">
        <div class="redeem-modal-card">
            
            <div class="redeem-modal-header">
                <i class="fas fa-gift fa-lg text-primary"></i>
                <h4>ยืนยันการแลกของรางวัล</h4>
            </div>

            <!-- กล่องพรีวิวของรางวัล -->
            <div class="redeem-preview-box">
                <img id="modal_reward_img" src="" alt="Reward Preview" class="redeem-preview-thumb">
                <div id="modal_reward_placeholder" class="redeem-preview-thumb redeem-preview-placeholder">
                    <i class="fas fa-gift fa-2x"></i>
                </div>
                <div class="redeem-preview-info">
                    <div class="redeem-preview-title" id="modal_reward_title">-</div>
                    <div class="redeem-preview-cost" id="modal_reward_cost">0 คะแนน</div>
                </div>
            </div>

            <!-- ตารางสรุปการหักคะแนน -->
            <div class="redeem-calc-table">
                <div class="redeem-calc-row">
                    <span>คะแนนสะสมปัจจุบัน:</span>
                    <strong id="modal_current_points">0 คะแนน</strong>
                </div>
                <div class="redeem-calc-row">
                    <span>คะแนนที่ใช้แลกครั้งนี้:</span>
                    <strong class="text-danger" id="modal_deduct_points">- 0 คะแนน</strong>
                </div>
                <div class="redeem-calc-row final">
                    <span>คะแนนคงเหลือหลังแลก:</span>
                    <strong id="modal_remain_points">0 คะแนน</strong>
                </div>
            </div>

            <!-- ข้อความแนะนำการรับของรางวัล -->
            <div class="redeem-note-banner">
                <i class="fas fa-info-circle fa-lg"></i>
                <div>
                    <strong>คำแนะนำการรับของรางวัล:</strong><br>
                    เมื่อยืนยัน ระบบจะตัดคะแนนทันที สามารถแสดงรหัสอ้างอิงเพื่อรับของรางวัลได้ที่เคาน์เตอร์สนาม T.S. Pattani
                </div>
            </div>

            <!-- ฟอร์มส่งไป actions/redeem_reward_db.php -->
            <form action="actions/redeem_reward_db.php" method="POST" id="redeemRewardForm">
                <input type="hidden" name="reward_id" id="modal_reward_id" value="">
                
                <div class="member-modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeRedeemModal()">
                        ยกเลิก
                    </button>
                    <button type="button" class="btn-submit-confirm" id="btnModalConfirmRedeem" onclick="executeRedeemSubmit()">
                        ยืนยันการแลกรางวัล <i class="fas fa-check"></i>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- เรียกใช้ JavaScript -->
    <script src="assets/js/member.js?v=2.1"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ปรับความกว้างของหลอดระดับสมาชิกตาม Data Attribute
            let pFill = document.getElementById('loyaltyProgressFill');
            if (pFill && pFill.dataset.progress) {
                pFill.style.width = pFill.dataset.progress + '%';
            }

            let rModal = document.getElementById('rewardConfirmModal');
            if (rModal) {
                // ปิด Modal เมื่อคลิกด้านนอก
                rModal.addEventListener('click', function(e) {
                    if (e.target === rModal) closeRedeemModal();
                });
            }
            // ปิด Modal เมื่อกด ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeRedeemModal();
            });
        });
    </script>
</body>
</html>
