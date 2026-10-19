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
    <title>เข้าสู่ระบบผู้ดูแลระบบ - T.S. BADMINTON PATTANI</title>
    <!-- เรียกใช้ไฟล์ CSS ส่วนกลางและของแอดมิน พร้อม Dynamic Cache Buster -->
    <link rel="stylesheet" href="../assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo file_exists(__DIR__ . '/../assets/css/admin.css') ? filemtime(__DIR__ . '/../assets/css/admin.css') : time(); ?>">
    <!-- นำเข้า Font Awesome สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    
    <div class="login-split-card">
        
        <!-- ================= ฝั่งซ้าย: แบรนดิ้งและภาพลักษณ์ (Brand Hero Side) ================= -->
        <div class="login-hero-side">
            <!-- ลายเส้นกราฟิกมินิมอลคอร์ทสนาม (Decorative Perspective Court Lines) -->
            <div class="hero-decorative-graphic" aria-hidden="true">
                <svg viewBox="0 0 420 420" fill="none" xmlns="http://www.w3.org/2000/svg" class="hero-bg-svg">
                    <rect x="50" y="30" width="280" height="200" rx="18" transform="rotate(-14 50 30)" stroke="rgba(255,185,29,0.22)" stroke-width="1.5" />
                    <rect x="80" y="60" width="280" height="200" rx="18" transform="rotate(-14 80 60)" stroke="rgba(255,255,255,0.08)" stroke-width="1.5" />
                    <rect x="110" y="90" width="280" height="200" rx="18" transform="rotate(-14 110 90)" stroke="rgba(255,185,29,0.12)" stroke-width="1.5" />
                    <rect x="140" y="120" width="280" height="200" rx="18" transform="rotate(-14 140 120)" stroke="rgba(255,255,255,0.05)" stroke-width="1.5" />
                    <line x1="60" y1="140" x2="340" y2="70" stroke="rgba(255,185,29,0.18)" stroke-width="1.2" stroke-dasharray="5 4" />
                </svg>
            </div>

            <div class="hero-content">
                <!-- สัญลักษณ์ประกายดาว 8 แฉกสีทอง (Hero Sparkle Icon) -->
                <div class="hero-sparkle-badge">
                    <i class="fas fa-asterisk hero-star-icon"></i>
                </div>

                <!-- ข้อความต้อนรับขนาดใหญ่ (Big Headline) -->
                <h1 class="hero-title">
                    Hello Admin! <span class="wave-emoji">🏸</span>
                </h1>

                <!-- คำอธิบายระบบ (Subtitle & System Mission) -->
                <p class="hero-description">
                    ระบบบริหารจัดการสนามกีฬาแบบครบวงจร ควบคุมการจองสนาม ระบบขายหน้าร้าน (POS) สต็อกอุปกรณ์ และตรวจสอบสลิปอย่างแม่นยำและรวดเร็ว
                </p>

                <!-- จุดเด่นระบบย่อ (Feature Highlights) -->
                <div class="hero-features-list">
                    <div class="hero-feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Role-Based Access Control</span>
                    </div>
                    <div class="hero-feature-item">
                        <i class="fas fa-bolt"></i>
                        <span>Real-time Court & POS Sync</span>
                    </div>
                </div>
            </div>

            <!-- ข้อความลิขสิทธิ์ท้ายการ์ด (Footer Copyright) -->
            <div class="hero-footer">
                <p>© <?php echo date('Y'); ?> T.S. BADMINTON PATTANI Club. By Farid.</p>
            </div>
        </div>

        <!-- ================= ฝั่งขวา: ฟอร์มเข้าสู่ระบบ (Form Side) ================= -->
        <div class="login-form-side">
            <div class="form-container">
                
                <!-- หัวข้อแบรนด์และป้ายสถานะ -->
                <div class="form-brand-header">
                    <div class="brand-identity">
                        <img src="../assets/img/ts-pattani-generated.jpg?v=<?php echo file_exists(__DIR__ . '/../assets/img/ts-pattani-generated.jpg') ? filemtime(__DIR__ . '/../assets/img/ts-pattani-generated.jpg') : time(); ?>" alt="T.S. BADMINTON PATTANI Logo" class="brand-logo-img">
                        <span class="brand-name">T.S. BADMINTON PATTANI</span>
                    </div>
                    <span class="admin-portal-badge">
                        <i class="fas fa-shield-alt"></i> Admin Portal
                    </span>
                </div>

                <div class="form-intro">
                    <h2 class="welcome-heading">Welcome Back!</h2>
                    <p class="welcome-subheading">กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบเพื่อจัดการระบบสนาม</p>
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

                <!-- ฟอร์มเข้าสู่ระบบ พร้อมระบบป้องกัน Double Submission -->
                <form action="actions/login_db.php" method="POST" class="login-form prevent-double-submit" data-loading-text="กำลังเข้าสู่ระบบ...">
                    <?php echo csrf_field(); ?>
                    
                    <!-- เบอร์โทรศัพท์สำหรับแอดมิน -->
                    <div class="form-group">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone-alt"></i> เบอร์โทรศัพท์ (ผู้ดูแลระบบ)
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-mobile-alt input-icon"></i>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="กรอกเบอร์โทรศัพท์ 10 หลัก" maxlength="10" autocomplete="tel" required autofocus>
                        </div>
                    </div>

                    <!-- รหัสผ่าน -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> รหัสผ่าน
                        </label>
                        <div class="input-with-icon password-field-group">
                            <i class="fas fa-key input-icon"></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="กรอกรหัสผ่านของคุณ" autocomplete="current-password" required>
                            <button type="button" class="btn-toggle-password" id="togglePasswordBtn" aria-label="แสดงหรือซ่อนรหัสผ่าน" tabindex="-1">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- ปุ่มเข้าสู่ระบบ ดึงโทนสีทองแบรนด์จาก CSS Variables -->
                    <button type="submit" class="btn-login-submit" id="btnLoginSubmit" data-loading-text="กำลังเข้าสู่ระบบ...">
                        <span>เข้าสู่ระบบ</span>
                    </button>

                    <!-- ลิงก์กลับหน้าหลัก -->
                    <div class="login-footer">
                        <a href="../index.php" class="btn-back-home">
                            <i class="fas fa-arrow-left"></i> กลับสู่หน้าเว็บไซต์หลัก
                        </a>
                    </div>

                </form>

            </div> <!-- ปิด form-container -->
        </div> <!-- ปิด login-form-side -->

    </div> <!-- ปิด login-split-card -->

    <!-- เรียกใช้สคริปต์กลาง พร้อม Dynamic Cache Buster -->
    <script src="../assets/js/admin.js?v=<?php echo file_exists(__DIR__ . '/../assets/js/admin.js') ? filemtime(__DIR__ . '/../assets/js/admin.js') : time(); ?>"></script>

</body>
</html>