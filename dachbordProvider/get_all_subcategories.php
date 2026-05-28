<?php
include "../connect.php";

// تحديد اللغة
$lang = $_SERVER['HTTP_LANG'] ?? 'en';

try {

    if ($lang == 'ar') {
        $nameField = "COALESCE(category_name_ar, category_name)";
    } else {
        $nameField = "category_name";
    }

    $stmt = $con->prepare("
        SELECT 
            category_id,
            $nameField AS category_name
        FROM categories
        WHERE parent_id IS NOT NULL
        AND is_active = true
        ORDER BY order_index
    ");

    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "failure",
        "message" => "Server Error"
    ]);

}