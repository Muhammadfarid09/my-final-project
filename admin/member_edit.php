<?php
require_once 'includes/auth_check.php';

if (!isset($_GET['id'])) {
    header("Location: members.php");
    exit();
}

$member_id = intval($_GET['id']);

try {
    // อัปเดตคำสั่ง SQL ให้ JOIN กับตาราง Point เพื่อดึงระดับและแต้มสะสม
    $sql = "SELECT m.*, p.point_balance, p.member_level 
            FROM Member m 
            LEFT JOIN Point p ON m.member_id = p.member_id 
            WHERE m.member_id = :id";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        $_SESSION['error'] = "ไม่พบข้อมูลสมาชิกนี้";
        header("Location: members.php");
        exit();
    }
    
    // ตั้งค่าเริ่มต้นเผื่อกรณีข้อมูลในตาราง Point ยังไม่มี (เพิ่งสมัครใหม่)
    $current_point = $member['point_balance'] ?? 0;
    $current_level = $member['member_level'] ?? 'Bronze';

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("Location: members.php");
    exit();
}
?>
<?php
$page_title = 'แก้ไขข้อมูลสมาชิก - Admin T.S. Pattani';
$page_header = '<i class="fas fa-user-edit"></i> แก้ไขข้อมูลสมาชิกรหัส #MB' . $member['member_id'];
include 'includes/header.php';
?>
    <div class="admin-card max-w-800 mx-auto">
        <div class="header-between mb-20">
            <h4 class="m-0 text-dark">รายละเอียดสมาชิก</h4>
            <a href="members.php" class="btn-back"><i class="fas fa-arrow-left"></i> กลับหน้ารายการ</a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error mb-15">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- ฟอร์มนี้จะส่งไปที่ actions/member_edit_db.php -->
        <form action="actions/member_edit_db.php" method="POST">
            <input type="hidden" name="member_id" value="<?php echo $member['member_id']; ?>">
            
            <div class="grid-2 gap-20">
                <!-- คอลัมน์ซ้าย: ข้อมูลส่วนตัว -->
                <div>
                    <h5 class="m-0 text-gray border-b pb-10">ข้อมูลทั่วไป</h5>
                    <div class="form-group">
                        <label for="member_name">ชื่อ - นามสกุล *</label>
                        <input type="text" name="member_name" id="member_name" class="form-control" value="<?php echo htmlspecialchars($member['member_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="member_phone">เบอร์ติดต่อ *</label>
                        <input type="text" name="member_phone" id="member_phone" class="form-control" value="<?php echo htmlspecialchars($member['member_phone']); ?>" maxlength="10" required>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="member_gender">เพศ</label>
                            <select name="member_gender" id="member_gender" class="form-control">
                                <option value="ชาย" <?php if($member['member_gender'] == 'ชาย') echo 'selected'; ?>>ชาย</option>
                                <option value="หญิง" <?php if($member['member_gender'] == 'หญิง') echo 'selected'; ?>>หญิง</option>
                                <option value="อื่นๆ" <?php if($member['member_gender'] == 'อื่นๆ') echo 'selected'; ?>>อื่นๆ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="member_age">อายุ</label>
                            <input type="number" name="member_age" id="member_age" class="form-control" value="<?php echo $member['member_age']; ?>" min="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="member_occupation">อาชีพ</label>
                        <input type="text" name="member_occupation" id="member_occupation" class="form-control" value="<?php echo htmlspecialchars($member['member_occupation']); ?>">
                    </div>
                </div>

                <!-- คอลัมน์ขวา: การตั้งค่าระบบสำหรับแอดมิน -->
                <div>
                    <h5 class="m-0 text-gray border-b pb-10">การจัดการโดยผู้ดูแล</h5>
                    <div class="form-group">
                        <label for="member_points">แต้มสะสมปัจจุบัน (Points)</label>
                        <input type="number" name="member_points" id="member_points" class="form-control text-18 font-bold text-success" value="<?php echo $current_point; ?>" min="0">
                    </div>

                    <div class="form-group">
                        <label for="member_level">ระดับสมาชิก (Tier)</label>
                        <select name="member_level" id="member_level" class="form-control font-bold">
                            <option value="Bronze" <?php if($current_level == 'Bronze') echo 'selected'; ?>>Bronze (เริ่มต้น)</option>
                            <option value="Silver" <?php if($current_level == 'Silver') echo 'selected'; ?>>Silver</option>
                            <option value="Gold" <?php if($current_level == 'Gold') echo 'selected'; ?>>Gold (สูงสุด)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="member_status">สถานะการใช้งานบัญชี</label>
                        <select name="member_status" id="member_status" class="form-control">
                            <option value="ปกติ" <?php if($member['member_status'] == 'ปกติ') echo 'selected'; ?>>✅ ปกติ (ใช้งานได้)</option>
                            <option value="ระงับสิทธิ์" <?php if($member['member_status'] == 'ระงับสิทธิ์') echo 'selected'; ?>>❌ ระงับสิทธิ์ (ห้ามจอง)</option>
                        </select>
                    </div>
                    
                    <p class="text-12 text-muted-custom mt-20">
                        <i class="fas fa-info-circle"></i> รหัสผ่านของสมาชิกไม่สามารถแก้ไขได้จากหน้านี้ หากลูกค้าลืมรหัสผ่าน กรุณาแนะนำให้ทำการกู้รหัสหรือสมัครใหม่
                    </p>
                </div>
            </div>

            <div class="mt-20 text-center">
                <button type="submit" class="btn-submit w-200 p-10">
                    <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>

    </div>
</main>
</div>

<!-- เรียกใช้ไฟล์ JS กลาง -->
<script src="../assets/js/admin.js?v=1.20"></script>

</body>
</html>