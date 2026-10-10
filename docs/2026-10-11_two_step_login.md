# บันทึกการพัฒนาระบบเข้าสู่ระบบแบบ 2 ขั้นตอน (Two-Step Login with Welcome Feedback)

**วันที่บันทึก:** 11 ตุลาคม 2026  
**ระบบ/โมดูล:** การพิสูจน์ตัวตนสมาชิก (Member Authentication System)  
**มาตรฐานที่เกี่ยวข้อง:** `docs/PROJECT_HCI_RULES.md` และ `docs/ADDITIONAL_RULES.md`

---

## 1. ทำอะไรบ้าง (What Was Done)

### รายการไฟล์ที่สร้างและแก้ไข
1. **สร้างไฟล์ใหม่:**
   - [`actions/check_phone_ajax.php`](file:///c:/xampp/htdocs/ts-pattani/actions/check_phone_ajax.php): จุดบริการ AJAX สำหรับตรวจสอบความถูกต้องของเบอร์โทรศัพท์ (10 หลักขึ้นต้นด้วย 0), ตรวจสอบการมีอยู่ของบัญชีในฐานข้อมูลตาราง `Member`, และตรวจสอบสถานะ `member_status` เพื่อส่งชื่อสมาชิกกลับมาแสดง Welcome Feedback
2. **แก้ไขไฟล์ระบบ:**
   - [`login.php`](file:///c:/xampp/htdocs/ts-pattani/login.php): ปรับปรุงโครงสร้างหน้าเข้าสู่ระบบใหม่ทั้งหมดเป็นแบบ 2 ขั้นตอน (Step 1: ระบุเบอร์โทรศัพท์, Step 2: ยืนยันรหัสผ่านพร้อมข้อมูลบัญชี) โทนสีและ Typography เข้าชุดกับ `register.php`
   - [`actions/login_db.php`](file:///c:/xampp/htdocs/ts-pattani/actions/login_db.php): เพิ่มระบบจัดการคุกกี้จดจำฉันไว้ในระบบ (`remember_phone` 30 วัน), การจำเบอร์เดิมผ่าน `$_SESSION['last_phone']` เมื่อกรอกรหัสผ่านผิดเพื่อให้ผู้ใช้ไม่ต้องพิมพ์เบอร์ใหม่, และการล้าง Session เมื่อล็อกอินสำเร็จ
   - [`assets/css/style.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/style.css): เพิ่มคลาสสไตล์สำหรับหน้าล็อกอิน 2 ขั้นตอน (`.auth-step-pane`, `.auth-step-progress`, `.auth-step-dot`, `.auth-step-header`, `.account-feedback-card`, `.btn-change-phone`, `.password-toggle-group`, `.btn-toggle-password`, `.auth-options-row`, `.remember-me-label`) โดยไม่มีการใช้ `!important`
   - [`assets/css/responsive-mobile.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/responsive-mobile.css): ปรับแต่งระยะขอบและ Padding ของการ์ดฟอร์มบนหน้าจอมือถือขนาดเล็ก (`max-width: 480px`)
   - [`assets/js/member.js`](file:///c:/xampp/htdocs/ts-pattani/assets/js/member.js): เพิ่มโมดูล JavaScript IIFE สำหรับควบคุม Stepper, การตรวจสอบรูปแบบเบอร์โทรศัพท์, การดึงชื่อผู้ใช้แบบ Asynchronous, การเปิด-ปิดตารหัสผ่าน, และระบบป้องกัน Double Submission ทั้งปุ่มถัดไปและปุ่มเข้าสู่ระบบ

---

## 2. ระบบเป็นอย่างไร (How the System Works)

### โฟลว์การทำงาน (Logic Flow)

```
[ผู้ใช้เข้าหน้า login.php]
         │
         ├── มีคุกกี้ remember_phone หรือ last_phone ?
         │        ├── ใช่: เติมเบอร์โทรลงช่องอัตโนมัติ
         │        └── ไม่ใช่: ช่องเบอร์โทรว่างเปล่า โฟกัสพร้อมพิมพ์
         ▼
[ขั้นตอนที่ 1: ระบุเบอร์โทรศัพท์]
         │
         ├── ผู้ใช้พิมพ์เบอร์ 10 หลัก (08X-XXX-XXXX) แล้วกด "ถัดไป" หรือเคาะ "Enter"
         ├── JS ตรวจสอบ Regex (/^0[0-9]{9}$/)
         ├── ยิง AJAX POST ไปยัง actions/check_phone_ajax.php
         │        ├── พบเบอร์ & สถานะปกติ: ส่ง { status: 'success', member_name, masked_phone }
         │        ├── ถูกระงับสิทธิ์: แจ้งเตือนข้อความเตือนระงับสิทธิ์
         │        └── ไม่พบเบอร์: แสดงข้อความแจ้งเตือนสีแดงในหน้า Step 1
         ▼
[ขั้นตอนที่ 2: ยืนยันรหัสผ่านพร้อม Welcome Feedback]
         │
         ├── Transition Slide-In นุ่มนวล ไม่รีโหลดหน้า
         ├── Progress Bar ขยับไปยังจุดที่ 2
         ├── แสดง Account Feedback Card:
         │        ├── ไอคอน Avatar
         │        ├── ชื่อจริงของสมาชิก (เช่น "นายเอกลักษณ์ ชูใจ")
         │        ├── เบอร์โทรศัพท์ที่ปกปิดบางส่วน (เช่น 081-XXX-4567)
         │        └── ปุ่ม "เปลี่ยนเบอร์" (กดย้อนกลับไป Step 1 เพื่อแก้เบอร์ได้ทันที)
         ├── ผู้ใช้กรอกรหัสผ่าน (สามารถกดรูปดวงตาเพื่อสลับแสดง/ซ่อนรหัสผ่านได้)
         ├── เลือกว่าต้องการ "จดจำฉันไว้ในระบบ" หรือไม่
         └── กดปุ่ม "เข้าสู่ระบบ"
                  │
                  ▼
[ส่งฟอร์มไปยัง actions/login_db.php]
         │
         ├── รหัสผ่านถูกต้อง:
         │        ├── สร้าง Session (member_id, member_name, member_phone)
         │        ├── บันทึก/ลบคุกกี้ remember_phone (30 วัน) ตามที่ติ๊กเลือก
         │        └── Redirect ไปยังหน้าที่ต้องการ (เช่น booking.php หรือ index.php)
         └── รหัสผ่านไม่ถูกต้อง:
                  ├── บันทึก $_SESSION['error'] และ $_SESSION['last_phone']
                  └── ส่งกลับมาที่ login.php -> เปิด Step 2 อัตโนมัติพร้อมแสดงแจ้งเตือน
```

### การเชื่อมโยงกับฐานข้อมูลและความปลอดภัย
- **Prepared Statements:** ทั้ง `check_phone_ajax.php` และ `login_db.php` ใช้ PDO Prepared Statements 100% ป้องกัน SQL Injection
- **Password Hashing:** ตรวจสอบรหัสผ่านผ่าน `password_verify()` กับ Hash ที่ปลอดภัย
- **Nielsen HCI Heuristic Compliance:**
  - *Heuristic 1 (Visibility of System Status):* มีปุ่มลูกตาเปิด/ปิดรหัสผ่าน, มีสถานะ Loading Spinner ระหว่างตรวจเบอร์โทรและเข้าสู่ระบบ
  - *Heuristic 2 (Match between System and Real World):* การแสดงชื่อจริงและเบอร์โทรที่ฟอร์แมตให้มองเห็นง่าย สร้างความมั่นใจว่าเข้าสู่ระบบถูกบัญชี
  - *Heuristic 3 (User Control and Freedom):* มีปุ่ม "เปลี่ยนเบอร์" ให้ผู้ใช้กดย้อนกลับไปแก้ไขได้ตลอดเวลาโดยไม่ต้องรีเฟรชหน้า
  - *Heuristic 5 (Error Prevention):* ตรวจสอบรูปแบบเบอร์โทรศัพท์และป้องกันการกดส่งซ้ำ (Double Submission Guard)

---

## 3. การใช้งานอย่างไร (How to Use / Test)

### ขั้นตอนการทดสอบ
1. เข้าไปที่ URL: `http://localhost/ts-pattani/login.php`
2. **ทดสอบ Step 1 (ระบุเบอร์โทรศัพท์):**
   - ลองกรอกเบอร์ไม่ครบ 10 หลัก (เช่น `081234`) แล้วกด "ถัดไป" -> ระบบแจ้งเตือนรูปแบบเบอร์ไม่ถูกต้อง
   - ลองกรอกเบอร์ที่ไม่มีในระบบ (เช่น `0999999999`) -> ระบบแจ้งเตือน "ไม่พบเบอร์โทรศัพท์นี้ในระบบ กรุณาตรวจสอบหรือสมัครสมาชิกใหม่"
   - ลองกรอกเบอร์สมาชิกจริงในระบบ (เช่น `0812345678`) -> ปุ่มแสดงสถานะ "กำลังตรวจสอบ..." และเลื่อนเปลี่ยนไปยัง Step 2 อย่างนุ่มนวล
3. **ทดสอบ Step 2 (ข้อมูลบัญชีและรหัสผ่าน):**
   - ตรวจสอบ Account Feedback Card แสดงชื่อสมาชิกจริงและเบอร์โทรศัพท์
   - ทดสอบกดปุ่ม **"เปลี่ยนเบอร์"** -> ฟอร์มจะสลับกลับมาหน้า Step 1 เพื่อให้แก้เบอร์โทร
   - กดปุ่ม **รูปลูกตา (Eye Toggle)** ด้านขวาของช่องรหัสผ่าน -> ตรวจสอบว่าประเภทของช่องสลับระหว่างรหัสผ่านและข้อความธรรมดา
   - ติ๊กเลือก Checkbox **"จดจำฉันไว้ในระบบ"**
4. **ทดสอบการเข้าสู่ระบบ:**
   - กรอกรหัสผ่านผิด -> ระบบจะ Redirect กลับมาเปิดหน้า Step 2 ทันทีพร้อมแสดง Alert ข้อผิดพลาด โดยไม่ต้องพิมพ์เบอร์ใหม่
   - กรอกรหัสผ่านถูกต้อง -> เข้าสู่ระบบสำเร็จและนำทางไปยัง `index.php` (หรือ `booking.php` หากมี `?redirect=booking.php`)
   - ปิดเบราว์เซอร์แล้วเปิด `login.php` ใหม่ -> จะพบว่าเบอร์โทรศัพท์ถูกเติมไว้ล่วงหน้าพร้อมติ๊ก "จดจำฉันไว้ในระบบ"

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)

1. **ปัญหาการกดปุ่ม Enter ในช่องเบอร์โทรศัพท์ส่งฟอร์มก่อนเวลา:**
   - *สาเหตุ:* ช่อง Input ในแท็ก `<form>` เมื่อผู้ใช้กดปุ่ม Enter จะทำการ Submit ฟอร์มทันที ทำให้ส่งค่าไปยัง `login_db.php` โดยที่ยังไม่ได้กรอกรหัสผ่านใน Step 2
   - *วิธีแก้:* ดักจับ `keydown` Event บน `#member_phone` ถ้าเป็นปุ่ม `Enter` ให้ `preventDefault()` แล้วสั่งเรียกฟังก์ชัน `proceedToStep2()` แทน
2. **ปัญหาการส่งซ้ำ (Double Submission):**
   - *สาเหตุ:* ผู้ใช้อาจกดย้ำปุ่ม "ถัดไป" ขณะรอเครือข่ายตอบกลับ หรือกดย้ำปุ่ม "เข้าสู่ระบบ"
   - *วิธีแก้:* เพิ่มตัวแปรสถานะ Flag `isVerifyingPhone` และ `isSubmittingLogin` พร้อมสั่ง `disabled = true` และเปลี่ยนข้อความปุ่มเป็น Spinner Loading ทันทีที่กด
3. **ปัญหาการรีโหลดหน้าเมื่อรหัสผ่านผิดแล้วต้องเริ่มกรอกเบอร์ใหม่:**
   - *สาเหตุ:* หน้าเดิมเมื่อ PHP แจ้งเตือน Error จะล้างค่าทั้งหมด ทำให้ผู้ใช้ต้องพิมพ์เบอร์โทรศัพท์และรหัสผ่านใหม่ตั้งแต่ต้น
   - *วิธีแก้:* ใน `actions/login_db.php` บันทึก `$_SESSION['last_phone']` ไว้ และใน `login.php` ตรวจสอบว่าหากมีข้อผิดพลาดให้ตั้งค่า `$start_step = 2` เริ่มต้นที่หน้า Step 2 ทันที
4. **การปฏิบัติตามกฎเหล็ก Separation of Concerns (CSS):**
   - ไม่มีการใช้ Inline Style (`style="..."`) ในมาร์กอัป HTML และไม่มีแท็ก `<style>` กลางไฟล์ PHP
   - คลาสและตัวแปรทั้งหมดถูกแยกเก็บใน `assets/css/style.css` และ `assets/css/responsive-mobile.css` โดยไม่มีการใช้ `!important`
