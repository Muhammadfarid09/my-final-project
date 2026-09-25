<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $cart_data_json = $_POST['cart_data'] ?? '[]';
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $admin_id = intval($_SESSION['admin_id']); 
    $payment_method = $_POST['payment_method'] ?? 'cash';

    $cart_items = json_decode($cart_data_json, true);

    if (empty($cart_items)) {
        $_SESSION['error'] = "ไม่พบรายการสินค้าหรือสนามในตะกร้า";
        header("Location: ../pos.php");
        exit();
    }

    try {
        $conn->beginTransaction();

        $revenue_court = 0.00;
        $revenue_rental = 0.00;
        $revenue_product = 0.00;
        $last_booking_id = 0;
        $last_pos_id = 0;

        // 1. ประมวลผลรายการเปิดสนาม Walk-in ก่อน (ถ้ามี)
        foreach ($cart_items as $item) {
            if (!empty($item['is_court'])) {
                $court_id = intval($item['court_id']);
                $b_date = $item['booking_date'] ?? date('Y-m-d');
                $b_start = $item['start_time'] ?? '08:00:00';
                $b_end = $item['end_time'] ?? '09:00:00';
                $court_price = floatval($item['price']);

                // Fallback คำนวณราคาตามประเภทวันหากไม่ได้ส่งมา
                if ($court_price <= 0) {
                    $c_stmt = $conn->prepare("SELECT court_price_per_hour, court_peak_price, court_offpeak_price FROM Court WHERE court_id = :cid");
                    $c_stmt->execute([':cid' => $court_id]);
                    $c_info = $c_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($c_info) {
                        $dow = intval(date('w', strtotime($b_date)));
                        $h_rate = ($dow == 0 || $dow == 6) 
                            ? (floatval($c_info['court_peak_price']) > 0 ? floatval($c_info['court_peak_price']) : 200.00) 
                            : (($dow == 2 || $dow == 4) 
                                ? (floatval($c_info['court_offpeak_price']) > 0 ? floatval($c_info['court_offpeak_price']) : 150.00) 
                                : (floatval($c_info['court_price_per_hour']) > 0 ? floatval($c_info['court_price_per_hour']) : 180.00));
                        $h_diff = max(1, (strtotime($b_end) - strtotime($b_start)) / 3600);
                        $court_price = $h_rate * $h_diff;
                    }
                }

                // ค้นหา member_id จากเบอร์โทร (ถ้ากรอก)
                $member_id = null;
                if (!empty($item['member_phone'])) {
                    $stmt_mem = $conn->prepare("SELECT member_id FROM Member WHERE member_phone = :phone");
                    $stmt_mem->execute([':phone' => trim($item['member_phone'])]);
                    $found_id = $stmt_mem->fetchColumn();
                    if ($found_id) {
                        $member_id = intval($found_id);
                    }
                }

                // บันทึกลงตาราง Booking (สถานะ 'จองแล้ว' ทันที)
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
                    ':bstart' => $b_start,
                    ':bend' => $b_end,
                    ':cprice' => $court_price,
                    ':tprice' => $court_price
                ]);
                $last_booking_id = $conn->lastInsertId();
                $revenue_court += $court_price;

                // ถ้าเป็นสมาชิก ให้คะแนนสะสมด้วย (เช่น 1 แต้ม ทุก 50 บาท)
                if ($member_id) {
                    $earned_points = floor($court_price / 50);
                    if ($earned_points > 0) {
                        // ตรวจสอบและอัปเดตหรือเพิ่มคะแนนสะสม
                        $check_pt = $conn->prepare("SELECT point_id FROM Point WHERE member_id = :mid");
                        $check_pt->execute([':mid' => $member_id]);
                        if ($check_pt->fetch()) {
                            $conn->prepare("UPDATE Point SET point_balance = point_balance + :pt, point_total_earned = point_total_earned + :pt WHERE member_id = :mid")
                                 ->execute([':pt' => $earned_points, ':mid' => $member_id]);
                        } else {
                            $conn->prepare("INSERT INTO Point (member_id, point_balance, point_total_earned, member_level) VALUES (:mid, :pt, :pt, 'Bronze')")
                                 ->execute([':mid' => $member_id, ':pt' => $earned_points]);
                        }
                        
                        $conn->prepare("INSERT INTO Point_Transaction (member_id, booking_id, transaction_type, transaction_point, transaction_note, transaction_date) VALUES (:mid, :bid, 'ได้รับ', :pt, 'ได้รับคะแนนจากการเปิดสนาม Walk-in', NOW())")
                             ->execute([':mid' => $member_id, ':bid' => $last_booking_id, ':pt' => $earned_points]);

                        // ตรวจสอบและอัปเกรดระดับสมาชิก (Tier Progression): Bronze -> Silver (500 pts) -> Gold (1000 pts)
                        $stmt_pts = $conn->prepare("SELECT point_total_earned FROM Point WHERE member_id = :mid");
                        $stmt_pts->execute([':mid' => $member_id]);
                        $tot = $stmt_pts->fetchColumn() ?: 0;
                        $new_lvl = ($tot >= 1000) ? 'Gold' : (($tot >= 500) ? 'Silver' : 'Bronze');
                        $conn->prepare("UPDATE Point SET member_level = :lvl WHERE member_id = :mid")
                             ->execute([':lvl' => $new_lvl, ':mid' => $member_id]);
                    }
                }
            }
        }

        // 2. ประมวลผลรายการสินค้าและอุปกรณ์เช่า
        foreach ($cart_items as $item) {
            if (!empty($item['is_court'])) {
                continue; // ข้ามเพราะจัดการไปแล้ว
            }

            $product_id = intval($item['id']);
            $qty = intval($item['qty']);
            $item_total_price = floatval($item['price']) * $qty;

            // ตรวจสอบสต็อก
            $stmt_chk = $conn->prepare("SELECT product_type, product_stock, product_name FROM Product WHERE product_id = :pid");
            $stmt_chk->execute([':pid' => $product_id]);
            $prod = $stmt_chk->fetch(PDO::FETCH_ASSOC);

            if (!$prod || $prod['product_stock'] < $qty) {
                throw new Exception("สินค้า/อุปกรณ์ '" . ($prod['product_name'] ?? '') . "' สต็อกคงเหลือไม่พอ");
            }

            // ตัดสต็อกสินค้า
            $sql_update = "UPDATE Product SET product_stock = product_stock - :qty WHERE product_id = :id";
            $conn->prepare($sql_update)->execute([':qty' => $qty, ':id' => $product_id]);

            // บันทึกประวัติการขายลง Pos_sale
            $sql_pos = "INSERT INTO Pos_sale (admin_id, product_id, pos_quantity, pos_total_price, pos_date) 
                        VALUES (:admin, :product, :qty, :price, NOW())";
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

        // 3. บันทึกรายรับรวมลงตาราง Revenue (แยกหมวดหมู่ชัดเจน)
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
            ':total' => $total_amount
        ]);

        $conn->commit();

        $_SESSION['success'] = "ชำระเงินและบันทึกรายการสำเร็จ!";
        if ($last_pos_id > 0) {
            $_SESSION['print_receipt_id'] = $last_pos_id;
        }
        if ($last_booking_id > 0) {
            $_SESSION['print_booking_id'] = $last_booking_id;
        }

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการทำรายการ: " . $e->getMessage();
    }

    header("Location: ../pos.php");
    exit();

} else {
    header("Location: ../pos.php");
    exit();
}
?>