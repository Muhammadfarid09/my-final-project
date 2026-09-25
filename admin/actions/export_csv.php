<?php
session_start();
require_once '../../config/config.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['admin_id'])) {
    die("Access Denied");
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

if (!in_array($type, ['revenue', 'members', 'bookings'])) {
    die("Invalid Export Type");
}

// ตั้งค่า Header สำหรับการดาวน์โหลด CSV
$filename = "export_" . $type . "_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// เปิด Output Stream
$output = fopen('php://output', 'w');

// ใส่ BOM (Byte Order Mark) เพื่อให้ Excel อ่านภาษาไทย (UTF-8) ได้ถูกต้อง
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

try {
    if ($type === 'revenue') {
        // หัวตาราง
        fputcsv($output, ['วันที่', 'รายได้ค่าสนาม (฿)', 'รายได้ค่าเช่า (฿)', 'รายได้ค่าสินค้า (฿)', 'รายได้รวม (฿)']);
        
        $stmt = $conn->query("SELECT revenue_date, revenue_court_amount, revenue_rental_amount, revenue_product_amount, revenue_total_amount 
                              FROM Revenue ORDER BY revenue_date DESC");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['revenue_date'],
                $row['revenue_court_amount'],
                $row['revenue_rental_amount'],
                $row['revenue_product_amount'],
                $row['revenue_total_amount']
            ]);
        }
    } 
    elseif ($type === 'members') {
        // หัวตาราง
        fputcsv($output, ['ID', 'ชื่อ-นามสกุล', 'เบอร์โทรศัพท์', 'เพศ', 'อายุ', 'อาชีพ', 'ระดับสมาชิก', 'แต้มสะสม', 'วันที่สมัคร', 'สถานะ']);
        
        $stmt = $conn->query("SELECT m.member_id, m.member_name, m.member_phone, m.member_gender, m.member_age, m.member_occupation, p.member_level, p.point_balance, m.member_created_at, m.member_status 
                              FROM Member m 
                              LEFT JOIN Point p ON m.member_id = p.member_id 
                              ORDER BY m.member_id ASC");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['member_id'],
                $row['member_name'],
                $row['member_phone'],
                $row['member_gender'],
                $row['member_age'],
                $row['member_occupation'],
                $row['member_level'] ?? 'Bronze',
                $row['point_balance'] ?? 0,
                $row['member_created_at'],
                $row['member_status']
            ]);
        }
    } 
    elseif ($type === 'bookings') {
        // หัวตาราง
        fputcsv($output, ['รหัสการจอง', 'ชื่อลูกค้า', 'สนาม', 'วันที่เล่น', 'เวลา', 'สถานะ', 'วันที่ทำรายการ']);
        
        $sql = "SELECT b.booking_id, m.member_name, c.court_name, b.booking_date, b.booking_start_time, b.booking_end_time, b.booking_status, b.booking_created_at
                FROM Booking b 
                JOIN Member m ON b.member_id = m.member_id
                JOIN Court c ON b.court_id = c.court_id
                ORDER BY b.booking_id DESC";
        $stmt = $conn->query($sql);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $time_str = date('H:i', strtotime($row['booking_start_time'])) . ' - ' . date('H:i', strtotime($row['booking_end_time']));
            fputcsv($output, [
                $row['booking_id'],
                $row['member_name'],
                $row['court_name'],
                $row['booking_date'],
                $time_str,
                $row['booking_status'],
                $row['booking_created_at']
            ]);
        }
    }
} catch(PDOException $e) {
    fputcsv($output, ['Error generating CSV: ' . $e->getMessage()]);
}

fclose($output);
exit();
?>
