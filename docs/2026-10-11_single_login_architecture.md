# บันทึกการปรับปรุงระบบเข้าสู่ระบบแบบรวมศูนย์หน้าเดียว (Single Login Architecture)

**วันที่บันทึก:** 11 ตุลาคม 2026  
**ระบบ/โมดูล:** การพิสูจน์ตัวตนและการส่งต่อการจอง (Unified Authentication & Booking Transfer Flow)  
**มาตรฐานที่เกี่ยวข้อง:** `docs/PROJECT_HCI_RULES.md` และ `docs/ADDITIONAL_RULES.md`

---

## 1. ทำอะไรบ้าง (What Was Done)

### รายการไฟล์ที่สร้าง แก้ไข และลบ
1. **ลบไฟล์ AJAX ชั่วคราว (Remove Redundant Endpoints):**
   - ลบ `actions/login_ajax.php` และ `actions/register_ajax.php` เพื่อยุติการมีระบบล็อกอิน 2 ชุดที่ซ้ำซ้อนกัน และบังคับให้การเข้าสู่ระบบและสมัครสมาชิกทั้งหมดผ่าน [`login.php`](file:///c:/xampp/htdocs/ts-pattani/login.php) และ [`register.php`](file:///c:/xampp/htdocs/ts-pattani/register.php) ตามหลัก DRY (Don't Repeat Yourself)
2. **แก้ไข [`booking.php`](file:///c:/xampp/htdocs/ts-pattani/booking.php):**
   - ลบ Modal Dialog (`#bookingAuthModal`) และเลเยอร์ Backdrop ทั้งหมดออกจากหน้าเว็บ
   - อัปเดตการทำงานของกล่องแจ้งเตือน `.guest-auth-gate-card` และปุ่มยืนยัน `#btnFinalSubmitBooking` ให้เรียกใช้ `submitGuestBooking()` เพื่อส่งต่อข้อมูลฟอร์มไปยังระบบ Session ชั่วคราว
   - เพิ่มตรรกะตรวจสอบ Session `pending_booking` ที่หัวไฟล์ PHP เมื่อสมาชิกเข้าสู่ระบบกลับมา ให้สร้าง Script Data `<script id="pendingBookingData" type="application/json">` พร้อมแสดง Alert ข้อความต้อนรับและแจ้งว่าระบบได้กู้คืนข้อมูลการจองเรียบร้อยแล้ว
3. **แก้ไข [`actions/booking_db.php`](file:///c:/xampp/htdocs/ts-pattani/actions/booking_db.php):**
   - ตรวจสอบ `auth_action` จากฟอร์ม: หากผู้ใช้เลือก "สมัครสมาชิกใหม่" ให้ส่งต่อไปยัง `../register.php?redirect=booking.php` หากเลือก "เข้าสู่ระบบ" ให้ส่งต่อไปยัง `../login.php?redirect=booking.php` พร้อมบันทึก `$_SESSION['pending_booking'] = $_POST;`
4. **แก้ไข [`assets/js/member.js`](file:///c:/xampp/htdocs/ts-pattani/assets/js/member.js):**
   - นำฟังก์ชัน Modal เดิมออก (`openBookingAuthModal`, `closeBookingAuthModal`, `switchBookingAuthTab`, `handleBookingAjaxLogin`, `handleBookingAjaxRegister`, `showAuthAlert`)
   - เพิ่มฟังก์ชัน `submitGuestBooking(authType)` เพื่ออัปเดตสรุปยอดและส่งฟอร์มไปยัง `booking_db.php` พร้อมสถานะ Loading ป้องกันการกดซ้ำ
   - ใน `initBookingWizard()` เพิ่มการตรวจสอบแท็ก `#pendingBookingData` หากพบ ให้เรียกใช้ฟังก์ชัน `restorePendingBooking(pData)` เพื่อกู้คืนวันที่, สนาม, เวลาเริ่มต้น-สิ้นสุด, อุปกรณ์/สินค้าเสริม และพาผู้ใช้ไปยังขั้นตอนที่ 5 (หน้าสรุปและชำระเงิน) ทันที
5. **แก้ไข [`assets/css/booking-wizard.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/booking-wizard.css):**
   - ลบคลาสสไตล์เกี่ยวกับ Modal Pop-up (`.booking-auth-modal-overlay`, `.booking-auth-modal-card`, `.auth-modal-header`, ฯลฯ) เพื่อลดขนาดไฟล์ CSS และรักษาเฉพาะ Responsive Rule ของการ์ดแจ้งเตือนบนมือถือ

---

## 2. ระบบเป็นอย่างไร (How the System Works)

### โฟลว์การทำงานแบบรวมศูนย์ (Unified Flow Diagram)

```
[ผู้ใช้ทั่วไป (Guest) เลือกวัน สนาม เวลา บริการเสริม ใน booking.php]
                        │
                        ▼ (มาถึงขั้นตอนที่ 5)
[การ์ดแจ้งเตือนแสดง: กรุณาเข้าสู่ระบบหรือสมัครสมาชิกก่อนชำระเงิน]
                        │
                        ├── ผู้ใช้กด "เข้าสู่ระบบเพื่อยืนยันและชำระเงิน"
                        │   หรือกด "สมัครสมาชิกใหม่"
                        ▼
           [submitGuestBooking()]
  (บันทึกข้อมูลฟอร์มทั้งหมด: วัน, สนาม, เวลา, สินค้า ส่ง POST)
                        │
                        ▼
             [actions/booking_db.php]
  (ตรวจพบว่ายังไม่ล็อกอิน -> เก็บ $_SESSION['pending_booking'] = $_POST)
                        │
                        ├── เลือกล็อกอิน -> Redirect ไปยัง login.php?redirect=booking.php
                        └── เลือกสมัครใหม่ -> Redirect ไปยัง register.php?redirect=booking.php
                                                │ (เมื่อสมัครเสร็จ)
                                                ▼
                                   [login.php?redirect=booking.php]
                        │
                        ▼
            [เข้าสู่ระบบสำเร็จใน actions/login_db.php]
  (พบว่ามี $_SESSION['pending_booking'] หรือ redirect=booking.php)
                        │
                        ▼ Redirect กลับมายัง
                  [booking.php]
                        │
                        ├── $is_logged_in เป็น true (ชื่อผู้ใช้ขึ้นบน Navbar)
                        ├── PHP ตรวจพบ $_SESSION['pending_booking'] -> ส่งออก JSON
                        ├── JS initBookingWizard() กู้คืนข้อมูลอัตโนมัติ:
                        │     - วันที่และเวลาเดิม
                        │     - สนามและช่วงเวลาเดิม
                        │     - รายการสินค้าและราคาเดิม
                        ├── เปิดแสดงที่ขั้นตอนที่ 5 ทันที พร้อมแสดง Alert สีเขียว:
                        │     "ยินดีต้อนรับกลับมาคุณ [ชื่อ]! ข้อมูลการจองของคุณได้รับการกู้คืนแล้ว"
                        └── ผู้ใช้ตรวจสอบความถูกต้อง แล้วกด "ยืนยันการจองและไปหน้าชำระเงิน"
```

### ข้อดีของสถาปัตยกรรมนี้
1. **Consistency (ความสม่ำเสมอของ UI/UX 100%):** ทั้งระบบมีหน้าเข้าสู่ระบบและสมัครสมาชิกเพียงหน้าเดียว สไตล์เดียวกัน ไม่มีหน้าต่างป๊อปอัปย่อยที่โทนสีหรือฟอร์มไม่เข้าชุดกัน
2. **Data Safety (ข้อมูลการจองไม่สูญหาย):** แม้จะเปลี่ยนหน้าจาก `booking.php` ไปยัง `login.php` หรือ `register.php` ข้อมูลที่เลือกไว้ทั้งหมดจะถูกฝากไว้ใน PHP Session ของเซิร์ฟเวอร์อย่างปลอดภัย เมื่อล็อกอินสำเร็จข้อมูลจะถูกนำกลับมาเรนเดอร์ใน Wizard ขั้นตอนที่ 5 ให้ทันที
3. **DRY & Maintainability:** โค้ดตรวจสอบความปลอดภัยและ Authentication อยู่ที่ `login_db.php` และ `register_db.php` จุดเดียว ง่ายต่อการบำรุงรักษาในอนาคต

---

## 3. การใช้งานอย่างไร (How to Use / Test)

### ขั้นตอนการทดสอบทีละสเต็ป
1. **เข้าสู่หน้าจองสนามในสถานะที่ยังไม่ได้ล็อกอิน:**
   - เปิดเบราว์เซอร์ (หรือโหมดไม่ระบุตัวตน/Incognito) ไปที่ [http://localhost/ts-pattani/booking.php](http://localhost/ts-pattani/booking.php)
2. **ทำรายการจองตามขั้นตอน:**
   - **Step 1:** เลือกวันที่ต้องการเล่น (เช่น วันนี้ หรือวันพรุ่งนี้)
   - **Step 2:** เลือกเวลาเริ่มต้น (เช่น 18:00 น.)
   - **Step 3:** เลือกสนามและชั่วโมง (เช่น คอร์ท 1 จำนวน 2 ชั่วโมง 18:00 - 20:00 น.) และตั้งชื่อก๊วน
   - **Step 4:** เลือกบริการเสริม (เช่น ลูกแบดมินตัน 2 หลอด)
3. **ตรวจสอบขั้นตอนที่ 5 (หน้าสรุปและชำระเงิน):**
   - จะเห็นกล่องแจ้งเตือน **"กรุณาเข้าสู่ระบบหรือสมัครสมาชิกก่อนชำระเงิน"**
   - ปุ่มหลักจะแสดงว่า **"เข้าสู่ระบบเพื่อยืนยันและชำระเงิน"**
4. **คลิกเพื่อเข้าสู่ระบบ:**
   - กดปุ่ม "เข้าสู่ระบบเพื่อยืนยันและชำระเงิน"
   - หน้าเว็บจะนำทางไปยัง `login.php?redirect=booking.php` อย่างเป็นทางการ
   - สังเกตว่าจะพบ Alert สีแดงแจ้งเตือน: *"กรุณาเข้าสู่ระบบหรือสมัครสมาชิกก่อนดำเนินการชำระเงิน"*
5. **เข้าสู่ระบบ:**
   - กรอกเบอร์โทรศัพท์และรหัสผ่านของสมาชิก แล้วกด "เข้าสู่ระบบ"
6. **ผลลัพธ์หลังเข้าสู่ระบบ:**
   - ระบบจะพาผู้ใช้กลับมาที่ `booking.php` ทันที
   - แถบเมนูด้านบนแสดงชื่อและโปรไฟล์สมาชิก
   - ปรากฏข้อความแจ้งเตือนสีเขียว: *"ยินดีต้อนรับกลับมาคุณ [ชื่อสมาชิก]! ข้อมูลการจองที่คุณเลือกไว้ได้รับการกู้คืนเรียบร้อยแล้ว กรุณาตรวจสอบและกดยืนยันเพื่อไปหน้าชำระเงิน"*
   - Wizard กระโดดมาที่ **Step 5** ทันที พร้อมแสดงรายการสนาม เวลา และสินค้าที่เลือกไว้ครบถ้วน
   - ปุ่มเปลี่ยนเป็นสีเขียว: **"ยืนยันการจองและไปหน้าชำระเงิน"**
   - เมื่อกดปุ่ม ระบบจะสร้าง Record ในฐานข้อมูล ล็อกสนาม 15 นาที และพาไปหน้าชำระเงินพร้อมแสดง QR Code ทันที

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)

1. **ปัญหาความไม่เข้ากันของหน้าตา UI (Inconsistent Styles):**
   - *สาเหตุ:* การมีทั้งหน้าเพจ `login.php` และ Modal Popup ใน `booking.php` ทำให้มีฟอร์มล็อกอิน 2 แบบที่หน้าตาไม่เหมือนกัน และต้องคอยอัปเดตโค้ดสไตล์ทั้ง 2 จุด
   - *วิธีแก้:* ตัด Modal Popup และไฟล์ `actions/login_ajax.php` ทิ้งทั้งหมด ยึดหน้า `login.php` เป็นศูนย์กลางเพียงหน้าเดียว
2. **ปัญหาข้อมูลหลุดหายเมื่อออกจากหน้า Wizard ไปหน้า Login:**
   - *สาเหตุ:* การเปลี่ยนหน้าตามปกติจะทำให้ State ที่เก็บใน JavaScript ของ Wizard หายไป
   - *วิธีแก้:* ใช้กลไก `$_SESSION['pending_booking']` บันทึกค่าผ่าน Form POST ก่อนที่จะทำการ Redirect ไป `login.php` และเมื่อล็อกอินเสร็จ ระบบจะส่งค่า JSON กลับมาให้ JavaScript ทำการ Re-hydrate (ฟื้นคืน) สเตตของ Wizard กลับมาที่ Step 5 อย่างสมบูรณ์
3. **การจัดการกรณีผู้ใช้เลือกสมัครสมาชิกใหม่:**
   - *สาเหตุ:* ผู้ใช้ที่เป็น Guest อาจยังไม่มีบัญชี จึงกดปุ่ม "สมัครสมาชิกใหม่" แทน "เข้าสู่ระบบ"
   - *วิธีแก้:* เพิ่มฟิลด์ `auth_action` ในฟอร์ม หากผู้ใช้กดสมัครสมาชิกใหม่ ระบบจะบันทึก Session การจอง แล้วส่งต่อไปที่ `register.php?redirect=booking.php` เมื่อสมัครเสร็จ ระบบส่งต่อไป `login.php` และส่งต่อกลับมาที่ `booking.php` โดยที่รายการจองยังคงอยู่ครบ
