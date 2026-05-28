<?php
include "../connect.php";

header("Content-Type: application/json");

// 🔥 قراءة JSON فقط
$data = json_decode(file_get_contents("php://input"), true);

$request_id = $data['request_id'] ?? null;

if (!$request_id) {
    echo json_encode([
        "status" => "failure",
        "message" => "Missing request_id"
    ]);
    exit;
}

// 🔥 الاستعلام
$stmt = $con->prepare("
    SELECT 
        m.match_id,
        m.service_id,

        sp.service_name AS user_name,  -- 🔥 الاسم من البروفايدر
        sp.image,                      -- 🔥 الصورة من البروفايدر

        COALESCE(sp.price, 0) AS price,
        m.status

    FROM matches m

    LEFT JOIN service_providers sp 
        ON m.service_id = sp.service_id

    WHERE m.request_id = ?
    AND m.status != 'rejected'

    ORDER BY 
        CASE 
            WHEN m.status = 'accepted' THEN 0
            ELSE 1
        END,
        price ASC
");
$stmt->execute([$request_id]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "success",
    "data" => $data
]);