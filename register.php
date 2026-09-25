<?php
session_start();
// หากลูกค้าล็อกอินอยู่แล้ว ให้ข้ามหน้าสมัครสมาชิกไปที่หน้าแรกเลย
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
    <title>สมัครสมาชิก - T.S. Pattani Badminton</title>
    
    <!-- เรียกใช้ไฟล์ CSS ของฝั่งลูกค้า (แยกไฟล์แล้ว) -->
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

    <div class="register-container">
        <div class="register-header">
            <h2>สมัครสมาชิก</h2>
            <p>T.S. Pattani Badminton</p>
        </div>

        <!-- แสดงข้อความ Error หากมีข้อผิดพลาด (เช่น เบอร์ซ้ำ) -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- ฟอร์มจะถูกส่งไปที่ actions/register_db.php -->
        <form action="actions/register_db.php" method="POST" onsubmit="return validatePassword()">
            
            <div class="form-group">
                <label for="member_name">ชื่อ - นามสกุล *</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="member_name" name="member_name" class="form-control" placeholder="กรอกชื่อ-นามสกุล" required>
                </div>
            </div>

            <div class="form-group">
                <label for="member_phone">เบอร์โทรศัพท์ (ใช้เข้าสู่ระบบ) *</label>
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="text" id="member_phone" name="member_phone" class="form-control" placeholder="08X-XXX-XXXX" maxlength="10" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="member_gender">เพศ</label>
                    <div class="input-group">
                        <i class="fas fa-venus-mars"></i>
                        <select id="member_gender" name="member_gender" class="form-control" style="padding-left: 40px;" required>
                            <option value="" disabled selected>เลือกเพศ</option>
                            <option value="ชาย">ชาย</option>
                            <option value="หญิง">หญิง</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="member_age">อายุ (ปี)</label>
                    <div class="input-group">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="number" id="member_age" name="member_age" class="form-control" placeholder="ระบุอายุ" min="1" max="100">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="member_occupation">อาชีพ</label>
                <div class="input-group">
                    <i class="fas fa-briefcase"></i>
                    <input type="text" id="member_occupation" name="member_occupation" class="form-control" placeholder="นักเรียน, นักศึกษา, พนักงานบริษัท ฯลฯ">
                </div>
            </div>

            <div class="form-group">
                <label for="member_password">รหัสผ่าน *</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="member_password" class="form-control" placeholder="ตั้งรหัสผ่านอย่างน้อย 6 ตัวอักษร" minlength="6" required>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">ยืนยันรหัสผ่านอีกครั้ง *</label>
                <div class="input-group">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="กรอกรหัสผ่านอีกครั้งให้ตรงกัน" required>
                </div>
            </div>

            <button type="submit" class="btn-register">ยืนยันการสมัครสมาชิก</button>

            <div class="login-link">
                มีบัญชีอยู่แล้วใช่ไหม? <a href="login.php">เข้าสู่ระบบที่นี่</a>
            </div>
        </form>
    </div>

    <!-- เรียกใช้ไฟล์ JS ฝั่งลูกค้า -->
    <script src="assets/js/member.js?v=1.0"></script>
</body>
</html>
