<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../admin/includes/auto_cancel.php';

header('Content-Type: application/json; charset=utf-8');

$date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

// ตรวจสอบรูปแบบวันที่ YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['status' => 'error', 'message' => 'รูปแบบวันที่ไม่ถูกต้อง']);
    exit();
}

try {
    // คำนวณวันในสัปดาห์ (0=อาทิตย์, 1=จันทร์, 2=อังคาร, 3=พุธ, 4=พฤหัส, 5=ศุกร์, 6=เสาร์)
    $dow = intval(date('w', strtotime($date)));
    $day_names = ['วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์'];
    $day_name = $day_names[$dow];

    // กำหนดเรทราคาตามเงื่อนไขของวัน:
    // 1. เสาร์ - อาทิตย์ (0, 6) = 200 ฿ (Peak / Weekend Rate)
    // 2. จันทร์, พุธ, ศุกร์ (1, 3, 5) = 180 ฿ (Standard Weekday Rate)
    // 3. อังคาร, พฤหัสบดี (2, 4) = 150 ฿ (Promo / Discount Rate)
    if ($dow == 0 || $dow == 6) {
        $rate_category = 'weekend';
        $rate_label = 'เสาร์ - อาทิตย์ (200 ฿/ชม.)';
        $rate_badge = 'เสาร์-อาทิตย์ (200฿)';
    } elseif ($dow == 2 || $dow == 4) {
        $rate_category = 'promo';
        $rate_label = 'อังคาร, พฤหัสบดี (150 ฿/ชม.)';
        $rate_badge = 'อังคาร-พฤหัส (150฿)';
    } else {
        $rate_category = 'normal';
        $rate_label = 'จันทร์, พุธ, ศุกร์ (180 ฿/ชม.)';
        $rate_badge = 'จันทร์-พุธ-ศุกร์ (180฿)';
    }

    // 1. ดึงข้อมูลสนามทั้งหมดที่พร้อมใช้งาน
    $stmt_court = $conn->query("
        SELECT court_id, court_name, court_price_per_hour, court_peak_price, court_offpeak_price, court_open_time, court_close_time 
        FROM Court 
        WHERE court_status != 'ปิดปรับปรุง' 
        ORDER BY court_id ASC
    ");
    $courts = $stmt_court->fetchAll(PDO::FETCH_ASSOC);

    // 2. ดึงรายการจองทั้งหมดในวันที่ระบุ (ไม่รวมรายการที่ยกเลิก)
    $stmt_booking = $conn->prepare("
        SELECT booking_id, court_id, booking_start_time, booking_end_time, booking_status 
        FROM Booking 
        WHERE booking_date = :bdate 
        AND booking_status != 'ยกเลิก'
    ");
    $stmt_booking->execute([':bdate' => $date]);
    $bookings = $stmt_booking->fetchAll(PDO::FETCH_ASSOC);

    // 3. กำหนด Time Slots ตั้งแต่ 09:00 ถึง 22:00 น. (13 ช่วงเวลา ช่วงละ 1 ชม.)
    $slots = [];
    for ($h = 9; $h < 22; $h++) {
        $start_str = sprintf("%02d:00", $h);
        $end_str = sprintf("%02d:00", $h + 1);
        $slots[] = [
            'start' => $start_str,
            'end' => $end_str,
            'label' => $start_str . ' - ' . $end_str
        ];
    }

    // 4. ประกอบข้อมูล Matrix สำหรับแต่ละสนามและแต่ละ Slot
    $court_data = [];
    foreach ($courts as $c) {
        $c_id = $c['court_id'];
        $c_open = $c['court_open_time'] ? substr($c['court_open_time'], 0, 5) : '09:00';
        $c_close = $c['court_close_time'] ? substr($c['court_close_time'], 0, 5) : '22:00';

        // คำนวณราคาของสนามนี้ตามประเภทวัน
        $base_rate = floatval($c['court_price_per_hour']) > 0 ? floatval($c['court_price_per_hour']) : 180.00;
        $peak_rate = floatval($c['court_peak_price']) > 0 ? floatval($c['court_peak_price']) : 200.00;
        $offpeak_rate = floatval($c['court_offpeak_price']) > 0 ? floatval($c['court_offpeak_price']) : 150.00;

        if ($rate_category === 'weekend') {
            $slot_price = $peak_rate;
        } elseif ($rate_category === 'promo') {
            $slot_price = $offpeak_rate;
        } else {
            $slot_price = $base_rate;
        }

        $court_slots = [];
        foreach ($slots as $slot) {
            $slot_start = $slot['start'] . ':00';
            $slot_end = $slot['end'] . ':00';

            // ตรวจสอบว่ามีผู้จองแล้วหรือไม่
            $is_booked = false;
            $booking_status = '';
            foreach ($bookings as $b) {
                if ($b['court_id'] == $c_id) {
                    if ($b['booking_start_time'] < $slot_end && $b['booking_end_time'] > $slot_start) {
                        $is_booked = true;
                        $booking_status = $b['booking_status'];
                        break;
                    }
                }
            }

            // สถานะของ Slot นี้
            $status = 'available';
            if ($is_booked) {
                $status = ($booking_status == 'รอตรวจสอบ') ? 'pending' : 'booked';
            } elseif ($slot['start'] < $c_open || $slot['end'] > $c_close) {
                $status = 'closed'; // นอกเวลาทำการ
            }

            $court_slots[$slot['start']] = [
                'status' => $status,
                'rate_category' => $rate_category,
                'price' => $slot_price,
                'slot_start' => $slot['start'],
                'slot_end' => $slot['end']
            ];
        }

        $court_data[] = [
            'court_id' => $c['court_id'],
            'court_name' => $c['court_name'],
            'current_rate' => $slot_price,
            'price_normal' => $base_rate,
            'price_weekend' => $peak_rate,
            'price_promo' => $offpeak_rate,
            'open_time' => $c_open,
            'close_time' => $c_close,
            'slots' => $court_slots
        ];
    }

    echo json_encode([
        'status' => 'success',
        'date' => $date,
        'day_name' => $day_name,
        'day_of_week' => $dow,
        'rate_category' => $rate_category,
        'rate_label' => $rate_label,
        'rate_badge' => $rate_badge,
        'slots' => $slots,
        'courts' => $court_data
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
