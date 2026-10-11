# บันทึกการพัฒนาระบบ: ออกแบบและสร้างหน้า Landing Page (T.S. Pattani Badminton)
**วันที่:** 2026-10-11  
**ระบบ:** T.S. Pattani Badminton Booking System  
**หัวข้อ:** ออกแบบและสร้าง Modern Landing Page สอดคล้องตามมาตรฐาน PROJECT_HCI_RULES และ ADDITIONAL_RULES.md (Hero Section, Infinite CSS Brand Marquee, Top Players Podium Leaderboard, Court Highlights & Amenities, How It Works, News, Location & Hours, และ Site Footer)

---

## 1. ทำอะไรบ้าง (What Was Done)

### 1.1 ไฟล์ที่สร้างใหม่ (New Files)
1. [`includes/footer.php`](file:///c:/xampp/htdocs/ts-pattani/includes/footer.php):
   - คอมโพเนนต์ Footer ส่วนกลางสไตล์โมเดิร์น โทนสี Slate Dark (`#0f172a`)
   - ประกอบด้วย ข้อมูลแบรนด์และสโลแกน, โซเชียลมีเดียชิป (Facebook, Line, Phone), เมนูด่วน (Quick Links), เวลาทำการ, ที่อยู่พร้อมปุ่มนำทาง Google Maps, และแถบลิขสิทธิ์

### 1.2 ไฟล์ที่ได้รับการปรับปรุง (Modified Files)
1. [`index.php`](file:///c:/xampp/htdocs/ts-pattani/index.php):
   - ปรับเปลี่ยนหน้าแรกให้เป็น Landing Page เต็มรูปแบบ ประกอบด้วย 8 เซกชันสำคัญ:
     1. **Hero Section:** พาดหัวทรงพลัง จุดเด่นสนาม BWF ปัตตานี, Dual CTA ("จองสนามทันที" ลิงก์ไป `booking.php` และ "ดูจุดเด่นสนาม & วิธีการจอง"), และแถบสถิติ 4 มิติ (10 คอร์ท, LED Anti-Glare, เวลาทำการ, ระบบสะสมแต้ม)
     2. **Brand & Sponsor Marquee:** สไลเดอร์แสดงแบรนด์กีฬาพันธมิตร (Yonex, Victor, Li-Ning, Mizuno, Dunlop, Ashaway, T.S. Pro Shop) ด้วย Pure CSS ไร้รอยต่อ
     3. **Top Players Leaderboard:** ทำเนียบผู้เล่นยอดเยี่ยม Top 3 ในรูปแบบ Podium โอลิมปิกสากล
     4. **Court Highlights & Amenities:** การ์ดจุดเด่น 6 มิติของสนามมาตรฐาน BWF
     5. **How It Works:** อธิบาย 3 ขั้นตอนการจองง่ายๆ พร้อมปุ่ม CTA ทดลองจอง
     6. **News & Announcements:** ข่าวสารและกิจกรรมล่าสุดจากฐานข้อมูล
     7. **Location, Hours & Contact:** ตารางเวลาทำการ ช่องทางการติดต่อ และแผนที่ตั้ง
     8. **Site Footer:** นำเข้า `includes/footer.php`
2. [`assets/css/global.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/global.css):
   - ประกาศชุดตัวแปร Design Tokens `:root` ครอบคลุมสีหลัก (`--brand-primary`, `--brand-accent`), สี Podium (`--gold-color`, `--silver-color`, `--bronze-color`), สีพื้นหลัง, เงา (`--shadow-sm` ถึง `--shadow-xl`), และรัศมีกรอบ (`--radius-sm` ถึง `--radius-full`)
3. [`includes/functions.php`](file:///c:/xampp/htdocs/ts-pattani/includes/functions.php):
   - เพิ่มฟังก์ชัน `get_top_booking_members(PDO $conn, int $limit = 3): array` ดึงข้อมูลผู้ใช้ที่จองสำเร็จสถานะ `'จองแล้ว'` สูงสุด 3 อันดับ พร้อมระบบ Fallback Mock Data เพื่อเติมเต็ม Podium ให้ครบ 3 ตำแหน่งเสมอ
   - เพิ่มฟังก์ชัน `mask_phone_number(?string $phone): string` เพื่อมาสก์เบอร์โทรศัพท์ (เช่น `081-XXX-5678`) ตามมาตรฐาน PDPA
4. [`assets/css/style.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/style.css):
   - เพิ่มคลาสสไตล์ Landing Page ครบทุกเซกชัน โดยยึดหลัก Separation of Concerns ปราศจาก Inline Style (`style="..."`), ปราศจาก `<style>` กลางไฟล์ PHP และปราศจากการใช้ `!important`
   - เพิ่มคีย์เฟรม `@keyframes marqueeScroll` และ `@keyframes crownFloat`
5. [`assets/css/responsive-mobile.css`](file:///c:/xampp/htdocs/ts-pattani/assets/css/responsive-mobile.css):
   - แยกชุดสไตล์สำหรับหน้าจอมือถือ (`@media (max-width: 768px)` และ `@media (max-width: 480px)`)
   - จัดเรียง Podium Leaderboard จากแนวนอนเป็น Vertical Stack Card (อันดับ 1 ทองอยู่บนสุด) ตามด้วยอันดับ 2 และ 3
   - ขยายขนาดปุ่ม Action เป็น Full Width เพื่อรองรับ Touch Target ขนาดไม่ต่ำกว่า 44x44px

---

## 2. ระบบเป็นอย่างไร (How the System Works)

### 2.1 แผนผังโครงสร้างของ Landing Page (Architecture Flow)
```
[index.php (Landing Page)]
   │
   ├──► Navbar (Navigation Bar)
   │
   ├──► 1. Hero Section (Gradient Dark Blue, Badge Tag, Dual CTA, 4 Quick Stats)
   │
   ├──► 2. Brand & Sponsor Marquee (Pure CSS Infinite 30s Loop, Hardware-Accelerated)
   │
   ├──► 3. Top Players Leaderboard (get_top_booking_members() -> Olympic Podium Top 3)
   │        ├─ Desktop: Rank 2 (Silver - Left) | Rank 1 (Gold - Center Lifted) | Rank 3 (Bronze - Right)
   │        └─ Mobile:  Rank 1 (Top) -> Rank 2 -> Rank 3 (Vertical Stack)
   │
   ├──► 4. Court Highlights & Amenities (BWF Flooring, Anti-Glare LED, Ventilation, Showers, Parking, Pro Shop)
   │
   ├──► 5. How It Works (3 Easy Steps: Select Time -> Pick Court & Addons -> Scan & Play)
   │
   ├──► 6. Latest News & Announcements (News cards with image/placeholder & modal link)
   │
   ├──► 7. Location, Hours & Contact (Operating Hours Table, Contact Chips, Google Maps Box)
   │
   └──► 8. Site Footer (Quick Links, Schedule, Address, Social Chips, Copyright)
```

### 2.2 การทำงานของฟีเจอร์หลัก
1. **Infinite CSS Brand Marquee:**
   - ใช้เทคนิค Duplicated Track (ชุดที่ 1 และชุดที่ 2) โดยสั่ง `@keyframes marqueeScroll` ให้เลื่อนจาก `translate3d(0, 0, 0)` ไปยัง `translate3d(-50%, 0, 0)` อย่างต่อเนื่อง
   - เมื่อเมาส์ชี้ (Hover) จะสั่ง `animation-play-state: paused` ให้หยุดนิ่งเพื่อความสะดวกในการดู
   - ขอบซ้าย-ขวามี Masking Gradient ซอฟต์ตาแบบมืออาชีพ
2. **Top Players Podium Leaderboard:**
   - ฟังก์ชันกลางใน `includes/functions.php` ดึงข้อมูลผู้เล่นที่สถานะการจองเป็น `'จองแล้ว'` เรียงตามจำนวนครั้งที่จอง
   - กรณีระบบยังมีประวัติไม่ครบ 3 ท่าน ระบบจะทำ Backfill อัตโนมัติด้วย Fallback Champions เพื่อรักษาความสวยงามและความสมบูรณ์ของแท่น Podium
   - เบอร์โทรศัพท์ถูกมาสก์ 3 ตัวกลางตามหลัก PDPA เสมอ
3. **การแสดงผล Responsive แบบ CSS Order:**
   - บน Desktop: กำหนด `order: 2` ให้อันดับ 1 (อยู่ตรงกลางและยกตัวสูงขึ้นพร้อมมงกุฎลอย) และ `order: 1` ให้อันดับ 2 (อยู่ซ้าย) และ `order: 3` ให้อันดับ 3 (อยู่ขวา)
   - บน Mobile (<= 768px): สลับ `order: 1` ให้อันดับ 1 ขึ้นบนสุดเพื่อให้อ่านง่ายและไม่ต้องกวาดสายตาสลับไปมา

---

## 3. การใช้งานอย่างไร (How to Use / Test)

1. **การเปิดหน้าแรก (Desktop View):**
   - เข้า URL: `http://localhost/ts-pattani/` หรือ `http://localhost/ts-pattani/index.php`
   - **Hero Section:** ตรวจสอบพาดหัว ข้อความบรรยาย และปุ่ม "จองสนามทันที" ให้คลิกแล้วเดินทางไปยัง `booking.php`
   - **Brand Marquee:** สังเกตแถบสปอนเซอร์ (Yonex, Victor, Li-Ning ฯลฯ) เลื่อนจากขวาไปซ้ายอย่างนุ่มนวล ทดสอบนำเมาส์ไปชี้เพื่อดูว่าแถบหยุดนิ่งหรือไม่
   - **Leaderboard Podium:** สังเกตอันดับ 1 สีทองอยู่ตรงกลางพร้อมมงกุฎและกรอบเรืองแสง อันดับ 2 สีเงินอยู่ซ้าย และอันดับ 3 ทองแดงอยู่ขวา พร้อมมาสก์เบอร์โทร
   - **Amenities & How It Works:** ตรวจสอบการจัดวางการ์ด 6 จุดเด่น และ 3 ขั้นตอนการจอง
   - **Location & Footer:** ตรวจสอบเวลาเปิด-ปิด และข้อมูลติดต่อ
2. **การทดสอบบนอุปกรณ์เคลื่อนที่ (Mobile View):**
   - เปิดโหมดจำลองมือถือ (Device Toolbar ใน Chrome DevTools เช่น iPhone 12/14 กว้าง 375px - 390px)
   - สังเกตว่าปุ่มใน Hero ปรับเป็นแนวยาวเต็มจอ สะดวกต่อการใช้นิ้วกด
   - สังเกตแท่น Podium สลับเป็น Stack Card เรียงอันดับ 1 สีทองอยู่บนสุด ตามด้วยอันดับ 2 และ 3
   - ตรวจสอบว่าไม่มีส่วนประกอบใดล้นหน้าจอในแนวนอน (No horizontal overflow)

---

## 4. ปัญหาที่เจอหรือแก้อะไรบ้าง (Issues Encountered & Resolved)

1. **ปัญหาจำนวนผู้เล่นในฐานข้อมูลไม่ครบ 3 อันดับ:**
   - *สาเหตุ:* ฐานข้อมูลจริงเพิ่งมีการจองสำเร็จจากสมาชิกเพียง 2 ท่าน หากเรนเดอร์ Podium 3 แท่น แท่นที่ 3 จะว่างเปล่าหรือเกิด PHP Notice
   - *วิธีแก้:* เขียนฟังก์ชัน `get_top_booking_members()` ใน `includes/functions.php` ให้ตรวจสอบจำนวนแถวที่ได้จาก DB หากน้อยกว่า 3 ให้ทำ Backfill ด้วย Mock Players อัตโนมัติ เพื่อให้หน้าตาของแท่นรับรางวัลครบถ้วนเสมอ
2. **ปัญหาการป้องกันข้อมูลส่วนบุคคลของผู้เล่น (PDPA Protection):**
   - *สาเหตุ:* ตาราง `Member` เก็บเบอร์โทรศัพท์จริง การแสดงเบอร์เต็มบนหน้าแรกอาจละเมิดความเป็นส่วนตัว
   - *วิธีแก้:* เพิ่มฟังก์ชัน `mask_phone_number()` แปลงเบอร์เป็นรูปแบบ `081-XXX-5678` ก่อนแสดงผล
3. **ปัญหาความต่อเนื่องของ Marquee Animation บนมือถือ:**
   - *สาเหตุ:* Marquee ที่คำนวณผ่าน JS มักกระตุกเมื่อเบราว์เซอร์มือถือประมวลผลหนัก
   - *วิธีแก้:* ใช้ Pure CSS Hardware-accelerated `translate3d` และทำชุดข้อมูลซ้ำ 2 ชุดใน Track ทำให้เบราว์เซอร์ Render ผ่าน GPU ลื่นไหล 60 FPS ไร้รอยต่อ
4. **การนำ Mock Players Fallback ออกอย่างเป็นทางการ (Pure Database Only):**
   - *รายละเอียด:* เมื่อระบบมีสมาชิกที่มีประวัติการจองสำเร็จครบ 3 ท่านจริงแล้ว (Farid Cheloh, อานัส เปิ้ล, Mister bin) จึงได้ดำเนินการตัดบล็อกตัวแปร `$mock_players` และการวนลูป Fallback ออกจาก [`includes/functions.php`](file:///c:/xampp/htdocs/ts-pattani/includes/functions.php) ทำให้ระบบทำงานด้วยข้อมูลจริงจากฐานข้อมูล 100% สะอาดและกระชับตามหลัก DRY


