<?php
/**
 * T.S. Pattani Badminton - Central Site Footer Component
 * แสดงส่วนท้ายของเว็บไซต์ พร้อมข้อมูลการติดต่อและเมนูด่วน
 */
?>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <!-- คอลัมน์ 1: ข้อมูลสนามและสโลแกน -->
            <div class="footer-col footer-col-brand">
                <a href="index.php" class="footer-brand">
                    <i class="fas fa-shuttlecock text-accent mr-8"></i> T.S. Pattani
                </a>
                <p class="footer-desc">
                    สนามแบดมินตันมาตรฐานสากล BWF ใจกลางเมืองปัตตานี พร้อมระบบจองสนามออนไลน์ สะสมแต้มรับของรางวัล และสิ่งอำนวยความสะดวกครบครันเพื่อคนรักสุขภาพ
                </p>
                <div class="footer-social-links">
                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="social-chip" title="Facebook Page">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://line.me" target="_blank" rel="noopener noreferrer" class="social-chip" title="Line Official Account">
                        <i class="fab fa-line"></i>
                    </a>
                    <a href="tel:0812345678" class="social-chip" title="โทรสอบถาม">
                        <i class="fas fa-phone-alt"></i>
                    </a>
                </div>
            </div>

            <!-- คอลัมน์ 2: เมนูลัด (Quick Links) -->
            <div class="footer-col footer-col-links">
                <h4 class="footer-heading">เมนูด่วน</h4>
                <ul class="footer-nav-list">
                    <li><a href="index.php"><i class="fas fa-angle-right"></i> หน้าแรก</a></li>
                    <li><a href="booking.php"><i class="fas fa-angle-right"></i> จองสนามออนไลน์</a></li>
                    <li><a href="promotions.php"><i class="fas fa-angle-right"></i> ข่าวสารและโปรโมชัน</a></li>
                    <li><a href="rewards.php"><i class="fas fa-angle-right"></i> แลกของรางวัล (Points)</a></li>
                    <li><a href="chat.php"><i class="fas fa-angle-right"></i> ติดต่อเจ้าหน้าที่</a></li>
                </ul>
            </div>

            <!-- คอลัมน์ 3: เวลาทำการ (Opening Hours) -->
            <div class="footer-col footer-col-hours">
                <h4 class="footer-heading">เวลาเปิดให้บริการ</h4>
                <div class="footer-hours-block">
                    <div class="hours-row">
                        <span class="days-label">จันทร์ - ศุกร์:</span>
                        <strong class="hours-time">09:00 - 24:00 น.</strong>
                    </div>
                    <div class="hours-row">
                        <span class="days-label">เสาร์ - อาทิตย์:</span>
                        <strong class="hours-time">08:00 - 24:00 น.</strong>
                    </div>
                    <div class="hours-row">
                        <span class="days-label">วันหยุดนักขัตฤกษ์:</span>
                        <strong class="hours-time">เปิดบริการปกติ</strong>
                    </div>
                </div>
            </div>

            <!-- คอลัมน์ 4: ที่ตั้งและการเดินทาง -->
            <div class="footer-col footer-col-contact">
                <h4 class="footer-heading">สถานที่ตั้ง</h4>
                <p class="footer-address">
                    <i class="fas fa-map-marker-alt text-accent mr-8"></i>
                    123 ถนนเจริญประดิษฐ์ ตำบลรูสะมิแล อำเภอเมือง จังหวัดปัตตานี 94000
                </p>
                <div class="footer-contact-actions">
                    <a href="https://maps.google.com" target="_blank" rel="noopener noreferrer" class="btn-footer-map">
                        <i class="fas fa-directions mr-6"></i> นำทางด้วย Google Maps
                    </a>
                </div>
            </div>
        </div>

        <div class="footer-bottom-divider"></div>

        <div class="footer-bottom-bar">
            <p class="copyright-text">
                &copy; <?php echo date('Y'); ?> T.S. Pattani Badminton Club. All rights reserved.
            </p>
            <div class="footer-bottom-meta">
                <span>มาตรฐานสนาม BWF เกรดแข่งขัน</span>
                <span class="dot-separator">&bull;</span>
                <span>ระบบรักษาความปลอดภัย CCTV 24 ชม.</span>
            </div>
        </div>
    </div>
</footer>

