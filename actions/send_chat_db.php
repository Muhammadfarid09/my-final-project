<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $member_id = $_SESSION['member_id'];
    $message = trim($_POST['chat_message']);

    if (!empty($message)) {
        try {
            $stmt = $conn->prepare("INSERT INTO Chat (member_id, chat_message, chat_sender) VALUES (:m_id, :msg, 'สมาชิก')");
            $stmt->execute([
                ':m_id' => $member_id,
                ':msg' => $message
            ]);
        } catch (PDOException $e) {
            // บันทึก Error Log ถ้าจำเป็น
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
