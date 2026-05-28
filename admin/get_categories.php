<?php
include "../connect.php";
include "admin_guard.php";

// قراءة JSON
$data = json_decode(file_get_contents("php://input"), true);

// تحقق من الأدمن
$admin_id = $data['admin_id'] ?? null;
requireAdmin($con, $admin_id);

// جلب الفئات
$stmt = $con->prepare("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name
");

$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "success",
    "data" => $categories
]);
