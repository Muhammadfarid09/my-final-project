<?php
require_once '../includes/auth_check.php';

$member_id = intval($_SESSION['member_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    // ==========================================
    // กรณีที่ 1: แก้ไขข้อมูลส่วนตัวทั่วไป
    // ==========================================
    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');

        // รองรับทั้งแบบแยกชื่อ-นามสกุล (Smart UI Split) และแบบรวมเดิม
        if (!empty($first_name) || !empty($last_name)) {
            if (empty($first_name) || empty($last_name)) {
                throw new Exception("กรุณากรอกทั้งชื่อและนามสกุลให้ครบถ้วน");
            }
            $member_name = $first_name . ' ' . $last_name;
        } else {
            $member_name = trim($_POST['member_name'] ?? '');
        }

        $member_phone = trim($_POST['member_phone'] ?? '');
        $member_gender = $_POST['member_gender'] ?? 'อื่นๆ';
        $member_age = !empty($_POST['member_age']) ? intval($_POST['member_age']) : null;
        $member_occupation = trim($_POST['member_occupation'] ?? '');

        try {
            if (empty($member_name) || empty($member_phone)) {
                throw new Exception("กรุณากรอกชื่อ นามสกุล และเบอร์โทรศัพท์ให้ครบถ้วน");
            }

            if (!preg_match('/^[0-9]{10}$/', $member_phone)) {
                throw new Exception("รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง กรุณากรอกตัวเลข 10 หลัก");
            }

            if ($member_age !== null && ($member_age < 5 || $member_age > 120)) {
                throw new Exception("กรุณากรอกอายุให้ถูกต้อง (ระหว่าง 5 - 120 ปี)");
            }

            // ตรวจสอบเบอร์โทรซ้ำกับสมาชิกท่านอื่น
            $stmt_check = $conn->prepare("SELECT member_id FROM Member WHERE member_phone = :phone AND member_id != :mid");
            $stmt_check->execute([':phone' => $member_phone, ':mid' => $member_id]);
            if ($stmt_check->fetch()) {
                throw new Exception("เบอร์โทรศัพท์ {$member_phone} นี้ถูกใช้งานโดยบัญชีอื่นในระบบแล้ว");
            }

            // อัปเดตข้อมูลลงฐานข้อมูล
            $sql = "UPDATE Member 
                    SET member_name = :name, 
                        member_phone = :phone, 
                        member_gender = :gender, 
                        member_age = :age, 
                        member_occupation = :occupation 
                    WHERE member_id = :mid";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $member_name,
                ':phone' => $member_phone,
                ':gender' => $member_gender,
                ':age' => $member_age,
                ':occupation' => $member_occupation,
                ':mid' => $member_id
            ]);

            // อัปเดต Session
            $_SESSION['member_name'] = $member_name;
            $_SESSION['success'] = "บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว";

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: ../profile.php");
        exit();
    }

    // ==========================================
    // กรณีที่ 2: เปลี่ยนรหัสผ่าน
    // ==========================================
    if ($action === 'change_password') {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        try {
            if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("กรุณากรอกข้อมูลรหัสผ่านให้ครบทุกช่อง");
            }

            if (strlen($new_password) < 6) {
                throw new Exception("รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน");
            }

            // ดึงรหัสผ่านเดิมจากฐานข้อมูลมาตรวจสอบ
            $stmt_pwd = $conn->prepare("SELECT member_password FROM Member WHERE member_id = :mid");
            $stmt_pwd->execute([':mid' => $member_id]);
            $current_hash = $stmt_pwd->fetchColumn();

            if (!password_verify($old_password, $current_hash)) {
                throw new Exception("รหัสผ่านเดิมไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง");
            }

            // ตรวจสอบว่ารหัสผ่านใหม่ไม่ซ้ำกับรหัสผ่านเดิม
            if (password_verify($new_password, $current_hash)) {
                throw new Exception("รหัสผ่านใหม่ต้องไม่เหมือนกับรหัสผ่านเดิม");
            }

            // เข้ารหัสผ่านใหม่และอัปเดต (BCRYPT)
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt_update = $conn->prepare("UPDATE Member SET member_password = :hash WHERE member_id = :mid");
            $stmt_update->execute([':hash' => $new_hash, ':mid' => $member_id]);

            $_SESSION['success'] = "เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว";

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: ../profile.php");
        exit();
    }

    // ==========================================
    // กรณีที่ 3: อัปโหลด / เปลี่ยนรูปโปรไฟล์ (Avatar Upload)
    // ==========================================
    if ($action === 'update_avatar') {
        try {
            if (!isset($_FILES['member_avatar']) || $_FILES['member_avatar']['error'] !== UPLOAD_ERR_OK) {
                $upload_error = $_FILES['member_avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
                if ($upload_error === UPLOAD_ERR_INI_SIZE || $upload_error === UPLOAD_ERR_FORM_SIZE) {
                    throw new Exception("ขนาดไฟล์รูปภาพเกินกำหนด (ต้องไม่เกิน 2MB)");
                }
                throw new Exception("กรุณาเลือกไฟล์รูปภาพที่ต้องการอัปโหลด");
            }

            $file = $_FILES['member_avatar'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if ($file['size'] > $max_size) {
                throw new Exception("ขนาดไฟล์รูปภาพต้องไม่เกิน 2MB");
            }

            // ตรวจสอบ MIME type และ Magic Bytes เพื่อความปลอดภัยสูงสุด
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file['tmp_name']);
            $allowed_mimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!array_key_exists($mime_type, $allowed_mimes)) {
                throw new Exception("รูปแบบไฟล์ไม่ถูกต้อง รองรับเฉพาะไฟล์ .jpg, .jpeg, .png และ .webp เท่านั้น");
            }

            // ตรวจสอบว่าเปิดอ่านเป็นรูปภาพได้จริง
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info === false) {
                throw new Exception("ไฟล์ที่อัปโหลดไม่ใช่รูปภาพที่ถูกต้อง");
            }

            $upload_dir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $use_gd = extension_loaded('gd');
            $src_image = null;
            $new_filename = '';

            if ($use_gd) {
                switch ($mime_type) {
                    case 'image/jpeg':
                        $src_image = @imagecreatefromjpeg($file['tmp_name']);
                        break;
                    case 'image/png':
                        $src_image = @imagecreatefrompng($file['tmp_name']);
                        break;
                    case 'image/webp':
                        if (function_exists('imagecreatefromwebp')) {
                            $src_image = @imagecreatefromwebp($file['tmp_name']);
                        }
                        break;
                }
            }

            if ($use_gd && $src_image) {
                // ทำการ Crop & Resize เป็นสี่เหลี่ยมจัตุรัส 300x300 px จากกึ่งกลาง
                $target_size = 300;
                $dst_image = imagecreatetruecolor($target_size, $target_size);

                // จัดการ Transparency สำหรับ PNG / WebP
                imagealphablending($dst_image, false);
                imagesavealpha($dst_image, true);
                $transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
                imagefilledrectangle($dst_image, 0, 0, $target_size, $target_size, $transparent);
                imagealphablending($dst_image, true);

                // คำนวณ Square Crop จากจุดกึ่งกลาง (Center Crop)
                $min_edge = min($orig_width, $orig_height);
                $crop_x = intval(($orig_width - $min_edge) / 2);
                $crop_y = intval(($orig_height - $min_edge) / 2);

                imagecopyresampled(
                    $dst_image,
                    $src_image,
                    0, 0,
                    $crop_x, $crop_y,
                    $target_size, $target_size,
                    $min_edge, $min_edge
                );

                if (function_exists('imagewebp')) {
                    $new_filename = 'avatar_' . $member_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
                    $dest_path = $upload_dir . $new_filename;
                    imagewebp($dst_image, $dest_path, 85);
                } else {
                    $new_filename = 'avatar_' . $member_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
                    $dest_path = $upload_dir . $new_filename;
                    imagejpeg($dst_image, $dest_path, 88);
                }

                imagedestroy($src_image);
                imagedestroy($dst_image);
            } else {
                // Graceful Fallback: กรณีที่เซิร์ฟเวอร์ไม่ได้เปิด GD
                $ext = $allowed_mimes[$mime_type];
                $new_filename = 'avatar_' . $member_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest_path = $upload_dir . $new_filename;
                if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
                    throw new Exception("ไม่สามารถบันทึกไฟล์รูปภาพได้ กรุณาลองใหม่อีกครั้ง");
                }
            }

            // ดึงชื่อไฟล์รูปเก่ามาลบทิ้ง ป้องกันไฟล์ตกค้าง
            $stmt_old = $conn->prepare("SELECT member_avatar FROM Member WHERE member_id = :mid");
            $stmt_old->execute([':mid' => $member_id]);
            $old_avatar = $stmt_old->fetchColumn();

            if (!empty($old_avatar)) {
                $old_file_path = $upload_dir . basename($old_avatar);
                if (file_exists($old_file_path) && is_file($old_file_path)) {
                    @unlink($old_file_path);
                }
            }

            // บันทึกลงฐานข้อมูล
            $relative_path = 'uploads/avatars/' . $new_filename;
            $stmt_update = $conn->prepare("UPDATE Member SET member_avatar = :avatar WHERE member_id = :mid");
            $stmt_update->execute([
                ':avatar' => $relative_path,
                ':mid' => $member_id
            ]);

            // อัปเดตใน Session
            $_SESSION['member_avatar'] = $relative_path;
            $_SESSION['success'] = "อัปเดตรูปโปรไฟล์เรียบร้อยแล้ว";

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: ../profile.php");
        exit();
    }

    // ==========================================
    // กรณีที่ 4: ลบรูปโปรไฟล์ (คืนค่ารูปเริ่มต้น)
    // ==========================================
    if ($action === 'delete_avatar') {
        try {
            $stmt_old = $conn->prepare("SELECT member_avatar FROM Member WHERE member_id = :mid");
            $stmt_old->execute([':mid' => $member_id]);
            $old_avatar = $stmt_old->fetchColumn();

            if (!empty($old_avatar)) {
                $upload_dir = __DIR__ . '/../uploads/avatars/';
                $old_file_path = $upload_dir . basename($old_avatar);
                if (file_exists($old_file_path) && is_file($old_file_path)) {
                    @unlink($old_file_path);
                }
            }

            $stmt_del = $conn->prepare("UPDATE Member SET member_avatar = NULL WHERE member_id = :mid");
            $stmt_del->execute([':mid' => $member_id]);

            $_SESSION['member_avatar'] = null;
            $_SESSION['success'] = "ลบรูปโปรไฟล์เรียบร้อยแล้ว คืนค่าเป็นรูปเริ่มต้น";

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: ../profile.php");
        exit();
    }
}

// ถ้าเข้าถึงไฟล์ตรงๆ ให้ redirect กลับหน้าโปรไฟล์
header("Location: ../profile.php");
exit();
?>
