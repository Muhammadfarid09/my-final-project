<?php
session_start();
// เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล
require_once '../config/config.php';

// ตรวจสอบว่าล็อกอินอยู่หรือไม่
if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าจากฟอร์ม
    $member_id = $_SESSION['member_id'];
    $court_id = $_POST['court_id'] ?? '';
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    $total_court_price = floatval($_POST['total_court_price']);
    $total_rental_price = floatval($_POST['total_rental_price']);
    $total_product_price = floatval($_POST['total_product_price']);
    $total_price = floatval($_POST['total_price']);
    
    // รับค่า Array ของสินค้า (รหัสสินค้า => จำนวน)
    $products = isset($_POST['products']) ? $_POST['products'] : [];

    try {
        // 2. ตรวจสอบความสมบูรณ์เบื้องต้น
        if (empty($court_id) || empty($start_time) || empty($end_time)) {
            throw new Exception("กรุณาเลือกสนามและเวลาให้ครบถ้วน");
        }

        $start_time_ts = strtotime($start_time);
        $end_time_ts = strtotime($end_time);

        if ($start_time_ts >= $end_time_ts) {
            throw new Exception("เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด");
        }

        // ========================================================
        // 2.5 Server-side Price Recalculation (ป้องกันการปลอมแปลงราคา)
        // คำนวณราคาค่าบริการตามวันในสัปดาห์ (Day-of-Week Pricing)
        // ========================================================
        $hours = ($end_time_ts - $start_time_ts) / 3600;
        if ($hours < 1) {
            throw new Exception("ระยะเวลาการจองต้องอย่างน้อย 1 ชั่วโมง");
        }

        $real_court_price = 0;
        $real_rental_price = 0;
        $real_product_price = 0;

        // คำนวณค่าสนาม
        $stmt_court = $conn->prepare("SELECT court_price_per_hour, court_peak_price, court_offpeak_price FROM Court WHERE court_id = :court_id");
        $stmt_court->execute([':court_id' => $court_id]);
        $court_data = $stmt_court->fetch(PDO::FETCH_ASSOC);
        if (!$court_data) {
            throw new Exception("ไม่พบข้อมูลสนาม");
        }

        // กำหนดเรทราคาตามเงื่อนไขของวัน:
        // - เสาร์ - อาทิตย์ (0, 6) = 200 ฿ (Peak / Weekend Rate)
        // - จันทร์, พุธ, ศุกร์ (1, 3, 5) = 180 ฿ (Standard Rate)
        // - อังคาร, พฤหัสบดี (2, 4) = 150 ฿ (Promo / Discount Rate)
        $dow = intval(date('w', strtotime($booking_date)));
        $base_rate = floatval($court_data['court_price_per_hour']) > 0 ? floatval($court_data['court_price_per_hour']) : 180.00;
        $peak_rate = floatval($court_data['court_peak_price']) > 0 ? floatval($court_data['court_peak_price']) : 200.00;
        $offpeak_rate = floatval($court_data['court_offpeak_price']) > 0 ? floatval($court_data['court_offpeak_price']) : 150.00;

        if ($dow == 0 || $dow == 6) {
            $hourly_rate = $peak_rate; // เสาร์ - อาทิตย์
        } elseif ($dow == 2 || $dow == 4) {
            $hourly_rate = $offpeak_rate; // อังคาร, พฤหัสบดี
        } else {
            $hourly_rate = $base_rate; // จันทร์, พุธ, ศุกร์
        }

        $real_court_price = $hourly_rate * $hours;

        // คำนวณค่าสินค้าและอุปกรณ์เช่า
        if (!empty($products)) {
            foreach ($products as $prod_id => $qty) {
                if ($qty > 0) {
                    $stmt_prod = $conn->prepare("SELECT product_price, product_type FROM Product WHERE product_id = :id");
                    $stmt_prod->execute([':id' => $prod_id]);
                    $prod_data = $stmt_prod->fetch(PDO::FETCH_ASSOC);
                    
                    if ($prod_data) {
                        $item_total = $prod_data['product_price'] * $qty;
                        if ($prod_data['product_type'] === 'อุปกรณ์เช่า') {
                            $real_rental_price += $item_total;
                        } else {
                            $real_product_price += $item_total;
                        }
                    }
                }
            }
        }

        $real_total_price = $real_court_price + $real_rental_price + $real_product_price;

        // ========================================================
        // 3. ระบบป้องกันการจองซ้อน (Overlap Protection)
        // ========================================================
        $sql_check = "SELECT COUNT(*) FROM BOOKING 
                      WHERE court_id = :court_id 
                      AND booking_date = :booking_date 
                      AND booking_status != 'ยกเลิก'
                      AND (
                          (booking_start_time < :end_time AND booking_end_time > :start_time)
                      )";
        
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([
            ':court_id' => $court_id,
            ':booking_date' => $booking_date,
            ':start_time' => $start_time,
            ':end_time' => $end_time
        ]);
        
        if ($stmt_check->fetchColumn() > 0) {
            throw new Exception("ขออภัย! สนามนี้มีผู้จองในช่วงเวลาดังกล่าวแล้ว กรุณาเลือกเวลาหรือสนามอื่น");
        }

        // ========================================================
        // 4. เริ่มต้น Transaction เพื่อล็อกสนามและบันทึกข้อมูล
        // ========================================================
        $conn->beginTransaction();

        // คำนวณเวลาหมดอายุ (ปัจจุบัน + 15 นาที) สำหรับ Atomic Locking
        $created_at = date('Y-m-d H:i:s');
        $lock_expire = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // 4.1 บันทึกการจองลงตาราง BOOKING (ตั้งสถานะเป็น "รอตรวจสอบ")
        $sql_booking = "INSERT INTO BOOKING (
                            member_id, court_id, booking_date, booking_start_time, booking_end_time, 
                            booking_status, booking_court_price, booking_rental_price, booking_product_price, 
                            booking_total_price, booking_created_at, booking_lock_expire
                        ) VALUES (
                            :member_id, :court_id, :booking_date, :start_time, :end_time, 
                            'รอตรวจสอบ', :court_price, :rental_price, :product_price, 
                            :total_price, :created_at, :lock_expire
                        )";
        
        $stmt_booking = $conn->prepare($sql_booking);
        $stmt_booking->execute([
            ':member_id' => $member_id,
            ':court_id' => $court_id,
            ':booking_date' => $booking_date,
            ':start_time' => $start_time,
            ':end_time' => $end_time,
            ':court_price' => $real_court_price,
            ':rental_price' => $real_rental_price,
            ':product_price' => $real_product_price,
            ':total_price' => $real_total_price,
            ':created_at' => $created_at,
            ':lock_expire' => $lock_expire
        ]);

        $booking_id = $conn->lastInsertId();

        // ========================================================
        // 5. บันทึกรายการสินค้าและตัดสต็อกชั่วคราว
        // ========================================================
        if (!empty($products)) {
            $sql_rental = "INSERT INTO RENTAL (
                                booking_id, product_id, rental_quantity, 
                                rental_start_time, rental_return_time, rental_status
                           ) VALUES (
                                :booking_id, :product_id, :qty, 
                                :start_time, :return_time, 'รอตรวจสอบ'
                           )";
            $stmt_rental = $conn->prepare($sql_rental);

            // เตรียมคำสั่งตัดสต็อก
            $sql_update_stock = "UPDATE PRODUCT SET product_stock = product_stock - :qty WHERE product_id = :id";
            $stmt_update_stock = $conn->prepare($sql_update_stock);

            foreach ($products as $prod_id => $qty) {
                if ($qty > 0) {
                    
                    // 5.1 ตรวจสอบสต็อกในฐานข้อมูลอีกครั้ง (Backend Validation)
                    $stmt_check_stock = $conn->prepare("SELECT product_name, product_stock FROM PRODUCT WHERE product_id = :id");
                    $stmt_check_stock->execute([':id' => $prod_id]);
                    $prod_db = $stmt_check_stock->fetch(PDO::FETCH_ASSOC);

                    if (!$prod_db || $prod_db['product_stock'] < $qty) {
                        throw new Exception("ขออภัย สินค้า/อุปกรณ์ '{$prod_db['product_name']}' มีจำนวนไม่พอ (คงเหลือ {$prod_db['product_stock']})");
                    }

                    // 5.2 บันทึกลงตาราง RENTAL
                    $rental_start = $booking_date . ' ' . $start_time;
                    $rental_end = $booking_date . ' ' . $end_time;
                    $stmt_rental->execute([
                        ':booking_id' => $booking_id,
                        ':product_id' => $prod_id,
                        ':qty' => $qty,
                        ':start_time' => $rental_start,
                        ':return_time' => $rental_end
                    ]);

                    // 5.3 ตัดสต็อกสินค้าทันทีเพื่อจองไว้ให้ลูกค้ารายนี้
                    $stmt_update_stock->execute([
                        ':qty' => $qty,
                        ':id' => $prod_id
                    ]);
                }
            }
        }

        // 6. ยืนยันการบันทึกข้อมูล (Commit)
        $conn->commit();

        // ส่งลูกค้าไปยังหน้าชำระเงิน
        $_SESSION['success'] = "ล็อกสนามและจองอุปกรณ์สำเร็จ! กรุณาชำระเงินภายใน 15 นาที";
        header("Location: ../payment.php?booking_id=" . $booking_id);
        exit();

    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาดใดๆ ให้ยกเลิกการบันทึกและการตัดสต็อกทั้งหมด (Rollback)
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../booking.php");
        exit();
    }

} else {
    header("Location: ../booking.php");
    exit();
}
?>