# เอกสารบันทึกการทำงานประจำวัน: การแยกและจัดระเบียบ CSS ฝั่งผู้ดูแลระบบ (Admin CSS Refactoring & Separation)
**วันที่:** 8 ตุลาคม 2026 (2026-10-08)  
**โครงการ:** ระบบจัดการสนามแบดมินตัน T.S. Pattani  
**ผู้รับผิดชอบ:** Antigravity (AI Coding Assistant)  

---

## 1. วัตถุประสงค์ (Objective)
เพื่อเตรียมความพร้อมสู่การปรับปรุง UI/UX ตามมาตรฐาน HCI ในฝั่งผู้ดูแลระบบ (Admin) โดยการลดภาระทางเทคนิค (Technical Debt) และปฏิบัติตามหลักการ **Separation of Concerns**:
1. กำจัดบล็อกแท็ก `<style>` ที่ฝังอยู่ในไฟล์ PHP/HTML ฝั่ง Admin ทั้งหมด (100%)
2. ตรวจสอบและกำจัดแอตทริบิวต์ Inline Style (`style="..."` / `style='...'`) ที่กระจัดกระจายอยู่ในหน้า Admin ออกทั้งหมด
3. ย้ายสไตล์ทั้งหมดเข้าสู่ `assets/css/admin.css` และใช้ประโยชน์จาก Utility Classes ใน `assets/css/global.css`
4. แปลง Dynamic inline styles ที่เคยพิมพ์แทรกด้วย PHP (เช่น สภาพการคืนล่าช้า/ค่าปรับ) ให้เป็น Semantic CSS Classes
5. รักษาความเข้ากันได้กับการทำงานของ JavaScript (Modal dialogs, Dropzones, Print Stylesheets) โดยไม่มี UI Regression แม้แต่จุดเดียว

---

## 2. รายการไฟล์ที่ดำเนินการ (Files Modified & Refactored)

### 2.1 ไฟล์สไตล์ชีตส่วนกลาง (Centralized Stylesheets)
1. **[assets/css/admin.css](file:///c:/xampp/htdocs/ts-pattani/assets/css/admin.css)**:
   - เพิ่มโมดูล CSS ครอบคลุมการแสดงผลในหน้า Admin ทุกส่วน รวมกว่า 360 บรรทัด ได้แก่:
     - **Admin Login & Dashboard:** `.admin-login-logo`, `.dashboard-main-grid`, `.dashboard-charts-grid`, `.badge-xs`
     - **Court Pricing Badges:** `.court-rate-pill`, `.rate-standard`, `.rate-peak`, `.rate-special`, `.text-rate-peak`, `.text-rate-special`
     - **Product Image Dropzones:** `.product-img-dropzone`, `.product-img-icon`, `.product-img-preview`
     - **Cancellations & Refund Modal:** `.cancellation-refund-box`, `.cancellation-account-no`, `.modal-refund-card`, `.modal-refund-row`, `.modal-refund-label`, `.modal-refund-value`, `.modal-refund-warn`, `.modal-refund-hint`
     - **Thermal Receipt Printing 80mm:** Scoped `body.receipt-body`, `.receipt-container`, `@media print`, `.receipt-col-*`, `.receipt-summary-text`, `.receipt-*-val`
     - **POS Terminal & Walk-in Modal:** `.pos-court-card-active`, `.pos-court-badge-selected`, `.pos-cart-panel`, `.pos-cart-items-scroll`, `.pos-cart-empty`, `.pos-modal-backdrop`, `.pos-modal-card`, `.pos-modal-court-*`, `.pos-badge-peak`, `.pos-badge-normal`
     - **Equipment Returns & Penalties:** `.stat-card-grid`, `.stat-card-box`, `.stat-card-overdue`, `.stat-card-fine`, `.return-item-cell`, `.return-item-thumb`, `.return-penalty-val`, `.text-late`, `.text-normal`, `.return-modal-backdrop`, `.return-modal-card`, `.modal-fee-box`, `#repairCauseGroup`

### 2.2 ไฟล์ฝั่งผู้ดูแลระบบที่ได้รับการ Refactor (Admin Files Refactored)
1. **[admin/login.php](file:///c:/xampp/htdocs/ts-pattani/admin/login.php)**:
   - ย้าย Inline style ของโลโก้ขนาดและอัตราส่วนไปยัง `.admin-login-logo`
2. **[admin/courts.php](file:///c:/xampp/htdocs/ts-pattani/admin/courts.php)**:
   - แปลง Inline styles แสดงราคาช่วงปกติ/ช่วง Peak/วันหยุด ไปเป็น Semantic Badge Classes (`.court-rate-pill`, `.rate-peak`, `.rate-special`, `.text-rate-peak`, `.text-rate-special`)
3. **[admin/product_add.php](file:///c:/xampp/htdocs/ts-pattani/admin/product_add.php)**:
   - ย้ายสไตล์ขอบประและพื้นที่ลากวางรูปภาพสินค้าไปยัง `.product-img-dropzone`, `.product-img-icon`
4. **[admin/product_edit.php](file:///c:/xampp/htdocs/ts-pattani/admin/product_edit.php)**:
   - ย้ายสไตล์ Dropzone และรูปตัวอย่างสินค้าไปยัง `.product-img-dropzone`, `.product-img-icon`, `.product-img-preview`
5. **[admin/index.php](file:///c:/xampp/htdocs/ts-pattani/admin/index.php)**:
   - กำจัด Inline styles ในการแบ่ง Grid แดชบอร์ด, การแสดงกราฟ Chart, และป้าย Badge ตัวเลขขนาดเล็ก โดยใช้ `.dashboard-main-grid`, `.dashboard-charts-grid`, `.table-responsive`, `.badge-xs`
6. **[admin/cancellations.php](file:///c:/xampp/htdocs/ts-pattani/admin/cancellations.php)**:
   - กำจัด 18 Inline styles ในตารางคำขอยกเลิกและ Modal ตรวจสอบสลิปคืนเงิน ไปเป็นชุดคลาส `.cancellation-refund-box`, `.cancellation-account-no`, `.modal-refund-card` ฯลฯ
7. **[admin/receipt.php](file:///c:/xampp/htdocs/ts-pattani/admin/receipt.php)**:
   - **ลบแท็ก `<style>` ฝังตัวขนาดใหญ่ (111 บรรทัด)** ออกจากตัวไฟล์
   - เชื่อมโยงเข้ากับ `assets/css/admin.css` และเพิ่มคลาส `receipt-body`
   - กำจัด 8 Inline styles ในตารางแจกแจงรายการและยอดรวมใบเสร็จความร้อน 80mm
8. **[admin/pos.php](file:///c:/xampp/htdocs/ts-pattani/admin/pos.php)**:
   - กำจัด 29 Inline styles ในการ์ดคอร์ท, รายการตะกร้าสินค้า, และ Pop-up Modal การจองแบบ Walk-in
9. **[admin/equipment_returns.php](file:///c:/xampp/htdocs/ts-pattani/admin/equipment_returns.php)**:
   - กำจัด 62 Inline styles ในแผงสถิติ (Stat Cards), รายการอุปกรณ์ที่ต้องคืน, ตารางค่าปรับ และ Modal ตรวจสภาพอุปกรณ์คืน
   - แปลง Dynamic Style การคืนเกินกำหนด (`<?= $is_late ? 'color: var(--danger-color); font-weight: 600;' : 'color: var(--text-muted);' ?>`) ไปเป็น Semantic Classes `.text-late` และ `.text-normal`

---

## 3. ผลการตรวจสอบและยืนยันผล (Verification & Quality Assurance)

### 3.1 การตรวจสอบอัตโนมัติด้วย Batch Audit Script
ผลการรันสคริปต์สแกนตรวจสอบไฟล์ทั้งหมด 63 ไฟล์ในโฟลเดอร์ `admin/`:
```
===========================================
Total Admin PHP Files Scanned: 63
Total <style> Blocks Remaining: 0
Total Inline Styles Remaining: 0
VERIFICATION PASSED: 100% of admin files are completely free of embedded & inline styles!
```

### 3.2 การตรวจสอบไวยากรณ์ภาษา PHP (PHP Syntax Linting)
ผ่านการตรวจสอบไวยากรณ์ด้วย `php -l` ทุกไฟล์ครบ 100%:
```
SUCCESS: All 63 PHP files in admin/ passed syntax check with 0 errors!
```

---

## 4. โครงสร้างการรายงานผลการทำงาน 4 ส่วนบังคับ (Mandatory 4-Section Report)

### 4.1 ทำอะไรบ้าง (What Was Done)
1. ดำเนินการค้นหาและรวบรวมบล็อก `<style>` ฝังตัว (111 บรรทัด ใน `admin/receipt.php`) และ Inline Style ทั้งหมด 130 จุด จาก 9 ไฟล์ในโฟลเดอร์ `admin/`
2. สร้างคอมโพเนนต์สไตล์ใหม่ใน `assets/css/admin.css` กว่า 360 บรรทัด เพื่อรองรับระบบ Dashboard, คอร์ท, จัดการสินค้า, รายการคืนอุปกรณ์, ระบบ POS, คำขอยกเลิก และใบเสร็จรับเงินความร้อน
3. ปรับปรุงไฟล์ PHP ทั้ง 9 ไฟล์ในฝั่งแอดมิน โดยเปลี่ยนสไตล์ฝังตัวเป็น Semantic CSS Classes
4. จัดการ Dynamic inline styles ใน `equipment_returns.php` โดยแปลงเป็นคลาส `.text-late` และ `.text-normal`
5. ตรวจสอบความถูกต้องของไวยากรณ์ PHP ทั้ง 63 ไฟล์ในโฟลเดอร์ `admin/` (Passed 0 errors)
6. บันทึกและจัดทำเอกสารประจำวัน `docs/2026-10-08_admin_css_refactoring.md`

### 4.2 ระบบเป็นอย่างไร (How the System Works)
1. **สถาปัตยกรรม CSS ส่วนกลาง:** ระบบฝั่ง Admin โหลด `assets/css/global.css` (สำหรับระบบตัวแปรสี ฟอนต์ และ Utility) และ `assets/css/admin.css` (สำหรับคอมโพเนนต์เฉพาะฝั่งแอดมิน) ผ่าน `admin/includes/header.php`
2. **การแยก Scope ใบเสร็จรับเงิน (Receipt Scoped Styles):** ไฟล์ `receipt.php` ทำงานเดี่ยวๆ โดยกำหนดคลาส `receipt-body` ที่แท็ก `<body>` ทำให้สไตล์สำหรับพิมพ์ใบเสร็จ 80mm ไม่ส่งผลกระทบต่อหน้าอื่นๆ
3. **การจัดการสถานะของ Modal ใน JavaScript:** กล่อง Modal ต่างๆ เช่น `#posWalkInModal`, `#returnModal`, และกลุ่มตัวเลือกเหตุผล `#repairCauseGroup` ถูกตั้งค่าเริ่มต้นเป็น `display: none;` ใน CSS ช่วยป้องกันปัญหา Element ปรากฏขึ้นมาแวบหนึ่งก่อนที่สคริปต์จะทำงาน (Flash of Unstyled Content)

### 4.3 การใช้งานอย่างไร (How to Use / Test)
1. **แดชบอร์ดหลัก (`admin/index.php`):** ตรวจสอบการแสดงผล Grid แดชบอร์ด การ์ดสถิติ ป้ายสถานะ และกราฟแสดงผล
2. **การจัดการสนาม (`admin/courts.php`):** ตรวจสอบป้ายบอกราคาช่วงเวลา Peak และราคาปกติ
3. **การเพิ่ม/แก้ไขสินค้า (`admin/product_add.php`, `admin/product_edit.php`):** ตรวจสอบกล่องลากวางรูปภาพสินค้า (Dropzone) และการพรีวิวรูป
4. **ระบบขายหน้าร้าน POS (`admin/pos.php`):** ตรวจสอบการ์ดสนาม ตะกร้าสินค้า และการกดเปิด Modal จองสนามแบบ Walk-in
5. **ระบบการคืนอุปกรณ์ (`admin/equipment_returns.php`):** ตรวจสอบแผงสถิติ 4 ช่อง ตารางรายการคืน และทดลองกดปุ่มตรวจสอบอุปกรณ์เพื่อเปิด Modal คำนวณค่าปรับ
6. **ระบบการยกเลิกและการคืนเงิน (`admin/cancellations.php`):** ตรวจสอบตารางรายการยกเลิก และ Modal แสดงข้อมูลบัญชีธนาคารสำหรับโอนเงินคืน
7. **พิมพ์ใบเสร็จรับเงิน (`admin/receipt.php?booking_id=XX`):** ตรวจสอบการจัดหน้าตารางใบเสร็จ และทดสอบคำสั่ง Print (Ctrl+P) เพื่อดูผลลัพธ์การพิมพ์ขนาด 80mm

### 4.4 ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)
1. **ปัญหา Scope ของใบเสร็จรับเงินความร้อน (80mm Thermal Receipt):**
   - *สาเหตุ:* `receipt.php` ไม่ได้ใช้โครงสร้าง Header/Sidebar รวมกับหน้าอื่น หากนำสไตล์ไปวางใน `admin.css` โดยไม่ระบุ Scope อาจทำให้หน้าอื่นได้รับผลกระทบจาก `@media print`
   - *วิธีแก้:* ใช้ Selector เจาะจง `body.receipt-body` ใน `admin.css` และเพิ่มคลาส `receipt-body` ใน `admin/receipt.php`
2. **ปัญหา Dynamic Style ในสถานะคืนล่าช้า:**
   - *สาเหตุ:* `equipment_returns.php` มีการแทรก Style สีแดงและความหนาของฟอนต์ด้วย Ternary Operator ของ PHP
   - *วิธีแก้:* แปลงเป็นคลาส `.text-late` (สีแดง) และ `.text-normal` (สีเทา) ตามหลักมาตรฐานสากล
3. **ปัญหาการทำงานร่วมกับ JavaScript (Modal Display States):**
   - *สาเหตุ:* หากลบ `style="display: none;"` ใน HTML โดยไม่ใส่ใน CSS ค่าเริ่มต้น Modal จะแสดงผลค้างไว้บนหน้าจอ
   - *วิธีแก้:* กำหนด `display: none;` ใน `.pos-modal-backdrop`, `.return-modal-backdrop`, และ `#repairCauseGroup` ใน `admin.css` ไว้อย่างชัดเจน
