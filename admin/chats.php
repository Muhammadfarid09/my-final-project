<?php
require_once 'includes/auth_check.php';

// 1. ดึงรายชื่อสมาชิกที่มีประวัติการแชททั้งหมด (เรียงตามการส่งข้อความล่าสุด)
$chat_members = [];
try {
    $sql_members = "SELECT m.member_id, m.member_name, MAX(ch.chat_datetime) AS latest_chat,
                           (SELECT chat_message FROM Chat WHERE member_id = m.member_id ORDER BY chat_datetime DESC LIMIT 1) AS last_message,
                           (SELECT chat_sender FROM Chat WHERE member_id = m.member_id ORDER BY chat_datetime DESC LIMIT 1) AS last_sender
                    FROM Member m 
                    JOIN Chat ch ON m.member_id = ch.member_id 
                    GROUP BY m.member_id, m.member_name 
                    ORDER BY latest_chat DESC";
    $stmt_members = $conn->query($sql_members);
    $chat_members = $stmt_members->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Chat members load error: " . $e->getMessage());
}

// 2. ตรวจสอบว่าแอดมินกำลังเลือกคุยกับสมาชิกคนไหนอยู่
$active_member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : ($chat_members[0]['member_id'] ?? null);

// 3. ดึงประวัติแชทของสมาชิกที่เลือก
$messages = [];
$active_member_name = "เลือกห้องแชท";

if ($active_member_id) {
    try {
        $stmt_msg = $conn->prepare("SELECT * FROM Chat WHERE member_id = :mid ORDER BY chat_datetime ASC");
        $stmt_msg->execute([':mid' => $active_member_id]);
        $messages = $stmt_msg->fetchAll(PDO::FETCH_ASSOC);

        // หาชื่อสมาชิกคนที่กำลังคุยด้วยมาแสดงหัวแชท
        $stmt_name = $conn->prepare("SELECT member_name FROM Member WHERE member_id = :mid");
        $stmt_name->execute([':mid' => $active_member_id]);
        $active_member_name = $stmt_name->fetchColumn() ?: "ลูกค้า #" . $active_member_id;
    } catch(PDOException $e) {
        error_log("Chat messages load error: " . $e->getMessage());
    }
}

$page_title = 'ระบบตอบแชท - Admin T.S. Pattani';
$page_header = '<i class="fas fa-comments"></i> ระบบสื่อสารและตอบแชทลูกค้า';
include 'includes/header.php';
?>

<!-- กล่องแชทหลัก 2 คอลัมน์ Desktop-First -->
<div class="chat-container">
    
    <!-- ฝั่งซ้าย: รายชื่อลูกค้าล่าสุด (320px) -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <span><i class="fas fa-inbox text-primary"></i> รายการแชทลูกค้า</span>
            <span class="badge-silver"><?php echo count($chat_members); ?> คน</span>
        </div>
        
        <div class="chat-sidebar-list">
            <?php if (count($chat_members) > 0): ?>
                <?php foreach ($chat_members as $m): ?>
                    <?php $is_active = ($m['member_id'] == $active_member_id); ?>
                    <a href="chats.php?member_id=<?php echo intval($m['member_id']); ?>" 
                       class="chat-member-item <?php echo $is_active ? 'active' : ''; ?>">
                        <div class="chat-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="chat-member-info">
                            <span class="chat-member-name"><?php echo htmlspecialchars($m['member_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="chat-member-meta">
                                <?php 
                                if (!empty($m['last_message'])) {
                                    $prefix = ($m['last_sender'] === 'Admin') ? 'คุณ: ' : '';
                                    echo htmlspecialchars($prefix . $m['last_message'], ENT_QUOTES, 'UTF-8');
                                } else {
                                    echo 'เริ่มการสนทนา';
                                }
                                ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="chat-empty">
                    <i class="fas fa-comments fa-2x mb-10 text-muted"></i>
                    <p>ยังไม่มีประวัติการพูดคุย</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ฝั่งขวา: หน้าต่างสนทนา -->
    <div class="chat-main">
        <?php if ($active_member_id): ?>
            <!-- หัวข้อแชท (58px) -->
            <div class="chat-main-header">
                <div class="d-flex align-items-center gap-10">
                    <div class="chat-avatar">
                        <i class="fas fa-user-check text-primary"></i>
                    </div>
                    <div>
                        <div class="font-bold text-dark-custom"><?php echo htmlspecialchars($active_member_name, ENT_QUOTES, 'UTF-8'); ?></div>
                        <small class="text-small-muted">รหัสสมาชิก #<?php echo intval($active_member_id); ?></small>
                    </div>
                </div>
                <span class="badge badge-success"><i class="fas fa-circle text-10"></i> กำลังสนทนา</span>
            </div>

            <!-- พื้นที่แสดงข้อความ (Scrollable) -->
            <div class="chat-messages" id="chatMessagesBox">
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php $is_admin = ($msg['chat_sender'] == 'Admin'); ?>
                        <div class="chat-bubble <?php echo $is_admin ? 'admin' : 'member'; ?>">
                            <?php echo nl2br(htmlspecialchars($msg['chat_message'], ENT_QUOTES, 'UTF-8')); ?>
                            <span class="chat-time">
                                <?php echo date('d/m/Y H:i', strtotime($msg['chat_datetime'])); ?> น.
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="chat-messages-empty">
                        <i class="fas fa-comment-dots fa-3x mb-15 text-muted"></i>
                        <p>เริ่มการสนทนากับคุณ <?php echo htmlspecialchars($active_member_name, ENT_QUOTES, 'UTF-8'); ?>...</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ช่องพิมพ์ข้อความ (40px Form Input) -->
            <form action="actions/chat_send_db.php" method="POST" class="chat-input-area" id="adminChatForm">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                <input type="hidden" name="member_id" value="<?php echo intval($active_member_id); ?>">
                <input type="text" name="message" id="chatInputMessage" placeholder="พิมพ์ข้อความตอบกลับลูกค้า..." required autocomplete="off">
                <button type="submit" class="btn-chat-send" id="btnChatSend">
                    <i class="fas fa-paper-plane"></i> ส่งข้อความ
                </button>
            </form>

            <script>
                (function() {
                    let lastChatId = <?php echo !empty($messages) ? intval(end($messages)['chat_id']) : 0; ?>;
                    const currentMemberId = <?php echo intval($active_member_id); ?>;
                    const chatBox = document.getElementById('chatMessagesBox');
                    const chatForm = document.getElementById('adminChatForm');
                    const btnChatSend = document.getElementById('btnChatSend');

                    // ฟังก์ชันเลื่อนลงล่างสุดอัตโนมัติ
                    function scrollChatToBottom() {
                        if (chatBox) {
                            chatBox.scrollTop = chatBox.scrollHeight;
                        }
                    }

                    // เลื่อนลงล่างทันทีเมื่อโหลดหน้าเว็บเสร็จ
                    scrollChatToBottom();

                    // ป้องกันการคลิกส่งซ้ำ (Double Submission Prevention)
                    if (chatForm && btnChatSend) {
                        chatForm.addEventListener('submit', function() {
                            btnChatSend.disabled = true;
                            btnChatSend.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังส่ง...';
                        });
                    }

                    // Real-time Polling ดึงข้อความใหม่ทุก 4 วินาที
                    setInterval(() => {
                        fetch(`actions/chat_messages_db.php?member_id=${currentMemberId}&last_id=${lastChatId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success' && data.data && data.data.length > 0) {
                                    data.data.forEach(msg => {
                                        const is_admin = (msg.chat_sender === 'Admin');
                                        const bubbleClass = is_admin ? 'admin' : 'member';
                                        
                                        const msgDiv = document.createElement('div');
                                        msgDiv.className = `chat-bubble ${bubbleClass}`;
                                        msgDiv.innerHTML = `${msg.chat_message} <span class="chat-time">${msg.chat_datetime_formatted} น.</span>`;
                                        
                                        chatBox.appendChild(msgDiv);
                                        lastChatId = msg.chat_id;
                                    });
                                    scrollChatToBottom();
                                }
                            })
                            .catch(err => console.error('Error fetching chats:', err));
                    }, 4000);
                })();
            </script>
        <?php else: ?>
            <div class="chat-messages-empty" style="margin: auto;">
                <i class="fas fa-inbox fa-3x mb-15 text-muted"></i>
                <h4 class="text-dark-custom">ยังไม่ได้เลือกห้องแชท</h4>
                <p>กรุณาเลือกรายชื่อลูกค้าจากแถบด้านซ้ายเพื่อเริ่มการสนทนา</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>