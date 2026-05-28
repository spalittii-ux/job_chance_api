<?php

include "../connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id    = $data['user_id'] ?? null;
$request_id = $data['request_id'] ?? null;

if(!$user_id || !$request_id){
    echo json_encode([
        "status" => "fail",
        "message" => "missing data"
    ]);
    exit;
}

// 🔥 جيب service_id من user_id
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

// 🚫 منع التكرار
$stmt = $con->prepare("
    SELECT * FROM matches 
    WHERE service_id = ? AND request_id = ?
");
$stmt->execute([$service_id, $request_id]);

if($stmt->rowCount() > 0){
    echo json_encode([
        "status" => "fail",
        "message" => "already applied"
    ]);
    exit;
}

// ✅ إضافة
$stmt = $con->prepare("
    INSERT INTO matches (user_id, service_id, request_id, status)
    VALUES (?, ?, ?, 'pending')
");

$success = $stmt->execute([$user_id, $service_id, $request_id]);

echo json_encode([
    "status" => $success ? "success" : "fail"
]);