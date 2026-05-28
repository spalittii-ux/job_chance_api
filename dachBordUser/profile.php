 <?php
include "../connect.php";

/// ==============================
/// 🔹 قراءة البيانات (يدعم كل الحالات)
/// ==============================
$action  = $_POST['action'] ?? $_GET['action'] ?? null;
$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;

// fallback إذا ما وصل POST
if (!$user_id || !$action) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if ($data) {
        $action  = $action  ?? ($data['action'] ?? null);
        $user_id = $user_id ?? ($data['user_id'] ?? null);
    }
}

/// ==============================
/// 🔴 تحقق user_id
/// ==============================
if (!$user_id) {
    echo json_encode([
        "status" => "failure",
        "message" => "Missing user_id"
    ]);
    exit;
}

/// ==============================
/// 🔍 تحقق المستخدم موجود
/// ==============================
$stmtCheck = $con->prepare("SELECT * FROM users WHERE user_id = ?");
$stmtCheck->execute([$user_id]);

if ($stmtCheck->rowCount() == 0) {
    echo json_encode([
        "status" => "failure",
        "message" => "User not found"
    ]);
    exit;
}

/// ==============================
/// 🔹 GET PROFILE
/// ==============================
// if ($action == "get") {

//     $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    

//     echo json_encode([
//         "status" => "success",
//         "data" => $user
//     ]);
//     exit;
// }
if ($action == "get") {

    $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "user_id" => $user["user_id"],
            "user_name" => $user["user_name"],
            "email" => $user["email"],
            "city" => $user["city"],
            "phone_number" => $user["phone_number"],
            "birthday" => $user["birthday"],
            "sex" => $user["sex"],
            "image" => $user["image"] ?? ""
        ]
    ]);

    exit;
}

/// ==============================
/// 🔹 UPDATE PROFILE
/// ==============================
if ($action == "update") {

    $user_name = $_POST['user_name'] ?? null;
    $email     = $_POST['email'] ?? null;
    $phone     = $_POST['phone_number'] ?? null;
    $city      = $_POST['city'] ?? null;
    $birthday  = $_POST['birthday'] ?? null;
    $sex       = $_POST['sex'] ?? null;

    /// 🧠 حل مشكلة التاريخ الفاضي
    if ($birthday == "") {
        $birthday = null;
    }

    /// ==============================
    /// 🖼️ رفع الصورة
    /// ==============================
    $image = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

        $image = uploadImage("image", "profile");

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
            UPDATE users SET 
            user_name = ?, 
            email = ?, 
            phone_number = ?, 
            city = ?, 
            birthday = ?, 
            sex = ?, 
            image = ?
            WHERE user_id = ?
        ");

        $result = $stmt->execute([
            $user_name,
            $email,
            $phone,
            $city,
            $birthday,
            $sex,
            $image,
            $user_id
        ]);

    } else {

        $stmt = $con->prepare("
            UPDATE users SET 
            user_name = ?, 
            email = ?, 
            phone_number = ?, 
            city = ?, 
            birthday = ?, 
            sex = ?
            WHERE user_id = ?
        ");

        $result = $stmt->execute([
            $user_name,
            $email,
            $phone,
            $city,
            $birthday,
            $sex,
            $user_id
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
/// ❌ ACTION غير معروف
/// ==============================
echo json_encode([
    "status" => "failure",
    "message" => "Invalid action"
]);