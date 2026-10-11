# รายงานการปรับปรุงหน้า Landing Page: ปรับปรุงปุ่ม Navbar, Hero Dual Actions และเพิ่ม Live Stats Dashboard

**วันที่:** 11 ตุลาคม 2026  
**ระบบ:** T.S. Pattani Badminton - Landing Page (`index.php`)  
**เป้าหมาย:** ปรับปรุงโครงสร้าง UI/UX และการจัดวางตามหลักสรีรศาสตร์และการออกแบบตามหลัก HCI (อ้างอิงภาพตัวอย่าง Rice AI) ประกอบด้วย:
1. ปรับเปลี่ยนปุ่ม Header Actions ด้านขวาบนของ Navbar: ให้ผู้เยี่ยมชมทั่วไป (Guest) เห็นปุ่ม `สมัครสมาชิก` (Ghost) และ `เข้าสู่ระบบ` (Gold) อย่างชัดเจน
2. ปรับปรุงปุ่ม Hero Dual Action: สลับปุ่มรองเป็น `เข้าสู่ระบบสมาชิก` สำหรับ Guest และ `ประวัติการจองของฉัน` สำหรับ Member ที่ล็อกอินแล้ว
3. เพิ่มแถบแดชบอร์ดสถิติสนาม 4 การ์ด (Stadium Live Stats Dashboard) ในธีม Premium Black & Gold พร้อมรองรับ Responsive ทุกหน้าจอ

---

## 1. สิ่งที่ได้ดำเนินการ (What Was Done)

### 1.1 การปรับปรุงปุ่ม Header Actions บน Navbar (`index.php`)
- **เดิม:** ผู้เยี่ยมชมทั่วไปเห็นปุ่ม `[เข้าสู่ระบบ]` (Ghost) และ `[จองสนาม]` (Gold)
  - ปัญหา: เกิดความซ้ำซ้อนเนื่องจากเมนูตรงกลางก็มี `จองสนาม` และใน Hero กลางจอก็มีปุ่มยักษ์ `จองสนามทันที` อีกทั้งยังไม่มีทางลัดสำหรับสมัครสมาชิก
- **ใหม่:** 
  - ปุ่มที่ 1: `<a href="register.php" class="btn-nav-ghost"><i class="fas fa-user-plus"></i> <span>สมัครสมาชิก</span></a>`
  - ปุ่มที่ 2: `<a href="login.php" class="btn-nav-gold"><i class="fas fa-sign-in-alt"></i> <span>เข้าสู่ระบบ</span></a>`
  - สมาชิกที่ล็อกอินแล้ว: คงสถานะแสดง User Profile Dropdown (ชื่อ, สถานะระดับ Bronze/Silver/Gold, พอยท์สะสม) พร้อมปุ่ม `จองสนาม` สีทอง

### 1.2 การปรับปรุงปุ่ม Action ใน Hero Section (`index.php`)
- ออกแบบปุ่มคู่แบบ Dynamic รองรับ 2 สถานะการใช้งาน:
  - **สถานะ Guest (ยังไม่ล็อกอิน):**
    - ปุ่มหลัก (Gold): `[📅 จองสนามทันที ->]` (ลิงก์ `booking.php`)
    - ปุ่มรอง (Outline): `[🔑 เข้าสู่ระบบสมาชิก]` (ลิงก์ `login.php`)
  - **สถานะ Member (ล็อกอินแล้ว):**
    - ปุ่มหลัก (Gold): `[📅 จองสนามทันที ->]` (ลิงก์ `booking.php`)
    - ปุ่มรอง (Outline): `[📋 ประวัติการจองของฉัน]` (ลิงก์ `booking_history.php`)

### 1.3 เพิ่มแถบแดชบอร์ดสถิติสนาม 4 การ์ด (Stadium Live Stats Dashboard)
- เพิ่มตรรกะฝั่ง Backend ใน `index.php` ดึงข้อมูลสถิติสนามจริงจากฐานข้อมูล MySQL (ตาราง `court` และ `booking`):
  - `$court_count`: นับจำนวนคอร์ทที่เปิดให้บริการจริง (10 คอร์ท)
  - `$booking_stat_display`: จำนวนแมตช์ที่จองสำเร็จ (500+ ครั้ง)
- สร้างการ์ดสถิติ 4 ใบในคอนเทนเนอร์ `.hero-stats-dashboard`:
  1. **10** - คอร์ทมาตรฐาน BWF (พื้นยางสังเคราะห์ 5 ชั้น · ไฟ LED Anti-Glare)
  2. **500+** - แมตช์ที่จองสำเร็จ (ระบบจองอัตโนมัติเรียลไทม์ · ล็อกคอร์ท 100%)
  3. **100%** - มาตรฐานความพึงพอใจ (สนามอันดับ 1 ที่นักกีฬาไว้วางใจในปัตตานี)
  4. **24 ชม.** - ระบบบริการครบวงจร (จองคิวออนไลน์ 24 ชม. · Pro Shop · ห้องอาบน้ำ)

### 1.4 การปรับแต่งสไตล์ CSS (`assets/css/style.css` & `responsive-mobile.css`)
- สไตล์ Black & Gold:
  - พื้นผิวการ์ด `var(--bg-surface)` ขอบ `var(--border-color)` ขอบมน `var(--radius-lg)`
  - ตัวเลขสถิติขนาดใหญ่ 38px สีทอง `var(--color-gold)` แบบ Tabular Numbers
  - Hover Micro-interactions: ยกการ์ดลอย 4px, ขอบสีทอง, มีเส้นไฟสีทองด้านบน และเรืองแสงหรูหรา
- Responsive Design:
  - Desktop (> 992px): 4 คอลัมน์เรียงแถวเดียว
  - Tablet (577px - 992px): 2x2 Grid (2 คอลัมน์ x 2 แถว)
  - Mobile (<= 576px): 1 คอลัมน์ Stack แนวตั้งพอดีหน้าจอ

---

## 2. รายการไฟล์ที่แก้ไขและเพิ่มใหม่

| ไฟล์ | การเปลี่ยนแปลง |
|---|---|
| `index.php` | เพิ่มตรรกะดึงสถิติ, ปรับปุ่ม Guest Navbar (สมัคร/เข้าสู่ระบบ), ปรับ Hero Dual Actions และใส่ Dashboard 4 Cards |
| `assets/css/style.css` | เพิ่มสไตล์คลาส `.hero-stats-dashboard`, `.stat-dashboard-card`, `.stat-number`, `.stat-badge-icon` |
| `assets/css/responsive-mobile.css` | เพิ่ม Responsive Breakpoints (992px, 768px, 576px) สำหรับ Stats Dashboard |
| `docs/2026-10-11_landing_stats_and_nav_redesign.md` | สร้างเอกสารบันทึกการทำงานประจำวันตามข้อกำหนด GEMINI.md |

---

## 3. การตรวจสอบความสอดคล้องกับกฎระเบียบ (Compliance Verification)

1. **DRY Principle:** ใช้ CSS Design Tokens (`var(--...)`) และฟังก์ชันกลาง
2. **Separation of Concerns:** 
   - ไม่มี Inline Style (`style="..."`) 100%
   - ไม่มีบล็อก `<style>` แทรกกลางไฟล์ PHP
   - ไม่มีคำสั่ง `!important` ในไฟล์ CSS ทั้งหมด
3. **Safety First & Blast Radius:** รักษาความเข้ากันได้ย้อนหลัง Session และ Auth Flow ไม่กระทบส่วนอื่น
4. **HCI Standards:** ตัวเลขสถิติมองเห็นชัดเจน เพิ่ม Social Proof และลดขั้นตอนการเข้าถึงระบบสำหรับสมาชิกและผู้ใช้ใหม่

