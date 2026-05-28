<?php
include "../connect.php";
include "admin_guard.php";

$data = json_decode(file_get_contents("php://input"), true);
$admin_id = $data['admin_id'] ?? null;

// تحقق فقط
requireAdmin($con, $admin_id);

// جلب كل المستخدمين
$stmt = $con->query("
    SELECT user_id, user_name, email, role
    FROM users
     WHERE role = 'user'
");

echo json_encode([
    "status" => "success",
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);

/*<?php
session_start();

include "../connect.php";
include "admin_guard.php"; // حماية الأدمن

$stmt = $con->query("
    SELECT 
        user_id,
        user_name,
        email,
        phone_number,
        city,
        role
    FROM users
    ORDER BY user_id ASC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "success",
    "data" => $users
]);*/
