<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$member_id = intval($_SESSION['member_id']);
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

try {
    $stmt = $conn->prepare("
        SELECT chat_id, chat_sender, chat_message, chat_datetime 
        FROM Chat 
        WHERE member_id = :mid AND chat_id > :last_id 
        ORDER BY chat_datetime ASC
    ");
    $stmt->execute([':mid' => $member_id, ':last_id' => $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as &$msg) {
        $msg['chat_datetime_formatted'] = date('d/m/Y H:i', strtotime($msg['chat_datetime']));
        $msg['chat_message_escaped'] = nl2br(htmlspecialchars($msg['chat_message']));
    }

    echo json_encode(['status' => 'success', 'data' => $messages]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
