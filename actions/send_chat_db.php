<?php
session_start();
require_once '../config/config.php';

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

if (!isset($_SESSION['member_id'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit();
    }
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $member_id = intval($_SESSION['member_id']);
    $message = trim($_POST['chat_message'] ?? '');

    if (!empty($message)) {
        try {
            $stmt = $conn->prepare("INSERT INTO Chat (member_id, chat_message, chat_sender, chat_datetime) VALUES (:m_id, :msg, 'สมาชิก', NOW())");
            $stmt->execute([
                ':m_id' => $member_id,
                ':msg' => $message
            ]);
            $new_chat_id = $conn->lastInsertId();

            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'chat_id' => $new_chat_id,
                    'chat_datetime_formatted' => date('d/m/Y H:i'),
                    'chat_message_escaped' => nl2br(htmlspecialchars($message))
                ]);
                exit();
            }
        } catch (PDOException $e) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit();
            }
        }
    }
    
    // กลับไปหน้าแชท
    header("Location: ../chat.php");
    exit();
} else {
    header("Location: ../chat.php");
    exit();
}
?>
