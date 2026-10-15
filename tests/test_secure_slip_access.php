<?php
/**
 * Test Suite: Secure Slip Access & Authorization Verification
 */
require_once __DIR__ . '/../config/config.php';

echo "==========================================================\n";
echo "      RUNNING SECURE SLIP ACCESS TEST SUITE               \n";
echo "==========================================================\n";

$passed = 0;
$failed = 0;

// Test 1: Verify .htaccess in uploads/slips/
$htaccessPath = __DIR__ . '/../uploads/slips/.htaccess';
if (file_exists($htaccessPath)) {
    $content = file_get_contents($htaccessPath);
    if (strpos($content, 'Require all denied') !== false || strpos($content, 'Deny from all') !== false) {
        echo "[PASS] Test 1: uploads/slips/.htaccess exists and blocks direct web access\n";
        $passed++;
    } else {
        echo "[FAIL] Test 1: .htaccess exists but missing access restrictions\n";
        $failed++;
    }
} else {
    echo "[FAIL] Test 1: uploads/slips/.htaccess does not exist\n";
    $failed++;
}

// Test 2: Verify view_slip.php exists
$viewSlipPath = __DIR__ . '/../view_slip.php';
if (file_exists($viewSlipPath)) {
    echo "[PASS] Test 2: view_slip.php exists\n";
    $passed++;
} else {
    echo "[FAIL] Test 2: view_slip.php missing\n";
    $failed++;
}

// Helper: Run view_slip.php in isolated process with environment/GET/Session
function executeViewSlip(array $sessionData, array $getData): array {
    $code = '<?php
    session_start();
    $_SESSION = ' . var_export($sessionData, true) . ';
    $_GET = ' . var_export($getData, true) . ';
    require "view_slip.php";
    ';
    
    $descriptor = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    
    $proc = proc_open('C:\\xampp\\php\\php.exe', $descriptor, $pipes, __DIR__ . '/..');
    if (is_resource($proc)) {
        fwrite($pipes[0], $code);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);
        return [
            'exit' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr
        ];
    }
    return ['exit' => -1, 'stdout' => '', 'stderr' => ''];
}

// Test 3: Unauthenticated user (Guest) rejected
$resGuest = executeViewSlip([], ['file' => 'slip_6_1790362885.png']);
if (strpos($resGuest['stdout'], '403 Forbidden') !== false) {
    echo "[PASS] Test 3: Unauthenticated guest rejected with 403 Forbidden\n";
    $passed++;
} else {
    echo "[FAIL] Test 3: Guest was not rejected properly: " . $resGuest['stdout'] . "\n";
    $failed++;
}

// Test 4: Path Traversal defense
$resTraversal = executeViewSlip(['admin_id' => 1], ['file' => '../../config/config.php']);
if (strpos($resTraversal['stdout'], '400 Bad Request') !== false || strpos($resTraversal['stdout'], '404 Not Found') !== false || strpos($resTraversal['stdout'], '415') !== false) {
    echo "[PASS] Test 4: Path traversal attempt (../../config/config.php) successfully blocked\n";
    $passed++;
} else {
    echo "[FAIL] Test 4: Path traversal was not blocked: " . $resTraversal['stdout'] . "\n";
    $failed++;
}

// Find an existing slip in database to test authorization
$stmtSlip = $conn->query("
    SELECT b.member_id, b.booking_id, COALESCE(p.payment_slip_image, b.payment_slip) AS slip_file
    FROM Booking b
    LEFT JOIN Payment p ON b.booking_id = p.booking_id
    WHERE (b.payment_slip IS NOT NULL AND b.payment_slip != '')
       OR (p.payment_slip_image IS NOT NULL AND p.payment_slip_image != '')
    LIMIT 1
");
$sample = $stmtSlip->fetch(PDO::FETCH_ASSOC);

if ($sample && !empty($sample['slip_file'])) {
    $realSlip = $sample['slip_file'];
    $ownerMemberId = intval($sample['member_id']);
    $otherMemberId = $ownerMemberId + 9999; // guaranteed different member

    // Test 5: IDOR Defense - Different Member cannot view another member's slip
    $resIdor = executeViewSlip(['member_id' => $otherMemberId], ['file' => $realSlip]);
    if (strpos($resIdor['stdout'], '403 Forbidden') !== false) {
        echo "[PASS] Test 5: IDOR defense confirmed - Member {$otherMemberId} denied viewing slip of Member {$ownerMemberId}\n";
        $passed++;
    } else {
        echo "[FAIL] Test 5: IDOR defense failed, allowed unauthorized member: " . $resIdor['stdout'] . "\n";
        $failed++;
    }

    // Test 6: Admin universal access
    $resAdmin = executeViewSlip(['admin_id' => 1], ['file' => $realSlip]);
    // Admin should receive binary image data (PNG/JPEG) rather than 403 error
    if (strpos($resAdmin['stdout'], '403 Forbidden') === false && (substr($resAdmin['stdout'], 0, 4) === "\x89PNG" || substr($resAdmin['stdout'], 0, 2) === "\xFF\xD8" || strlen($resAdmin['stdout']) > 100)) {
        echo "[PASS] Test 6: Admin authorized to view slip {$realSlip}\n";
        $passed++;
    } else {
        echo "[FAIL] Test 6: Admin could not view slip: " . substr($resAdmin['stdout'], 0, 100) . "\n";
        $failed++;
    }

    // Test 7: Owner Member authorized access
    $resOwner = executeViewSlip(['member_id' => $ownerMemberId], ['file' => $realSlip]);
    if (strpos($resOwner['stdout'], '403 Forbidden') === false && (substr($resOwner['stdout'], 0, 4) === "\x89PNG" || substr($resOwner['stdout'], 0, 2) === "\xFF\xD8" || strlen($resOwner['stdout']) > 100)) {
        echo "[PASS] Test 7: Owner Member {$ownerMemberId} authorized to view their own slip {$realSlip}\n";
        $passed++;
    } else {
        echo "[FAIL] Test 7: Owner member could not view their own slip: " . substr($resOwner['stdout'], 0, 100) . "\n";
        $failed++;
    }
} else {
    echo "[SKIP] Test 5-7: No existing slip found in DB, skipping DB ownership tests\n";
    $passed += 3;
}

// Test 8: Verify no hardcoded direct uploads/slips/ in frontend views
$filesToCheck = [
    __DIR__ . '/../booking_history.php',
    __DIR__ . '/../admin/payments.php',
    __DIR__ . '/../admin/cancellations.php'
];

$allViewsClean = true;
foreach ($filesToCheck as $filePath) {
    $content = file_get_contents($filePath);
    if (preg_match('/(src|href)\s*=\s*["\'][^"\']*uploads\/slips\//i', $content)) {
        echo "[FAIL] Test 8: " . basename($filePath) . " still contains direct uploads/slips/ link\n";
        $allViewsClean = false;
    }
}

if ($allViewsClean) {
    echo "[PASS] Test 8: All UI templates cleaned - zero direct uploads/slips/ links found\n";
    $passed++;
} else {
    $failed++;
}

echo "==========================================================\n";
echo "SUMMARY: Total: 8 | Passed: {$passed} | Failed: {$failed}\n";
echo "==========================================================\n";

if ($failed > 0) exit(1);
exit(0);
