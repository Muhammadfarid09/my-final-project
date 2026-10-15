<?php
/**
 * Test Suite: CSRF Protection Verification
 */
require_once __DIR__ . '/../config/config.php';

echo "==========================================================\n";
echo "           RUNNING CSRF PROTECTION TEST SUITE             \n";
echo "==========================================================\n";

$passed = 0;
$failed = 0;

// Test 1: Generate CSRF token
$token = get_csrf_token();
if (!empty($token) && strlen($token) === 64) {
    echo "[PASS] Test 1: get_csrf_token generates 64-char hex token: {$token}\n";
    $passed++;
} else {
    echo "[FAIL] Test 1: get_csrf_token failed\n";
    $failed++;
}

// Test 2: validate_csrf_token with matching token
if (validate_csrf_token($token)) {
    echo "[PASS] Test 2: validate_csrf_token with valid token returns true\n";
    $passed++;
} else {
    echo "[FAIL] Test 2: validate_csrf_token with valid token failed\n";
    $failed++;
}

// Test 3: validate_csrf_token with mismatched token
if (!validate_csrf_token('invalid_token_12345')) {
    echo "[PASS] Test 3: validate_csrf_token with invalid token returns false\n";
    $passed++;
} else {
    echo "[FAIL] Test 3: validate_csrf_token with invalid token failed\n";
    $failed++;
}

// Test 4: validate_csrf_token with empty / null token
if (!validate_csrf_token(null) && !validate_csrf_token('')) {
    echo "[PASS] Test 4: validate_csrf_token with null or empty returns false\n";
    $passed++;
} else {
    echo "[FAIL] Test 4: validate_csrf_token with null/empty failed\n";
    $failed++;
}

// Test 5: csrf_field output structure
$fieldHtml = csrf_field();
if (strpos($fieldHtml, 'type="hidden"') !== false && strpos($fieldHtml, 'name="csrf_token"') !== false && strpos($fieldHtml, $token) !== false) {
    echo "[PASS] Test 5: csrf_field outputs valid hidden input with token\n";
    $passed++;
} else {
    echo "[FAIL] Test 5: csrf_field output format invalid: {$fieldHtml}\n";
    $failed++;
}

// Test 6: Verify all action files contain require_csrf_token()
$targetActions = [
    'admin_add_db.php',
    'admin_delete_db.php',
    'admin_edit_db.php',
    'cancellation_action_db.php',
    'chat_send_db.php',
    'court_add_db.php',
    'court_delete_db.php',
    'court_edit_db.php',
    'court_repair_add_db.php',
    'court_repair_edit_db.php',
    'court_repair_finish_db.php',
    'equipment_repair_add_db.php',
    'equipment_repair_finish_db.php',
    'equipment_return_db.php',
    'login_db.php',
    'logout_db.php',
    'member_edit_db.php',
    'news_add_db.php',
    'news_delete_db.php',
    'news_edit_db.php',
    'payment_verify_db.php',
    'pos_checkout_db.php',
    'product_add_db.php',
    'product_delete_db.php',
    'product_edit_db.php',
    'purchase_add_db.php',
    'reward_add_db.php',
    'reward_delete_db.php',
    'reward_edit_db.php'
];

$allActionsProtected = true;
foreach ($targetActions as $actionFile) {
    $content = file_get_contents(__DIR__ . '/../admin/actions/' . $actionFile);
    if (strpos($content, 'require_csrf_token()') === false) {
        echo "[FAIL] Test 6: {$actionFile} missing require_csrf_token()\n";
        $allActionsProtected = false;
    }
}

if ($allActionsProtected) {
    echo "[PASS] Test 6: All " . count($targetActions) . " state-changing action files contain require_csrf_token()\n";
    $passed++;
} else {
    $failed++;
}

echo "==========================================================\n";
echo "SUMMARY: Total: 6 | Passed: {$passed} | Failed: {$failed}\n";
echo "==========================================================\n";

if ($failed > 0) exit(1);
exit(0);
