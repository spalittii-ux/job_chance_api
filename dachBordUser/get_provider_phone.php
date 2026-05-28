<?php
include "../connect.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$service_id = $data['service_id'] ?? null;

if (!$service_id) {
    echo json_encode([
        "status" => "failure",
        "message" => "Missing service_id"
    ]);
    exit;
}

$stmt = $con->prepare("
  SELECT 
    sp.phone_number AS phone,
    sp.service_name
FROM service_providers sp
WHERE sp.service_id = ?
");

$stmt->execute([$service_id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "success",
    "data" => $row
]);