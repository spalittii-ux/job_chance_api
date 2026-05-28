<?php
/*include "../connect.php";

// قراءة raw JSON
$data = json_decode(file_get_contents("php://input"), true);

$email  = filterRequest($data, 'email');
$city     = filterRequest($data, 'city');
$birthday = filterRequest($data, 'birthday');
$sex      = filterRequest($data, 'sex');

// 1️⃣ تحقق من الحقول الأساسية
if (!$email || !$city || !$birthday || !$sex) {
    echo json_encode([
        "status" => "failure",
        "message" => "Missing required fields"
    ]);
    exit;
}

// 2️⃣ تحقق أن المستخدم موجود
$stmt = $con->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() == 0) {
    echo json_encode([
        "status" => "failure",
        "message" => "User not found"
    ]);
    exit;
}

// 3️⃣ تحديث البيانات
$update = $con->prepare("
    UPDATE users 
    SET city = ?, birthday = ?, sex = ?
    WHERE email = ?
");

$result = $update->execute([
    $city,
    $birthday,
    $sex,
    $email
]);

// 4️⃣ إرسال النتيجة
if ($result) {
    echo json_encode([
        "status" => "success",
        "message" => "Profile completed successfully"
    ]);
} else {
    echo json_encode([
        "status" => "failure",
        "message" => "Failed to update profile"
    ]);
}
*/
include "../connect.php";

// 🔹 استقبال البيانات من form-data
$email    = filterRequest($_POST, 'email');
$city     = filterRequest($_POST, 'city');
$birthday = filterRequest($_POST, 'birthday');
$sex      = filterRequest($_POST, 'sex');

// 1️⃣ تحقق من الحقول
if (!$email || !$city || !$birthday || !$sex) {
    echo json_encode([
        "status" => "failure",
        "message" => "Missing required fields"
    ]);
    exit;
}

// 2️⃣ تحقق المستخدم
$stmt = $con->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() == 0) {
    echo json_encode([
        "status" => "failure",
        "message" => "User not found"
    ]);
    exit;
}

// 3️⃣ رفع الصورة (اختياري)
$image = null;

if(isset($_FILES['image']) && $_FILES['image']['error'] === 0){

    $image = uploadImage("image", "profile");

    if($image == "type_error"){
        echo json_encode([
            "status" => "failure",
            "message" => "Invalid image type"
        ]);
        exit;
    }

    if($image == "size_error"){
        echo json_encode([
            "status" => "failure",
            "message" => "Image too large"
        ]);
        exit;
    }
}

// 4️⃣ التحديث
if($image != null){

    // ✅ مع صورة
    $update = $con->prepare("
        UPDATE users 
        SET city = ?, birthday = ?, sex = ?, image = ?
        WHERE email = ?
    ");

    $result = $update->execute([
        $city,
        $birthday,
        $sex,
        $image,
        $email
    ]);

} else {

    // ✅ بدون صورة
    $update = $con->prepare("
        UPDATE users 
        SET city = ?, birthday = ?, sex = ?
        WHERE email = ?
    ");

    $result = $update->execute([
        $city,
        $birthday,
        $sex,
        $email
    ]);
}

// 5️⃣ النتيجة
if ($result) {
    echo json_encode([
        "status" => "success",
        "message" => "Profile completed successfully"
    ]);
} else {
    echo json_encode([
        "status" => "failure",
        "message" => "Failed to update profile"
    ]);
}