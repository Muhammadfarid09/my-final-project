<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. ตรวจสอบ CSRF Token
    require_csrf_token();

    $cart_data_json = $_POST['cart_data'] ?? '[]';
    $admin_id = intval($_SESSION['admin_id']); 
    $payment_method = $_POST['payment_method'] ?? 'cash';

    $cart_items = json_decode($cart_data_json, true);

    if (empty($cart_items) || !is_array($cart_items)) {
        $_SESSION['error'] = "ไม่พบรายการสินค้าหรือสนามในตะกร้า";
        header("Location: ../pos.php");
        exit();
    }

    try {
        // เคลียร์ล็อกที่หมดอายุในระบบก่อน
        require_once __DIR__ . '/../includes/auto_cancel.php';

        $conn->beginTransaction();

        $revenue_court = 0.00;
        $revenue_rental = 0.00;
        $revenue_product = 0.00;
        $last_booking_id = 0;
        $last_pos_id = 0;
        $active_member_id = null;

        // ========================================================
        // 1. ประมวลผลรายการเปิดสนาม Walk-in (ถ้ามี)
        // ========================================================
        foreach ($cart_items as $item) {
            if (!empty($item['is_court'])) {
                $court_id = intval($item['court_id']);
                $b_date = trim($item['booking_date'] ?? date('Y-m-d'));
                $b_start = trim($item['start_time'] ?? '08:00:00');
                $b_end = trim($item['end_time'] ?? '09:00:00');

                if ($court_id <= 0) {
                    throw new Exception("รหัสสนามที่เปิดใช้งานไม่ถูกต้อง");
                }

                // 1.1 ล็อกแถวสนามด้วย SELECT ... FOR UPDATE เพื่อป้องกันการจองชนกัน
                $c_stmt = $conn->prepare("
                    SELECT court_name, court_status, court_price_per_hour, court_peak_price, court_offpeak_price 
                    FROM Court 
                    WHERE court_id = :cid 
                    FOR UPDATE
                ");
                $c_stmt->execute([':cid' => $court_id]);
                $c_info = $c_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$c_info) {
                    throw new Exception("ไม่พบข้อมูลสนามในระบบ");
                }

                if ($c_info['court_status'] === 'ปิดปรับปรุง') {
                    throw new Exception("ขออภัย สนาม '{$c_info['court_name']}' อยู่ในระหว่างปิดปรับปรุง ไม่สามารถเปิดใช้งานได้");
                }

                $b_start_str = date('H:i:s', strtotime($b_start));
                $b_end_str = date('H:i:s', strtotime($b_end));

                // 1.2 ตรวจสอบการจองซ้ำซ้อน (Overlap Protection) กับการจองออนไลน์และหน้าร้านทั้งหมด
                $sql_ov = "
                    SELECT COUNT(*) FROM Booking 
                    WHERE court_id = :cid 
                    AND booking_date = :bdate 
                    AND booking_status != 'ยกเลิก'
                    AND (booking_start_time < :bend AND booking_end_time > :bstart)
                ";
                $stmt_ov = $conn->prepare($sql_ov);
                $stmt_ov->execute([
                    ':cid' => $court_id,
                    ':bdate' => $b_date,
                    ':bstart' => $b_start_str,
                    ':bend' => $b_end_str
                ]);

                if ($stmt_ov->fetchColumn() > 0) {
                    throw new Exception("สนาม '{$c_info['court_name']}' มีผู้จองหรือใช้งานอยู่ในช่วงเวลาดังกล่าวแล้ว ไม่สามารถเปิดสนามซ้ำได้");
                }

                // 1.3 บังคับคำนวณราคาสนามตามเรทวันในสัปดาห์ฝั่ง Server (Server-side Recalculation)
                $dow = intval(date('w', strtotime($b_date)));
                $base_rate = floatval($c_info['court_price_per_hour']) > 0 ? floatval($c_info['court_price_per_hour']) : 180.00;
                $peak_rate = floatval($c_info['court_peak_price']) > 0 ? floatval($c_info['court_peak_price']) : 200.00;
                $offpeak_rate = floatval($c_info['court_offpeak_price']) > 0 ? floatval($c_info['court_offpeak_price']) : 150.00;
                $h_rate = ($dow == 0 || $dow == 6) ? $peak_rate : (($dow == 2 || $dow == 4) ? $offpeak_rate : $base_rate);
                
                $h_diff = max(1, (strtotime($b_end_str) - strtotime($b_start_str)) / 3600);
                $court_price = $h_rate * $h_diff;

                // ค้นหา member_id จากเบอร์โทรศัพท์ (ถ้ามีการระบุ)
                $member_id = null;
                if (!empty($item['member_phone'])) {
                    $stmt_mem = $conn->prepare("SELECT member_id FROM Member WHERE member_phone = :phone");
                    $stmt_mem->execute([':phone' => trim($item['member_phone'])]);
                    $found_id = $stmt_mem->fetchColumn();
                    if ($found_id) {
                        $member_id = intval($found_id);
                        $active_member_id = $member_id;
                    }
                }

                // บันทึกลงตาราง Booking (สถานะ 'จองแล้ว' ทันทีสำหรับการเปิดหน้าร้าน)
                $sql_booking = "
                    INSERT INTO Booking 
                    (member_id, court_id, booking_date, booking_start_time, booking_end_time, 
                     booking_status, booking_court_price, booking_rental_price, booking_product_price, 
                     booking_total_price, booking_created_at) 
                    VALUES 
                    (:mid, :cid, :bdate, :bstart, :bend, 'จองแล้ว', :cprice, 0.00, 0.00, :tprice, NOW())
                ";
                $stmt_booking = $conn->prepare($sql_booking);
                $stmt_booking->execute([
                    ':mid' => $member_id,
                    ':cid' => $court_id,
                    ':bdate' => $b_date,
                    ':bstart' => $b_start_str,
                    ':bend' => $b_end_str,
                    ':cprice' => $court_price,
                    ':tprice' => $court_price
                ]);
                $last_booking_id = $conn->lastInsertId();
                $revenue_court += $court_price;
            }
        }

        // ========================================================
        // 2. ประมวลผลรายการสินค้าและอุปกรณ์เช่า
        // ========================================================
        foreach ($cart_items as $item) {
            if (!empty($item['is_court'])) {
                continue; // ข้ามเพราะจัดการไปแล้ว
            }

            $product_id = intval($item['id'] ?? 0);
            $qty = intval($item['qty'] ?? 0);

            if ($product_id <= 0 || $qty <= 0) {
                continue;
            }

            // 2.1 ดึงราคาและข้อมูลสินค้าจริงจากฐานข้อมูล (Server Trust Boundary)
            $stmt_chk = $conn->prepare("SELECT product_type, product_stock, product_name, product_price FROM Product WHERE product_id = :pid");
            $stmt_chk->execute([':pid' => $product_id]);
            $prod = $stmt_chk->fetch(PDO::FETCH_ASSOC);

            if (!$prod) {
                throw new Exception("ไม่พบรหัสสินค้า/อุปกรณ์ #{$product_id} ในระบบ");
            }

            $real_item_price = floatval($prod['product_price']);
            $item_total_price = $real_item_price * $qty;

            // 2.2 ตัดสต็อกสินค้าแบบ Atomic Update ป้องกันสต็อกติดลบ
            $sql_update = "
                UPDATE Product 
                SET product_stock = product_stock - :qty 
                WHERE product_id = :id AND product_stock >= :qty
            ";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->execute([':qty' => $qty, ':id' => $product_id]);

            if ($stmt_update->rowCount() === 0) {
                $cur_stock = $conn->query("SELECT product_stock FROM Product WHERE product_id = $product_id")->fetchColumn() ?: 0;
                throw new Exception("สินค้า/อุปกรณ์ '{$prod['product_name']}' สต็อกคงเหลือไม่พอ (เหลือ {$cur_stock} ชิ้น)");
            }

            // บันทึกประวัติการขายลง Pos_sale
            $sql_pos = "
                INSERT INTO Pos_sale (admin_id, product_id, pos_quantity, pos_total_price, pos_date) 
                VALUES (:admin, :product, :qty, :price, NOW())
            ";
            $stmt_pos = $conn->prepare($sql_pos);
            $stmt_pos->execute([
                ':admin' => $admin_id,
                ':product' => $product_id,
                ':qty' => $qty,
                ':price' => $item_total_price
            ]);
            $last_pos_id = $conn->lastInsertId();

            if ($prod['product_type'] === 'อุปกรณ์เช่า') {
                $revenue_rental += $item_total_price;

                // ถ้ามีการเช่าอุปกรณ์ ให้ลงตาราง Rental ด้วยเพื่อตรวจรับคืนได้
                $b_id_for_rental = ($last_booking_id > 0) ? $last_booking_id : null;
                $sql_rental = "
                    INSERT INTO Rental 
                    (booking_id, product_id, rental_quantity, rental_start_time, rental_return_time, rental_status) 
                    VALUES 
                    (:bid, :pid, :qty, NOW(), DATE_ADD(NOW(), INTERVAL 2 HOUR), 'กำลังเช่า')
                ";
                $conn->prepare($sql_rental)->execute([
                    ':bid' => $b_id_for_rental,
                    ':pid' => $product_id,
                    ':qty' => $qty
                ]);
            } else {
                $revenue_product += $item_total_price;
            }
        }

        // ========================================================
        // 3. คำนวณยอดรวมรายรับจากฝั่ง Server เท่านั้น (Server Source of Truth)
        // ========================================================
        $server_total_amount = $revenue_court + $revenue_rental + $revenue_product;

        // 3.1 บันทึกรายรับลงตาราง Revenue
        $sql_revenue = "
            INSERT INTO Revenue 
            (payment_id, revenue_court_amount, revenue_rental_amount, revenue_product_amount, revenue_total_amount, revenue_date, revenue_type) 
            VALUES 
            (NULL, :court, :rental, :product, :total, CURDATE(), 'หน้าร้าน')
        ";
        $stmt_revenue = $conn->prepare($sql_revenue);
        $stmt_revenue->execute([
            ':court' => $revenue_court,
            ':rental' => $revenue_rental,
            ':product' => $revenue_product,
            ':total' => $server_total_amount
        ]);

        // ========================================================
        // 4. การสะสมคะแนน (มาตรฐานเดียวกัน: 1 คะแนน ต่อ 100 บาท จากยอดบิลรวม)
        // ========================================================
        if ($active_member_id && $server_total_amount >= 100) {
            $earned_points = floor($server_total_amount / 100);
            if ($earned_points > 0) {
                $check_pt = $conn->prepare("SELECT point_id FROM Point WHERE member_id = :mid");
                $check_pt->execute([':mid' => $active_member_id]);
                if ($check_pt->fetch()) {
                    $conn->prepare("UPDATE Point SET point_balance = point_balance + :pt, point_total_earned = point_total_earned + :pt WHERE member_id = :mid")
                         ->execute([':pt' => $earned_points, ':mid' => $active_member_id]);
                } else {
                    $conn->prepare("INSERT INTO Point (member_id, point_balance, point_total_earned, member_level) VALUES (:mid, :pt, :pt, 'Bronze')")
                         ->execute([':mid' => $active_member_id, ':pt' => $earned_points]);
                }
                
                $conn->prepare("
                    INSERT INTO Point_Transaction (member_id, booking_id, transaction_type, transaction_point, transaction_note, transaction_date) 
                    VALUES (:mid, :bid, 'ได้รับ', :pt, 'ได้รับคะแนนจากการซื้อสินค้า/บริการหน้าร้าน (POS)', NOW())
                ")->execute([
                    ':mid' => $active_member_id, 
                    ':bid' => ($last_booking_id > 0 ? $last_booking_id : null), 
                    ':pt' => $earned_points
                ]);

                // ตรวจสอบและอัปเกรดระดับสมาชิก (Tier Progression): Bronze -> Silver (500 pts) -> Gold (1000 pts)
                $stmt_pts = $conn->prepare("SELECT point_total_earned FROM Point WHERE member_id = :mid");
                $stmt_pts->execute([':mid' => $active_member_id]);
                $tot = $stmt_pts->fetchColumn() ?: 0;
                $new_lvl = ($tot >= 1000) ? 'Gold' : (($tot >= 500) ? 'Silver' : 'Bronze');
                $conn->prepare("UPDATE Point SET member_level = :lvl WHERE member_id = :mid")
                     ->execute([':lvl' => $new_lvl, ':mid' => $active_member_id]);
            }
        }

        $conn->commit();

        $_SESSION['success'] = "ชำระเงินและบันทึกรายการขายสำเร็จ! ยอดรวมทั้งสิ้น " . number_format($server_total_amount, 2) . " บาท";
        if ($last_pos_id > 0) {
            $_SESSION['print_receipt_id'] = $last_pos_id;
        }
        if ($last_booking_id > 0) {
            $_SESSION['print_booking_id'] = $last_booking_id;
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการทำรายการ: " . $e->getMessage();
    }

    header("Location: ../pos.php");
    exit();

} else {
    header("Location: ../pos.php");
    exit();
}
?>