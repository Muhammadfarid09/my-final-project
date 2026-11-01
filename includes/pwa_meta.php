<?php
/**
 * T.S. Pattani Badminton System - Global PWA Integration Include
 * ผนวก Web App Manifest, Service Worker, Apple Touch Icons และ Custom Install Prompt
 */
$pwa_base = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
?>
<!-- Web App Manifest & Progressive Web App (PWA) Standards -->
<link rel="manifest" href="<?php echo $pwa_base; ?>manifest.json">
<meta name="theme-color" content="#0F172A">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="T.S. Badminton">
<link rel="apple-touch-icon" href="<?php echo $pwa_base; ?>assets/img/icons/icon-192x192.png">
<link rel="stylesheet" href="<?php echo $pwa_base; ?>assets/css/pwa-install.css?v=<?php echo file_exists(__DIR__ . '/../assets/css/pwa-install.css') ? filemtime(__DIR__ . '/../assets/css/pwa-install.css') : '1.0'; ?>">
<script src="<?php echo $pwa_base; ?>assets/js/pwa-handler.js?v=<?php echo file_exists(__DIR__ . '/../assets/js/pwa-handler.js') ? filemtime(__DIR__ . '/../assets/js/pwa-handler.js') : '1.0'; ?>" defer></script>

