<?php
include "../connect.php";

header("Content-Type: application/json; charset=UTF-8");

try {
    $action  = $_POST['action'] ?? $_GET['action'] ?? null;
    $user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;

    if (!$user_id || !$action) {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (is_array($data)) {
            $action  = $action  ?? ($data['action'] ?? null);
            $user_id = $user_id ?? ($data['user_id'] ?? null);
        }
    }

    if (!$action) {
        echo json_encode([
            "status" => "failure",
            "message" => "Missing action"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$user_id || $user_id == "null") {
        echo json_encode([
            "status" => "failure",
            "message" => "Missing user_id"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmtCheck = $con->prepare("
        SELECT 
            user_id,
            user_name,
            email,
            city,
            phone_number,
            birthday,
            sex,
            image
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmtCheck->execute([$user_id]);
    $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "status" => "failure",
            "message" => "User not found"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==============================
    // GET PROFILE
    // ==============================
    if ($action == "get") {
        echo json_encode([
            "status" => "success",
            "data" => [
                "user_id" => (int) $user["user_id"],
                "user_name" => $user["user_name"] ?? "",
                "email" => $user["email"] ?? "",
                "city" => $user["city"] ?? "",
                "phone_number" => $user["phone_number"] ?? "",
                "birthday" => $user["birthday"] ?? "",
                "sex" => $user["sex"] ?? "",
                "image" => $user["image"] ?? ""
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==============================
    // UPDATE PROFILE
    // ==============================
    if ($action == "update") {

        $user_name = $_POST['user_name'] ?? "";
        $email     = $_POST['email'] ?? "";
        $phone     = $_POST['phone_number'] ?? "";
        $city      = $_POST['city'] ?? "";
        $birthday  = $_POST['birthday'] ?? null;
        $sex       = $_POST['sex'] ?? "";

        if ($birthday === "") {
            $birthday = null;
        }

        $image = $user["image"] ?? "";

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadedImage = uploadImage("image", "profile");

            if ($uploadedImage == "type_error") {
                echo json_encode([
                    "status" => "failure",
                    "message" => "Invalid image type"
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($uploadedImage == "size_error") {
                echo json_encode([
                    "status" => "failure",
                    "message" => "Image too large"
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($uploadedImage == "upload_error" || $uploadedImage === null) {
                echo json_encode([
                    "status" => "failure",
                    "message" => "Image upload failed"
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $image = $uploadedImage;
        }

        $stmt = $con->prepare("
            UPDATE users SET 
                user_name = ?, 
                email = ?, 
                phone_number = ?, 
                city = ?, 
                birthday = ?, 
                sex = ?, 
                image = ?
            WHERE user_id = ?
            RETURNING 
                user_id,
                user_name,
                email,
                phone_number,
                city,
                birthday,
                sex,
                image
        ");

        $stmt->execute([
            $user_name,
            $email,
            $phone,
            $city,
            $birthday,
            $sex,
            $image,
            $user_id
        ]);

        $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($updatedUser) {
            echo json_encode([
                "status" => "success",
                "message" => "Profile updated successfully",
                "image" => $updatedUser["image"] ?? "",
                "data" => $updatedUser
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            "status" => "failure",
            "message" => "Update failed"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        "status" => "failure",
        "message" => "Invalid action"
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        "status" => "failure",
        "message" => "Server error in profile.php",
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}