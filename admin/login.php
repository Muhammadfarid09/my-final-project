<?php
session_start();
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
    <link rel="stylesheet" href="../assets/css/admin.css?v=1.1">
    <!-- นำเข้า Font Awesome สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page"> <!-- ใช้คลาส login-page เพื่อจัดกลางหน้าจอ -->
    
    <div class="login-box">
        <!-- ฝั่งซ้าย: รูปภาพหรือโลโก้สนาม -->
        <div class="login-image-side">
            <div class="login-image-content">
                <!-- หากมีรูปภาพหรือโลโก้สนาม สามารถลบแท็ก <i> ออก แล้วใช้แท็ก <img> ด้านล่างแทนได้เลย -->
                <!-- <i class="fas fa-volleyball-ball"></i> -->
                <img src="../assets/img/ts-pattani-generated.jpg" alt="T.S. Pattani Logo" class="stadium-logo-img" style="max-width: 180px; margin-bottom: 20px; border-radius: 50%; box-shadow: 0 10px 25px rgba(0,0,0,0.4); border: 4px solid rgba(255,255,255,0.1);">
                <h2>T.S. Pattani</h2>
                <p>ระบบบริหารจัดการสนามกีฬาแบบครบวงจร</p>
            </div>
        </div>

        <!-- ฝั่งขวา: ฟอร์มเข้าสู่ระบบ -->
        <div class="login-form-side">
            <form action="actions/login_db.php" method="POST">
                
                <div class="login-logo">
                <i class="fas fa-shield-alt"></i>
                <h3>Admin Login</h3>
            </div>
            
            <p class="text-center text-gray mb-25">ระบบจัดการหลังบ้าน T.S. Pattani</p>

            <!-- ================= บล็อกแสดงแจ้งเตือน (Alerts) ================= -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <!-- ================= สิ้นสุดบล็อกแสดงแจ้งเตือน ================= -->

            <!-- เบอร์โทรศัพท์สำหรับแอดมิน -->
            <div class="form-group text-left">
                <label for="phone">เบอร์โทรศัพท์ (ผู้ดูแลระบบ)</label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="กรอกเบอร์โทรศัพท์" maxlength="10" required>
            </div>

            <!-- รหัสผ่าน -->
            <div class="form-group text-left">
                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน" required>
            </div>
            
            <!-- ใช้คลาส btn-submit แบบมีสีแดง -->
            <button type="submit" class="btn-danger w-full p-10 text-16">เข้าสู่ระบบ (Admin)</button>

            <div class="text-center mt-20">
                <a href="../index.php" class="btn-back">&larr; กลับหน้าเว็บไซต์หลัก</a>
            </div>

            </form>
        </div> <!-- ปิด login-form-side -->
    </div> <!-- ปิด login-box -->

    <!-- เรียกใช้สคริปต์กลางเพื่อให้กล่องแจ้งเตือนหายไปเอง (v=1.21) -->
    <script src="../assets/js/admin.js?v=1.21"></script>

</body>
</html>