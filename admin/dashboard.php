<?php
include "../connect.php";
include "admin_guard.php";

// عدد المستخدمين
$users = $con->query("SELECT COUNT(*) FROM users")->fetchColumn();

// عدد الطلبات
$requests = $con->query("SELECT COUNT(*) FROM requests")->fetchColumn();

// المكتملة
$complete = $con->query("
    SELECT COUNT(*) FROM requests WHERE status = 'complete'
")->fetchColumn();

// المعلقة
$pending = $con->query("
    SELECT COUNT(*) FROM requests WHERE status = 'pending'
")->fetchColumn();

echo json_encode([
  "status" => "success",
  "data" => [
    "users" => (int)$users,
    "requests" => (int)$requests,
    "complete" => (int)$complete,
    "pending" => (int)$pending
  ]
]);
