<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/api-functions.php");
global $localhost_db, $username_db, $password_db, $name_db;
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strpos($contentType, 'application/json') !== false) {
    $post = json_decode(file_get_contents('php://input'), true);
} else {
    $post = $_POST;
}
$get = $_GET; //GET request

$condition = true;
if ($condition) {
    $response = null;

    //Using prepared statements -> it's the safest way for MySQL queries
    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $images_table;

        $image_id = null;
        if (isset($get["image-id"]) && checkFieldValidity($get["image-id"])) $image_id = strtolower(trim($get["image-id"]));

        $offset = 0;
        if (isset($get["offset"]) && checkNumberValidity($get["offset"])) $offset = intval($get["offset"]);

        $limit = 50;
        if (isset($get["limit"]) && checkNumberValidity($get["limit"])) $limit = min(intval($get["limit"]), $limit); //cap limit to max 50
        $limit = max(1, $limit); //ensure limit is at least 1

        $stmt = '';
        if ($image_id != null) {
            $stmt = $c->prepare("SELECT `image-id`, `image-url`, `image-source` FROM $images_table WHERE `image-id` = ?  LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $image_id, $limit, $offset);
        } else {
            $stmt = $c->prepare("SELECT `image-id`, `image-url`, `image-source` FROM $images_table LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
        }
        try {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $rows = array();
                while ($row = $result->fetch_assoc()) {
                    if (isset($row["image-id"]) && isset($row["image-url"]) && isset($row["image-source"])) {
                        $rows[] = $row;
                    }
                }

                responseSuccess(200, null, array_values($rows));
            } else {
                responseError(404, "No images found.");
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt->close();

        $c->close();
    } else {
        responseError(500);
    }
} else {
    responseError(400);
}

?>