<?php
/**
 * T.S. Pattani Badminton System - POS Shift Closeout Action Handler
 * บันทึกการปิดกะการเงิน สรุปยอดเงินสด และออกรหัส Z-Report
 */
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. ตรวจสอบ CSRF Token
    require_csrf_token();

    $admin_id = intval($_SESSION['admin_id'] ?? 0);
    $admin_name = $_SESSION['admin_name'] ?? 'ผู้ดูแลระบบ';

    $opening_cash_float = max(0, floatval($_POST['opening_cash_float'] ?? 0));
    $actual_cash = max(0, floatval($_POST['actual_cash'] ?? 0));
    $notes = trim($_POST['notes'] ?? '');

    try {
        $conn->beginTransaction();

        // 2. คำนวณช่วงเวลากะ (Opened At -> Closed At)
        $stmt_last = $conn->query("SELECT MAX(closed_at) FROM Shift_Closeout WHERE status = 'ปิดกะแล้ว'");
        $last_closed = $stmt_last->fetchColumn();
        $opened_at = $last_closed ? $last_closed : date('Y-m-d 00:00:00');
        $closed_at = date('Y-m-d H:i:s');

        // 3. ดึงยอดขายหน้าร้าน (POS Sales) ในช่วงเวลากะ
        $stmt_pos = $conn->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN (pos_payment_method = 'cash' OR pos_payment_method IS NULL) THEN pos_total_price ELSE 0 END), 0) AS pos_cash,
                COALESCE(SUM(CASE WHEN pos_payment_method = 'qr' THEN pos_total_price ELSE 0 END), 0) AS pos_qr,
                COALESCE(SUM(pos_total_price), 0) AS pos_total,
                COUNT(pos_id) AS pos_items_count
            FROM Pos_sale
            WHERE pos_date >= :opened_at AND pos_date <= :closed_at
        ");
        $stmt_pos->execute([':opened_at' => $opened_at, ':closed_at' => $closed_at]);
        $pos_data = $stmt_pos->fetch(PDO::FETCH_ASSOC);

        $pos_cash_sales = floatval($pos_data['pos_cash'] ?? 0);
        $pos_qr_sales = floatval($pos_data['pos_qr'] ?? 0);
        $pos_items_count = intval($pos_data['pos_items_count'] ?? 0);

        // 4. ดึงยอดเงินโอนออนไลน์ (Online Bookings Approved) ในช่วงเวลากะ
        $stmt_online = $conn->prepare("
            SELECT 
                COALESCE(SUM(payment_amount), 0) AS online_total,
                COUNT(payment_id) AS online_count
            FROM Payment
            WHERE payment_status = 'ยืนยันแล้ว'
              AND payment_verified_at >= :opened_at AND payment_verified_at <= :closed_at
        ");
        $stmt_online->execute([':opened_at' => $opened_at, ':closed_at' => $closed_at]);
        $online_data = $stmt_online->fetch(PDO::FETCH_ASSOC);

        $online_transfer_sales = floatval($online_data['online_total'] ?? 0);
        $online_count = intval($online_data['online_count'] ?? 0);

        // 5. ดึงยอดแยกตามหมวดหมู่ (Revenue Breakdown)
        $stmt_dept = $conn->prepare("
            SELECT 
                COALESCE(SUM(revenue_court_amount), 0) AS court_total,
                COALESCE(SUM(revenue_rental_amount), 0) AS rental_total,
                COALESCE(SUM(revenue_product_amount), 0) AS product_total
            FROM Revenue
            WHERE revenue_date = CURDATE()
        ");
        $stmt_dept->execute();
        $dept_data = $stmt_dept->fetch(PDO::FETCH_ASSOC);

        $court_sales = floatval($dept_data['court_total'] ?? 0);
        $rental_sales = floatval($dept_data['rental_total'] ?? 0);
        $product_sales = floatval($dept_data['product_total'] ?? 0);

        // 6. คำนวณยอดสรุปและการกระทบยอดเงินสดในลิ้นชัก (Cash Reconciliation)
        $total_revenue = $pos_cash_sales + $pos_qr_sales + $online_transfer_sales;
        // กรณีถ้าไม่มีการขาย pos เลยแต่มี revenue ให้ตรวจสอบค่าความสอดคล้อง
        if ($total_revenue === 0.00 && ($court_sales + $rental_sales + $product_sales) > 0) {
            $total_revenue = $court_sales + $rental_sales + $product_sales;
        }

        $expected_cash = $opening_cash_float + $pos_cash_sales;
        $cash_difference = $actual_cash - $expected_cash;
        $total_transactions = $pos_items_count + $online_count;

        // 7. สร้าง Shift Code ไม่ซ้ำ (Format: SHIFT-YYYYMMDD-XXX)
        $today_prefix = 'SHIFT-' . date('Ymd') . '-';
        $stmt_count_today = $conn->prepare("SELECT COUNT(*) FROM Shift_Closeout WHERE shift_code LIKE :prefix");
        $stmt_count_today->execute([':prefix' => $today_prefix . '%']);
        $shift_num = intval($stmt_count_today->fetchColumn()) + 1;
        $shift_code = $today_prefix . str_pad($shift_num, 3, '0', STR_PAD_LEFT);

        // 8. บันทึกลงตาราง Shift_Closeout
        $sql_insert = "
            INSERT INTO `Shift_Closeout` (
                `shift_code`, `admin_id`, `shift_date`, `opened_at`, `closed_at`,
                `opening_cash_float`, `pos_cash_sales`, `pos_qr_sales`, `online_transfer_sales`,
                `court_sales`, `rental_sales`, `product_sales`, `total_revenue`,
                `expected_cash`, `actual_cash`, `cash_difference`, `total_transactions`,
                `notes`, `status`, `created_at`
            ) VALUES (
                :shift_code, :admin_id, CURDATE(), :opened_at, :closed_at,
                :opening_cash_float, :pos_cash_sales, :pos_qr_sales, :online_transfer_sales,
                :court_sales, :rental_sales, :product_sales, :total_revenue,
                :expected_cash, :actual_cash, :cash_difference, :total_transactions,
                :notes, 'ปิดกะแล้ว', NOW()
            )
        ";
        $stmt_ins = $conn->prepare($sql_insert);
        $stmt_ins->execute([
            ':shift_code'            => $shift_code,
            ':admin_id'              => $admin_id,
            ':opened_at'             => $opened_at,
            ':closed_at'             => $closed_at,
            ':opening_cash_float'    => $opening_cash_float,
            ':pos_cash_sales'        => $pos_cash_sales,
            ':pos_qr_sales'          => $pos_qr_sales,
            ':online_transfer_sales' => $online_transfer_sales,
            ':court_sales'           => $court_sales,
            ':rental_sales'          => $rental_sales,
            ':product_sales'         => $product_sales,
            ':total_revenue'         => $total_revenue,
            ':expected_cash'         => $expected_cash,
            ':actual_cash'           => $actual_cash,
            ':cash_difference'       => $cash_difference,
            ':total_transactions'   => $total_transactions,
            ':notes'                 => $notes
        ]);

        $new_shift_id = $conn->lastInsertId();

        // 9. บันทึกลง Audit_Log เพื่อการตรวจสอบย้อนหลัง
        log_activity($conn, 'POS_SHIFT_CLOSE', 'Shift', $new_shift_id, [
            'shift_code'      => $shift_code,
            'total_revenue'   => $total_revenue,
            'expected_cash'   => $expected_cash,
            'actual_cash'     => $actual_cash,
            'cash_difference' => $cash_difference,
            'admin_name'      => $admin_name
        ]);

        $conn->commit();

        // 10. ส่งการแจ้งเตือน Real-time เข้า Discord Webhook
        $diff_text = '✅ ตรงเป๊ะ (0.00 ฿)';
        $embed_color = 1097601; // เขียว
        if ($cash_difference < 0) {
            $diff_text = '🔴 เงินขาด ' . number_format(abs($cash_difference), 2) . ' ฿';
            $embed_color = 15746887; // แดง
        } elseif ($cash_difference > 0) {
            $diff_text = '🟡 เงินเกิน +' . number_format($cash_difference, 2) . ' ฿';
            $embed_color = 16759069; // ทอง
        }

        send_discord_alert(
            '📋 บันทึกปิดกะการเงินสิ้นวัน (POS Shift Closed)',
            "พนักงาน **{$admin_name}** ได้ทำการปิดกะรหัส **`{$shift_code}`** และตรวจนับเงินสดเรียบร้อยแล้ว",
            [
                ['name' => 'รหัสกะ (Shift Code)', 'value' => $shift_code, 'inline' => true],
                ['name' => 'ผู้ปิดกะ', 'value' => $admin_name, 'inline' => true],
                ['name' => 'รายรับรวมทั้งสิ้น', 'value' => number_format($total_revenue, 2) . ' ฿', 'inline' => true],
                ['name' => 'เงินสดขายหน้าร้าน', 'value' => number_format($pos_cash_sales, 2) . ' ฿', 'inline' => true],
                ['name' => 'ยอดเงินโอน (QR/ออนไลน์)', 'value' => number_format($pos_qr_sales + $online_transfer_sales, 2) . ' ฿', 'inline' => true],
                ['name' => 'ผลต่างเงินในลิ้นชัก', 'value' => $diff_text, 'inline' => true],
                ['name' => 'เงินสดที่นับได้จริง', 'value' => number_format($actual_cash, 2) . ' ฿', 'inline' => true],
                ['name' => 'เงินสดที่ควรมี', 'value' => number_format($expected_cash, 2) . ' ฿', 'inline' => true]
            ],
            $embed_color
        );

        $_SESSION['success'] = "บันทึกการปิดกะสำเร็จ! รหัสกะ {$shift_code}";
        header("Location: ../pos_zreport.php?shift_id=" . $new_shift_id . "&auto_print=1");
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกปิดกะ: " . $e->getMessage();
        header("Location: ../pos_closeout.php");
        exit();
    }

} else {
    header("Location: ../pos_closeout.php");
    exit();
}
