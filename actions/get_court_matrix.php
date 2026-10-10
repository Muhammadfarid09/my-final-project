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
    $day_names = get_thai_day_names();
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

    // 1. ดึงข้อมูลสนามทั้งหมด (รวมถึงสนามปิดปรับปรุง เพื่อแสดงสถานะให้ผู้ใช้ทราบ)
    $stmt_court = $conn->query("
        SELECT court_id, court_name, court_status, court_price_per_hour, court_peak_price, court_offpeak_price, court_open_time, court_close_time 
        FROM Court 
        ORDER BY court_id ASC
    ");
    $courts = $stmt_court->fetchAll(PDO::FETCH_ASSOC);

    // 2. ดึงรายการจองทั้งหมดในวันที่ระบุ (ไม่รวมรายการที่ยกเลิก) พร้อมชื่อเล่นตามหลัก PDPA
    $stmt_booking = $conn->prepare("
        SELECT b.booking_id, b.court_id, b.booking_start_time, b.booking_end_time, b.booking_status, b.booking_nickname, m.member_name 
        FROM Booking b
        LEFT JOIN Member m ON b.member_id = m.member_id
        WHERE b.booking_date = :bdate 
        AND b.booking_status != 'ยกเลิก'
    ");
    $stmt_booking->execute([':bdate' => $date]);
    $bookings = $stmt_booking->fetchAll(PDO::FETCH_ASSOC);

    // 3. กำหนด Time Slots แบบ Dynamic ตามเวลาเปิด-ปิดจริงของสนามในฐานข้อมูล
    $min_open_hour = 8;
    $max_close_hour = 23;

    if (!empty($courts)) {
        $open_hours = [];
        $close_hours = [];
        foreach ($courts as $c) {
            if (!empty($c['court_open_time'])) {
                $open_hours[] = intval(substr($c['court_open_time'], 0, 2));
            }
            if (!empty($c['court_close_time'])) {
                $close_hours[] = intval(substr($c['court_close_time'], 0, 2));
            }
        }
        if (!empty($open_hours)) {
            $min_open_hour = min($open_hours);
        }
        if (!empty($close_hours)) {
            $max_close_hour = max($close_hours);
        }
    }

    $slots = [];
    for ($h = $min_open_hour; $h < $max_close_hour; $h++) {
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
        $c_status = $c['court_status'] ?? 'ว่าง';
        $c_open = $c['court_open_time'] ? substr($c['court_open_time'], 0, 5) : sprintf("%02d:00", $min_open_hour);
        $c_close = $c['court_close_time'] ? substr($c['court_close_time'], 0, 5) : sprintf("%02d:00", $max_close_hour);

        // คำนวณราคาของสนามนี้ตามประเภทวัน
        $slot_price = calculate_court_hourly_rate($c, $date);

        $court_slots = [];
        foreach ($slots as $slot) {
            $slot_start = $slot['start'] . ':00';
            $slot_end = $slot['end'] . ':00';

            // ตรวจสอบว่ามีผู้จองแล้วหรือไม่
            $is_booked = false;
            $booking_status = '';
            $booked_nickname = '';
            foreach ($bookings as $b) {
                if ($b['court_id'] == $c_id) {
                    if ($b['booking_start_time'] < $slot_end && $b['booking_end_time'] > $slot_start) {
                        $is_booked = true;
                        $booking_status = $b['booking_status'];
                        // PDPA Privacy: แสดงเฉพาะชื่อเล่น หากไม่มีให้ใช้ชื่อตัวแรก ไม่แสดงนามสกุล
                        if (!empty($b['booking_nickname'])) {
                            $booked_nickname = $b['booking_nickname'];
                        } elseif (!empty($b['member_name'])) {
                            $parts = explode(' ', trim($b['member_name']));
                            $booked_nickname = $parts[0];
                        } else {
                            $booked_nickname = 'ผู้ใช้งาน';
                        }
                        break;
                    }
                }
            }

            // สถานะของ Slot นี้ (Available, Selected, Pending, Booked, Maintenance, Closed)
            $status = 'available';
            $booked_by = '';
            if ($c_status === 'ปิดปรับปรุง') {
                $status = 'maintenance';
            } elseif ($is_booked) {
                $status = ($booking_status == 'รอตรวจสอบ') ? 'pending' : 'booked';
                $booked_by = $booked_nickname;
            } elseif ($slot['start'] < $c_open || $slot['end'] > $c_close) {
                $status = 'closed'; // นอกเวลาทำการ
            }

            $court_slots[$slot['start']] = [
                'status' => $status,
                'booked_by' => $booked_by,
                'rate_category' => $rate_category,
                'price' => $slot_price,
                'slot_start' => $slot['start'],
                'slot_end' => $slot['end']
            ];
        }

        $court_data[] = [
            'court_id' => $c['court_id'],
            'court_name' => $c['court_name'],
            'court_status' => $c_status,
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
