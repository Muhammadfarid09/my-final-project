<?php
require_once '../config/config.php';
header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'รหัสข่าวสารหรือโปรโมชันไม่ถูกต้อง']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT * FROM News 
        WHERE news_id = :id AND (status = 'active' OR status IS NULL)
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$news) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลข่าวสารหรือโปรโมชัน']);
        exit();
    }

    $news_type = $news['news_type'] ?? 'ข่าวสารกิจกรรม';
    $badge_label = ($news_type === 'โปรโมชั่น') ? 'โปรโมชั่นพิเศษ' : 'ข่าวสาร & กิจกรรม';

    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => intval($news['news_id']),
            'title' => htmlspecialchars($news['news_title'], ENT_QUOTES, 'UTF-8'),
            'content' => nl2br(htmlspecialchars($news['news_content'], ENT_QUOTES, 'UTF-8')),
            'image' => !empty($news['news_image']) ? 'uploads/news/' . htmlspecialchars($news['news_image'], ENT_QUOTES, 'UTF-8') : '',
            'published_at' => date('d/m/Y H:i', strtotime($news['news_published_at'])),
            'type' => $badge_label
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' . $e->getMessage()]);
}
