<?php
session_start();
// หากลูกค้าล็อกอินอยู่แล้ว ให้ข้ามหน้า Login ไปที่หน้าแรก (index.php) ทันที
if (isset($_SESSION['member_id'])) {
    header("Location: index.php");
    exit();
}

$remember_phone = $_COOKIE['remember_phone'] ?? '';
$has_remember = !empty($remember_phone);
$initial_phone = $_SESSION['last_phone'] ?? $remember_phone;
$has_error = isset($_SESSION['error']);
$error_msg = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
$success_msg = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// หากเพิ่งล็อกอินผิดพลาด (มี error message และมีเบอร์เดิม) ให้เปิด Step 2 ทันทีพร้อมแสดงแจ้งเตือน
$start_step = ($has_error && !empty($initial_phone)) ? 2 : 1;
$redirect_target = htmlspecialchars($_GET['redirect'] ?? ($_POST['redirect'] ?? ''));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - T.S. Pattani</title>
    
    <!-- เรียกใช้ไฟล์ CSS ของฝั่งลูกค้า -->
    <link rel="stylesheet" href="assets/css/global.css?v=1.1">
    <link rel="stylesheet" href="assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="assets/css/responsive-mobile.css?v=1.1">
    
    <!-- นำเข้า Font Awesome สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- นำเข้า Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-register">

    <!-- เลเยอร์ทำภาพพื้นหลังให้มืดลง -->
    <div class="overlay"></div>

    <div class="register-container auth-card-sm">
        <form id="twoStepLoginForm" action="actions/login_db.php" method="POST" novalidate>
            <!-- รักษาค่า Redirect URL หลังล็อกอินสำเร็จ -->
            <input type="hidden" name="redirect" value="<?php echo $redirect_target; ?>">

            <!-- แถบแสดงสถานะขั้นตอน (Step Progress Indicator) -->
            <div class="auth-step-progress" aria-hidden="true">
                <div class="auth-step-dot <?php echo $start_step === 1 ? 'active' : ''; ?>" id="stepDot1"></div>
                <div class="auth-step-dot <?php echo $start_step === 2 ? 'active' : ''; ?>" id="stepDot2"></div>
            </div>

            <!-- ============================================== -->
            <!-- ขั้นตอนที่ 1: ระบุเบอร์โทรศัพท์ (Step 1) -->
            <!-- ============================================== -->
            <div class="auth-step-pane <?php echo $start_step === 2 ? 'is-hidden' : ''; ?>" id="loginStep1">
                <div class="auth-step-header">
                    <h2>ยินดีต้อนรับ</h2>
                    <p>เข้าสู่ระบบ T.S. Pattani Badminton</p>
                </div>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        <span><?php echo htmlspecialchars($success_msg); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg) && $start_step === 1): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> 
                        <span><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php endif; ?>

                <div id="step1AlertBox"></div>

                <div class="form-group">
                    <label for="member_phone">เบอร์โทรศัพท์</label>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="member_phone" name="member_phone" class="form-control" placeholder="08X-XXX-XXXX" maxlength="10" inputmode="numeric" value="<?php echo htmlspecialchars($initial_phone); ?>" autocomplete="tel" required>
                    </div>
                </div>

                <button type="button" id="btnNextStep" class="btn-register">
                    <span>ถัดไป</span> <i class="fas fa-arrow-right"></i>
                </button>

                <div class="login-link">
                    ยังไม่มีบัญชีใช่ไหม? <a href="register.php<?php echo !empty($redirect_target) ? '?redirect=' . urlencode($redirect_target) : ''; ?>">สมัครสมาชิกที่นี่</a>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- ขั้นตอนที่ 2: ยืนยันรหัสผ่าน (Step 2) -->
            <!-- ============================================== -->
            <div class="auth-step-pane <?php echo $start_step === 1 ? 'is-hidden' : ''; ?>" id="loginStep2">
                <div class="auth-step-header">
                    <h2>ยินดีต้อนรับกลับมา!</h2>
                    <p>กรุณาระบุรหัสผ่านเพื่อเข้าใช้งาน</p>
                </div>

                <!-- ข้อมูลบัญชีผู้ใช้ (Account Feedback Card) -->
                <div class="account-feedback-card" id="accountFeedbackCard">
                    <div class="account-feedback-info">
                        <div class="account-feedback-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="account-feedback-text">
                            <span class="account-name-text" id="displayMemberName">สมาชิก T.S. Pattani</span>
                            <span class="account-phone-text" id="displayMemberPhone"><?php echo htmlspecialchars($initial_phone); ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn-change-phone" id="btnChangePhone" title="เปลี่ยนเบอร์โทรศัพท์">
                        <i class="fas fa-pencil-alt"></i> เปลี่ยนเบอร์
                    </button>
                </div>

                <?php if (!empty($error_msg) && $start_step === 2): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> 
                        <span><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php endif; ?>

                <div id="step2AlertBox"></div>

                <div class="form-group">
                    <div class="auth-label-row">
                        <label for="member_password" class="auth-label-mb-0">รหัสผ่าน</label>
                    </div>
                    <div class="input-group password-toggle-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="member_password" name="member_password" class="form-control" placeholder="กรอกรหัสผ่านของคุณ" autocomplete="current-password" required>
                        <button type="button" class="btn-toggle-password" id="btnTogglePassword" aria-label="แสดงหรือซ่อนรหัสผ่าน" tabindex="-1">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="auth-options-row">
                    <label class="remember-me-label" for="remember_me">
                        <input type="checkbox" id="remember_me" name="remember_me" value="1" <?php echo $has_remember ? 'checked' : ''; ?>>
                        <span>จดจำฉันไว้ในระบบ</span>
                    </label>
                    <a href="forgot_password.php" class="auth-forgot-link">ลืมรหัสผ่าน?</a>
                </div>

                <button type="submit" id="btnLoginSubmit" class="btn-register">
                    <span>เข้าสู่ระบบ</span> <i class="fas fa-sign-in-alt"></i>
                </button>

                <div class="login-link">
                    ยังไม่มีบัญชีใช่ไหม? <a href="register.php<?php echo !empty($redirect_target) ? '?redirect=' . urlencode($redirect_target) : ''; ?>">สมัครสมาชิกที่นี่</a>
                </div>
            </div>

        </form>
    </div>

    <!-- เรียกใช้ไฟล์ JS ฝั่งลูกค้า -->
    <script src="assets/js/member.js?v=1.1"></script>
</body>
</html>
