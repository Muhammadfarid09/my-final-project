<?php
/**
 * T.S. Pattani Badminton - Member Authentication & Session Guard
 * ตรวจสอบความถูกต้องของเซสชันสมาชิก และตรวจสอบสถานะบัญชีแบบ Real-time
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

if (!function_exists('check_member_auth')) {
    function check_member_auth(bool $is_ajax = false): void {
        global $conn;

        $login_path = file_exists('login.php') ? 'login.php' : (file_exists('../login.php') ? '../login.php' : '/ts-pattani/login.php');

        // 1. ตรวจสอบการมีอยู่ของ Session สมาชิก
        if (!isset($_SESSION['member_id']) || empty($_SESSION['member_id'])) {
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนทำรายการ']);
                exit();
            }
            $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนเข้าใช้งาน";
            header("Location: " . $login_path);
            exit();
        }

        // 2. ตรวจสอบสถานะบัญชีจากฐานข้อมูลแบบ Real-time (ป้องกันกรณีแอดมินเพิ่งระงับสิทธิ์)
        if (isset($conn)) {
            try {
                $stmt_check = $conn->prepare("SELECT member_status FROM Member WHERE member_id = :mid");
                $stmt_check->execute([':mid' => $_SESSION['member_id']]);
                $status = $stmt_check->fetchColumn();

                if (!$status || $status === 'ระงับสิทธิ์') {
                    unset($_SESSION['member_id'], $_SESSION['member_name'], $_SESSION['member_phone']);
                    if ($is_ajax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['status' => 'error', 'message' => 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ']);
                        exit();
                    }
                    $_SESSION['error'] = "บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ";
                    header("Location: " . $login_path);
                    exit();
                }
            } catch (PDOException $e) {
                // หากเชื่อมต่อ DB ล้มเหลว ปล่อยให้ Error handler ระดับบนจัดการ
            }
        }
    }
}

// ตรวจสอบประเภทคำขอ หากเป็น AJAX หรือเรียกแบบ API
$is_api_request = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == '1')
    || (defined('IS_AJAX_AUTH') && IS_AJAX_AUTH === true);

// รันการตรวจสอบทันทีเมื่อ include ไฟล์นี้
check_member_auth($is_api_request);
