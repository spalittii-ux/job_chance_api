<?php

include "connect.php";

$data = json_decode(file_get_contents("php://input"), true);

$type = $data['type'];
$id   = $data['id'];

if ($type == "request") {

    $stmt = $con->prepare("
        DELETE FROM requests
        WHERE request_id = ?
    ");

    $stmt->execute([$id]);

}

elseif ($type == "service") {

    $stmt = $con->prepare("
        DELETE FROM matches
        WHERE match_id = ?
    ");

    $stmt->execute([$id]);



}

echo json_encode([
    "status" => "success"
]);