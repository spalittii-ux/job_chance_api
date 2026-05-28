<?php

include "../connect.php";

// قراءة البيانات بصيغة RAW JSON
$data = json_decode(file_get_contents("php://input"), true);

// قراءة الإيميل
$email = $data['email'] ?? null;
$verify_code = rand(10000, 99999);

if (!$email) {
    echo json_encode([
        "status" => "fail",
        "message" => "Email is required"
    ]);
    exit;
}

// تحقق من وجود المستخدم
$stmt = $con->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() == 0) {
    echo json_encode([
        "status" => "fail",
        "message" => "Email not found"
    ]);
    exit;
}



// تحديث الكود في الداتا بيز
$update = $con->prepare("
    UPDATE users 
    SET verifycode = ? 
    WHERE email = ?
");

$success = $update->execute([
    $verify_code,
    $email
]);

if ($success) {
     // sendcode(
    //     $email,
    //     "Verify Code JobChance",
    //     "Your verification code is: $verifycode"
    // );

    echo json_encode([
        "status" => "success",
        "message" => "Verify code sent successfully",
        "verify_code" => $verify_code // احذفيه بالإنتاج
    ]);

} else {
    echo json_encode([
        "status" => "fail",
        "message" => "Failed to generate verify code"
    ]);
}
