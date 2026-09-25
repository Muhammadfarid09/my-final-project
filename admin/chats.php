<?php
require_once 'includes/auth_check.php';

// 1. ดึงรายชื่อสมาชิกที่มีประวัติการแชททั้งหมด (เอาคนที่ส่งล่าสุดขึ้นก่อน)
$chat_members = [];
try {
    $sql_members = "SELECT DISTINCT m.member_id, m.member_name 
                    FROM Chat ch 
                    JOIN Member m ON ch.member_id = m.member_id 
                    ORDER BY ch.chat_datetime DESC";
    $stmt_members = $conn->query($sql_members);
    $chat_members = $stmt_members->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// 2. ตรวจสอบว่าแอดมินกำลังเลือกคุยกับสมาชิกคนไหนอยู่
// ถ้ามีการส่ง id มาทาง URL ให้ใช้ค่านั้น ถ้าไม่มีให้ดึงคนแรกในรายชื่อมาเป็นค่าเริ่มต้น
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
        $active_member_name = $stmt_name->fetchColumn();
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
<?php
$page_title = 'ระบบตอบแชท - Admin T.S. Pattani';
$page_header = '<i class="fas fa-comments"></i> ระบบสื่อสารและตอบแชทลูกค้า';
include 'includes/header.php';
?>
<!-- กล่องแชทหลัก -->
<div class="chat-container">
    
    <!-- ฝั่งซ้าย: รายชื่อลูกค้า -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            รายการแชทล่าสุด
        </div>
        
        <?php if (count($chat_members) > 0): ?>
            <?php foreach ($chat_members as $m): ?>
                <a href="chats.php?member_id=<?php echo $m['member_id']; ?>" 
                   class="chat-member-item <?php echo ($m['member_id'] == $active_member_id) ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle chat-icon"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($m['member_name']); ?></span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="chat-empty">
                ยังไม่มีการพูดคุย
            </div>
        <?php endif; ?>
    </div>
    
    <!-- ฝั่งขวา: หน้าต่างสนทนา -->
    <div class="chat-main">
        
        <!-- หัวข้อแชท -->
        <div class="chat-main-header">
            สนทนากับ: <span class="text-primary"><?php echo htmlspecialchars($active_member_name); ?></span>
        </div>

        <!-- พื้นที่แสดงข้อความ -->
        <div class="chat-messages" id="chatMessagesBox">
            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $msg): ?>
                    <!-- เช็คว่าใครเป็นคนส่ง (Admin หรือ Member) เพื่อกำหนด CSS Class -->
                    <?php $is_admin = ($msg['chat_sender'] == 'Admin'); ?>
                    
                    <div class="chat-bubble <?php echo $is_admin ? 'admin' : 'member'; ?>">
                        <?php echo nl2br(htmlspecialchars($msg['chat_message'])); ?>
                        <span class="chat-time">
                            <?php echo date('d/m/Y H:i', strtotime($msg['chat_datetime'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if ($active_member_id): ?>
                    <div class="chat-messages-empty">
                        เริ่มการสนทนา...
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- ช่องพิมพ์ข้อความ (แสดงเฉพาะเมื่อมีการเลือกคนคุย) -->
        <?php if ($active_member_id): ?>
            <form action="actions/chat_send_db.php" method="POST" class="chat-input-area">
                <input type="hidden" name="member_id" value="<?php echo $active_member_id; ?>">
                <input type="text" name="message" placeholder="พิมพ์ข้อความตอบกลับ..." required autocomplete="off">
                <button type="submit"><i class="fas fa-paper-plane"></i></button>
            </form>

            <script>
                let lastChatId = <?php echo !empty($messages) ? end($messages)['chat_id'] : 0; ?>;
                const currentMemberId = <?php echo $active_member_id; ?>;
                const chatBox = document.getElementById('chatMessagesBox');

                setInterval(() => {
                    fetch(`actions/chat_messages_db.php?member_id=${currentMemberId}&last_id=${lastChatId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success' && data.data.length > 0) {
                                data.data.forEach(msg => {
                                    const is_admin = (msg.chat_sender === 'Admin');
                                    const bubbleClass = is_admin ? 'admin' : 'member';
                                    
                                    const msgDiv = document.createElement('div');
                                    msgDiv.className = `chat-bubble ${bubbleClass}`;
                                    msgDiv.innerHTML = `${msg.chat_message} <span class="chat-time">${msg.chat_datetime_formatted}</span>`;
                                    
                                    chatBox.appendChild(msgDiv);
                                    lastChatId = msg.chat_id;
                                });
                                // เลื่อนจอลงเมื่อมีข้อความใหม่
                                chatBox.scrollTop = chatBox.scrollHeight;
                            }
                        })
                        .catch(err => console.error('Error fetching chats:', err));
                }, 5000); // เช็คทุก 5 วินาที
            </script>
        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>