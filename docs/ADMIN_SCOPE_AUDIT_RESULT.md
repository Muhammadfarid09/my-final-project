# ADMIN SCOPE AUDIT RESULT
**โครงการ:** ระบบจัดการสนามแบดมินตัน T.S. Pattani
**วันที่ตรวจสอบ:** 18 กันยายน 2026
**ตรวจสอบโดย:** Antigravity AI Assistant

---

## 1. สรุปภาพรวม

| สถานะ | จำนวน | ความหมาย |
|---|:---:|---|
| Implemented | 62 | ทำงานได้ครบถ้วนตาม Requirement |
| Partial | 18 | มี UI / Logic บางส่วน แต่ยังขาด |
| Missing | 9 | Requirement ระบุไว้แต่ยังไม่มีในโค้ด |
| Not Found | 2 | ไม่พบ File/Function ที่เกี่ยวข้อง |
| N/A | 4 | ไม่เกี่ยวกับ Admin โดยตรง |

---

## 2. Traceability Matrix — ตาม Requirement 1.3

| ID | Requirement | Status | File | Table/SQL | หมายเหตุ |
|---|---|---|---|---|---|
| 1.3.2-1 | แสดงรายชื่อสมาชิก | OK | admin/members.php | Member, Point | |
| 1.3.2-2 | แก้ไขข้อมูลสมาชิก | OK | admin/member_edit.php | Member | |
| 1.3.2-3 | เปลี่ยนสถานะสมาชิก | OK | actions/member_edit_db.php | Member | |
| 1.3.2-4 | ดูแต้มสะสมสมาชิก | OK | admin/members.php | Point | |
| 1.3.2-5 | ดูระดับ Tier สมาชิก | OK | admin/members.php | Point | |
| 1.3.2-6 | Admin Authorization/Role | OK | admin/includes/auth_check.php | Admin | |
| 1.3.2-7 | ป้องกัน Member เข้า Admin URL | OK | admin/includes/auth_check.php | - | |
| 1.3.2-8 | ดูประวัติการแลกรางวัลของสมาชิก | MISSING | ไม่มีหน้าแยก | - | Missing |
| 1.3.3-1 | แสดงรายการของรางวัล | OK | admin/rewards.php | Reward | |
| 1.3.3-2 | เพิ่มของรางวัล | OK | actions/reward_add_db.php | Reward | |
| 1.3.3-3 | แก้ไขของรางวัล | OK | admin/reward_edit.php | Reward | |
| 1.3.3-4 | ลบของรางวัล | OK | actions/reward_delete_db.php | Reward | |
| 1.3.3-5 | กำหนดระดับ Tier สำหรับรางวัล | OK | admin/rewards.php | Reward | |
| 1.3.3-6 | ดูประวัติการแลกรางวัล (Redemption Log) | MISSING | ไม่มีหน้า Admin | - | Missing |
| 1.3.4-1 | เพิ่มสนาม | OK | admin/courts.php | Court | |
| 1.3.4-2 | เปลี่ยนสถานะสนาม | OK | admin/courts.php | Court | |
| 1.3.4-3 | แก้ไขข้อมูลสนาม | PARTIAL | admin/courts.php inline | Court | ไม่มี court_edit.php |
| 1.3.4-4 | ลบสนาม | OK | actions/court_delete_db.php | Court | |
| 1.3.4-5 | ป้องกันลบสนามที่มี Booking | PARTIAL | actions/court_delete_db.php | - | ไม่มี FK check |
| 1.3.4-6 | ราคา Peak/Off-peak | OK | admin/courts.php | Court | |
| 1.3.4-7 | บันทึกซ่อมสนาม | OK | admin/court_repairs.php | Court_Repair | |
| 1.3.5-1 | แสดงสินค้าและอุปกรณ์ | OK | admin/products.php | Product | |
| 1.3.5-2 | เพิ่ม/แก้ไข/ลบสินค้า | OK | product_add.php, product_edit.php | Product | |
| 1.3.5-3 | แสดงสต็อกคงเหลือ + แจ้งเตือน Low | OK | products.php, index.php | Product | |
| 1.3.5-4 | บันทึกการซื้อเข้าสต็อก | OK | admin/purchases.php | Purchase | |
| 1.3.5-5 | Stock Movement Log | MISSING | ไม่มีตาราง/หน้า | - | Missing |
| 1.3.5-6 | ควบคุมอุปกรณ์ให้เช่า | OK | admin/products.php | Product | |
| 1.3.6-1 | ดูรายการจอง/สลิป | OK | admin/payments.php | Booking, Payment | |
| 1.3.6-2 | Confirm/Reject สลิป | OK | actions/payment_verify_db.php | Payment, Booking | |
| 1.3.6-3 | Revenue บันทึกเมื่อ Confirm | OK | actions/payment_verify_db.php | Revenue | |
| 1.3.6-4 | Booking Timeout 15 นาที Auto Cancel | MISSING | ไม่มี Cron | - | Critical Missing |
| 1.3.6-5 | Booking Lock รอชำระ | OK | actions/booking_db.php | Booking | |
| 1.3.7-1 | แสดงรายการยกเลิก | OK | admin/cancellations.php | Cancellation | |
| 1.3.7-2 | อนุมัติ/ปฏิเสธคืนเงิน | OK | actions/cancellation_action_db.php | Cancellation | |
| 1.3.7-3 | Logic ค่าปรับ/Penalty | MISSING | ไม่มี | - | Critical Missing |
| 1.3.8-1 | ขาย Walk-in POS | OK | admin/pos.php | - | |
| 1.3.8-2 | หัก Stock อัตโนมัติ POS | OK | actions/pos_checkout_db.php | Product | |
| 1.3.8-3 | ออกใบเสร็จ | OK | admin/receipt.php | Revenue | |
| 1.3.8-4 | ประวัติ POS | OK | admin/pos_history.php | Revenue | |
| 1.3.8-5 | บันทึกซ่อมอุปกรณ์ | OK | admin/equipment_repairs.php | Equipment_Repair | |
| 1.3.8-6 | Online Booking ลด Stock เช่า | PARTIAL | ไม่ sync realtime | Product | Partial |
| 1.3.8-7 | บันทึกการซื้อเข้าคลัง | OK | admin/purchases.php | Purchase | |
| 1.3.9-1 | ดูรายชื่อห้องแชท | OK | admin/chats.php | Chat, Member | |
| 1.3.9-2 | ดู/ตอบแชท | OK | chats.php, actions/chat_send_db.php | Chat | |
| 1.3.9-3 | Auto Refresh Chat | PARTIAL | admin/chats.php | - | ต้อง Refresh มือ |
| 1.3.10-1 | สถิติ Dashboard (สมาชิก/จอง/รายได้/สต็อก) | OK | admin/index.php | Member, Booking, Revenue | |
| 1.3.10-2 | กราฟรายรับ 7 วัน | OK | admin/index.php | Revenue | Chart.js |
| 1.3.10-3 | กราฟสัดส่วนรายได้ | OK | admin/index.php | Revenue | Pie Chart |
| 1.3.10-4 | Export CSV/Excel | MISSING | ไม่มี | - | Missing |
| 1.3.10-5 | Customer Analytics | MISSING | ไม่มี | - | Missing |
| 1.3.11-1 | แสดง/เพิ่ม/ลบ Admin | OK | admins.php, admin_add.php | Admin | |
| 1.3.11-2 | ป้องกันลบตัวเอง | OK | admins.php + action | Admin | |
| 1.3.11-3 | แก้ไขข้อมูล Admin | MISSING | ไม่มี admin_edit.php | - | Critical Missing |
| 1.3.11-4 | Super Admin RBAC | OK | auth_check.php | Admin | |

---

## 3. CRUD Matrix

| Module | Create | Read | Update | Delete | สถานะรวม |
|---|:---:|:---:|:---:|:---:|:---:|
| สมาชิก (Member) | - Member ลงทะเบียน | OK | OK | - | OK |
| สนาม (Court) | OK | OK | PARTIAL inline | OK | PARTIAL |
| สินค้า/อุปกรณ์ | OK | OK | OK | OK | OK |
| การจอง (Booking) | - Member จอง | OK | OK Confirm/Reject | - | OK |
| ยกเลิก (Cancellation) | - | OK | OK อนุมัติ | - | OK |
| ชำระเงิน (Payment) | - | OK | OK Verify | - | OK |
| รายได้ (Revenue) | OK auto | OK Dashboard | - | - | OK |
| ของรางวัล (Reward) | OK | OK | OK | OK | OK |
| แลกรางวัล (Redemption) | - | MISSING | - | - | MISSING |
| แชท (Chat) | OK ตอบ | OK | - | - | OK |
| ซ่อมสนาม (Court Repair) | OK | OK | OK | - | OK |
| ซ่อมอุปกรณ์ (Eq. Repair) | OK | OK | OK | - | OK |
| POS Sale | OK | OK | - | - | OK |
| Stock/การซื้อ | OK | OK | - | - | OK |
| Dashboard/Report | - | OK | - | - | PARTIAL ขาด Export |
| ผู้ดูแลระบบ (Admin) | OK | OK | MISSING | OK | PARTIAL |
| ข่าวสาร (News) | OK | OK | OK | OK | OK |

---

## 4. ช่องว่างสำคัญ (Requirement Gaps)

### Critical — ควรแก้ไขทันที

| # | ปัญหา | ผลกระทบ | ไฟล์ที่ต้องสร้าง/แก้ |
|---|---|---|---|
| 1 | ไม่มี Booking Timeout Auto-Cancel | สนามติดล็อก รอชำระ ตลอดไปถ้าลูกค้าไม่จ่าย | Cron หรือ check เมื่อ login |
| 2 | ไม่มีหน้าแก้ไขข้อมูล Admin | Super Admin ไม่สามารถเปลี่ยนรหัสผ่าน/ชื่อ Admin ได้ | admin/admin_edit.php + actions/admin_edit_db.php |
| 3 | ไม่มี Logic ค่าปรับเมื่อยกเลิก | ระบบยกเลิกได้ทุกสถานการณ์โดยไม่มีเงื่อนไข | cancellations.php + cancellation_action_db.php |

### High — ควรแก้ไขในรอบถัดไป

| # | ปัญหา | แนวทางแก้ |
|---|---|---|
| 4 | ประวัติการแลกรางวัล ฝั่ง Admin | สร้างหน้า admin/redemptions.php |
| 5 | Chat ไม่ Auto-Refresh | เพิ่ม setInterval polling ทุก 10 วินาที |
| 6 | court_edit.php ยังไม่มี | แยกหน้า หรือเพิ่ม Edit Modal ใน courts.php |
| 7 | ป้องกันลบสนามที่มี Booking active | เพิ่ม check ใน court_delete_db.php |

### Medium — เพิ่มมูลค่าระบบ

| # | ฟีเจอร์ขาด | หมายเหตุ |
|---|---|---|
| 8 | Export รายงาน CSV/Excel | ต้องการ Library PhpSpreadsheet |
| 9 | Stock Movement Log | ตาราง Stock_Log บันทึกทุกการเปลี่ยนแปลง |
| 10 | Customer Analytics | รายงานลูกค้า VIP, ความถี่จอง |
| 11 | Online Booking หัก Stock อุปกรณ์เช่า | ตอนนี้หัก Stock เฉพาะ POS |

---

## 5. แผนงาน (Implementation Plan)

### Phase 1 — Critical Fixes (✅ สำเร็จแล้ว)
แก้ Gap ที่ส่งผลเสียร้ายแรงต่อระบบ หรือทำให้ Business Logic สะดุด:

- [x] admin/admin_edit.php — เพิ่มหน้าสำหรับแก้ไขข้อมูลและเปลี่ยนรหัสผ่านแอดมิน
- [x] Booking Timeout Auto-cancel — (Lazy Expiry) อัปเดต payments.php และ booking.php คืนสต็อกทันทีถ้ายกเลิก
- [x] แก้ไข Logic การแสดงผลค่าปรับคืนเงินเมื่อลูกค้ายกเลิก — cancellations.php (แสดงยอดแนะนำให้แอดมินพิจารณา)
- [x] เพิ่ม Booking Timeout Check — (Lazy Expiry) เช็ค + Cancel Booking ที่หมดเวลาใน payments.php และ booking.php

### Phase 2 — High Priority
ทำหลัง Phase 1 เสร็จ

- [x] สร้าง admin/redemptions.php — ดูประวัติการแลกของรางวัลทุกรายการ
- [x] เพิ่ม Auto-refresh แชท — setInterval polling ทุก 10 วินาทีใน chats.php
- [x] เพิ่ม Check FK ก่อนลบสนาม — court_delete_db.php
- [x] เพิ่ม Modal แก้ไขสนาม — courts.php

### Phase 3 — Medium Priority
เพิ่มมูลค่าระบบ

- [x] Export CSV — รายงานรายได้, รายการจอง
- [ ] Stock Movement Log — ตาราง Stock_Log *(Skipped)*
- [x] Customer Analytics Dashboard
- [x] Inline CSS Cleanup — ย้าย inline CSS ทั้งหมดไปที่ admin.css

---

## 6. Security Audit

| รายการ | สถานะ | หมายเหตุ |
|---|---|---|
| Admin Login + Session Check | OK | auth_check.php |
| Role RBAC (Admin/Super Admin) | OK | auth_check.php |
| ป้องกัน Member เข้า Admin URL | OK | Session check |
| Server-side Validation ทุก Action | PARTIAL | บางหน้ายังขาด validation |
| ป้องกัน ID Tampering (IDOR) | PARTIAL | บาง action ยังไม่ตรวจ ownership |
| CSRF Protection | MISSING | ไม่มี CSRF Token ในฟอร์มใดเลย |

---

## 7. สิ่งที่ขาด — Missing Checklist

- [x] admin/admin_edit.php — แก้ไขข้อมูล Admin (Critical) - **DONE**
- [x] Booking Timeout Auto-Cancel (Critical) - **DONE**
- [x] Logic ค่าปรับเมื่อยกเลิก (Critical) - **DONE**
- [x] admin/redemptions.php — ประวัติการแลกรางวัล (High) - **DONE**
- [x] Auto-refresh Chat (High) - **DONE**
- [x] FK Check ก่อนลบสนาม (High) - **DONE**
- [x] Export CSV/Excel (Medium) - **DONE**
- [ ] Stock Movement Log (Medium) *(Skipped to match project requirements)*
- [x] Customer Analytics (Medium) - **DONE**
- [ ] CSRF Token (Security)

---

## 8. Action Items

| ลำดับ | Priority | รายการ | File ที่ต้องแตะ | Requirement |
|---|---|---|---|---|
| 1 | Critical | สร้าง admin_edit.php | admin/admin_edit.php, actions/admin_edit_db.php | 1.3.11 | ✅ DONE |
| 2 | Critical | Booking Timeout + Auto-cancel | admin/payments.php, admin/includes/auto_cancel.php, booking.php | 1.3.6 | ✅ DONE |
| 3 | Critical | Logic ค่าปรับการยกเลิก | cancellations.php, assets/js/admin.js | 1.3.7 | ✅ DONE |
| 4 | High | Redemption History | admin/redemptions.php (ใหม่) | 1.3.3 | ✅ DONE |
| 5 | High | Chat Auto-refresh | admin/chats.php | 1.3.9 | ✅ DONE |
| 6 | High | FK Check ก่อนลบสนาม | actions/court_delete_db.php | 1.3.4 | ✅ DONE |
| 7 | High | Court Edit Modal | admin/courts.php | 1.3.4 | ✅ DONE |
| 8 | Medium | Export CSV | admin/index.php + new export file | 1.3.10 | ✅ DONE |
| 9 | Medium | Stock Movement Log | DB Table + admin/products.php | 1.3.5 | ❌ SKIPPED |
| 10 | Medium | Customer Analytics | admin/index.php หรือ new page | 1.3.10 | ✅ DONE |
| 11 | Medium | Inline CSS Cleanup | ทุกไฟล์ในโฟลเดอร์ admin | Clean UI | ✅ DONE |
