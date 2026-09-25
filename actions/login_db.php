<?php
session_start();
// เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล
require_once '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าและทำความสะอาดข้อมูล (ตัดช่องว่างหน้า-หลัง)
    $member_phone = trim($_POST['member_phone']);
    $password = $_POST['member_password'];

    try {
        // 2. ตรวจสอบว่ากรอกข้อมูลมาครบหรือไม่
        if (empty($member_phone) || empty($password)) {
            throw new Exception("กรุณากรอกเบอร์โทรศัพท์และรหัสผ่านให้ครบถ้วน");
        }

        // 3. ค้นหาข้อมูลสมาชิกจากเบอร์โทรศัพท์
        $stmt = $conn->prepare("SELECT * FROM Member WHERE member_phone = :phone");
        $stmt->execute([':phone' => $member_phone]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. ตรวจสอบว่าพบข้อมูลหรือไม่
        if ($member) {
            
            // 5. นำรหัสผ่านที่กรอกมา เทียบกับรหัสผ่านที่เข้ารหัสไว้ในฐานข้อมูล
            if (password_verify($password, $member['member_password'])) {
                
                // 6. ตรวจสอบสถานะการใช้งาน (เผื่อแอดมินระงับสิทธิ์สมาชิกที่ทำผิดกฎ)
                if ($member['member_status'] === 'ระงับสิทธิ์') {
                    throw new Exception("บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ");
                }

                // 7. ถ้ารหัสถูกและสถานะปกติ -> สร้าง Session เก็บข้อมูลผู้ใช้
                $_SESSION['member_id'] = $member['member_id'];
                $_SESSION['member_name'] = $member['member_name'];
                $_SESSION['member_phone'] = $member['member_phone'];

                // ล็อกอินสำเร็จ ส่งกลับไปที่หน้าแรกของฝั่งลูกค้า
                header("Location: ../index.php");
                exit();

            } else {
                // รหัสผ่านผิด
                throw new Exception("เบอร์โทรศัพท์หรือรหัสผ่านไม่ถูกต้อง");
            }
        } else {
            // ไม่พบเบอร์โทรนี้ในระบบ
            throw new Exception("เบอร์โทรศัพท์หรือรหัสผ่านไม่ถูกต้อง");
        }

    } catch (Exception $e) {
        // หากมี Error (รหัสผิด, ข้อมูลไม่ครบ, ถูกระงับสิทธิ์) ให้ส่งข้อความแจ้งเตือนกลับไปที่หน้า login
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../login.php");
        exit();
    }

} else {
    // ป้องกันคนพิมพ์ URL เข้ามาหน้านี้โดยตรง
    header("Location: ../login.php");
    exit();
}
?>