<?php

include "../connect.php";

// قراءة JSON (RAW)
$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;

try {

    if (!$user_id) {
        echo json_encode([
            "status" => "failure",
            "message" => "user_id is required"
        ]);
        exit;
    }

    $stmt = $con->prepare("
        SELECT 
            service_id,
            user_id,
            service_name,
            phone_number,
            job_time,
            job_type,
            experience,
            image
        FROM service_providers
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$user_id]);

    $provider = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($provider) {

        echo json_encode([
            "status" => "success",
            "exists" => true,
            "provider" => $provider
        ]);

    } else {

        echo json_encode([
            "status" => "success",
            "exists" => false
        ]);

    }

} catch (PDOException $e) {

    echo json_encode([
        "status" => "failure",
        "message" => "Database error"
    ]);

}

?>