<?php
function filterRequest($data, $key) {
    if (!isset($data[$key])) {
        return null;
    }
    return htmlspecialchars(strip_tags($data[$key]));
}

// function sendcode($to , $title , $body){
//     $header = "From: support@maisahmad.com" . "\n" . "CC: maisahmad584@gmail.com";
//     mail($to , $title , $body ,$header);
// } 

function uploadImage($imageRequest, $folder) {

    if (!isset($_FILES[$imageRequest]) || $_FILES[$imageRequest]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $originalName = $_FILES[$imageRequest]['name'];
    $imageTmp     = $_FILES[$imageRequest]['tmp_name'];
    $imageSize    = $_FILES[$imageRequest]['size'];

    $allowedExt = ["jpg", "jpeg", "png", "gif", "webp"];

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt)) {
        return "type_error";
    }

    if ($imageSize > 5 * 1024 * 1024) {
        return "size_error";
    }

    $imageName = uniqid("img_", true) . "." . $ext;

    $targetDir = __DIR__ . "/upload/" . $folder;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $targetPath = $targetDir . "/" . $imageName;

    if (!move_uploaded_file($imageTmp, $targetPath)) {
        return "upload_error";
    }

    return $imageName;
}
function createNotification(
    $con,
    $userId,
    $title,
    $body,
    $type,
    $requestId = null,
    $matchId = null
) {
    $stmt = $con->prepare("
        INSERT INTO notifications 
        (
            user_id,
            title,
            body,
            type,
            related_request_id,
            related_match_id
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    return $stmt->execute([
        $userId,
        $title,
        $body,
        $type,
        $requestId,
        $matchId
    ]);
}