<?php
include "../connect.php";

// قراءة JSON
$data = json_decode(file_get_contents("php://input"), true);

$email    = $data['email'] ?? null;
$password = $data['password'] ?? null;

// تحقق من القيم
if (!$email || !$password) {
    echo json_encode([
        "status" => "fail",
        "message" => "Email and password are required"
    ]);
    exit;
}

// تحقق من وجود المستخدم
$stmt = $con->prepare("
    SELECT user_id 
    FROM users 
    WHERE email = ?
");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "status" => "fail",
        "message" => "Email not found"
    ]);
    exit;
}

// تشفير كلمة المرور
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// تحديث كلمة المرور + حذف كود التحقق
$update = $con->prepare("
    UPDATE users 
    SET password = ?, verifycode = NULL
    WHERE email = ?
");

$success = $update->execute([
    $hashedPassword,
    $email
]);

if ($success) {
    echo json_encode([
        "status" => "success",
        "message" => "Password reset successfully"
    ]);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "Password reset failed"
    ]);
}
