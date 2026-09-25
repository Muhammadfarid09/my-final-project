<?php
require_once '../includes/auth_check.php';

if (isset($_GET['id']) && isset($_GET['tab'])) {
    $product_id = intval($_GET['id']);
    $tab_return = $_GET['tab']; // เพื่อให้ลบเสร็จแล้วเด้งกลับไปถูกแท็บ (consumable หรือ rental)

    try {
        // 1. ดึงข้อมูลรูปภาพของสินค้าที่จะลบก่อน เพื่อเอาชื่อไฟล์ไปลบออกจากโฟลเดอร์
        $stmt_img = $conn->prepare("SELECT product_name, product_image FROM Product WHERE product_id = :id");
        $stmt_img->execute([':id' => $product_id]);
        $product = $stmt_img->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // ถ้ารายการนี้มีรูปภาพแนบมาด้วย ให้ทำการลบไฟล์ออกจากโฟลเดอร์
            if (!empty($product['product_image'])) {
                $file_path = '../../uploads/products/' . $product['product_image'];
                if (file_exists($file_path)) {
                    unlink($file_path); // ลบไฟล์
                }
            }

            // 2. ทำการลบข้อมูลออกจากฐานข้อมูล
            $stmt_del = $conn->prepare("DELETE FROM Product WHERE product_id = :id");
            $stmt_del->execute([':id' => $product_id]);

            $_SESSION['success'] = "ลบรายการ <strong>" . htmlspecialchars($product['product_name']) . "</strong> ออกจากระบบสำเร็จ";
        } else {
            $_SESSION['error'] = "ไม่พบข้อมูลรายการสินค้าที่คุณต้องการลบ";
        }

    } catch (PDOException $e) {
        // ถ้าเกิด Error มักจะมาจากการที่มีการผูก Foreign Key ไว้ (เช่น สินค้านี้เคยถูกจองหรือเช่าไปแล้ว)
        // กรณีนี้เราอาจจะไม่ลบ แต่อาจจะใช้วิธีซ่อนข้อมูลแทน (แล้วแต่การออกแบบ)
        // แต่เบื้องต้นแจ้งเตือนแบบนี้ไปก่อนครับ
        $_SESSION['error'] = "ไม่สามารถลบรายการนี้ได้ เนื่องจากมีข้อมูลเกี่ยวข้องอยู่ในระบบ (เช่น ประวัติการเช่า/ขาย): " . $e->getMessage();
    }

    // ลบเสร็จให้เด้งกลับไปที่หน้า products.php พร้อมเปิดแท็บเดิม
    header("Location: ../products.php?tab=" . $tab_return);
    exit();

} else {
    // ถ้าพยายามเข้าหน้านี้โดยตรงโดยไม่ส่ง ID มา
    header("Location: ../products.php");
    exit();
}
?>