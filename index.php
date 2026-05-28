<?php
include "connect.php";

echo json_encode([
    "status" => "success",
    "message" => "Job Chance API is running on Railway"
], JSON_UNESCAPED_UNICODE);