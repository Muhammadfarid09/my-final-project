<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// หากลูกค้าล็อกอินอยู่แล้ว ให้ข้ามหน้าสมัครสมาชิกไปที่หน้าแรกเลย
if (isset($_SESSION['member_id'])) {
    header("Location: index.php");
    exit();
}

// รักษาค่า Redirect URL หลังสมัครสมาชิกสำเร็จ
$redirect_target = htmlspecialchars($_GET['redirect'] ?? ($_POST['redirect'] ?? ''));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - T.S. Pattani Badminton</title>
    
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
        <!-- ก. ฝั่งซ้าย: Brand & Member Privileges Panel (50% Desktop)    -->
        <!-- ============================================================ -->
        <aside class="login-visual-panel">
            <!-- เส้นกราฟิกสนามคอร์ทมินิมอลและแสงสะท้อนสีทอง -->
            <div class="visual-court-pattern"></div>
            <div class="visual-ambient-glow"></div>

            <div class="visual-panel-content">
                <!-- ส่วนบน: Capsule Badge -->
                <div class="visual-panel-top">
                    <div class="visual-badge-capsule">
                        <i class="fas fa-id-card text-gold"></i>
                        <span>🏸 T.S. Pattani Membership Portal</span>
                    </div>
                </div>

                <!-- ส่วนกลาง: Hero Heading & Floating Privilege Cards -->
                <div class="visual-panel-center">
                    <div class="visual-brand-heading">
                        <h1 class="visual-hero-title">
                            สมัครสมาชิกใหม่ <br>
                            <span class="text-gold-highlight">T.S. Pattani Badminton</span>
                        </h1>
                        <p class="visual-hero-subtitle">
                            เริ่มต้นประสบการณ์การจองสนามระดับพรีเมียม สะสมแต้มรับของรางวัลและสิทธิพิเศษมากมาย
                        </p>
                    </div>

                    <!-- การ์ดสรุปสิทธิประโยชน์ของสมาชิก 3 ใบ -->
                    <div class="visual-floating-cards">
                        <div class="visual-highlight-card">
                            <div class="visual-card-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="visual-card-text">
                                <h3 class="visual-card-title">สะสมแต้มทุกการเล่น (TS Points)</h3>
                                <p class="visual-card-desc">รับคะแนนสะสมอัตโนมัติทุกชั่วโมงคอร์ท แลกชั่วโมงฟรีและของรางวัล</p>
                            </div>
                        </div>

                        <div class="visual-highlight-card">
                            <div class="visual-card-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="visual-card-text">
                                <h3 class="visual-card-title">จองคอร์ทไวในคลิกเดียว</h3>
                                <p class="visual-card-desc">บันทึกข้อมูลส่วนตัว ระบบล็อกคอร์ททันใจ ไม่ต้องกรอกซ้ำทุกรอบ</p>
                            </div>
                        </div>

                        <div class="visual-highlight-card">
                            <div class="visual-card-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="visual-card-text">
                                <h3 class="visual-card-title">สิทธิ์เข้าร่วมกิจกรรมเฉพาะสมาชิก</h3>
                                <p class="visual-card-desc">รับสิทธิ์แข่งขันทัวร์นาเมนต์ แคมเปญวันเกิด และส่วนลดพิเศษตลอดปี</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ส่วนล่าง: Footer Security & Trust Tag -->
                <div class="visual-panel-bottom">
                    <span class="visual-footer-text">
                        <i class="fas fa-user-shield text-gold mr-6"></i> ข้อมูลส่วนบุคคลของคุณได้รับการปกป้องตามมาตรฐานความปลอดภัย
                    </span>
                </div>
            </div>
        </aside>

        <!-- ============================================================ -->
        <!-- ข. ฝั่งขวา: Interaction & Register Form Panel (50%)          -->
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

            <!-- กล่องคอนเทนเนอร์การ์ดฟอร์มลอยตัว (Floating Register Card) -->
            <div class="register-card-floating">
                
                <div class="login-card-header">
                    <div class="login-brand-tag">
                        <i class="fas fa-user-plus text-gold"></i>
                        <span>NEW MEMBER REGISTRATION</span>
                    </div>
                    <h2 class="login-card-title">สมัครสมาชิก</h2>
                    <p class="login-card-desc">กรอกข้อมูลเบื้องต้นเพื่อเปิดใช้งานบัญชีสมาชิกและสะสมคะแนน</p>
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
                    <div class="form-group-dark">
                        <label for="member_name" class="form-label-dark">ชื่อ - นามสกุล <span class="text-danger">*</span></label>
                        <div class="input-group-dark">
                            <i class="fas fa-user input-icon-dark"></i>
                            <input type="text" id="member_name" name="member_name" class="form-control-dark" placeholder="กรอกชื่อ-นามสกุล" autocomplete="name" required>
                        </div>
                    </div>

                    <!-- 2. เบอร์โทรศัพท์ -->
                    <div class="form-group-dark">
                        <label for="member_phone" class="form-label-dark">เบอร์โทรศัพท์ (ใช้เข้าสู่ระบบ) <span class="text-danger">*</span></label>
                        <div class="input-group-dark">
                            <i class="fas fa-phone input-icon-dark"></i>
                            <input type="tel" id="member_phone" name="member_phone" class="form-control-dark" placeholder="08X-XXX-XXXX" maxlength="10" inputmode="tel" autocomplete="tel" required>
                        </div>
                    </div>

                    <!-- แถวสองคอลัมน์: เพศ และ อายุ -->
                    <div class="form-row-dark">
                        <!-- 3. เพศ -->
                        <div class="form-group-dark">
                            <label for="member_gender" class="form-label-dark">เพศ</label>
                            <div class="input-group-dark select-wrap-dark">
                                <i class="fas fa-venus-mars input-icon-dark"></i>
                                <select id="member_gender" name="member_gender" class="form-control-dark select-control-dark" required>
                                    <option value="" disabled selected>เลือกเพศ</option>
                                    <option value="ชาย">ชาย</option>
                                    <option value="หญิง">หญิง</option>
                                    <option value="อื่นๆ / ไม่ระบุ">อื่นๆ / ไม่ระบุ</option>
                                </select>
                            </div>
                        </div>

                        <!-- 4. อายุ (ปี) -->
                        <div class="form-group-dark">
                            <label for="member_age" class="form-label-dark">อายุ (ปี)</label>
                            <div class="input-group-dark">
                                <i class="fas fa-calendar-alt input-icon-dark"></i>
                                <input type="number" id="member_age" name="member_age" class="form-control-dark" placeholder="ระบุอายุ" min="5" max="100">
                            </div>
                        </div>
                    </div>

                    <!-- 5. อาชีพ (Dropdown สไตล์ดาร์กพรีเมียม) -->
                    <div class="form-group-dark">
                        <label for="member_occupation" class="form-label-dark">อาชีพ</label>
                        <div class="input-group-dark select-wrap-dark">
                            <i class="fas fa-briefcase input-icon-dark"></i>
                            <select id="member_occupation" name="member_occupation" class="form-control-dark select-control-dark">
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
                    <div class="form-group-dark">
                        <label for="password" class="form-label-dark">รหัสผ่าน <span class="text-danger">*</span></label>
                        <div class="input-group-dark has-toggle">
                            <i class="fas fa-lock input-icon-dark"></i>
                            <input type="password" id="password" name="member_password" class="form-control-dark" placeholder="ตั้งรหัสผ่านอย่างน้อย 6 ตัวอักษร" minlength="6" autocomplete="new-password" required>
                            <button type="button" class="btn-toggle-password-dark" id="btnTogglePassword" aria-label="แสดงหรือซ่อนรหัสผ่าน" tabindex="-1">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- 7. ยืนยันรหัสผ่านอีกครั้ง พร้อมปุ่ม Toggle ลูกตา -->
                    <div class="form-group-dark">
                        <label for="confirm_password" class="form-label-dark">ยืนยันรหัสผ่านอีกครั้ง <span class="text-danger">*</span></label>
                        <div class="input-group-dark has-toggle">
                            <i class="fas fa-check-circle input-icon-dark"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control-dark" placeholder="กรอกรหัสผ่านอีกครั้งให้ตรงกัน" minlength="6" autocomplete="new-password" required>
                            <button type="button" class="btn-toggle-password-dark" id="btnToggleConfirmPassword" aria-label="แสดงหรือซ่อนรหัสผ่าน" tabindex="-1">
                                <i class="fas fa-eye" id="toggleConfirmPasswordIcon"></i>
                            </button>
                        </div>
                        <div id="passwordMatchHint" class="password-match-hint"></div>
                    </div>

                    <!-- รักษาค่า Redirect URL หลังสมัครสมาชิกสำเร็จ -->
                    <input type="hidden" name="redirect" value="<?php echo $redirect_target; ?>">

                    <button type="submit" id="btnRegisterSubmit" class="btn-register-gold-full">
                        <span>ยืนยันการสมัครสมาชิก</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>

                    <div class="login-footer-register-dark">
                        มีบัญชีอยู่แล้วใช่ไหม? <a href="login.php<?php echo !empty($redirect_target) ? '?redirect=' . urlencode($redirect_target) : ''; ?>">เข้าสู่ระบบที่นี่</a>
                    </div>
                </form>
            </div>

            <!-- ข้อความลิขสิทธิ์ด้านล่างสุด -->
            <div class="login-form-bottom-info">
                © <?php echo date('Y'); ?> T.S. Pattani Badminton Court. All rights reserved.
            </div>

        </main>
    </div>

    <!-- เรียกใช้ไฟล์ JS ฝั่งลูกค้า -->
    <script src="assets/js/member.js?v=<?php echo filemtime('assets/js/member.js'); ?>"></script>
</body>
</html>
