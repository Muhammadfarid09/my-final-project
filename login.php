<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    <title>เข้าสู่ระบบ - T.S. Pattani Badminton</title>
    
    <!-- เรียกใช้ไฟล์ CSS ของระบบ -->
    <link rel="stylesheet" href="assets/css/global.css?v=<?php echo filemtime('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="assets/css/responsive-mobile.css?v=<?php echo filemtime('assets/css/responsive-mobile.css'); ?>">
    
    <!-- นำเข้า Font Awesome สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- นำเข้า Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-register">

    <!-- เลเยอร์ทำภาพพื้นหลังให้มืดลงอย่างนุ่มนวล -->
    <div class="overlay"></div>

    <!-- คอนเทนเนอร์หลัก: Split-Screen 2 หน้าจอซ้าย-ขวาสไตล์เปิดเล่มหนังสือ -->
    <div class="login-book-wrapper">
        
        <!-- ฝั่งซ้าย (Cover / Visual Page - หน้าปกต้อนรับ) -->
        <div class="login-book-cover">
            <div class="cover-court-lines"></div>
            
            <div class="cover-content">
                <!-- แบรนด์และโลโก้สนาม -->
                <div class="cover-brand-header">
                    <div class="cover-logo-icon">
                        <i class="fas fa-shuttlecock"></i>
                    </div>
                    <span class="cover-brand-name">T.S. PATTANI BADMINTON</span>
                </div>

                <!-- ข้อความต้อนรับและสโลแกน -->
                <div>
                    <h1 class="cover-hero-title">
                        ยินดีต้อนรับสู่ <br>
                        T.S. Pattani Badminton
                    </h1>
                    <p class="cover-hero-slogan">
                        สนามแบดมินตันมาตรฐาน BWF จองง่าย สะดวก รวดเร็ว พร้อมลุยทุกแมตช์
                    </p>
                </div>

                <!-- 3 จุดเด่นสนามพร้อมไอคอน -->
                <div class="cover-features-list">
                    <div class="cover-feature-item">
                        <div class="cover-feature-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <div class="cover-feature-text">
                            <span class="cover-feature-title">คอร์ทมาตรฐานพรีเมียม</span>
                            <span class="cover-feature-desc">พื้นยางเกรดแข่งขันและไฟ LED Anti-Glare ถนอมสายตา</span>
                        </div>
                    </div>

                    <div class="cover-feature-item">
                        <div class="cover-feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="cover-feature-text">
                            <span class="cover-feature-title">ระบบจองคิวออนไลน์ 24 ชม.</span>
                            <span class="cover-feature-desc">เลือกคอร์ทและเวลาที่ต้องการได้ทันใจ ไม่ต้องรอคิว</span>
                        </div>
                    </div>

                    <div class="cover-feature-item">
                        <div class="cover-feature-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="cover-feature-text">
                            <span class="cover-feature-title">ชุมชนคนรักกีฬา & สะสมแต้ม</span>
                            <span class="cover-feature-desc">ทุกการจองสะสมแต้มแลกชั่วโมงสนามและของรางวัลพิเศษ</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ฝั่งขวา (Form Page - หน้าฟอร์มเข้าสู่ระบบ) -->
        <div class="login-book-form-section">
            <div class="login-form-inner">
                
                <div class="login-header-group">
                    <h2 class="login-title">เข้าสู่ระบบ</h2>
                    <p class="login-subtitle">ยินดีต้อนรับกลับมา! กรุณากรอกข้อมูลเพื่อเข้าใช้งาน</p>
                </div>

                <!-- พื้นที่แสดงแจ้งเตือน Error ฝั่ง Client-side -->
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
                    
                    <!-- ฟิลด์ที่ 1: เบอร์โทรศัพท์ (Username) -->
                    <div class="form-group">
                        <label for="member_phone" class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="text" id="member_phone" name="member_phone" class="form-control" placeholder="08X-XXX-XXXX" inputmode="tel" maxlength="10" autocomplete="tel" value="<?php echo $prefilled_phone; ?>" required>
                        </div>
                    </div>

                    <!-- ฟิลด์ที่ 2: รหัสผ่าน พร้อมปุ่ม Toggle ลูกตา -->
                    <div class="form-group">
                        <label for="member_password" class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                        <div class="input-group has-toggle">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="member_password" name="member_password" class="form-control" placeholder="กรอกรหัสผ่านของคุณ" autocomplete="current-password" required>
                            <button type="button" class="btn-toggle-password" id="btnToggleLoginPassword" aria-label="แสดงหรือซ่อนรหัสผ่าน" tabindex="-1">
                                <i class="fas fa-eye" id="toggleLoginPasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- แถวควบคุม: Remember Me & Forgot Password -->
                    <div class="login-control-row">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember_me" value="1" <?php echo $is_remembered ? 'checked' : ''; ?>>
                            <span>จดจำการเข้าสู่ระบบ</span>
                        </label>
                        <a href="forgot_password.php" class="forgot-password-link">ลืมรหัสผ่าน?</a>
                    </div>

                    <!-- รักษาค่า Redirect URL หลังล็อกอินสำเร็จ -->
                    <input type="hidden" name="redirect" value="<?php echo $redirect_target; ?>">

                    <!-- ปุ่มเข้าสู่ระบบ -->
                    <button type="submit" id="btnLoginSubmit" class="btn-register btn-auth-full">
                        <span>เข้าสู่ระบบ</span>
                    </button>

                    <!-- ลิงก์สมัครสมาชิกใหม่ -->
                    <div class="login-footer-register">
                        ยังไม่มีบัญชีใช่ไหม? <a href="register.php<?php echo !empty($redirect_target) ? '?redirect=' . urlencode($redirect_target) : ''; ?>">สมัครสมาชิกที่นี่</a>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <!-- เรียกใช้ไฟล์ JS ฝั่งลูกค้า -->
    <script src="assets/js/member.js?v=<?php echo filemtime('assets/js/member.js'); ?>"></script>
</body>
</html>
