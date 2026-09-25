<?php
session_start();
// เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล (ถอยกลับ 1 โฟลเดอร์เพื่อเข้าหาโฟลเดอร์ config)
require_once '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าและทำความสะอาดข้อมูลที่ส่งมาจากฟอร์ม
    $member_name = trim($_POST['member_name']);
    $member_phone = trim($_POST['member_phone']);
    $member_gender = $_POST['member_gender'] ?? 'อื่นๆ';
    // เช็คค่าอายุ ถ้าไม่กรอก ให้บันทึกเป็น NULL
    $member_age = !empty($_POST['member_age']) ? intval($_POST['member_age']) : null;
    $member_occupation = trim($_POST['member_occupation']);
    
    $password = $_POST['member_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // 2. ตรวจสอบความสมบูรณ์ของข้อมูลเบื้องต้น (Backend Validation)
        if (empty($member_name) || empty($member_phone) || empty($password)) {
            throw new Exception("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน");
        }

        if (!preg_match('/^[0-9]{10}$/', $member_phone)) {
            throw new Exception("รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง กรุณากรอกตัวเลข 10 หลัก");
        }

        if ($password !== $confirm_password) {
            throw new Exception("รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน");
        }

        // 3. ตรวจสอบเบอร์โทรซ้ำในระบบ
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM Member WHERE member_phone = :phone");
        $stmt_check->execute([':phone' => $member_phone]);
        if ($stmt_check->fetchColumn() > 0) {
            throw new Exception("เบอร์โทรศัพท์นี้ถูกใช้งานในระบบแล้ว กรุณาเข้าสู่ระบบ หรือใช้เบอร์โทรอื่น");
        }

        // 4. เข้ารหัสผ่าน (Password Hashing) เพื่อความปลอดภัยสูงสุด
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ==========================================
        // เริ่มกระบวนการบันทึกข้อมูล (Transaction)
        // ==========================================
        $conn->beginTransaction();

        // 5. บันทึกข้อมูลลงตาราง Member
        $sql_member = "INSERT INTO Member (member_name, member_phone, member_gender, member_age, member_occupation, member_password, member_status) 
                       VALUES (:name, :phone, :gender, :age, :occupation, :password, 'ปกติ')";
        
        $stmt_member = $conn->prepare($sql_member);
        $stmt_member->execute([
            ':name' => $member_name,
            ':phone' => $member_phone,
            ':gender' => $member_gender,
            ':age' => $member_age,
            ':occupation' => $member_occupation,
            ':password' => $hashed_password
        ]);

        // ดึงค่า ID ของสมาชิกใหม่ที่เพิ่งถูกเพิ่มเข้าไป
        $new_member_id = $conn->lastInsertId();

        // 6. เปิดบัญชี Point ให้ลูกค้าอัตโนมัติ ลงตาราง Point (เริ่มต้นที่ 0 พอยท์ ระดับ Bronze)
        $sql_point = "INSERT INTO Point (member_id, point_balance, point_total_earned, member_level) 
                      VALUES (:member_id, 0, 0, 'Bronze')";
        
        $stmt_point = $conn->prepare($sql_point);
        $stmt_point->execute([
            ':member_id' => $new_member_id
        ]);

        // 7. ยืนยันการบันทึก (Commit)
        $conn->commit();

        // สมัครสำเร็จ พากลับไปหน้าเข้าสู่ระบบ พร้อมข้อความแจ้งเตือนสีเขียว
        $_SESSION['success'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบด้วยเบอร์โทรศัพท์ของคุณ";
        header("Location: ../login.php");
        exit();

    } catch (Exception $e) {
        // หากเกิด Error ตรงขั้นตอนไหนก็ตาม ให้ยกเลิกการกระทำทั้งหมดทันที (Rollback)
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // ส่งข้อความ Error กลับไปแสดงที่หน้าสมัครสมาชิก
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../register.php");
        exit();
    }

} else {
    // ถ้ามีคนพยายามเข้าหน้านี้ตรงๆ โดยไม่ผ่านฟอร์ม ให้เด้งกลับ
    header("Location: ../register.php");
    exit();
}
?>