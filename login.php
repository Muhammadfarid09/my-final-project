<?php
session_start();
// หากลูกค้าล็อกอินอยู่แล้ว ให้ข้ามหน้า Login ไปที่หน้าแรก (index.php) ทันที
if (isset($_SESSION['member_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - T.S. Pattani</title>
    
    <!-- เรียกใช้ไฟล์ CSS ของฝั่งลูกค้า (v=1.0) -->
    <link rel="stylesheet" href="assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    
    <!-- นำเข้า Font Awesome สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- นำเข้า Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-register">

    <!-- เลเยอร์ทำภาพพื้นหลังให้มืดลง -->
    <div class="overlay"></div>

    <div class="register-container auth-card-sm">
        <!-- ฟอร์มส่งไปประมวลผลที่ actions/login_db.php -->
        <form action="actions/login_db.php" method="POST">
            
            <div class="register-header auth-header-mb">
                <h2>เข้าสู่ระบบสมาชิก</h2>
                <p>ระบบจัดการและจองสนาม T.S. Pattani</p>
            </div>

            <!-- ============================================== -->
            <!-- บล็อกแสดงแจ้งเตือน Error หรือ Success (สำคัญมาก) -->
            <!-- ============================================== -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- เบอร์โทรศัพท์ (ใช้เป็น Username) -->
            <div class="form-group">
                <label for="member_phone">เบอร์โทรศัพท์</label>
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <!-- แก้ชื่อ name เป็น member_phone ให้ตรงกับฝั่งรับค่า -->
                    <input type="text" id="member_phone" name="member_phone" class="form-control" placeholder="กรอกเบอร์โทรศัพท์ 10 หลัก" maxlength="10" required>
                </div>
            </div>

            <!-- รหัสผ่าน -->
            <div class="form-group">
                <div class="auth-label-row">
                    <label for="member_password" class="auth-label-mb-0">รหัสผ่าน</label>
                    <a href="forgot_password.php" class="auth-forgot-link">ลืมรหัสผ่าน?</a>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <!-- แก้ชื่อ name เป็น member_password ให้ตรงกับฝั่งรับค่า -->
                    <input type="password" id="member_password" name="member_password" class="form-control" placeholder="กรอกรหัสผ่านของคุณ" required>
                </div>
            </div>
            
            <!-- รักษาค่า Redirect URL หลังล็อกอินสำเร็จ -->
            <?php $redirect_target = htmlspecialchars($_GET['redirect'] ?? ($_POST['redirect'] ?? '')); ?>
            <input type="hidden" name="redirect" value="<?php echo $redirect_target; ?>">

            <button type="submit" class="btn-register">เข้าสู่ระบบ</button>

            <div class="login-link">
                ยังไม่มีบัญชีใช่ไหม? <a href="register.php<?php echo !empty($redirect_target) ? '?redirect=' . urlencode($redirect_target) : ''; ?>">สมัครสมาชิกใหม่</a>
            </div>
        </form>
    </div>

    <!-- เรียกใช้ไฟล์ JS ฝั่งลูกค้า เพื่อให้แจ้งเตือน Alert หายไปอัตโนมัติ -->
    <script src="assets/js/member.js?v=1.0"></script>
</body>
</html>
