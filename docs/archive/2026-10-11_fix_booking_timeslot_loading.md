# บันทึกการแก้ไขปัญหาหน้าจองสนามค้าง "กำลังโหลดช่วงเวลา..." (Step 2 Booking Wizard)
**วันที่บันทึก:** 11 ตุลาคม 2026  
**ขอบเขตงาน:** แก้ไขปัญหา Step 2 ในหน้าจองสนามแสดงตัวโหลดค้างไม่แสดงช่องเวลา, ปัญหา Timezone UTC Shift ข้ามวัน และปัญหา Race Condition ระหว่างการดึงข้อมูลสล็อตเวลากับการเปลี่ยนสเต็ป

---

## 1. สาเหตุของปัญหา (Root Causes)
1. **Race Condition & Missing Reactive Re-render ใน JavaScript:**
   - เมื่อผู้ใช้กด "ถัดไป: เลือกเวลาเริ่มต้น" เข้าสู่ Step 2 ตัวฟังก์ชัน `goToStep(2)` จะเรียก `renderTimeSlots()`
   - หากคำขอ AJAX (`actions/get_court_matrix.php`) ยังโหลดไม่เสร็จ (`wizardState.matrixData` ยังเป็น `null`) โค้ดจะแสดงข้อความ `<p>กำลังโหลดช่วงเวลา...</p>`
   - แต่เมื่อคำขอ AJAX โหลดเสร็จสิ้นในภายหลัง ฟังก์ชัน `loadCourtMatrixForWizard` ไม่ได้มีการเรียก `renderTimeSlots()` ซ้ำเพื่อวาดสล็อตเวลา ส่งผลให้หน้าจอค้างอยู่ที่ข้อความ "กำลังโหลดช่วงเวลา..." ตลอดไป
2. **ปัญหา UTC Timezone Shift ข้ามคืน (00:00 - 06:59 น.):**
   - การใช้ `new Date().toISOString().split('T')[0]` ทำให้เวลาท้องถิ่นประเทศไทย (UTC+7) หลังเที่ยงคืนถูกแปลงเป็นเวลาสากล UTC ซึ่งถอยหลังไป 7 ชั่วโมง กลายเป็นวันก่อนหน้า ส่งผลให้การระบุวันที่ในระบบไม่ตรงกับวันจริงของเซิร์ฟเวอร์
3. **ตัวแปรเรทราคาเดิมใน API ไม่ได้ถูกประกาศ:**
   - ใน `actions/get_court_matrix.php` มีการส่งค่า `$base_rate`, `$peak_rate`, `$offpeak_rate` ซึ่งหลังจากรวมศูนย์ตรรกะราคา ตัวแปรเหล่านี้ไม่ได้ถูกกำหนดค่าเริ่มต้น ทำให้เกิด Warning ในบางสภาพแวดล้อม
4. **Browser Cache ในไฟล์ JavaScript:**
   - เบราว์เซอร์อาจแคชไฟล์ `member.js` เก่าไว้ ทำให้การทำงานของสเต็ปไม่ตอบสนองต่อการเปลี่ยนแปลง

---

## 2. รายละเอียดการแก้ไข (Changes Made)

### 2.1 ปรับปรุง JavaScript ([assets/js/member.js](file:///c:/xampp/htdocs/ts-pattani/assets/js/member.js))
- เพิ่มฟังก์ชัน `getLocalDateString()` เพื่อคำนวณ `YYYY-MM-DD` จากเวลาท้องถิ่นของเครื่องโดยตรง แทนการใช้ `toISOString()`
- ใน `initBookingWizard()`: ดึงค่าเริ่มต้นของวันที่จากฟิลด์ Hidden Input ของ PHP (`date('Y-m-d')`) โดยตรง เพื่อให้ตรงกับ Timezone `Asia/Bangkok` ของเซิร์ฟเวอร์
- ใน `goToStep(2)`: ตรวจสอบความพร้อมของข้อมูล `wizardState.matrixData` หากยังไม่ตรงกับวันที่เลือก จะสั่งโหลดข้อมูลทันที
- ใน `loadCourtMatrixForWizard(dateStr)`:
  - เพิ่มการตรวจสอบว่าหากผู้ใช้อยู่ที่ขั้นตอนที่ 2 (`wizardState.currentStep === 2`) เมื่อโหลดข้อมูลเสร็จสมบูรณ์ จะสั่งเรียก `renderTimeSlots()` ทันทีโดยอัตโนมัติ
  - เพิ่ม Error Fallback & Retry Button: หากเครือข่ายขัดข้อง จะแสดงปุ่ม "ลองใหม่อีกครั้ง" ให้ผู้ใช้กดโหลดใหม่ได้ทันที ไม่ค้างที่ตัวหมุนรอ
- ใน `renderTimeSlots()`: ตรวจสอบความสอดคล้องระหว่าง `matrixData.date` กับ `selectedDate` อย่างถูกต้อง

### 2.2 ปรับปรุง API หลังบ้าน ([actions/get_court_matrix.php](file:///c:/xampp/htdocs/ts-pattani/actions/get_court_matrix.php))
- ประกาศและกำหนดค่า `$base_rate`, `$peak_rate`, `$offpeak_rate` จากฟิลด์ข้อมูลสนาม เพื่อส่งคืนใน JSON Structure อย่างครบถ้วน ปราศจาก Warning

### 2.3 เพิ่ม Cache-Busting ในหน้าจอ ([booking.php](file:///c:/xampp/htdocs/ts-pattani/booking.php))
- ปรับลิงก์นำเข้าสคริปต์เป็น `assets/js/member.js?v=<?php echo filemtime('assets/js/member.js'); ?>` เพื่อบังคับให้เบราว์เซอร์ดาวน์โหลดสคริปต์เวอร์ชันล่าสุดทันทีโดยไม่ต้องกด Hard Refresh (Ctrl+F5)

---

## 3. ผลการทดสอบ (Verification & Testing)
1. ตรวจสอบไวยากรณ์ผ่าน `php -l` และ `node -c` ทั้ง PHP และ JS ไม่พบข้อผิดพลาด
2. ทดสอบเรียก API `actions/get_court_matrix.php?date=2026-10-11` ส่งผลลัพธ์เป็น JSON สมบูรณ์และรวดเร็ว (HTTP 200 OK)
3. การสลับขั้นตอนระหว่าง Step 1 และ Step 2 แสดงผลสล็อตเวลาเปิด-ปิดทำการ (08:00 - 23:00 น.) ครบถ้วนทันที ไม่ติดค้างตัวโหลด

