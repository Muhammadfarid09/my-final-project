<?php
session_start();
require_once 'includes/csrf.php';

// หากสมาชิกเข้าสู่ระบบอยู่แล้ว ให้เปลี่ยนเส้นทางไปหน้าแรก
if (!empty($_SESSION['member_id'])) {
    header("Location: index.php");
    exit();
}

$flash_error = $_SESSION['error'] ?? null;
$flash_success = $_SESSION['success'] ?? null;
$demo_link = $_SESSION['demo_reset_link'] ?? null;
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['demo_reset_link']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - T.S. Pattani Badminton</title>
    
    <!-- นำเข้า Global Tokens และ Scoped Recovery Stylesheet -->
    <link rel="stylesheet" href="assets/css/global.css?v=<?php echo filemtime('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="assets/css/auth-recovery.css?v=<?php echo filemtime('assets/css/auth-recovery.css'); ?>">
    
    <!-- Font Awesome และ Google Font Prompt -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="recovery-body">

    <!-- แสงเรืองรองและกราฟิกคอร์ทแบดมินตันพื้นหลังระดับ Luxury -->
    <div class="recovery-ambient-glow"></div>
    <div class="recovery-ambient-glow-bottom"></div>
    <div class="recovery-court-pattern"></div>

    <main class="recovery-container">
        <div class="recovery-card">
            
            <!-- ไอคอนความปลอดภัยสีทอง -->
            <div class="recovery-icon-badge">
                <i class="fas fa-shield-halved"></i>
            </div>

            <h1 class="recovery-title">ลืมรหัสผ่าน</h1>
            <p class="recovery-desc">กรุณากรอกเบอร์โทรศัพท์ที่ลงทะเบียนไว้กับระบบ เพื่อรับลิงก์สำหรับตั้งรหัสผ่านใหม่</p>

            <!-- แจ้งเตือนข้อผิดพลาด -->
            <?php if ($flash_error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($flash_error); ?></span>
                </div>
            <?php endif; ?>

            <!-- แจ้งเตือนส่งคำขอสำเร็จ (User Enumeration Prevention) -->
            <?php if ($flash_success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($flash_success); ?></span>
                </div>
            <?php endif; ?>

            <!-- โหมดจำลองสำหรับอาจารย์และกรรมการสอบโครงงาน (Demo Mode) -->
            <?php if ($demo_link): ?>
                <div class="demo-simulation-box">
                    <div class="demo-title">
                        <i class="fas fa-flask"></i> สำหรับการสาธิต / สอบโครงงาน:
                    </div>
                    <div>ระบบสร้างโทเค็นรีเซ็ตรหัสผ่านจำลองเรียบร้อย (มีอายุ 30 นาที)</div>
                    <a href="<?php echo htmlspecialchars($demo_link); ?>" class="btn-demo-link">
                        คลิกเพื่อตั้งรหัสผ่านใหม่ <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- ฟอร์มส่งคำขอรีเซ็ตรหัสผ่าน -->
            <form action="actions/forgot_password_db.php" method="POST" class="recovery-form" id="forgotForm">
                <?php echo csrf_field(); ?>

                <div class="recovery-form-group">
                    <label for="member_phone" class="recovery-label">เบอร์โทรศัพท์ที่ลงทะเบียน</label>
                    <div class="recovery-input-wrap">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" 
                               id="member_phone" 
                               name="member_phone" 
                               class="recovery-input" 
                               placeholder="กรอกเบอร์โทรศัพท์ 10 หลัก" 
                               maxlength="10" 
                               pattern="[0-9]{10}"
                               required 
                               autofocus 
                               autocomplete="tel">
                    </div>
                </div>

                <button type="submit" class="btn-recovery-submit" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    <span>ส่งคำขอรีเซ็ตรหัสผ่าน</span>
                </button>
            </form>

            <div class="recovery-back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i>
                    <span>จำรหัสผ่านได้แล้ว? กลับไปหน้าเข้าสู่ระบบ</span>
                </a>
            </div>

        </div>
    </main>

    <script>
        // ป้องกัน Double Submission & ตรวจสอบเบอร์โทร 10 หลัก Client-side
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const phoneInput = document.getElementById('member_phone');
            const phone = phoneInput.value.trim();

            if (!/^[0-9]{10}$/.test(phone)) {
                e.preventDefault();
                alert('กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้องครบ 10 หลัก (เฉพาะตัวเลขเท่านั้น)');
                phoneInput.focus();
                return;
            }

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>กำลังส่งคำขอ...</span>';
        });
    </script>
</body>
</html>
