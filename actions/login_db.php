<?php
session_start();
// เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล
require_once '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 0. ตรวจสอบความปลอดภัย CSRF Token
    require_csrf_token();
    
    // 1. รับค่าและทำความสะอาดข้อมูล (ตัดช่องว่างหน้า-หลัง)
    $member_phone = trim($_POST['member_phone'] ?? '');
    $password = $_POST['member_password'] ?? '';
    $remember_me = !empty($_POST['remember_me']);

    try {
        // 2. ตรวจสอบว่ากรอกข้อมูลมาครบหรือไม่
        if (empty($member_phone) || empty($password)) {
            throw new Exception("กรุณากรอกเบอร์โทรศัพท์และรหัสผ่านให้ครบถ้วน");
        }

        // 2.1 ตรวจสอบ Rate Limiting ป้องกัน Brute-Force โจมตีรหัสผ่าน
        $throttle = check_login_throttle($conn, $member_phone, 'Member');
        if (!$throttle['allowed']) {
            throw new Exception($throttle['message']);
        }

        // 3. ค้นหาข้อมูลสมาชิกจากเบอร์โทรศัพท์ด้วย Prepared Statement
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

                // ปลดและล้างประวัติการพยายามล็อกอินผิดพลาด (เมื่อรหัสผ่านถูกต้อง)
                clear_login_throttle($conn, $member_phone, 'Member');

                // 7. Regenerate Session ID ป้องกัน Session Fixation
                session_regenerate_id(true);

                // 8. ถ้ารหัสถูกและสถานะปกติ -> สร้าง Session เก็บข้อมูลผู้ใช้
                $_SESSION['member_id'] = $member['member_id'];
                $_SESSION['member_name'] = $member['member_name'];
                $_SESSION['member_phone'] = $member['member_phone'];
                $_SESSION['member_avatar'] = $member['member_avatar'] ?? null;

                // บันทึกกิจกรรมการเข้าสู่ระบบสำเร็จ
                log_activity($conn, 'MEMBER_LOGIN', 'Member', $member['member_id'], 'เข้าสู่ระบบสำเร็จผ่านเบอร์ ' . $member_phone, 'Member', $member['member_id'], $member['member_name']);

                // 9. จัดการฟีเจอร์จดจำเบอร์โทรศัพท์ (Remember Me)
                if ($remember_me) {
                    setcookie('remember_phone', $member_phone, [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'samesite' => 'Lax'
                    ]);
                } else {
                    if (isset($_COOKIE['remember_phone'])) {
                        setcookie('remember_phone', '', [
                            'expires' => time() - 3600,
                            'path' => '/',
                            'samesite' => 'Lax'
                        ]);
                    }
                }

                // ล้างค่าฟอร์มค้าง (ถ้ามี)
                unset($_SESSION['form_phone']);

                // ตรวจสอบปลายทางที่ต้องการให้ Redirect กลับไป
                $redirect_url = "../index.php";
                $redirect_param = trim($_POST['redirect'] ?? '');
                if ($redirect_param === 'booking.php' || strpos($redirect_param, 'booking.php') === 0 || isset($_SESSION['pending_booking'])) {
                    $redirect_url = "../booking.php";
                }

                header("Location: " . $redirect_url);
                exit();

            } else {
                // รหัสผ่านผิด บันทึกการพยายามเข้าสู่ระบบล้มเหลว
                $fail = record_login_failure($conn, $member_phone, 'Member', 'รหัสผ่านสมาชิกไม่ถูกต้อง');
                throw new Exception($fail['message']);
            }
        } else {
            // ไม่พบเบอร์โทรนี้ในระบบ บันทึกการพยายามเข้าสู่ระบบล้มเหลว
            $fail = record_login_failure($conn, $member_phone, 'Member', 'ไม่พบเบอร์โทรศัพท์สมาชิกในระบบ');
            throw new Exception($fail['message']);
        }

    } catch (Exception $e) {
        // หากมี Error ให้เก็บเบอร์เดิมไว้ช่วยให้ผู้ใช้ไม่ต้องพิมพ์ใหม่
        $_SESSION['error'] = $e->getMessage();
        if (!empty($member_phone)) {
            $_SESSION['form_phone'] = $member_phone;
        }
        $redirect_param = trim($_POST['redirect'] ?? '');
        $fallback_login = "../login.php" . (!empty($redirect_param) ? "?redirect=" . urlencode($redirect_param) : "");
        header("Location: " . $fallback_login);
        exit();
    }

} else {
    // ป้องกันคนพิมพ์ URL เข้ามาหน้านี้โดยตรง
    header("Location: ../login.php");
    exit();
}
?>