<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - ไม่พบหน้าที่คุณต้องการ | T.S. Pattani Badminton</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/error-page.css?v=<?php echo file_exists('assets/css/error-page.css') ? filemtime('assets/css/error-page.css') : time(); ?>">
</head>
<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-badge-status badge-404">
            <i class="fas fa-search-location"></i>
            <span>Page Not Found</span>
        </div>
        
        <div class="error-code-display">404</div>

        <h1 class="error-title">ขออภัย ไม่พบหน้าที่คุณกำลังค้นหา</h1>
        
        <p class="error-description">
            หน้าที่คุณพยายามเข้าถึงอาจถูกย้าย เปลี่ยนชื่อ หรือไม่มีอยู่อีกต่อไป กรุณาตรวจสอบ URL หรือเลือกเมนูที่คุณต้องการด้านล่าง
        </p>

        <div class="error-actions">
            <a href="index.php" class="btn-error-primary">
                <i class="fas fa-home"></i>
                <span>กลับสู่หน้าหลัก</span>
            </a>
            <a href="booking.php" class="btn-error-secondary">
                <i class="fas fa-calendar-check"></i>
                <span>จองสนามแบดมินตัน</span>
            </a>
        </div>

        <div class="error-footer-brand">
            <i class="fas fa-feather-pointed"></i>
            <span>T.S. Pattani Badminton System • ระบบบริการสนามแบดมินตันมาตรฐาน</span>
        </div>
    </div>
</body>
</html>

