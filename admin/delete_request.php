<?php
include "../connect.php";
include "admin_guard.php";

// قراءة البيانات من raw JSON
$data = json_decode(file_get_contents("php://input"), true);

$admin_id   = $data['admin_id']   ?? null;
$request_id = $data['request_id'] ?? null;

// حماية الأدمن
requireAdmin($con, $admin_id);

// تحقق من البيانات
if (!$request_id) {
    echo json_encode([
        "status" => "fail",
        "message" => "Missing request_id"
    ]);
    exit;
}

// حذف الطلب
$stmt = $con->prepare("
    DELETE FROM requests
    WHERE request_id = ?
");

$stmt->execute([$request_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "status" => "success",
        "message" => "Request deleted successfully"
    ]);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "Request not found"
    ]);
}
