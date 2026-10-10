<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

$login_path = file_exists('login.php') ? 'login.php' : (file_exists('../login.php') ? '../login.php' : '/ts-pattani/admin/login.php');

if (!isset($_SESSION['admin_id'])) {
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || (defined('IS_AJAX_AUTH') && IS_AJAX_AUTH === true);

    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit();
    }
    header("Location: " . $login_path);
    exit();
}
?>

