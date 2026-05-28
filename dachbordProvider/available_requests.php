<?php

include "../connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;

if(!$user_id){
    echo json_encode([
        "status" => "fail",
        "message" => "user_id is required"
    ]);
    exit;
}

// 🔥 جيب البروفايدر من user_id
$stmt = $con->prepare("
    SELECT service_id, job_type 
    FROM service_providers 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);

$provider = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$provider){
    echo json_encode([
        "status" => "fail",
        "message" => "provider not found"
    ]);
    exit;
}

$job_type   = $provider['job_type'];
$service_id = $provider['service_id'];

// 🔥 جيب الطلبات (مع استثناء الطلبات اللي قدم عليها)
$stmt = $con->prepare("
    SELECT 
        r.request_id,
        r.description,
        r.location,
        r.job_start_date,
        r.job_time,
        r.primary_type,
        r.image,
        u.user_name,
        c.category_name,
        c.category_name_ar
    FROM requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN categories c ON r.category_id = c.category_id

    WHERE r.category_id = ?
    AND r.status = 'pending'

    AND NOT EXISTS (
        SELECT 1 FROM matches m
        WHERE m.request_id = r.request_id
        AND m.service_id = ?
    )

    ORDER BY r.request_id DESC
");

$stmt->execute([$job_type, $service_id]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "success",
    "data" => $data
]);