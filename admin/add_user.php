<?php
include "../connect.php";
include "admin_guard.php";

// استقبال الداتا
$data = json_decode(file_get_contents("php://input"), true);

$admin_id  = $data['admin_id']  ?? null;
$user_name = trim($data['user_name'] ?? '');
$email     = trim($data['email'] ?? '');
$password  = $data['password'] ?? '';
$role      = $data['role'] ?? 'user'; // افتراضي user

// 🔐 تحقق أنو أدمن
requireAdmin($con, $admin_id);

// 🧪 تحقق من القيم
if (
    empty($user_name) ||
    empty($email) ||
    empty($password)
) {
    echo json_encode([
        "status" => "failure",
        "message" => "All fields are required"
    ]);
    exit;
}

// ✉️ تحقق من الإيميل
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "failure",
        "message" => "Email id invalid"
    ]);
    exit;
}

// 👥 تحقق من الدور
if (!in_array($role, ['user', 'admin'])) {
    $role = 'user';
}

// 🔍 تحقق من وجود الإيميل
$stmt = $con->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "status" => "failure",
        "message" => "Email already exists"
    ]);
    exit;
}

// 🔒 تشفير كلمة المرور
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// ➕ إضافة المستخدم
$stmt = $con->prepare("
    INSERT INTO users (user_name, email, password, role, is_verified)
    VALUES (?, ?, ?, 'user', true)
");

$success = $stmt->execute([
    $user_name,
    $email,
    $hashedPassword,

]);

if ($success) {
    echo json_encode([
        "status" => "success",
        "message" => "Account created successfully"
    ]);
} else {
    echo json_encode([
        "status" => "failure",
        "message" => "Registration failed"
    ]);
}
