<?php

// include "../connect.php";

// // 📥 قراءة RAW JSON
// $data = json_decode(file_get_contents("php://input"), true);

// $user_id = $data['user_id'] ?? null;

// // ❌ تحقق
// if(!$user_id){
//     echo json_encode([
//         "status" => "failure",
//         "message" => "user_id is required"
//     ]);
//     exit;
// }

// // 🔥 التأكد من وجود البروفايدر (عن طريق user_id)
// $stmtCheck = $con->prepare("
//     SELECT job_type
//     FROM service_providers
//     WHERE user_id = ?
// ");
// $stmtCheck->execute([$user_id]);

// $provider = $stmtCheck->fetch(PDO::FETCH_ASSOC);

// if(!$provider){
//     echo json_encode([
//         "status" => "failure",
//         "message" => "Provider not found"
//     ]);
//     exit;
// }

// $job_type = $provider['job_type'];

// //
// // 📊 1. الطلبات اللي وصلت للبروفايدر حسب شغله
// //
// $stmtReceived = $con->prepare("
//     SELECT COUNT(*) AS total_received_requests
//     FROM requests
//     WHERE primary_type = ?
// ");
// $stmtReceived->execute([$job_type]);

// $total_received = $stmtReceived->fetch(PDO::FETCH_ASSOC)['total_received_requests'];

// //
// // 📊 2. الطلبات اللي اشتغل عليها (matches)
// //
// $stmtMatches = $con->prepare("
//     SELECT 
//         COUNT(*) AS total_applied_requests,
//         SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS total_accepted,
//         SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS total_rejected
//     FROM matches
//     WHERE user_id = ?
// ");

// $stmtMatches->execute([$user_id]);
// $matches = $stmtMatches->fetch(PDO::FETCH_ASSOC);

// //
// // 📤 RESPONSE FINAL
// //
// echo json_encode([
//     "status" => "success",
//     "data" => [
//         "total_received_requests" => (int)$total_received,
//         "total_applied_requests" => (int)$matches['total_applied_requests'],
//         "total_accepted" => (int)$matches['total_accepted'],
//         "total_rejected" => (int)$matches['total_rejected']
//     ]
// ]); 




include "../connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "failure",
        "message" => "user_id is required"
    ]);
    exit;
}

/*
========================================
1. جلب بيانات البروفايدر
========================================
*/
$stmt = $con->prepare("
    SELECT service_id, job_type 
    FROM service_providers 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);

$provider = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$provider) {
    echo json_encode([
        "status" => "failure",
        "message" => "Provider not found"
    ]);
    exit;
}

$service_id = $provider['service_id'];
$job_type   = $provider['job_type'];

/*
========================================
2. الطلبات المستلمة (فئة فرعية فقط)
- category_id مرتبط بالـ categories
- والفئة الفرعية = parent_id IS NOT NULL
========================================
*/
$stmtReceived = $con->prepare("
   SELECT COUNT(*) AS total_received_requests
FROM requests r
JOIN service_providers sp ON sp.user_id = ?
WHERE r.category_id = sp.job_type
");

$stmtReceived->execute([$user_id]); // ✔ لازم تمرير user_id

$total_received = $stmtReceived->fetch(PDO::FETCH_ASSOC)['total_received_requests'];
/*
========================================
3. الطلبات اللي تعامل معها البروفايدر
========================================
*/
$stmtMatches = $con->prepare("
    SELECT 
        COUNT(*) AS total_applied_requests,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS total_accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS total_rejected
    FROM matches
    WHERE service_id = ?
");

$stmtMatches->execute([$service_id]);

$matches = $stmtMatches->fetch(PDO::FETCH_ASSOC);

/*
========================================
4. RESPONSE
========================================
*/
echo json_encode([
    "status" => "success",
    "data" => [
        "total_received_requests" => (int)$total_received,
        "total_applied_requests"  => (int)$matches['total_applied_requests'],
        "total_accepted"          => (int)$matches['total_accepted'],
        "total_rejected"          => (int)$matches['total_rejected']
    ]
]);

?>