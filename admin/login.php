<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// ถ้าระบบจำได้ว่า Admin ล็อกอินอยู่แล้ว ให้เด้งไปหน้า Dashboard เลย ไม่ต้องล็อกอินซ้ำ
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแลระบบ - T.S. Pattani</title>
    <!-- เรียกใช้ไฟล์ CSS ส่วนกลางและของแอดมิน -->
    <link rel="stylesheet" href="../assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css?v=1.41">
    <!-- นำเข้า Font Awesome สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    
    <div class="login-box">
        
        <!-- ส่วนหัว: โลโก้และชื่อระบบ -->
        <div class="login-header">
            <div class="login-logo-wrapper">
                <img src="../assets/img/ts-pattani-generated.jpg" alt="T.S. Pattani Logo" class="login-logo-img">
            </div>
            <h1 class="login-title">T.S. Pattani</h1>
            <div class="login-badge">
                <i class="fas fa-shield-alt"></i> เข้าสู่ระบบผู้ดูแลระบบ
            </div>
            <p class="login-subtitle">ระบบบริหารจัดการสนามกีฬาแบบครบวงจร</p>
        </div>

        <!-- ================= บล็อกแสดงแจ้งเตือน (Alerts) ================= -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> <span><?php echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> <span><?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>
        <!-- ================= สิ้นสุดบล็อกแสดงแจ้งเตือน ================= -->

        <!-- ฟอร์มเข้าสู่ระบบ -->
        <form action="actions/login_db.php" method="POST" class="login-form">
            <?php echo csrf_field(); ?>
            
            <!-- เบอร์โทรศัพท์สำหรับแอดมิน -->
            <div class="form-group">
                <label for="phone" class="form-label">
                    <i class="fas fa-phone-alt"></i> เบอร์โทรศัพท์ (ผู้ดูแลระบบ)
                </label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="กรอกเบอร์โทรศัพท์ 10 หลัก" maxlength="10" autocomplete="tel" required autofocus>
            </div>

            <!-- รหัสผ่าน -->
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> รหัสผ่าน
                </label>
                <input type="password" id="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน" autocomplete="current-password" required>
            </div>
            
            <!-- ปุ่มเข้าสู่ระบบ ดึงโทนสีทองแบรนด์จาก CSS Variables -->
            <button type="submit" class="btn-login-submit">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ (Admin)
            </button>

            <!-- ลิงก์กลับหน้าหลัก -->
            <div class="login-footer">
                <a href="../index.php" class="btn-back-home">
                    <i class="fas fa-arrow-left"></i> กลับหน้าเว็บไซต์หลัก
                </a>
            </div>

        </form>

    </div> <!-- ปิด login-box -->

    <!-- เรียกใช้สคริปต์กลางเพื่อให้กล่องแจ้งเตือนหายไปเอง -->
    <script src="../assets/js/admin.js?v=1.22"></script>

</body>
</html>