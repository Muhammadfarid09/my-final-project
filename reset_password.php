<?php
session_start();
require_once 'config/config.php';
require_once 'includes/csrf.php';

// หากสมาชิกเข้าสู่ระบบอยู่แล้ว ให้เปลี่ยนเส้นทางไปหน้าแรก
if (!empty($_SESSION['member_id'])) {
    header("Location: index.php");
    exit();
}

$raw_token = trim($_GET['token'] ?? '');
$is_valid_token = false;
$member_info = null;
$error_message = null;

if (empty($raw_token)) {
    $error_message = "ไม่พบโทเค็นสำหรับการตั้งรหัสผ่านใหม่";
} else {
    try {
        $token_hash = hash('sha256', $raw_token);

        // ตรวจสอบความถูกต้องและวันหมดอายุของโทเค็น (อายุ 30 นาที และยังไม่ถูกใช้)
        $stmt = $conn->prepare("
            SELECT r.*, m.member_name, m.member_phone 
            FROM Password_Resets r 
            JOIN Member m ON r.member_id = m.member_id 
            WHERE r.token_hash = :hash 
              AND r.used = 0 
              AND r.expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute([':hash' => $token_hash]);
        $reset_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reset_record) {
            $is_valid_token = true;
            $member_info = $reset_record;
        } else {
            $error_message = "ลิงก์สำหรับตั้งรหัสผ่านใหม่นี้หมดอายุแล้ว (เกิน 30 นาที) หรือถูกใช้งานไปแล้ว";
        }
    } catch (PDOException $e) {
        $error_message = "เกิดข้อผิดพลาดในการตรวจสอบโทเค็น กรุณาลองใหม่อีกครั้ง";
    }
}

$flash_error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่ - T.S. Pattani Badminton</title>
    
    <link rel="stylesheet" href="assets/css/global.css?v=<?php echo filemtime('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="assets/css/auth-recovery.css?v=<?php echo filemtime('assets/css/auth-recovery.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="recovery-body">

    <div class="recovery-ambient-glow"></div>
    <div class="recovery-ambient-glow-bottom"></div>
    <div class="recovery-court-pattern"></div>

    <main class="recovery-container">
        <div class="recovery-card">

            <?php if ($is_valid_token && $member_info): ?>
                <!-- ============================================== -->
                <!-- กรณีที่ 1: โทเค็นถูกต้อง แสดงฟอร์มตั้งรหัสผ่านใหม่ -->
                <!-- ============================================== -->
                <div class="recovery-icon-badge">
                    <i class="fas fa-lock"></i>
                </div>

                <h1 class="recovery-title">ตั้งรหัสผ่านใหม่</h1>
                <p class="recovery-desc">
                    สำหรับสมาชิก: <strong><?php echo htmlspecialchars($member_info['member_name']); ?></strong> 
                    (เบอร์โทร <?php echo htmlspecialchars($member_info['member_phone']); ?>)
                </p>

                <?php if ($flash_error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($flash_error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="actions/reset_password_db.php" method="POST" class="recovery-form" id="resetForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($raw_token); ?>">

                    <!-- รหัสผ่านใหม่ -->
                    <div class="recovery-form-group">
                        <label for="new_password" class="recovery-label">รหัสผ่านใหม่ (อย่างน้อย 6 ตัวอักษร)</label>
                        <div class="recovery-input-wrap">
                            <i class="fas fa-key input-icon"></i>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="recovery-input has-toggle" 
                                   placeholder="กรอกรหัสผ่านใหม่อย่างน้อย 6 ตัวอักษร" 
                                   minlength="6" 
                                   required 
                                   autofocus>
                            <button type="button" class="btn-toggle-password-recovery" id="toggleNewPassword" aria-label="แสดง/ซ่อนรหัสผ่าน" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ยืนยันรหัสผ่านใหม่ -->
                    <div class="recovery-form-group">
                        <label for="confirm_password" class="recovery-label">ยืนยันรหัสผ่านใหม่อีกครั้ง</label>
                        <div class="recovery-input-wrap">
                            <i class="fas fa-check-double input-icon"></i>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="recovery-input has-toggle" 
                                   placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" 
                                   minlength="6" 
                                   required>
                            <button type="button" class="btn-toggle-password-recovery" id="toggleConfirmPassword" aria-label="แสดง/ซ่อนรหัสผ่าน" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="passwordMatchHint" class="password-match-hint"></div>
                    </div>

                    <button type="submit" class="btn-recovery-submit" id="submitResetBtn">
                        <i class="fas fa-save"></i>
                        <span>บันทึกรหัสผ่านใหม่</span>
                    </button>
                </form>

            <?php else: ?>
                <!-- ============================================== -->
                <!-- กรณีที่ 2: โทเค็นไม่ถูกต้อง หรือหมดอายุแล้ว      -->
                <!-- ============================================== -->
                <div class="recovery-icon-badge" style="color: #f87171; border-color: rgba(239, 68, 68, 0.4); background: rgba(239, 68, 68, 0.12);">
                    <i class="fas fa-clock-rotate-left"></i>
                </div>

                <h1 class="recovery-title">ลิงก์ไม่ถูกต้องหรือหมดอายุแล้ว</h1>
                <p class="recovery-desc">
                    <?php echo htmlspecialchars($error_message ?? 'ลิงก์สำหรับตั้งรหัสผ่านใหม่นี้ไม่สามารถใช้งานได้'); ?>
                </p>

                <a href="forgot_password.php" class="btn-recovery-submit">
                    <i class="fas fa-arrow-rotate-left"></i>
                    <span>ขอลิงก์รีเซ็ตรหัสผ่านใหม่</span>
                </a>
            <?php endif; ?>

            <div class="recovery-back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับไปหน้าเข้าสู่ระบบ</span>
                </a>
            </div>

        </div>
    </main>

    <script>
        // Toggle การแสดง/ซ่อนรหัสผ่าน
        function setupPasswordToggle(inputId, toggleBtnId) {
            const input = document.getElementById(inputId);
            const btn = document.getElementById(toggleBtnId);
            if (!input || !btn) return;

            btn.addEventListener('click', function() {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
                }
            });
        }

        setupPasswordToggle('new_password', 'toggleNewPassword');
        setupPasswordToggle('confirm_password', 'toggleConfirmPassword');

        // ตรวจสอบความตรงกันของรหัสผ่านแบบ Real-time
        const newPassInput = document.getElementById('new_password');
        const confirmPassInput = document.getElementById('confirm_password');
        const matchHint = document.getElementById('passwordMatchHint');
        const submitBtn = document.getElementById('submitResetBtn');

        function checkPasswordMatch() {
            if (!newPassInput || !confirmPassInput || !matchHint) return;
            const p1 = newPassInput.value;
            const p2 = confirmPassInput.value;

            if (p2.length === 0) {
                matchHint.style.display = 'none';
                return;
            }

            if (p1 === p2) {
                matchHint.className = 'password-match-hint valid';
                matchHint.innerHTML = '<i class="fas fa-check-circle"></i> รหัสผ่านตรงกันเรียบร้อย';
            } else {
                matchHint.className = 'password-match-hint invalid';
                matchHint.innerHTML = '<i class="fas fa-times-circle"></i> รหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง';
            }
        }

        if (newPassInput && confirmPassInput) {
            newPassInput.addEventListener('input', checkPasswordMatch);
            confirmPassInput.addEventListener('input', checkPasswordMatch);
        }

        // ป้องกัน Double Submission & ตรวจความถูกต้อง Client-side
        const form = document.getElementById('resetForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const p1 = newPassInput.value;
                const p2 = confirmPassInput.value;

                if (p1.length < 6) {
                    e.preventDefault();
                    alert('รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
                    newPassInput.focus();
                    return;
                }

                if (p1 !== p2) {
                    e.preventDefault();
                    alert('รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน');
                    confirmPassInput.focus();
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>กำลังบันทึกรหัสผ่านใหม่...</span>';
            });
        }
    </script>
</body>
</html>
