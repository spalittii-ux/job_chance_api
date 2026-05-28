<?php
include "../connect.php";
include "admin_guard.php";

// قراءة JSON
$data = json_decode(file_get_contents("php://input"), true);

// بيانات الأدمن
$admin_id = $data['admin_id'] ?? null;

// حماية الأدمن
requireAdmin($con, $admin_id);

// بيانات المستخدم المراد تعديله
$user_id   = $data['user_id']   ?? null;
$user_name = $data['user_name'] ?? null;
$email     = $data['email']     ?? null;


// تحقق
if (!$user_id || !$user_name || !$email ) {
    echo json_encode([
        "status" => "fail",
        "message" => "Missing required fields"
    ]);
    exit;
}

// تحديث
$stmt = $con->prepare("
    UPDATE users
    SET user_name = ?, email = ?, role = 'user'
    WHERE user_id = ?
");
try{
$success = $stmt->execute([
    $user_name,
    $email,
    $user_id
]);

if ($success && $stmt->rowCount() > 0) {
    echo json_encode([
        "status" => "success",
        "message" => "User updated successfully"
    ]);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "Update failed or no changes"
    ]);
    
}} catch (PDOException $e) {

    // كود خطأ الإيميل المكرر (PostgreSQL)
    if ($e->getCode() == "23505") {
        echo json_encode([
            "status" => "fail",
            "message" => "Email already exists"
        ]);
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "Database error"
        ]);
    }}

/*<?php
include "admin_guard.php";
include "../connect.php";

$user_id   = $_POST['user_id'];
$user_name = $_POST['user_name'];
$email     = $_POST['email'];
$role      = $_POST['role'];

$stmt = $con->prepare("
    UPDATE users 
    SET user_name = ?, email = ?, role = ?
    WHERE user_id = ?
");

$stmt->execute([$user_name, $email, $role, $user_id]);

echo json_encode([
    "status" => "success",
    "message" => "User updated"
]);*/
