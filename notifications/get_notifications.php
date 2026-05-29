<?php
include "../connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "failure",
        "message" => "user_id is required"
    ]);
    exit;
}

$stmt = $con->prepare("
    SELECT
        notification_id,
        title,
        body,
        type,
        related_request_id,
        related_match_id,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $con->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = ?
    AND is_read = false
");

$countStmt->execute([$user_id]);
$unreadCount = $countStmt->fetchColumn();

echo json_encode([
    "status" => "success",
    "unread_count" => (int) $unreadCount,
    "data" => $notifications
], JSON_UNESCAPED_UNICODE);