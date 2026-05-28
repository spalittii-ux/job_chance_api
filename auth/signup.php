<?php
include "../connect.php";

// قراءة raw JSON
$data = json_decode(file_get_contents("php://input"), true);

$name     = filterRequest($data, 'user_name');
$email    = filterRequest($data, 'email');
$password = $data['password'] ?? null;
$phone    = filterRequest($data, 'phone_number');
$verifycode =  rand(10000 , 99999);

// 1️⃣ تحقق من الحقول
if (!$name || !$email || !$password) {
    echo json_encode(array(
        "status" => "failure",
        "message" => "Missing required fields"
    ));
    exit;
}

// 2️⃣ تحقق إذا الإيميل أو الهاتف موجود
$stmt = $con->prepare("
    SELECT user_id FROM users 
    WHERE email = ? OR phone_number = ?
");
$stmt->execute([$email, $phone]);

if ($stmt->rowCount() > 0) {
    echo json_encode(array(
        "status" => "failure",
        "message" => "Email or phone number already exists"
    ));
    exit;
}

// 3️⃣ تشفير كلمة المرور
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 4️⃣ إدخال المستخدم
$stmt = $con->prepare("
    INSERT INTO users (user_name, email, password, phone_number,verifycode, role , is_verified)
    VALUES (?, ?, ?, ?, ?, 'user',false)
");

$data = $stmt->execute(array(
    $name,
    $email,
    $hashedPassword,
    $phone,
    $verifycode,
    
));
  if ($data) {
    // sendcode(
    //     $email,
    //     "Verify Code JobChance",
    //     "Your verification code is: $verifycode"
    // );

$user_id = $con->lastInsertId();

echo json_encode([
    "status" => "success",
    "user_id" =>(int) $user_id,
    "username" => $name,
    "role" => "user"
]);
    
} else {
    echo json_encode([
        "status" => "failure",
        "message" => "Registration failed"
    ]);
}