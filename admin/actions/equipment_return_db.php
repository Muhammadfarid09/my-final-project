<?php
require_once '../includes/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $admin_id = intval($_SESSION['admin_id']);
    $rental_id = intval($_POST['rental_id'] ?? 0);
    $condition_status = $_POST['condition_status'] ?? 'ปกติ'; // 'ปกติ', 'ชำรุด', 'สูญหาย'
    $fine_amount = !empty($_POST['fine_amount']) ? floatval($_POST['fine_amount']) : 0.00;
    $fine_reason = trim($_POST['fine_reason'] ?? '');
    $repair_cause = trim($_POST['repair_cause'] ?? '');

    if ($rental_id <= 0) {
        $_SESSION['error'] = "ไม่พบรหัสการเช่าอุปกรณ์";
        header("Location: ../equipment_returns.php");
        exit();
    }

    try {
        $conn->beginTransaction();

        // 1. ตรวจสอบข้อมูลรายการเช่า
        $stmt_check = $conn->prepare("
            SELECT r.*, p.product_name, p.product_stock 
            FROM Rental r
            JOIN Product p ON r.product_id = p.product_id
            WHERE r.rental_id = :r_id
        ");
        $stmt_check->execute([':r_id' => $rental_id]);
        $rental = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$rental) {
            throw new Exception("ไม่พบข้อมูลรายการเช่าอุปกรณ์นี้ในระบบ");
        }

        if (in_array($rental['rental_status'], ['คืนแล้ว', 'สูญหาย'])) {
            throw new Exception("รายการนี้ถูกตรวจรับคืนไปแล้ว ไม่สามารถดำเนินการซ้ำได้");
        }

        $product_id = intval($rental['product_id']);
        $qty = intval($rental['rental_quantity']);

        // 2. กำหนดสถานะการเช่าใหม่
        if ($condition_status === 'ชำรุด') {
            $new_status = 'ชำรุด';
        } elseif ($condition_status === 'สูญหาย') {
            $new_status = 'สูญหาย';
        } else {
            $new_status = 'คืนแล้ว';
        }

        // 3. อัปเดตข้อมูลตาราง Rental
        $sql_update_rental = "
            UPDATE Rental 
            SET rental_status = :status,
                rental_fine = :fine,
                rental_fine_reason = :fine_reason,
                rental_actual_return = NOW()
            WHERE rental_id = :r_id
        ";
        $stmt_update_rental = $conn->prepare($sql_update_rental);
        $stmt_update_rental->execute([
            ':status' => $new_status,
            ':fine' => $fine_amount,
            ':fine_reason' => !empty($fine_reason) ? $fine_reason : null,
            ':r_id' => $rental_id
        ]);

        // 4. จัดการสต็อกสินค้าและระบบส่งซ่อมตามสภาพอุปกรณ์
        if ($condition_status === 'ปกติ') {
            // สภาพสมบูรณ์ -> คืนสต็อกเข้าสู่ระบบพร้อมให้เช่า
            $sql_stock = "UPDATE Product SET product_stock = product_stock + :qty WHERE product_id = :p_id";
            $conn->prepare($sql_stock)->execute([
                ':qty' => $qty,
                ':p_id' => $product_id
            ]);
            $msg = "ตรวจรับคืนอุปกรณ์ '{$rental['product_name']}' สภาพปกติ คืนสต็อก {$qty} ชิ้น เรียบร้อยแล้ว";
            
        } elseif ($condition_status === 'ชำรุด') {
            // สภาพชำรุด -> ไม่คืนสต็อก แต่ส่งเข้าคิวซ่อมบำรุง Equipment_repair ทันที
            $cause_text = !empty($repair_cause) ? $repair_cause : ($fine_reason ? $fine_reason : "ชำรุดจากการเช่าใช้งาน");
            $sql_repair = "
                INSERT INTO Equipment_repair 
                (product_id, admin_id, eq_repair_qty, eq_repair_cause, eq_repair_date, eq_repair_status)
                VALUES (:p_id, :a_id, :qty, :cause, CURDATE(), 'กำลังซ่อม')
            ";
            $conn->prepare($sql_repair)->execute([
                ':p_id' => $product_id,
                ':a_id' => $admin_id,
                ':qty' => $qty,
                ':cause' => $cause_text
            ]);
            $msg = "บันทึกรับคืนอุปกรณ์ชำรุด ส่งเข้าสู่คิวซ่อมบำรุงเรียบร้อยแล้ว";

        } elseif ($condition_status === 'สูญหาย') {
            // สูญหาย -> ตัดจำหน่ายถาวร (สต็อกถูกตัดไปตั้งแต่ตอนจองแล้ว จึงไม่ต้องทำอะไรเพิ่ม)
            $msg = "บันทึกอุปกรณ์ '{$rental['product_name']}' สูญหาย ตัดออกจากระบบถาวรแล้ว";
        }

        // 5. บันทึกรายรับค่าปรับ (ถ้ามี) ลงตาราง Revenue
        if ($fine_amount > 0) {
            $sql_rev = "
                INSERT INTO Revenue 
                (payment_id, revenue_court_amount, revenue_rental_amount, revenue_product_amount, revenue_total_amount, revenue_date, revenue_type)
                VALUES (NULL, 0.00, :fine, 0.00, :fine, CURDATE(), 'หน้าร้าน')
            ";
            $conn->prepare($sql_rev)->execute([':fine' => $fine_amount]);
            $msg .= " (บันทึกรายรับค่าปรับจำนวน " . number_format($fine_amount, 2) . " บาท)";
        }

        $conn->commit();
        $_SESSION['success'] = $msg;

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: ../equipment_returns.php");
    exit();

} else {
    header("Location: ../equipment_returns.php");
    exit();
}
?>
