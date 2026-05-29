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

$stmt = $con->prepare("
    INSERT INTO matches (user_id, service_id, request_id, status)
    VALUES (?, ?, ?, 'pending')
    RETURNING match_id
");

$stmt->execute([$user_id, $service_id, $request_id]);
$match_id = $stmt->fetchColumn();

if ($match_id) {

    // جلب صاحب الطلب واسم مقدم الخدمة
    $infoStmt = $con->prepare("
        SELECT 
            r.user_id AS owner_id,
            sp.service_name
        FROM requests r
        JOIN service_providers sp ON sp.service_id = ?
        WHERE r.request_id = ?
    ");

    $infoStmt->execute([$service_id, $request_id]);
    $info = $infoStmt->fetch(PDO::FETCH_ASSOC);

    if ($info) {
        createNotification(
            $con,
            $info['owner_id'],
            "متقدم جديد على طلبك",
            "قام {$info['service_name']} بالتقدم لتنفيذ طلبك.",
            "new_applicant",
            $request_id,
            $match_id
        );
    }

    echo json_encode([
        "status" => "success",
        "match_id" => (int) $match_id
    ]);

} else {
    echo json_encode([
        "status" => "fail"
    ]);
}