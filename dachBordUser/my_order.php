<?php
include "../connect.php";

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;

$lang = $_SERVER['HTTP_LANG'] ?? 'en';

if(!$user_id){
    echo json_encode(["status" => "failure"]);
    exit;
}

if ($lang == 'ar') {
    $subName = "COALESCE(sub.category_name_ar, sub.category_name)";
    $mainName = "COALESCE(main.category_name_ar, main.category_name)";
} else {
    $subName = "sub.category_name";
    $mainName = "main.category_name";
}

$stmt = $con->prepare("
SELECT 
    r.request_id,
    r.image,
    r.primary_type,
    r.status,

    $subName AS sub_category_name,
    $mainName AS main_category_name,

    COUNT(m.match_id) AS applicants_count,

    MAX(m.accepted_at) AS accepted_at

FROM requests r

LEFT JOIN categories sub ON r.category_id = sub.category_id
LEFT JOIN categories main ON sub.parent_id = main.category_id
LEFT JOIN matches m ON r.request_id = m.request_id

WHERE r.user_id = ?

GROUP BY 
    r.request_id,
    r.image,
    r.primary_type,
    r.status,
    $subName,
    $mainName

HAVING 
(
    r.status = 'pending'
)
OR
(
    r.status = 'complete'
    AND MAX(m.accepted_at) >= (NOW() - INTERVAL '48 hours')
)

ORDER BY r.request_id DESC
");

$stmt->execute([$user_id]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "success",
    "data" => $data
]);