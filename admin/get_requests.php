<?php
include "../connect.php";
include "admin_guard.php";

$data = json_decode(file_get_contents("php://input"), true);
$admin_id = $data['admin_id'] ?? null;

// حماية الأدمن
requireAdmin($con, $admin_id);

// 🔹 جلب كل الطلبات مع المستخدم + الكاتيغوري
$stmt = $con->prepare("
    SELECT 
        u.user_id,
        u.user_name,

        r.request_id,
        r.category_id,
        c.category_name,

        r.primary_type,
        r.description,
        r.job_start_date,
        r.job_time,
        r.status,
        r.location,
        r.image
    FROM requests r
    JOIN users u ON r.user_id = u.user_id
    JOIN categories c ON r.category_id = c.category_id
    ORDER BY u.user_id DESC, r.request_id DESC
");

$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 تصنيف الطلبات حسب المستخدم
$result = [];

foreach ($rows as $row) {
    $userId = $row['user_id'];

    if (!isset($result[$userId])) {
        $result[$userId] = [
            "user_id"   => $userId,
            "user_name" => $row['user_name'],
            "requests"  => []
        ];
    }

    $result[$userId]['requests'][] = [
        "request_id"     => $row['request_id'],
        "category_id"    => $row['category_id'],
        "category_name"  => $row['category_name'],
        "primary_type"   => $row['primary_type'], // جزئي / دائم / مؤقت
        "description"    => $row['description'],
        "job_start_date" => $row['job_start_date'],
        "job_time"       => $row['job_time'],
        "status"         => $row['status'],
        "location"       => $row['location'],
        "image"          => $row['image'],
    ];
}

echo json_encode([
    "status" => "success",
    "data" => array_values($result) // إعادة فهرسة
]);
