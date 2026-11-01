<?php
require_once 'includes/auth_check.php';

$page_title = 'บันทึกประวัติการทำงานของระบบ (Audit Trail) - Admin T.S. Pattani';
$page_header = '<i class="fas fa-shield-alt"></i> บันทึกประวัติการทำงานของระบบ (Audit Trail & Activity Log)';

// -------------------------------------------------------------
// ตัวกรอง (Filter Parameters)
// -------------------------------------------------------------
$filter_action = trim($_GET['action'] ?? '');
$filter_actor_type = trim($_GET['actor_type'] ?? '');
$filter_keyword = trim($_GET['keyword'] ?? '');
$filter_date_from = trim($_GET['date_from'] ?? '');
$filter_date_to = trim($_GET['date_to'] ?? '');

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

if (!empty($filter_action)) {
    $where_clauses[] = "action = :action";
    $params[':action'] = $filter_action;
}

if (!empty($filter_actor_type)) {
    $where_clauses[] = "actor_type = :actor_type";
    $params[':actor_type'] = $filter_actor_type;
}

if (!empty($filter_keyword)) {
    $where_clauses[] = "(actor_name LIKE :kw OR details LIKE :kw OR ip_address LIKE :kw OR entity_type LIKE :kw)";
    $params[':kw'] = '%' . $filter_keyword . '%';
}

if (!empty($filter_date_from)) {
    $where_clauses[] = "DATE(created_at) >= :d_from";
    $params[':d_from'] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_clauses[] = "DATE(created_at) <= :d_to";
    $params[':d_to'] = $filter_date_to;
}

$where_sql = implode(' AND ', $where_clauses);

try {
    // 1. KPI สรุปภาพรวม
    $stmt_kpi_total = $conn->query("SELECT COUNT(*) FROM Audit_Log");
    $total_logs = intval($stmt_kpi_total->fetchColumn() ?: 0);

    $stmt_kpi_admin = $conn->query("SELECT COUNT(*) FROM Audit_Log WHERE actor_type = 'Admin'");
    $admin_actions_count = intval($stmt_kpi_admin->fetchColumn() ?: 0);

    $stmt_kpi_fin = $conn->query("SELECT COUNT(*) FROM Audit_Log WHERE action IN ('APPROVE_PAYMENT', 'REJECT_PAYMENT', 'POS_CHECKOUT', 'PROCESS_CANCELLATION')");
    $fin_actions_count = intval($stmt_kpi_fin->fetchColumn() ?: 0);

    $stmt_kpi_today = $conn->query("SELECT COUNT(*) FROM Audit_Log WHERE DATE(created_at) = CURDATE()");
    $today_actions_count = intval($stmt_kpi_today->fetchColumn() ?: 0);

    // 2. ดึงรายการประเภท Action สำหรับใส่ Dropdown
    $stmt_actions = $conn->query("SELECT DISTINCT action FROM Audit_Log ORDER BY action ASC");
    $available_actions = $stmt_actions->fetchAll(PDO::FETCH_COLUMN);

    // 3. นับจำนวนรวมตาม Filter สำหรับแบ่งหน้า
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM Audit_Log WHERE $where_sql");
    $stmt_count->execute($params);
    $filtered_total = intval($stmt_count->fetchColumn() ?: 0);
    $total_pages = max(1, ceil($filtered_total / $limit));

    // 4. ดึงข้อมูลจริงตาม Filter และ Pagination
    $sql_logs = "
        SELECT * FROM Audit_Log 
        WHERE $where_sql 
        ORDER BY created_at DESC, log_id DESC 
        LIMIT :limit OFFSET :offset
    ";
    $stmt_logs = $conn->prepare($sql_logs);
    foreach ($params as $k => $v) {
        $stmt_logs->bindValue($k, $v);
    }
    $stmt_logs->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt_logs->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_logs->execute();
    $logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการโหลด Audit Log: " . $e->getMessage();
    $logs = [];
    $filtered_total = 0;
    $total_pages = 1;
}

include 'includes/header.php';
?>

<!-- KPI Stat Cards Overview (Aligned with TS-Pattani System Standards) -->
<div class="dashboard-grid mb-24">
    <div class="stat-card">
        <div class="stat-icon" style="color: #2563EB; background: #EFF6FF;">
            <i class="fas fa-list-alt"></i>
        </div>
        <div class="stat-info">
            <p>ประวัติทั้งหมด</p>
            <h3><?php echo number_format($total_logs); ?> <span class="stat-unit">รายการ</span></h3>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="color: #7C3AED; background: #FAF5FF;">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="stat-info">
            <p>ดำเนินการโดยแอดมิน</p>
            <h3><?php echo number_format($admin_actions_count); ?> <span class="stat-unit">ครั้ง</span></h3>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="color: #059669; background: #ECFDF5;">
            <i class="fas fa-hand-holding-usd"></i>
        </div>
        <div class="stat-info">
            <p>ธุรกรรมการเงิน</p>
            <h3><?php echo number_format($fin_actions_count); ?> <span class="stat-unit">รายการ</span></h3>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="color: #D97706; background: #FFFBEB;">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-info">
            <p>กิจกรรมวันนี้</p>
            <h3><?php echo number_format($today_actions_count); ?> <span class="stat-unit">รายการ</span></h3>
        </div>
    </div>
</div>

<div class="admin-card">
    <!-- Header with Title & Export Button -->
    <div class="header-between mb-20">
        <div>
            <h4 class="m-0 text-dark-custom" style="font-size: 17.5px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-history text-gold"></i> ตรวจสอบประวัติกิจกรรมระบบ (Audit Log Trail)
            </h4>
            <p class="text-muted font-13 mb-0 mt-4">
                บันทึกความเปลี่ยนแปลงทุกขั้นตอนเพื่อความโปร่งใส ตรวจสอบย้อนหลังได้ (พบทั้งหมด <?php echo number_format($filtered_total); ?> รายการ)
            </p>
        </div>
        <div>
            <a href="actions/export_csv.php?type=audit_logs" class="btn-export text-decor-none">
                <i class="fas fa-file-csv icon-bookings"></i> ส่งออก Audit CSV
            </a>
        </div>
    </div>

    <!-- Structured 2-Tier Filter Panel -->
    <form method="GET" action="audit_logs.php" class="audit-filter-panel mb-24">
        <!-- Row 1: Search Keyword & Core Dropdowns -->
        <div class="filter-tier-primary">
            <div class="filter-field-search">
                <label for="filter_keyword"><i class="fas fa-search text-muted mr-4"></i> ค้นหาคำสำคัญ</label>
                <div class="filter-input-wrap">
                    <i class="fas fa-search filter-prefix-icon"></i>
                    <input type="text" id="filter_keyword" name="keyword" class="filter-input-ctrl" 
                           placeholder="ค้นหาชื่อผู้กระทำ, IP Address, หรือรายละเอียดข้อความ..." 
                           value="<?php echo htmlspecialchars($filter_keyword); ?>">
                </div>
            </div>

            <div class="filter-field-select">
                <label for="filter_action"><i class="fas fa-filter text-muted mr-4"></i> ประเภทกิจกรรม</label>
                <select id="filter_action" name="action" class="filter-input-ctrl" onchange="this.form.submit()">
                    <option value="">-- กิจกรรมทั้งหมด --</option>
                    <?php foreach ($available_actions as $act): ?>
                        <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $filter_action === $act ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($act); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field-select">
                <label for="filter_actor_type"><i class="fas fa-user-tag text-muted mr-4"></i> ผู้กระทำ (Actor)</label>
                <select id="filter_actor_type" name="actor_type" class="filter-input-ctrl" onchange="this.form.submit()">
                    <option value="">-- ผู้กระทำทั้งหมด --</option>
                    <option value="Admin" <?php echo $filter_actor_type === 'Admin' ? 'selected' : ''; ?>>แอดมิน (Admin)</option>
                    <option value="Member" <?php echo $filter_actor_type === 'Member' ? 'selected' : ''; ?>>สมาชิก (Member)</option>
                    <option value="System" <?php echo $filter_actor_type === 'System' ? 'selected' : ''; ?>>ระบบอัตโนมัติ (System)</option>
                </select>
            </div>
        </div>

        <!-- Row 2: Date Range Pickers & Action Buttons -->
        <div class="filter-tier-secondary">
            <div class="filter-date-range">
                <div class="filter-date-item">
                    <label for="filter_date_from"><i class="fas fa-calendar-alt text-muted mr-4"></i> ตั้งแต่วันที่</label>
                    <input type="date" id="filter_date_from" name="date_from" class="filter-input-ctrl" 
                           value="<?php echo htmlspecialchars($filter_date_from); ?>">
                </div>
                <div class="filter-date-separator">
                    <span>ถึง</span>
                </div>
                <div class="filter-date-item">
                    <label for="filter_date_to"><i class="fas fa-calendar-alt text-muted mr-4"></i> ถึงวันที่</label>
                    <input type="date" id="filter_date_to" name="date_to" class="filter-input-ctrl" 
                           value="<?php echo htmlspecialchars($filter_date_to); ?>">
                </div>
            </div>

            <div class="filter-action-group">
                <button type="submit" class="btn-filter-apply">
                    <i class="fas fa-search mr-6"></i> กรองข้อมูล
                </button>
                <?php if (!empty($filter_action) || !empty($filter_actor_type) || !empty($filter_keyword) || !empty($filter_date_from) || !empty($filter_date_to)): ?>
                    <a href="audit_logs.php" class="btn-filter-clear" title="ล้างตัวกรองทั้งหมด">
                        <i class="fas fa-redo-alt mr-6"></i> ล้างค่า
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Audit Logs Table -->
    <div class="table-responsive">
        <table class="admin-table table-audit-logs">
            <thead>
                <tr>
                    <th width="70" class="text-center">#ID</th>
                    <th width="140">วันและเวลา</th>
                    <th width="170">ผู้ดำเนินการ (Actor)</th>
                    <th width="180">กิจกรรม (Action)</th>
                    <th width="140">เป้าหมาย (Entity)</th>
                    <th>รายละเอียด (Details)</th>
                    <th width="150">ที่อยู่ IP & เครื่อง</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-40">
                            <div class="table-empty-container">
                                <div class="empty-icon-circle">
                                    <i class="fas fa-search-minus"></i>
                                </div>
                                <h5 class="empty-title">ไม่พบรายการประวัติการทำงานตามเงื่อนไขที่เลือก</h5>
                                <p class="empty-desc">ไม่มีข้อมูลที่ตรงกับตัวกรองที่คุณกำหนด ลองปรับเปลี่ยนเงื่อนไขหรือคลิกล้างตัวกรอง</p>
                                <a href="audit_logs.php" class="btn-empty-reset">
                                    <i class="fas fa-redo-alt mr-6"></i> ล้างตัวกรองทั้งหมด
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            // สีและ Badge ตามประเภท Action
                            $badge_class = 'badge-secondary';
                            $action_icon = 'fas fa-info-circle';
                            $act = $log['action'];

                            if (strpos($act, 'APPROVE') !== false || strpos($act, 'SUCCESS') !== false) {
                                $badge_class = 'badge-success';
                                $action_icon = 'fas fa-check-circle';
                            } elseif (strpos($act, 'REJECT') !== false || strpos($act, 'DELETE') !== false || strpos($act, 'CANCEL') !== false) {
                                $badge_class = 'badge-danger';
                                $action_icon = 'fas fa-times-circle';
                            } elseif (strpos($act, 'POS') !== false || strpos($act, 'PAYMENT') !== false) {
                                $badge_class = 'badge-info';
                                $action_icon = 'fas fa-cash-register';
                            } elseif (strpos($act, 'UPDATE') !== false || strpos($act, 'EDIT') !== false || strpos($act, 'POINT') !== false) {
                                $badge_class = 'badge-warning';
                                $action_icon = 'fas fa-edit';
                            } elseif (strpos($act, 'LOGIN') !== false) {
                                $badge_class = 'badge-primary';
                                $action_icon = 'fas fa-sign-in-alt';
                            }

                            // Actor Badge
                            $actor_badge = 'badge-outline';
                            if ($log['actor_type'] === 'Admin') $actor_badge = 'badge-gold';
                            elseif ($log['actor_type'] === 'Member') $actor_badge = 'badge-info';
                        ?>
                        <tr>
                            <td class="text-center"><span class="text-small-muted font-weight-600">#<?php echo $log['log_id']; ?></span></td>
                            <td>
                                <div class="font-weight-600 text-dark"><?php echo date('d/m/Y', strtotime($log['created_at'])); ?></div>
                                <small class="text-small-muted"><?php echo date('H:i:s น.', strtotime($log['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-6">
                                    <span class="badge <?php echo $actor_badge; ?> font-11">
                                        <?php echo htmlspecialchars($log['actor_type']); ?>
                                    </span>
                                </div>
                                <div class="font-weight-600 text-dark mt-2">
                                    <?php echo htmlspecialchars($log['actor_name'] ?: 'N/A'); ?>
                                </div>
                                <?php if (!empty($log['actor_id'])): ?>
                                    <small class="text-small-muted">ID: <?php echo $log['actor_id']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $badge_class; ?> d-inline-flex align-items-center gap-4">
                                    <i class="<?php echo $action_icon; ?>"></i> <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="entity-chip">
                                    <i class="fas fa-tag font-11 text-muted mr-4"></i>
                                    <strong><?php echo htmlspecialchars($log['entity_type']); ?></strong>
                                    <?php if (!empty($log['entity_id'])): ?>
                                        <span class="text-muted ml-2">#<?php echo $log['entity_id']; ?></span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                    $raw_details = $log['details'] ?? '';
                                    $is_json = false;
                                    $json_data = json_decode($raw_details, true);
                                    if (is_array($json_data)) {
                                        $is_json = true;
                                    }
                                ?>
                                <div class="audit-details-preview">
                                    <?php if ($is_json): ?>
                                        <div class="json-summary">
                                            <?php foreach (array_slice($json_data, 0, 3) as $k => $v): ?>
                                                <span class="json-pill">
                                                    <strong><?php echo htmlspecialchars($k); ?>:</strong> 
                                                    <?php echo htmlspecialchars(is_array($v) ? json_encode($v) : strval($v)); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" class="btn-view-details" 
                                                data-json="<?php echo htmlspecialchars($raw_details, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-action="<?php echo htmlspecialchars($log['action']); ?>"
                                                data-actor="<?php echo htmlspecialchars($log['actor_name'] ?? ''); ?>"
                                                data-time="<?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>"
                                                onclick="showAuditModal(this)">
                                            <i class="fas fa-expand-alt mr-4"></i> ดูเต็ม
                                        </button>
                                    <?php else: ?>
                                        <span class="text-truncate-2"><?php echo htmlspecialchars($raw_details ?: '-'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="ip-chip" title="IP Address">
                                    <i class="fas fa-network-wired font-11 mr-4 text-muted"></i>
                                    <code><?php echo htmlspecialchars($log['ip_address'] ?: '127.0.0.1'); ?></code>
                                </div>
                                <small class="text-small-muted d-block text-truncate mt-2" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>" style="max-width: 140px;">
                                    <?php 
                                        $ua = $log['user_agent'] ?? '';
                                        if (strpos($ua, 'Chrome') !== false) echo '<i class="fab fa-chrome text-primary"></i> Chrome';
                                        elseif (strpos($ua, 'Firefox') !== false) echo '<i class="fab fa-firefox text-orange"></i> Firefox';
                                        elseif (strpos($ua, 'Safari') !== false) echo '<i class="fab fa-safari text-blue"></i> Safari';
                                        elseif (strpos($ua, 'Edge') !== false) echo '<i class="fab fa-edge text-info"></i> Edge';
                                        else echo '<i class="fas fa-globe text-muted"></i> Web Client';
                                    ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-wrapper mt-24">
            <div class="pagination-info">
                แสดงหน้า <?php echo $page; ?> จากทั้งหมด <?php echo $total_pages; ?> หน้า (รวม <?php echo number_format($filtered_total); ?> รายการ)
            </div>
            <div class="pagination-pages">
                <?php
                    $query_params = $_GET;
                    if ($page > 1) {
                        $query_params['page'] = $page - 1;
                        echo '<a href="?' . http_build_query($query_params) . '" class="page-link">&laquo; ก่อนหน้า</a>';
                    }
                    for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
                        $query_params['page'] = $i;
                        $active = ($i == $page) ? 'page-active' : '';
                        echo '<a href="?' . http_build_query($query_params) . '" class="page-link ' . $active . '">' . $i . '</a>';
                    }
                    if ($page < $total_pages) {
                        $query_params['page'] = $page + 1;
                        echo '<a href="?' . http_build_query($query_params) . '" class="page-link">ถัดไป &raquo;</a>';
                    }
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Detail Script -->
<script>
function showAuditModal(btn) {
    const rawJson = btn.getAttribute('data-json');
    const action = btn.getAttribute('data-action');
    const actor = btn.getAttribute('data-actor');
    const time = btn.getAttribute('data-time');

    let formattedContent = '';
    try {
        const parsed = JSON.parse(rawJson);
        formattedContent = '<pre style="text-align: left; background: #0F172A; color: #38BDF8; padding: 16px; border-radius: 8px; font-size: 13px; max-height: 350px; overflow-y: auto;">' + 
                           JSON.stringify(parsed, null, 4) + '</pre>';
    } catch (e) {
        formattedContent = '<p style="text-align: left; background: #F1F5F9; padding: 14px; border-radius: 8px;">' + rawJson + '</p>';
    }

    Swal.fire({
        title: '<i class="fas fa-shield-alt text-warning"></i> รายละเอียดกิจกรรม: ' + action,
        html: `
            <div style="font-size: 13.5px; margin-bottom: 12px; text-align: left; color: #64748B;">
                <div><strong>ผู้ดำเนินการ:</strong> ${actor}</div>
                <div><strong>เวลาที่บันทึก:</strong> ${time}</div>
            </div>
            ${formattedContent}
        `,
        width: 600,
        confirmButtonText: 'ปิดหน้าต่าง',
        confirmButtonColor: '#0F172A'
    });
}
</script>

<?php include 'includes/footer.php'; ?>
