<?php
include "../connect.php";

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "status" => "failure",
        "message" => "Invalid JSON data"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $data['action'] ?? null;

if (!$action) {
    echo json_encode([
        "status" => "failure",
        "message" => "Missing action"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * إنشاء إشعار داخل التطبيق
 */
function addNotification(
    $con,
    $user_id,
    $title,
    $body,
    $type,
    $request_id = null,
    $match_id = null
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
        $user_id,
        $title,
        $body,
        $type,
        $request_id,
        $match_id
    ]);
}

// ==========================
// 1) جلب تفاصيل مقدم الخدمة
// ==========================
if ($action == "get_details") {

    $service_id = $data['service_id'] ?? null;
    $request_id = $data['request_id'] ?? null;

    if (!$service_id) {
        echo json_encode([
            "status" => "failure",
            "message" => "Missing service_id"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $sql = "
            SELECT 
                sp.service_id,
                sp.service_name,
                sp.job_time,
                sp.experience,
                sp.price,
                sp.location,
                sp.image,

                u.user_id AS provider_user_id,
                u.user_name,

                m.status,
                m.match_id,
                m.request_id
            FROM service_providers sp
            JOIN users u 
                ON sp.user_id = u.user_id
            JOIN matches m 
                ON m.service_id = sp.service_id
            WHERE sp.service_id = ?
        ";

        $params = [$service_id];

        if ($request_id) {
            $sql .= " AND m.request_id = ? ";
            $params[] = $request_id;
        }

        $sql .= " ORDER BY m.match_id DESC LIMIT 1 ";

        $stmt = $con->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode([
                "status" => "failure",
                "message" => "No details found"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            "status" => "success",
            "data" => $row
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            "status" => "failure",
            "message" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ==========================
// 2) قبول أو رفض مقدم الخدمة
// ==========================
elseif ($action == "update_status") {

    $match_id   = $data['match_id'] ?? null;
    $request_id = $data['request_id'] ?? null;
    $type       = $data['type'] ?? null;

    if (!$match_id || !$request_id || !$type) {
        echo json_encode([
            "status" => "failure",
            "message" => "Missing data"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!in_array($type, ["accept", "reject"])) {
        echo json_encode([
            "status" => "failure",
            "message" => "Invalid type"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $con->beginTransaction();

        // التأكد أن الـ match موجود
        $checkStmt = $con->prepare("
            SELECT 
                m.match_id,
                m.request_id,
                m.service_id,
                m.status,
                sp.user_id AS provider_user_id,
                sp.service_name
            FROM matches m
            JOIN service_providers sp
                ON sp.service_id = m.service_id
            WHERE m.match_id = ?
            AND m.request_id = ?
            LIMIT 1
        ");

        $checkStmt->execute([$match_id, $request_id]);
        $selectedMatch = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$selectedMatch) {
            $con->rollBack();

            echo json_encode([
                "status" => "failure",
                "message" => "Match not found"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ======================
        // ACCEPT
        // ======================
        if ($type == "accept") {

            // 1. قبول مقدم الخدمة المختار
            $stmt1 = $con->prepare("
                UPDATE matches 
                SET status = 'accepted',
                    accepted_at = NOW()
                WHERE match_id = ?
                AND request_id = ?
            ");
            $stmt1->execute([$match_id, $request_id]);

            // 2. جلب باقي المتقدمين قبل رفضهم لإرسال إشعارات لهم
            $otherStmt = $con->prepare("
                SELECT 
                    m.match_id,
                    sp.user_id AS provider_user_id,
                    sp.service_name
                FROM matches m
                JOIN service_providers sp
                    ON sp.service_id = m.service_id
                WHERE m.request_id = ?
                AND m.match_id != ?
                AND m.status != 'rejected'
            ");
            $otherStmt->execute([$request_id, $match_id]);
            $otherProviders = $otherStmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. رفض باقي المتقدمين
            $stmt2 = $con->prepare("
                UPDATE matches 
                SET status = 'rejected' 
                WHERE request_id = ?
                AND match_id != ?
            ");
            $stmt2->execute([$request_id, $match_id]);

            // 4. تحويل حالة الطلب إلى complete
            $stmt3 = $con->prepare("
                UPDATE requests 
                SET status = 'complete' 
                WHERE request_id = ?
            ");
            $stmt3->execute([$request_id]);

            // 5. إشعار مقدم الخدمة المقبول
            addNotification(
                $con,
                $selectedMatch['provider_user_id'],
                "تم قبول طلبك",
                "تم قبولك لتنفيذ الطلب.",
                "request_accepted",
                $request_id,
                $match_id
            );

            // 6. إشعار باقي مقدمي الخدمة
            foreach ($otherProviders as $provider) {
                addNotification(
                    $con,
                    $provider['provider_user_id'],
                    "لم يتم اختيارك لهذا الطلب",
                    "تم اختيار مقدم خدمة آخر لتنفيذ هذا الطلب.",
                    "request_rejected",
                    $request_id,
                    $provider['match_id']
                );
            }
        }

        // ======================
        // REJECT
        // ======================
        if ($type == "reject") {

            $stmt = $con->prepare("
                UPDATE matches 
                SET status = 'rejected' 
                WHERE match_id = ?
                AND request_id = ?
            ");
            $stmt->execute([$match_id, $request_id]);

            // إشعار مقدم الخدمة المرفوض
            addNotification(
                $con,
                $selectedMatch['provider_user_id'],
                "تم رفض الطلب",
                "لم يتم قبولك لتنفيذ هذا الطلب.",
                "request_rejected",
                $request_id,
                $match_id
            );
        }

        $con->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Status updated successfully"
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Exception $e) {
        if ($con->inTransaction()) {
            $con->rollBack();
        }

        echo json_encode([
            "status" => "failure",
            "message" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

else {
    echo json_encode([
        "status" => "failure",
        "message" => "Unknown action"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}