<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Set JSON Header
header('Content-Type: application/json; charset=utf-8');

try {
    // 1. นับจำนวนสลิปรอตรวจสอบ
    $stmt_pay = $conn->query("
        SELECT COUNT(p.payment_id) 
        FROM Payment p
        JOIN Booking b ON p.booking_id = b.booking_id
        WHERE p.payment_status = 'รอตรวจสอบ' AND b.booking_status != 'ยกเลิก'
    ");
    $pending_payments = intval($stmt_pay->fetchColumn() ?: 0);

    // 2. นับจำนวนคำขอยกเลิกรอดำเนินการ
    $stmt_cancel = $conn->query("
        SELECT COUNT(cancel_id) 
        FROM Cancellation 
        WHERE refund_status = 'รอดำเนินการ'
    ");
    $pending_cancellations = intval($stmt_cancel->fetchColumn() ?: 0);

    // 3. นับจำนวนสินค้าใกล้หมดสต็อก
    $stmt_stock = $conn->query("
        SELECT COUNT(product_id) 
        FROM Product 
        WHERE product_stock <= product_min_stock AND product_type = 'สินค้าบริโภค'
    ");
    $low_stock = intval($stmt_stock->fetchColumn() ?: 0);

    // 4. ดึง 5 รายการสลิปล่าสุดที่รอตรวจสอบ
    $stmt_recent_slips = $conn->query("
        SELECT p.payment_id, p.booking_id, p.payment_amount, p.payment_transfer_time,
               b.booking_date, b.booking_start_time, b.booking_end_time,
               m.member_name, c.court_name
        FROM Payment p
        JOIN Booking b ON p.booking_id = b.booking_id
        JOIN Member m ON b.member_id = m.member_id
        JOIN Court c ON b.court_id = c.court_id
        WHERE p.payment_status = 'รอตรวจสอบ' AND b.booking_status != 'ยกเลิก'
        ORDER BY p.payment_transfer_time DESC, p.payment_id DESC
        LIMIT 5
    ");
    $recent_slips = $stmt_recent_slips->fetchAll(PDO::FETCH_ASSOC);

    // 5. ดึง 3 รายการขอยกเลิกล่าสุด
    $stmt_recent_cancels = $conn->query("
        SELECT c.cancel_id, c.booking_id, c.cancel_reason, c.cancel_date,
               m.member_name
        FROM Cancellation c
        JOIN Booking b ON c.booking_id = b.booking_id
        JOIN Member m ON b.member_id = m.member_id
        WHERE c.refund_status = 'รอดำเนินการ'
        ORDER BY c.cancel_date DESC
        LIMIT 3
    ");
    $recent_cancels = $stmt_recent_cancels->fetchAll(PDO::FETCH_ASSOC);

    $items = [];

    foreach ($recent_slips as $s) {
        $items[] = [
            'type'      => 'payment',
            'id'        => intval($s['payment_id']),
            'title'     => 'สลิปใหม่: ' . htmlspecialchars($s['member_name']),
            'subtitle'  => 'คอร์ท: ' . htmlspecialchars($s['court_name']) . ' (' . number_format($s['payment_amount'], 2) . ' ฿)',
            'time'      => date('H:i น.', strtotime($s['payment_transfer_time'])),
            'link'      => 'payments.php'
        ];
    }

    foreach ($recent_cancels as $c) {
        $items[] = [
            'type'      => 'cancellation',
            'id'        => intval($c['cancel_id']),
            'title'     => 'คำขอยกเลิก: ' . htmlspecialchars($c['member_name']),
            'subtitle'  => 'เหตุผล: ' . htmlspecialchars(mb_strimwidth($c['cancel_reason'], 0, 30, '...')),
            'time'      => date('H:i น.', strtotime($c['cancel_date'])),
            'link'      => 'cancellations.php'
        ];
    }

    $total_pending = $pending_payments + $pending_cancellations;

    echo json_encode([
        'status'                => 'success',
        'total_pending'         => $total_pending,
        'pending_payments'      => $pending_payments,
        'pending_cancellations' => $pending_cancellations,
        'low_stock'             => $low_stock,
        'items'                 => $items
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

