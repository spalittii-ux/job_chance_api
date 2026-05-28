<?php
include "../connect.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$action = $data['action'] ?? null;

// ==========================
// 🔹 1) جلب التفاصيل
// ==========================
if ($action == "get_details") {

    $service_id = $data['service_id'] ?? null;

    if (!$service_id) {
        echo json_encode(["status" => "failure", "message" => "Missing service_id"]);
        exit;
    }

    $stmt = $con->prepare("
        SELECT 
    sp.service_name,
    sp.job_time,
    sp.experience,
    sp.price,
    sp.location,
    sp.image,
    u.user_name,
    m.status,
    m.match_id,
    m.request_id
FROM service_providers sp
JOIN users u ON sp.user_id = u.user_id
JOIN matches m ON m.service_id = sp.service_id
WHERE sp.service_id = ?
    ");

    $stmt->execute([$service_id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data"   => $row
    ]);
}


elseif ($action == "update_status") {

    $match_id   = $data['match_id'] ?? null;
    $request_id = $data['request_id'] ?? null;
    $type       = $data['type'] ?? null;

    if (!$match_id || !$request_id || !$type) {
        echo json_encode(["status" => "failure", "message" => "Missing data"]);
        exit;
    }

    try {

        // ======================
        // ✅ ACCEPT
        // ======================
        if ($type == "accept") {

            // 1. قبول الشخص المختار + وقت القبول
            $stmt1 = $con->prepare("
                UPDATE matches 
                SET status = 'accepted',
                    accepted_at = NOW()
                WHERE match_id = ?
            ");
            $stmt1->execute([$match_id]);

            // 2. رفض الباقي
            $stmt2 = $con->prepare("
                UPDATE matches 
                SET status = 'rejected' 
                WHERE request_id = ? AND match_id != ?
            ");
            $stmt2->execute([$request_id, $match_id]);

            // 3. الطلب يصبح complete
            $stmt3 = $con->prepare("
                UPDATE requests 
                SET status = 'complete' 
                WHERE request_id = ?
            ");
            $stmt3->execute([$request_id]);
        }

        // ======================
        // ❌ REJECT
        // ======================
        if ($type == "reject") {

            $stmt = $con->prepare("
                UPDATE matches 
                SET status = 'rejected' 
                WHERE match_id = ?
            ");
            $stmt->execute([$match_id]);
        }

        echo json_encode([
            "status" => "success"
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "status" => "failure",
            "message" => $e->getMessage()
        ]);
    }
}
 
