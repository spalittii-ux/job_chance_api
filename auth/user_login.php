<?php
include "../connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$email    = filterRequest($data, 'email');
$password = $data['password'] ?? null;

if (!$email || !$password) {
    echo json_encode([
        "status" => "failure",
        "message" => "Missing email or password"
    ]);
    exit;
}

$stmt = $con->prepare("
    SELECT user_id, user_name, password, role, is_verified
    FROM users
    WHERE email = ?
");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 1️⃣ تحقق من وجود المستخدم
if (!$user) {
    echo json_encode([
        "status" => "failure",
        "message" => "Invalid email or password user"
    ]);
    exit;
}

// 2️⃣ تحقق من كلمة المرور
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        "status" => "failure",
        "message" => "Invalid email or password password"
    ]);
    exit;
}

// 3️⃣ تحقق من تفعيل الحساب 👈 (التعديل المهم)
if (!$user['is_verified']) {
    echo json_encode([
        "status" => "failure",
        "message" => "Account not verified. Please verify your email."
    ]);
    exit;
}

// 4️⃣ نجاح تسجيل الدخول
echo json_encode([
    "status"  => "success",
    "user_id" => $user['user_id'],
    "username"    => $user['user_name'],
    "role"    => $user['role']
]);

/*<?php
include "../connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$email    = filterRequest($data, 'email');
$password = $data['password'] ?? null;


if (!$email || !$password) {
    echo json_encode([
        "status" => "fail",
        "message" => "Missing email or password"
    ]);
    exit;
}

$stmt = $con->prepare("
    SELECT user_id, user_name, password, role
    FROM users
    WHERE email = ?
");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($password, $user['password'])) {
        echo json_encode([
            "status"  => "success",
            "role"    => $user['role'],
            "user_id" => $user['user_id'],
            "name"    => $user['user_name']
        ]);
        exit;
    }
}


echo json_encode([
    "status" => "fail",
    "message" => "Invalid email or password"
]);
*/