<?php
require_once 'includes/auth_check.php';

// รับค่าหมายเลขการขายล่าสุดจาก URL
$pos_id = isset($_GET['pos_id']) ? intval($_GET['pos_id']) : 0;

if ($pos_id === 0) {
    die("ไม่พบข้อมูลใบเสร็จ");
}

try {
    // 1. ดึงข้อมูลว่าบิลนี้ใครเป็นคนขาย และขายเมื่อไหร่
    // (เราดึงแค่รายการเดียวเพื่อเอาหัวบิล เพราะใน POS เราขายหลายชิ้นในบิลเดียว)
    $sql_header = "SELECT pos_date, admin_name 
                   FROM Pos_sale 
                   JOIN Admin ON Pos_sale.admin_id = Admin.admin_id 
                   WHERE pos_id = :pos_id";
    $stmt_header = $conn->prepare($sql_header);
    $stmt_header->execute([':pos_id' => $pos_id]);
    $header_info = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header_info) {
        die("ไม่พบข้อมูลบิลนี้");
    }

    // เนื่องจาก POS ของคุณบันทึกแยกรายชิ้น เราต้องใช้เวลา (pos_date) และรหัสแอดมินที่ตรงกัน 
    // เพื่อดึงสินค้า "ทั้งหมด" ที่ถูกขายในตะกร้าเดียวกัน (ขายในเวลาเดียวกันเป๊ะๆ)
    $sale_time = $header_info['pos_date'];
    
    $sql_items = "SELECT p.product_name, pos.pos_quantity, pos.pos_total_price 
                  FROM Pos_sale pos
                  JOIN Product p ON pos.product_id = p.product_id
                  WHERE pos.pos_date = :sale_time";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->execute([':sale_time' => $sale_time]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $total_amount = 0;

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จรับเงิน - T.S. Pattani</title>
    <style>
        /* สไตล์สำหรับใบเสร็จขนาด 80mm */
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f0f0f0;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            font-size: 12px;
            color: #000;
        }
        .receipt-container {
            background: #fff;
            width: 300px; /* ความกว้างประมาณ 80mm */
            padding: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .header h3 { margin: 0 0 5px 0; font-size: 16px; }
        .header p { margin: 2px 0; }
        
        .info {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        .info p { margin: 3px 0; display: flex; justify-content: space-between; }

        .items table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items th, .items td {
            text-align: left;
            padding: 3px 0;
        }
        .items th:last-child, .items td:last-child {
            text-align: right;
        }
        .items border-bottom {
            border-bottom: 1px dashed #000;
        }

        .totals {
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-bottom: 15px;
        }
        .totals p {
            margin: 3px 0;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }
        .totals p.grand-total {
            font-size: 16px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
        }
        .footer p { margin: 2px 0; }

        /* ปุ่มกดพิมพ์ (จะถูกซ่อนตอนพิมพ์จริง) */
        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            background: #28a745;
            color: white;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            margin-top: 15px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; width: 100%; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        
        <div class="header">
            <h3>T.S. Pattani</h3>
            <p>บริการสนามแบดมินตันและจำหน่ายอุปกรณ์</p>
            <p>อ.เมือง จ.ปัตตานี</p>
            <p>ใบเสร็จรับเงิน / ใบกำกับภาษีอย่างย่อ</p>
        </div>

        <div class="info">
            <p><span>วันที่:</span> <span><?php echo date('d/m/Y H:i', strtotime($header_info['pos_date'])); ?></span></p>
            <p><span>ผู้รับเงิน:</span> <span><?php echo htmlspecialchars($header_info['admin_name']); ?></span></p>
        </div>

        <div class="items">
            <table>
                <tr>
                    <th style="width: 50%;">รายการ</th>
                    <th style="width: 20%; text-align: center;">จน.</th>
                    <th style="width: 30%;">รวม (฿)</th>
                </tr>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td style="text-align: center;"><?php echo $item['pos_quantity']; ?></td>
                    <td><?php echo number_format($item['pos_total_price'], 2); ?></td>
                </tr>
                <?php $total_amount += $item['pos_total_price']; ?>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="totals">
            <p class="grand-total">
                <span>ยอดชำระสุทธิ:</span> 
                <span><?php echo number_format($total_amount, 2); ?> ฿</span>
            </p>
        </div>

        <div class="footer">
            <p>ขอบคุณที่ใช้บริการครับ</p>
            <p>*** Please Come Again ***</p>
        </div>

        <button class="btn-print" onclick="window.print();">🖨️ พิมพ์ใบเสร็จ</button>
    </div>

</body>
</html>