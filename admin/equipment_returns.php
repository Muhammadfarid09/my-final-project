<?php
require_once 'includes/auth_check.php';

$active_tab = $_GET['tab'] ?? 'active';
$search = trim($_GET['search'] ?? '');

try {
    // 1. ดึงสถิติภาพรวม
    $stats_query = "
        SELECT 
            SUM(CASE WHEN rental_status IN ('กำลังเช่า', 'รอตรวจสอบ') THEN rental_quantity ELSE 0 END) AS active_qty,
            COUNT(CASE WHEN rental_status = 'กำลังเช่า' AND rental_return_time < NOW() THEN 1 END) AS overdue_count,
            COUNT(CASE WHEN rental_status IN ('คืนแล้ว', 'ชำรุด', 'สูญหาย') AND DATE(rental_actual_return) = CURDATE() THEN 1 END) AS returned_today,
            COALESCE(SUM(rental_fine), 0) AS total_fines
        FROM Rental
    ";
    $stats = $conn->query($stats_query)->fetch(PDO::FETCH_ASSOC);

    // 2. ดึงรายการตามแท็บ
    $where_clauses = [];
    $params = [];

    if ($active_tab === 'history') {
        $where_clauses[] = "r.rental_status IN ('คืนแล้ว', 'ชำรุด', 'สูญหาย')";
    } else {
        $where_clauses[] = "r.rental_status IN ('กำลังเช่า', 'รอตรวจสอบ')";
    }

    if (!empty($search)) {
        $where_clauses[] = "(m.member_name LIKE :s OR m.member_phone LIKE :s OR p.product_name LIKE :s OR r.booking_id = :sid)";
        $params[':s'] = "%$search%";
        $params[':sid'] = intval($search);
    }

    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

    $sql = "
        SELECT r.*, 
               p.product_name, p.product_image, p.product_price,
               m.member_name, m.member_phone,
               b.booking_date, b.booking_status
        FROM Rental r
        JOIN Product p ON r.product_id = p.product_id
        LEFT JOIN Booking b ON r.booking_id = b.booking_id
        LEFT JOIN Member m ON b.member_id = m.member_id
        $where_sql
        ORDER BY " . ($active_tab === 'history' ? "r.rental_actual_return DESC" : "r.rental_start_time ASC") . "
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการโหลดข้อมูล: " . $e->getMessage();
    $rentals = [];
}

$page_title = 'ตรวจรับคืนอุปกรณ์กีฬา - Admin T.S. Pattani';
$page_header = '<i class="fas fa-undo-alt"></i> ระบบตรวจรับคืนอุปกรณ์กีฬา (Equipment Return)';
include 'includes/header.php';
?>

<!-- การ์ดสรุปสถิติด้านบน -->
<div class="stats-grid mb-25" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
    <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #007bff;">
        <div style="font-size: 13px; color: #6c757d; margin-bottom: 5px;"><i class="fas fa-table-tennis text-primary"></i> อุปกรณ์ที่กำลังเช่า</div>
        <div style="font-size: 26px; font-weight: bold; color: #007bff;"><?php echo number_format($stats['active_qty'] ?? 0); ?> <span style="font-size: 15px; color: #6c757d;">ชิ้น</span></div>
    </div>
    <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #dc3545;">
        <div style="font-size: 13px; color: #6c757d; margin-bottom: 5px;"><i class="fas fa-clock text-danger"></i> เกินกำหนดคืน (Overdue)</div>
        <div style="font-size: 26px; font-weight: bold; color: #dc3545;"><?php echo number_format($stats['overdue_count'] ?? 0); ?> <span style="font-size: 15px; color: #6c757d;">รายการ</span></div>
    </div>
    <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #28a745;">
        <div style="font-size: 13px; color: #6c757d; margin-bottom: 5px;"><i class="fas fa-check-circle text-success"></i> คืนแล้ววันนี้</div>
        <div style="font-size: 26px; font-weight: bold; color: #28a745;"><?php echo number_format($stats['returned_today'] ?? 0); ?> <span style="font-size: 15px; color: #6c757d;">รายการ</span></div>
    </div>
    <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #ffc107;">
        <div style="font-size: 13px; color: #6c757d; margin-bottom: 5px;"><i class="fas fa-coins text-warning"></i> ยอดค่าปรับสะสม</div>
        <div style="font-size: 26px; font-weight: bold; color: #d39e00;"><?php echo number_format($stats['total_fines'] ?? 0, 2); ?> <span style="font-size: 15px; color: #6c757d;">฿</span></div>
    </div>
</div>

<div class="admin-card">
    <div class="header-between mb-20" style="flex-wrap: wrap; gap: 15px;">
        <!-- แท็บสลับหน้า -->
        <div class="page-tabs m-0">
            <a href="equipment_returns.php?tab=active" class="tab-btn <?php echo $active_tab === 'active' ? 'tab-active' : 'tab-inactive'; ?>">
                <i class="fas fa-hourglass-half"></i> รายการที่ต้องตรวจรับคืน (<?php echo $active_tab === 'active' ? count($rentals) : '-'; ?>)
            </a>
            <a href="equipment_returns.php?tab=history" class="tab-btn <?php echo $active_tab === 'history' ? 'tab-active' : 'tab-inactive'; ?>">
                <i class="fas fa-history"></i> ประวัติการรับคืนแล้ว
            </a>
        </div>

        <!-- ช่องค้นหา -->
        <form method="GET" action="equipment_returns.php" style="display: flex; gap: 8px;">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
            <input type="text" name="search" class="form-control" placeholder="ค้นหา รหัสจอง / สมาชิก / อุปกรณ์" value="<?php echo htmlspecialchars($search); ?>" style="width: 250px;">
            <button type="submit" class="btn-submit" style="padding: 8px 15px;"><i class="fas fa-search"></i></button>
            <?php if (!empty($search)): ?>
                <a href="equipment_returns.php?tab=<?php echo $active_tab; ?>" class="btn-cancel" style="padding: 8px 12px; text-decoration: none;"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ตารางข้อมูล -->
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="70">รหัสเช่า</th>
                    <th>อุปกรณ์ที่เช่า</th>
                    <th width="80" class="text-center">จำนวน</th>
                    <th>ผู้เช่า / การจอง</th>
                    <th>เวลาเริ่มเช่า - กำหนดคืน</th>
                    <th class="text-center">สถานะ</th>
                    <?php if ($active_tab === 'history'): ?>
                        <th>เวลาคืนจริง</th>
                        <th class="text-right">ค่าปรับ</th>
                    <?php else: ?>
                        <th class="text-center" width="130">จัดการ</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rentals)): ?>
                    <tr>
                        <td colspan="<?php echo $active_tab === 'history' ? '8' : '7'; ?>" class="text-center py-40 text-muted">
                            <i class="fas fa-inbox fa-3x mb-10 d-block" style="color: #cbd5e1;"></i>
                            <?php echo !empty($search) ? 'ไม่พบข้อมูลที่ตรงกับคำค้นหา' : 'ไม่มีรายการเช่าอุปกรณ์ในขณะนี้'; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rentals as $row): 
                        $is_late = false;
                        $diff_minutes = 0;
                        if (!empty($row['rental_return_time']) && in_array($row['rental_status'], ['กำลังเช่า', 'รอตรวจสอบ'])) {
                            $due_ts = strtotime($row['rental_return_time']);
                            $now_ts = time();
                            if ($now_ts > $due_ts) {
                                $is_late = true;
                                $diff_minutes = round(($now_ts - $due_ts) / 60);
                            }
                        }
                    ?>
                    <tr>
                        <td class="font-bold">#RT<?php echo $row['rental_id']; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <?php if (!empty($row['product_image'])): ?>
                                    <img src="../uploads/products/<?php echo htmlspecialchars($row['product_image']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; background: #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                                        <i class="fas fa-table-tennis"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-bold text-dark"><?php echo htmlspecialchars($row['product_name']); ?></div>
                                    <div style="font-size: 11px; color: #64748b;">ราคาเช่า: <?php echo number_format($row['product_price'], 2); ?> ฿ / ชิ้น</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 13px; font-weight: bold; padding: 4px 10px; border-radius: 12px;">
                                <?php echo $row['rental_quantity']; ?> ชิ้น
                            </span>
                        </td>
                        <td>
                            <div class="font-bold"><?php echo htmlspecialchars($row['member_name'] ?? 'หน้าร้าน / Walk-in'); ?></div>
                            <div style="font-size: 12px; color: #64748b;">
                                <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($row['member_phone'] ?? '-'); ?>
                                <?php if (!empty($row['booking_id'])): ?>
                                    &bull; จอง #BK<?php echo $row['booking_id']; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 13px;">
                                <div><i class="far fa-clock text-primary"></i> <?php echo date('d/m/Y H:i', strtotime($row['rental_start_time'])); ?></div>
                                <div style="<?php echo $is_late ? 'color: #dc3545; font-weight: bold;' : 'color: #64748b;'; ?>">
                                    <i class="fas fa-flag-checkered"></i> <?php echo date('d/m/Y H:i', strtotime($row['rental_return_time'])); ?>
                                    <?php if ($is_late): ?>
                                        <span class="badge badge-danger" style="font-size: 10px; margin-left: 5px;">เลย <?php echo floor($diff_minutes/60); ?> ชม. <?php echo ($diff_minutes%60); ?> นาที</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php 
                                $status = $row['rental_status'];
                                if ($status === 'คืนแล้ว') {
                                    echo '<span class="badge badge-success"><i class="fas fa-check"></i> คืนแล้ว</span>';
                                } elseif ($status === 'ชำระเงินแล้ว' || $status === 'กำลังเช่า') {
                                    if ($is_late) {
                                        echo '<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> เกินกำหนด</span>';
                                    } else {
                                        echo '<span class="badge badge-info"><i class="fas fa-sync-alt"></i> กำลังเช่า</span>';
                                    }
                                } elseif ($status === 'ชำรุด') {
                                    echo '<span class="badge badge-warning"><i class="fas fa-tools"></i> ส่งซ่อม</span>';
                                } elseif ($status === 'สูญหาย') {
                                    echo '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> สูญหาย</span>';
                                } else {
                                    echo '<span class="badge" style="background:#f1f5f9; color:#475569;">' . htmlspecialchars($status) . '</span>';
                                }
                            ?>
                        </td>

                        <?php if ($active_tab === 'history'): ?>
                            <td style="font-size: 13px;">
                                <?php if (!empty($row['rental_actual_return'])): ?>
                                    <i class="far fa-calendar-check text-success"></i> <?php echo date('d/m/Y H:i', strtotime($row['rental_actual_return'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <?php if ($row['rental_fine'] > 0): ?>
                                    <span class="text-danger font-bold"><?php echo number_format($row['rental_fine'], 2); ?> ฿</span>
                                    <?php if (!empty($row['rental_fine_reason'])): ?>
                                        <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($row['rental_fine_reason']); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        <?php else: ?>
                            <td class="text-center">
                                <button type="button" class="btn-submit" style="padding: 6px 14px; font-size: 13px; background: #059669;"
                                        onclick="openReturnModal(
                                            <?php echo $row['rental_id']; ?>,
                                            '<?php echo htmlspecialchars(addslashes($row['product_name'])); ?>',
                                            <?php echo $row['rental_quantity']; ?>,
                                            '<?php echo htmlspecialchars(addslashes($row['member_name'] ?? 'ลูกค้าทั่วไป')); ?>',
                                            '<?php echo date('d/m/Y H:i', strtotime($row['rental_return_time'])); ?>',
                                            <?php echo $is_late ? 'true' : 'false'; ?>,
                                            <?php echo $diff_minutes; ?>,
                                            <?php echo $row['product_price']; ?>
                                        )">
                                    <i class="fas fa-check-circle"></i> ตรวจรับคืน
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal ตรวจรับคืนอุปกรณ์ -->
<div id="returnModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; border-radius: 12px; width: 90%; max-width: 520px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h4 style="margin: 0 0 15px 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-undo-alt text-success"></i> ตรวจรับคืนอุปกรณ์กีฬา
        </h4>

        <form action="actions/equipment_return_db.php" method="POST" id="returnForm">
            <input type="hidden" name="rental_id" id="modal_rental_id">

            <!-- ข้อมูลสรุปรายการเช่า -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 15px; margin-bottom: 18px; font-size: 14px;">
                <div style="margin-bottom: 5px;"><strong>อุปกรณ์:</strong> <span id="modal_item_name" class="text-primary font-bold"></span> (<span id="modal_qty"></span> ชิ้น)</div>
                <div style="margin-bottom: 5px;"><strong>ผู้เช่า:</strong> <span id="modal_member_name"></span></div>
                <div><strong>กำหนดคืน:</strong> <span id="modal_due_time"></span></div>
                <div id="lateAlertBox" style="display: none; margin-top: 8px; background: #fee2e2; color: #991b1b; padding: 6px 10px; border-radius: 6px; font-size: 13px;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>คืนช้ากว่ากำหนด:</strong> <span id="modal_late_text"></span>
                </div>
            </div>

            <!-- เลือกสภาพอุปกรณ์ -->
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="font-bold" style="display: block; margin-bottom: 8px;">สภาพของอุปกรณ์ที่นำมาคืน <span class="text-danger">*</span></label>
                <div style="display: flex; gap: 15px;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="radio" name="condition_status" value="ปกติ" checked onchange="handleConditionChange(this.value)">
                        <span class="badge badge-success" style="font-size: 13px;"><i class="fas fa-check"></i> ปกติ (สมบูรณ์)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="radio" name="condition_status" value="ชำรุด" onchange="handleConditionChange(this.value)">
                        <span class="badge badge-warning" style="font-size: 13px;"><i class="fas fa-tools"></i> ชำรุด (ส่งซ่อม)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="radio" name="condition_status" value="สูญหาย" onchange="handleConditionChange(this.value)">
                        <span class="badge badge-danger" style="font-size: 13px;"><i class="fas fa-times"></i> สูญหาย</span>
                    </label>
                </div>
            </div>

            <!-- สาเหตุการชำรุด (เฉพาะกรณีชำรุด) -->
            <div class="form-group" id="repairCauseGroup" style="display: none; margin-bottom: 15px;">
                <label class="font-bold">สาเหตุ / รายละเอียดความเสียหาย <span class="text-danger">*</span></label>
                <input type="text" name="repair_cause" id="repairCauseInput" class="form-control" placeholder="เช่น เอ็นขาด, ก้านไม้หัก, ลูกแบดแตก">
                <small class="text-muted">*ระบบจะส่งรายการนี้เข้าสู่คิว 'แจ้งซ่อมอุปกรณ์' ให้อัตโนมัติ</small>
            </div>

            <!-- ส่วนค่าปรับ -->
            <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 15px; margin-bottom: 18px;">
                <h5 style="margin: 0 0 10px 0; color: #92400e; font-size: 14px;"><i class="fas fa-coins"></i> การคิดค่าปรับ (ถ้ามี)</h5>
                
                <div class="form-group" style="margin-bottom: 10px;">
                    <label style="font-size: 13px;">จำนวนเงินค่าปรับ (บาท):</label>
                    <input type="number" step="0.01" min="0" name="fine_amount" id="fineAmountInput" class="form-control font-bold" value="0.00" style="color: #b45309; font-size: 16px;">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 13px;">เหตุผล / บันทึกค่าปรับ:</label>
                    <input type="text" name="fine_reason" id="fineReasonInput" class="form-control" placeholder="เช่น คืนช้าเกินกำหนด 1 ชม., ค่าเอ็นไม้แบดขาด">
                </div>
            </div>

            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn-cancel" onclick="closeReturnModal()">ยกเลิก</button>
                <button type="submit" class="btn-submit" style="background: #059669;">
                    <i class="fas fa-check-circle"></i> ยืนยันการรับคืน
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentLateMinutes = 0;
let currentItemPrice = 0;

function openReturnModal(id, name, qty, member, due, isLate, lateMinutes, price) {
    document.getElementById('modal_rental_id').value = id;
    document.getElementById('modal_item_name').textContent = name;
    document.getElementById('modal_qty').textContent = qty;
    document.getElementById('modal_member_name').textContent = member;
    document.getElementById('modal_due_time').textContent = due;
    
    currentLateMinutes = lateMinutes;
    currentItemPrice = price;

    const lateBox = document.getElementById('lateAlertBox');
    const fineInput = document.getElementById('fineAmountInput');
    const reasonInput = document.getElementById('fineReasonInput');

    // รีเซ็ตฟอร์ม
    document.querySelector('input[name="condition_status"][value="ปกติ"]').checked = true;
    document.getElementById('repairCauseGroup').style.display = 'none';

    if (isLate && lateMinutes > 0) {
        lateBox.style.display = 'block';
        const hours = Math.ceil(lateMinutes / 60);
        document.getElementById('modal_late_text').textContent = `${hours} ชั่วโมง (${lateMinutes} นาที)`;
        
        // คำนวณค่าปรับคืนช้าอัตโนมัติ (เช่น ชม. ละ 20 บาท ต่อชิ้น)
        const lateFine = hours * 20 * qty;
        fineInput.value = lateFine.toFixed(2);
        reasonInput.value = `คืนอุปกรณ์ช้ากว่ากำหนด ${hours} ชม.`;
    } else {
        lateBox.style.display = 'none';
        fineInput.value = '0.00';
        reasonInput.value = '';
    }

    const modal = document.getElementById('returnModal');
    modal.style.display = 'flex';
}

function closeReturnModal() {
    document.getElementById('returnModal').style.display = 'none';
}

function handleConditionChange(status) {
    const repairGroup = document.getElementById('repairCauseGroup');
    const repairInput = document.getElementById('repairCauseInput');
    const fineInput = document.getElementById('fineAmountInput');
    const reasonInput = document.getElementById('fineReasonInput');

    if (status === 'ชำรุด') {
        repairGroup.style.display = 'block';
        repairInput.required = true;
        // แนะนำค่าปรับซ่อม (ถ้ายังเป็น 0)
        if (parseFloat(fineInput.value) === 0) {
            fineInput.value = '100.00';
            reasonInput.value = 'อุปกรณ์ชำรุดจากการใช้งาน';
        }
    } else if (status === 'สูญหาย') {
        repairGroup.style.display = 'none';
        repairInput.required = false;
        // คิดค่าปรับเต็มราคาอุปกรณ์
        if (currentItemPrice > 0) {
            fineInput.value = currentItemPrice.toFixed(2);
            reasonInput.value = 'อุปกรณ์สูญหาย คิดค่าชดใช้เต็มจำนวน';
        }
    } else {
        // ปกติ
        repairGroup.style.display = 'none';
        repairInput.required = false;
        if (currentLateMinutes <= 0) {
            fineInput.value = '0.00';
            reasonInput.value = '';
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
