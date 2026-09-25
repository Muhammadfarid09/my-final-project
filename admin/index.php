<?php
require_once 'includes/auth_check.php';

try {
    // 1. นับจำนวนสมาชิกทั้งหมด
    $stmt = $conn->query("SELECT COUNT(*) FROM Member");
    $total_members = $stmt->fetchColumn() ?: 0;

    // 2. นับรายการจองที่ "รอตรวจสอบ"
    $stmt = $conn->query("SELECT COUNT(*) FROM Booking WHERE booking_status = 'รอตรวจสอบ'");
    $pending_bookings = $stmt->fetchColumn() ?: 0;

    // 3. คำนวณรายได้รวมทั้งหมด
    $stmt = $conn->query("SELECT SUM(revenue_total_amount) FROM Revenue");
    $total_revenue = $stmt->fetchColumn() ?: 0; 

    // 4. นับจำนวนสินค้าที่ "ใกล้หมดสต็อก"
    $stmt = $conn->query("SELECT COUNT(*) FROM Product WHERE product_stock <= product_min_stock AND product_type = 'สินค้าบริโภค'");
    $low_stock_count = $stmt->fetchColumn() ?: 0;

    // 5. ดึงรายการจองล่าสุด 5 รายการ
    $sql_recent = "SELECT b.booking_id, m.member_name, c.court_name, b.booking_date, b.booking_status 
                   FROM Booking b 
                   JOIN Member m ON b.member_id = m.member_id 
                   JOIN Court c ON b.court_id = c.court_id 
                   ORDER BY b.booking_created_at DESC LIMIT 5";
    $recent_bookings = $conn->query($sql_recent)->fetchAll(PDO::FETCH_ASSOC);

    // 6. ดึงรายการสินค้าที่ต้องสั่งซื้อด่วน 5 รายการ
    $sql_low_stock = "SELECT product_name, product_stock, product_min_stock 
                      FROM Product 
                      WHERE product_stock <= product_min_stock AND product_type = 'สินค้าบริโภค'
                      ORDER BY product_stock ASC LIMIT 5";
    $low_stocks = $conn->query($sql_low_stock)->fetchAll(PDO::FETCH_ASSOC);

    // 7. Customer Analytics: ลูกค้าที่ใช้บริการบ่อยสุด (Top 5)
    $sql_top_customers = "SELECT m.member_name, m.member_phone, p.member_level, COUNT(b.booking_id) as booking_count 
                          FROM Member m 
                          LEFT JOIN Point p ON m.member_id = p.member_id
                          JOIN Booking b ON m.member_id = b.member_id 
                          WHERE b.booking_status = 'จองแล้ว' 
                          GROUP BY m.member_id 
                          ORDER BY booking_count DESC LIMIT 5";
    $top_customers = $conn->query($sql_top_customers)->fetchAll(PDO::FETCH_ASSOC);

    // 8. Demographic Analytics: เพศ (นับจากจำนวนการจอง)
    $sql_gender = "SELECT m.member_gender, COUNT(b.booking_id) as booking_count 
                   FROM Booking b 
                   JOIN Member m ON b.member_id = m.member_id 
                   WHERE b.booking_status = 'จองแล้ว' 
                   GROUP BY m.member_gender";
    $gender_data = $conn->query($sql_gender)->fetchAll(PDO::FETCH_ASSOC);

    // 9. Demographic Analytics: อาชีพ (นับจากจำนวนการจอง)
    $sql_occupation = "SELECT IFNULL(m.member_occupation, 'ไม่ได้ระบุ') as occupation, COUNT(b.booking_id) as booking_count 
                       FROM Booking b 
                       JOIN Member m ON b.member_id = m.member_id 
                       WHERE b.booking_status = 'จองแล้ว' 
                       GROUP BY occupation 
                       ORDER BY booking_count DESC";
    $occupation_data = $conn->query($sql_occupation)->fetchAll(PDO::FETCH_ASSOC);

    // ==========================================
    // ส่วนดึงข้อมูลสำหรับสร้างกราฟ (Chart.js)
    // ==========================================
    
    // กราฟวงกลม: สัดส่วนรายได้แยกหมวดหมู่ (ค่าสนาม / ค่าเช่า / ค่าสินค้า)
    $stmt_pie = $conn->query("SELECT SUM(revenue_court_amount) as court, SUM(revenue_rental_amount) as rental, SUM(revenue_product_amount) as product FROM Revenue");
    $pie_data = $stmt_pie->fetch(PDO::FETCH_ASSOC);
    $rev_court = $pie_data['court'] ?: 0;
    $rev_rental = $pie_data['rental'] ?: 0;
    $rev_product = $pie_data['product'] ?: 0;

    // กราฟแท่ง: รายรับย้อนหลัง 7 วัน
    $stmt_bar = $conn->query("SELECT revenue_date, SUM(revenue_total_amount) as daily_total FROM Revenue GROUP BY revenue_date ORDER BY revenue_date DESC LIMIT 7");
    $bar_data_raw = $stmt_bar->fetchAll(PDO::FETCH_ASSOC);
    $bar_data_raw = array_reverse($bar_data_raw); // พลิกกลับให้เรียงจากเก่าไปใหม่เพื่อแสดงบนกราฟ

    $chart_dates = [];
    $chart_totals = [];
    foreach($bar_data_raw as $row) {
        $chart_dates[] = date('d/m', strtotime($row['revenue_date']));
        $chart_totals[] = (float)$row['daily_total'];
    }

    // แปลงข้อมูลสำหรับกราฟเพศ
    $gender_labels = [];
    $gender_counts = [];
    foreach($gender_data as $row) {
        $gender_labels[] = $row['member_gender'];
        $gender_counts[] = $row['booking_count'];
    }

    // แปลงข้อมูลสำหรับกราฟอาชีพ
    $occupation_labels = [];
    $occupation_counts = [];
    foreach($occupation_data as $row) {
        $occupation_labels[] = $row['occupation'] === '' ? 'ไม่ได้ระบุ' : $row['occupation'];
        $occupation_counts[] = $row['booking_count'];
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}
?>
<?php
$page_title = 'แดชบอร์ด - Admin T.S. Pattani';
$extra_head = <<<HTML
<!-- นำเข้า Chart.js ตามขอบเขตเครื่องมือพัฒนา -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
HTML;
$page_header = '<div class="header-between">
                    <span><i class="fas fa-tachometer-alt"></i> แดชบอร์ดภาพรวมระบบ</span>
                    <div>
                        <a href="actions/export_csv.php?type=bookings" class="btn-secondary btn-action-small text-decor-none"><i class="fas fa-file-csv"></i> Export การจอง</a>
                        <a href="actions/export_csv.php?type=members" class="btn-secondary btn-action-small text-decor-none"><i class="fas fa-file-csv"></i> Export สมาชิก</a>
                        <a href="actions/export_csv.php?type=revenue" class="btn-success btn-action-small text-decor-none"><i class="fas fa-file-excel"></i> Export รายได้</a>
                    </div>
                </div>';
include 'includes/header.php';
?>
<!-- กล่องสรุปสถิติ 4 กล่อง -->
<div class="dashboard-grid">
    
    <div class="stat-card warning">
        <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
        <div class="stat-info">
            <p>รอตรวจสอบสลิป</p>
            <h3><?php echo number_format($pending_bookings); ?> <span class="stat-unit">รายการ</span></h3>
        </div>
    </div>

    <div class="stat-card success">
        <div class="stat-icon revenue"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="stat-info">
            <p>รายได้รวมระบบ</p>
            <h3><?php echo number_format($total_revenue, 2); ?> <span class="stat-unit">฿</span></h3>
        </div>
    </div>

    <div class="stat-card <?php echo $low_stock_count > 0 ? 'danger' : 'success'; ?>">
        <div class="stat-icon">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="stat-info">
            <p>สินค้าใกล้หมดสต็อก</p>
            <h3><?php echo number_format($low_stock_count); ?> <span class="stat-unit">รายการ</span></h3>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <p>สมาชิกรวม</p>
            <h3><?php echo number_format($total_members); ?> <span class="stat-unit">คน</span></h3>
        </div>
    </div>

</div>

<!-- กราฟสถิติ (Chart.js) -->
<div class="chart-grid">
    <!-- กราฟแท่ง (Bar Chart) -->
    <div class="chart-card">
        <h4 class="section-header"><i class="fas fa-chart-bar"></i> รายรับย้อนหลัง 7 วัน</h4>
        <div class="chart-wrapper">
            <canvas id="revenueBarChart" 
                    data-labels='<?php echo json_encode($chart_dates); ?>' 
                    data-values='<?php echo json_encode($chart_totals); ?>'>
            </canvas>
        </div>
    </div>

    <!-- กราฟวงกลมโดนัท (Doughnut Chart) -->
    <div class="chart-card">
        <h4 class="section-header"><i class="fas fa-chart-pie"></i> สัดส่วนรายได้แยกหมวดหมู่</h4>
        <div class="chart-wrapper">
            <canvas id="revenuePieChart"
                    data-court="<?php echo $rev_court; ?>"
                    data-rental="<?php echo $rev_rental; ?>"
                    data-product="<?php echo $rev_product; ?>">
            </canvas>
        </div>
    </div>
</div>

<!-- แบ่งตารางเป็น 2 ฝั่ง ซ้าย/ขวา -->
<div class="detail-grid">
    
    <!-- ฝั่งซ้าย: ตารางรายการจองล่าสุด -->
    <div class="admin-card">
        <h4 class="section-header"><i class="fas fa-calendar-check text-primary"></i> รายการจองสนามล่าสุด</h4>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ผู้จอง</th>
                    <th>สนาม</th>
                    <th>วันที่จอง</th>
                    <th class="text-center">สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recent_bookings) > 0): ?>
                    <?php foreach ($recent_bookings as $row): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($row['member_name']); ?></strong><br>
                            <span class="text-small-muted">#BK-<?php echo $row['booking_id']; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($row['court_name']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['booking_date'])); ?></td>
                        <td class="text-center">
                            <?php if ($row['booking_status'] == 'รอตรวจสอบ'): ?>
                                <span class="badge badge-warning">รอตรวจสอบ</span>
                            <?php elseif ($row['booking_status'] == 'จองแล้ว'): ?>
                                <span class="badge badge-success">จองแล้ว</span>
                            <?php else: ?>
                                <span class="badge badge-danger">ยกเลิก</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="table-empty-state">ยังไม่มีรายการจองสนาม</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ฝั่งขวา: แจ้งเตือนสินค้าใกล้หมด -->
    <div class="admin-card">
        <div class="header-between">
            <h4 class="section-header text-danger"><i class="fas fa-exclamation-triangle"></i> แจ้งเตือนสินค้าใกล้หมด</h4>
            <a href="purchases.php" class="btn-sm-info text-decor-none">
                <i class="fas fa-plus"></i> สั่งซื้อเพิ่ม
            </a>
        </div>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>รายการสินค้า</th>
                    <th class="text-center">คงเหลือ</th>
                    <th class="text-center">จุดสั่งซื้อขั้นต่ำ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($low_stock_count > 0): ?>
                    <?php foreach ($low_stocks as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                        <td class="text-center">
                            <strong class="text-danger"><?php echo $item['product_stock']; ?></strong>
                        </td>
                        <td class="text-center text-small-muted">
                            <?php echo $item['product_min_stock']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="table-empty-state">สต็อกสินค้าเพียงพอทุกรายการ</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ฝั่งขวา (เพิ่มเติม): ลูกค้าที่ใช้บริการบ่อยสุด -->
    <div class="admin-card">
        <h4 class="section-header text-success"><i class="fas fa-medal"></i> ลูกค้าประจำยอดฮิต (Top 5)</h4>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ชื่อลูกค้า</th>
                    <th>ระดับสมาชิก</th>
                    <th class="text-center">จำนวนครั้งที่จอง</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($top_customers) > 0): ?>
                    <?php foreach ($top_customers as $cust): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($cust['member_name']); ?></strong><br>
                            <span class="text-small-muted"><?php echo htmlspecialchars($cust['member_phone']); ?></span>
                        </td>
                        <td>
                            <?php if ($cust['member_level'] == 'Gold'): ?>
                                <span class="badge-gold">Gold</span>
                            <?php elseif ($cust['member_level'] == 'Silver'): ?>
                                <span class="badge-silver">Silver</span>
                            <?php else: ?>
                                <span class="badge-bronze">Bronze</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <strong class="text-success"><?php echo $cust['booking_count']; ?></strong> <span class="text-small-muted">ครั้ง</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="table-empty-state">ยังไม่มีข้อมูลการจอง</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- กราฟประชากรศาสตร์ (Demographic Analytics) -->
<div class="chart-grid mt-20">
    <!-- กราฟวงกลม: สัดส่วนเพศ -->
    <div class="chart-card">
        <h4 class="section-header"><i class="fas fa-venus-mars"></i> สัดส่วนการจองแบ่งตามเพศ</h4>
        <div class="chart-wrapper">
            <canvas id="genderPieChart"
                    data-labels='<?php echo json_encode($gender_labels); ?>'
                    data-values='<?php echo json_encode($gender_counts); ?>'>
            </canvas>
        </div>
    </div>

    <!-- กราฟแท่งแนวนอน: สัดส่วนอาชีพ -->
    <div class="chart-card">
        <h4 class="section-header"><i class="fas fa-briefcase"></i> สัดส่วนการจองแบ่งตามอาชีพ</h4>
        <div class="chart-wrapper">
            <canvas id="occupationBarChart"
                    data-labels='<?php echo json_encode($occupation_labels); ?>'
                    data-values='<?php echo json_encode($occupation_counts); ?>'>
            </canvas>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>