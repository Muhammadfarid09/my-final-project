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

<!-- KPI Overview Grid -->
<div class="kpi-grid mb-24">
    <div class="kpi-card">
        <div class="kpi-icon kpi-blue">
            <i class="fas fa-list-alt"></i>
        </div>
        <div class="kpi-info">
            <span class="kpi-label">ประวัติทั้งหมด (Total Logs)</span>
            <h3 class="kpi-value"><?php echo number_format($total_logs); ?> <small>รายการ</small></h3>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon kpi-purple">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="kpi-info">
            <span class="kpi-label">การกระทำโดยแอดมิน (Admin Actions)</span>
            <h3 class="kpi-value"><?php echo number_format($admin_actions_count); ?> <small>ครั้ง</small></h3>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon kpi-green">
            <i class="fas fa-hand-holding-usd"></i>
        </div>
        <div class="kpi-info">
            <span class="kpi-label">ธุรกรรมการเงิน (Financial Logs)</span>
            <h3 class="kpi-value"><?php echo number_format($fin_actions_count); ?> <small>รายการ</small></h3>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon kpi-orange">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="kpi-info">
            <span class="kpi-label">บันทึกวันนี้ (Today's Logs)</span>
            <h3 class="kpi-value"><?php echo number_format($today_actions_count); ?> <small>รายการ</small></h3>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="card-header-flex">
        <div class="header-left">
            <h3 class="card-title">
                <i class="fas fa-history text-gold"></i> ตรวจสอบประวัติกิจกรรมระบบ (Audit Log Trail)
            </h3>
            <p class="card-subtitle">
                บันทึกความเปลี่ยนแปลงทุกขั้นตอนเพื่อความโปร่งใส ตรวจสอบย้อนหลังได้ (พบทั้งหมด <?php echo number_format($filtered_total); ?> รายการ)
            </p>
        </div>
        <div class="header-right">
            <a href="actions/export_csv.php?type=audit_logs" class="btn-admin btn-outline">
                <i class="fas fa-file-csv mr-6"></i> ส่งออก Audit CSV
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="audit_logs.php" class="audit-filter-bar mb-20">
        <div class="filter-group">
            <label><i class="fas fa-filter mr-4"></i> ประเภทกิจกรรม</label>
            <select name="action" class="form-control-sm" onchange="this.form.submit()">
                <option value="">-- กิจกรรมทั้งหมด --</option>
                <?php foreach ($available_actions as $act): ?>
                    <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $filter_action === $act ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($act); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><i class="fas fa-user-tag mr-4"></i> ผู้กระทำ (Actor)</label>
            <select name="actor_type" class="form-control-sm" onchange="this.form.submit()">
                <option value="">-- ผู้กระทำทั้งหมด --</option>
                <option value="Admin" <?php echo $filter_actor_type === 'Admin' ? 'selected' : ''; ?>>แอดมิน (Admin)</option>
                <option value="Member" <?php echo $filter_actor_type === 'Member' ? 'selected' : ''; ?>>สมาชิก (Member)</option>
                <option value="System" <?php echo $filter_actor_type === 'System' ? 'selected' : ''; ?>>ระบบอัตโนมัติ (System)</option>
            </select>
        </div>

        <div class="filter-group">
            <label><i class="fas fa-search mr-4"></i> ค้นหาคำสำคัญ</label>
            <input type="text" name="keyword" class="form-control-sm" placeholder="ชื่อ, IP, หรือรายละเอียด..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>

        <div class="filter-group">
            <label><i class="fas fa-calendar mr-4"></i> ตั้งแต่วันที่</label>
            <input type="date" name="date_from" class="form-control-sm" value="<?php echo htmlspecialchars($filter_date_from); ?>">
        </div>

        <div class="filter-group">
            <label><i class="fas fa-calendar mr-4"></i> ถึงวันที่</label>
            <input type="date" name="date_to" class="form-control-sm" value="<?php echo htmlspecialchars($filter_date_to); ?>">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn-filter-submit">
                <i class="fas fa-search"></i> ค้นหา
            </button>
            <?php if (!empty($filter_action) || !empty($filter_actor_type) || !empty($filter_keyword) || !empty($filter_date_from) || !empty($filter_date_to)): ?>
                <a href="audit_logs.php" class="btn-filter-reset" title="ล้างตัวกรอง">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="70">#ID</th>
                    <th width="150">วันและเวลา</th>
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
                        <td colspan="7" class="text-center py-40 text-muted">
                            <i class="fas fa-clipboard-check fs-36 mb-10 d-block"></i>
                            ไม่พบรายการประวัติการทำงานตามเงื่อนไขที่เลือก
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
                            <td><span class="text-muted">#<?php echo $log['log_id']; ?></span></td>
                            <td>
                                <div class="font-weight-600"><?php echo date('d/m/Y', strtotime($log['created_at'])); ?></div>
                                <small class="text-muted"><?php echo date('H:i:s น.', strtotime($log['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-6">
                                    <span class="badge <?php echo $actor_badge; ?> font-11">
                                        <?php echo htmlspecialchars($log['actor_type']); ?>
                                    </span>
                                </div>
                                <div class="font-weight-600 mt-2">
                                    <?php echo htmlspecialchars($log['actor_name'] ?: 'N/A'); ?>
                                </div>
                                <?php if (!empty($log['actor_id'])): ?>
                                    <small class="text-muted">ID: <?php echo $log['actor_id']; ?></small>
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
                                        #<?php echo $log['entity_id']; ?>
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
                                <small class="text-muted d-block text-truncate mt-2" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>" style="max-width: 140px;">
                                    <?php 
                                        $ua = $log['user_agent'] ?? '';
                                        if (strpos($ua, 'Chrome') !== false) echo '<i class="fab fa-chrome"></i> Chrome';
                                        elseif (strpos($ua, 'Firefox') !== false) echo '<i class="fab fa-firefox"></i> Firefox';
                                        elseif (strpos($ua, 'Safari') !== false) echo '<i class="fab fa-safari"></i> Safari';
                                        elseif (strpos($ua, 'Edge') !== false) echo '<i class="fab fa-edge"></i> Edge';
                                        else echo '<i class="fas fa-globe"></i> Web Client';
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
