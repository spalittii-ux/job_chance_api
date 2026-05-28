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

// 🔥 جيب service_id
$stmt = $con->prepare("
    SELECT service_id 
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

$service_id = $provider['service_id'];

// 🔥 جيب الطلبات اللي قدم عليها
$stmt = $con->prepare("
    SELECT 
        m.match_id,
        m.status,

        r.request_id,
        r.description,
        r.image,

        u.user_name

    FROM matches m
    JOIN requests r ON m.request_id = r.request_id
    JOIN users u ON r.user_id = u.user_id

    WHERE m.service_id = ?

    ORDER BY m.match_id DESC
");

$stmt->execute([$service_id]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "success",
    "data" => $data
]);