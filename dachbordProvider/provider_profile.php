<?php
include "../connect.php";

/// ==============================
/// 🔹 قراءة البيانات
/// ==============================
$action  = $_POST['action'] ?? $_GET['action'] ?? null;
$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;

if (!$user_id || !$action) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if ($data) {
        $action  = $action  ?? ($data['action'] ?? null);
        $user_id = $user_id ?? ($data['user_id'] ?? null);
    }
}

/// ==============================
/// ❌ تحقق البيانات
/// ==============================
if (!$user_id || !$action) {
    echo json_encode([
        "status" => "failure",
        "message" => "Missing data"
    ]);
    exit;
}

/// ==============================
/// 🔥 GET service_id من جدول البروفايدر
/// ==============================
$stmtProvider = $con->prepare("
    SELECT service_id 
    FROM service_providers 
    WHERE user_id = ?
");
$stmtProvider->execute([$user_id]);
$provider = $stmtProvider->fetch(PDO::FETCH_ASSOC);

if (!$provider) {
    echo json_encode([
        "status" => "failure",
        "message" => "Provider not found"
    ]);
    exit;
}

$service_id = $provider['service_id'];

/// ==============================
/// 🔹 GET PROFILE
/// ==============================
if ($action == "get") {

    $stmt = $con->prepare("
        SELECT *
        FROM service_providers
        WHERE service_id = ?
    ");
    $stmt->execute([$service_id]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);
    exit;
}

/// ==============================
/// 🔹 UPDATE PROFILE
/// ==============================
if ($action == "update") {

    $service_name = $_POST['service_name'] ?? null;
    $phone        = $_POST['phone_number'] ?? null;
    $job_time     = $_POST['job_time'] ?? null;
    $job_type = (int)($_POST['job_type'] ?? 0);
    $experience   = $_POST['experience'] ?? null;
    $price        = $_POST['price'] ?? null;
    $location     = $_POST['location'] ?? null;

    /// ==============================
    /// 🖼️ صورة
    /// ==============================
    $image = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

        $image = uploadImage("image", "providers");

        if ($image == "type_error") {
            echo json_encode([
                "status" => "failure",
                "message" => "Invalid image type"
            ]);
            exit;
        }

        if ($image == "size_error") {
            echo json_encode([
                "status" => "failure",
                "message" => "Image too large"
            ]);
            exit;
        }
    }

    /// ==============================
    /// 🔄 UPDATE
    /// ==============================
    if ($image != null) {

        $stmt = $con->prepare("
            UPDATE service_providers SET
                service_name = ?,
                phone_number = ?,
                job_time = ?,
                job_type = ?,
                experience = ?,
                price = ?,
                location = ?,
                image = ?
            WHERE service_id = ?
        ");

        $result = $stmt->execute([
            $service_name,
            $phone,
            $job_time,
            $job_type,
            $experience,
            $price,
            $location,
            $image,
            $service_id
        ]);

    } else {

        $stmt = $con->prepare("
            UPDATE service_providers SET
                service_name = ?,
                phone_number = ?,
                job_time = ?,
                job_type = ?,
                experience = ?,
                price = ?,
                location = ?
            WHERE service_id = ?
        ");

        $result = $stmt->execute([
            $service_name,
            $phone,
            $job_time,
            $job_type,
            $experience,
            $price,
            $location,
            $service_id
        ]);
    }

    /// ==============================
    /// 📤 RESPONSE
    /// ==============================
    if ($result) {
        echo json_encode([
            "status" => "success",
            "message" => "Profile updated successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "failure",
            "message" => "Update failed"
        ]);
    }

    exit;
}

/// ==============================
/// ❌ ACTION INVALID
/// ==============================
echo json_encode([
    "status" => "failure",
    "message" => "Invalid action"
]);