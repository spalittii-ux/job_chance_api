<?php
include "../connect.php";

header("Content-Type: application/json; charset=UTF-8");

try {
    // ==========================================
    // قراءة البيانات من POST / GET / JSON
    // ==========================================
    $jsonData = [];

    $rawInput = file_get_contents("php://input");
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $jsonData = $decoded;
        }
    }

    $action = $_POST['action']
        ?? $_GET['action']
        ?? ($jsonData['action'] ?? null);

    $user_id = $_POST['user_id']
        ?? $_GET['user_id']
        ?? ($jsonData['user_id'] ?? null);

    // ==========================================
    // التحقق من البيانات الأساسية
    // ==========================================
    if (!$action) {
        echo json_encode([
            "status" => "failure",
            "message" => "Missing action"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$user_id || $user_id === "null") {
        echo json_encode([
            "status" => "failure",
            "message" => "Missing user_id"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $user_id = (int) $user_id;

    if ($user_id <= 0) {
        echo json_encode([
            "status" => "failure",
            "message" => "Invalid user_id"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==========================================
    // جلب بيانات المستخدم الحالية
    // ==========================================
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

    // ==========================================
    // GET PROFILE
    // ==========================================
    if ($action === "get") {
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

    // ==========================================
    // UPDATE PROFILE
    // ==========================================
    if ($action === "update") {

        // ملاحظة:
        // إذا الحقل لم يُرسل من Flutter، نحافظ على القيمة القديمة بدل ما نفرغها.
        $user_name = $_POST['user_name']
            ?? ($jsonData['user_name'] ?? ($user["user_name"] ?? ""));

        $email = $_POST['email']
            ?? ($jsonData['email'] ?? ($user["email"] ?? ""));

        $phone = $_POST['phone_number']
            ?? ($jsonData['phone_number'] ?? ($user["phone_number"] ?? ""));

        $city = $_POST['city']
            ?? ($jsonData['city'] ?? ($user["city"] ?? ""));

        $birthday = $_POST['birthday']
            ?? ($jsonData['birthday'] ?? ($user["birthday"] ?? null));

        $sex = $_POST['sex']
            ?? ($jsonData['sex'] ?? ($user["sex"] ?? ""));

        $user_name = trim((string) $user_name);
        $email = trim((string) $email);
        $phone = trim((string) $phone);
        $city = trim((string) $city);
        $sex = trim((string) $sex);

        if ($birthday === "" || $birthday === "null") {
            $birthday = null;
        }

        if ($user_name === "") {
            echo json_encode([
                "status" => "failure",
                "message" => "User name is required"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($email === "") {
            echo json_encode([
                "status" => "failure",
                "message" => "Email is required"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ==========================================
        // التأكد أن الإيميل غير مستخدم من مستخدم آخر
        // ==========================================
        $emailCheck = $con->prepare("
            SELECT user_id
            FROM users
            WHERE email = ?
            AND user_id != ?
            LIMIT 1
        ");

        $emailCheck->execute([$email, $user_id]);

        if ($emailCheck->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                "status" => "failure",
                "message" => "Email already exists"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ==========================================
        // معالجة الصورة
        // ==========================================
        $image = $user["image"] ?? "";

        if (isset($_FILES['image'])) {

            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {

                $uploadedImage = uploadImage("image", "profile");

                if ($uploadedImage === "type_error") {
                    echo json_encode([
                        "status" => "failure",
                        "message" => "Invalid image type"
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if ($uploadedImage === "size_error") {
                    echo json_encode([
                        "status" => "failure",
                        "message" => "Image too large"
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if ($uploadedImage === "upload_error" || $uploadedImage === null) {
                    echo json_encode([
                        "status" => "failure",
                        "message" => "Image upload failed"
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $image = $uploadedImage;

            } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {

                echo json_encode([
                    "status" => "failure",
                    "message" => "Image upload error",
                    "error_code" => $_FILES['image']['error']
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // ==========================================
        // تحديث بيانات المستخدم
        // ==========================================
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

        if (!$updatedUser) {
            echo json_encode([
                "status" => "failure",
                "message" => "Update failed"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            "status" => "success",
            "message" => "Profile updated successfully",
            "image" => $updatedUser["image"] ?? "",
            "data" => [
                "user_id" => (int) $updatedUser["user_id"],
                "user_name" => $updatedUser["user_name"] ?? "",
                "email" => $updatedUser["email"] ?? "",
                "phone_number" => $updatedUser["phone_number"] ?? "",
                "city" => $updatedUser["city"] ?? "",
                "birthday" => $updatedUser["birthday"] ?? "",
                "sex" => $updatedUser["sex"] ?? "",
                "image" => $updatedUser["image"] ?? ""
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==========================================
    // ACTION غير معروف
    // ==========================================
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