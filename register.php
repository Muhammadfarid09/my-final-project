<?php
session_start();
// หากลูกค้าล็อกอินอยู่แล้ว ให้ข้ามหน้าสมัครสมาชิกไปที่หน้าแรกเลย
if (isset($_SESSION['member_id'])) {
    header("Location: index.php");
    exit();
}

$redirect_target = htmlspecialchars($_GET['redirect'] ?? ($_POST['redirect'] ?? ''));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - T.S. Pattani Badminton</title>
    
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

    <div class="register-container auth-card-register">
        <div class="register-header">
            <h2 class="auth-title">สมัครสมาชิก</h2>
            <p class="auth-subtitle">T.S. Pattani Badminton</p>
        </div>

        <!-- แสดงข้อความ Error หากมีข้อผิดพลาด (เช่น เบอร์ซ้ำ) -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> 
                <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- กล่องแจ้งเตือนฝั่ง Client-side (JS Validation) -->
        <div id="registerClientAlertBox"></div>

        <!-- ฟอร์มจะถูกส่งไปที่ actions/register_db.php -->
        <form id="registerForm" action="actions/register_db.php" method="POST" novalidate>
            
            <!-- 1. ชื่อ - นามสกุล -->
            <div class="form-group">
                <label for="member_name" class="form-label">ชื่อ - นามสกุล <span class="text-danger">*</span></label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="member_name" name="member_name" class="form-control" placeholder="กรอกชื่อ-นามสกุล" autocomplete="name" required>
                </div>
            </div>

            <!-- 2. เบอร์โทรศัพท์ -->
            <div class="form-group">
                <label for="member_phone" class="form-label">เบอร์โทรศัพท์ (ใช้เข้าสู่ระบบ) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <i class="fas fa-phone input-icon"></i>
                    <input type="tel" id="member_phone" name="member_phone" class="form-control" placeholder="08X-XXX-XXXX" maxlength="10" inputmode="tel" autocomplete="tel" required>
                </div>
            </div>

            <!-- แถวสองคอลัมน์: เพศ และ อายุ -->
            <div class="form-row">
                <!-- 3. เพศ -->
                <div class="form-group">
                    <label for="member_gender" class="form-label">เพศ</label>
                    <div class="input-group select-wrap">
                        <i class="fas fa-venus-mars input-icon"></i>
                        <select id="member_gender" name="member_gender" class="form-control select-control" required>
                            <option value="" disabled selected>เลือกเพศ</option>
                            <option value="ชาย">ชาย</option>
                            <option value="หญิง">หญิง</option>
                            <option value="อื่นๆ / ไม่ระบุ">อื่นๆ / ไม่ระบุ</option>
                        </select>
                    </div>
                </div>

                <!-- 4. อายุ (ปี) -->
                <div class="form-group">
                    <label for="member_age" class="form-label">อายุ (ปี)</label>
                    <div class="input-group">
                        <i class="fas fa-calendar-alt input-icon"></i>
                        <input type="number" id="member_age" name="member_age" class="form-control" placeholder="ระบุอายุ" min="5" max="100">
                    </div>
                </div>
            </div>

            <!-- 5. อาชีพ (Dropdown สไตล์ตรงกับช่องเพศ 100%) -->
            <div class="form-group">
                <label for="member_occupation" class="form-label">อาชีพ</label>
                <div class="input-group select-wrap">
                    <i class="fas fa-briefcase input-icon"></i>
                    <select id="member_occupation" name="member_occupation" class="form-control select-control">
                        <option value="" disabled selected>เลือกอาชีพ</option>
                        <option value="นักเรียน / นักศึกษา">นักเรียน / นักศึกษา</option>
                        <option value="พนักงานบริษัท / เอกชน">พนักงานบริษัท / เอกชน</option>
                        <option value="ข้าราชการ / รัฐวิสาหกิจ">ข้าราชการ / รัฐวิสาหกิจ</option>
                        <option value="ธุรกิจส่วนตัว / ค้าขาย">ธุรกิจส่วนตัว / ค้าขาย</option>
                        <option value="ฟรีแลนซ์ / รับจ้างทั่วไป">ฟรีแลนซ์ / รับจ้างทั่วไป</option>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>
            </div>

            <!-- 6. รหัสผ่าน พร้อมปุ่ม Toggle ลูกตา -->
            <div class="form-group">
                <label for="password" class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                <div class="input-group has-toggle">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="member_password" class="form-control" placeholder="ตั้งรหัสผ่านอย่างน้อย 6 ตัวอักษร" minlength="6" autocomplete="new-password" required>
                    <button type="button" class="btn-toggle-password" id="btnTogglePassword" aria-label="แสดงหรือซ่อนรหัสผ่าน" tabindex="-1">
                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <!-- 7. ยืนยันรหัสผ่านอีกครั้ง พร้อมปุ่ม Toggle ลูกตา -->
            <div class="form-group">
                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านอีกครั้ง <span class="text-danger">*</span></label>
                <div class="input-group has-toggle">
                    <i class="fas fa-check-circle input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="กรอกรหัสผ่านอีกครั้งให้ตรงกัน" minlength="6" autocomplete="new-password" required>
                    <button type="button" class="btn-toggle-password" id="btnToggleConfirmPassword" aria-label="แสดงหรือซ่อนรหัสผ่าน" tabindex="-1">
                        <i class="fas fa-eye" id="toggleConfirmPasswordIcon"></i>
                    </button>
                </div>
                <div id="passwordMatchHint" class="password-match-hint"></div>
            </div>

            <!-- รักษาค่า Redirect URL หลังสมัครสมาชิกสำเร็จ -->
            <input type="hidden" name="redirect" value="<?php echo $redirect_target; ?>">

            <button type="submit" id="btnRegisterSubmit" class="btn-register btn-auth-full">
                <span>ยืนยันการสมัครสมาชิก</span>
            </button>

            <div class="login-link">
                มีบัญชีอยู่แล้วใช่ไหม? <a href="login.php<?php echo !empty($redirect_target) ? '?redirect=' . urlencode($redirect_target) : ''; ?>">เข้าสู่ระบบที่นี่</a>
            </div>
        </form>
    </div>

    <!-- เรียกใช้ไฟล์ JS ฝั่งลูกค้า -->
    <script src="assets/js/member.js?v=1.2"></script>
</body>
</html>
