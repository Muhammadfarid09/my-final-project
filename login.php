<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/csrf.php';

// หากลูกค้าล็อกอินอยู่แล้ว ให้ข้ามหน้า Login ไปที่หน้าแรก (index.php) ทันที
if (isset($_SESSION['member_id'])) {
    header("Location: index.php");
    exit();
}

// ตรวจสอบค่าเบอร์โทรศัพท์ที่เคยกรอกค้างไว้ หรือดึงจาก Cookie Remember Me
$prefilled_phone = '';
if (isset($_SESSION['form_phone'])) {
    $prefilled_phone = htmlspecialchars($_SESSION['form_phone']);
    unset($_SESSION['form_phone']);
} elseif (isset($_COOKIE['remember_phone'])) {
    $prefilled_phone = htmlspecialchars($_COOKIE['remember_phone']);
}
$is_remembered = !empty($_COOKIE['remember_phone']);

// รักษาค่า Redirect URL หลังล็อกอินสำเร็จ
$redirect_target = htmlspecialchars($_GET['redirect'] ?? ($_POST['redirect'] ?? ''));

// ตรวจสอบ Flash Message กรณีเพิ่งสมัครสมาชิกสำเร็จ
$registered_flash = isset($_GET['registered']) && $_GET['registered'] == '1';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบสมาชิก - T.S. Pattani Badminton</title>
    
    <!-- เรียกใช้ไฟล์ CSS ของระบบ -->
    <link rel="stylesheet" href="assets/css/global.css?v=<?php echo filemtime('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="assets/css/responsive-mobile.css?v=<?php echo filemtime('assets/css/responsive-mobile.css'); ?>">
    
    <!-- นำเข้า Font Awesome สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- นำเข้า Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="login-split-body">

    <!-- Layout Wrapper: Full Split-Screen 50/50 (min-height: 100vh) -->
    <div class="login-split-wrapper">
        
        <!-- ============================================================ -->
        <!-- ก. ฝั่งซ้าย: Brand & Visual Panel (Dark Black & Gold 50%)    -->
        <!-- ============================================================ -->
        <aside class="login-visual-panel">
            <!-- เส้นกราฟิกสนามคอร์ทมินิมอลและแสงสะท้อนสีทอง -->
            <div class="visual-court-pattern"></div>
            <div class="visual-ambient-glow"></div>

            <div class="visual-panel-content">
                <!-- ส่วนบน: Capsule Badge -->
                <div class="visual-panel-top">
                    <div class="visual-badge-capsule">
                        <i class="fas fa-shuttlecock text-gold"></i>
                        <span>🏸 T.S. Pattani Badminton Booking Portal</span>
                    </div>
                </div>

                <!-- ส่วนกลาง: Hero Heading & Floating Highlight Cards -->
                <div class="visual-panel-center">
                    <div class="visual-brand-heading">
                        <h1 class="visual-hero-title">
                            ยินดีต้อนรับสู่ <br>
                            <span class="text-gold-highlight">T.S. Pattani Badminton</span>
                        </h1>
                        <p class="visual-hero-subtitle">
                            สนามแบดมินตันมาตรฐาน BWF จองง่าย สะดวก รวดเร็ว พร้อมลุยทุกแมตช์
                        </p>
                    </div>

                    <!-- การ์ดสรุปจุดเด่นลอยตัว 3 ใบ -->
                    <div class="visual-floating-cards">
                        <div class="visual-highlight-card">
                            <div class="visual-card-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <div class="visual-card-text">
                                <h3 class="visual-card-title">คอร์ทมาตรฐาน BWF</h3>
                                <p class="visual-card-desc">พื้นยาง 5 ชั้น ซับแรงกระแทก เกรดแข่งขันระดับสากล</p>
                            </div>
                        </div>

                        <div class="visual-highlight-card">
                            <div class="visual-card-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="visual-card-text">
                                <h3 class="visual-card-title">ระบบไฟส่องสว่างระดับแข่งขัน</h3>
                                <p class="visual-card-desc">LED Anti-Glare ตัดแสงสะท้อน ถนอมสายตาทุกมุมมอง</p>
                            </div>
                        </div>

                        <div class="visual-highlight-card">
                            <div class="visual-card-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="visual-card-text">
                                <h3 class="visual-card-title">จองคิวออนไลน์เรียลไทม์ 24 ชม.</h3>
                                <p class="visual-card-desc">เลือกรอบเวลา ล็อกคอร์ทอัตโนมัติ รวดเร็ว 100%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ส่วนล่าง: Footer Security & Trust Tag -->
                <div class="visual-panel-bottom">
                    <span class="visual-footer-text">
                        <i class="fas fa-shield-alt text-gold mr-6"></i> ระบบการจองและชำระเงินที่ปลอดภัยมาตรฐานสากล
                    </span>
                </div>
            </div>
        </aside>

        <!-- ============================================================ -->
        <!-- ข. ฝั่งขวา: Interaction & Form Panel (50%)                   -->
        <!-- ============================================================ -->
        <main class="login-form-panel">
            
            <!-- แถบเครื่องมือด้านบน (Top Utility Bar) -->
            <div class="login-top-utility">
                <a href="index.php" class="btn-utility-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับหน้าหลัก</span>
                </a>
                <div class="utility-status-pill">
                    <span class="status-indicator-dot"></span>
                    <span>ระบบพร้อมให้บริการ</span>
                </div>
            </div>

            <!-- กล่องคอนเทนเนอร์การ์ดฟอร์มลอยตัว (Floating Form Card) -->
            <div class="login-card-floating">
                
                <div class="login-card-header">
                    <div class="login-brand-tag">
                        <i class="fas fa-shuttlecock text-gold"></i>
                        <span>T.S. PATTANI</span>
                    </div>
                    <h2 class="login-card-title">เข้าสู่ระบบสมาชิก</h2>
                    <p class="login-card-desc">ยินดีต้อนรับกลับมา! กรุณากรอกเบอร์โทรศัพท์เพื่อเข้าใช้งาน</p>
                </div>

                <!-- พื้นที่แสดงแจ้งเตือน Error ฝั่ง Client-side (JS Validation) -->
                <div id="loginClientAlertBox"></div>

                <!-- แจ้งเตือนเมื่อสมัครสมาชิกสำเร็จแล้วถูกส่งต่อมาหน้านี้ -->
                <?php if ($registered_flash): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        <span>สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบด้วยเบอร์โทรศัพท์ของคุณ</span>
                    </div>
                <?php endif; ?>

                <!-- แจ้งเตือน Success หรือ Error จาก Session -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> 
                        <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <!-- ฟอร์มเข้าสู่ระบบส่งไปยัง actions/login_db.php -->
                <form action="actions/login_db.php" method="POST" id="loginForm" novalidate>
                    <?php echo csrf_field(); ?>
                    
                    <!-- ฟิลด์ที่ 1: เบอร์โทรศัพท์ (Username) -->
                    <div class="form-group-dark">
                        <label for="member_phone" class="form-label-dark">
                            เบอร์โทรศัพท์ <span class="text-danger">*</span>
                        </label>
                        <div class="input-group-dark">
                            <i class="fas fa-phone input-icon-dark"></i>
                            <input type="text" id="member_phone" name="member_phone" class="form-control-dark" placeholder="08X-XXX-XXXX" inputmode="tel" maxlength="10" autocomplete="tel" value="<?php echo $prefilled_phone; ?>" required>
                        </div>
                    </div>

                    <!-- ฟิลด์ที่ 2: รหัสผ่าน พร้อมปุ่ม Toggle ลูกตา -->
                    <div class="form-group-dark">
                        <label for="member_password" class="form-label-dark">
                            รหัสผ่าน <span class="text-danger">*</span>
                        </label>
                        <div class="input-group-dark has-toggle">
                            <i class="fas fa-lock input-icon-dark"></i>
                            <input type="password" id="member_password" name="member_password" class="form-control-dark" placeholder="กรอกรหัสผ่านของคุณ" autocomplete="current-password" required>
                            <button type="button" class="btn-toggle-password-dark" id="btnToggleLoginPassword" aria-label="แสดงหรือซ่อนรหัสผ่าน" tabindex="-1">
                                <i class="fas fa-eye" id="toggleLoginPasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- แถวควบคุม: Remember Me & Forgot Password -->
                    <div class="login-control-row-dark">
                        <label class="checkbox-label-dark">
                            <input type="checkbox" name="remember_me" value="1" <?php echo $is_remembered ? 'checked' : ''; ?>>
                            <span>จดจำการเข้าสู่ระบบ</span>
                        </label>
                        <a href="forgot_password.php" class="forgot-link-gold">ลืมรหัสผ่าน?</a>
                    </div>

                    <!-- รักษาค่า Redirect URL หลังล็อกอินสำเร็จ -->
                    <input type="hidden" name="redirect" value="<?php echo $redirect_target; ?>">

                    <!-- ปุ่มเข้าสู่ระบบ (Submit CTA) -->
                    <button type="submit" id="btnLoginSubmit" class="btn-login-gold-full">
                        <span>เข้าสู่ระบบ</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>

                    <!-- ลิงก์สมัครสมาชิกใหม่ -->
                    <div class="login-footer-register-dark">
                        ยังไม่มีบัญชีใช่ไหม? <a href="register.php<?php echo !empty($redirect_target) ? '?redirect=' . urlencode($redirect_target) : ''; ?>">สมัครสมาชิกที่นี่</a>
                    </div>
                </form>

            </div>

            <!-- ข้อมูลย่อยท้ายหน้า -->
            <div class="login-form-bottom-info">
                <span>© 2026 T.S. Pattani Badminton · ระบบความปลอดภัยมาตรฐาน BWF</span>
            </div>

        </main>

    </div>

    <!-- เรียกใช้ไฟล์ JS ฝั่งลูกค้า (มีระบบ Double Submit Prevention & Input Sanitation) -->
    <script src="assets/js/member.js?v=<?php echo filemtime('assets/js/member.js'); ?>"></script>
</body>
</html>
