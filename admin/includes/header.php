<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin T.S. Pattani'; ?></title>
    <link rel="stylesheet" href="../assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css?v=1.30">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CDN (v11) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="<?php echo get_csrf_token(); ?>">
    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body>

    <div class="admin-layout">
        
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-content">
            <div class="top-navbar">
                <h3><?php echo $page_header ?? '<i class="fas fa-tachometer-alt"></i> แดชบอร์ดภาพรวมระบบ'; ?></h3>
                <div class="user-profile">
                    <i class="fas fa-user-circle"></i> 
                    <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <?php 
                // เก็บค่า Flash Message แล้วล้าง Session ทันที เพื่อนำไปยิง SweetAlert2 Toast ใน JavaScript
                $flash_success = $_SESSION['success'] ?? null;
                $flash_error = $_SESSION['error'] ?? null;
                unset($_SESSION['success'], $_SESSION['error']);
            ?>
            <?php if ($flash_success): ?>
                <div id="flashSuccessData" class="d-none" data-message="<?php echo htmlspecialchars($flash_success); ?>"></div>
            <?php endif; ?>
            <?php if ($flash_error): ?>
                <div id="flashErrorData" class="d-none" data-message="<?php echo htmlspecialchars($flash_error); ?>"></div>
            <?php endif; ?>
