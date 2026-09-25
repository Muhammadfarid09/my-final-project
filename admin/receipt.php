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
    <style>
        /* สไตล์สำหรับใบเสร็จสลิปขนาดความกว้าง 80mm */
        body {
            font-family: 'Courier New', Courier, 'Prompt', monospace;
            background: #f1f5f9;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            font-size: 13px;
            color: #111;
        }
        .receipt-container {
            background: #ffffff;
            width: 320px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 6px;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
        }
        .header h3 { 
            margin: 0 0 4px 0; 
            font-size: 18px; 
            letter-spacing: 0.5px;
        }
        .header p { margin: 2px 0; font-size: 12px; }
        
        .info {
            margin-bottom: 12px;
            border-bottom: 1px dashed #333;
            padding-bottom: 8px;
            font-size: 12px;
        }
        .info p { margin: 3px 0; display: flex; justify-content: space-between; }

        .items table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .items th, .items td {
            text-align: left;
            padding: 4px 0;
            font-size: 12px;
        }
        .items th:last-child, .items td:last-child {
            text-align: right;
        }
        .items th {
            border-bottom: 1px dashed #333;
        }

        .totals {
            border-top: 1px dashed #333;
            padding-top: 8px;
            margin-bottom: 15px;
        }
        .totals p {
            margin: 4px 0;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }
        .totals p.grand-total {
            font-size: 17px;
            font-weight: bold;
            border-top: 1px solid #111;
            padding-top: 6px;
            margin-top: 6px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            border-top: 1px dashed #333;
            padding-top: 10px;
        }
        .footer p { margin: 2px 0; }

        /* ปุ่มสั่งพิมพ์ */
        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            background: #2563eb;
            color: white;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            margin-top: 15px;
            border: none;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
        
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; width: 100%; padding: 0; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        
        <div class="header">
            <h3>T.S. PATTANI</h3>
            <p>สนามแบดมินตัน ที.เอส. ปัตตานี</p>
            <p>อ.เมือง จ.ปัตตานี &bull; โทร. 099-324-1657</p>
            <p style="font-weight: bold; margin-top: 6px;">[ ใบเสร็จรับเงิน / สลิปบริการ ]</p>
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
                        <th style="width: 55%;">รายการ</th>
                        <th style="width: 15%; text-align: center;">จน.</th>
                        <th style="width: 30%;">รวม (฿)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- รายการเปิดสนาม Walk-in (ถ้ามี) -->
                    <?php if ($court_item): ?>
                    <tr>
                        <td>
                            <strong>เปิดสนาม <?php echo htmlspecialchars($court_item['court_name']); ?></strong><br>
                            <span style="font-size: 11px; color: #555;">
                                <?php echo date('d/m/Y', strtotime($court_item['booking_date'])); ?> 
                                (<?php echo substr($court_item['booking_start_time'], 0, 5); ?> - <?php echo substr($court_item['booking_end_time'], 0, 5); ?>)
                            </span>
                        </td>
                        <td style="text-align: center;">1 รอบ</td>
                        <td><?php echo number_format($court_item['booking_court_price'], 2); ?></td>
                    </tr>
                    <?php endif; ?>

                    <!-- รายการสินค้าและอุปกรณ์เช่า (ถ้ามี) -->
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td style="text-align: center;"><?php echo $item['pos_quantity']; ?></td>
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