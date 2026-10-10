<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ตรวจสอบสิทธิ์การเข้าสู่ระบบ: หากยังไม่ล็อกอิน ให้เก็บข้อมูลฟอร์มและพาไปหน้า Login
    if (empty($_SESSION['member_id'])) {
        $_SESSION['pending_booking'] = $_POST;
        $_SESSION['error'] = "กรุณาเข้าสู่ระบบหรือสมัครสมาชิกก่อนดำเนินการชำระเงิน";
        header("Location: ../login.php?redirect=booking.php");
        exit();
    }

    // ตรวจสอบสถานะบัญชีสมาชิกแบบ Real-time
    require_once '../includes/auth_check.php';
    
    // ตรวจสอบความถูกต้องของ CSRF Token ก่อนดำเนินการใดๆ
    require_csrf_token();

    // 1. รับค่าจากฟอร์ม
    $member_id = intval($_SESSION['member_id']);
    $court_id = intval($_POST['court_id'] ?? 0);
    $booking_date = trim($_POST['booking_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    // รับค่าชื่อเล่น/ชื่อก๊วนผู้จอง (booking_nickname ไม่เกิน 30 ตัวอักษร)
    $booking_nickname = trim($_POST['booking_nickname'] ?? '');
    if (empty($booking_nickname)) {
        $booking_nickname = $_SESSION['member_name'] ?? 'ผู้ใช้งาน';
    }
    $booking_nickname = mb_substr($booking_nickname, 0, 30, 'UTF-8');
    
    // รับค่า Array ของสินค้า (รหัสสินค้า => จำนวน)
    $products = isset($_POST['products']) && is_array($_POST['products']) ? $_POST['products'] : [];

    try {
        // ========================================================
        // 2. เคลียร์สล็อตที่หมดเวลาล็อก 15 นาทีอัตโนมัติก่อนเริ่มตรวจสอบ
        // ========================================================
        require_once __DIR__ . '/../admin/includes/auto_cancel.php';

        // 2.1 ตรวจสอบความสมบูรณ์ของข้อมูลเบื้องต้น
        if ($court_id <= 0 || empty($booking_date) || empty($start_time) || empty($end_time)) {
            throw new Exception("กรุณาเลือกสนาม วันที่ และเวลาให้ครบถ้วน");
        }

        // ตรวจสอบรูปแบบวันที่ (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) {
            throw new Exception("รูปแบบวันที่ไม่ถูกต้อง");
        }

        $start_time_ts = strtotime($start_time);
        $end_time_ts = strtotime($end_time);

        if (!$start_time_ts || !$end_time_ts) {
            throw new Exception("รูปแบบเวลาไม่ถูกต้อง");
        }

        if ($start_time_ts >= $end_time_ts) {
            throw new Exception("เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด");
        }

        $start_time_str = date('H:i:s', $start_time_ts);
        $end_time_str = date('H:i:s', $end_time_ts);

        // 2.2 ตรวจสอบไม่ให้จองวันหรือเวลาย้อนหลัง
        $booking_start_datetime = strtotime($booking_date . ' ' . $start_time_str);
        // ให้ระยะผ่อนปรน 5 นาที (300 วินาที) สำหรับกรณีเวลาเครื่องเพี้ยนเล็กน้อย
        if ($booking_start_datetime < (time() - 300)) {
            throw new Exception("ไม่สามารถเลือกวันหรือช่วงเวลาย้อนหลังได้");
        }

        // 2.4 ตรวจสอบความยาวการจองอย่างน้อย 1 ชั่วโมง
        $hours = ($end_time_ts - $start_time_ts) / 3600;
        if ($hours < 1) {
            throw new Exception("ระยะเวลาการจองต้องอย่างน้อย 1 ชั่วโมง");
        }

        // 2.5 ตรวจสอบสถานะสมาชิกในฐานข้อมูล
        $stmt_member = $conn->prepare("SELECT member_status FROM Member WHERE member_id = :id");
        $stmt_member->execute([':id' => $member_id]);
        $member_status = $stmt_member->fetchColumn();
        if (!$member_status) {
            throw new Exception("ไม่พบข้อมูลสมาชิกในระบบ");
        }
        if ($member_status === 'ระงับสิทธิ์') {
            throw new Exception("บัญชีของคุณถูกระงับสิทธิ์การใช้งาน ไม่สามารถทำการจองสนามได้");
        }

        // ========================================================
        // 3. เริ่มต้น Database Transaction และ Pessimistic Row Lock
        // ========================================================
        $conn->beginTransaction();

        // 3.1 ล็อกแถวสนามที่เลือกด้วย SELECT ... FOR UPDATE เพื่อป้องกัน Concurrent Booking
        $stmt_court = $conn->prepare("
            SELECT court_name, court_status, court_price_per_hour, court_peak_price, court_offpeak_price, court_open_time, court_close_time 
            FROM Court 
            WHERE court_id = :court_id 
            FOR UPDATE
        ");
        $stmt_court->execute([':court_id' => $court_id]);
        $court_data = $stmt_court->fetch(PDO::FETCH_ASSOC);

        if (!$court_data) {
            throw new Exception("ไม่พบข้อมูลสนามที่ต้องการจอง");
        }

        if ($court_data['court_status'] === 'ปิดปรับปรุง') {
            throw new Exception("ขออภัย สนาม '{$court_data['court_name']}' อยู่ระหว่างปิดปรับปรุง ไม่สามารถจองได้");
        }

        // ตรวจสอบเวลาเปิดทำการของสนามนี้จากฐานข้อมูลจริง
        $court_open = !empty($court_data['court_open_time']) ? $court_data['court_open_time'] : '08:00:00';
        $court_close = !empty($court_data['court_close_time']) ? $court_data['court_close_time'] : '23:00:00';

        if ($start_time_str < $court_open || $end_time_str > $court_close) {
            $open_show = substr($court_open, 0, 5);
            $close_show = substr($court_close, 0, 5);
            throw new Exception("การจองสนาม '{$court_data['court_name']}' ต้องอยู่ในช่วงเวลาทำการ ({$open_show} - {$close_show} น.)");
        }

        // ========================================================
        // 3.2 คำนวณราคาค่าบริการตามวันในสัปดาห์ (Server-side Recalculation)
        // ========================================================
        $hourly_rate = calculate_court_hourly_rate($court_data, $booking_date);

        $real_court_price = $hourly_rate * $hours;
        $real_rental_price = 0.00;
        $real_product_price = 0.00;

        // คำนวณราคาค่าสินค้าและอุปกรณ์เช่าจากฐานข้อมูลจริง
        $valid_products = [];
        if (!empty($products)) {
            foreach ($products as $prod_id => $qty) {
                $qty = intval($qty);
                $prod_id = intval($prod_id);
                if ($qty > 0 && $prod_id > 0) {
                    $stmt_prod = $conn->prepare("SELECT product_name, product_price, product_type, product_stock FROM Product WHERE product_id = :id");
                    $stmt_prod->execute([':id' => $prod_id]);
                    $prod_data = $stmt_prod->fetch(PDO::FETCH_ASSOC);
                    
                    if ($prod_data) {
                        $item_total = floatval($prod_data['product_price']) * $qty;
                        if ($prod_data['product_type'] === 'อุปกรณ์เช่า') {
                            $real_rental_price += $item_total;
                        } else {
                            $real_product_price += $item_total;
                        }
                        $valid_products[$prod_id] = [
                            'qty' => $qty,
                            'name' => $prod_data['product_name'],
                            'type' => $prod_data['product_type'],
                            'price' => floatval($prod_data['product_price'])
                        ];
                    }
                }
            }
        }

        $real_total_price = $real_court_price + $real_rental_price + $real_product_price;

        // ========================================================
        // 3.3 ตรวจสอบการจองซ้อน (Overlap Protection) ภายใต้ Transaction & Lock
        // ========================================================
        $sql_check = "
            SELECT COUNT(*) FROM Booking 
            WHERE court_id = :court_id 
            AND booking_date = :booking_date 
            AND booking_status != 'ยกเลิก'
            AND (
                (booking_start_time < :end_time AND booking_end_time > :start_time)
            )
        ";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([
            ':court_id' => $court_id,
            ':booking_date' => $booking_date,
            ':start_time' => $start_time_str,
            ':end_time' => $end_time_str
        ]);
        
        if ($stmt_check->fetchColumn() > 0) {
            throw new Exception("ขออภัย! สนามนี้มีผู้จองในช่วงเวลาดังกล่าวแล้ว กรุณาเลือกเวลาหรือสนามอื่น");
        }

        // ========================================================
        // 4. บันทึกข้อมูลลงตาราง Booking พร้อมกำหนดเวลาล็อก 15 นาที
        // ========================================================
        $created_at = date('Y-m-d H:i:s');
        $lock_expire = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $sql_booking = "
            INSERT INTO Booking (
                member_id, booking_nickname, court_id, booking_date, booking_start_time, booking_end_time, 
                booking_status, booking_court_price, booking_rental_price, booking_product_price, 
                booking_total_price, booking_created_at, booking_lock_expire
            ) VALUES (
                :member_id, :booking_nickname, :court_id, :booking_date, :start_time, :end_time, 
                'รอตรวจสอบ', :court_price, :rental_price, :product_price, 
                :total_price, :created_at, :lock_expire
            )
        ";
        
        $stmt_booking = $conn->prepare($sql_booking);
        $stmt_booking->execute([
            ':member_id' => $member_id,
            ':booking_nickname' => $booking_nickname,
            ':court_id' => $court_id,
            ':booking_date' => $booking_date,
            ':start_time' => $start_time_str,
            ':end_time' => $end_time_str,
            ':court_price' => $real_court_price,
            ':rental_price' => $real_rental_price,
            ':product_price' => $real_product_price,
            ':total_price' => $real_total_price,
            ':created_at' => $created_at,
            ':lock_expire' => $lock_expire
        ]);

        $booking_id = $conn->lastInsertId();

        // ========================================================
        // 5. บันทึกรายการสินค้าและตัดสต็อกแบบ Atomic Concurrency
        // ========================================================
        if (!empty($valid_products)) {
            $sql_rental = "
                INSERT INTO Rental (
                    booking_id, product_id, rental_quantity, 
                    rental_start_time, rental_return_time, rental_status
                ) VALUES (
                    :booking_id, :product_id, :qty, 
                    :start_time, :return_time, 'รอตรวจสอบ'
                )
            ";
            $stmt_rental = $conn->prepare($sql_rental);

            // คำสั่งตัดสต็อกแบบมีเงื่อนไข Atomic ป้องกันสต็อกติดลบ
            $sql_update_stock = "
                UPDATE Product 
                SET product_stock = product_stock - :qty 
                WHERE product_id = :id AND product_stock >= :qty
            ";
            $stmt_update_stock = $conn->prepare($sql_update_stock);

            foreach ($valid_products as $prod_id => $item) {
                $qty = $item['qty'];
                
                // ตัดสต็อกแบบ Atomic
                $stmt_update_stock->execute([
                    ':qty' => $qty,
                    ':id' => $prod_id
                ]);

                // หากแถวที่ได้รับผลกระทบเป็น 0 แสดงว่าสต็อกไม่พอ ณ เสี้ยววินาทีนั้น
                if ($stmt_update_stock->rowCount() === 0) {
                    $stmt_cur = $conn->prepare("SELECT product_stock FROM Product WHERE product_id = :id");
                    $stmt_cur->execute([':id' => $prod_id]);
                    $cur_stock = $stmt_cur->fetchColumn() ?: 0;
                    throw new Exception("ขออภัย สินค้า/อุปกรณ์ '{$item['name']}' มีจำนวนไม่พอ (คงเหลือ {$cur_stock} ชิ้น)");
                }

                // บันทึกลงตาราง Rental
                $rental_start = $booking_date . ' ' . $start_time_str;
                $rental_end = $booking_date . ' ' . $end_time_str;
                $stmt_rental->execute([
                    ':booking_id' => $booking_id,
                    ':product_id' => $prod_id,
                    ':qty' => $qty,
                    ':start_time' => $rental_start,
                    ':return_time' => $rental_end
                ]);
            }
        }

        // ========================================================
        // 6. ยืนยัน Transaction (Commit)
        // ========================================================
        $conn->commit();

        // เคลียร์ข้อมูลการจองชั่วคราวออกจาก Session (ถ้ามี)
        unset($_SESSION['pending_booking']);

        // ส่งลูกค้าไปยังหน้าชำระเงิน
        $_SESSION['success'] = "ล็อกสนามและจองอุปกรณ์สำเร็จ! กรุณาชำระเงินและแนบสลิปภายใน 15 นาที";
        header("Location: ../payment.php?booking_id=" . $booking_id);
        exit();

    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาดใดๆ ให้ Rollback ยกเลิกรายการและคืนสต็อกทั้งหมดทันที
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