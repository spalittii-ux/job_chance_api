<?php
include "../connect.php";

header("Content-Type: application/json");

/// ==============================
/// 🔹 قراءة البيانات (POST + RAW)
/// ==============================
$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if ($data) {
        $user_id = $data['user_id'] ?? null;
    }
}

/// ==============================
/// 🔴 تحقق من user_id
/// ==============================
if (!$user_id) {
    echo json_encode([
        "status" => "failure",
        "message" => "Missing user_id"
    ]);
    exit;
}

/// ==============================
/// 🔍 تحقق المستخدم + جلب الصورة
/// ==============================
$stmt = $con->prepare("SELECT image FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);

if ($stmt->rowCount() == 0) {
    echo json_encode([
        "status" => "failure",
        "message" => "User not found"
    ]);
    exit;
}

$data = $stmt->fetch(PDO::FETCH_ASSOC);
$image = $data['image'];

/// ==============================
/// 🖼️ حذف الصورة من السيرفر
/// ==============================
if (!empty($image) && $image !== "null") {

    $path = "../upload/profile/" . $image;

    if (file_exists($path)) {
        unlink($path); // 🔥 حذف الصورة
    }
}

/// ==============================
/// 🔥 حذف المستخدم من DB
/// ==============================
$stmt = $con->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);

/// ==============================
/// 📤 RESPONSE
/// ==============================
if ($stmt->rowCount() > 0) {
    echo json_encode([
        "status" => "success",
        "message" => "Account deleted successfully"
    ]);
} else {
    echo json_encode([
        "status" => "failure",
        "message" => "Delete failed"
    ]);
}