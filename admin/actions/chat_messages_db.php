<?php
session_start();
require_once '../../config/config.php';

// คืนค่าเป็น JSON
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

if ($member_id > 0) {
    try {
        // ดึงเฉพาะแชทที่ใหม่กว่า last_id
        $stmt = $conn->prepare("SELECT chat_id, chat_sender, chat_message, chat_datetime 
                                FROM Chat 
                                WHERE member_id = :mid AND chat_id > :last_id 
                                ORDER BY chat_datetime ASC");
        $stmt->execute([':mid' => $member_id, ':last_id' => $last_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // จัด format วันที่ให้เรียบร้อยก่อนส่งให้ JS
        foreach ($messages as &$msg) {
            $msg['chat_datetime_formatted'] = date('d/m/Y H:i', strtotime($msg['chat_datetime']));
            // Escape HTML สำหรับ message
            $msg['chat_message'] = nl2br(htmlspecialchars($msg['chat_message']));
        }

        echo json_encode(['status' => 'success', 'data' => $messages]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid member_id']);
}
?>
