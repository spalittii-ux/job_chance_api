<?php
include "../connect.php";

// قراءة البيانات من POST
$user_id        = filterRequest($_POST, "user_id");
$category_id    = filterRequest($_POST, "category_id");
$primary_type   = filterRequest($_POST, "primary_type");
$description    = filterRequest($_POST, "description");
$job_start_date = filterRequest($_POST, "job_start_date");
$job_time       = filterRequest($_POST, "job_time");
$location       = filterRequest($_POST, "location");

// رفع الصورة فقط إذا المستخدم أرسل صورة
$image = null;
if(isset($_FILES['image']) && $_FILES['image']['error'] === 0){
    $image = uploadImage("image", "requests");

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

 //التحقق من الحقول الأساسية
if(!$user_id || !$category_id || !$description){
    echo json_encode([
        "status" => "failure",
        "message" => "Missing required fields"
    ]);
    exit;
}

// تحقق من وجود المستخدم (Foreign Key)
$stmtCheck = $con->prepare("SELECT user_id FROM users WHERE user_id = ?");
$stmtCheck->execute([$user_id]);
if($stmtCheck->rowCount() == 0){
    echo json_encode([
        "status" => "failure",
        "message" => "User does not exist"
    ]);
    exit;
}
 $job_start_date = empty($job_start_date) ? null : $job_start_date;
$job_time       = empty($job_time) ? null : $job_time;
// إدخال الطلب في قاعدة البيانات
$stmt = $con->prepare("
    INSERT INTO requests
    (user_id, category_id, primary_type, description, job_start_date, job_time, location, image)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$success = $stmt->execute([
    $user_id,
    $category_id,
    $primary_type,
    $description,
    $job_start_date,
    $job_time,
    $location,
    $image // ممكن تكون null إذا المستخدم ما رفع صورة
]);

// إرجاع النتيجة
if($success){
    echo json_encode([
        "status" => "success",
        "message" => "Request added successfully"
    ]);
}else{
    echo json_encode([
        "status" => "failure",
        "message" => "Failed to add request"
    ]);
}