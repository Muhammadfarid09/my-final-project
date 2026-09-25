<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin T.S. Pattani'; ?></title>
    <link rel="stylesheet" href="../assets/css/global.css?v=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css?v=1.21">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
