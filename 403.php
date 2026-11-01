<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - ปฏิเสธการเข้าถึง (Access Forbidden) | T.S. Pattani Badminton</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/error-page.css?v=<?php echo file_exists('assets/css/error-page.css') ? filemtime('assets/css/error-page.css') : time(); ?>">
</head>
<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-badge-status badge-403">
            <i class="fas fa-shield-halved"></i>
            <span>Access Forbidden</span>
        </div>
        
        <div class="error-code-display code-403">403</div>

        <h1 class="error-title">การเข้าถึงถูกจำกัดสิทธิ์</h1>
        
        <p class="error-description">
            คุณไม่มีสิทธิ์ในการเข้าถึงหน้านี้ หรือจำเป็นต้องเข้าสู่ระบบด้วยบัญชีผู้ใช้ที่มีระดับสิทธิ์ที่เหมาะสม
        </p>

        <div class="error-actions">
            <a href="login.php" class="btn-error-primary">
                <i class="fas fa-sign-in-alt"></i>
                <span>เข้าสู่ระบบ</span>
            </a>
            <a href="index.php" class="btn-error-secondary">
                <i class="fas fa-home"></i>
                <span>กลับสู่หน้าหลัก</span>
            </a>
        </div>

        <div class="error-footer-brand">
            <i class="fas fa-feather-pointed"></i>
            <span>T.S. Pattani Badminton System • ระบบบริการสนามแบดมินตันมาตรฐาน</span>
        </div>
    </div>
</body>
</html>

