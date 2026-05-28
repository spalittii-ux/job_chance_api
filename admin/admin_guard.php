<?php
function requireAdmin($con, $admin_id) {

    // if (!$admin_id) {
    //     echo json_encode([
    //         "status" => "fail",
    //         "message" => "Missing admin id"
    //     ]);
    //     exit;
    // }

    // $stmt = $con->prepare("SELECT role FROM users WHERE user_id = ?");
    // $stmt->execute([$admin_id]);
    // $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // if (!$user || $user['role'] !== 'admin') {
    //     echo json_encode([
    //         "status" => "fail",
    //         "message" => "Admins only"
    //     ]);
    //     exit;
    // }
}

//<?php
//session_start();

//if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
   // echo json_encode([
    //    "status" => "fail",
    //    "message" => "Access denied"
   // ]);
   // exit;
//}
