<?php
/**
 * T.S. Pattani Badminton System - Central Helper Functions (DRY Framework)
 * รวบรวมฟังก์ชันช่วยเหลือและตรรกะคำนวณกลางตามหลัก Don't Repeat Yourself
 */

// =========================================================================
// 1. Booking Status Constants (มาตรฐานสถานะการจองตามสคีมาฐานข้อมูล)
// =========================================================================
if (!defined('STATUS_BOOKING_PENDING'))   define('STATUS_BOOKING_PENDING', 'รอตรวจสอบ');
if (!defined('STATUS_BOOKING_CONFIRMED')) define('STATUS_BOOKING_CONFIRMED', 'จองแล้ว');
if (!defined('STATUS_BOOKING_CANCELLED')) define('STATUS_BOOKING_CANCELLED', 'ยกเลิก');

// =========================================================================
// 2. Date & Time Helpers (การจัดรูปแบบวันที่และเวลาภาษาไทย)
// =========================================================================
if (!function_exists('format_thai_date')) {
    function format_thai_date($datetime, string $format = 'd/m/Y'): string {
        if (empty($datetime)) return '';
        $ts = is_numeric($datetime) ? intval($datetime) : strtotime($datetime);
        if (!$ts) return '';
        return date($format, $ts);
    }
}

if (!function_exists('format_thai_datetime')) {
    function format_thai_datetime($datetime): string {
        return format_thai_date($datetime, 'd/m/Y H:i');
    }
}

if (!function_exists('get_thai_months')) {
    function get_thai_months(): array {
        return [
            '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
            '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
            '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
        ];
    }
}

if (!function_exists('get_thai_day_names')) {
    function get_thai_day_names(): array {
        return ['วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์'];
    }
}

if (!function_exists('format_baht')) {
    function format_baht(float $amount, int $decimals = 2): string {
        return number_format($amount, $decimals);
    }
}

// =========================================================================
// 3. Court & Pricing Business Logic (การคำนวณราคาสนามและตรวจความพร้อม)
// =========================================================================
if (!function_exists('calculate_court_booking_price')) {
    /**
     * คำนวณราคาค่าบริการสนามแบดมินตันตามวันและช่วงเวลา (Time-Window & Cross-Window Pricing Engine)
     * - เสาร์-อาทิตย์: ทุกช่วงเวลา (08:00 - 23:00 น.) = 200 ฿/ชม.
     * - จันทร์-ศุกร์ (กลางวัน): 08:00 - 17:00 น. = 150 ฿/ชม.
     * - จันทร์-ศุกร์ (ช่วงเย็น): 17:00 - 23:00 น. = 180 ฿/ชม.
     * รองรับการจองคาบเกี่ยวช่วงเวลา (Cross-window booking เช่น 16:00 - 18:00 น. = 150 + 180 = 330 ฿)
     *
     * @param string $date วันที่จอง (YYYY-MM-DD)
     * @param string $startTime เวลาเริ่ม (เช่น '16:00' หรือ '16:00:00')
     * @param string $endTime เวลาสิ้นสุด (เช่น '18:00' หรือ '18:00:00')
     * @param array|null $courtData ข้อมูลสนาม (กรณีดึงเรทเฉพาะสนามหรือเวลาเปิด-ปิด)
     * @return array ผลลัพธ์การคำนวณพร้อม Breakdown และสถานะความถูกต้อง
     */
    function calculate_court_booking_price(string $date, string $startTime, string $endTime, ?array $courtData = null): array {
        // 1. ตรวจสอบความถูกต้องของวันที่ (YYYY-MM-DD และต้องมีอยู่จริงตามปฏิทิน)
        $date = trim($date);
        if (empty($date) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $date_matches)) {
            return ['status' => 'error', 'message' => 'รูปแบบวันที่ไม่ถูกต้อง'];
        }
        $year = intval($date_matches[1]);
        $month = intval($date_matches[2]);
        $day = intval($date_matches[3]);
        if (!checkdate($month, $day, $year)) {
            return ['status' => 'error', 'message' => 'วันที่ระบุไม่มีอยู่จริงตามปฏิทิน'];
        }

        // 2. ตรวจสอบรูปแบบเวลาอย่างเข้มงวด (ต้องอยู่ในกรอบ 00:00 - 23:59 เท่านั้น ไม่อนุญาต 24:00)
        $startTime = trim($startTime);
        $endTime = trim($endTime);
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $startTime, $start_matches) ||
            !preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $endTime, $end_matches)) {
            return ['status' => 'error', 'message' => 'รูปแบบเวลาไม่ถูกต้อง'];
        }

        $s_hi = sprintf('%02d:%02d', intval($start_matches[1]), intval($start_matches[2]));
        $e_hi = sprintf('%02d:%02d', intval($end_matches[1]), intval($end_matches[2]));

        $s_time_dec = intval($start_matches[1]) + (intval($start_matches[2]) / 60);
        $e_time_dec = intval($end_matches[1]) + (intval($end_matches[2]) / 60);

        if ($s_time_dec >= $e_time_dec) {
            return ['status' => 'error', 'message' => 'เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด'];
        }

        $c_open = !empty($courtData['court_open_time']) ? substr($courtData['court_open_time'], 0, 5) : '08:00';
        $c_close = !empty($courtData['court_close_time']) ? substr($courtData['court_close_time'], 0, 5) : '23:00';

        if ($s_hi < $c_open || $e_hi > $c_close) {
            return ['status' => 'error', 'message' => "ช่วงเวลาที่เลือกอยู่นอกเวลาทำการ ({$c_open} - {$c_close} น.)"];
        }

        $hours = $e_time_dec - $s_time_dec;
        if ($hours < 1) {
            return ['status' => 'error', 'message' => 'ระยะเวลาการจองต้องอย่างน้อย 1 ชั่วโมง'];
        }

        // 3. กำหนดเรทราคา (ดึงจากฐานข้อมูลสนาม หรือใช้ค่ามาตรฐานระบบ)
        $peak_rate = !empty($courtData['court_peak_price']) && floatval($courtData['court_peak_price']) > 0
            ? floatval($courtData['court_peak_price']) : 200.00;
        $offpeak_rate = !empty($courtData['court_offpeak_price']) && floatval($courtData['court_offpeak_price']) > 0
            ? floatval($courtData['court_offpeak_price']) : 150.00;
        $standard_rate = !empty($courtData['court_price_per_hour']) && floatval($courtData['court_price_per_hour']) > 0
            ? floatval($courtData['court_price_per_hour']) : 180.00;

        $dow = intval(date('w', strtotime($date))); // 0=อาทิตย์, 6=เสาร์

        // 4. กรณีวันเสาร์-อาทิตย์: อัตราเดียวตลอดทั้งวัน (200 ฿/ชม.)
        if ($dow === 0 || $dow === 6) {
            $total = round($hours * $peak_rate, 2);
            if ($total <= 0) {
                return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการคำนวณราคา'];
            }
            return [
                'status' => 'success',
                'total_price' => $total,
                'total_hours' => $hours,
                'rate_type' => 'weekend',
                'daytime_hours' => 0,
                'evening_hours' => 0,
                'weekend_hours' => $hours,
                'breakdown' => [
                    [
                        'label' => 'เสาร์ - อาทิตย์ (ทุกช่วงเวลา)',
                        'hours' => $hours,
                        'rate' => $peak_rate,
                        'subtotal' => $total
                    ]
                ]
            ];
        }

        // 5. กรณีวันจันทร์-ศุกร์: คำนวณแบบคาบเกี่ยวช่วงเวลา (Cross-Window: 08:00-17:00 / 17:00-23:00)
        $day_open_time = min($c_open, '17:00');
        $eve_close_time = max($c_close, '17:00');

        $day_window_start = strtotime($date . ' ' . $day_open_time . ':00');
        $day_window_end   = strtotime($date . ' 17:00:00');
        $eve_window_start = strtotime($date . ' 17:00:00');
        $eve_window_end   = strtotime($date . ' ' . $eve_close_time . ':00');

        $booking_start = strtotime($date . ' ' . $s_hi . ':00');
        $booking_end   = strtotime($date . ' ' . $e_hi . ':00');

        // ส่วนที่ทับซ้อนช่วงกลางวัน (08:00 - 17:00 หรือเวลาเปิดจริง)
        $day_start = max($booking_start, $day_window_start);
        $day_end   = min($booking_end, $day_window_end);
        $day_hours = ($day_end > $day_start) ? ($day_end - $day_start) / 3600 : 0;
        $day_price = round($day_hours * $offpeak_rate, 2);

        // ส่วนที่ทับซ้อนช่วงเย็น (17:00 - 23:00 หรือเวลาปิดจริง)
        $eve_start = max($booking_start, $eve_window_start);
        $eve_end   = min($booking_end, $eve_window_end);
        $eve_hours = ($eve_end > $eve_start) ? ($eve_end - $eve_start) / 3600 : 0;
        $eve_price = round($eve_hours * $standard_rate, 2);

        $total = round($day_price + $eve_price, 2);

        // 6. Fail-Safe Circuit Breaker ป้องกัน Zero Price โดยเด็ดขาด
        if ($total <= 0) {
            return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการคำนวณราคา กรุณาตรวจสอบช่วงเวลาการจอง'];
        }

        $breakdown = [];
        if ($day_hours > 0) {
            $breakdown[] = [
                'label' => 'ช่วงกลางวัน (' . $day_open_time . ' - 17:00 น.)',
                'hours' => $day_hours,
                'rate' => $offpeak_rate,
                'subtotal' => $day_price
            ];
        }
        if ($eve_hours > 0) {
            $breakdown[] = [
                'label' => 'ช่วงเย็น (17:00 - ' . $eve_close_time . ' น.)',
                'hours' => $eve_hours,
                'rate' => $standard_rate,
                'subtotal' => $eve_price
            ];
        }

        return [
            'status' => 'success',
            'total_price' => $total,
            'total_hours' => $hours,
            'rate_type' => ($day_hours > 0 && $eve_hours > 0) ? 'cross_window' : ($day_hours > 0 ? 'daytime' : 'evening'),
            'daytime_hours' => $day_hours,
            'evening_hours' => $eve_hours,
            'weekend_hours' => 0,
            'breakdown' => $breakdown
        ];
    }
}

if (!function_exists('calculate_court_hourly_rate')) {
    /**
     * คำนวณราคาค่าบริการสนามต่อชั่วโมง (Backward Compatible Helper)
     * - เสาร์ - อาทิตย์: Peak Price (200 ฿)
     * - จันทร์ - ศุกร์ (กลางวัน 08:00 - 17:00): Off-peak Price (150 ฿)
     * - จันทร์ - ศุกร์ (ช่วงเย็น 17:00 - 23:00): Standard Price (180 ฿)
     */
    function calculate_court_hourly_rate(array $courtData, string $date, ?string $slotTime = null): float {
        $dow = intval(date('w', strtotime($date)));
        $base_rate = !empty($courtData['court_price_per_hour']) && floatval($courtData['court_price_per_hour']) > 0
            ? floatval($courtData['court_price_per_hour'])
            : 180.00;
        $peak_rate = !empty($courtData['court_peak_price']) && floatval($courtData['court_peak_price']) > 0
            ? floatval($courtData['court_peak_price'])
            : 200.00;
        $offpeak_rate = !empty($courtData['court_offpeak_price']) && floatval($courtData['court_offpeak_price']) > 0
            ? floatval($courtData['court_offpeak_price'])
            : 150.00;

        if ($dow === 0 || $dow === 6) {
            return $peak_rate;
        }

        // กรณีระบุเวลาสล็อต (เช่น '14:00' หรือ '18:00')
        if ($slotTime !== null) {
            $slot_hour = intval(substr(trim($slotTime), 0, 2));
            if ($slot_hour < 17) {
                return $offpeak_rate;
            } else {
                return $base_rate;
            }
        }

        // หากไม่ระบุเวลาสล็อต สำหรับวันธรรมดาให้ใช้เรทช่วงเย็นมาตรฐาน (180.00)
        return $base_rate;
    }
}

if (!function_exists('is_court_available')) {
    /**
     * ตรวจสอบว่าสนามว่างและอยู่ในช่วงเวลาทำการหรือไม่ (Overlap & Availability Check)
     */
    function is_court_available(PDO $conn, int $courtId, string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): bool {
        // 1. ตรวจสอบสถานะสนามและเวลาเปิด-ปิดทำการ
        $stmt_court = $conn->prepare("SELECT court_status, court_open_time, court_close_time FROM Court WHERE court_id = :cid");
        $stmt_court->execute([':cid' => $courtId]);
        $court = $stmt_court->fetch(PDO::FETCH_ASSOC);

        if (!$court || ($court['court_status'] ?? '') === 'ปิดปรับปรุง') {
            return false;
        }

        $c_open = !empty($court['court_open_time']) ? substr($court['court_open_time'], 0, 8) : '08:00:00';
        $c_close = !empty($court['court_close_time']) ? substr($court['court_close_time'], 0, 8) : '23:00:00';

        $s_time = date('H:i:s', strtotime($startTime));
        $e_time = date('H:i:s', strtotime($endTime));

        if ($s_time < $c_open || $e_time > $c_close || $s_time >= $e_time) {
            return false;
        }

        // 2. ตรวจสอบว่ามีการจองซ้อนทับที่ไม่ถูกยกเลิกหรือไม่
        $sql = "
            SELECT COUNT(*) FROM Booking 
            WHERE court_id = :cid 
              AND booking_date = :bdate 
              AND booking_status != 'ยกเลิก'
              AND (booking_start_time < :bend AND booking_end_time > :bstart)
        ";
        $params = [
            ':cid' => $courtId,
            ':bdate' => $date,
            ':bstart' => $s_time,
            ':bend' => $e_time
        ];

        if ($excludeBookingId !== null && $excludeBookingId > 0) {
            $sql .= " AND booking_id != :exclude_id";
            $params[':exclude_id'] = $excludeBookingId;
        }

        $stmt_check = $conn->prepare($sql);
        $stmt_check->execute($params);
        return intval($stmt_check->fetchColumn()) === 0;
    }
}

// =========================================================================
// 4. Loyalty & Points Progression (การคำนวณพอยท์และการยกระดับสมาชิก)
// =========================================================================
if (!function_exists('award_member_points')) {
    /**
     * คำนวณและเพิ่มพอยท์สะสมให้สมาชิก (ทุก 100 บาทได้ 1 พอยท์) พร้อมตรวจสอบเลเวล Tier
     */
    function award_member_points(PDO $conn, int $memberId, float $paidAmount, ?int $bookingId = null, string $note = ''): int {
        if ($memberId <= 0 || $paidAmount < 100) {
            return 0;
        }
        $earned_points = intval(floor($paidAmount / 100));
        if ($earned_points <= 0) {
            return 0;
        }

        // 1. ตรวจสอบหรืออัปเดตตาราง Point
        $check_pt = $conn->prepare("SELECT point_id FROM Point WHERE member_id = :mid");
        $check_pt->execute([':mid' => $memberId]);
        if ($check_pt->fetch()) {
            $conn->prepare("UPDATE Point SET point_balance = point_balance + :pt, point_total_earned = point_total_earned + :pt WHERE member_id = :mid")
                 ->execute([':pt' => $earned_points, ':mid' => $memberId]);
        } else {
            $conn->prepare("INSERT INTO Point (member_id, point_balance, point_total_earned, member_level) VALUES (:mid, :pt, :pt, 'Bronze')")
                 ->execute([':mid' => $memberId, ':pt' => $earned_points]);
        }

        // 2. บันทึก Point_Transaction
        $tx_note = !empty($note) ? $note : ($bookingId ? 'ได้รับพอยท์จากการจองสนามออนไลน์' : 'ได้รับคะแนนจากการซื้อสินค้า/บริการหน้าร้าน (POS)');
        $conn->prepare("
            INSERT INTO Point_Transaction (member_id, booking_id, transaction_type, transaction_point, transaction_note, transaction_date)
            VALUES (:mid, :bid, 'ได้รับ', :pt, :note, NOW())
        ")->execute([
            ':mid' => $memberId,
            ':bid' => $bookingId,
            ':pt' => $earned_points,
            ':note' => $tx_note
        ]);

        // 3. คำนวณและยกระดับสมาชิก (Tier Progression): Bronze (0-99) -> Silver (100-199 pts) -> Gold (200+ pts)
        $stmt_tot = $conn->prepare("SELECT point_balance, point_total_earned FROM Point WHERE member_id = :mid");
        $stmt_tot->execute([':mid' => $memberId]);
        $pt_data = $stmt_tot->fetch(PDO::FETCH_ASSOC);

        $total_earned = intval($pt_data['point_total_earned'] ?? 0);
        $balance = intval($pt_data['point_balance'] ?? 0);
        // ประเมินจากค่าสูงสุดระหว่างแต้มสะสมตลอดชีพกับแต้มคงเหลือ เพื่อป้องกันปัญหาแต้มเหลื่อมล้ำ
        $eval_points = max($total_earned, $balance);

        $new_level = 'Bronze';
        if ($eval_points >= 200) {
            $new_level = 'Gold';
        } elseif ($eval_points >= 100) {
            $new_level = 'Silver';
        }

        $conn->prepare("
            UPDATE Point 
            SET member_level = :lvl, 
                point_total_earned = GREATEST(point_total_earned, :eval_pt) 
            WHERE member_id = :mid
        ")->execute([
            ':lvl' => $new_level, 
            ':eval_pt' => $eval_points, 
            ':mid' => $memberId
        ]);

        return $earned_points;
    }
}

// =========================================================================
// 5. Booking Cancellation & Stock Restoration (การยกเลิกและคืนสต็อกอุปกรณ์)
// =========================================================================
if (!function_exists('cancel_booking_and_restore_stock')) {
    /**
     * คืนสต็อกอุปกรณ์เช่าและอัปเดตสถานะ Booking / Rental เป็น 'ยกเลิก' อย่างสมบูรณ์
     */
    function cancel_booking_and_restore_stock(PDO $conn, int $bookingId): bool {
        if ($bookingId <= 0) return false;

        // 1. ดึงรายการ Rental ของ Booking นี้เพื่อคืน Stock อุปกรณ์
        $stmt_rent = $conn->prepare("
            SELECT product_id, rental_quantity 
            FROM Rental 
            WHERE booking_id = :id AND rental_status IN ('กำลังเช่า', 'รอตรวจสอบ')
        ");
        $stmt_rent->execute([':id' => $bookingId]);
        $rentals = $stmt_rent->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rentals as $rent) {
            $stmt_stock = $conn->prepare("UPDATE Product SET product_stock = product_stock + :qty WHERE product_id = :p_id");
            $stmt_stock->execute([
                ':qty' => $rent['rental_quantity'],
                ':p_id' => $rent['product_id']
            ]);
        }

        // 2. อัปเดตสถานะ Rental เป็น 'ยกเลิก'
        $stmt_cancel_rent = $conn->prepare("UPDATE Rental SET rental_status = 'ยกเลิก' WHERE booking_id = :id AND rental_status != 'ยกเลิก'");
        $stmt_cancel_rent->execute([':id' => $bookingId]);

        // 3. อัปเดตสถานะ Booking เป็น 'ยกเลิก'
        $stmt_cancel_book = $conn->prepare("UPDATE Booking SET booking_status = 'ยกเลิก' WHERE booking_id = :id");
        $stmt_cancel_book->execute([':id' => $bookingId]);

        return true;
    }
}

// =========================================================================
// 6. Secure Image Upload (ฟังก์ชันอัปโหลดรูปภาพมาตรฐานความปลอดภัยสูง)
// =========================================================================
if (!function_exists('handle_secure_image_upload')) {
    /**
     * ตรวจสอบความถูกต้องของไฟล์รูปภาพและอัปโหลดอย่างปลอดภัย (Size + Ext + MIME Check)
     */
    function handle_secure_image_upload(array $file, string $targetFolder, int $maxSizeMb = 5, array $allowedExts = ['jpg', 'jpeg', 'png', 'webp']): string {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดไฟล์ (Error code: " . ($file['error'] ?? 'UNKNOWN') . ")");
        }

        if ($file['size'] > ($maxSizeMb * 1024 * 1024)) {
            throw new Exception("ขนาดไฟล์ต้องไม่เกิน {$maxSizeMb}MB");
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            throw new Exception("รองรับเฉพาะไฟล์ประเภท (" . strtoupper(implode(', ', $allowedExts)) . ") เท่านั้น");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $valid_mimes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif'
        ];
        $expected_mime = $valid_mimes[$ext] ?? null;
        if (!$expected_mime || $mime !== $expected_mime) {
            throw new Exception("รูปแบบไฟล์รูปภาพไม่ถูกต้อง หรือไฟล์ถูกดัดแปลงนามสกุล");
        }

        // 4. Magic Bytes & Image Integrity Verification (ตรวจสอบความสมบูรณ์และมิติของภาพจริง)
        $image_info = @getimagesize($file['tmp_name']);
        if ($image_info === false || empty($image_info[0]) || empty($image_info[1])) {
            throw new Exception("ไฟล์ที่อัปโหลดไม่ใช่ไฟล์รูปภาพที่สมบูรณ์ หรือโครงสร้างไฟล์รูปภาพเสียหาย");
        }

        // 5. Anti-Polyglot & Malicious Script Injection Inspection (ป้องกันการฝัง Web-Shell ในรูปภาพ)
        $file_sample = @file_get_contents($file['tmp_name'], false, null, 0, 8192);
        if ($file_sample !== false) {
            $dangerous_patterns = [
                '<\?(?:php|=|\s)',
                '<script[\s>]',
                '(?:eval|base64_decode|passthru|shell_exec|system)\s*\('
            ];
            foreach ($dangerous_patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $file_sample)) {
                    throw new Exception("ระบบตรวจพบลักษณะไฟล์ที่ไม่ปลอดภัย (Security Violation: Potential Script Injection)");
                }
            }
        }

        if (!is_dir($targetFolder)) {
            mkdir($targetFolder, 0777, true);
        }

        $new_filename = uniqid('img_', true) . '.' . $ext;
        $dest_path = rtrim($targetFolder, '/\\') . DIRECTORY_SEPARATOR . $new_filename;

        if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
            throw new Exception("ไม่สามารถบันทึกไฟล์รูปภาพลงในเซิร์ฟเวอร์ได้");
        }

        return $new_filename;
    }
}

// =========================================================================
// 8. Member Leaderboard & PDPA Helpers (ทำเนียบผู้เล่นและการคุ้มครองข้อมูล)
// =========================================================================
if (!function_exists('mask_phone_number')) {
    /**
     * มาสก์เบอร์โทรศัพท์เพื่อการแสดงผลสาธารณะตามมาตรฐาน PDPA (เช่น 081-XXX-5678)
     */
    function mask_phone_number(?string $phone): string {
        if (empty($phone)) return '-';
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleaned) === 10) {
            return substr($cleaned, 0, 3) . '-XXX-' . substr($cleaned, 6, 4);
        }
        return $phone;
    }
}

if (!function_exists('get_top_booking_members')) {
    /**
     * ดึงข้อมูลอันดับสมาชิกที่จองสำเร็จสูงสุดจากฐานข้อมูลจริง (Top Players Leaderboard)
     */
    function get_top_booking_members(PDO $conn, int $limit = 3): array {
        $results = [];
        try {
            $sql = "SELECT m.member_id, m.member_name, m.member_phone, m.member_avatar, 
                           IFNULL(p.member_level, 'Bronze') as member_level, 
                           IFNULL(p.point_total_earned, 0) as point_total_earned,
                           COUNT(b.booking_id) as booking_count 
                    FROM Member m 
                    LEFT JOIN Point p ON m.member_id = p.member_id
                    JOIN Booking b ON m.member_id = b.member_id 
                    WHERE b.booking_status = :status 
                    GROUP BY m.member_id, m.member_name, m.member_phone, m.member_avatar, p.member_level, p.point_total_earned
                    ORDER BY booking_count DESC, point_total_earned DESC, m.member_id ASC
                    LIMIT :limit";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':status', STATUS_BOOKING_CONFIRMED, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $results = [];
        }

        return $results;
    }
}


// =========================================================================
// 8. Enterprise Audit Trail & Activity Logging Engine
// =========================================================================
if (!function_exists('log_activity')) {
    /**
     * บันทึกประวัติการทำงานของระบบ (Enterprise Audit Trail Engine)
     *
     * @param PDO $conn ออบเจกต์การเชื่อมต่อฐานข้อมูล
     * @param string $action ชื่องานหรือพฤติกรรม เช่น 'LOGIN', 'APPROVE_PAYMENT', 'REJECT_PAYMENT', 'POS_CHECKOUT', 'ADJUST_POINT'
     * @param string $entityType ประเภทของเอนทิตี เช่น 'Payment', 'Booking', 'Member', 'Product', 'Court', 'POS'
     * @param int|null $entityId รหัส ID ของเอนทิตีที่เกี่ยวข้อง
     * @param string|array|null $details รายละเอียดเพิ่มเติม (หากเป็น array จะถูกแปลงเป็น JSON string อัตโนมัติ)
     * @param string|null $actorType ประเภทผู้กระทำ 'Admin', 'Member', หรือ 'System' (หากเว้นว่างจะตรวจจับจาก Session อัตโนมัติ)
     * @param int|null $actorId รหัสผู้กระทำ (หากเว้นว่างจะตรวจจับจาก Session อัตโนมัติ)
     * @param string|null $actorName ชื่อผู้กระทำ (หากเว้นว่างจะตรวจจับจาก Session อัตโนมัติ)
     * @return bool สำเร็จหรือไม่
     */
    function log_activity(
        PDO $conn, 
        string $action, 
        string $entityType, 
        ?int $entityId = null, 
        $details = null, 
        ?string $actorType = null, 
        ?int $actorId = null, 
        ?string $actorName = null
    ): bool {
        try {
            // Auto-detect actor from Session if not specified
            if (empty($actorType)) {
                if (isset($_SESSION['admin_id'])) {
                    $actorType = 'Admin';
                    $actorId = $actorId ?? intval($_SESSION['admin_id']);
                    $actorName = $actorName ?? ($_SESSION['admin_name'] ?? 'Admin');
                } elseif (isset($_SESSION['member_id'])) {
                    $actorType = 'Member';
                    $actorId = $actorId ?? intval($_SESSION['member_id']);
                    $actorName = $actorName ?? ($_SESSION['member_name'] ?? 'Member');
                } else {
                    $actorType = 'System';
                    $actorId = null;
                    $actorName = 'System Automated';
                }
            }

            // Convert array details to clean JSON
            if (is_array($details)) {
                $details = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            // Capture IP Address and User Agent
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            if (strpos($ipAddress, ',') !== false) {
                $ipParts = explode(',', $ipAddress);
                $ipAddress = trim($ipParts[0]);
            }
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

            $stmt = $conn->prepare("
                INSERT INTO `Audit_Log` 
                (`actor_type`, `actor_id`, `actor_name`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `user_agent`, `created_at`) 
                VALUES (:actor_type, :actor_id, :actor_name, :action, :entity_type, :entity_id, :details, :ip_address, :user_agent, NOW())
            ");

            return $stmt->execute([
                ':actor_type'  => $actorType,
                ':actor_id'    => $actorId,
                ':actor_name'  => $actorName,
                ':action'      => $action,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
                ':details'     => $details,
                ':ip_address'  => $ipAddress,
                ':user_agent'  => $userAgent
            ]);
        } catch (Throwable $e) {
            // Enterprise resilience: Audit logging must never block business transactions
            error_log('Audit Log Failure: ' . $e->getMessage());
            return false;
        }
    }
}


// =========================================================================
// 9. Discord Webhook Notification Engine
// =========================================================================
if (!function_exists('send_discord_alert')) {
    /**
     * ส่งการแจ้งเตือนแบบ Rich Embeds ไปยัง Discord Webhook
     *
     * @param string $title หัวข้อการแจ้งเตือน
     * @param string $description รายละเอียดสรุป
     * @param array $fields ข้อมูลแบบตาราง Key-Value (Array of ['name' => '...', 'value' => '...', 'inline' => true/false])
     * @param int $color รหัสสีตัวเลข Hex to Decimal (ทอง: 16759069, เขียว: 1097601, แดง: 15746887)
     * @param string|null $imageUrl URL รูปภาพประกอบ (ถ้ามี)
     * @return bool
     */
    function send_discord_alert(string $title, string $description, array $fields = [], int $color = 16759069, ?string $imageUrl = null): bool {
        $webhook_url = defined('DISCORD_WEBHOOK_URL') ? DISCORD_WEBHOOK_URL : '';
        if (empty($webhook_url)) return false;

        try {
            $embed = [
                'title'       => $title,
                'description' => $description,
                'color'       => $color,
                'fields'      => $fields,
                'footer'      => [
                    'text' => 'T.S. Badminton Pattani System • ' . date('d/m/Y H:i:s น.')
                ],
                'timestamp'   => date('c')
            ];

            if (!empty($imageUrl)) {
                $embed['thumbnail'] = ['url' => $imageUrl];
            }

            $payload = json_encode([
                'username'   => 'T.S. Badminton Bot',
                'avatar_url' => 'https://cdn-icons-png.flaticon.com/512/2964/2964514.png',
                'embeds'     => [$embed]
            ], JSON_UNESCAPED_UNICODE);

            if (function_exists('curl_init')) {
                $ch = curl_init($webhook_url);
                curl_setopt_array($ch, [
                    CURLOPT_HTTPHEADER      => ['Content-Type: application/json; charset=utf-8'],
                    CURLOPT_POST            => 1,
                    CURLOPT_POSTFIELDS      => $payload,
                    CURLOPT_RETURNTRANSFER  => true,
                    CURLOPT_SSL_VERIFYPEER  => false,
                    CURLOPT_TIMEOUT         => 4
                ]);
                curl_exec($ch);
                curl_close($ch);
            } else {
                $ctx = stream_context_create([
                    'http' => [
                        'method'  => 'POST',
                        'header'  => "Content-Type: application/json; charset=utf-8\r\n",
                        'content' => $payload,
                        'timeout' => 4
                    ],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ]);
                @file_get_contents($webhook_url, false, $ctx);
            }

            return true;
        } catch (Throwable $e) {
            error_log("Discord Webhook Error: " . $e->getMessage());
            return false;
        }
    }
}

// =========================================================================
// 10. Login Security & Brute-Force Throttling Engine
// =========================================================================
if (!function_exists('get_client_ip_address')) {
    /**
     * ดึง IP Address ของไคลเอนต์อย่างปลอดภัย
     */
    function get_client_ip_address(): string {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        if (strpos($ip, ',') !== false) {
            $parts = explode(',', $ip);
            $ip = trim($parts[0]);
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }
}

if (!function_exists('check_login_throttle')) {
    /**
     * ตรวจสอบว่าบัญชีหรือ IP นี้ถูกระงับการเข้าสู่ระบบชั่วคราวหรือไม่ (Rate Limiting Check)
     * - จำกัดความพยายามไม่เกิน 5 ครั้ง ภายใน 10 นาที
     * - หากเกิน 5 ครั้ง จะระงับการเข้าสู่ระบบชั่วคราวเป็นเวลา 15 นาที นับจากครั้งล่าสุด
     *
     * @param PDO $conn การเชื่อมต่อฐานข้อมูล
     * @param string $identifier เบอร์โทรศัพท์ หรือชื่อผู้ใช้
     * @param string $userType 'Admin' หรือ 'Member'
     * @param int $maxAttempts จำนวนครั้งสูงสุดที่อนุญาต (ค่าเริ่มต้น 5)
     * @param int $decayMinutes ช่วงเวลานับรอบความล้มเหลว (ค่าเริ่มต้น 10 นาที)
     * @param int $lockoutMinutes ระยะเวลาที่ระงับเมื่อเกินกำหนด (ค่าเริ่มต้น 15 นาที)
     * @return array ['allowed' => bool, 'failed_count' => int, 'remaining_attempts' => int, 'remaining_seconds' => int, 'message' => string]
     */
    function check_login_throttle(PDO $conn, string $identifier, string $userType = 'Member', int $maxAttempts = 5, int $decayMinutes = 10, int $lockoutMinutes = 15): array {
        $ip = get_client_ip_address();
        
        try {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as failed_count, MAX(attempted_at) as last_attempt
                FROM `Login_Attempts`
                WHERE (identifier = :identifier OR ip_address = :ip)
                  AND user_type = :user_type
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL :lockout_window MINUTE)
            ");
            $stmt->execute([
                ':identifier'     => $identifier,
                ':ip'             => $ip,
                ':user_type'      => $userType,
                ':lockout_window' => $lockoutMinutes
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $failed_count = (int)($row['failed_count'] ?? 0);
            $last_attempt = $row['last_attempt'] ?? null;

            if ($failed_count >= $maxAttempts && $last_attempt) {
                $last_ts = strtotime($last_attempt);
                $lockout_end = $last_ts + ($lockoutMinutes * 60);
                $remaining_seconds = $lockout_end - time();

                if ($remaining_seconds > 0) {
                    $remaining_minutes = max(1, (int)ceil($remaining_seconds / 60));
                    return [
                        'allowed'            => false,
                        'failed_count'       => $failed_count,
                        'remaining_attempts' => 0,
                        'remaining_seconds'  => $remaining_seconds,
                        'message'            => "คุณพยายามเข้าสู่ระบบผิดพลาดเกินกำหนด ระบบได้ระงับการเข้าใช้งานชั่วคราวเพื่อความปลอดภัย กรุณารออีกประมาณ {$remaining_minutes} นาที ก่อนลองใหม่อีกครั้ง"
                    ];
                }
            }

            return [
                'allowed'            => true,
                'failed_count'       => $failed_count,
                'remaining_attempts' => max(0, $maxAttempts - $failed_count),
                'remaining_seconds'  => 0,
                'message'            => ''
            ];
        } catch (Throwable $e) {
            error_log("Login Throttle Check Failure: " . $e->getMessage());
            return ['allowed' => true, 'failed_count' => 0, 'remaining_attempts' => $maxAttempts, 'remaining_seconds' => 0, 'message' => ''];
        }
    }
}

if (!function_exists('record_login_failure')) {
    /**
     * บันทึกประวัติการล็อกอินล้มเหลวลงตาราง Login_Attempts และ Audit_Log
     *
     * @param PDO $conn การเชื่อมต่อฐานข้อมูล
     * @param string $identifier เบอร์โทรศัพท์ หรือชื่อผู้ใช้
     * @param string $userType 'Admin' หรือ 'Member'
     * @param string $reason สาเหตุความล้มเหลว
     * @return array ['failed_count' => int, 'remaining_attempts' => int, 'message' => string]
     */
    function record_login_failure(PDO $conn, string $identifier, string $userType = 'Member', string $reason = 'รหัสผ่านไม่ถูกต้อง'): array {
        $ip = get_client_ip_address();
        $maxAttempts = 5;
        $lockoutMinutes = 15;

        try {
            // 1. บันทึกลง Login_Attempts
            $stmt = $conn->prepare("
                INSERT INTO `Login_Attempts` (`identifier`, `ip_address`, `user_type`, `attempted_at`)
                VALUES (:identifier, :ip, :user_type, NOW())
            ");
            $stmt->execute([
                ':identifier' => $identifier,
                ':ip'         => $ip,
                ':user_type'  => $userType
            ]);

            // 2. บันทึกลง Audit_Log เพื่อตรวจสอบย้อนหลัง
            log_activity(
                $conn,
                'LOGIN_FAILED',
                $userType,
                null,
                "พยายามเข้าสู่ระบบไม่สำเร็จ: {$identifier} ({$reason})",
                $userType,
                null,
                $identifier
            );

            // 3. ตรวจสอบจำนวนครั้งสะสม
            $check = check_login_throttle($conn, $identifier, $userType, $maxAttempts, 10, $lockoutMinutes);

            if (!$check['allowed']) {
                return [
                    'failed_count'       => $check['failed_count'],
                    'remaining_attempts' => 0,
                    'message'            => $check['message']
                ];
            } else {
                $rem = $check['remaining_attempts'];
                $warning = "เบอร์โทรศัพท์หรือรหัสผ่านไม่ถูกต้อง";
                if ($rem > 0 && $rem < $maxAttempts) {
                    $warning .= " (คุณสามารถลองได้อีก {$rem} ครั้ง ก่อนระบบจะระงับการเข้าสู่ระบบ 15 นาที)";
                }
                return [
                    'failed_count'       => $check['failed_count'],
                    'remaining_attempts' => $rem,
                    'message'            => $warning
                ];
            }
        } catch (Throwable $e) {
            error_log("Record Login Failure Error: " . $e->getMessage());
            return [
                'failed_count'       => 1,
                'remaining_attempts' => 4,
                'message'            => 'เบอร์โทรศัพท์หรือรหัสผ่านไม่ถูกต้อง'
            ];
        }
    }
}

if (!function_exists('clear_login_throttle')) {
    /**
     * ล้างประวัติการพยายามล็อกอินผิดพลาดเมื่อเข้าสู่ระบบสำเร็จ
     *
     * @param PDO $conn การเชื่อมต่อฐานข้อมูล
     * @param string $identifier เบอร์โทรศัพท์ หรือชื่อผู้ใช้
     * @param string $userType 'Admin' หรือ 'Member'
     */
    function clear_login_throttle(PDO $conn, string $identifier, string $userType = 'Member'): void {
        try {
            $ip = get_client_ip_address();
            $stmt = $conn->prepare("
                DELETE FROM `Login_Attempts`
                WHERE (identifier = :identifier OR ip_address = :ip)
                  AND user_type = :user_type
            ");
            $stmt->execute([
                ':identifier' => $identifier,
                ':ip'         => $ip,
                ':user_type'  => $userType
            ]);

            // Housekeeping: ลบประวัติเก่ากว่า 24 ชั่วโมง เพื่อให้ตารางมีขนาดกะทัดรัดเสมอ
            $conn->query("DELETE FROM `Login_Attempts` WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        } catch (Throwable $e) {
            error_log("Clear Login Throttle Error: " . $e->getMessage());
        }
    }
}
