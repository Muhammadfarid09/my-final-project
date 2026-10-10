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
if (!function_exists('calculate_court_hourly_rate')) {
    /**
     * คำนวณราคาค่าบริการสนามต่อชั่วโมงตามวันในสัปดาห์ (DOW Rate)
     * - เสาร์ - อาทิตย์: Peak Price (200 ฿)
     * - อังคาร, พฤหัสบดี: Off-peak Price (150 ฿)
     * - จันทร์, พุธ, ศุกร์: Base Standard Price (180 ฿)
     */
    function calculate_court_hourly_rate(array $courtData, string $date): float {
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
        } elseif ($dow === 2 || $dow === 4) {
            return $offpeak_rate;
        } else {
            return $base_rate;
        }
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

        // 3. คำนวณและยกระดับสมาชิก (Tier Progression): Bronze -> Silver (500 pts) -> Gold (1000 pts)
        $stmt_tot = $conn->prepare("SELECT point_total_earned FROM Point WHERE member_id = :mid");
        $stmt_tot->execute([':mid' => $memberId]);
        $total_earned = intval($stmt_tot->fetchColumn() ?: 0);
        $new_level = 'Bronze';
        if ($total_earned >= 1000) {
            $new_level = 'Gold';
        } elseif ($total_earned >= 500) {
            $new_level = 'Silver';
        }
        $conn->prepare("UPDATE Point SET member_level = :lvl WHERE member_id = :mid")
             ->execute([':lvl' => $new_level, ':mid' => $memberId]);

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
