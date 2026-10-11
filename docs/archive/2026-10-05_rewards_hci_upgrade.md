# บันทึกการพัฒนาระบบ: การยกระดับหน้าแลกของรางวัลสู่มาตรฐาน HCI (rewards.php)
**วันที่:** 5 ตุลาคม 2026 (2026-10-05)  
**ระบบ/โมดูล:** Frontend & Member Loyalty Engine (rewards.php, assets/css/style.css, assets/js/member.js, actions/redeem_reward_db.php)  
**มาตรฐานการออกแบบ:** HCI Usability Heuristics, Gamification & Tier Progression, Zero Inline Styles, Error Prevention (Custom Modal)

---

## 1. วัตถุประสงค์และปัญหาเดิม (Background & Problem Statement)
1. **ขาดประวัติการแลกของรางวัล (Lack of Redemption History):**
   - ผู้ใช้แลกของรางวัลแล้วคะแนนถูกหัก แต่ไม่มีประวัติให้ดูว่าเคยแลกรางวัลอะไรไปบ้าง และไม่มีรหัสอ้างอิงสำหรับนำไปแสดงรับของรางวัลที่เคาน์เตอร์สนาม
2. **การสื่อสารสถานะไม่ชัดเจน (Visibility of System Status & Recognition):**
   - ปุ่มกดของรางวัลที่คะแนนไม่พอ ขึ้นข้อความเพียง *"คะแนนไม่พอ"* โดยไม่ระบุว่า **"ขาดอีกกี่คะแนน"** ทำให้ผู้ใช้ขาดเป้าหมายในการสะสมคะแนน
   - สินค้าที่ใกล้หมดสต็อกไม่มีป้ายเตือนความเร่งด่วน (Urgency Badge)
3. **การใช้ Browser `confirm()` (Error Prevention Gap):**
   - การยืนยันแลกรางวัลเดิมใช้ `confirm()` ดั้งเดิมของเบราว์เซอร์ ซึ่งขัดต่อกฎ HCI ข้อ 2.5 และ Section 15
4. **ขาด Empty States & ละเมิด Clean Code:**
   - มีการใช้ inline styles (`style="..."`) และแท็ก `<style>` ใน `rewards.php`

---

## 2. รายละเอียดการดำเนินงานและการเปลี่ยนแปลง (What Was Done)

### 2.1 ปรับปรุงหน้า `rewards.php`
- **Zero Inline Styles 100%:** ถอด inline style ทุกจุด และลบแท็ก `<style>` ออกทั้งหมด
- **Loyalty Hero Card:**
  * แสดงคะแนนสะสมคงเหลือตัวเลขใหญ่ชัดเจน
  * แสดงระดับสมาชิก (Bronze, Silver, Gold) พร้อมเหรียญรางวัล
  * แสดงหลอดระดับความคืบหน้า (Tier Progress Bar) คำนวณตามสูตร Bronze (0-499) -> Silver (500-999) -> Gold (1,000+) พร้อมคำนวณคะแนนที่ต้องสะสมเพิ่มเพื่อเลื่อนระดับ
- **ระบบแท็บ 2 มุมมอง (Tabbed Interface):**
  * **แท็บ 1: ของรางวัลทั้งหมด (Catalog):**
    - การ์ดของรางวัลแสดงภาพ, ชื่อ, คำอธิบาย, จำนวนคงเหลือ
    - ป้ายเตือนความเร่งด่วน: ป้าย `เหลือเพียง X ชิ้น!` (เมื่อสินค้าเหลือ $\le 3$ ชิ้น) หรือ `สินค้าหมด`
    - ปุ่มสถานะอัจฉริยะ 4 รูปแบบ:
      1. `แลกของรางวัล` (คลิกเพื่อเปิด Modal ยืนยัน)
      2. `ขาดอีก XX คะแนน` (คำนวณส่วนต่างคะแนนให้อัตโนมัติเมื่อคะแนนไม่พอ)
      3. `ต้องใช้ระดับ {Tier} ขึ้นไป` (เมื่อระดับสมาชิกไม่ถึงเกณฑ์)
      4. `สินค้าหมดชั่วคราว` (เมื่อสต็อกเป็น 0)
  * **แท็บ 2: ประวัติการแลกของฉัน (My Redemptions):**
    - ดึงข้อมูลจากตาราง `Point_Transaction` ที่มีประเภท 'ใช้'
    - แสดงวันที่ทำรายการ, รหัสอ้างอิงการแลก (เช่น `#RDM-00014`), รายการของรางวัล, คะแนนที่ใช้, และสถานะการรับของที่เคาน์เตอร์
    - **Empty State:** หากยังไม่เคยมีประวัติ จะแสดงไอคอนกล่องของขวัญ พร้อมข้อความเชิญชวนและปุ่มกดเพื่อกลับไปเลือกของรางวัลทันที
- **Redemption Confirmation Modal:**
  * ออกแบบ Custom Modal แทนที่ Browser `confirm()`
  * แสดงพรีวิวของรางวัล, ตารางคำนวณคะแนน (คะแนนปัจจุบัน - คะแนนที่ใช้ = คะแนนคงเหลือสุทธิ)
  * ปุ่มยืนยันมี Loading Spinner State ป้องกันการกดซ้ำ (Double Submit)

### 2.2 ปรับปรุงไฟล์สไตล์ `assets/css/style.css`
- เพิ่มคลาส `.rewards-hero-card`, `.loyalty-progress-container`, `.loyalty-progress-track`, `.loyalty-progress-fill`
- เพิ่มคลาส `.rewards-tab-nav`, `.rewards-tab-btn`, `.rewards-tab-pane`
- เพิ่มคลาส `.rewards-catalog-grid`, `.reward-item-card`, `.badge-urgent-stock`, `.badge-tier-req`, `.btn-redeem-action`
- เพิ่มคลาส `.redeem-modal-overlay`, `.redeem-modal-card`, `.redeem-calc-table`, `.redeem-preview-box`
- เพิ่มคลาส `.redemptions-table`, `.badge-rdm-code`, `.badge-rdm-status`, `.rewards-empty-box`

### 2.3 ปรับปรุงไฟล์สคริปต์ `assets/js/member.js`
- ฟังก์ชัน `switchRewardsTab(targetTab)` สำหรับสลับแท็บแบบนุ่มนวล
- ฟังก์ชัน `openRedeemModal(...)` สำหรับเปิดหน้าต่างยืนยันพร้อมคำนวณแต้มคงเหลือแบบ Real-time
- ฟังก์ชัน `closeRedeemModal()` และการปิด Modal ด้วยการกด ESC หรือคลิกพื้นที่ภายนอก
- ฟังก์ชัน `executeRedeemSubmit()` พร้อมเปลี่ยนปุ่มเป็น Spinner ระหว่างส่งคำขอไปยัง Backend

---

## 3. รายการไฟล์ที่เกี่ยวข้อง (Files Modified)
1. `rewards.php` (Refactored to Tabbed Layout, Tier Progress, Custom Modal, Zero Inline Styles)
2. `assets/css/style.css` (Added complete Rewards Hero, Tabs, Cards, Badges, Table & Modal styles)
3. `assets/js/member.js` (Added Rewards Tab Switcher and Modal Controller)
4. `docs/2026-10-05_rewards_hci_upgrade.md` (Daily Work Documentation)

---

## 4. ผลการทดสอบ (Verification & Test Results)
- ตรวจสอบไวยากรณ์ PHP (`php -l rewards.php`): ผ่าน 100% ไม่มีข้อผิดพลาด
- ตรวจสอบไวยากรณ์ JavaScript (`node -c assets/js/member.js`): ผ่าน 100%
- ตรวจสอบ Inline Style (`Select-String -Pattern style=`): ไม่พบ `style=` ในแท็ก HTML ของ `rewards.php` แม้แต่จุดเดียว
- จำลองการ Render หน้าเว็บผ่านบัญชีสมาชิก:
  * Hero Card แสดงคะแนนและระดับสมาชิกถูกต้อง
  * ระบบแท็บสามารถสลับระหว่างแคตตาล็อกและประวัติการแลกได้อย่างสมบูรณ์
  * ของรางวัลที่คะแนนไม่พอ แสดงปุ่มคำนวณคะแนนที่ขาดได้อย่างถูกต้อง
  * Modal แสดงผลและคำนวณแต้มคงเหลือสุทธิถูกต้อง
