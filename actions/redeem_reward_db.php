<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $member_id = $_SESSION['member_id'];
    $reward_id = intval($_POST['reward_id']);

    try {
        $conn->beginTransaction();

        // 1. ดึงข้อมูลของรางวัล (และ Lock แถวนี้ไว้เพื่อป้องกัน Concurrency หรือแย่งกันแลก)
        $stmt_reward = $conn->prepare("SELECT * FROM Reward WHERE reward_id = :id FOR UPDATE");
        $stmt_reward->execute([':id' => $reward_id]);
        $reward = $stmt_reward->fetch(PDO::FETCH_ASSOC);

        if (!$reward) {
            throw new Exception("ไม่พบข้อมูลของรางวัลนี้");
        }

        // 2. ดึงข้อมูลคะแนนสะสมและระดับสมาชิก (Lock แถวเช่นกัน)
        $stmt_point = $conn->prepare("SELECT point_balance, member_level FROM Point WHERE member_id = :m_id FOR UPDATE");
        $stmt_point->execute([':m_id' => $member_id]);
        $user_point = $stmt_point->fetch(PDO::FETCH_ASSOC);

        if (!$user_point) {
            throw new Exception("คุณยังไม่มีข้อมูลคะแนนสะสมในระบบ");
        }

        // ============================================
        // 3. ตรวจสอบเงื่อนไขทั้งหมด
        // ============================================
        
        // เช็คสต็อก
        if ($reward['reward_stock'] <= 0) {
            throw new Exception("ขออภัย สินค้านี้หมดแล้ว");
        }

        // เช็คระดับสมาชิก (Bronze=1, Silver=2, Gold=3)
        $level_rank = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3];
        $user_rank = $level_rank[$user_point['member_level']];
        $req_rank = $level_rank[$reward['reward_min_level']];

        if ($user_rank < $req_rank) {
            throw new Exception("ระดับสมาชิกของคุณไม่ถึงเงื่อนไขของรางวัลนี้ (ขั้นต่ำ: {$reward['reward_min_level']})");
        }

        // เช็คคะแนนคงเหลือ
        if ($user_point['point_balance'] < $reward['reward_point_cost']) {
            throw new Exception("คะแนนสะสมของคุณไม่เพียงพอสำหรับการแลกรางวัลนี้");
        }

        // ============================================
        // 4. บันทึกข้อมูลการแลก
        // ============================================
        $cost = $reward['reward_point_cost'];

        // 4.1 ตัดสต็อกของรางวัล
        $stmt_deduct_stock = $conn->prepare("UPDATE Reward SET reward_stock = reward_stock - 1 WHERE reward_id = :id");
        $stmt_deduct_stock->execute([':id' => $reward_id]);

        // 4.2 หักคะแนนผู้ใช้
        $stmt_deduct_point = $conn->prepare("UPDATE Point SET point_balance = point_balance - :cost WHERE member_id = :m_id");
        $stmt_deduct_point->execute([':cost' => $cost, ':m_id' => $member_id]);

        // 4.3 บันทึกประวัติลง Point_Transaction
        $note = "แลกของรางวัล: " . $reward['reward_name'];
        $stmt_trans = $conn->prepare("INSERT INTO Point_Transaction (member_id, transaction_type, transaction_point, transaction_note) 
                                      VALUES (:m_id, 'ใช้', :cost, :note)");
        $stmt_trans->execute([
            ':m_id' => $member_id,
            ':cost' => $cost,
            ':note' => $note
        ]);

        $conn->commit();

        $_SESSION['success'] = "แลกของรางวัล '{$reward['reward_name']}' สำเร็จแล้ว! คุณสามารถติดต่อรับของได้ที่หน้าเคาน์เตอร์สนาม";
        header("Location: ../rewards.php");
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../rewards.php");
        exit();
    }

} else {
    header("Location: ../rewards.php");
    exit();
}
?>
