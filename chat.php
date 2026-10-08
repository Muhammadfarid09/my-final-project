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
    <link rel="stylesheet" href="assets/css/style.css?v=1.7">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="page-body">

    <?php include 'includes/navbar.php'; ?>

    <div class="container chat-page-wrapper">
        
        <div class="chat-container">
            <div class="chat-header">
                <div><i class="fas fa-headset"></i> ติดต่อสอบถามแอดมิน (T.S. Pattani)</div>
                <div class="chat-header-status"><span class="status-dot"></span> ระบบออนไลน์ (Auto-sync)</div>
            </div>
            
            <div class="chat-body" id="chatBody">
                <?php if (empty($chats)): ?>
                    <div id="emptyChatPlaceholder" class="chat-empty-box">
                        <i class="fas fa-comments fa-3x chat-empty-icon"></i>
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
