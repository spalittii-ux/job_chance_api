<?php

include "../connect.php";

// 📥 قراءة البيانات من POST
$user_id      = filterRequest($_POST, "user_id");
$service_name = filterRequest($_POST, "service_name");
$phone_number = filterRequest($_POST, "phone_number");
$job_time     = filterRequest($_POST, "job_time");
$job_type     = filterRequest($_POST, "job_type");
$experience   = filterRequest($_POST, "experience");
$price        = filterRequest($_POST, "price");
$location     = filterRequest($_POST, "location");

// 🖼️ رفع الصورة (اختياري)
$image = null;

if(isset($_FILES['image']) && $_FILES['image']['error'] === 0){

    $image = uploadImage("image", "providers");

    if($image === "type_error"){
        echo json_encode([
            "status" => "failure",
            "message" => "Invalid image type"
        ]);
        exit;
    }

    if($image === "size_error"){
        echo json_encode([
            "status" => "failure",
            "message" => "Image size exceeds 5MB"
        ]);
        exit;
    }
}

// 🚨 التحقق من الحقول الأساسية
if(!$user_id || !$service_name){
    echo json_encode([
        "status" => "failure",
        "message" => "Missing required fields"
    ]);
    exit;
}

$stmtCheck = $con->prepare("
    SELECT service_id FROM service_providers WHERE user_id = ?
");
$stmtCheck->execute([$user_id]);

if($stmtCheck->rowCount() > 0){
    echo json_encode([
        "status" => "failure",
        "message" => "Provider already exists"
    ]);
    exit;
}

// 🔐 تحقق من وجود المستخدم (foreign key)
$stmtCheck = $con->prepare("SELECT user_id FROM users WHERE user_id = ?");
$stmtCheck->execute([$user_id]);

if($stmtCheck->rowCount() == 0){
    echo json_encode([
        "status" => "failure",
        "message" => "User does not exist"
    ]);
    exit;
}

// 🧠 معالجة القيم الفارغة
$job_time   = empty($job_time) ? null : $job_time;
$job_type   = empty($job_type) ? null : $job_type;
$experience = empty($experience) ? null : $experience;
$price      = empty($price) ? null : $price;
$location   = empty($location) ? null : $location;

// 🗄️ إدخال البيانات
$stmt = $con->prepare("
    INSERT INTO service_providers
    (user_id, service_name, phone_number, job_time, job_type, experience, image, price, location)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$success = $stmt->execute([
    $user_id,
    $service_name,
    $phone_number,
    $job_time,
    $job_type,
    $experience,
    $image, // ممكن null
    $price,
    $location
]);

// 📤 النتيجة
if($success){

    $service_id = $con->lastInsertId();

    echo json_encode([
        "status" => "success",
        "message" => "Provider created successfully",
        "service_id" =>(int) $service_id
    ]);
}else{
    echo json_encode([
        "status" => "failure",
        "message" => "Failed to create provider"
    ]);
}