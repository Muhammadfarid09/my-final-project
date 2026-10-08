<?php
require_once 'includes/auth_check.php';

// รับค่ารหัสบิล POS หรือรหัสการจองสนาม Walk-in
$pos_id = isset($_GET['pos_id']) ? intval($_GET['pos_id']) : 0;
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($pos_id === 0 && $booking_id === 0) {
    die("ไม่พบข้อมูลใบเสร็จที่ต้องการพิมพ์");
}

try {
    $receipt_date = date('Y-m-d H:i:s');
    $admin_name = $_SESSION['admin_name'] ?? 'Admin';
    $customer_name = 'ลูกค้าทั่วไป (Walk-in)';
    $total_amount = 0;
    $items = [];
    $court_item = null;

    // 1. ดึงข้อมูลการจองสนาม Walk-in (ถ้ามี)
    if ($booking_id > 0) {
        $stmt_book = $conn->prepare("
            SELECT b.*, c.court_name, m.member_name, m.member_phone 
            FROM Booking b
            JOIN Court c ON b.court_id = c.court_id
            LEFT JOIN Member m ON b.member_id = m.member_id
            WHERE b.booking_id = :bid
        ");
        $stmt_book->execute([':bid' => $booking_id]);
        $court_item = $stmt_book->fetch(PDO::FETCH_ASSOC);

        if ($court_item) {
            $receipt_date = $court_item['booking_created_at'];
            if (!empty($court_item['member_name'])) {
                $customer_name = $court_item['member_name'] . ' (' . $court_item['member_phone'] . ')';
            }
            $total_amount += floatval($court_item['booking_court_price']);
        }
    }

    // 2. ดึงข้อมูลสินค้าจาก Pos_sale (ถ้ามี)
    if ($pos_id > 0) {
        $stmt_header = $conn->prepare("
            SELECT pos_date, admin_name 
            FROM Pos_sale 
            JOIN Admin ON Pos_sale.admin_id = Admin.admin_id 
            WHERE pos_id = :pos_id
        ");
        $stmt_header->execute([':pos_id' => $pos_id]);
        $header_info = $stmt_header->fetch(PDO::FETCH_ASSOC);

        if ($header_info) {
            $receipt_date = $header_info['pos_date'];
            $admin_name = $header_info['admin_name'];
            $sale_time = $header_info['pos_date'];

            $stmt_items = $conn->prepare("
                SELECT p.product_name, pos.pos_quantity, pos.pos_total_price 
                FROM Pos_sale pos
                JOIN Product p ON pos.product_id = p.product_id
                WHERE pos.pos_date = :sale_time
            ");
            $stmt_items->execute([':sale_time' => $sale_time]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $total_amount += floatval($item['pos_total_price']);
            }
        }
    }

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการโหลดใบเสร็จ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จรับเงิน - T.S. Pattani Badminton</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=1.31">
</head>
<body class="receipt-body">

    <div class="receipt-container">
        
        <div class="header">
            <h3>T.S. PATTANI</h3>
            <p>สนามแบดมินตัน ที.เอส. ปัตตานี</p>
            <p>อ.เมือง จ.ปัตตานี &bull; โทร. 099-324-1657</p>
            <p class="receipt-doc-title">[ ใบเสร็จรับเงิน / สลิปบริการ ]</p>
        </div>

        <div class="info">
            <p>
                <span>เลขที่บิล:</span> 
                <span>#<?php echo $booking_id ? 'BK' . $booking_id : 'POS' . $pos_id; ?></span>
            </p>
            <p>
                <span>วันที่:</span> 
                <span><?php echo date('d/m/Y H:i', strtotime($receipt_date)); ?></span>
            </p>
            <p>
                <span>พนักงาน:</span> 
                <span><?php echo htmlspecialchars($admin_name); ?></span>
            </p>
            <p>
                <span>ลูกค้า:</span> 
                <span><?php echo htmlspecialchars($customer_name); ?></span>
            </p>
        </div>

        <div class="items">
            <table>
                <thead>
                    <tr>
                        <th class="receipt-col-desc">รายการ</th>
                        <th class="receipt-col-qty">จน.</th>
                        <th class="receipt-col-price">รวม (฿)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- รายการเปิดสนาม Walk-in (ถ้ามี) -->
                    <?php if ($court_item): ?>
                    <tr>
                        <td>
                            <strong>เปิดสนาม <?php echo htmlspecialchars($court_item['court_name']); ?></strong><br>
                            <span class="receipt-item-time">
                                <?php echo date('d/m/Y', strtotime($court_item['booking_date'])); ?> 
                                (<?php echo substr($court_item['booking_start_time'], 0, 5); ?> - <?php echo substr($court_item['booking_end_time'], 0, 5); ?>)
                            </span>
                        </td>
                        <td class="receipt-text-center">1 รอบ</td>
                        <td><?php echo number_format($court_item['booking_court_price'], 2); ?></td>
                    </tr>
                    <?php endif; ?>

                    <!-- รายการสินค้าและอุปกรณ์เช่า (ถ้ามี) -->
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="receipt-text-center"><?php echo $item['pos_quantity']; ?></td>
                        <td><?php echo number_format($item['pos_total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="totals">
            <p class="grand-total">
                <span>ยอดชำระสุทธิ:</span> 
                <span><?php echo number_format($total_amount, 2); ?> ฿</span>
            </p>
        </div>

        <div class="footer">
            <p>ขอขอบคุณที่ไว้วางใจใช้บริการ</p>
            <p>*** Please Come Again ***</p>
        </div>

        <button class="btn-print" onclick="window.print();">🖨️ พิมพ์ใบเสร็จ</button>
    </div>

</body>
</html>