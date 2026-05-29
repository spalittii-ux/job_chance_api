<?php
include "../connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;
$notification_id = $data['notification_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "failure",
        "message" => "user_id is required"
    ]);
    exit;
}

if ($notification_id) {
    $stmt = $con->prepare("
        UPDATE notifications
        SET is_read = true
        WHERE user_id = ?
        AND notification_id = ?
    ");

    $stmt->execute([$user_id, $notification_id]);
} else {
    $stmt = $con->prepare("
        UPDATE notifications
        SET is_read = true
        WHERE user_id = ?
    ");

    $stmt->execute([$user_id]);
}

echo json_encode([
    "status" => "success"
]);