<?php
include "../connect.php";
include "admin_guard.php";

$data = json_decode(file_get_contents("php://input"), true);

// تحقق من الأدمن
$admin_id = $data['admin_id'] ?? null;
requireAdmin($con, $admin_id);

// بيانات الطلب
$user_id        = $data['user_id'] ?? null;
$category_id    = $data['category_id'] ?? null;
$primary_type   = $data['primary_type'] ?? null;
$description    = $data['description'] ?? null;
$job_start_date = $data['job_start_date'] ?? null;
$job_time       = $data['job_time'] ?? null;
$location       = $data['location'] ?? null;
$image          = $data['image'] ?? null;
$status         = $data['status'] ?? 'pending';

// تحقق
if (
    !$user_id ||
    !$category_id ||
    !$primary_type ||
    !$job_start_date ||
    !$job_time ||
    !$location
) {
    echo json_encode([
        "status" => "fail",
        "message" => "Missing required fields"
    ]);
    exit;
}

// إدخال
$stmt = $con->prepare("
    INSERT INTO requests (
        user_id,
        category_id,
        primary_type,
        description,
        job_start_date,
        job_time,
        location,
        image,
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$success = $stmt->execute([
    $user_id,
    $category_id,
    $primary_type,
    $description,
    $job_start_date,
    $job_time,
    $location,
    $image,
    $status
]);

if ($success) {
    echo json_encode([
        "status" => "success",
        "message" => "Request added successfully"
    ]);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "Failed to add request"
    ]);
}
