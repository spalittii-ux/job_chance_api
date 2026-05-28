<?php
include "../connect.php";

// قراءة JSON
$data = json_decode(file_get_contents("php://input"), true);

$parent_id = $data['parent_id'] ?? null;

// تحديد اللغة من الهيدر
$lang = $_SERVER['HTTP_LANG'] ?? 'en';

try {

    // 🔥 اختيار العمود + fallback (مهم جداً)
    if ($lang == 'ar') {
        $nameField = "COALESCE(category_name_ar, category_name)";
    } else {
        $nameField = "category_name";
    }

    // 1️⃣ الفئات الرئيسية
    if ($parent_id == null) {

        $stmt = $con->prepare("
            SELECT 
                category_id,
                $nameField AS category_name
            FROM categories
            WHERE parent_id IS NULL
            AND is_active = true
            ORDER BY order_index
        ");

        $stmt->execute();

    } else {

        // 2️⃣ الفئات الفرعية
        $stmt = $con->prepare("
            SELECT 
                category_id,
                $nameField AS category_name
            FROM categories
            WHERE parent_id = ?
            AND is_active = true
            ORDER BY order_index
        ");

        $stmt->execute([$parent_id]);
    }

    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $categories
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "failure",
        "message" => "Server Error"
    ]);

}