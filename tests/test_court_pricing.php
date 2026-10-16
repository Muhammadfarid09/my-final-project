<?php
/**
 * Test Suite: Badminton Court Pricing Engine Verification
 */
require_once __DIR__ . '/../config/config.php';

$testCases = [
    [
        'name' => 'Case 1: Saturday 09:00 - 11:00 (2 hrs, Weekend)',
        'date' => '2026-10-17', // Saturday
        'start' => '09:00',
        'end' => '11:00',
        'expected_status' => 'success',
        'expected_price' => 400.00
    ],
    [
        'name' => 'Case 2: Sunday 18:00 - 21:00 (3 hrs, Weekend)',
        'date' => '2026-10-18', // Sunday
        'start' => '18:00',
        'end' => '21:00',
        'expected_status' => 'success',
        'expected_price' => 600.00
    ],
    [
        'name' => 'Case 3: Wednesday 10:00 - 12:00 (2 hrs, Weekday Daytime)',
        'date' => '2026-10-14', // Wednesday
        'start' => '10:00',
        'end' => '12:00',
        'expected_status' => 'success',
        'expected_price' => 300.00
    ],
    [
        'name' => 'Case 4: Friday 18:00 - 20:00 (2 hrs, Weekday Evening)',
        'date' => '2026-10-16', // Friday
        'start' => '18:00',
        'end' => '20:00',
        'expected_status' => 'success',
        'expected_price' => 360.00
    ],
    [
        'name' => 'Case 5: Thursday 16:00 - 18:00 (2 hrs, Cross-Window 1h Day + 1h Eve)',
        'date' => '2026-10-15', // Thursday
        'start' => '16:00',
        'end' => '18:00',
        'expected_status' => 'success',
        'expected_price' => 330.00
    ],
    [
        'name' => 'Case 6: Tuesday 15:00 - 19:00 (4 hrs, Cross-Window 2h Day + 2h Eve)',
        'date' => '2026-10-13', // Tuesday
        'start' => '15:00',
        'end' => '19:00',
        'expected_status' => 'success',
        'expected_price' => 660.00
    ],
    [
        'name' => 'Case 7: Validation start >= end (18:00 - 17:00)',
        'date' => '2026-10-16',
        'start' => '18:00',
        'end' => '17:00',
        'expected_status' => 'error',
        'expected_price' => null
    ],
    [
        'name' => 'Case 8: Validation outside business hours (07:00 - 09:00)',
        'date' => '2026-10-16',
        'start' => '07:00',
        'end' => '09:00',
        'expected_status' => 'error',
        'expected_price' => null
    ],
    [
        'name' => 'Case 9: Validation duration < 1 hour (10:00 - 10:30)',
        'date' => '2026-10-16',
        'start' => '10:00',
        'end' => '10:30',
        'expected_status' => 'error',
        'expected_price' => null
    ],
    [
        'name' => 'Case 10: Weekend full day (2026-10-17 08:00-23:00)',
        'date' => '2026-10-17',
        'start' => '08:00',
        'end' => '23:00',
        'expected_status' => 'success',
        'expected_price' => 3000.00
    ],
    [
        'name' => 'Case 11: Weekday cross-window (2026-10-19 16:00-18:00)',
        'date' => '2026-10-19',
        'start' => '16:00',
        'end' => '18:00',
        'expected_status' => 'success',
        'expected_price' => 330.00
    ],
    [
        'name' => 'Case 12: Weekday full day (2026-10-19 08:00-23:00)',
        'date' => '2026-10-19',
        'start' => '08:00',
        'end' => '23:00',
        'expected_status' => 'success',
        'expected_price' => 2430.00
    ],
    [
        'name' => 'Case 13: Time collision start==end (2026-10-19 16:00-16:00)',
        'date' => '2026-10-19',
        'start' => '16:00',
        'end' => '16:00',
        'expected_status' => 'error',
        'expected_price' => null
    ],
    [
        'name' => 'Case 14: Fractional hour 1.5h (2026-10-19 16:30-18:00)',
        'date' => '2026-10-19',
        'start' => '16:30',
        'end' => '18:00',
        'expected_status' => 'success',
        'expected_price' => 255.00
    ],
    [
        'name' => 'Case 15: Invalid midnight boundary 24:00 (2026-10-19 22:00-24:00)',
        'date' => '2026-10-19',
        'start' => '22:00',
        'end' => '24:00',
        'expected_status' => 'error',
        'expected_price' => null
    ],
    [
        'name' => 'Case 16: Non-existent calendar date (2026-02-30 16:00-18:00)',
        'date' => '2026-02-30',
        'start' => '16:00',
        'end' => '18:00',
        'expected_status' => 'error',
        'expected_price' => null
    ],
    [
        'name' => 'Case 17: Garbage date string ("garbage" 16:00-18:00)',
        'date' => 'garbage',
        'start' => '16:00',
        'end' => '18:00',
        'expected_status' => 'error',
        'expected_price' => null
    ],
    [
        'name' => 'Case 18: Empty date string ("" 16:00-18:00)',
        'date' => '',
        'start' => '16:00',
        'end' => '18:00',
        'expected_status' => 'error',
        'expected_price' => null
    ]
];

$passed = 0;
$failed = 0;

echo "==========================================================\n";
echo "       RUNNING COURT PRICING ENGINE TEST SUITE             \n";
echo "==========================================================\n";

foreach ($testCases as $idx => $tc) {
    $res = calculate_court_booking_price($tc['date'], $tc['start'], $tc['end']);
    $caseNum = $idx + 1;
    
    $isOk = false;
    if ($tc['expected_status'] === 'success') {
        if ($res['status'] === 'success' && abs($res['total_price'] - $tc['expected_price']) < 0.01) {
            $isOk = true;
        }
    } else {
        if ($res['status'] === 'error') {
            $isOk = true;
        }
    }
    
    if ($isOk) {
        $passed++;
        $actual = ($res['status'] === 'success') ? "{$res['total_price']} ฿" : "Error: {$res['message']}";
        echo "[PASS] {$tc['name']} -> Result: {$actual}\n";
    } else {
        $failed++;
        $actual = ($res['status'] === 'success') ? "{$res['total_price']} ฿" : "Error: {$res['message']}";
        echo "[FAIL] {$tc['name']}\n";
        echo "       Expected: {$tc['expected_status']} (" . ($tc['expected_price'] ?? 'N/A') . ")\n";
        echo "       Actual:   {$res['status']} ({$actual})\n";
    }
}

echo "==========================================================\n";
echo "SUMMARY: Total: " . count($testCases) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "==========================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

