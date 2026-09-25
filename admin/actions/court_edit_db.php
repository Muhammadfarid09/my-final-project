<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $court_id = intval($_POST['court_id']);
    $court_name = trim($_POST['court_name']);
    $court_price_per_hour = floatval($_POST['court_price_per_hour']);
    $court_open_time = !empty($_POST['court_open_time']) ? $_POST['court_open_time'] : null;
    $court_close_time = !empty($_POST['court_close_time']) ? $_POST['court_close_time'] : null;
    
    $court_peak_price = !empty($_POST['court_peak_price']) ? floatval($_POST['court_peak_price']) : null;
    $court_offpeak_price = !empty($_POST['court_offpeak_price']) ? floatval($_POST['court_offpeak_price']) : null;

    try {
        $stmt = $conn->prepare("UPDATE COURT SET 
                                court_name = :name, 
                                court_price_per_hour = :price,
                                court_open_time = :open_time,
                                court_close_time = :close_time,
                                court_peak_price = :peak_price,
                                court_offpeak_price = :offpeak_price
                                WHERE court_id = :id");
        $stmt->execute([
            ':name' => $court_name,
            ':price' => $court_price_per_hour,
            ':open_time' => $court_open_time,
            ':close_time' => $court_close_time,
            ':peak_price' => $court_peak_price,
            ':offpeak_price' => $court_offpeak_price,
            ':id' => $court_id
        ]);

        $_SESSION['success'] = "อัปเดตข้อมูลสนามเรียบร้อยแล้ว";
    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการแก้ไข: " . $e->getMessage();
    }
}

header("Location: ../courts.php");
exit();
?>
