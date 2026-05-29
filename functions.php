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

function uploadImage($imageRequest, $folder){

    if(!isset($_FILES[$imageRequest])){
        return "noimage.png";
    }

    $imagename = rand(1000,100000) . "_" . $_FILES[$imageRequest]['name'];
    $imagetmp  = $_FILES[$imageRequest]['tmp_name'];
    $imagesize = $_FILES[$imageRequest]['size'];

    $allowExt = array("jpg","png","jpeg","gif");

    $strToArray = explode(".", $imagename);
    $ext = strtolower(end($strToArray));

    if(!in_array($ext , $allowExt)){
        return "type_error";
    }

    if($imagesize > 5 * 1024 * 1024){
        return "size_error";
    }

    $path = "../upload/" . $folder . "/" . $imagename;

    move_uploaded_file($imagetmp , $path);

    return $imagename;
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