<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit();
}

$member_id = $_SESSION['member_id'];

// ดึงข้อความแชทของสมาชิกคนนี้
$stmt = $conn->prepare("SELECT * FROM Chat WHERE member_id = :member_id ORDER BY chat_datetime ASC");
$stmt->execute([':member_id' => $member_id]);
$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดต่อแอดมิน - T.S. Pattani</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .chat-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 600px;
        }
        .chat-header {
            background: #1e3c72;
            color: #fff;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 600;
        }
        .chat-body {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .chat-message {
            margin-bottom: 15px;
            max-width: 70%;
        }
        .chat-message.admin {
            margin-right: auto;
        }
        .chat-message.member {
            margin-left: auto;
            text-align: right;
        }
        .chat-bubble {
            padding: 10px 15px;
            border-radius: 15px;
            display: inline-block;
            font-size: 15px;
            word-wrap: break-word;
            text-align: left;
        }
        .chat-message.admin .chat-bubble {
            background: #e9ecef;
            color: #333;
            border-bottom-left-radius: 0;
        }
        .chat-message.member .chat-bubble {
            background: #007bff;
            color: #fff;
            border-bottom-right-radius: 0;
        }
        .chat-time {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
            display: block;
        }
        .chat-footer {
            padding: 15px;
            background: #fff;
            border-top: 1px solid #ddd;
        }
        .chat-input-group {
            display: flex;
            gap: 10px;
        }
        .chat-input {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-family: 'Prompt', sans-serif;
            outline: none;
        }
        .chat-input:focus {
            border-color: #007bff;
        }
        .btn-send {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 0 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-send:hover {
            background: #0056b3;
        }
    </style>
</head>
<body style="background-color: #f4f6f9;">

    <?php include 'includes/navbar.php'; ?>

    <div class="container" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
        
        <div class="chat-container">
            <div class="chat-header">
                <i class="fas fa-headset"></i> ติดต่อสอบถามแอดมิน (T.S. Pattani)
            </div>
            
            <div class="chat-body" id="chatBody">
                <?php if (empty($chats)): ?>
                    <div style="text-align: center; color: #999; margin-top: 50px;">
                        <i class="fas fa-comments fa-3x" style="margin-bottom: 10px;"></i>
                        <p>ยังไม่มีข้อความสนทนา ส่งข้อความเพื่อเริ่มคุยกับแอดมินได้เลย</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($chats as $msg): ?>
                        <div class="chat-message <?php echo $msg['chat_sender'] == 'Admin' ? 'admin' : 'member'; ?>">
                            <div class="chat-bubble">
                                <?php echo nl2br(htmlspecialchars($msg['chat_message'])); ?>
                            </div>
                            <span class="chat-time">
                                <?php echo date('d/m/Y H:i', strtotime($msg['chat_datetime'])); ?>
                                <?php if($msg['chat_sender'] == 'Admin') echo '(แอดมิน)'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-footer">
                <form action="actions/send_chat_db.php" method="POST" class="chat-input-group">
                    <input type="text" name="chat_message" class="chat-input" placeholder="พิมพ์ข้อความที่นี่..." required autocomplete="off" autofocus>
                    <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i> ส่ง</button>
                </form>
            </div>
        </div>

    </div>

    <script src="assets/js/member.js?v=1.4"></script>
    <script>
        // เลื่อนหน้าจอแชทลงไปล่างสุดเสมอเมื่อเปิดหน้า
        var chatBody = document.getElementById('chatBody');
        chatBody.scrollTop = chatBody.scrollHeight;
    </script>
</body>
</html>

