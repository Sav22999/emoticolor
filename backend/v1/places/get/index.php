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

        //global $logins_table, $users_table, $otps_table;
        global $places_table, $icons_table;

        $place_id = null;
        if (isset($get["place-id"]) && checkNumberValidity($get["place-id"])) $place_id = $get["place-id"];

        $stmt = '';
        if ($place_id != null) {
            $stmt = $c->prepare("SELECT `places`.`place-id`, `places`.`it`, `places`.`icon-id`, `icons`.`icon-url` FROM $places_table AS `places` LEFT JOIN $icons_table AS `icons` ON `places`.`icon-id` = `icons`.`icon-id` WHERE `place-id` = ?");
            $stmt->bind_param("s", $place_id);
        } else {
            $stmt = $c->prepare("SELECT `places`.`place-id`, `places`.`it`, `places`.`icon-id`, `icons`.`icon-url` FROM $places_table AS `places` LEFT JOIN $icons_table AS `icons` ON `places`.`icon-id` = `icons`.`icon-id`");
        }

        try {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $rows = array();
                while ($row = $result->fetch_assoc()) {
                    if (isset($row["place-id"]) && isset($row["it"])) {
                        if ($row["icon-id"] === null) {
                            //remove icon-id and icon-url if icon-id is null
                            unset($row["icon-id"]);
                            unset($row["icon-url"]);
                        }
                        $rows[] = $row;
                    }
                }

                responseSuccess(200, null, array_values($rows));
            } else {
                responseError(404, "No places found");
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