# Project Development Guidelines & Reporting Standard (TS-Pattani)

ทุกครั้งที่ดำเนินการทำงาน แก้ไขโค้ด พัฒนาระบบ หรือทำขั้นตอนใดๆ เสร็จสิ้น จะต้องรายงานผลสรุปให้ผู้ใช้ทราบอย่างละเอียดตามโครงสร้าง 4 ส่วนนี้เสมอ:

---

### 1. ทำอะไรบ้าง (What Was Done)
* สรุปรายการไฟล์ที่สร้าง เพิ่มเติม หรือแก้ไข
* รายละเอียดการเปลี่ยนแปลงโค้ด, ฐานข้อมูล (DB Schema), หรือไฟล์ Configuration

### 2. ระบบเป็นอย่างไร (How the System Works)
* อธิบายหลักการทำงาน (Mechanism / Logic Flow) ของระบบที่ทำ
* ความสัมพันธ์กับส่วนอื่นๆ ในระบบ (เช่น ตารางฐานข้อมูล, API/Actions, Session, สิทธิ์ผู้ใช้ Member/Admin)
* Diagram หรือโฟลว์การทำงาน (ถ้ามี)

### 3. การใช้งานอย่างไร (How to Use / Test)
* ขั้นตอนการทดสอบและการใช้งานทีละสเต็ป (Step-by-step instructions)
* การเข้าถึงผ่าน URL หรือเมนูทั้งฝั่งผู้ใช้ (Member) และฝั่งผู้ดูแลระบบ (Admin)
* ข้อมูลตัวอย่างสำหรับทดสอบ (Test inputs / Credentials)
* ผลลัพธ์ที่คาดหวังหลังจากทำรายการ (Expected outputs / UI behavior)

### 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)
* สาเหตุของปัญหาเดิมหรือ Bug ที่ตรวจพบ (Root Cause)
* วิธีการที่ใช้แก้ปัญหา (Solution / Workaround)
* ข้อจำกัด หรือข้อควรระวัง (Edge Cases & Notes)
