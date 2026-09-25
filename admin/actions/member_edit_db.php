<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. รับค่าจากฟอร์ม
    $member_id = intval($_POST['member_id']);
    $member_name = trim($_POST['member_name']);
    $member_phone = trim($_POST['member_phone']);
    $member_gender = $_POST['member_gender'];
    // เช็คค่าอายุ ถ้าไม่กรอกให้เป็นค่าว่าง (NULL)
    $member_age = !empty($_POST['member_age']) ? intval($_POST['member_age']) : null;
    $member_occupation = trim($_POST['member_occupation']);
    $member_status = $_POST['member_status'];
    
    // รับค่าส่วนของระบบจัดการ (จะเอาไปลงตาราง Point)
    $new_points = intval($_POST['member_points']);
    $new_level = $_POST['member_level'];

    try {
        // ตรวจสอบข้อมูลเบื้องต้น
        if (empty($member_id) || empty($member_name) || empty($member_phone)) {
            throw new Exception("กรุณากรอกข้อมูลที่จำเป็น (ชื่อและเบอร์โทร) ให้ครบถ้วน");
        }

        // เริ่มต้น Transaction (ป้องกันข้อมูลอัปเดตไม่ครบ)
        $conn->beginTransaction();

        // ----------------------------------------------------
        // ส่วนที่ 1: อัปเดตข้อมูลส่วนตัวลงตาราง Member
        // ----------------------------------------------------
        $sql_member = "UPDATE Member 
                       SET member_name = :name, 
                           member_phone = :phone, 
                           member_gender = :gender, 
                           member_age = :age, 
                           member_occupation = :occupation, 
                           member_status = :status 
                       WHERE member_id = :id";
        
        $stmt_member = $conn->prepare($sql_member);
        $stmt_member->execute([
            ':name' => $member_name,
            ':phone' => $member_phone,
            ':gender' => $member_gender,
            ':age' => $member_age,
            ':occupation' => $member_occupation,
            ':status' => $member_status,
            ':id' => $member_id
        ]);

        // ----------------------------------------------------
        // ส่วนที่ 2: จัดการแต้มและระดับสมาชิก ลงตาราง Point
        // ----------------------------------------------------
        
        // เช็คก่อนว่ามีข้อมูลของคนนี้ในตาราง Point หรือยัง
        $stmt_check = $conn->prepare("SELECT point_balance FROM Point WHERE member_id = :id");
        $stmt_check->execute([':id' => $member_id]);
        $existing_point = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing_point) {
            // กรณีมีข้อมูลแล้ว -> ทำการ UPDATE
            $old_points = $existing_point['point_balance'];
            
            $sql_point = "UPDATE Point 
                          SET point_balance = :pts, 
                              member_level = :lvl 
                          WHERE member_id = :id";
            $conn->prepare($sql_point)->execute([
                ':pts' => $new_points,
                ':lvl' => $new_level,
                ':id' => $member_id
            ]);

            // ถ้าแอดมินแก้ตัวเลขแต้มไม่ตรงกับของเดิม ให้บันทึกประวัติด้วย
            if ($old_points != $new_points) {
                // หาผลต่าง (อาจจะติดลบถ้าแอดมินหักแต้ม)
                $diff = $new_points - $old_points; 
                
                $sql_trans = "INSERT INTO Point_Transaction (member_id, transaction_type, transaction_point, transaction_note) 
                              VALUES (:id, 'ปรับโดย Admin', :diff, 'ผู้ดูแลระบบแก้ไขข้อมูลแต้มสะสมจากหน้าระบบสมาชิก')";
                $conn->prepare($sql_trans)->execute([
                    ':id' => $member_id,
                    ':diff' => $diff
                ]);
            }

        } else {
            // กรณีเป็นสมาชิกใหม่มากๆ ยังไม่เคยมีตาราง Point -> ทำการ INSERT
            $sql_point = "INSERT INTO Point (member_id, point_balance, point_total_earned, member_level) 
                          VALUES (:id, :pts, :pts, :lvl)";
            $conn->prepare($sql_point)->execute([
                ':id' => $member_id,
                ':pts' => $new_points,
                ':lvl' => $new_level
            ]);
            
            // บันทึกประวัติเริ่มต้นถ้าแต้มมากกว่า 0
            if ($new_points > 0) {
                $sql_trans = "INSERT INTO Point_Transaction (member_id, transaction_type, transaction_point, transaction_note) 
                              VALUES (:id, 'ปรับโดย Admin', :pts, 'ผู้ดูแลระบบเพิ่มข้อมูลแต้มเริ่มต้น')";
                $conn->prepare($sql_trans)->execute([
                    ':id' => $member_id,
                    ':pts' => $new_points
                ]);
            }
        }

        // ยืนยันการเปลี่ยนแปลงทั้งหมดลงฐานข้อมูล
        $conn->commit();

        $_SESSION['success'] = "อัปเดตข้อมูลของ " . htmlspecialchars($member_name) . " เรียบร้อยแล้ว";

    } catch(Exception $e) {
        // หากเกิด Error ตรงไหนก็ตาม ให้ยกเลิก (Rollback) ข้อมูลจะไม่พัง
        $conn->rollBack();
        
        // เช็คเผื่อ Error เกิดจากเบอร์โทรซ้ำ (Duplicate Entry)
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: เบอร์โทรศัพท์นี้ถูกใช้งานโดยสมาชิกท่านอื่นแล้ว";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }

    // กลับไปหน้าแสดงรายการสมาชิก
    header("Location: ../members.php");
    exit();

} else {
    header("Location: ../members.php");
    exit();
}
?>