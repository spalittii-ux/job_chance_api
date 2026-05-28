<?php
include "connect.php";

echo json_encode([
    "status" => "success",
    "message" => "Database connection is working"
]);
