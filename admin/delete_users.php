<?php
include "../connect.php";
include "admin_guard.php";

// قراءة JSON
$data = json_decode(file_get_contents("php://input"), true);

// admin id
$admin_id = $data['admin_id'] ?? null;

// حماية الأدمن
requireAdmin($con, $admin_id);

// user id المراد حذفه
$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "fail",
        "message" => "Missing user id"
    ]);
    exit;
}

// منع حذف الأدمن نفسه (حماية مهمة)
if ($admin_id == $user_id) {
    echo json_encode([
        "status" => "fail",
        "message" => "You cannot delete yourself"
    ]);
    exit;
}

// تنفيذ الحذف
$stmt = $con->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "status" => "success",
        "message" => "User deleted successfully"
    ]);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "User not found"
    ]);
}

/*<?php
include "admin_guard.php";
include "../connect.php";

$user_id = $_POST['user_id'];

$stmt = $con->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);

echo json_encode([
    "status" => "success",
    "message" => "User deleted"
]);
*/