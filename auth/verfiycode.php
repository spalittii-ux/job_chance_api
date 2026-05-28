<?php
include "../connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$email      = $data['email'] ?? null;
$verifycode = $data['verifycode'] ?? null;

// 1️⃣ تحقق من المدخلات
if (!$email || !$verifycode) {
    echo json_encode([
        "status" => "failure",
        "message" => "Missing email or verification code"
    ]);
    exit;
}

// 2️⃣ تحقق من الإيميل والكود
$stmt = $con->prepare("
    SELECT user_id 
    FROM users 
    WHERE email = ? AND verifycode = ?
");
$stmt->execute([$email, $verifycode]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "status" => "failure",
        "message" => "Invalid verification code"
    ]);
    exit;
}

// 3️⃣ تفعيل الحساب
$stmt = $con->prepare("
    UPDATE users
    SET 
        is_verified = true,
        verifycode = NULL
    WHERE user_id = ?
");
$stmt->execute([$user['user_id']]);

echo json_encode([
    "status" => "success",
    "message" => "Account verified successfully"
]);
