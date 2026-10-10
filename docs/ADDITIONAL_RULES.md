# [SYSTEM RULES & STRICT OPERATIONAL CONSTRAINTS]
ก่อนที่จะทำการอ่าน เขียน หรือแก้ไขโค้ดใดๆ ภายในโปรเจกต์นี้ ต้องปฏิบัติตามกฎเหล็กทั้ง 6 ข้อด้านล่างนี้อย่างเคร่งครัดเด็ดขาด:

### 1. DRY Principle & Code Reuse (ห้ามเขียนซ้ำซ้อน)
- **Pre-Execution Check:** ก่อนสร้างฟังก์ชัน, CSS Class, Helper, หรือ SQL Query ใหม่ ต้องค้นหาในโปรเจกต์ก่อนเสมอว่ามี Logic หรือสไตล์นี้อยู่แล้วหรือไม่
- **Single Source of Truth:** 
  - หากมีฟังก์ชัน/คลาสเดิมอยู่แล้ว ต้องนำกลับมาใช้ซ้ำ (Reuse) ทันที ห้ามสร้างซ้ำซ้อน
  - คำสั่ง SQL ที่ใช้งานบ่อย (เช่น เช็คสถานะสนามว่าง, คำนวณราคา, เช็คสิทธิ์ Admin) ต้องเรียกใช้ผ่านฟังก์ชันกลางเท่านั้น ห้ามเขียน Query สดซ้ำๆ ในหลายไฟล์
  - ค่าคงที่หรือสถานะระบบ (Booking Status) ต้องอ้างอิงจาก Constants ชุดเดียวกันทั้งระบบ

### 2. Strict Separation of Concerns (สถาปัตยกรรม CSS)
- **No Inline Styles:** ห้ามใช้ Attribute `style="..."` ในไฟล์ HTML/PHP เด็ดขาด 
  *(ข้อยกเว้นเพียงหนึ่งเดียว: Dynamic Values ที่ต้องคำนวณสดจาก PHP เช่น style="width: <?= $rate ?>%;")*
- **No Embedded `<style>`:** ห้ามแทรกบล็อก `<style>` กลางไฟล์ PHP โดยเด็ดขาด
- **Strict File Ownership:** ต้องเขียนสไตล์ลงในไฟล์ที่เป็นเจ้าของ Scope โดยตรงเท่านั้น:
  1. `assets/css/global.css`: ตัวแปรสี ฟอนต์ รีเซ็ตพื้นฐาน (CSS Variables & Resets)
  2. `assets/css/style.css`: โครงสร้างหลัก Layout กลาง (Navbar, Footer, Container พื้นฐาน)
  3. `assets/css/booking-wizard.css`: สไตล์เฉพาะระบบขั้นตอนการจอง (Stepper, การ์ดสนาม, สล็อตเวลา, Add-ons, กล่องสรุป)
  4. `assets/css/responsive-mobile.css`: เฉพาะ Media Queries สำหรับหน้าจอมือถือ (<= 768px)
  5. `assets/css/admin.css`: สไตล์เฉพาะระบบจัดการหลังบ้านทั้งหมด
- **Specificity & !important Policy:** ห้ามใช้ `!important` เพื่อแก้ปัญหาการทับซ้อนเด็ดขาด หากสไตล์ไม่แสดงผล ให้แก้ที่ Hierarchy ของ Selector หรือการตั้งชื่อคลาส

### 3. Safety First & Blast Radius Control (ควบคุมขอบเขตผลกระทบ)
- **Zero Structure Mutation:** ห้ามเปลี่ยนชื่อ Class เดิม, ลบ Class เดิม, หรือปรับเปลี่ยนโครงสร้าง Nesting ของแท็ก HTML โดยไม่ได้รับคำสั่งอย่างชัดเจน
- **Atomic Modification:** แก้ไขเฉพาะจุดที่ได้รับมอบหมายเท่านั้น ห้าม Refactor ส่วนอื่นที่ไม่เกี่ยวข้องแถมมาเป็นอันขาด
- **Backward Compatibility:** โค้ดที่เพิ่มเข้ามาใหม่ต้องไม่ไปทำลายการแสดงผลหรือการทำงานของโมดูลอื่นที่มีอยู่เดิม

### 4. JavaScript Safety & Event Integrity (ความปลอดภัยของสคริปต์)
- **Single Binding Policy:** ห้ามผูก Event ซ้ำซ้อน (ห้ามใช้ `onclick="..."` ปะปนกับ `addEventListener`)
- **Double Submission Prevention:** ฟอร์มและปุ่ม Action สำคัญ (เช่น บันทึกการจอง, กดยืนยัน, ชำระเงิน) ต้องมีกลไก Disable ปุ่มทันทีที่กดเพื่อป้องกันการส่ง Request เบิ้ล
- **Scope Isolation:** ห้ามประกาศตัวแปรลงใน Global Scope (`window`) โดยไม่จำเป็น ให้ครอบด้วย Function Scope หรือ Module เสมอ

### 5. Backend Logic & Session Integrity (ความถูกต้องฝั่ง PHP)
- **Unified Auth Check:** การตรวจสอบสิทธิ์การใช้งาน (Session/Auth) ต้อง Include จากโมดูลกลางที่หัวไฟล์เท่านั้น ห้ามเขียนโค้ดเช็ค Session ซ้ำซ้อนทุกหน้า
- **Input Sanitization:** ข้อมูลที่รับผ่าน POST/GET ต้องผ่านการ Clean และ Validate เสมอ

### 6. AI Operational Protocol (ขั้นตอนการตอบและการลงมือของ AI)
- **Plan & Impact Analysis First:** เมื่อได้รับคำสั่งตกแต่ง UI หรือแก้โค้ด ต้องสรุปแผนสั้นๆ พร้อมระบุไฟล์ที่จะแก้ และชี้แจงผลกระทบให้ทราบก่อนลงมือ
- **No Silent Changes:** ห้ามแอบเพิ่มไฟล์ ปรับคอนฟิก หรือลบโค้ดใดๆ โดยไม่รายงานให้ทราบเด็ดขาด

