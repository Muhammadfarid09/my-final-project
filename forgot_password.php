<?php
session_start();
// หากลูกค้าล็อกอินอยู่แล้ว ให้ข้ามหน้านี้ไปที่หน้าแรก
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
    <title>ลืมรหัสผ่าน - T.S. Pattani</title>
    
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

    <div class="register-container" style="max-width: 450px;">
        <!-- ฟอร์มส่งไปประมวลผลที่ actions/forgot_password_db.php -->
        <form action="actions/forgot_password_db.php" method="POST" onsubmit="return validateResetPassword()">
            
            <div class="register-header" style="margin-bottom: 20px;">
                <h2>ลืมรหัสผ่าน</h2>
                <p>กรุณากรอกข้อมูลเพื่อยืนยันตัวตนและตั้งรหัสผ่านใหม่</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px;">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="member_phone">เบอร์โทรศัพท์ที่ลงทะเบียน</label>
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="text" id="member_phone" name="member_phone" class="form-control" placeholder="กรอกเบอร์โทรศัพท์ 10 หลัก" maxlength="10" required>
                </div>
            </div>

            <div class="form-group">
                <label for="member_name">ชื่อ-นามสกุลที่ลงทะเบียน</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="member_name" name="member_name" class="form-control" placeholder="ชื่อ-นามสกุล ที่ใช้สมัคร" required>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px dashed #ccc; margin: 20px 0;">

            <div class="form-group">
                <label for="new_password">รหัสผ่านใหม่</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="ตั้งรหัสผ่านใหม่อย่างน้อย 6 ตัวอักษร" minlength="6" required>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" minlength="6" required>
                </div>
            </div>
            
            <button type="submit" class="btn-register">รีเซ็ตรหัสผ่าน</button>

            <div class="login-link">
                จำรหัสผ่านได้แล้ว? <a href="login.php">กลับไปเข้าสู่ระบบ</a>
            </div>
        </form>
    </div>

    <!-- เรียกใช้ไฟล์ JS ฝั่งลูกค้า -->
    <script src="assets/js/member.js?v=1.0"></script>
    <script>
        function validateResetPassword() {
            var password = document.getElementById("new_password").value;
            var confirmPassword = document.getElementById("confirm_password").value;
            var phone = document.getElementById("member_phone").value;

            if(!/^[0-9]{10}$/.test(phone)) {
                alert("กรุณากรอกเบอร์โทรศัพท์ให้ครบ 10 หลัก (เฉพาะตัวเลขเท่านั้น)");
                return false;
            }

            if (password !== confirmPassword) {
                alert("รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>

