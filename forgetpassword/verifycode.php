<?php
include "../connect.php";

// قراءة JSON
$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? null;
$verifycode = $data['verifycode'] ?? null;

// تحقق من القيم
 if (!$email || !$verifycode) {
     echo json_encode([
         "status" => "fail",
         "message" => "Email and verify code are required"
     ]);
     exit;
}

// جلب المستخدم
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
// نجاح التحقق
echo json_encode([
    "status" => "success",
    "message" => "Verification successful",
    "user_id" => $user['user_id']
]);
