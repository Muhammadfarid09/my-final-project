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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-header-status {
            font-size: 13px;
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            background-color: #28a745;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 6px #28a745;
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
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-send:hover {
            background: #0056b3;
        }
        .btn-send:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
    </style>
</head>
<body style="background-color: #f4f6f9;">

    <?php include 'includes/navbar.php'; ?>

    <div class="container" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
        
        <div class="chat-container">
            <div class="chat-header">
                <div><i class="fas fa-headset"></i> ติดต่อสอบถามแอดมิน (T.S. Pattani)</div>
                <div class="chat-header-status"><span class="status-dot"></span> ระบบออนไลน์ (Auto-sync)</div>
            </div>
            
            <div class="chat-body" id="chatBody">
                <?php if (empty($chats)): ?>
                    <div id="emptyChatPlaceholder" style="text-align: center; color: #999; margin-top: 50px;">
                        <i class="fas fa-comments fa-3x" style="margin-bottom: 10px;"></i>
                        <p>ยังไม่มีข้อความสนทนา ส่งข้อความเพื่อเริ่มคุยกับแอดมินได้เลย</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($chats as $msg): ?>
                        <div class="chat-message <?php echo $msg['chat_sender'] == 'Admin' ? 'admin' : 'member'; ?>" data-id="<?php echo $msg['chat_id']; ?>">
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
                <form action="actions/send_chat_db.php" method="POST" class="chat-input-group" id="chatForm">
                    <input type="text" name="chat_message" id="chatInput" class="chat-input" placeholder="พิมพ์ข้อความที่นี่..." required autocomplete="off" autofocus>
                    <button type="submit" id="btnSend" class="btn-send"><i class="fas fa-paper-plane"></i> ส่ง</button>
                </form>
            </div>
        </div>

    </div>

    <script src="assets/js/member.js?v=1.4"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var chatBody = document.getElementById('chatBody');
            var chatForm = document.getElementById('chatForm');
            var chatInput = document.getElementById('chatInput');
            var btnSend = document.getElementById('btnSend');

            // คำนวณรหัสแชทล่าสุดที่มีอยู่ในหน้าจอ
            var lastChatId = 0;
            document.querySelectorAll('.chat-message[data-id]').forEach(function(el) {
                var id = parseInt(el.getAttribute('data-id'), 10);
                if (id > lastChatId) lastChatId = id;
            });

            // เลื่อนหน้าจอแชทลงไปล่างสุด
            function scrollToBottom() {
                chatBody.scrollTop = chatBody.scrollHeight;
            }
            scrollToBottom();

            // ส่งข้อความแบบ AJAX (เรียลไทม์ไม่ต้องโหลดหน้าใหม่)
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var msg = chatInput.value.trim();
                if (!msg) return;

                chatInput.disabled = true;
                btnSend.disabled = true;

                var formData = new FormData(chatForm);
                formData.append('ajax', '1');

                fetch('actions/send_chat_db.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(res) {
                    chatInput.value = '';
                    chatInput.disabled = false;
                    btnSend.disabled = false;
                    chatInput.focus();

                    if (res.status === 'success') {
                        fetchMessages();
                    }
                })
                .catch(function(err) {
                    console.error('Send error:', err);
                    chatInput.disabled = false;
                    btnSend.disabled = false;
                    chatForm.submit(); // fallback ส่งแบบฟอร์มปกติถ้ามีข้อผิดพลาด
                });
            });

            // ฟังก์ชันดึงข้อความใหม่เป็นระยะ (Polling)
            function fetchMessages() {
                fetch('actions/get_chat_messages.php?last_id=' + lastChatId)
                .then(function(res) { return res.json(); })
                .then(function(res) {
                    if (res.status === 'success' && res.data && res.data.length > 0) {
                        var placeholder = document.getElementById('emptyChatPlaceholder');
                        if (placeholder) {
                            placeholder.remove();
                        }

                        res.data.forEach(function(msg) {
                            var msgId = parseInt(msg.chat_id, 10);
                            if (msgId > lastChatId) {
                                lastChatId = msgId;
                            }

                            var isAdmin = msg.chat_sender === 'Admin';
                            var div = document.createElement('div');
                            div.className = 'chat-message ' + (isAdmin ? 'admin' : 'member');
                            div.setAttribute('data-id', msg.chat_id);
                            
                            var bubble = document.createElement('div');
                            bubble.className = 'chat-bubble';
                            bubble.innerHTML = msg.chat_message_escaped;

                            var timeSpan = document.createElement('span');
                            timeSpan.className = 'chat-time';
                            timeSpan.textContent = msg.chat_datetime_formatted + (isAdmin ? ' (แอดมิน)' : '');

                            div.appendChild(bubble);
                            div.appendChild(timeSpan);
                            chatBody.appendChild(div);
                        });

                        scrollToBottom();
                    }
                })
                .catch(function(err) {
                    console.error('Chat polling error:', err);
                });
            }

            // ตั้งเวลาดึงข้อความอัตโนมัติทุกๆ 3 วินาที
            setInterval(fetchMessages, 3000);
        });
    </script>
</body>
</html>
