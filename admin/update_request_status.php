<?php
include "../connect.php";
include "admin_guard.php";

$data = json_decode(file_get_contents("php://input"), true);

$admin_id   = $data['admin_id'] ?? null;
$request_id = $data['request_id'] ?? null;
$status     = $data['status'] ?? null;

// حماية
requireAdmin($con, $admin_id);

$allowedStatus = ['pending', 'complete', 'canceled'];

if (!$request_id || !in_array($status, $allowedStatus)) {
    echo json_encode([
        "status" => "fail",
        "message" => "Invalid data"
    ]);
    exit;
}

try {
    $stmt = $con->prepare("
        UPDATE requests
        SET status = ?
        WHERE request_id = ?
    ");
    $stmt->execute([$status, $request_id]);

    echo json_encode([
        "status" => "success",
        "message" => "Request status updated"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => $e->getMessage()
    ]);
}

