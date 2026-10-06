<?php
/**
 * CSRF Protection Helper for T.S. Pattani
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('get_csrf_token')) {
    function get_csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        $token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token(?string $token): bool {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('require_csrf_token')) {
    function require_csrf_token(): void {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!validate_csrf_token($token)) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'CSRF Token ไม่ถูกต้องหรือเซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง']);
                exit();
            }
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['error'] = 'คำขอไม่ถูกต้องหรือเซสชันหมดอายุ (CSRF Token ไม่ถูกต้อง) กรุณาลองใหม่อีกครั้ง';
            $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
            header("Location: $referer");
            exit();
        }
    }
}
?>
