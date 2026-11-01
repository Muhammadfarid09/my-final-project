<?php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - ระบบเกิดข้อผิดพลาดชั่วคราว (Internal Server Error) | T.S. Pattani Badminton</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/error-page.css?v=<?php echo file_exists('assets/css/error-page.css') ? filemtime('assets/css/error-page.css') : time(); ?>">
</head>
<body class="error-page-wrapper">
    <div class="error-card">
        <div class="error-badge-status badge-500">
            <i class="fas fa-triangle-exclamation"></i>
            <span>Internal Server Error</span>
        </div>
        
        <div class="error-code-display">500</div>

        <h1 class="error-title">ระบบเกิดข้อผิดพลาดชั่วคราว</h1>
        
        <p class="error-description">
            เซิร์ฟเวอร์พบข้อผิดพลาดที่ไม่คาดคิดในการประมวลผลคำขอของคุณ กรุณาลองใหม่อีกครั้ง หรือกลับสู่หน้าหลักเพื่อเริ่มทำรายการใหม่
        </p>

        <div class="error-actions">
            <button onclick="window.location.reload();" class="btn-error-primary">
                <i class="fas fa-rotate-right"></i>
                <span>ลองใหม่อีกครั้ง</span>
            </button>
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
