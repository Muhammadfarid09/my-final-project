<?php
// ตั้งค่าเซิร์ฟเวอร์และฐานข้อมูล
$host = "localhost";
$username = "root"; // ชื่อผู้ใช้งานเริ่มต้นของ XAMPP
$password = ""; // รหัสผ่านเริ่มต้นของ XAMPP (ปล่อยว่างไว้)
$database = "ts_pattani_db"; // ชื่อฐานข้อมูลตามที่เราออกแบบไว้

try {
    // 1. สร้างการเชื่อมต่อฐานข้อมูลด้วย PDO และเซ็ตให้รองรับภาษาไทย (utf8mb4)
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    
    // 2. ตั้งค่าให้แสดงข้อผิดพลาดออกมาให้เห็นชัดเจน (ช่วยให้หาบั๊กได้ง่ายขึ้นเวลาเขียนโค้ด)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 3. ตั้งค่า Timezone เป็นเวลาประเทศไทย (สำคัญมากสำหรับระบบจองที่มีการล็อกเวลา)
    date_default_timezone_set('Asia/Bangkok');

    // (สำหรับการทดสอบ) ถ้าอยากเช็คว่าเชื่อมต่อได้ไหม ให้เอาเครื่องหมาย // ข้างหน้าบรรทัดล่างออก
    // echo "เชื่อมต่อฐานข้อมูล ts_pattani_db สำเร็จ!"; 

} catch(PDOException $e) {
    // กรณีเชื่อมต่อไม่สำเร็จ จะหยุดการทำงานและแสดงข้อความแจ้งเตือน
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
}
?>