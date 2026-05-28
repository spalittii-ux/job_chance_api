<?php
include "../connect.php";

// قراءة JSON
$data = json_decode(file_get_contents("php://input"), true);

$service_type = $data['service_type'] ?? null;

// تحديد اللغة من الهيدر
$lang = $_SERVER['HTTP_LANG'] ?? 'en';

try {

    // اختيار الأعمدة حسب اللغة
    if ($lang == 'ar') {
        $title = "title_ar";
        $desc = "description_ar";
        $type = "service_type_ar";
    } else {
        $title = "title";
        $desc = "description";
        $type = "service_type";
    }

    // في حال وجود فلترة
    if ($service_type) {

        // ⚠️ لازم نفلتر حسب نفس اللغة
        if ($lang == 'ar') {
            $filterField = "service_type_ar";
        } else {
            $filterField = "service_type";
        }

        $stmt = $con->prepare("
            SELECT 
                id,
                $title AS title,
                $desc AS description,
                $type AS service_type,
                discount,
                start_date,
                end_date,
                created_at
            FROM offers
            WHERE $filterField = ?
            ORDER BY created_at DESC
        ");

        $stmt->execute([$service_type]);

    } else {

        // بدون فلترة
        $stmt = $con->prepare("
            SELECT 
                id,
                $title AS title,
                $desc AS description,
                $type AS service_type,
                discount,
                start_date,
                end_date,
                created_at
            FROM offers
            ORDER BY created_at DESC
        ");

        $stmt->execute();
    }

    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "offers" => $offers
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "status" => "failure",
        "message" => "Database query failed"
    ]);

}
?> 